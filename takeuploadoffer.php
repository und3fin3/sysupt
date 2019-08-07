<?php
require_once("include/bittorrent.php");

use OurBits\Bencode;

dbconn();
require_once(get_langfile_path());
require(get_langfile_path("", true));
loggedinorreturn();
global $CURUSER, $enablenfo_main, $BASEURL, $SITENAME, $max_torrent_size, $enableoffer, $largesize_torrent, $largepro_torrent, $beanonymous_class, $middlesize_torrent, $middlepro_torrent, $altname_main, $prorules_torrent, $emailnotify_smtp, $smtptype, $torrent_dir, $SITEEMAIL, $uploadtorrent_bonus;
ini_set("upload_max_filesize", $max_torrent_size);

function bark($msg)
{
    global $lang_takeuploadoffer;
    genbark($msg, $lang_takeuploadoffer ['std_upload_failed']);
    die ();
}

if ($CURUSER ["uploadpos"] == 'no')
    die ();

if (!isset ($_FILES ["file"]))
    bark($lang_takeuploadoffer ['std_missing_form_data']);

$f = $_FILES ["file"];
$fname = $f ["name"];
if (empty ($fname))
    bark($lang_takeuploadoffer ['std_empty_filename']);
if (get_user_class() >= $beanonymous_class && $_POST ['uplver'] == 'yes') { // $_POST['uplver']
    // ==
    // 'yes'匿名发布;
    $anonymous = "yes";
    $anon = "匿名用户";
} else {
    $anonymous = "no";
    $anon = "用户 " . $CURUSER ["username"];
}

$url = parse_imdb_id($_POST ['url']);

$nfo = '';
if ($enablenfo_main == 'yes') { // NFO文件
    $nfofile = $_FILES ['nfo'];
    if ($nfofile ['name'] != '') {

        if ($nfofile ['size'] == 0)
            bark($lang_takeuploadoffer['std_zero_byte_nfo']);

        if ($nfofile ['size'] > 65535)
            bark($lang_takeuploadoffer['std_nfo_too_big']);

        $nfofilename = $nfofile ['tmp_name'];

        if (@!is_uploaded_file($nfofilename))
            bark($lang_takeuploadoffer['std_nfo_upload_failed']);
        $nfo = str_replace("\x0d\x0d\x0a", "\x0d\x0a", @file_get_contents($nfofilename));
    }
}

$catid = (0 + $_POST ["type"]);
$sourceid = (0 + $_POST ["source_sel"]);
$mediumid = (0 + $_POST ["medium_sel"]);
$codecid = (0 + $_POST ["codec_sel"]);
$standardid = (0 + $_POST ["standard_sel"]);
$processingid = (0 + $_POST ["processing_sel"]);
$teamid = (0 + $_POST ["team_sel"]);
$audiocodecid = (0 + $_POST ["audiocodec_sel"]);

if (!validfilename($fname))
    bark($lang_takeuploadoffer['std_invalid_filename']);
if (!preg_match('/^(.+)\.torrent$/si', $fname, $matches))
    bark($lang_takeuploadoffer['std_filename_not_torrent']);
$shortfname = $torrent = $matches [1];

if ($f ['size'] > $max_torrent_size)
    bark($lang_takeuploadoffer['std_torrent_file_too_big'] . number_format($max_torrent_size) . $lang_takeuploadoffer['std_remake_torrent_note']);
$tmpname = $f ["tmp_name"];
if (!is_uploaded_file($tmpname))
    bark($lang_takeupload ['std_missing_tmp_file']);
if (!filesize($tmpname))
    bark($lang_takeuploadoffer['std_empty_file']);
$dict = Bencode::load($tmpname);

function checkTorrentDict($dict, $key, $type = null)
{
    global $lang_takeupload;

    if (!is_array($dict)) bark($lang_takeupload['std_not_a_dictionary']);
    $value = $dict[$key];
    if (!$value) bark($lang_takeupload['std_dictionary_is_missing_key']);
    if (!is_null($type)) {
        $isFunction = 'is_' . $type;
        if (function_exists($isFunction) && !$isFunction($value)) {
            bark($lang_takeupload['std_invalid_entry_in_dictionary']);
        }
    }
    return $value;
}

$info = checkTorrentDict($dict, 'info');
$plen = checkTorrentDict($info, 'piece length', 'integer');  // Only Check without use
$dname = checkTorrentDict($info, 'name', 'string');
$pieces = checkTorrentDict($info, 'pieces', 'string');

if (strlen($pieces) % 20 != 0)
    bark($lang_takeuploadoffer['std_invalid_pieces']);

$totallen = $info['length'];
$filelist = array();
if ($totallen) {
    $filelist[] = [$dname, $totallen];
    $type = "single";
} else {
    $f_list = checkTorrentDict($info, 'files', 'array');
    if (!isset($f_list)) bark($lang_takeupload['std_missing_length_and_files']);
    if (!count($f_list)) bark("no files");

    $totallen = 0;
    foreach ($f_list as $fn) {
        $ll = checkTorrentDict($fn, 'length', 'integer');
        $path_key = isset($fn['path.utf-8']) ? 'path.utf-8' : 'path';
        $ff = checkTorrentDict($fn, $path_key, 'list');
        $totallen += $ll;
        $ffa = [];
        foreach ($ff as $ffe) {
            if (!is_string($ffe)) bark($lang_takeupload['std_filename_errors']);
            $ffa[] = $ffe;
        }
        if (!count($ffa)) bark($lang_takeupload['std_filename_errors']);
        $ffe = implode("/", $ffa);
        $filelist[] = [$ffe, $ll];
    }
    $type = "multi";
}

// -- makePrivateTorrent --
$dict['announce'] = get_protocol_prefix() . $announce_urls[0];  // change announce url to local
$dict['info']['private'] = 1;

// Remove un-need field in the torrent
if ($dict['created by'] == "hdchina.org") {
    $dict['created by'] = "[$BASEURL]";
    $dict['comment'] = "Torrent From TJUPT";
}
if ($dict['created by'] == "http://cgbt.org") {
    $dict['created by'] = "[$BASEURL]";
    $dict['comment'] = "Torrent From TJUPT";
}
if (isset ($dict['info']['ttg_tag'])) {
    unset ($dict['info']['ttg_tag']);
    $dict['created by'] = "[$BASEURL]";
    $dict['comment'] = "Torrent From TJUPT";
}
// The following line requires uploader to re-download torrents after uploading
// even the torrent is set as private and with uploader's passkey in it.
$dict['info']['source'] = "[$BASEURL] $SITENAME";
unset ($dict['announce-list']); // remove multi-tracker capability
unset ($dict['nodes']); // remove cached peers (Bitcomet & Azareus)

// Clean The `info` dict
$allowed_keys = [
    'files', 'name', 'piece length', 'pieces', 'private', 'length',
    'name.utf8', 'name.utf-8', 'md5sum', 'sha1', 'source',
    'file-duration', 'file-media', 'profiles'
];
foreach ($dict['info'] as $key => $value) {
    if (!in_array($key, $allowed_keys)) {
        unset($dict['info'][$key]);
    }
}

$infohash = pack("H*", sha1(Bencode::encode($dict['info'])));

// ------------- start: check upload authority ------------------//
$allowtorrents = user_can_upload("torrents");
$allowspecial = user_can_upload("music");

$catmod = 4;
$offerid = $_POST ['offer'];

if ($offerid == 0)
    bark($lang_takeuploadoffer['std_please_choose_a_offer']);

$is_offer = false;
if ($browsecatmode != $specialcatmode && $catmod == $specialcatmode) { // upload to special section
    if (!$allowspecial)
        bark($lang_takeuploadoffer['std_unauthorized_upload_freely']);
} elseif ($catmod == $browsecatmode) { // upload to torrents section
    if ($offerid) { // it is a offer
        $allowed_offer_count = get_row_count("offers", "WHERE allowed='allowed' AND userid=" . sqlesc($CURUSER ["id"]));
        if ($allowed_offer_count && $enableoffer == 'yes') {
            $allowed_offer = get_row_count("offers", "WHERE id=" . sqlesc($offerid) . " AND allowed='allowed' AND userid=" . sqlesc($CURUSER ["id"]));
            if ($allowed_offer != 1) // user uploaded torrent that is not an
                // allowed offer
                bark($lang_takeuploadoffer['std_uploaded_not_offered']);
            else
                $is_offer = true;
        } else
            bark($lang_takeuploadoffer['std_uploaded_not_offered']);
    } elseif (!$allowtorrents)
        bark($lang_takeuploadoffer['std_unauthorized_upload_freely']);
} else // upload to unknown section
    die ("Upload to unknown section.");

if ($largesize_torrent && $totallen > ($largesize_torrent * 1073741824)) // Large Torrent Promotion
{
    switch ($largepro_torrent) {
        case 2 : // Free
            {
                $sp_state = 2;
                break;
            }
        case 3 : // 2X
            {
                $sp_state = 3;
                break;
            }
        case 4 : // 2X Free
            {
                $sp_state = 4;
                break;
            }
        case 5 : // Half Leech
            {
                $sp_state = 5;
                break;
            }
        case 6 : // 2X Half Leech
            {
                $sp_state = 6;
                break;
            }
        case 7 : // 30% Leech
            {
                $sp_state = 7;
                break;
            }
        case 8 : // Free Forever
            {
                $sp_state = 8;
                break;
            }
        case 9 : // 2X Forever
            {
                $sp_state = 9;
                break;
            }
        case 10 : // 2X Free Forever
            {
                $sp_state = 10;
                break;
            }
        case 11 : // Half Leech Forever
            {
                $sp_state = 11;
                break;
            }
        case 12 : // 2X Half Leech Forever
            {
                $sp_state = 12;
                break;
            }
        case 13 : // 30% Leech Forever
            {
                $sp_state = 13;
                break;
            }
        default : // normal
            {
                $sp_state = 1;
                break;
            }
    }
} elseif ($middlesize_torrent && $totallen > ($middlesize_torrent * 1073741824)) {
    switch ($middlepro_torrent) {
        case 2 : // Free
            {
                $sp_state = 2;
                break;
            }
        case 3 : // 2X
            {
                $sp_state = 3;
                break;
            }
        case 4 : // 2X Free
            {
                $sp_state = 4;
                break;
            }
        case 5 : // Half Leech
            {
                $sp_state = 5;
                break;
            }
        case 6 : // 2X Half Leech
            {
                $sp_state = 6;
                break;
            }
        case 7 : // 30% Leech
            {
                $sp_state = 7;
                break;
            }
        case 8 : // Free Forever
            {
                $sp_state = 8;
                break;
            }
        case 9 : // 2X Forever
            {
                $sp_state = 9;
                break;
            }
        case 10 : // 2X Free Forever
            {
                $sp_state = 10;
                break;
            }
        case 11 : // Half Leech Forever
            {
                $sp_state = 11;
                break;
            }
        case 12 : // 2X Half Leech Forever
            {
                $sp_state = 12;
                break;
            }
        case 13 : // 30% Leech Forever
            {
                $sp_state = 13;
                break;
            }
        default : // normal
            {
                $sp_state = 1;
                break;
            }
    }
} else { // ramdom torrent promotion
    global $randomfree_torrent, $randomtwoup_torrent, $randomtwoupfree_torrent, $randomhalfleech_torrent,
           $randomtwouphalfdown_torrent, $randomthirtypercentdown_torrent, $randomfreeforever_torrent,
           $randomtwoupforever_torrent, $randomtwoupfreeforever_torrent, $randomhalfleechforever_torrent,
           $randomtwouphalfdownforever_torrent, $randomthirtypercentdownforever_torrent;
    $sp_id = mt_rand(1, 100);
    if ($sp_id <= ($probability = $randomfree_torrent)) // Free
        $sp_state = 2;
    elseif ($sp_id <= ($probability += $randomtwoup_torrent)) // 2X
        $sp_state = 3;
    elseif ($sp_id <= ($probability += $randomtwoupfree_torrent)) // 2X Free
        $sp_state = 4;
    elseif ($sp_id <= ($probability += $randomhalfleech_torrent)) // Half Leech
        $sp_state = 5;
    elseif ($sp_id <= ($probability += $randomtwouphalfdown_torrent)) // 2X Half
        $sp_state = 6;
    elseif ($sp_id <= ($probability += $randomthirtypercentdown_torrent)) // 30%
        $sp_state = 7;
    elseif ($sp_id <= ($probability += $randomfreeforever_torrent)) // Free Forever
        $sp_state = 8;
    elseif ($sp_id <= ($probability += $randomtwoupforever_torrent)) // 2X Forever
        $sp_state = 9;
    elseif ($sp_id <= ($probability += $randomtwoupfreeforever_torrent)) // 2X Free Forever
        $sp_state = 10;
    elseif ($sp_id <= ($probability += $randomhalfleechforever_torrent)) // Half Leech Forever
        $sp_state = 11;
    elseif ($sp_id <= ($probability += $randomtwouphalfdownforever_torrent)) // 2X Half Leech Forever
        $sp_state = 12;
    elseif ($sp_id <= ($probability += $randomthirtypercentdownforever_torrent)) // 30% Leech Forever
        $sp_state = 13;
    else
        $sp_state = 1; // normal
}

if ($altname_main == 'yes') {
    $cnname_part = trim($_POST ["cnname"]);
    $size_part = str_replace(" ", "", mksize($totallen));
    $date_part = date("m.d.y");
    $category_part = get_single_value("categories", "name", "WHERE id = " . sqlesc($catid));
    $torrent = "【" . $date_part . "】" . ($_POST ["name"] ? "[" . $_POST ["name"] . "]" : "") . ($cnname_part ? "[" . $cnname_part . "]" : "");
}

// some ugly code of automatically promoting torrents based on some rules
if ($prorules_torrent == 'yes') {
    foreach ($promotionrules_torrent as $rule) {
        if (!array_key_exists('catid', $rule) || in_array($catid, $rule ['catid']))
            if (!array_key_exists('sourceid', $rule) || in_array($sourceid, $rule ['sourceid']))
                if (!array_key_exists('mediumid', $rule) || in_array($mediumid, $rule ['mediumid']))
                    if (!array_key_exists('codecid', $rule) || in_array($codecid, $rule ['codecid']))
                        if (!array_key_exists('standardid', $rule) || in_array($standardid, $rule ['standardid']))
                            if (!array_key_exists('processingid', $rule) || in_array($processingid, $rule ['processingid']))
                                if (!array_key_exists('teamid', $rule) || in_array($teamid, $rule ['teamid']))
                                    if (!array_key_exists('audiocodecid', $rule) || in_array($audiocodecid, $rule ['audiocodecid']))
                                        if (!array_key_exists('pattern', $rule) || preg_match($rule ['pattern'], $torrent))
                                            if (is_numeric($rule ['promotion'])) {
                                                $sp_state = $rule ['promotion'];
                                                break;
                                            }
    }
}

/**
 * ***********************************
 * 从offers和offersinfo中查出信息并存入torrents和torrentsinfo中***********************************
 */
$offerlist = sql_query("SELECT name, descr, category FROM offers WHERE id = " . $offerid);
while ($row = mysql_fetch_assoc($offerlist)) {
    $catid = $row ["category"];
    $offername = $row ["name"];
    $offerdescr = $row ["descr"];
}
if ($catid == 404)
    $sp_state = 11; // 资料类特殊优惠
// my codes
//------------------------禁止发布包含不规范文件的种子--------------------//
$errfile = array();
foreach ($filelist as $file) {
    $filename = $file [0];
    // if (preg_match('/^(.+)\.torrent$/si', $filename, $match))
    $bannedflie = '(qsv)|(KUX)';
    $notallowed = '(torrent)';
    $bannedflieres = sql_query("SELECT * FROM banned_file_type WHERE catid=$catid AND class='banned'") or sqlerr(__FILE__, __LINE__);
    $notallowedres = sql_query("SELECT * FROM banned_file_type WHERE catid=$catid AND class='notallowed'") or sqlerr(__FILE__, __LINE__);
    while ($bannedfliearr = mysql_fetch_array($bannedflieres))
        $bannedflie .= "|" . $bannedfliearr['type'];
    while ($notallowedarr = mysql_fetch_array($notallowedres))
        $notallowed .= "|" . $notallowedarr['type'];
    if (preg_match('/(((~u[t,T]orrent)|(~\$))(.+))|((.+)(\.((torrent)|' . $notallowed . "|" . $bannedflie . '|(!ut)|(url)|(qdl2)|(td)|(tdl)|(td\.cfg)|(tmp))$))/si', $filename, $match))
        $errfile [] = $filename;
    if (preg_match('/(((~u[t,T]orrent)|(~\$))(.+))|((.+)(\.(' . $bannedflie . '|(xv)|(qsv))$))/si', $filename, $match))
        $notoffer = 1;
}
if (count($errfile)) {
    if ($notoffer) {
        stderr($lang_takeuploadoffer ['std_upload_failed'], "您发布的种子内包含不符合发布要求的文件。<br/>如果它们是你要发布的<b>主要文件</b>，可能相应<a href='rules.php#rules_upload' class='faqlink'>规则</a>不允许发布这类格式文件，请不要尝试转换格式发布。如果您坚持认为该文件可以发布，请联系管理员。<br/>下列文件不符合发布要求：<p>" . join("<br/>", $errfile) . "</p>", false);
    }
}
// end of my codes
if (is_banned_title($offername, $catid)) {
    bark($lang_takeuploadoffer ['std_banned_title1'] . $offername . $lang_takeuploadoffer ['std_banned_title2'] . $lang_takeuploadoffer ['std_banned_title_hit']);
}

$offerinfolist = sql_query("SELECT * FROM offersinfo WHERE offerid = " . $offerid);
while ($row = mysql_fetch_assoc($offerinfolist)) {
    $cname = $row ["cname"];
    $ename = $row ["ename"];
    $issuedate = $row ["issuedate"];
    $subsinfo = $row ["subsinfo"];
    $format = $row ["format"];
    $imdbnum = $row ["imdbnum"];
    $specificcat = $row ["specificcat"];
    $language = $row ["language"];
    $district = $row ["district"];
    $version = $row ["version"];
    $substeam = $row ["substeam"];
    $animenum = $row ["animenum"];
    $resolution = $row ["resolution"];
    $tvalias = $row ["tvalias"];
    $tvseasoninfo = $row ["tvseasoninfo"];
    $tvshowscontent = $row ["tvshowscontent"];
    $tvshowsguest = $row ["tvshowsguest"];
    $tvshowsremarks = $row ["tvshowsremarks"];
    $company = $row ["company"];
    $platform = $row ["platform"];
    $artist = $row ["artist"];
    $hqname = $row ["hqname"];
    $hqtone = $row ["hqtone"];
}

/**
 * *********************************** end
 * 从offers和offersinfo中查出信息并存入torrents和torrentsinfo中***********************************
 */
$exist_hash = sql_query("select id, info_hash from torrents where pulling_out = '0' AND " . hash_where("info_hash", $infohash));
if ($arr = mysql_fetch_row($exist_hash)) {
    stderr($lang_takeuploadoffer ['std_upload_failed'], $lang_takeuploadoffer ['std_torrent_existed'] . "<a href='details.php?id=" . $arr[0] . "&hit=1'><b>点此进入种子页</b></a>", false);
}
$ret = sql_query("INSERT INTO torrents (filename, owner, visible, anonymous, name, size, numfiles, type, url, small_descr, descr, ori_descr, category, source, medium, codec, audiocodec, standard, processing, team, save_as, sp_state, added, last_action, nfo, info_hash, sp_time) VALUES (" . sqlesc($fname) . ", " . sqlesc($CURUSER ["id"]) . ", 'yes', " . sqlesc($anonymous) . ", " . sqlesc($offername) . ", " . sqlesc($totallen) . ", " . count($filelist) . ", " . sqlesc($type) . ", " . sqlesc($url) . ", " . sqlesc($small_descr) . ", " . sqlesc($offerdescr) . ", " . sqlesc($descr) . ", " . sqlesc($catid) . ", " . sqlesc($sourceid) . ", " . sqlesc($mediumid) . ", " . sqlesc($codecid) . ", " . sqlesc($audiocodecid) . ", " . sqlesc($standardid) . ", " . sqlesc($processingid) . ", " . sqlesc($teamid) . ", " . sqlesc($dname) . ", " . sqlesc($sp_state) . ", " . sqlesc(date("Y-m-d H:i:s")) . ", " . sqlesc(date("Y-m-d H:i:s")) . ", " . sqlesc($nfo) . ", " . sqlesc($infohash) . ", " . sqlesc(date("Y-m-d H:i:s")) . ")");
if (!$ret) {
    if (mysql_errno() == 1062)
        bark($lang_takeuploadoffer ['std_torrent_existed']);
    bark("mysql puked: " . mysql_error());
    // bark("mysql puked: ".preg_replace_callback('/./s', "hex_esc2",
    // mysql_error()));
}
$id = mysql_insert_id();
if ($sp_state > 7 && $sp_state < 14) {
    sql_query("UPDATE torrents SET promotion_time_type = 1 WHERE id=" . $id);
}

/**
 * ***********************************
 * 从offers和offersinfo中查出信息并存入torrents和torrentsinfo中***********************************
 */
$torinfo = sql_query("INSERT INTO torrentsinfo (torid,category,cname,ename,issuedate,subsinfo,format,imdbnum,specificcat,language,district,version,substeam,animenum,resolution,tvalias,tvseasoninfo,tvshowscontent,tvshowsguest,tvshowsremarks,company,platform,artist,hqname,hqtone) VALUES (" . sqlesc($id) . "," . sqlesc($catid) . "," . sqlesc($cname) . "," . sqlesc($ename) . "," . sqlesc($issuedate) . "," . sqlesc($subsinfo) . "," . sqlesc($format) . "," . sqlesc($imdbnum) . "," . sqlesc($specificcat) . "," . sqlesc($language) . "," . sqlesc($district) . "," . sqlesc($version) . "," . sqlesc($substeam) . "," . sqlesc($animenum) . "," . sqlesc($resolution) . "," . sqlesc($tvalias) . "," . sqlesc($tvseasoninfo) . "," . sqlesc($tvshowscontent) . "," . sqlesc($tvshowsguest) . "," . sqlesc($tvshowsremarks) . "," . sqlesc($company) . "," . sqlesc($platform) . "," . sqlesc($artist) . "," . sqlesc($hqname) . "," . sqlesc($hqtone) . ")");

if (!$torinfo) {
    if (mysql_errno() == 1062)
        bark($lang_takeupload ['std_torrent_existed']);
    bark("mysql puked: " . mysql_error());
    // bark("mysql puked: ".preg_replace_callback('/./s', "hex_esc2",
    // mysql_error()));
}
/**
 * *********************************** end
 * 从offers和offersinfo中查出信息并存入torrents和torrentsinfo中***********************************
 */

@sql_query("DELETE FROM files WHERE torrent = $id");
foreach ($filelist as $file) {
    @sql_query("INSERT INTO files (torrent, filename, size) VALUES ($id, " . sqlesc($file [0]) . "," . $file [1] . ")");
}

// 保存种子文件
$dump_status = Bencode::dump("$torrent_dir/$id.torrent", $dict);

// ===add karma
KPS("+", $uploadtorrent_bonus, $CURUSER ["id"]);
// ===end

write_log("$anon 上传了资源 $id (" . $offername . ") ");

// ===notify people who voted on offer thanks CoLdFuSiOn :)
if ($is_offer) {
    $res = sql_query("SELECT `userid` FROM `offervotes` WHERE `userid` != " . $CURUSER ["id"] . " AND `offerid` = " . sqlesc($offerid) . " AND `vote` = 'yeah'") or sqlerr(__FILE__, __LINE__);

    while ($row = mysql_fetch_assoc($res)) {
        $pn_msg = $lang_takeuploadoffer_target [get_user_lang($row ["userid"])] ['msg_offer_you_voted'] . $torrent . $lang_takeuploadoffer_target [get_user_lang($row ["userid"])] ['msg_was_uploaded_by'] . $CURUSER ["username"] . $lang_takeuploadoffer_target [get_user_lang($row ["userid"])] ['msg_you_can_download'] . "[url=details.php?id=$id&hit=1]" . $lang_takeuploadoffer_target [get_user_lang($row ["userid"])] ['msg_here'] . "[/url]";

        // === use this if you DO have subject in your PMs
        $subject = $lang_takeuploadoffer_target [get_user_lang($row ["userid"])] ['msg_offer'] . $torrent . $lang_takeuploadoffer_target [get_user_lang($row ["userid"])] ['msg_was_just_uploaded'];
        // === use this if you DO NOT have subject in your PMs
        // $some_variable .= "(0, $row[userid], '" . date("Y-m-d H:i:s") . "', "
        // . sqlesc($pn_msg) . ")";

        // === use this if you DO have subject in your PMs
        sql_query("INSERT INTO messages (sender, subject, receiver, added, msg) VALUES (0, " . sqlesc($subject) . ", $row[userid], " . sqlesc(date("Y-m-d H:i:s")) . ", " . sqlesc($pn_msg) . ")") or sqlerr(__FILE__, __LINE__);
        // === use this if you do NOT have subject in your PMs
        // sql_query("INSERT INTO messages (sender, receiver, added, msg) VALUES
        // ".$some_variable."") or sqlerr(__FILE__, __LINE__);
        // ===end
    }
    // === delete all offer stuff
    sql_query("DELETE FROM offers WHERE id = " . $offerid);
    sql_query("DELETE FROM offervotes WHERE offerid = " . $offerid);
    sql_query("DELETE FROM comments WHERE offer = " . $offerid);
    sql_query("DELETE FROM offersinfo WHERE offerid=$offerid");
}
// === end notify people who voted on offer

/* Email notifs */

if ($emailnotify_smtp == 'yes' && $smtptype != 'none') {
    $cat = get_single_value("categories", "name", "WHERE id=" . sqlesc($catid));
    $res = sql_query("SELECT id, email, lang FROM users WHERE enabled='yes' AND parked='no' AND status='confirmed' AND notifs LIKE '%[cat$catid]%' AND notifs LIKE '%[email]%' ORDER BY lang ASC") or sqlerr(__FILE__, __LINE__);

    $uploader = $anon;

    $size = mksize($totallen);

    $description = format_comment($descr);

    // dirty code, change later

    $langfolder_array = array(
        "en",
        "chs",
        "cht",
        "ko",
        "ja"
    );
    $body_arr = array(
        "en" => "",
        "chs" => "",
        "cht" => "",
        "ko" => "",
        "ja" => ""
    );
    $i = 0;
    foreach ($body_arr as $body) {
        $body_arr [$langfolder_array [$i]] = <<<EOD
{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_hi']}

{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_new_torrent']}

{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_torrent_name']}$torrent
{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_torrent_size']}$size
{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_torrent_category']}$cat
{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_torrent_uppedby']}$uploader

{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_torrent_description']}
-------------------------------------------------------------------------------------------------------------------------
$description
-------------------------------------------------------------------------------------------------------------------------

{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_torrent']}<b><a href=https://$BASEURL/details.php?id=$id&hit=1 >{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_here']}</a></b><br />
https://$BASEURL/details.php?id=$id&hit=1

------{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_yours']}
{$lang_takeuploadoffer_target[$langfolder_array[$i]]['mail_team']}
EOD;

        $body_arr [$langfolder_array [$i]] = str_replace("<br />", "<br />", nl2br($body_arr [$langfolder_array [$i]]));
        $i++;
    }

    while ($arr = mysql_fetch_array($res)) {
        $current_lang = $arr ["lang"];
        $to = $arr ["email"];

        sent_mail($to, $SITENAME, $SITEEMAIL, change_email_encode(validlang($current_lang), $lang_takeuploadoffer_target [validlang($current_lang)] ['mail_title'] . $torrent), change_email_encode(validlang($current_lang), $body_arr [validlang($current_lang)]), "torrent upload", false, false, '', get_email_encode(validlang($current_lang)), "eYou");
    }
}

header("Location: details.php?id=" . htmlspecialchars($id) . "&uploaded=1");