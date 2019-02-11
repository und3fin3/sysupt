# TJUPT Code

[TJUPT](https://tjupt.org) (Tianjin University Private Tracker) is a site based on NexusPHP. All modifications made are here.

## Build Manual

### 1.Create Folders
    mkdir torrents bitbucket attachments imdb/cache subs
### 2.Change permission of config
    chmod -R 777 config torrents bitbucket attachments imdb/cache subs
### 3.Create Database
**Please use Mysql/MariaDB and DISABLE NO_ZERO_DATE, NO_ZERO_IN_DATE AND STRICT_TRANS_TABLES in `sql_mode`!**<br>
```bash
    source sql/pt.sql
    source sql/data.sql
```
### 4.Edit allconfig.php
Copy *config/example.allconfig.php* to *config/allconfig.php*,<br/>
Edit BASIC array, which contains mysql configure.<br>
If you want to use donate system, you should install dependency by composer:
```bash
    composer install
```
Then edit DONATION array in allconfig.php.
### 5.Something you need edit
Please edit the argument *domain*(.tjupt.org) to your domain name or just delete it in function logincookie(), set_langfolder_cookie() and logoutcookie() (You can find them in include/functions.php), otherwise you will not be able to log in.<br>
In vim, you can use this command:
```bash
    vim include/functions.php
    :%s/, "\/", ".tjupt.org"/, "\/"/g
```
### 6.Login
Username: NexusPHP<br/>
Password: nexusphp
