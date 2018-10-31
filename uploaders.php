<?php
require "include/bittorrent.php";
dbconn();
require_once(get_langfile_path());
loggedinorreturn();

if (get_user_class() < UC_UPLOADER)
    permissiondenied();

$year = 0 + $_GET['year'];
if (!$year || $year < 2000)
    $year = date('Y');

$month = 0 + $_GET['month'];
if (!$month || $month <= 0 || $month > 12)
    $month = date('m');

$order = $_GET['order'];
if (!in_array($order, array('username', 'torrent_size', 'torrent_count')))
    $order = 'username';
$order .= ($order == 'username') ? ' ASC' : ' DESC';

global $datefounded;
$year2 = substr($datefounded, 0, 4);
$yearfounded = ($year2 ? $year2 : 2007);
$yearnow = date("Y");

$timestart = strtotime($year . "-" . $month . "-01 00:00:00");
$sqlstarttime = date("Y-m-d H:i:s", $timestart);
$timeend = strtotime("+1 month", $timestart);
$sqlendtime = date("Y-m-d H:i:s", $timeend);

$yearselection = "<select name=\"year\">";
for ($i = $yearfounded; $i <= $yearnow; $i++)
    $yearselection .= "<option value=\"$i\" " . ($i == $year ? "selected=\"selected\"" : "") . " >$i</option>";
$yearselection .= "</select>";

$monthselection = "<select name=\"month\">";
for ($i = 1; $i <= 12; $i++)
    $monthselection .= "<option value=\"$i\" " . ($i == $month ? "selected=\"selected\"" : "") . " >$i</option>";
$monthselection .= "</select>";

stdhead($lang_uploaders['head_uploaders']);
begin_main_frame($lang_uploaders['text_uploaders'] . " - " . date("Y-m", $timestart), true);

begin_table(false, 5, true);
?>
    <tr>
        <td>
            <form method="get" action="?">
            <span>
                <?php echo $lang_uploaders['text_select_month'] ?><?php echo $yearselection ?>
                &nbsp;&nbsp;<?php echo $monthselection ?>&nbsp;&nbsp;
                <input type="submit" value="<?php echo $lang_uploaders['submit_go'] ?>"/>
            </span>
            </form>
        </td>
        <td>
            <input type="checkbox" id="show_not_pass"
                   onclick="only_show_not_passed_uploaders(this);"/> <?php echo $lang_uploaders["text_only_show_not_passed"] ?>
        </td>
        <td>
        <span id="order" onclick="dropmenu(this);">
            <span style="cursor: pointer;">
                <b><?php echo $lang_uploaders['text_order_by'] ?></b>
            </span>
            <span id="orderlist" class="dropmenu" style="display: none">
                <ul>
                    <li><a href="?year=<?php echo $year ?>&amp;month=<?php echo $month ?>&amp;order=username"><?php echo $lang_uploaders['text_username'] ?></a></li>
                    <li><a href="?year=<?php echo $year ?>&amp;month=<?php echo $month ?>&amp;order=torrent_size"><?php echo $lang_uploaders['text_torrent_size'] ?></a></li>
                    <li><a href="?year=<?php echo $year ?>&amp;month=<?php echo $month ?>&amp;order=torrent_count"><?php echo $lang_uploaders['text_torrent_num'] ?></a></li>
                </ul>
            </span>
        </span>
        </td>
    </tr>
<?php
end_table();

$numres = sql_query("SELECT COUNT(users.id) FROM users WHERE class >= " . UC_UPLOADER) or sqlerr(__FILE__, __LINE__);
$numrow = mysql_fetch_array($numres);
if (!$numrow[0])
    print("<p align=\"center\">" . $lang_uploaders['text_no_uploaders_yet'] . "</p>");
else {
    print("<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\" align=\"center\" width=\"700\" style=\"text-align:center;\"><tr>");
    print("<td class=\"colhead\">" . $lang_uploaders['col_username'] . "</td>");
    print("<td class=\"colhead\">" . $lang_uploaders['col_torrents_size'] . "</td>");
    print("<td class=\"colhead\">" . $lang_uploaders['col_torrents_num'] . "</td>");
    if ($year == date('Y') && $month == date('m')) {
        print("<td class=\"colhead\">合集删种数</td>");
        print("<td class=\"colhead\">预计工资</td>");
        print("<td class=\"colhead\">是否通过考核</td>");
        print("</tr>");
    } else {
        print("<td class=\"colhead\">" . $lang_uploaders['col_last_upload_time'] . "</td>");
        print("<td class=\"colhead\">" . $lang_uploaders['col_last_upload'] . "</td>");
        print("</tr>");
    }
    
    $uploaders = array();
    $res = sql_query("SELECT users.class AS class, users.id AS userid, users.username AS username, COUNT(torrents.id) AS torrent_count, SUM(torrents.size) AS torrent_size FROM torrents LEFT JOIN users ON torrents.owner=users.id WHERE users.class >= " . UC_UPLOADER . " AND torrents.added > " . sqlesc($sqlstarttime) . " AND torrents.added < " . sqlesc($sqlendtime) . " GROUP BY userid ORDER BY " . $order) or sqlerr(__FILE__, __LINE__);
    $hasupuserid = array();
    while ($row = mysql_fetch_array($res)) {
        $uploaders[] = $row;
        $hasupuserid[] = $row['userid'];
    }
    $res = sql_query("SELECT users.class AS class, users.id AS userid, users.username AS username, 0 AS torrent_count, 0 AS torrent_size FROM users WHERE class >= " . UC_UPLOADER . (count($hasupuserid) ? " AND users.id NOT IN (" . implode(",", $hasupuserid) . ")" : "") . " ORDER BY username ASC") or sqlerr(__FILE__, __LINE__);
    while ($row = mysql_fetch_array($res))
        $uploaders[] = $row;
    
    foreach ($uploaders as $row) {
        $res2 = sql_query("SELECT torrents.id, torrents.name, torrents.added FROM torrents WHERE owner=" . $row['userid'] . " ORDER BY id DESC LIMIT 1") or sqlerr(__FILE__, __LINE__);
        $row2 = mysql_fetch_array($res2);
        print("<tr>");
        print("<td class=\"colfollow\">" . get_username($row['userid'], false, true, true, false, false, false) . "</td>");
        print("<td class=\"colfollow\">" . ($row['torrent_size'] ? mksize($row['torrent_size']) : "0") . "</td>");
        print("<td class=\"colfollow\">" . $row['torrent_count'] . "</td>");
        $deleted = mysql_fetch_array(sql_query("SELECT deleted_torrents FROM uploaders WHERE uid = " . $row['userid']));
        $salary = salary($row['torrent_count'] + $deleted['deleted_torrents'], $row['torrent_size'] / (1024 * 1024 * 1024), 20, 30);
        if ($row['torrent_size'] / (1024 * 1024 * 1024) >= 30 && $row['torrent_count'] >= 20)
            $passed = '<b><font color="green">是</font></b>';
        elseif ($row['class'] > UC_UPLOADER)
            $passed = '-';
        else
            $passed = '<b><font color="red">否</font></b>';
        if ($year == date('Y') && $month == date('m')) {
            print("<td class=\"colfollow\">" . $deleted['deleted_torrents'] . "</td>");
            print("<td class=\"colfollow\">" . $salary . "</td>");
            print("<td class=\"colfollow\">" . $passed . "</td>");
            print("</tr>");
        } else {
            print("<td class=\"colfollow\">" . ($row2['added'] ? gettime($row2['added']) : $lang_uploaders['text_not_available']) . "</td>");
            print("<td class=\"colfollow\">" . ($row2['name'] ? "<a href=\"details.php?id=" . $row2['id'] . "\">" . htmlspecialchars($row2['name']) . "</a>" : $lang_uploaders['text_not_available']) . "</td>");
            print("</tr>");
        }
    }
    end_table();
}
end_main_frame();
stdfoot();