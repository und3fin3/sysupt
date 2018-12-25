<?php
require "include/bittorrent.php";
dbconn();
require_once(get_langfile_path());
loggedinorreturn();
if (get_user_class() < $pollmanage_class)
  permissiondenied();

$action = $_GET["action"];
$pollid = $_GET["pollid"];

if ($action == "edit")
{
	int_check($pollid,true);
	$res = sql_query("SELECT * FROM polls WHERE id = $pollid") or sqlerr(__FILE__, __LINE__);
	if (mysql_num_rows($res) == 0)
		stderr($lang_makepoll['std_error'], $lang_makepoll['std_no_poll_id']);
	$poll = mysql_fetch_array($res);
}

if ($_SERVER["REQUEST_METHOD"] == "POST")
{
	$pollid = (int)$_POST["pollid"];
	$question = htmlspecialchars($_POST["question"]);
	$option0 = htmlspecialchars($_POST["option0"]);
	$option1 = htmlspecialchars($_POST["option1"]);
	$option2 = htmlspecialchars($_POST["option2"]);
	$option3 = htmlspecialchars($_POST["option3"]);
	$option4 = htmlspecialchars($_POST["option4"]);
	$option5 = htmlspecialchars($_POST["option5"]);
	$option6 = htmlspecialchars($_POST["option6"]);
	$option7 = htmlspecialchars($_POST["option7"]);
	$option8 = htmlspecialchars($_POST["option8"]);
	$option9 = htmlspecialchars($_POST["option9"]);
	$option10 = htmlspecialchars($_POST["option10"]);
	$option11 = htmlspecialchars($_POST["option11"]);
	$option12 = htmlspecialchars($_POST["option12"]);
	$option13 = htmlspecialchars($_POST["option13"]);
	$option14 = htmlspecialchars($_POST["option14"]);
	$option15 = htmlspecialchars($_POST["option15"]);
	$option16 = htmlspecialchars($_POST["option16"]);
	$option17 = htmlspecialchars($_POST["option17"]);
	$option18 = htmlspecialchars($_POST["option18"]);
	$option19 = htmlspecialchars($_POST["option19"]);
    $option20 = htmlspecialchars($_POST["option20"]);
    $option21 = htmlspecialchars($_POST["option21"]);
    $option22 = htmlspecialchars($_POST["option22"]);
    $option23 = htmlspecialchars($_POST["option23"]);
    $option24 = htmlspecialchars($_POST["option24"]);
    $option25 = htmlspecialchars($_POST["option25"]);
    $option26 = htmlspecialchars($_POST["option26"]);
    $option27 = htmlspecialchars($_POST["option27"]);
    $option28 = htmlspecialchars($_POST["option28"]);
    $option29 = htmlspecialchars($_POST["option29"]);
    $option30 = htmlspecialchars($_POST["option30"]);
    $option31 = htmlspecialchars($_POST["option31"]);
    $option32 = htmlspecialchars($_POST["option32"]);
    $option33 = htmlspecialchars($_POST["option33"]);
    $option34 = htmlspecialchars($_POST["option34"]);
    $option35 = htmlspecialchars($_POST["option35"]);
    $option36 = htmlspecialchars($_POST["option36"]);
    $option37 = htmlspecialchars($_POST["option37"]);
    $option38 = htmlspecialchars($_POST["option38"]);
    $option39 = htmlspecialchars($_POST["option39"]);
    $option40 = htmlspecialchars($_POST["option40"]);
    $option41 = htmlspecialchars($_POST["option41"]);
    $option42 = htmlspecialchars($_POST["option42"]);
    $option43 = htmlspecialchars($_POST["option43"]);
    $option44 = htmlspecialchars($_POST["option44"]);
    $option45 = htmlspecialchars($_POST["option45"]);
    $option46 = htmlspecialchars($_POST["option46"]);
    $option47 = htmlspecialchars($_POST["option47"]);
    $option48 = htmlspecialchars($_POST["option48"]);
    $option49 = htmlspecialchars($_POST["option49"]);
	$returnto = htmlspecialchars($_POST["returnto"]);

	if (!$question || !$option0 || !$option1)
		stderr($lang_makepoll['std_error'], $lang_makepoll['std_missing_form_data']);

	if ($pollid)
        sql_query("UPDATE polls SET " .
            "question = " . sqlesc($question) . ", " .
            "option0 = " . sqlesc($option0) . ", " .
            "option1 = " . sqlesc($option1) . ", " .
            "option2 = " . sqlesc($option2) . ", " .
            "option3 = " . sqlesc($option3) . ", " .
            "option4 = " . sqlesc($option4) . ", " .
            "option5 = " . sqlesc($option5) . ", " .
            "option6 = " . sqlesc($option6) . ", " .
            "option7 = " . sqlesc($option7) . ", " .
            "option8 = " . sqlesc($option8) . ", " .
            "option9 = " . sqlesc($option9) . ", " .
            "option10 = " . sqlesc($option10) . ", " .
            "option11 = " . sqlesc($option11) . ", " .
            "option12 = " . sqlesc($option12) . ", " .
            "option13 = " . sqlesc($option13) . ", " .
            "option14 = " . sqlesc($option14) . ", " .
            "option15 = " . sqlesc($option15) . ", " .
            "option16 = " . sqlesc($option16) . ", " .
            "option17 = " . sqlesc($option17) . ", " .
            "option18 = " . sqlesc($option18) . ", " .
            "option19 = " . sqlesc($option19) . ", " .
            "option20 = " . sqlesc($option20) . ", " .
            "option21 = " . sqlesc($option21) . ", " .
            "option22 = " . sqlesc($option22) . ", " .
            "option23 = " . sqlesc($option23) . ", " .
            "option24 = " . sqlesc($option24) . ", " .
            "option25 = " . sqlesc($option25) . ", " .
            "option26 = " . sqlesc($option26) . ", " .
            "option27 = " . sqlesc($option27) . ", " .
            "option28 = " . sqlesc($option28) . ", " .
            "option29 = " . sqlesc($option29) . ", " .
            "option30 = " . sqlesc($option30) . ", " .
            "option31 = " . sqlesc($option31) . ", " .
            "option32 = " . sqlesc($option32) . ", " .
            "option33 = " . sqlesc($option33) . ", " .
            "option34 = " . sqlesc($option34) . ", " .
            "option35 = " . sqlesc($option35) . ", " .
            "option36 = " . sqlesc($option36) . ", " .
            "option37 = " . sqlesc($option37) . ", " .
            "option38 = " . sqlesc($option38) . ", " .
            "option39 = " . sqlesc($option39) . ", " .
            "option40 = " . sqlesc($option40) . ", " .
            "option41 = " . sqlesc($option41) . ", " .
            "option42 = " . sqlesc($option42) . ", " .
            "option43 = " . sqlesc($option43) . ", " .
            "option44 = " . sqlesc($option44) . ", " .
            "option45 = " . sqlesc($option45) . ", " .
            "option46 = " . sqlesc($option46) . ", " .
            "option47 = " . sqlesc($option47) . ", " .
            "option48 = " . sqlesc($option48) . ", " .
            "option49 = " . sqlesc($option49) . " " .
            " WHERE id = $pollid") or sqlerr(__FILE__, __LINE__);
	else
        sql_query("INSERT INTO polls VALUES(0, " . sqlesc(date("Y-m-d H:i:s")) . ", " .
            sqlesc($question) . ", " .
            sqlesc($option0) . ", " .
            sqlesc($option1) . ", " .
            sqlesc($option2) . ", " .
            sqlesc($option3) . ", " .
            sqlesc($option4) . ", " .
            sqlesc($option5) . ", " .
            sqlesc($option6) . ", " .
            sqlesc($option7) . ", " .
            sqlesc($option8) . ", " .
            sqlesc($option9) . ", " .
            sqlesc($option10) . ", " .
            sqlesc($option11) . ", " .
            sqlesc($option12) . ", " .
            sqlesc($option13) . ", " .
            sqlesc($option14) . ", " .
            sqlesc($option15) . ", " .
            sqlesc($option16) . ", " .
            sqlesc($option17) . ", " .
            sqlesc($option18) . ", " .
            sqlesc($option19) . ", " .
            sqlesc($option20) . ", " .
            sqlesc($option21) . ", " .
            sqlesc($option22) . ", " .
            sqlesc($option23) . ", " .
            sqlesc($option24) . ", " .
            sqlesc($option25) . ", " .
            sqlesc($option26) . ", " .
            sqlesc($option27) . ", " .
            sqlesc($option28) . ", " .
            sqlesc($option29) . ", " .
            sqlesc($option30) . ", " .
            sqlesc($option31) . ", " .
            sqlesc($option32) . ", " .
            sqlesc($option33) . ", " .
            sqlesc($option34) . ", " .
            sqlesc($option35) . ", " .
            sqlesc($option36) . ", " .
            sqlesc($option37) . ", " .
            sqlesc($option38) . ", " .
            sqlesc($option39) . ", " .
            sqlesc($option40) . ", " .
            sqlesc($option41) . ", " .
            sqlesc($option42) . ", " .
            sqlesc($option43) . ", " .
            sqlesc($option44) . ", " .
            sqlesc($option45) . ", " .
            sqlesc($option46) . ", " .
            sqlesc($option47) . ", " .
            sqlesc($option48) . ", " .
            sqlesc($option49) . ")") or sqlerr(__FILE__, __LINE__);

	$Cache->delete_value('current_poll_content');
	$Cache->delete_value('current_poll_result', true);
  	if ($returnto == "main")
		header("Location: " . get_protocol_prefix() . "$BASEURL");
  	elseif ($pollid)
		header("Location: " . get_protocol_prefix() . "$BASEURL/log.php?action=poll#$pollid");
	else
		header("Location: " . get_protocol_prefix() . "$BASEURL");
	die;
}

if ($pollid){
	stdhead($lang_makepoll['head_edit_poll']);
	print("<h1>".$lang_makepoll['text_edit_poll']."</h1>");
}
else
{
	stdhead($lang_makepoll['head_new_poll']);
	// Warn if current poll is less than 3 days old
	$res = sql_query("SELECT question, added FROM polls ORDER BY added DESC LIMIT 1") or sqlerr();
	$arr = mysql_fetch_assoc($res);
	if ($arr)
	{
		$hours = floor((strtotime(date("Y-m-d H:i:s")) - strtotime($arr["added"])) / 3600);
		$days = floor($hours / 24);
		if ($days < 3)
		{
			if ($days >= 1)
				$t = $days.$lang_makepoll['text_day'] . add_s($days);
			else
				$t = $hours.$lang_makepoll['text_hour'] . add_s($hours);
			print("<p><font class=striking><b>".$lang_makepoll['text_current_poll']."(<i>" . $arr["question"] . "</i>)".$lang_makepoll['text_is_only'].$t.$lang_makepoll['text_old']."</b></font></p>");
		}
	}
	print("<h1>".$lang_makepoll['text_make_poll']."</h1>");
}
?>

<table border=1 cellspacing=0 cellpadding=5>
<form method=post action=makepoll.php>
<style type="text/css">
input.mp
{
	width: 450px;
}
</style>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_question']?> <font color=red>*</font></td><td align=left><input name=question class=mp maxlength=255 value="<?php echo $poll['question']?>"></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>1 <font color=red>*</font></td><td align=left><input name=option0 class=mp maxlength=40 value="<?php echo $poll['option0']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>2 <font color=red>*</font></td><td align=left><input name=option1 class=mp maxlength=40 value="<?php echo $poll['option1']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>3</td><td align=left><input name=option2 class=mp maxlength=40 value="<?php echo $poll['option2']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>4</td><td align=left><input name=option3 class=mp maxlength=40 value="<?php echo $poll['option3']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>5</td><td align=left><input name=option4 class=mp maxlength=40 value="<?php echo $poll['option4']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>6</td><td align=left><input name=option5 class=mp maxlength=40 value="<?php echo $poll['option5']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>7</td><td align=left><input name=option6 class=mp maxlength=40 value="<?php echo $poll['option6']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>8</td><td align=left><input name=option7 class=mp maxlength=40 value="<?php echo $poll['option7']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>9</td><td align=left><input name=option8 class=mp maxlength=40 value="<?php echo $poll['option8']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>10</td><td align=left><input name=option9 class=mp maxlength=40 value="<?php echo $poll['option9']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>11</td><td align=left><input name=option10 class=mp maxlength=40 value="<?php echo $poll['option10']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>12</td><td align=left><input name=option11 class=mp maxlength=40 value="<?php echo $poll['option11']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>13</td><td align=left><input name=option12 class=mp maxlength=40 value="<?php echo $poll['option12']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>14</td><td align=left><input name=option13 class=mp maxlength=40 value="<?php echo $poll['option13']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>15</td><td align=left><input name=option14 class=mp maxlength=40 value="<?php echo $poll['option14']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>16</td><td align=left><input name=option15 class=mp maxlength=40 value="<?php echo $poll['option15']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>17</td><td align=left><input name=option16 class=mp maxlength=40 value="<?php echo $poll['option16']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>18</td><td align=left><input name=option17 class=mp maxlength=40 value="<?php echo $poll['option17']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>19</td><td align=left><input name=option18 class=mp maxlength=40 value="<?php echo $poll['option18']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>20</td><td align=left><input name=option19 class=mp maxlength=40 value="<?php echo $poll['option19']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>21</td><td align=left><input name=option20 class=mp maxlength=40 value="<?php echo $poll['option20']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>22</td><td align=left><input name=option21 class=mp maxlength=40 value="<?php echo $poll['option21']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>23</td><td align=left><input name=option22 class=mp maxlength=40 value="<?php echo $poll['option22']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>24</td><td align=left><input name=option23 class=mp maxlength=40 value="<?php echo $poll['option23']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>25</td><td align=left><input name=option24 class=mp maxlength=40 value="<?php echo $poll['option24']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>26</td><td align=left><input name=option25 class=mp maxlength=40 value="<?php echo $poll['option25']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>27</td><td align=left><input name=option26 class=mp maxlength=40 value="<?php echo $poll['option26']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>28</td><td align=left><input name=option27 class=mp maxlength=40 value="<?php echo $poll['option27']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>29</td><td align=left><input name=option28 class=mp maxlength=40 value="<?php echo $poll['option28']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>30</td><td align=left><input name=option29 class=mp maxlength=40 value="<?php echo $poll['option29']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>31</td><td align=left><input name=option30 class=mp maxlength=40 value="<?php echo $poll['option30']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>32</td><td align=left><input name=option31 class=mp maxlength=40 value="<?php echo $poll['option31']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>33</td><td align=left><input name=option32 class=mp maxlength=40 value="<?php echo $poll['option32']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>34</td><td align=left><input name=option33 class=mp maxlength=40 value="<?php echo $poll['option33']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>35</td><td align=left><input name=option34 class=mp maxlength=40 value="<?php echo $poll['option34']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>36</td><td align=left><input name=option35 class=mp maxlength=40 value="<?php echo $poll['option35']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>37</td><td align=left><input name=option36 class=mp maxlength=40 value="<?php echo $poll['option36']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>38</td><td align=left><input name=option37 class=mp maxlength=40 value="<?php echo $poll['option37']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>39</td><td align=left><input name=option38 class=mp maxlength=40 value="<?php echo $poll['option38']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>40</td><td align=left><input name=option39 class=mp maxlength=40 value="<?php echo $poll['option39']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>41</td><td align=left><input name=option40 class=mp maxlength=40 value="<?php echo $poll['option40']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>42</td><td align=left><input name=option41 class=mp maxlength=40 value="<?php echo $poll['option41']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>43</td><td align=left><input name=option42 class=mp maxlength=40 value="<?php echo $poll['option42']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>44</td><td align=left><input name=option43 class=mp maxlength=40 value="<?php echo $poll['option43']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>45</td><td align=left><input name=option44 class=mp maxlength=40 value="<?php echo $poll['option44']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>46</td><td align=left><input name=option45 class=mp maxlength=40 value="<?php echo $poll['option45']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>47</td><td align=left><input name=option46 class=mp maxlength=40 value="<?php echo $poll['option46']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>48</td><td align=left><input name=option47 class=mp maxlength=40 value="<?php echo $poll['option47']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>49</td><td align=left><input name=option48 class=mp maxlength=40 value="<?php echo $poll['option48']?>"><br /></td></tr>
    <tr><td class=rowhead><?php echo $lang_makepoll['text_option']?>50</td><td align=left><input name=option49 class=mp maxlength=40 value="<?php echo $poll['option49']?>"><br /></td></tr>
    <tr><td colspan=2 align=center><input type=submit value="<?php echo $pollid ? $lang_makepoll['submit_edit_poll'] : $lang_makepoll['submit_create_poll']?>" style='height: 20pt'></td></tr>
</table>
<p><font color=red>*</font><?php echo $lang_makepoll['text_required']?></p>
<?php
if ($pollid)
print("<input type=hidden name=pollid value=\"".$poll["id"]."\">");
?>
<input type=hidden name=returnto value="<?php echo htmlspecialchars($_GET["returnto"]) ? htmlspecialchars($_GET["returnto"]) : htmlspecialchars($_SERVER["HTTP_REFERER"])?>">
</form>

<?php
stdfoot();
?>
