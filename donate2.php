<?php
/**
 * Created by PhpStorm.
 * User: tongyifan
 * Date: 6/12/18
 * Time: 4:42 PM
 */
require "include/bittorrent.php";
dbconn();
loggedinorreturn();
require_once(get_langfile_path());
global $donation_enabled;
if ($donation_enabled != 'yes')
    stderr($lang_donate['std_sorry'], $lang_donate['std_do_not_accept_donation']);
stdhead($lang_donate['head_donation']);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!is_numeric($amount) || $amount < 1 || is_null($amount)) {
        stderr($lang_donate['std_error'], "捐赠金额不是合规的数字！", $head = false);
    }
    $amount = round((float)$_POST['amount'], 2);
    $message = $_POST['message'];
    $anonymous = $_POST['anonymous'] == 'yes' ? 'yes' : 'no';
    $nickname = $anonymous == 'yes' ? $_POST['nickname'] : "";
    $params = [
        'qr_name' => '捐赠北洋媛',
        'qr_price' => $amount*100,
        'qr_type' => 'QR_TYPE_DYNAMIC',
    ];
    $qr = youzan_request('youzan.pay.qrcode.create', $params);
    if (!isset($qr['qr_id']) || !isset($qr['qr_code'])) {
        stderr($lang_donate['std_error'], "生成二维码错误，如果这个情况一直发生，请报告管理员！", $head = false);
    }
    sql_query("INSERT INTO donation (uid, amount, message, anonymous, nickname, qrid, create_time) VALUES(" . $CURUSER['id'] . ", " . sqlesc($amount) . ", " . sqlesc($message) . ", " . sqlesc($anonymous) . ", " . sqlesc($nickname) . ", " . sqlesc($qr['qr_id']) . ", " . sqlesc(date("Y-m-d H:i:s")) . ")") or sqlerr(__FILE__, __LINE__);
    begin_main_frame();
    print("<h2>" . $lang_donate['text_donate'] . "</h2>");
    print("<table width=100%><tr><td colspan=2 class=text align=center>");
    print("<img id='qrcode' src='" . $qr['qr_code'] . "' width='25%'><br/><br/><font size='3'>" .
        $lang_donate['text_scan'] . "</font></td></tr>");
    print("<tr><td colspan=2 class=text align=center><input type='button' class='btn' onclick='location.href=(\"donate2.php?action=thanks\")' value='" . $lang_donate['btn_paid'] . "' /></td></tr></table>");
    end_main_frame();
} else if ($_GET['action'] == 'thanks'){
    stdmsg("完成", $lang_donate['text_thanks']);
} else {
    ?>
    <script type='text/javascript'>
        function amount_change() {
            if (document.getElementById('amount_select').value === '0') {
                document.getElementById('amount_custom').value = '';
                document.getElementById('amount_custom').disabled = false;
            } else {
                document.getElementById('amount_custom').value = '选择”其它金额“后可用';
                document.getElementById('amount_custom').disabled = true;
            }
        }

        function anonymous_change() {
            if (document.getElementById('anonymous').checked === true) {
                document.getElementById('nickname').value = '';
                document.getElementById('nickname').disabled = false;
            } else {
                document.getElementById('nickname').value = '仅限匿名用户填写';
                document.getElementById('nickname').disabled = true;
            }
        }
    </script>
    <?php
    begin_main_frame();
    print("<h2>" . $lang_donate['text_donate'] . "</h2>");
    print("<table width=100%><tr>");
    print("<td colspan=2 class=text align=left>" . $lang_donate['text_donate_explain_note'] . "</td></tr>");
    print("<tr><td colspan=2 class=text align=center>");
    print("<form action='donate2.php' method='post'><table class='main' border='1' cellspacing='0' cellpadding='10'>");
    tr($lang_donate['text_select_donation_amount'], "<select id='amount_select' name='amount' onchange='amount_change();'>" .
        "<option value='' selected>" . $lang_donate['select_choose_donation_amount'] . "</option>" .
        "<option value='10'>10" . $lang_donate['text_donation'] . "</option>" .
        "<option value='20'>20" . $lang_donate['text_donation'] . "</option>" .
        "<option value='50'>50" . $lang_donate['text_donation'] . "</option>" .
        "<option value='66'>66" . $lang_donate['text_donation'] . "</option>" .
        "<option value='88'>88" . $lang_donate['text_donation'] . "</option>" .
        "<option value='100'>100" . $lang_donate['text_donation'] . "</option>" .
        "<option value='200'>200" . $lang_donate['text_donation'] . "</option>" .
        "<option value='0'>" . $lang_donate['select_other_donation_amount'] . "</option></select>&nbsp;&nbsp;" .
        "<input id='amount_custom' name='amount' disabled='disabled' value='" . $lang_donate['text_select_other_will_enable'] . "'>", 1);
    tr($lang_donate['text_donate_message'], "<textarea name='message' rows='5' cols='50'></textarea>" . "<br/>" . $lang_donate['text_donate_message_note'], 1);
    tr($lang_donate['text_anonymous'], "<input type='checkbox' id='anonymous' name='anonymous' value='yes' onchange='anonymous_change();'>" . $lang_donate['text_anonymous_note'], 1);
    tr($lang_donate['text_nickname'], "<input type='text' id='nickname' name='nickname' value='" . $lang_donate['text_nickname_only'] . "' disabled='disabled'><br/>" . $lang_donate['text_nickname_note'], 1);
    print("<tr><td class='toolbox' colspan='2' align='center'>
        <input type='submit' class='btn' value='" . $lang_donate['btn_next'] . "'>&nbsp;
        <input type='reset' class='btn' value='" . $lang_donate['btn_reset'] . "'></td></tr>");
    print("</td></tr></table>");
    end_main_frame();
}
stdfoot();