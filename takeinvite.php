<?php
require_once("include/bittorrent.php");
dbconn();
require_once(get_langfile_path());
global $sendinvite_class, $CURUSER, $BASEURL, $invite_timeout, $SITEEMAIL, $SITENAME, $permanent_invite_checkip, $temporary_invite_checkip, $permanent_invite, $temporary_invite;
registration_check('invitesystem', true, false);
if (get_user_class() < $sendinvite_class)
    stderr($lang_takeinvite['std_error'], $lang_takeinvite['std_invite_denied']);
function bark($msg)
{
    global $lang_takeinvite;
    stdhead();
    stdmsg($lang_takeinvite['head_invitation_failed'], $msg);
    stdfoot();
    exit;
}

$id = $CURUSER['id'];
$email = htmlspecialchars(trim($_POST["email"]));
$email = safe_email($email);
$invite_id = 0 + $_POST['invite_type'];

if (!$email)
    bark($lang_takeinvite['std_must_enter_email']);
if (!check_email($email))
    bark($lang_takeinvite['std_invalid_email_address']);
if (EmailBanned($email))
    bark($lang_takeinvite['std_email_address_banned']);

if (!EmailAllowed($email))
    bark($lang_takeinvite['std_wrong_email_address_domains'] . allowedemails());

$body = str_replace("<br />", "<br />", nl2br(trim(strip_tags($_POST["body"]))));
if (!$body)
    bark($lang_takeinvite['std_must_enter_personal_message']);


// check if email addy is already in use
$a = (@mysql_fetch_row(@sql_query("select count(*) from users where email=" . sqlesc($email)))) or die(mysql_error());
if ($a[0] != 0)
    bark($lang_takeinvite['std_email_address'] . htmlspecialchars($email) . $lang_takeinvite['std_is_in_use']);
$b = (@mysql_fetch_row(@sql_query("select count(*) from invites where invitee=" . sqlesc($email)))) or die(mysql_error());
if ($b[0] != 0)
    bark($lang_takeinvite['std_invitation_already_sent_to'] . htmlspecialchars($email) . $lang_takeinvite['std_await_user_registeration']);

if ($invite_id == 0) {
    stderr($lang_takeinvite['std_error'], $lang_takeinvite['head_invitation_failed']);
} else if ($invite_id == -1) {
    if ($CURUSER['invites'] <= 0)
        stderr($lang_takeinvite['std_error'], $lang_takeinvite['std_no_invite']);

    if ($permanent_invite != 'yes') {
        stderr($lang_takeinvite['std_error'], "暂不允许发出永久邀请。");
    }
    $remark = "永久邀请";
    $ipcheck = $permanent_invite_checkip == 'yes' ? 1 : 0;
    sql_query("UPDATE users SET invites = invites - 1 WHERE id = " . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
} else {
    $res = sql_query("SELECT COUNT(*) FROM temp_invite WHERE id = " . sqlesc($invite_id) . " AND uid = " . sqlesc($CURUSER['id']) . " AND expired > NOW() ");
    if (mysql_fetch_row($res)[0] == 0)
        stderr($lang_takeinvite['std_error'], "你的账户中无指定ID的邀请码");
    if ($temporary_invite != 'yes') {
        stderr($lang_takeinvite['std_error'], "暂不允许发出永久邀请。");
    }
    $remark = "临时邀请";
    $ipcheck = $temporary_invite_checkip == 'yes' ? 1 : 0;
    sql_query("DELETE FROM temp_invite WHERE id = " . sqlesc($invite_id)) or sqlerr(__FILE__, __LINE__);
}

$hash = md5(mt_rand(1, 10000) . $CURUSER['username'] . TIMENOW . $CURUSER['passhash']);

sql_query("INSERT INTO invites (inviter, invitee, hash, time_invited, remark, ipcheck) VALUES ( " . sqlesc($CURUSER['id']) . " , " . sqlesc($email) . " , " . sqlesc($hash) . " , " . sqlesc(date("Y-m-d H:i:s")) . " , " . sqlesc($remark) . " , " . sqlesc($ipcheck) . " )");

$title = $SITENAME . $lang_takeinvite['mail_tilte'];

$message = <<<EOD
{$lang_takeinvite['mail_one']}{$CURUSER['username']}{$lang_takeinvite['mail_two']}
<b><a href="https://$BASEURL/signup/signup?code=$hash" target="_blank">{$lang_takeinvite['mail_here']}</a></b><br />
https://$BASEURL/signup/signup?code=$hash
<br />{$lang_takeinvite['mail_three']}$invite_timeout{$lang_takeinvite['mail_four']}{$CURUSER['username']}{$lang_takeinvite['mail_five']}<br />
$body
<br /><br />{$lang_takeinvite['mail_six']}
EOD;

sent_mail($email, $SITENAME, $SITEEMAIL, change_email_encode(get_langfolder_cookie(), $title), change_email_encode(get_langfolder_cookie(), $message), "invitesignup", false, false, '', get_email_encode(get_langfolder_cookie()));

header("Refresh: 0; url=invite.php?id=" . htmlspecialchars($id) . "&sent=1");
