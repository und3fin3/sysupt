<?php
/**
 * Created by PhpStorm.
 * User: zcqian
 * Date: 15/9/16
 * Time: 上午10:18
 */

require_once("bittorrent.php");

function assert_tjuip()
{
    $ip = getip();
    $nip = ip2long($ip);
    if ($nip) {

        if (!check_tjuip($nip)) {
            stdhead("没有权限");
            stdmsg("没有访问权限", "<div align=\"center\">你正在使用校外IP地址访问本站，不允许浏览本页面，请尝试通过IPv6网络访问<hr><font size='3' color='red'>扫码调戏北洋媛(●'◡'●)</font><br><img src='images/qrcode-wx.png' width='30%'/><img src='images/qrcode-qq.png' width='30%'/><img src='images/qrcode-weibo.png' width='30%'/></div>");
            stdfoot();
            exit;
        }
    }
}

function check_tjuip_or_warning($CURUSER)
{
    $ip = getip();
    $nip = ip2long($ip);
    if ($nip) {

        if (($CURUSER['enablepublic4'] != 'yes') && !check_tjuip($nip)) {
            stdhead("没有权限");
            stdmsg("没有访问权限", "<div align=\"center\">你正在使用校外IP地址访问本站，如果想要在校外使用本站，请参考<a class='faqlink' href='/forums.php?action=viewtopic&topicid=15457'>北洋园PT校外IPv4下载设置步骤</a>进行设置<hr><font size='3' color='red'>扫码调戏北洋媛(●'◡'●)</font><br><img src='images/qrcode-wx.png' width='30%'/><img src='images/qrcode-qq.png' width='30%'/><img src='images/qrcode-weibo.png' width='30%'/></div>");
            stdfoot();
            exit;
        }
    }
}