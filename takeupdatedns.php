<?php
/**
 * Created by PhpStorm.
 * User: tongyifan
 * Date: 18-2-26
 * Time: 上午12:04
 */

$appSecret = ""; // md5(base64(str))
$apiKey = "";
$file_path = "config/updatedns.txt"; // todo modify this
if (file_exists($file_path)) {
    $file_arr = file($file_path);
    $appSecret = trim($file_arr[0]);
    $apiKey = trim($file_arr[1]);
} else {
    err("Error: Key file is missing!");
}

$postArray = array();
if (!empty($_POST['appId']) && !empty($_POST['timestamp']) && !empty($_POST['type']) && !empty($_POST['content']) && !empty($_POST['sign'])) {
    $postArray = array(
        "appId" => $_POST['appId'],
        "appSecret" => $appSecret,
        "timestamp" => $_POST['timestamp'],
        "type" => $_POST['type'],
        "content" => $_POST['content']
    );
    $sign = $_POST['sign'];
} else {
    err("Error: missing parameter!");
}

if (abs($postArray['timestamp'] - time()) > 600000000) {
    err("Error: time out!");
}

foreach ($postArray as $key => $value) {
    $arr[$key] = $value;
}
ksort($arr);

if (sha1(implode($arr)) == $sign) {
    // update DNS in cloudflare
    if ($postArray['type'] == "A") {
        $identifier = ""; // todo modify this
    } else if ($postArray['type'] == "AAAA") {
        $identifier = ""; // todo modify this
    } else {
        err("Type error!");
    }

    $curl = curl_init();
    $header[0] = "X-Auth-Email: tongyfan@gmail.com";
    $header[1] = "X-Auth-Key: " . $apiKey;
    $header[2] = "Content-Type: application/json";
    curl_setopt($curl, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/{zoneKey}/dns_records/" . $identifier);  // todo modify this
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, '{"type":"' . $postArray['type'] . '","name":"{domainName}","content":"' . $postArray['content'] . '","ttl":120,"proxied":false}');  // todo modify this
    $res = curl_exec($curl);
    curl_close($curl);
    echo($res);
} else {
    err("Check signature error!");
}

function err($msg)
{
    print($msg);
    die();
}
