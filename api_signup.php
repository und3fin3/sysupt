<?php
require_once("include/bittorrent.php");
require_once("api/API_Response.php");
dbconn();
require_once(get_langfile_path("takesignup.php", true));
require_once(get_langfile_path("takesignup.php", false, get_langfolder_cookie()));
global $lang_takesignup, $BASEURL, $SITENAME, $SITEEMAIL, $smtptype, $showschool, $registration, $registration_checkip,
       $self_invite;

$ip = getip();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(@file_get_contents("php://input"), true);
    $email = safe_email($data['email']);
    $username = $data['username'];
    $password = $data['password'];
    $school = 0 + $data['school'];
    $country = 0 + $data['country'];
    $gender = $data['gender'];
    $message = $data['message'];
    $type = $data['type'];

    // ------检查基础注册信息------
    if (empty($email) || empty($username) || empty($password) || empty($country) || empty($gender)) {
        echo json_encode(new API_Response(null, -2, "注册信息不完整"));
        die();
    }
    if (mb_strlen($username, 'UTF-8') > 12) {
        echo json_encode(new API_Response(null, -3, "用户名超过12字符"));
        die();
    }
    $res_check_user = sql_query("SELECT * FROM users WHERE username = " . sqlesc($username));

    if (mysql_num_rows($res_check_user) > 0) {
        echo json_encode(new API_Response(null, -4, "用户名已被占用，请修改"));
        die();
    }

    if (strlen($password) < 6 || strlen($password) > 40) {
        echo json_encode(new API_Response(null, -5, "密码长度应为6~40"));
        die();
    }
    if (!validemail($email)) {
        echo json_encode(new API_Response(null, -6, "邮箱格式有误"));
        die();
    }
    if (EmailBanned($email)) {
        echo json_encode(new API_Response(null, -6, "该邮箱域已被禁用！"));
        die();
    }
    $res = sql_query("SELECT COUNT(*) FROM users WHERE email = " . sqlesc($email));
    $a = mysql_fetch_row($res)[0];
    if ($a != 0) {
        echo json_encode(new API_Response(null, -6, "此邮箱已经被使用！"));
        die();
    }
    if (!validusername($username)) {
        echo json_encode(new API_Response(null, -7, "用户名包含敏感词，请修改"));
        die();
    }
    $allowed_genders = array("Male", "Female", "male", "female");
    if (!in_array($gender, $allowed_genders, true)) {
        echo json_encode(new API_Response(null, -8, "性别选择有误"));
        die();
    }

    if ($showschool == 'yes') {
        if (empty($school)) {
            echo json_encode(new API_Response(null, -9, "未选择学校信息，请重新检查"));
            die();
        }
        $res = sql_query("SELECT COUNT(*) FROM schools WHERE id = " . sqlesc($school));
        if (mysql_fetch_row($res)[0] == 0) {
            echo json_encode(new API_Response(null, -10, "学校信息有误，请重新检查"));
            die();
        }
    }

    $res = sql_query("SELECT COUNT(*) FROM countries WHERE id = " . sqlesc($country));
    if (mysql_fetch_row($res)[0] == 0) {
        echo json_encode(new API_Response(null, -11, "国家信息有误，请重新检查"));
        die();
    }
    // ------检查基础信息完整完毕------

    if ($type == 'invite') {
        $code = $data['code'];
        $res = sql_query("SELECT * FROM invites WHERE hash = '" . mysql_real_escape_string($code) . "' LIMIT 1") or sqlerr(__FILE__, __LINE__);
        $inv = mysql_fetch_array($res);
        if (!$inv) {
            echo json_encode(new API_Response(null, -1, "邀请码错误、已被注册或已过期！"));
            die();
        }
        sql_query("DELETE FROM invites WHERE hash = '" . mysql_real_escape_string($code) . "' LIMIT 1");
        sql_query("DELETE FROM temp_invite WHERE invite_code = '" . mysql_real_escape_string($code) . "' LIMIT 1");
    }

    global $registration_checkip;
    $need_ipcheck = ($type == 'normal' && $registration_checkip == 'yes') || ($type == 'invite' && $inv['ipcheck'] == 1);
    if ($need_ipcheck) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip_version = "6";
            $iplib_ip = IPLib\Address\IPv6::fromString($ip);
        } else {
            $ip_version = "4";
            $iplib_ip = IPLib\Address\IPv4::fromString($ip);
        }
        $check_result = false;
        if ($inv['inviter'] == 0) {
            $email_domain = strtolower(explode('@', $email)[1]);
            $invite_email_domain = strtolower(explode('@', $inv['invitee'])[1]);
            $res = sql_query("SELECT * FROM invite_rule WHERE enable = 1 AND ip_version = {$ip_version} AND (FIND_IN_SET(" . sqlesc($invite_email_domain) . ", email_domain) OR FIND_IN_SET(" . sqlesc($email_domain) . ", email_domain))");
        } else {
            $res = sql_query("SELECT * FROM invite_rule WHERE enable = 1 AND ip_version = {$ip_version}");
        }
        while ($rule = mysql_fetch_array($res)) {
            $range = IPLib\Range\Subnet::fromString($rule['ip_range']);
            if ($range->contains($iplib_ip)) {
                $check_result = true;
                break;
            }
        }
    } else $check_result = true;

    $secret = mksecret();
    $passhash = md5($secret . $password . $secret);
    global $verification;
    $editsecret = ($verification == 'admin' ? '' : $secret);
    $invite_count = (int)$invite_count;

    $username = sqlesc($username);
    $passhash = sqlesc($passhash);
    $psecret = md5($secret);
    $secret = sqlesc($secret);
    $editsecret = sqlesc($editsecret);
    $send_email = $email;
    $email = sqlesc($email);
    $country = sqlesc($country);
    $gender = sqlesc($gender);
    $sitelangid = sqlesc(get_langid_from_langcookie());
    $modcomment = sqlesc(date("Y-m-d") . " - 受邀原因：" . $inv['remark'] . "；邀请邮箱：" . $inv['invitee'] . "。");

    $status = sqlesc($check_result ? 'pending' : 'needverify');

    global $defaultclass_class, $defcss, $deflang, $iniupload_main;
    sql_query("INSERT INTO users (username, passhash, secret, editsecret, email, country, gender, status, class, invites, invited_by, added, last_access, ip, lang, stylesheet" . ($showschool == 'yes' ? ", school" : "") . ", uploaded, modcomment) VALUES (" . $username . "," . $passhash . "," . $secret . "," . $editsecret . "," . $email . "," . $country . "," . $gender . ", " . $status . ", " . $defaultclass_class . "," . $invite_count . ", " . ($type == 'invite' ? sqlesc($inv['inviter']) : "0") . ", '" . date("Y-m-d H:i:s") . "' , " . " '" . date("Y-m-d H:i:s") . "' , " . " '" . getip() . "' , " . $sitelangid . "," . $defcss . ($showschool == 'yes' ? "," . $school : "") . "," . ($iniupload_main > 0 ? $iniupload_main : 0) . ", " . $modcomment . " )") or sqlerr(__FILE__, __LINE__);
    $id = mysql_insert_id();

    sql_query("INSERT INTO iplog (ip, userid, access) VALUES(" . sqlesc($ip) . " , " . sqlesc($id) . " , " . sqlesc(date("Y-m-d H:i:s")) . " )") or sqlerr(__FILE__, __LINE__);

    $dt = sqlesc(date("Y-m-d H:i:s"));
    $subject = sqlesc($lang_takesignup['msg_subject'] . $SITENAME . "!");
    $msg = sqlesc($lang_takesignup['msg_congratulations'] . htmlspecialchars($wantusername) . $lang_takesignup['msg_you_are_a_member']);
    sql_query("INSERT INTO messages (sender, receiver, subject, added, msg) VALUES(0, $id, $subject, $dt, $msg)") or sqlerr(__FILE__, __LINE__);

    $usern = htmlspecialchars($username);
    $title = $SITENAME . $lang_takesignup['mail_title'];
    $body = <<<EOD
{$lang_takesignup['mail_one']}$usern{$lang_takesignup['mail_two']}($email){$lang_takesignup['mail_three']}$ip{$lang_takesignup['mail_four']}
<b><a href="https://$BASEURL/confirm.php?id=$id&secret=$psecret" target="_blank">
{$lang_takesignup['mail_this_link']} </a></b><br />
https://$BASEURL/confirm.php?id=$id&secret=$psecret
{$lang_takesignup['mail_four_1']}
<b><a href="https://$BASEURL/confirm_resend.php" target="_blank">{$lang_takesignup['mail_here']}</a></b><br />
https://$BASEURL/confirm_resend.php
<br />
{$lang_takesignup['mail_five']}
EOD;

    if ($type == 'invite') {
        $dt = sqlesc(date("Y-m-d H:i:s"));
        $subject = sqlesc($lang_takesignup_target[get_user_lang($inv['inviter'])]['msg_invited_user_has_registered']);
        $msg = sqlesc($lang_takesignup_target[get_user_lang($inv['inviter'])]['msg_user_you_invited'] . $usern . $lang_takesignup_target[get_user_lang($inv['inviter'])]['msg_has_registered']);
        if ($inv['inviter']) {
            sql_query("INSERT INTO messages (sender, receiver, subject, added, msg) VALUES(0, {$inv['inviter']}, $subject, $dt, $msg)") or sqlerr(__FILE__, __LINE__);
        }
        $Cache->delete_value('user_' . $inv['inviter'] . '_unread_message_count');
        $Cache->delete_value('user_' . $inv['inviter'] . '_inbox_count');
    }

    if ($check_result) {
        if ($verification == 'admin') {
            if ($type == 'invite')
                $goto = "ok.php?type=inviter";
            else
                $goto = "ok.php?type=adminactivate";
        } elseif ($verification == 'automatic' || $smtptype == 'none') {
            $goto = "confirm.php?id=$id&secret=$psecret";
        } else {
            sent_mail($send_email, $SITENAME, $SITEEMAIL, change_email_encode(get_langfolder_cookie(), $title), change_email_encode(get_langfolder_cookie(), $body), "signup", false, false, '', get_email_encode(get_langfolder_cookie()));
            $goto = "ok.php?type=signup&email=" . rawurlencode($send_email);
        }
    } else {
        sql_query("INSERT INTO needverify (uid, message) VALUES (" . sqlesc($id) . ", " . sqlesc($message) . ")");
        $Cache->delete_value('staff_needverify_count');
        $goto = "ok.php?type=needverify";
    }

    echo json_encode(new API_Response($goto, 1, "OK"));
    die();
} else {
    $countries = array();
    $res = sql_query("SELECT id, name FROM countries");
    while ($row = mysql_fetch_array($res)) {
        $countries[$row['id']] = $row['name'];
    }

    $schools = array();
    if ($showschool == 'yes') {
        $res = sql_query("SELECT id, name FROM schools");
        while ($row = mysql_fetch_array($res)) {
            $schools[$row['id']] = $row['name'];
        }
    }

    $code = $_GET['code'];
    $res_inv = sql_query("SELECT * FROM invites WHERE hash = '" . mysql_real_escape_string($code) . "' LIMIT 1") or sqlerr(__FILE__, __LINE__);
    $inv = mysql_fetch_array($res_inv);
    if (isset($code) && !$inv) {
        echo json_encode(new API_Response(null, -1, "邀请码错误、已被注册或已过期！"));
    } elseif (!isset($code)) {
        if ($registration != 'yes') {
            if ($self_invite != 'yes') {
                echo json_encode(new API_Response(null, -2, "暂未开放注册！"));
            } else {
                echo json_encode(new API_Response(null, -3, "暂未开放注册！点击确定将跳转至自助邀请系统。"));
            }
        } else {
            $check_result = true;
            if ($registration_checkip == 'yes') {
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ip_version = "6";
                    $iplib_ip = IPLib\Address\IPv6::fromString($ip);
                } else {
                    $ip_version = "4";
                    $iplib_ip = IPLib\Address\IPv4::fromString($ip);
                }
                $check_result = false;
                $res = sql_query("SELECT * FROM invite_rule WHERE enable = 1 AND ip_version = {$ip_version} ");
                while ($rule = mysql_fetch_array($res)) {
                    $range = IPLib\Range\Subnet::fromString($rule['ip_range']);
                    if ($range->contains($iplib_ip)) {
                        $check_result = true;
                        break;
                    }
                }
            }

            echo json_encode(new API_Response([
                'ipcheck' => $registration_checkip,
                'check_result' => $check_result,
                'email' => "",
                'ip' => $ip,
                'location' => ip_to_location($ip),
                'countries' => $countries,
                'schools' => $schools
            ], 0, "OK"));
        }
    } else {
        $check_result = true;
        if ($inv['ipcheck'] == 1) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ip_version = "6";
                $iplib_ip = IPLib\Address\IPv6::fromString($ip);
            } else {
                $ip_version = "4";
                $iplib_ip = IPLib\Address\IPv4::fromString($ip);
            }
            $check_result = false;
            if ($inv['inviter'] == 0) {
                // 自助邀请
                $email_domain = explode('@', $inv['invitee']);
                $res = sql_query("SELECT * FROM invite_rule WHERE enable = 1 AND ip_version = {$ip_version} AND FIND_IN_SET(" . sqlesc($email_domain) . " , email_domain)");
            } else {
                $res = sql_query("SELECT * FROM invite_rule WHERE enable = 1 AND ip_version = {$ip_version}");
            }
            while ($rule = mysql_fetch_array($res)) {
                $range = IPLib\Range\Subnet::fromString($rule['ip_range']);
                if ($range->contains($iplib_ip)) {
                    $check_result = true;
                    break;
                }
            }
        }

        echo json_encode(new API_Response([
            'ipcheck' => $inv['ipcheck'],
            'check_result' => $check_result,
            'email' => $inv["invitee"],
            'ip' => $ip,
            'location' => ip_to_location($ip),
            'countries' => $countries,
            'schools' => $schools
        ], 0, "OK"));
    }
}