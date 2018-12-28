<?php
require_once(dirname(__FILE__) . "/../include/bittorrent.php");
dbconn();
if (!auth_token(htmlspecialchars($_GET['token']))) {
    header('HTTP/1.1 401 Unauthorized');
    die();
}
if ($username = $_GET['username']) {
    $arr = @mysql_fetch_row(@sql_query("SELECT COUNT(*) FROM users WHERE username = " . sqlesc($username))) or mysql_error();
    if ($arr[0] > 0) {
        $response = new API_Response("", 0, "Username is valid!");
    } else {
        $response = new API_Response("", -1, "Username is invalid!");
    }
} else {
    $response = new API_Response("", -999, "Missing data!");
}

echo json_encode($response);
