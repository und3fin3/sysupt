<?php
require_once('include/bittorrent_announce.php');
require_once('include/benc.php');
dbconn_announce();

// BLOCK ACCESS WITH WEB BROWSERS AND CHEATS!
block_browser();

preg_match_all('/info_hash=([^&]*)/i', $_SERVER["QUERY_STRING"], $info_hash_array);

if (count($info_hash_array[1]) < 1) {
    err("Empty info_hash is not allowed.");
} else {
    $query = "SELECT info_hash, times_completed, seeders, leechers FROM torrents WHERE " . hash_where_arr('info_hash', $info_hash_array[1]);
    $res = sql_query($query);

    if (mysql_num_rows($res) < 1) {
        err("Torrent not registered with this tracker.");
    }

    $r = "d" . benc_str("files") . "d";
    while ($row = mysql_fetch_assoc($res)) {
        $r .= "20:" . hash_pad($row["info_hash"]) . "d" .
            benc_str("complete") . "i" . $row["seeders"] . "e" .
            benc_str("downloaded") . "i" . $row["times_completed"] . "e" .
            benc_str("incomplete") . "i" . $row["leechers"] . "e" .
            "e";
    }
    $r .= "ee";

    benc_resp_raw($r);
}
