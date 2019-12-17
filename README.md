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

### Prerequisite

The site is running on Debian 10.2.0, nginx 1.17.6, php-fpm 7.4, MySQL 8.0

Please setup the environment before running this site.

### 0. INSTALL PACKAGES

**Refer to the end of this README for package installation.**

### 1. Change permission of config
```bash
chmod -R 777 config torrents bitbucket attachments douban/cache subs
```
### 2. Create Database
**Please use Mysql/MariaDB and DISABLE NO_ZERO_DATE, NO_ZERO_IN_DATE AND STRICT_TRANS_TABLES in `sql_mode`!**<br>
```bash
source sql/pt.sql
source sql/data.sql
```
### 3. Install required package
You can find required ext in `composer.json`, enable them in `php.ini`.
```bash
composer install
```
### 4. Edit allconfig.php
Copy *config/example.allconfig.php* to *config/allconfig.php*,<br/>
Edit BASIC array, which contains mysql configure.<br>
### 5. Something you need edit
Please edit the argument *domain*(.tjupt.org) to your domain name or just delete it in function logincookie(), set_langfolder_cookie() and logoutcookie() (You can find them in include/functions.php), otherwise you will not be able to log in.<br>
In vim, you can use this command:
```bash
vim include/functions.php
:%s/, "\/", ".tjupt.org"/, "\/"/g
```

And there're some edits in tracker(announce.php) for SYSUPT's network environment. You should edit it too. 
### 6. Login
Username: NexusPHP<br/>
Password: nexusphp


## Appendix

The following commands are used to setup production environment on a Debian 10.2 VM.

```bash
# Install essential packages
sudo apt -y install lsb-release apt-transport-https ca-certificates git build-essential curl gnupg2 gcc make autoconf libc-dev pkg-config zlib1g-dev libmemcached-dev

# Install PHP 7.4
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update
sudo apt install php7.4-{common,mysql,xml,xmlrpc,curl,gd,imagick,cli,dev,imap,mbstring,opcache,soap,zip,intl,bcmath,fpm} -y

# Install Composer
EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
rm composer-setup.php
sudo mv composer.phar /usr/local/bin/composer

# Install nginx 1.17.6
echo "deb http://nginx.org/packages/mainline/debian `lsb_release -cs` nginx"     | sudo tee /etc/apt/sources.list.d/nginx.list
sudo apt update
sudo apt install nginx

# Install MySQL 8.0
wget https://repo.mysql.com//mysql-apt-config_0.8.13-1_all.deb
sudo dpkg -i mysql-apt-config_0.8.13-1_all.deb
sudo apt update
sudo apt -y install mysql-server
```