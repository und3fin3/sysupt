<?php
/**
 * Created by PhpStorm.
 * User: tongyifan
 * Date: 6/17/18
 * Time: 12:06 AM
 */
global $youzan_client_id, $youzan_client_secret;
$request = $GLOBALS['HTTP_RAW_POST_DATA'];
echo json_encode(['code' => 0, 'msg' => 'success']);
file_put_contents('/var/log/tjupt/pay.log', $request . PHP_EOL, FILE_APPEND);
$request = json_decode($request, true);
if (empty($request) || !isset($request['kdt_id']) || !isset($request['id']) || !isset($request['status']) || !isset($request['sign'])) {
    // hack
    write_log(getip() . "试图侵入捐赠订单确认系统！如果次数过多请管理员注意封禁此ip！");
    return;
}
if ($request['sign'] != md5($youzan_client_id . $request['msg'] . $youzan_client_secret) || $youzan_client_id != $request['client_id']) {
    // hack
    write_log(getip() . "试图侵入捐赠订单确认系统！如果次数过多请管理员注意封禁此ip！");
    return;
}
if ($request['test'] == true) {
    // youzan's test
    return;
}
$msg = json_decode(urldecode($request['msg']));
if ($msg['status'] != 'TRADE_SUCCESS' || $msg['status'] != 'TRADE_CLOSED') {
    return;
}
$tid = $msg['tid'];
$params = [
    'tid' => $tid,
    'with_childs' => false,
];
$trade = youzan_request('youzan.trade.get', $params);
/* status
    0 => SUCCESS
    -1 => CREATED(DEFAULT)
    -2 => CLOSED
*/
if ($trade['status'] == 'TRADE_SUCCESS') {
    sql_query("UPDATE donation SET status = 0, success_time = " . sqlesc($trade['pay_time']) . "WHERE qrid = " . sqlesc($trade['qr_id']) . "AND amount = " . sqlesc($trade['price']) . "AND status = -1") or sqlerr(__FILE__, __LINE__);
} elseif ($trade['status'] == 'TRADE_CLOSED') {
    sql_query("UPDATE donation SET status = -2 WHERE qrid = " . sqlesc($trade['qr_id']) . "AND amount = " . sqlesc($trade['price']) . "AND status = -1") or sqlerr(__FILE__, __LINE__);
}
