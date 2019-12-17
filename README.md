# SYSUPT Code

[SYSUPT](https://21weeks.icu) is a site based on NexusPHP. All modifications made are here.

This is a fork from [TJUPT](https://github.com/zcqian/tjupt)

## First of all 写在前面的话
After uploading to the production environment, you should delete all "SYSUPT mark" including but not limited to:<br>
在上线至生产环境前，你应该删除所有“有关SYSUPT的标识”，包括但不限于：
* Site name 站点名称
* QQ group number/QR code QQ群号/二维码
* some text/mark about SYSUPT 一些关于SYSUPT的文字/标识
* ...

By the license, using these code is your privilege, but we also recommended you that **DO NOT GIVE TROUBLE TO OUR ORIGINAL SITE (SYSUPT)** is the basic respect. It’s very unprofessional and offensive to make the website online without knowing/editing the code.<br>
根据开源协议，使用这些代码是你的权利，但我们仍要提醒你**不要给原网站（即SYSUPT）惹麻烦** 是你应有的最基本的尊重。在没有搞清楚代码的情况下直接上线是非常不专业而且令人反感的行为。

## Build Manual

### 1.Change permission of config
```bash
chmod -R 777 config torrents bitbucket attachments douban/cache subs
```
### 2.Create Database
**Please use Mysql/MariaDB and DISABLE NO_ZERO_DATE, NO_ZERO_IN_DATE AND STRICT_TRANS_TABLES in `sql_mode`!**<br>
```bash
source sql/pt.sql
source sql/data.sql
```
### 3.Install required package
You can find required ext in `composer.json`, enable them in `php.ini`.
```bash
composer install
```
### 4.Edit allconfig.php
Copy *config/example.allconfig.php* to *config/allconfig.php*,<br/>
Edit BASIC array, which contains mysql configure.<br>
### 5.Something you need edit
Please edit the argument *domain*(.tjupt.org) to your domain name or just delete it in function logincookie(), set_langfolder_cookie() and logoutcookie() (You can find them in include/functions.php), otherwise you will not be able to log in.<br>
In vim, you can use this command:
```bash
vim include/functions.php
:%s/, "\/", ".tjupt.org"/, "\/"/g
```

And there're some edits in tracker(announce.php) for SYSUPT's network environment. You should edit it too. 
### 6.Login
Username: NexusPHP<br/>
Password: nexusphp
