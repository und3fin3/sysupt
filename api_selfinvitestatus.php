<?php
require_once("include/bittorrent.php");
require_once("api/API_Response.php");
dbconn();

global $self_invite, $self_invite_checkip;

$ip = getip();
if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    $ip_version = "6";
    $iplib_ip = IPLib\Address\IPv6::fromString($ip);
} else {
    $ip_version = "4";
    $iplib_ip = IPLib\Address\IPv4::fromString($ip);
}

$loc = ip_to_location($ip);

$emails = array();
$res = sql_query("SELECT * FROM invite_rule WHERE enable = 1 AND ip_version = {$ip_version}");
while ($row = mysql_fetch_array($res)) {
    $range = IPLib\Range\Subnet::fromString($row['ip_range']);
    if ($range->contains($iplib_ip))
        $emails = array_merge($emails, explode(',', $row['email_domain']));
}

$result = array(
    'ip' => $ip,
    'ip_version' => $ip_version,
    'location' => $loc,
    'email_domain' => $emails,
    'self_invite_enable' => $self_invite == 'yes',
    'self_invite_checkip' => $self_invite_checkip == 'yes'
);

$response = new API_Response($result, 0, "Success!");
echo json_encode($response);