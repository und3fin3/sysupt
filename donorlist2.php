<?php
/**
 * Created by PhpStorm.
 * User: tongyifan
 * Date: 7/7/18
 * Time: 10:17 AM
 */
require "include/bittorrent.php";
require_once(get_langfile_path());
dbconn();
loggedinorreturn();

global $Cache;

$action = isset ($_POST ['action']) ? htmlspecialchars($_POST ['action']) : (isset ($_GET ['action']) ? htmlspecialchars($_GET ['action']) : '');

$allowed_actions = array(
    "donor",
    "star",
    "top10",
    "export",
);
$need_cache = array(
    "star",
    "top10",
);
if (!$action) {
    $action = "donor";
}
if (!in_array($action, $allowed_actions))
    stderr($lang_donorls ['std_error'], $lang_donorls ['std_invalid_action']);
else {
    function donormenu($selected = "request", $title = "")
    {
        stdhead($title);
        begin_main_frame();
        print ("<div id=\"lognav\"><ul id=\"logmenu\" class=\"menu\">");
        print ("<li" . ($selected == "donor" ? " class=selected" : "") . "><a href=\"?action=donor\">捐赠列表</a></li>");
        print ("<li" . ($selected == "star" ? " class=selected" : "") . "><a href=\"?action=star\">小黄星列表</a></li>");
        print ("<li" . ($selected == "top10" ? " class=selected" : "") . "><a href=\"?action=top10\">排行榜</a></li>");
        if (get_user_class() > UC_MODERATOR) {
            print ("<li" . ($selected == "export" ? " class=selected" : "") . "><a href=\"?action=export\">&nbsp;导&nbsp;&nbsp;出&nbsp;</a></li>");
        }
        print ("</ul></div>");
    }
    
    switch ($action) {
        case 'donor':
            donormenu('donor', $lang_donorls['head_donor']);
            break;
        case 'star':
            donormenu('star', $lang_donorls['head_star']);
            break;
        case 'top10':
            donormenu('top10', $lang_donorls['head_top10']);
            break;
        case 'export':
            donormenu('export', $lang_donorls['head_export']);
            break;
        default:
    }
    if (in_array($action, $need_cache)) {
        $cachename = "donorlist_" . $action;
        $cachename .= isset($_GET['page']) ? "_page_" . htmlspecialchars($_GET['page']) : "";
        $cachename .= isset($_GET['type']) ? "_type_" . htmlspecialchars($_GET['type']) : "";
        $cachename .= isset($_GET['limit']) ? "_limit_" . (0 + $_GET['limit']) : "";
        $cachetime = 15 * 60; // 60 minutes
        $Cache->new_page($cachename, $cachetime, true);
        if (!$Cache->get_page()) {
            $Cache->add_whole_row();
            switch ($action) {
                case 'star':
                    {
                        $res = sql_query("SELECT COUNT(*) FROM users WHERE donor = 'yes'") or sqlerr(__FILE__, __LINE__);
                        $row = mysql_fetch_array($res);
                        $count = $row[0];
                        $perpage = 20;
                        list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, "donorlist2.php?action=star&");
                        begin_frame($lang_donorls['head_star'], true);
                        begin_table();
                        echo $pagertop;
                        ?>
                        <tr>
                            <td class="colhead" align="center"><?php echo $lang_donorls['col_id'] ?></td>
                            <td class="colhead" align="center"><?php echo $lang_donorls['col_donor'] ?></td>
                            <td class="colhead" align="center"><?php echo $lang_donorls['col_total'] ?></td>
                        </tr>
                        <?php
                        $res = sql_query("SELECT donation.uid, SUM(donation.amount) total FROM donation INNER JOIN (SELECT users.id FROM users WHERE donor = 'yes') AS u ON donation.uid = u.id AND donation.status = 0 GROUP BY donation.uid ORDER BY donation.uid DESC $limit") or sqlerr(__FILE__, __LINE__);
                        while ($arr = mysql_fetch_assoc($res)) {
                            echo "<tr>";
                            echo "<td class='rowfollow' align=\"center\">" . $arr['uid'] . "</td>";
                            echo "<td class='rowfollow' align=\"center\">" . get_username($arr['uid']) . "</td>";
                            echo "<td class='rowfollow' align=\"center\">" . $arr['total'] . "</td>";
                            echo "</tr>";
                        }
                        end_table();
                        end_frame();
                        end_main_frame();
                        break;
                    }
                case 'top10':
                    {
                        $allowed_types = array(
                            "total",
                            "single",
                            "times",
                        );
                        $type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : "";
                        $limit = isset($_GET["limit"]) ? 0 + $_GET["limit"] : false;
                        if (!$limit || $limit > 250 || !in_array($type, $allowed_types)) {
                            $limit = 10;
                        }
                        if ($limit == 10 || $type == "total") {
                            begin_frame($lang_donorls['text_top_total_donation'] . $limit . "<font class='small'>" . $lang_donorls['text_top_total_donation_note'] . "</font>" . ($limit == 10 ? " <font class=\"small\"> - [<a class=\"altlink\" href=\"donorlist2.php?action=top10&amp;type=total&amp;limit=100\">Top 100</a>] - [<a class=\"altlink\" href=\"donorlist2.php?action=top10&amp;type=total&amp;limit=250\">Top 250</a>]</font>" : ""), true);
                            begin_table();
                            ?>
                            <tr>
                                <td class="colhead" align="center"><?php echo $lang_donorls['col_rank'] ?></td>
                                <td class="colhead" align="center"><?php echo $lang_donorls['col_donor'] ?></td>
                                <td class="colhead" align="center"><?php echo $lang_donorls['col_total'] ?></td>
                            </tr>
                            <?php
                            $res = sql_query("SELECT uid, SUM(amount) total FROM donation WHERE status = 0 GROUP BY uid ORDER BY total DESC, success_time DESC LIMIT " . $limit) or sqlerr(__FILE__, __LINE__);
                            $i = 1;
                            while ($row = mysql_fetch_assoc($res)) {
                                ?>
                                <tr>
                                    <td class="rowfollow" align="center"><?php echo $i ?></td>
                                    <td class="rowfollow" align="center"><?php echo get_username($row['uid']) ?></td>
                                    <td class="rowfollow" align="center"><?php echo $row['total'] ?></td>
                                </tr>
                                <?php
                                $i++;
                            }
                            end_table();
                            end_frame();
                        }
                        if ($limit == 10 || $type == "single") {
                            begin_frame($lang_donorls['text_top_single_donation'] . $limit . "<font class='small'>" . $lang_donorls['text_top_single_donation_note'] . "</font>" . ($limit == 10 ? " <font class=\"small\"> - [<a class=\"altlink\" href=\"donorlist2.php?action=top10&amp;type=single&amp;limit=100\">Top 100</a>] - [<a class=\"altlink\" href=\"donorlist2.php?action=top10&amp;type=single&amp;limit=250\">Top 250</a>]</font>" : ""), true);
                            begin_table();
                            ?>
                            <tr>
                                <td class="colhead" align="center"><?php echo $lang_donorls['col_rank'] ?></td>
                                <td class="colhead" align="center"><?php echo $lang_donorls['col_donor'] ?></td>
                                <td class="colhead" align="center"><?php echo $lang_donorls['col_amount'] ?></td>
                            </tr>
                            <?php
                            $res = sql_query("SELECT uid, amount FROM donation WHERE status = 0 ORDER BY amount DESC, success_time DESC LIMIT " . $limit) or sqlerr(__FILE__, __LINE__);
                            $i = 1;
                            while ($row = mysql_fetch_assoc($res)) {
                                ?>
                                <tr>
                                    <td class="rowfollow" align="center"><?php echo $i ?></td>
                                    <td class="rowfollow" align="center"><?php echo get_username($row['uid']) ?></td>
                                    <td class="rowfollow" align="center"><?php echo $row['amount'] ?></td>
                                </tr>
                                <?php
                                $i++;
                            }
                            end_table();
                            end_frame();
                        }
                        if ($limit == 10 || $type == "times") {
                            begin_frame($lang_donorls['text_top_times_donation'] . $limit . "<font class='small'>" . $lang_donorls['text_top_times_donation_note'] . "</font>" . ($limit == 10 ? " <font class=\"small\"> - [<a class=\"altlink\" href=\"donorlist2.php?action=top10&amp;type=times&amp;limit=100\">Top 100</a>] - [<a class=\"altlink\" href=\"donorlist2.php?action=top10&amp;type=times&amp;limit=250\">Top 250</a>]</font>" : ""), true);
                            begin_table();
                            ?>
                            <tr>
                                <td class="colhead" align="center"><?php echo $lang_donorls['col_rank'] ?></td>
                                <td class="colhead" align="center"><?php echo $lang_donorls['col_donor'] ?></td>
                                <td class="colhead" align="center"><?php echo $lang_donorls['col_times'] ?></td>
                            </tr>
                            <?php
                            $res = sql_query("SELECT uid, COUNT(*) times FROM donation WHERE status = 0 GROUP BY uid ORDER BY times DESC, success_time DESC LIMIT " . $limit) or sqlerr(__FILE__, __LINE__);
                            $i = 1;
                            while ($row = mysql_fetch_assoc($res)) {
                                ?>
                                <tr>
                                    <td class="rowfollow" align="center"><?php echo $i ?></td>
                                    <td class="rowfollow" align="center"><?php echo get_username($row['uid']) ?></td>
                                    <td class="rowfollow" align="center"><?php echo $row['times'] ?></td>
                                </tr>
                                <?php
                                $i++;
                            }
                            end_table();
                            end_frame();
                        }
                        end_main_frame();
                    }
            }
            print("<p><font class=\"small\">" . $lang_donorls['text_this_page_last_updated'] . date('Y-m-d H:i:s') . ", " . $lang_donorls['text_started_recording_date'] . $lang_donorls['text_start_date'] . $lang_donorls['text_update_interval'] . "</font></p>");
            $Cache->end_whole_row();
            $Cache->cache_page();
        }
        echo $Cache->next_row();
    } else {
        switch ($action) {
            case 'donor':
                {
                    $res = sql_query("SELECT COUNT(*) FROM donation WHERE status = 0") or sqlerr(__FILE__, __LINE__);
                    $row = mysql_fetch_array($res);
                    $count = $row[0];
                    $perpage = 50;
                    list($pagertop, $pagerbottom, $limit) = pager($perpage, $count, "donorlist2.php?");
                    begin_frame($lang_donorls['head_donor'], true);
                    begin_table();
                    echo $pagertop;
                    ?>
                    <tr>
                        <td class="colhead" align="center"><?php echo $lang_donorls['col_time'] ?></td>
                        <td class="colhead" align="center"><?php echo $lang_donorls['col_donor'] ?></td>
                        <td class="colhead" align="center"><?php echo $lang_donorls['col_message'] ?></td>
                        <td class="colhead" align="center"><?php echo $lang_donorls['col_amount'] ?></td>
                    </tr>
                    <?php
                    $res = sql_query("SELECT id, uid, amount, message, anonymous, nickname, success_time FROM donation WHERE status = 0 AND used = 1 ORDER BY success_time DESC $limit") or sqlerr(__FILE__, __LINE__);
                    while ($arr = mysql_fetch_assoc($res)) {
                        echo "<tr>";
                        echo "<td class='rowfollow' align=\"center\" nowrap>" . $arr['success_time'] . "</td>";
                        echo "<td class='rowfollow' align=\"center\">";
                        echo $arr['anonymous'] == 'yes' ? "<i>" . ($arr['nickname'] != '' ? $arr['nickname'] : "（匿名）") . "</i>" . (get_user_class() > UC_MODERATOR ? "<br>(" . get_username($arr['uid']) . ")" : "") : get_username($arr['uid']);
                        echo "</td>";
                        echo "<td class='rowfollow' align=\"center\" width='40%'>" . $arr['message'] . "</td>";
                        echo "<td class='rowfollow' align=\"center\">" . $arr['amount'] . "</td>";
                        echo "</tr>";
                    }
                    end_table();
                    end_frame();
                    end_main_frame();
                    break;
                }
            case 'export':
                {
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        if (get_user_class() < UC_ADMINISTRATOR) {
                            stderr($lang_donorls['std_error'], $lang_donorls['std_permission_denied'], true, false);
                        }
                        $i_all = $total_all = 0;
                        if ($_FILES['csv']) {
                            $file = $_FILES['csv'];
                            if ($file['error'] > 0)
                                stderr($lang_donorls['std_error'], $_FILES['csv']['error'], true, false);
                            // if (!in_array($file['type'], ['text/csv', 'text/comma-separated-values']))
                            if (strtolower(substr(strrchr($file['name'], '.'), 1)) != 'csv')
                                stderr($lang_donorls['std_error'], $lang_donorls['std_invalid_file_type'] . $file['type'], true, false);
                            $handle = fopen($file['tmp_name'], 'r');
                            if ($handle === FALSE)
                                stderr($lang_donorls['std_error'], $lang_donorls['std_invalid_csvfile'], true, false);
                            $i = $total = 0;
                            $alipay_table = "\n[b][size=3][color=Blue]支付宝[/color][/size][/b]";
                            $alipay_table .= "\n[table][tr][td]捐赠时间[/td][td]交易对方[/td][td]商品名称[/td][td]金额（元）[/td][/tr]";
                            while ($row = fgetcsv($handle, 100000, ",")) {
                                if (gbk2utf8($row[10]) == "收入") {
                                    $alipay_table .= "[tr][td]" . gbk2utf8($row[3]) . "[/td][td]" . gbk2utf8($row[7]) . "[/td][td]" . gbk2utf8($row[8]) . "[/td][td]" . gbk2utf8($row[9]) . "[/td][/tr]";
                                    $i++;
                                    $total += (float)gbk2utf8($row[9]);
                                }
                            }
                            fclose($handle);
                            $alipay_table .= "[/table]";
                            $alipay_table .= "\n总计： $i 笔， $total 元";
                            $i_all += $i;
                            $total_all += $total;
                        }
                        $year = 0 + $_POST['year'];
                        if (!$year || $year < 2018)
                            $year = date('Y');
                        $month = 0 + $_POST['month'];
                        if (!$month || $month < 1 || $month > 12)
                            $month = date('m');
                        $timestart = strtotime($year . "-" . $month . "-01 00:00:00");
                        $sqlstarttime = date("Y-m-d H:i:s", $timestart);
                        $timeend = strtotime("+1 month", $timestart);
                        $sqlendtime = date("Y-m-d H:i:s", $timeend);
                        $res = sql_query("SELECT uid, amount, anonymous, nickname, success_time FROM donation WHERE status = 0 AND used = 1 AND success_time BETWEEN " . sqlesc($sqlstarttime) . "AND" . sqlesc($sqlendtime) . "ORDER BY success_time DESC") or sqlerr(__FILE__, __LINE__);
                        $i = $total = 0;
                        $db_table = "\n\n[b][size=3][color=Green]捐赠系统[/color][/size][/b]";
                        // $db_table .= "\n[table][tr][td]捐赠时间[/td][td]捐赠者[/td][td]金额（元）[/td][/tr]";
                        
                        while ($row = mysql_fetch_assoc($res)) {
                            /*
                            if ($row['anonymous'] == 'yes') {
                                $name = "[i]" . $row['nickname'] . "（匿名）[/i]";
                            } else {
                                $name = get_username($row['uid'], false, false, false);
                            }
                            $db_table .= "[tr][td]" . $row['success_time'] . "[/td][td]" . $name . "[/td][td]" . $row['amount'] . "[/td][/tr]";
                            */
                            $i += 1;
                            $total += $row['amount'];
                        }
                        // $db_table .= "[/table]";
                        $db_table .= "\n具体明细请点击[url=donorlist2.php]这里[/url]查看。";
                        $db_table .= "\n总计： $i 笔， $total 元";
                        $i_all += $i;
                        $total_all += $total;
                        
                        $text = "[b][size=6][color=Red] $year 年 $month 月财报[/color][/size][/b]
                    \n\n[hr=[b][size=5]货币收入[/size][/b]]
                    \n[b][size=4][color=DarkOrange]捐款收入：[/color][/size][/b]";
                        if ($alipay_table) {
                            $text .= $alipay_table;
                        }
                        $text .= $db_table;
                        $text .= "\n\n[b][size=3]$year 年$month 月总计： $i_all 笔，$total_all 元[/size][/b]";
                        $text .= "\n\n[hr=[b][size=5]货币支出[/size][/b]]\n(请填写此处)\n\n[hr=[b][size=5]结余[/size][/b]]\n￥(请填写此处)";
                        begin_frame($lang_donorls['text_export'] . $year . $lang_donorls['text_year'] . $month . $lang_donorls['text_month'], true);
                        print("<textarea rows='50' cols='120'>$text</textarea>");
                        end_frame();
                        end_main_frame();
                        break;
                    } else {
                        begin_frame($lang_donorls['text_export'], true);
                        echo "<form action='donorlist2.php?action=export' method='post' enctype='multipart/form-data'>";
                        begin_table();
                        $y_html = "<select name='year'>";
                        for ($y = 2018; $y <= 0 + date('Y'); $y++)
                            $y_html .= "<option value='$y'>$y</option>";
                        $y_html .= "</select>";
                        $m_html = "<select name='month'>";
                        for ($m = 1; $m <= 12; $m++)
                            $m_html .= "<option value='$m'>$m</option>";
                        $m_html .= "</select>";
                        tr($lang_donorls['row_year'], $y_html, 1);
                        tr($lang_donorls['row_month'], $m_html, 1);
                        tr($lang_donorls['row_csvfile'], "<input type='file' class='file' name='csv'>", 1);
                        print("<tr><td class='toolbox' colspan='2' align='center'><input type='submit' class='btn' value='" . $lang_donorls['btn_submit'] . "'></td></tr>");
                        end_table();
                        echo "</form>";
                        end_frame();
                        end_main_frame();
                    }
                }
        }
    }
    stdfoot();
}

function gbk2utf8($str)
{
    return trim(iconv("gb2312", "utf-8", $str));
}
