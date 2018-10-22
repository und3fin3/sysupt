<?php
require_once("include/bittorrent.php");
require_once("include/tjuip_helper.php");
dbconn();

set_time_limit(120);
$id = (int)$_GET["id"];
if (!$id)
    httperr();
$passkey = $_GET['passkey'];
if ($passkey) {
    $res = sql_query("SELECT * FROM users WHERE passkey='" . mysql_real_escape_string($passkey) . "' LIMIT 1");
    $user = mysql_fetch_array($res);
    if (!$user)
        die("invalid passkey");
    elseif ($user['enabled'] == 'no' || $user['parked'] == 'yes')
        die("account disabed or parked");
    $oldip = $user['ip'];
    $user['ip'] = getip();
    if (!validateIPv6($user['ip']) && $user['enablepublic4'] != 'yes' && !check_tjuip(ip2long($user['ip'])))
        die("account disable public IPv4");
    $CURUSER = $user;
} else {
    loggedinorreturn();
    parked();
    check_tjuip_or_warning($CURUSER);
    $letdown = $_GET['letdown'];
    if (!$letdown && $CURUSER['showdlnotice'] == 1) {
        header("Location: downloadnotice.php?torrentid=" . $id . "&type=firsttime");
    } elseif (!$letdown && $CURUSER['showclienterror'] == 'yes') {
        header("Location: downloadnotice.php?torrentid=" . $id . "&type=client");
    } elseif (!$letdown && $CURUSER['leechwarn'] == 'yes') {
        header("Location: downloadnotice.php?torrentid=" . $id . "&type=ratio");
    }
}
// IPv4 Tracker come back!
// pttracker4 is public network IPv4 tracker for future consideration
$announce_urls[0] = "pttrackertju.tjupt.org/announce.php";
$announce_urls[1] = "pttracker6.tjupt.org/announce.php";
$announce_urls[2] = "pttracker4.tjupt.org/announce.php";

//User may choose to download torrent from RSS. So log ip changes when downloading torrents.
if ($iplog1 == "yes") {
    if (($oldip != $CURUSER["ip"]) && $CURUSER["ip"])
        sql_query("INSERT INTO iplog (ip, userid, access) VALUES (" . sqlesc($CURUSER['ip']) . ", " . $CURUSER['id'] . ", '" . $CURUSER['last_access'] . "')");
}
//User may choose to download torrent from RSS. So update his last_access and ip when downloading torrents.
sql_query("UPDATE users SET last_access = " . sqlesc(date("Y-m-d H:i:s")) . ", ip = " . sqlesc($CURUSER['ip']) . "  WHERE id = " . sqlesc($CURUSER['id']));

/*
@ini_set('zlib.output_compression', 'Off');
@set_time_limit(0);

if (@ini_get('output_handler') == 'ob_gzhandler' AND @ob_get_length() !== false)
{	// if output_handler = ob_gzhandler, turn it off and remove the header sent by PHP
	@ob_end_clean();
	header('Content-Encoding:');
}
*/
// HTTPS Tracker Only
$ssl_torrent = "https://";
$base_announce_url = $announce_urls[0];

$res = sql_query("SELECT name, filename, save_as,  size, owner,banned FROM torrents WHERE pulling_out ='0' AND id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
$row = mysql_fetch_assoc($res);
$fn = "$torrent_dir/$id.torrent";
if ($CURUSER['downloadpos'] == "no")
    permissiondenied();
if (!$row || !is_file($fn) || !is_readable($fn))
    httperr();
if ($row['banned'] == 'yes' && get_user_class() < $seebanned_class)
    if ($row['owner'] != $CURUSER['id'])
        permissiondenied();
sql_query("UPDATE torrents SET hits = hits + 1 WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);

require_once "include/benc.php";

if (strlen($CURUSER['passkey']) != 32) {
    $CURUSER['passkey'] = md5($CURUSER['username'] . date("Y-m-d H:i:s") . $CURUSER['passhash']);
    sql_query("UPDATE users SET passkey=" . sqlesc($CURUSER[passkey]) . " WHERE id=" . sqlesc($CURUSER[id]));
}

$dict = bdec_file($fn, $max_torrent_size);
$dict['value']['announce']['value'] = $ssl_torrent . $base_announce_url . "?passkey=$CURUSER[passkey]";
$dict['value']['announce']['string'] = strlen($dict['value']['announce']['value']) . ":" . $dict['value']['announce']['value'];
$dict['value']['announce']['strlen'] = strlen($dict['value']['announce']['string']);

if (count($announce_urls) > 1) // add multi-tracker
{
    $dict['value']['announce-list']['type'] = "list";
    $dict['value']['announce-list']['string'] = "l";
    for($i = 0; $i < count($announce_urls); $i++){
        $dict['value']['announce-list']['value'][$i]['type'] = "list";
        $dict['value']['announce-list']['value'][$i]['value'][0]["type"] = "string";
        $dict['value']['announce-list']['value'][$i]['value'][0]["value"] = $ssl_torrent . $announce_urls[$i] . "?passkey=$CURUSER[passkey]";
        $dict['value']['announce-list']['value'][$i]['value'][0]["string"] = strlen($dict['value']['announce-list']['value'][$i]['value'][0]["value"]) . ":" . $dict['value']['announce-list']['value'][$i]['value'][0]["value"];
        $dict['value']['announce-list']['value'][$i]['value'][0]["strlen"] = strlen($dict['value']['announce-list']['value'][$i]['value'][0]["string"]);
        $dict['value']['announce-list']['value'][$i]['string'] = "l" . $dict['value']['announce-list']['value'][$i]['value'][0]["string"] . "e";
        $dict['value']['announce-list']['value'][$i]['strlen'] = strlen($dict['value']['announce-list']['value'][$i]['string']);
        $dict['value']['announce-list']['string'] = $dict['value']['announce-list']['string'] . $dict['value']['announce-list']['value'][$i]['string'];
    }
    $dict['value']['announce-list']['string'] = $dict['value']['announce-list']['string'] . "e";
    $dict['value']['announce-list']['strlen'] = strlen($dict['value']['announce-list']['string']);
}
/*
header ("Expires: Tue, 1 Jan 1980 00:00:00 GMT");
header ("Last-Modified: ".date("D, d M Y H:i:s"));
header ("Cache-Control: no-store, no-cache, must-revalidate");
header ("Cache-Control: post-check=0, pre-check=0", false);
header ("Pragma: no-cache");
header ("X-Powered-By: ".VERSION." (c) ".date("Y")." ".$SITENAME."");
header ("Accept-Ranges: bytes");
header ("Connection: close");
header ("Content-Transfer-Encoding: binary");
*/

header("Content-Type: application/x-bittorrent");

if (str_replace("Gecko", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=\"$torrentnameprefix." . $row["save_as"] . ".torrent\" ; charset=utf-8");
} else if (str_replace("Firefox", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=\"$torrentnameprefix." . $row["save_as"] . ".torrent\" ; charset=utf-8");
} else if (str_replace("Opera", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=\"$torrentnameprefix." . $row["save_as"] . ".torrent\" ; charset=utf-8");
} else if (str_replace("IE", "", $_SERVER['HTTP_USER_AGENT']) != $_SERVER['HTTP_USER_AGENT']) {
    header("Content-Disposition: attachment; filename=" . str_replace("+", "%20", rawurlencode("$torrentnameprefix." . $row["save_as"] . ".torrent")));
} else {
    header("Content-Disposition: attachment; filename=" . str_replace("+", "%20", rawurlencode("$torrentnameprefix." . $row["save_as"] . ".torrent")));
}

//header ("Content-Disposition: attachment; filename=".$row["filename"]."");
//ob_implicit_flush(true);
//$dict = preg_replace('/^%EF%BB%BF/', '', $dict);
print(benc($dict));
?>
