<?php
/**
 * Created by PhpStorm.
 * User: tongyifan
 * Date: 19-2-22
 * Time: 上午12:37
 */
require_once("include/bittorrent.php");
dbconn();
require_once(get_langfile_path());
loggedinorreturn();
global $lang_disabletjuipnotice, $CURUSER;

$text = $_POST['text'];
$enable = $_POST['enable'];


if (isset($text)) {
    if (trim($text) == $lang_disabletjuipnotice['text']) {
        sql_query("UPDATE users SET showtjuipnotice = 'never' WHERE id = " . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
        stderr($lang_disabletjuipnotice['text_success'], $lang_disabletjuipnotice['text_you_have_disabled_notice']);
    } else {
        stderr($lang_disabletjuipnotice['text_error'], $lang_disabletjuipnotice['text_input_error']);
    }
} elseif (isset($enable)) {
    sql_query("UPDATE users SET showtjuipnotice = 'no' WHERE id = " . sqlesc($CURUSER['id'])) or sqlerr(__FILE__, __LINE__);
    stderr($lang_disabletjuipnotice['text_success'], $lang_disabletjuipnotice['text_enable_notice']);
} else {
    if ($CURUSER['showtjuipnotice'] == 'never') {
        stdhead($lang_disabletjuipnotice['head_enable']);
        stdmsg($lang_disabletjuipnotice['head_enable'], "<form method='post' action='{$_SERVER['PHP_SELF']}'><input type='hidden' name='enable' value='yes'><input type='submit' value='{$lang_disabletjuipnotice['head_enable']}'></form>");
        stdfoot();
    } else {
        stdhead($lang_disabletjuipnotice['head_disable']);
        stdmsg($lang_disabletjuipnotice['head_disable'], "<form method='post' action='{$_SERVER['PHP_SELF']}'>{$lang_disabletjuipnotice['text_input_below']}<br><font color='red'>{$lang_disabletjuipnotice['text']}</font><br><input type='text' name='text' style='width: 400px'/><input type='submit'></form>");
        stdfoot();
    }
}