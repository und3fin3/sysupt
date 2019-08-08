<?php
require_once('include/bittorrent_announce.php');
dbconn_announce();
global $BASEURL, $seebanned_class, $REPORTMAIL, $waitsystem, $uploaderdouble_torrent, $cheaterdet_security, $nodetect_security;

// 1. BLOCK ACCESS WITH WEB BROWSERS AND CHEATS!
$agent = $_SERVER ["HTTP_USER_AGENT"];
block_browser();

// 2. GET ANNOUNCE VARIABLES
// get string type passkey, info_hash, peer_id, event, ip from client
foreach (array("passkey", "info_hash", "peer_id", "event", "key") as $x) {
    if (isset ($_GET ["$x"]))
        $GLOBALS [$x] = $_GET [$x];
}

if (!isset ($event))
    $event = "";

// get integer type port, downloaded, uploaded, left from client
foreach (array("port", "downloaded", "uploaded", "left", "compact", "no_peer_id") as $x) {
    $GLOBALS [$x] = 0 + $_GET [$x];
}

// check info_hash, peer_id and passkey
foreach (array("passkey", "info_hash", "peer_id", "port", "downloaded", "uploaded", "left") as $x) {
    if (!isset ($x))
        err("丢失关键信息: $x");
}
foreach (array("info_hash", "peer_id") as $x) {
    if (strlen($GLOBALS [$x]) != 20)
        err(" $x 的长度为 " . strlen($GLOBALS [$x]) . "( " . rawurlencode($GLOBALS [$x]) . " )");
}

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
$query_ip['ipv6'] = $_GET ['ipv6'] ?? "";
$query_ip['ipv4'] = $_GET ['ipv4'] ?? "";
$query_ip['ip'] = $_GET ['ip'] ?? "";

// handle IPv6 address
// client may send local ipv6 address to server, so we use `remote addr` first, then query param
if (validateIPv6($ip)) {
    $ipv6 = $ip;
} else if (validateIPv6($query_ip['ipv6'])) {
    $ipv6 = $query_ip['ipv6'];
} else if (validateIPv6($query_ip['ip'])) {
    $ipv6 = $query_ip['ip'];
}

// IPv4 same with IPv6
if (validateIPv4($query_ip['ipv4'])) {
    $ipv4 = $query_ip['ipv4'];
} else if (validateIPv4($query_ip['ip'])) {
    $ipv4 = $query_ip['ip'];
} else if (validateIPv4($ip)) {
    $ipv4 = $ip;
}

if (isset($ipv4)) {
    $nip = ip2long($ipv4);
    // check banned IPv4 address
    $res = sql_query("SELECT * FROM bans WHERE $nip >= first AND $nip <= last") or sqlerr(__FILE__, __LINE__);
    if (mysql_num_rows($res) > 0) err("403-该IP被封禁，请与{$REPORTMAIL}联系！");

    // warn the user who enables `public4` in campus
    if (check_tjuip($nip) && $az['enablepublic4'] == 'yes' && $az['showtjuipnotice'] == 'no')
        sql_query("UPDATE users SET showtjuipnotice = 'yes' WHERE id = " . sqlesc($az['id']));

    // drop teredo IPv6 address for campus users
    if (check_tjuip($nip) && substr($ipv6, 0, 7) == '2001:0:') unset ($ipv6);
}

// check banned IPv6 address
if (isset ($ipv6)) {
    $longipv6 = IPv6ToLong($ipv6);
    $res = sql_query("SELECT * FROM banipv6 WHERE ip0 = $longipv6[0]  AND ip1=$longipv6[1] AND ip2=$longipv6[2] AND (type='school' OR
	( ip3=$longipv6[3] AND  type='building' OR 
	( ip4=$longipv6[4] AND ip5=$longipv6[5] AND ip6=$longipv6[6] AND ip7=$longipv6[7] )))") or sqlerr(__FILE__, __LINE__);

    if (mysql_num_rows($res) > 0) err("403-该IP被封禁，请与{$REPORTMAIL}联系！");
}

// check port
if (!$port || $port > 0xffff)
    err("002-端口号错误");
if (portblacklisted($port))
    err("005-端口 " . $port . " 在黑名单里，请更换端口");

// 5. GET PEER LIST

// Number of peers that the client would like to receive from the tracker.This
// value is permitted to be zero. If omitted, typically defaults to 50 peers.

$rsize = 50;

foreach (array("numwant", "num want", "num_want") as $k) {
    if (isset ($_GET [$k])) {
        $rsize = 0 + $_GET [$k];
        break;
    }
}

// set if seeder based on left field

$seeder = ($left == 0) ? "yes" : "no";

$userid = 0 + $az ['id'];

// 3. CHECK IF CLIENT IS ALLOWED

$clicheck_res = check_client($peer_id, $agent);

// After modified, I use this clicheck_res to transmit client_familyid.
// So there is a check whether the check response is numeric or just a error message.
if (is_numeric($clicheck_res)) {
    $client_familyid = $clicheck_res;
    $clicheck_res = 0;
}

if (!$clicheck_res)
    $clicheck_res = check_aria2(getallheaders(), $peer_id, $key);

if ($clicheck_res) {
    if ($az ['showclienterror'] == 'no') {
        sql_query("UPDATE users SET showclienterror = 'yes' WHERE id = " . sqlesc($userid));
        $Cache->delete_value('user_passkey_' . $passkey . '_content');
    }

    err($clicheck_res);
} elseif ($az ['showclienterror'] == 'yes') {
    $USERUPDATESET [] = "showclienterror = 'no'";
    $Cache->delete_value('user_passkey_' . $passkey . '_content');
}

// check torrent based on info_hash

if (!$torrent = $Cache->get_value('torrent_hash_' . $info_hash . '_content')) {
    $res = sql_query("SELECT id, owner, category, sp_state, seeders, leechers, UNIX_TIMESTAMP(last_seed) AS lastseed, UNIX_TIMESTAMP(last_reseed) AS lastreseed, UNIX_TIMESTAMP(added) AS ts, banned FROM torrents WHERE pulling_out ='0' AND " . hash_where("info_hash", $info_hash));
    $torrent = mysql_fetch_array($res);
    $Cache->cache_value('torrent_hash_' . $info_hash . '_content', $torrent, 350);
}

if (!$torrent)
    err("006-种子尚未上传或者已经被删除");
elseif ($torrent ['banned'] == 'yes' && $az ['class'] < $seebanned_class) {
    if ($torrent ['owner'] != $az ['id'])
        err("007-该资源被管理员禁止下载，请与相关分类管理员联系");
}

// select peers info from peers table for this torrent
$torrentid = $torrent ["id"];

$numpeers = $torrent ["seeders"] + $torrent ["leechers"];

if ($seeder == 'yes') { // Don't report seeds to other seeders
    $only_leech_query = " AND seeder = 'no' ";
    $newnumpeers = $torrent ["leechers"];
} else {
    $only_leech_query = "";
    $newnumpeers = $numpeers;
}
if ($newnumpeers > $rsize)
    $limit = " ORDER BY RAND() LIMIT $rsize";
else
    $limit = "";

$announce_wait = 10;

// $fields = "seeder, peer_id, ipv4, ipv6, port, uploaded, downloaded, (" . TIMENOW . " - UNIX_TIMESTAMP(last_action)) AS announcetime, UNIX_TIMESTAMP(prev_action) AS prevts, connectable";
$fields = "seeder, peer_id, ipv4, ipv6, port, uploaded, downloaded, (" . TIMENOW . " - UNIX_TIMESTAMP(last_action)) AS announcetime, UNIX_TIMESTAMP(prev_action) AS prevts";
$peerlistsql = "SELECT " . $fields . " FROM peers WHERE torrent = " . $torrentid . " " . $only_leech_query . $limit;
$res = sql_query($peerlistsql);
$real_annnounce_interval = $announce_interval;

if ($anninterthreeage && ($anninterthree > $announce_wait) && (TIMENOW - $torrent ['ts']) >= ($anninterthreeage * 86400))
    $real_annnounce_interval = $anninterthree;
elseif ($annintertwoage && ($annintertwo > $announce_wait) && (TIMENOW - $torrent ['ts']) >= ($annintertwoage * 86400))
    $real_annnounce_interval = $annintertwo;

$real_annnounce_interval *= rand(1000, 1400) / 1000; // random interval avoid BOOM
$real_annnounce_interval = ceil($real_annnounce_interval);

$rep_dict = [
    "interval" => (int)$real_annnounce_interval,
    "min interval" => (int)$announce_wait,
    "complete" => (int)$torrent["seeders"],
    "incomplete" => (int)$torrent["leechers"],
    "peers" => []  // By default it is a array object, only when `&compact=1` then it should be a string
];

if ($compact == 1) {
    $rep_dict["peers"] = "";  // Change `peers` from array to string
    if (validateIPv6($ip))
        $rep_dict["peers6"] = "";   // If peer use IPv6 address , we should add packed string in `peers6`
}

unset ($self);
// bencoding the peers info get for this announce
while ($row = mysql_fetch_assoc($res)) {
    $row ["peer_id"] = hash_pad($row ["peer_id"]);

    if ($row ["peer_id"] === $peer_id) {
        $self = $row;
        continue;
    }

    if (isset($event) && $event == "stopped") {
        continue;
    }

    if ($compact == 1) {
        if (validateIPv6($row["ipv6"])) {
            $rep_dict['peers6'] .= inet_pton($row["ipv6"]) . pack("n", $row["port"]);
        }
        if (validateIPv4($row["ipv4"])) {
            if (!check_tjuip(ip2long($row['ipv4'])) && $az['enablepublic4'] == 'no') {
                continue;
            }
            $rep_dict['peers'] .= inet_pton($row["ipv4"]) . pack("n", $row["port"]);
        }
    } else {
        if (validateIPv6($row["ipv6"])) {
            $rep_dict['peers'][] = [
                'ip' => $row["ipv6"],
                'port' => $row["port"]
            ];
        }
        if (validateIPv4($row["ipv4"])) {
            if (!check_tjuip(ip2long($row['ipv4'])) && $az['enablepublic4'] == 'no') {
                continue;
            }
            $rep_dict['peers'][] = [
                'ip' => $row["ipv4"],
                'port' => $row["port"]
            ];
        }
    }
}

$selfwhere = "torrent = $torrentid AND " . hash_where("peer_id", $peer_id);

// no found in the above random selection
if (!isset ($self)) {
    $res = sql_query("SELECT $fields FROM peers WHERE $selfwhere LIMIT 1");
    $row = mysql_fetch_assoc($res);
    if ($row) {
        $self = $row;
    }
}

if (validateIPv6($ip))
    $ipv4 = $self ['ipv4'];

// min announce time
if (isset ($self) && $self ['prevts'] > (TIMENOW - $announce_wait) && $event != "stopped" && $event != "completed")
    err('008-您的刷新过于频繁，请等候 ' . $announce_wait . ' 秒再尝试');

// current peer_id, or you could say session with tracker not found in table `peers`
if (!isset ($self)) {
    $valid = @mysql_fetch_row(@sql_query("SELECT COUNT(*) FROM peers WHERE torrent=$torrentid AND userid=" . sqlesc($userid)));
    if ($valid [0] >= 1 && $seeder == 'no')
        err("009-你正在从其他地方下载相同的内容，如果这是由下载软件非正常退出造成的，请到个人信息页清除冗余种子");

    if ($valid [0] >= 3 && $seeder == 'yes')
        err("010-同一种子不能在超过三个地方保种");

    if ($az ["enabled"] == "no")
        err("011-您的帐户已经被禁用");
    elseif ($az ["parked"] == "yes")
        err("012-您的帐户已经被封存，请到控制面板中重新启用帐户。（详情请阅读“常见问题”）");
    elseif ($az ["downloadpos"] == "no")
        err("013-您的下载权限被取消(请与管理组联系)！");

    if ($az ["class"] < UC_ADMINISTRATOR) {
        $ratio = (($az ["downloaded"] > 0) ? ($az ["uploaded"] / $az ["downloaded"]) : 1);
        $gigs = $az ["downloaded"] / (1024 * 1024 * 1024);
        if ($waitsystem == "yes") {
            if ($gigs > 10) {
                $elapsed = strtotime(date("Y-m-d H:i:s")) - $torrent ["ts"];
                if ($ratio < 0.4)
                    $wait = 24;
                elseif ($ratio < 0.5)
                    $wait = 12;
                elseif ($ratio < 0.6)
                    $wait = 6;
                elseif ($ratio < 0.8)
                    $wait = 3;
                else
                    $wait = 0;

                if ($elapsed < $wait)
                    err("014-你的分享率太低，需要等待 " . mkprettytime($wait * 3600 - $elapsed) . "小时才能开始下载, 如需了解详情，请参阅 $BASEURL/faq.php#id46");
            }
        }
    }
} else { // continue an existing session
    $upthis = $trueupthis = max(0, $uploaded - $self ["uploaded"]);
    $downthis = $truedownthis = max(0, $downloaded - $self ["downloaded"]);

    if ($self ['announcetime'] > 0 && $self ['announcetime'] < 43200)
        $announcetime = ($self ["seeder"] == "yes" ? "seedtime = seedtime + $self[announcetime]" : "leechtime = leechtime + $self[announcetime]");
    else
        $announcetime = "seedtime = seedtime";

    $is_cheater = false;

    if ($cheaterdet_security) {
        if ($az ['class'] < $nodetect_security && $self ['announcetime'] > 10) {
            $is_cheater = check_cheater($userid, $az ['username'], $torrent ['id'], $upthis, $downthis, $self ['announcetime'], $self ['ipv4'] . "/" . $self ['ipv6'], $port, $torrent ['seeders'], $torrent ['leechers']);
        }
    }

    if (!$is_cheater && ($trueupthis > 0 || $truedownthis > 0)) {
        if ($az ['class'] == UC_VIP)
            $truedownthis = 0;
        $global_promotion_state = get_global_sp_state();
        if ($global_promotion_state == 1) { // Normal, see individual torrent
            if ($torrent ['sp_state'] == 3 || $torrent ['sp_state'] == 9) { // 2X
                $USERUPDATESET [] = "uploaded = uploaded + 2*$trueupthis";
                $USERUPDATESET [] = "downloaded = downloaded + $truedownthis";
            } elseif ($torrent ['sp_state'] == 4 || $torrent ['sp_state'] == 10) { // 2X
                // Free
                $USERUPDATESET [] = "uploaded = uploaded + 2*$trueupthis";
            } elseif ($torrent ['sp_state'] == 6 || $torrent ['sp_state'] == 12) { // 2X
                // 50%
                $USERUPDATESET [] = "uploaded = uploaded + 2*$trueupthis";
                $USERUPDATESET [] = "downloaded = downloaded + $truedownthis/2";
            } else {
                if ($torrent ['owner'] == $userid && $uploaderdouble_torrent > 1)
                    $upthis = $trueupthis * $uploaderdouble_torrent;

                if ($torrent ['sp_state'] == 2 || $torrent ['sp_state'] == 8) { // Free
                    // and
                    // Forever
                    // free
                    $USERUPDATESET [] = "uploaded = uploaded + $upthis";
                } elseif ($torrent ['sp_state'] == 5 || $torrent ['sp_state'] == 11) { // 50%
                    $USERUPDATESET [] = "uploaded = uploaded + $upthis";
                    $USERUPDATESET [] = "downloaded = downloaded + $truedownthis/2";
                } elseif ($torrent ['sp_state'] == 7 || $torrent ['sp_state'] == 13) { // 30%
                    $USERUPDATESET [] = "uploaded = uploaded + $upthis";
                    $USERUPDATESET [] = "downloaded = downloaded + $truedownthis*3/10";
                } elseif ($torrent ['sp_state'] == 1) { // Normal
                    $USERUPDATESET [] = "uploaded = uploaded + $upthis";
                    $USERUPDATESET [] = "downloaded = downloaded + $truedownthis";
                }
            }
        } elseif ($global_promotion_state == 2) { // Free
            if ($torrent ['owner'] == $userid && $uploaderdouble_torrent > 0)
                $upthis = $trueupthis * $uploaderdouble_torrent;

            $USERUPDATESET [] = "uploaded = uploaded + $upthis";
        } elseif ($global_promotion_state == 3) { // 2X
            if ($uploaderdouble_torrent > 2 && $torrent ['owner'] == $userid && $uploaderdouble_torrent > 0)
                $upthis = $trueupthis * $uploaderdouble_torrent;
            else
                $upthis = 2 * $trueupthis;

            $USERUPDATESET [] = "uploaded = uploaded + $upthis";
            $USERUPDATESET [] = "downloaded = downloaded + $truedownthis";
        } elseif ($global_promotion_state == 4) { // 2X Free
            if ($uploaderdouble_torrent > 2 && $torrent ['owner'] == $userid && $uploaderdouble_torrent > 0)
                $upthis = $trueupthis * $uploaderdouble_torrent;
            else
                $upthis = 2 * $trueupthis;

            $USERUPDATESET [] = "uploaded = uploaded + $upthis";
        } elseif ($global_promotion_state == 5) { // 50%
            if ($torrent ['owner'] == $userid && $uploaderdouble_torrent > 0)
                $upthis = $trueupthis * $uploaderdouble_torrent;

            $USERUPDATESET [] = "uploaded = uploaded + $upthis";
            $USERUPDATESET [] = "downloaded = downloaded + $truedownthis/2";
        } elseif ($global_promotion_state == 6) { // 2X 50%
            if ($uploaderdouble_torrent > 2 && $torrent ['owner'] == $userid && $uploaderdouble_torrent > 0)
                $upthis = $trueupthis * $uploaderdouble_torrent;
            else
                $upthis = 2 * $trueupthis;

            $USERUPDATESET [] = "uploaded = uploaded + $upthis";
            $USERUPDATESET [] = "downloaded = downloaded + $truedownthis/2";
        } elseif ($global_promotion_state == 7) { // 30%
            if ($torrent ['owner'] == $userid && $uploaderdouble_torrent > 0)
                $upthis = $trueupthis * $uploaderdouble_torrent;

            $USERUPDATESET [] = "uploaded = uploaded + $upthis";
            $USERUPDATESET [] = "downloaded = downloaded + $truedownthis*3/10";
        }
    }
}

$dt = sqlesc(date("Y-m-d H:i:s"));
$updateset = array();

// set non-type event
if (!isset ($event))
    $event = "";

if (isset ($self) && $event == "stopped") {
    sql_query("DELETE FROM peers WHERE $selfwhere") or err("删除P表失败（请向程序员汇报这个信息）");

    if (mysql_affected_rows()) {
        $updateset [] = ($self ["seeder"] == "yes" ? "seeders = seeders - 1" : "leechers = leechers - 1");
        sql_query("UPDATE snatched SET uploaded = uploaded + $trueupthis, downloaded = downloaded + $truedownthis, to_go = $left, $announcetime, last_action = " . $dt . " WHERE torrentid = $torrentid AND userid = $userid") or err("更新S表失败1（请向程序员汇报这个信息）");
    }
} elseif (isset ($self)) {
    if ($event == "completed") {
        $finished = ", finishedat = " . TIMENOW;
        $finished_snatched = ", completedat = " . $dt . ", finished  = 'yes'";
        $updateset [] = "times_completed = times_completed + 1";
    }

    sql_query("UPDATE peers SET " . ($ipv4 == "" ? "" : "ipv4 = " . sqlesc($ipv4) . ", ") . ($ipv6 == "" ? "" : "ipv6 = " . sqlesc($ipv6) . ", ") . "port = $port, uploaded = $uploaded, downloaded = $downloaded, to_go = $left, prev_action = last_action, last_action = $dt, seeder = '$seeder', agent = " . sqlesc($agent) . " $finished WHERE $selfwhere  LIMIT 1") or err("更新P表失败（请向程序员汇报这个信息）");

    if (mysql_affected_rows()) {
        if ($seeder != $self ["seeder"]) {
            $updateset [] = ($seeder == "yes" ? "seeders = seeders + 1, leechers = leechers - 1" : "seeders = seeders - 1, leechers = leechers + 1");
        }
        sql_query("UPDATE snatched SET uploaded = uploaded + $trueupthis, downloaded = downloaded + $truedownthis, to_go = $left, $announcetime, last_action = " . $dt . " $finished_snatched WHERE torrentid = $torrentid AND userid = $userid") or err("更新S表失败2（请向程序员汇报这个信息）");
    }
} else {
    if ($event != "stopped") {
        if (!mysql_fetch_assoc(sql_query("SELECT $fields FROM peers WHERE $selfwhere LIMIT 1"))) {
            // sql_query ( "INSERT INTO peers (torrent, userid, peer_id, " . ($ipv4 == "" ? "" : "ipv4, ") . ($ipv6 == "" ? "" : "ipv6, ") . "port, connectable, uploaded, downloaded, to_go, started, last_action, seeder, agent, downloadoffset, uploadoffset, passkey) VALUES ($torrentid, $userid, " . sqlesc ( $peer_id ) . ", " . ($ipv4 == "" ? "" : sqlesc ( $ipv4 ) . ", ") . ($ipv6 == "" ? "" : sqlesc ( $ipv6 ) . ", ") . "$port, '$connectable', $uploaded, $downloaded, $left, $dt, $dt, '$seeder', " . sqlesc ( $agent ) . ", $downloaded, $uploaded, " . sqlesc ( $passkey ) . ")" ) or err ( "插入P表失败（请向程序员汇报这个信息）" );
            sql_query("INSERT INTO peers (torrent, userid, peer_id, " . ($ipv4 == "" ? "" : "ipv4, ") . ($ipv6 == "" ? "" : "ipv6, ") . " port, uploaded, downloaded, to_go, started, last_action, seeder, agent, downloadoffset, uploadoffset, passkey) VALUES ($torrentid, $userid, " . sqlesc($peer_id) . ", " . ($ipv4 == "" ? "" : sqlesc($ipv4) . ", ") . ($ipv6 == "" ? "" : sqlesc($ipv6) . ", ") . "$port, $uploaded, $downloaded, $left, $dt, $dt, '$seeder', " . sqlesc($agent) . ", $downloaded, $uploaded, " . sqlesc($passkey) . ")") or err("插入P表失败（请向程序员汇报这个信息）");

            if (mysql_affected_rows()) {
                $updateset [] = ($seeder == "yes" ? "seeders = seeders + 1" : "leechers = leechers + 1");
                $check = @mysql_fetch_row(@sql_query("SELECT COUNT(*) FROM snatched WHERE torrentid = $torrentid AND userid = $userid"));

                if (!$check ['0'])
                    sql_query("INSERT INTO snatched (torrentid, userid, ip, port, uploaded, downloaded, to_go, startdat, last_action) VALUES ($torrentid, $userid, " . sqlesc($ip) . ", $port, $uploaded, $downloaded, $left, $dt, $dt)") or err("插入S表失败（请向程序员汇报这个信息）");
                else
                    sql_query("UPDATE snatched SET to_go = $left, last_action = " . $dt . " WHERE torrentid = $torrentid AND userid = $userid") or err("更新S表失败3（请向程序员汇报这个信息）");
            }
        }
    }
}

if (count($updateset)) { // Update only when there is change in peer counts
    $updateset [] = "visible = 'yes'";
    $updateset [] = "last_action = $dt";

    if ($seeder == "yes") {
        $updateset [] = "last_seed = $dt";

        if ($torrent ['lastseed'] < $torrent ['lastreseed'])
            $USERUPDATESET [] = "seedbonus = seedbonus + 90.0"; // 响应续种请求
    }

    sql_query("UPDATE torrents SET " . join(", ", $updateset) . " WHERE id = " . $torrentid) or err("更新T表失败（请向程序员汇报这个信息）");
}

if ($client_familyid != 0 && $client_familyid != $az ['clientselect'])
    $USERUPDATESET [] = "clientselect = " . sqlesc($client_familyid);

if (count($USERUPDATESET) && $userid) {
    sql_query("UPDATE users SET " . join(",", $USERUPDATESET) . " WHERE id = " . $userid) or err("更新U表失败（请向程序员汇报这个信息）");
}

/* 与下载客户端交互 */

benc_resp($rep_dict);

/* 与下载客户端交互 */

function check_tjuip($nip)
{
    global $Cache;
    $nontjuip = $Cache->get_value('nontjuip');
    if (!$nontjuip) {
        $nontjuip = array();
        $res = sql_query("SELECT * FROM nontjuip");
        while ($row = mysql_fetch_array($res)) {
            $nontjuip[] = $row;
        }
        $Cache->cache_value('nontjuip', $nontjuip, 3600);
    }
    foreach ($nontjuip as $row) {
        if ($nip >= $row['first'] && $nip <= $row['last']) {
            return FALSE;
        }
    }
    return TRUE;
}
