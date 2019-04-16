<?php
# IMPORTANT: Do not edit below unless you know what you are doing!
define('IN_TRACKER', true);
$rootpath = realpath(dirname(__FILE__) . '/..') . "/";
require_once($rootpath . 'mysqli_wrapper/mysql.php');
include_once($rootpath . 'include/database/database.inc');
include($rootpath . 'include/core.php');
include_once($rootpath . 'include/functions_announce.php');
