<?php
require_once("include/bittorrent.php");
require_once("api/API_Response.php");
dbconn();

global $self_invite, $self_invite_checkip, $SITENAME, $SITEEMAIL, $BASEURL, $REPORTMAIL, $invite_timeout;

$ip = getip();
$data = json_decode(@file_get_contents("php://input"), true);
$email = safe_email($data['email']);
$type = $data['type'];
$username = $data['username'];
$get_code = $_GET['code'];

$revive_bonus = 3000;
$add_bonus = 7000;
$domains = array(
    "edu.cn",
    "ac.cn"

);

if (!$get_code) {
    if ($self_invite !== 'yes') {
        echo json_encode(new API_Response(null, -1, "自助邀请系统未开放！"));
        die();
    } else {
        // 检查邮箱
        if (!preg_match("/^[\w'.%+-]+@(?:[a-zA-Z0-9-]+\.)+(?:edu\.cn|ac\.cn)$/", $email)) {
            echo json_encode(new API_Response(null, -2, "邮箱格式不正确！"));
            die();
        }
        if (EmailBanned($email)) {
            echo json_encode(new API_Response(null, -3, "该邮箱域已被禁用！"));
            die();
        }
        $res = sql_query("SELECT * FROM self_invite WHERE email = " . sqlesc($email) . " LIMIT 1");
        $a = mysql_fetch_array($res);
        if ($a !== null && $a['used'] == 1) {
            echo json_encode(new API_Response(null, -4, "此邮箱已经被使用！"));
            die();
        }

        if ($type != 'invite') {
            $res = sql_query("SELECT * FROM users WHERE username = " . sqlesc($username));
            $row = mysql_fetch_array($res);
            if ($row === null) {
                echo json_encode(new API_Response(null, -6, "用户名不存在！"));
                die();
            } else {
                $uid = $row['id'];
                if ($row['enabled'] == 'no' && $type == 'addbonus') {
                    echo json_encode(new API_Response(null, -7, "该账户已被禁用，你需要解禁后才能为其增加魔力值！"));
                    die();
                } elseif ($row['enabled'] == 'yes' && $type == 'revive') {
                    echo json_encode(new API_Response(null, -8, "该账户未被禁用！"));
                    die();
                } elseif ($row['enabled'] == 'no' && $row['downloadpos'] == 'no' && $type == 'revive') {
                    echo json_encode(new API_Response(null, -9, "该账户因为违规被禁用，请联系管理员！"));
                    die();
                }
            }
        }

        if ($a !== null) {
            if ($type == $a['used_type']) {
                if ($type == 'invite') {
                    $code = $a['code'];
                    sql_query("UPDATE self_invite SET ip = " . sqlesc($ip) . " WHERE email = " . sqlesc($email));
                } elseif ($a['bonus_uid'] == $uid) {
                    $code = $a['code'];
                    sql_query("UPDATE self_invite SET ip = " . sqlesc($ip) . " WHERE email = " . sqlesc($email));
                } else {
                    $code = md5(mt_rand(1, 10000) . $ip . TIMENOW . $email);
                    sql_query("UPDATE self_invite SET ip = " . sqlesc($ip) . ", code = " . sqlesc($code) . ", bonus_uid = " . sqlesc($uid) . " WHERE email = " . sqlesc($email));
                }
            } else {
                sql_query("DELETE FROM self_invite WHERE email = " . sqlesc($email));
                $code = md5(mt_rand(1, 10000) . $ip . TIMENOW . $email);
                sql_query("INSERT INTO self_invite (email, used_type, code, bonus_uid, ip) VALUES ( " . sqlesc($email) . " , " . sqlesc($type) . " , " . sqlesc($code) . " , " . sqlesc($uid) . " , " . sqlesc($ip) . " )");
            }
        } else {
            $code = md5(mt_rand(1, 10000) . $ip . TIMENOW . $email);
            sql_query("INSERT INTO self_invite (email, used_type, code, bonus_uid, ip) VALUES ( " . sqlesc($email) . " , " . sqlesc($type) . " , " . sqlesc($code) . " , " . sqlesc($uid) . " , " . sqlesc($ip) . " )");
        }

        $title = $SITENAME . "自助邀请确认邮件";
        $message = <<<EOD
您好，<br /><br />
您正在使用{$SITENAME}的自助邀请系统。<br />
如果这是您主动申请的，请在阅读网站规则后确认本申请。<br /><br />
请点击以下链接确认申请：<br />
<b><a href="https://$BASEURL/signup/self_invite_confirm?code=$code" target="_blank">https://$BASEURL/signup/self_invite_confirm?code=$code</a></b><br /><br />
本申请由IP为 {$ip} 的用户主动申请。如果这不是您本人所为，请将此邮件转发至{$REPORTMAIL}(在主题里标明“垃圾邮件举报”)<br /><br />
------<br />
{$SITENAME}管理组
<br />
EOD;

        sent_mail($email, $SITENAME, $SITEEMAIL, change_email_encode(get_langfolder_cookie(), $title), change_email_encode(get_langfolder_cookie(), $message), "invitesignup", false, false, '', get_email_encode(get_langfolder_cookie()));
        echo json_encode(new API_Response(null, 0, "确认邮件发送成功，邮箱可能存在延迟，请耐心等待。若长时间未收到请重新申请~"));
    }
} else {
    $res = sql_query("SELECT * FROM self_invite WHERE code = " . sqlesc($get_code) . " LIMIT 1");
    $arr = mysql_fetch_array($res);
    if ($arr === null) {
        echo json_encode(new API_Response(null, -2, "错误的确认代码，请重新申请或联系管理员！"));
    } else {
        if ($arr['used'] == 1) {
            if ($arr['used_type'] == 'invite') {
                $invite_row = mysql_fetch_row(@sql_query("SELECT COUNT(*) FROM invites WHERE hash = " . sqlesc($arr['invite_code'])));
                if ($invite_row[0]) {
                    echo json_encode(new API_Response(['invite_code' => $arr['invite_code']], 0, "自助邀请状态已确认，3秒后跳转至注册页"));
                } else {
                    echo json_encode(new API_Response(null, -1, "邀请已使用或已过期，请选择其他途径注册"));
                }
            } else {
                echo json_encode(new API_Response(null, 0, "此次申请已被确认，请检查是否已收到奖励"));
            }
        } else {
            switch ($arr['used_type']) {
                case 'invite':
                    $invitecode = md5(mt_rand(1, 10000) . $_SERVER['REMOTE_ADDR'] . TIMENOW . $arr['email']);
                    $remark = '自助邀请';
                    sql_query("UPDATE self_invite SET used = 1, invite_code = " . sqlesc($invitecode) . " WHERE code = " . sqlesc($arr['code']));
                    sql_query("INSERT INTO invites (inviter, invitee, hash, remark, ipcheck) VALUES (0, " . sqlesc($arr['email']) . " , " . sqlesc($invitecode) . " , " . sqlesc($remark) . " , " . sqlesc($self_invite_checkip == 'yes' ? 1 : 0) . " ) ");
                    echo json_encode(new API_Response(['invite_code' => $invitecode], 0, "自助邀请状态已确认，3秒后跳转至注册页"));
                    break;
                case 'revive':
                    sql_query("UPDATE self_invite SET used = 1 WHERE code = " . sqlesc($arr['code']));
                    $r = sql_query("SELECT * FROM users WHERE id = " . sqlesc($arr['bonus_uid']));
                    $a = mysql_fetch_array($r);
                    $modcomment = $a['modcomment'];
                    $modcomment = date("Y-m-d") . " - enabled by " . $arr['email'] . ".\n" . htmlspecialchars($modcomment);
                    $bonuscomment = $a['bonuscomment'];
                    $bonuscomment = date("Y-m-d") . " + $revive_bonus Points added by " . $arr['email'] . ".\n" . htmlspecialchars($bonuscomment);
                    sql_query("UPDATE users SET enabled = 'yes', class = 1, leechwarn='no', seedbonus = seedbonus + $revive_bonus, modcomment = " . sqlesc($modcomment) . " , bonuscomment = " . sqlesc($bonuscomment) . " WHERE id = " . sqlesc($arr['bonus_uid'])) or sqlerr(__FILE__, __LINE__);
                    echo json_encode(new API_Response(null, 1, "帐号已解禁并增加 $revive_bonus 魔力值，3秒后跳转至登录页"));
                    break;
                case 'addbonus':
                    sql_query("UPDATE self_invite SET used = 1 WHERE code = " . sqlesc($arr['code']));
                    $r = sql_query("SELECT * FROM users WHERE id = " . sqlesc($arr['bonus_uid']));
                    $a = mysql_fetch_array($r);
                    $bonuscomment = $a['bonuscomment'];
                    $bonuscomment = date("Y-m-d") . " + $add_bonus Points added by " . $arr['email'] . ".\n" . htmlspecialchars($bonuscomment);
                    sql_query("UPDATE users SET seedbonus = seedbonus +$add_bonus, bonuscomment = " . sqlesc($bonuscomment) . " WHERE id = " . sqlesc($arr['bonus_uid'])) or sqlerr(__FILE__, __LINE__);
                    echo json_encode(new API_Response(null, 1, "帐号已增加 $add_bonus 魔力值，3秒后跳转至登录页"));
                    break;
                default:
                    echo json_encode(new API_Response(null, -999, "系统错误，请联系管理员！"));
            }
        }
    }
}

