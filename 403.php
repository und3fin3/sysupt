<?php
require('include/bittorrent.php');
dbconn();
global $BASEURL;
header('HTTP/1.1 403 Forbidden');
header('Refresh: 5; url=/index.php');
stderr('这谁家熊孩子啊', '别到处乱跑了，赶紧回家吃饭去吧<br /><br /><a href="/index.php">5秒后自动回到首页</a>', false);
