<?php
/**
 * Created by PhpStorm.
 * User: scaryken
 * Date: 2017/3/15
 * Time: 0:14
 */
require "include/bittorrent.php";
dbconn(true);
require_once(get_langfile_path());
loggedinorreturn(true);
stdhead('自杀');
if (!isset($_POST['suicide_option'])) {
    $form = <<<HTML
<h1>你确定删除或禁用你的账号吗？</h1>
<form action="suicide.php">
<table width="100%" border="1" cellspacing="0" cellpadding="4">
    <tbody>
    <tr>
        <td class="rowhead" width="100px">请选择：</td>
        <td class="rowfollow">
            <input type="radio" name="suicide_option" value="ban"/>我要禁用我的账户<br/>
            <input type="radio" name="suicide_option" value="delete"/>我要彻底删除我的账户<br/>
        </td>
    </tr>
    <tr>
        <td class="rowhead">请输入你的登录密码以确认操作：</td>
        <td class="rowfollow"><input type="password" name="passwd" size="5"></td>
    </tr>
    <tr>
        <td colspan="2" class="toolbox" align="center"><input type="submit" value="确认" class="btn"></td>
    </tr>
    </tbody>
</table>
</form>
HTML;
    print($form);
} else {
    $option = $_POST['suicide_option'];
    $password = $_POST['passwd'];
    if (check_password($password)) {
        switch ($option) {
            case 'ban':
                suicide();
                $message='<h1>您的账号已经被禁用</h1>';
                break;
            case 'delete':
                suicide(true);
                $message='<h1>您的账号已经被删除</h1>';
                break;
        }
    }
    logoutcookie();
    print($message);
}
stdfoot();