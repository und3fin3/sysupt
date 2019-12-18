<?php
require_once("include/bittorrent.php");
require_once("include/tjuip_helper.php");

use OurBits\Bencode;

dbconn();
global $CURUSER, $torrent_dir, $seebanned_class, $torrentnameprefix, $max_torrent_size, $iplog1,
       $multi_tracker_behaviour, $announce_urls;
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

//User may choose to download torrent from RSS. So log ip changes when downloading torrents.
if ($iplog1 == "yes") {
    if (($oldip != $CURUSER["ip"]) && $CURUSER["ip"])
        sql_query("INSERT INTO iplog (ip, userid, access) VALUES (" . sqlesc($CURUSER['ip']) . ", " . $CURUSER['id'] . ", '" . $CURUSER['last_access'] . "')");
}
//User may choose to download torrent from RSS. So update his last_access and ip when downloading torrents.
sql_query("UPDATE users SET last_access = " . sqlesc(date("Y-m-d H:i:s")) . ", ip = " . sqlesc($CURUSER['ip']) . "  WHERE id = " . sqlesc($CURUSER['id']));

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

if (strlen($CURUSER['passkey']) != 32) {
    $CURUSER['passkey'] = md5($CURUSER['username'] . date("Y-m-d H:i:s") . $CURUSER['passhash']);
    sql_query("UPDATE users SET passkey=" . sqlesc($CURUSER['passkey']) . " WHERE id=" . sqlesc($CURUSER['id']));
}

$dict = Bencode::load($fn);
$dict['announce'] = $ssl_torrent . $base_announce_url . "?passkey=" . $CURUSER['passkey'];

// add multi-tracker
if (count($announce_urls) > 1) {
    foreach ($announce_urls as $announce_url) {
        if ($multi_tracker_behaviour == 'separate') {
            /** d['announce-list'] = [ [tracker1], [backup1], [backup2] ] */
            $dict['announce-list'][] = [$ssl_torrent . $announce_url . "?passkey=" . $CURUSER['passkey']];
        } else {
            /** d['announce-list'] = [[ tracker1, tracker2, tracker3 ]] */
            $dict['announce-list'][0][] = $ssl_torrent . $announce_url . "?passkey=" . $CURUSER['passkey'];
        }
    }
}

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
print(Bencode::encode($dict));
