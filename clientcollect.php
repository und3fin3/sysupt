<?php
require_once('include/bittorrent.php');
require_once('include/functions_announce.php');

use OurBits\Bencode;

dbconn();
global $BASEURL, $CURUSER;
$agent = $_SERVER ["HTTP_USER_AGENT"];
if ($_GET['download'] == 1) {
    $fn = "attachments/clientcollect.torrent";
    if (!is_file($fn) || !is_readable($fn)) {
        http_response_code(404);
        die();
    }
    $dict = Bencode::load($fn);
    $dict['announce'] = get_protocol_prefix() . $BASEURL . "/clientcollect.php?passkey=" . $CURUSER['passkey'];
    header("Content-Type: application/x-bittorrent");

    if (str_replace("Gecko", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
        header("Content-Disposition: attachment; filename=\"clientcollect.torrent\" ; charset=utf-8");
    } else if (str_replace("Firefox", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
        header("Content-Disposition: attachment; filename=\"clientcollect.torrent\" ; charset=utf-8");
    } else if (str_replace("Opera", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
        header("Content-Disposition: attachment; filename=\"clientcollect.torrent\" ; charset=utf-8");
    } else if (str_replace("IE", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
        header("Content-Disposition: attachment; filename=" . str_replace("+", "%20", rawurlencode("clientcollect.torrent")));
    } else {
        header("Content-Disposition: attachment; filename=" . str_replace("+", "%20", rawurlencode("clientcollect.torrent")));
    }

    print(Bencode::encode($dict));
} else {
    // block_browser();
    foreach (array("passkey", "info_hash", "peer_id", "event", "key") as $x) {
        if (isset ($_GET ["$x"]))
            $GLOBALS [$x] = $_GET [$x];
    }
// get integer type port, downloaded, uploaded, left from client
    foreach (array("port", "compact", "no_peer_id") as $x) {
        $GLOBALS [$x] = 0 + $_GET [$x];
    }
// check info_hash, peer_id and passkey
    foreach (array("passkey", "info_hash", "peer_id", "port") as $x)
        if (!isset ($$x))
            err("丢失数据: $x");
    foreach (array("info_hash", "peer_id") as $x)
        if (strlen($GLOBALS [$x]) != 20)
            err("非法的 $x (" . strlen($GLOBALS [$x]) . " - " . rawurlencode($GLOBALS [$x]) . ")");

// check passkey
    if (strlen($passkey) != 32)
        err("001-错误的passkey( $passkey )! 请从" . $BASEURL . "重新下载torrent文件");

    if (!$az = $Cache->get_value('user_passkey_' . $passkey . '_content')) {
        $res = sql_query("SELECT id, username, downloadpos, enabled, uploaded, downloaded, class, parked, clientselect, showclienterror, enablepublic4, showtjuipnotice FROM users WHERE passkey='" . mysql_real_escape_string($passkey) . "' LIMIT 1");
        $az = mysql_fetch_array($res);
        $Cache->cache_value('user_passkey_' . $passkey . '_content', $az, 950);
    }

    if (!$az)
        err("001-错误的passkey( $passkey )! 请从" . $BASEURL . "重新下载torrent文件");

// 4. GET IP AND CHECK PORT
    $ip = getip(); // avoid to get the spoof ip from some agent
    if (!$port || $port > 0xffff)
        err("端口号错误");
// 3. CHECK IF CLIENT IS ALLOWED
    $clicheck_res = check_client($peer_id, $agent);
    $aria2 = check_aria2(getallheaders(), $peer_id, $key);
    if (is_numeric($clicheck_res)) {
        err("你所使用的客户端是允许使用的客户端");
    }

    if ($aria2) {
        err("请勿提交Aria2伪造的客户端！");
    }

    $dt = sqlesc(date("Y-m-d H:i:s"));

    if ($event == "started") {
        $msg = "提交未知客户端。信息如下:
agent: " . $agent . "
peer_id: " . $peer_id;
        $subject = "提交客户端";
        $msg = sqlesc($msg);
        $subject = sqlesc($subject);

        if ($ip != $Cache->get_value('clientcollect_' . md5($ip) . '_msg')) {
            sql_query("INSERT INTO staffmessages (sender, added, msg, subject) VALUES({$az['id']}, $dt, $msg, $subject)") or err(SL1);

            $Cache->delete_value('staff_message_count');
            $Cache->delete_value('staff_new_message_count');
            $Cache->cache_value('clientcollect_' . md5($ip) . '_msg', $ip, 86400);

            $rep_dict = [
                "interval" => 86400,
                "min interval" => 86400,
                "complete" => 0,
                "incomplete" => 0,
                "peers" => []
            ];
            benc_resp($rep_dict);
        } else {
            err("您已经提交过该客户端，管理组将根据分析情况决定是否对该客户端添加支持。");
        }
    }
}