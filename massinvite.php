<?php
/**
 * Created by PhpStorm.
 * User: tongyifan
 * Date: 5/23/18
 * Time: 4:40 PM
 */
require "include/bittorrent.php";
dbconn();
loggedinorreturn();
if (get_user_class() < UC_ADMINISTRATOR)
    stderr("权限不足", "仅允许SysOP及以上用户组使用批量邀请");
$id = $CURUSER[id];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $addr = explode(PHP_EOL, $_POST['addr']);
    $event = str_replace("<br />", "<br />", nl2br(trim(strip_tags($_POST["event"]))));
    if (!$event) {
        stderr("发送失败", "请添加活动名称", false);
        exit;
    }
    stdhead("批量邀请");
    print("<table width=500 border=1 cellspacing=0 cellpadding=5><tr><td class='colhead'>邮箱</td><td class='colhead'>状态</td></tr>");
    foreach ($addr as $a) {
        $err = checkemail($a);
        if ($err == "") {
            $ret = sql_query("SELECT username FROM users WHERE id = " . sqlesc($id)) or sqlerr();
            $arr = mysql_fetch_assoc($ret);
            $hash = md5(mt_rand(1, 10000) . $CURUSER['username'] . TIMENOW . $CURUSER['passhash']);
            sql_query("INSERT INTO invites (inviter, invitee, hash, time_invited) VALUES ('" . mysql_real_escape_string($id) . "', '" . mysql_real_escape_string($a) . "', '" . mysql_real_escape_string($hash) . "', " . sqlesc(date("Y-m-d H:i:s")) . ")");
            $title = "北洋园PT网站邀请函";
            $message = "你好，<br /><br />恭喜你从【".$event."】活动获得一个".$SITENAME."邀请资格<br />如果你有意加入，请在阅读网站规则后确认该邀请。<br /><br />请点击以下链接确认邀请：".
                "<b><a href=\"https://$BASEURL/signup.php?type=invite&invitenumber=$hash\" target=\"_blank\">点击这里</a></b><br />https://$BASEURL/signup.php?type=invite&invitenumber=$hash<br />".
                "请在".$invite_timeout."天内确认该邀请，之后邀请将作废。<br />".$SITENAME."真诚欢迎你加入我们的社区！<br /><br />".
                "如果你没有参加过此次活动，请将此邮件转发至".$REPORTMAIL."<br /><br />------<br />".$SITENAME."-https://tjupt.org";
            sent_mail($a,$SITENAME,$SITEEMAIL,change_email_encode(get_langfolder_cookie(), $title),change_email_encode(get_langfolder_cookie(),$message),"invitesignup",false,false,'',get_email_encode(get_langfolder_cookie()));
            print("<tr><td class='colfollow'>$a</td><td class='colfollow'><font color='green'>发送成功</font></td>");
        }else{
            print("<tr><td class='colfollow'>$a</td><td class='colfollow'><font color='red'>$err</font></td>");
        }
    }
    print("</table>");
    stdfoot();
} else {
    stdhead("批量邀请");
    print("<table width=400 class=main border=0 cellspacing=0 cellpadding=0><tr><td class=embedded>");
    print("<h1 align=center><a href=\"massinvite.php\">批量邀请</a></h1>");
    print("<form method=\"post\" action=\"massinvite.php\" onsubmit=\"return confirm('确定为共' + $('#addr').val().split('\\n').length + '名用户发送邀请吗?')\">" .
        "<table border=1 width=300 cellspacing=0 cellpadding=5>" .
        "<tr><td class=\"rowhead nowrap\" valign=\"top\" align=\"right\">" . "邮箱地址" . "</td><td align=left><textarea name=addr id=addr cols=40 rows=20></textarea><br /><font align=left class=small>每行一个邮箱地址</font>" . ($restrictemaildomain == 'yes' ? "<br />" . $lang_invite['text_email_restriction_note'] . allowedemails() : "") . "</td></tr>" .
        "<tr><td class=\"rowhead nowrap\" valign=\"top\" align=\"right\">" . "活动名称" . "</td><td align=left><input type='text' name='event'></td></tr>" .
        "<tr><td align=center colspan=2><input type=submit value='发送'></td></tr>" .
        "</form></table></td></tr></table>");
    stdfoot();
}

function checkemail($email)
{
    $email = safe_email(unesc(htmlspecialchars(trim($email))));
    $err = "";
    if (!$email)
        $err .= "邮箱地址为空";
    if (!check_email($email))
        $err .= "无效的邮箱地址";
    if (EmailBanned($email))
        $err .= "邮箱地址被禁用";
    if (!EmailAllowed($email))
        $err .= "邮箱域不被允许";
    $a = (@mysql_fetch_row(@sql_query("select count(*) from users where email=" . sqlesc($email)))) or die(mysql_error());
    if ($a[0] != 0)
        $err .= "此邮箱已被注册";
    $b = (@mysql_fetch_row(@sql_query("select count(*) from invites where invitee=" . sqlesc($email)))) or die(mysql_error());
    if ($b[0] != 0)
        $err .= "此邮箱已被发送过邀请";
    return $err;

}