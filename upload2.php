<?php
require_once("include/bittorrent.php");
require_once("include/tjuip_helper.php");
dbconn();
require_once(get_langfile_path());
global $CURUSER, $enableoffer, $torrent_dir, $max_torrent_size, $smalldescription_main, $enablenfo_main, $viewnfo_class, $beanonymous_class;
loggedinorreturn();
parked();
check_tjuip_or_warning($CURUSER);

if ($CURUSER["uploadpos"] == 'no')
    stderr($lang_upload['std_sorry'], $lang_upload['std_unauthorized_to_upload'], false);


