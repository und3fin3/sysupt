<?php
require_once('include/bittorrent_announce.php');
dbconn_announce();

// BLOCK ACCESS WITH WEB BROWSERS AND CHEATS!
block_browser();

preg_match_all('/info_hash=([^&]*)/i', $_SERVER["QUERY_STRING"], $info_hash_array);

// Check passkey
$passkey = $_GET['passkey'];
if (strlen($passkey) != 32) {
    err("Passkey invalid.");
} elseif (!$az = $Cache->get_value('user_passkey_' . $passkey . '_content')) {
    $res = sql_query("SELECT id, username, downloadpos, enabled, uploaded, downloaded, class, parked, clientselect, showclienterror, enablepublic4 FROM users WHERE passkey='" . mysql_real_escape_string($passkey) . "' LIMIT 1");
    $az = mysql_fetch_array($res);
    $Cache->cache_value('user_passkey_' . $passkey . '_content', $az, 950);
}

if (count($info_hash_array[1]) < 1) {
    err("Empty info_hash is not allowed.");
} elseif (!$az) {
    err("Passkey invalid.");
} else {
    $query = "SELECT info_hash, times_completed, seeders, leechers FROM torrents WHERE " . hash_where_arr('info_hash', $info_hash_array[1]);
    $res = sql_query($query);

    if (mysql_num_rows($res) < 1) {
        err("Torrent not registered with this tracker.");
    }

    $torrent_details = [];
    while ($row = mysql_fetch_assoc($res)) {
        $torrent_details[$row["info_hash"]] = [
            'complete' => (int)$row["seeders"],
            'downloaded' => (int)$row["times_completed"],
            'incomplete' => (int)$row["leechers"]
        ];
    }
    $d = ['files' => $torrent_details];

    benc_resp($d);
}
