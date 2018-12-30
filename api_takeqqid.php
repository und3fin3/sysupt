<?php
/**
 * Created by PhpStorm.
 * 这个API记录加QQ群的记录，以期将用户名与QQ号对应起来，来解决共享帐号或一人多号作弊
 * User: tongyifan
 * Date: 18-12-29
 * Time: 上午12:14
 */

require_once("include/bittorrent.php");
require_once("api/API_Response.php");
dbconn();
$token = htmlspecialchars($_POST['token']);
$sign = htmlspecialchars($_POST['sign']);
$qq = htmlspecialchars($_POST['qq']);
$username = htmlspecialchars($_POST['username']);
if (!auth_token($token, $sign, $username . $qq)) {
    header('HTTP/1.1 401 Unauthorized');
    die();
}
$dupe_users = array();
$res = sql_query("SELECT username FROM users WHERE qq = " . sqlesc($qq) . " OR email LIKE '%$qq%' OR username LIKE '%$qq%'") or sqlerr(__FILE__, __LINE__);
while ($arr = mysql_fetch_array($res)) {
    if ($arr['username'] != $username)
        $dupe_users[] = $arr['username'];
}

if (!empty($dupe_users)) {
    $resp = new API_Response($dupe_users, -1, "Found duplicated user.");
} else
    $resp = new API_Response("", 0, "Not found duplicated user.");

sql_query("UPDATE users SET qq = " . sqlesc($qq) . " WHERE username = " . sqlesc($username)) or sqlerr(__FILE__, __LINE__);
echo json_encode($resp);