<?php
require_once("include/bittorrent.php");
require_once("api/API_Response.php");
dbconn();
$token = htmlspecialchars($_GET['token']);
$sign = htmlspecialchars($_GET['sign']);
$type = htmlspecialchars($_GET['type']);
$username = htmlspecialchars($_GET['username']);
$qq = htmlspecialchars($_GET['qq']);
$id = htmlspecialchars($_GET['id']);
$passkey = htmlspecialchars($_GET['passkey']);

if (!auth_token($token, $sign, $type . $username . $qq . $id . $passkey)) {
    header('HTTP/1.1 401 Unauthorized');
    die();
}

switch ($type) {
    case "get_username_validation":
        {
            $arr = @mysql_fetch_row(@sql_query("SELECT COUNT(*) FROM users WHERE username = " . sqlesc($username))) or mysql_error();
            if ($arr[0] > 0) {
                $response = new API_Response("", 0, "Username is valid!");
            } else {
                $response = new API_Response("", -1, "Username is invalid!");
            }
            break;
        }
    case "get_username_by_qq":
        {
            $arr = @mysql_fetch_array(@sql_query("SELECT username FROM users WHERE qq = " . sqlesc($qq))) or mysql_error();
            if ($arr['username']) {
                $response = new API_Response($arr['username'], 0, "Found matched user.");
            } else {
                $response = new API_Response("", -1, "Not found matched user.");
            }
            break;
        }
    case "verify_id_passkey":
        {
            $arr = @mysql_fetch_array(@sql_query("SELECT COUNT(*) FROM users WHERE id = " . sqlesc($id) . " AND passkey = " . sqlesc($passkey))) or mysql_error();
            if ($arr[0] > 0) {
                $response = new API_Response("", 0, "Passkey is valid!");
            } else {
                $response = new API_Response("", -1, "Passkey is invalid!");
            }
            break;
        }
    default:
        $response = new API_Response("", -999, "Missing data!");
}
echo json_encode($response);