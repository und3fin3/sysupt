# TJUPT Code

[TJUPT](https://tjupt.org) (Tianjin University Private Tracker) is a site based on NexusPHP. All modifications made are here.

## Build Manual

### 1.Create Folders
    mkdir torrents bitbucket attachments imdb/cache subs
### 2.Change permission of config
    chmod -R 777 config torrents bitbucket attachments imdb/cache subs
### 3.Create Database
**Please use Mysql/MariaDB**<br/>
Run *sql/pt.sql* to create table structure<br/>
Then run *sql/data.sql* to import basic data
### 4.Edit allconfig.php
Copy *config/example.allconfig.php* to *config/allconfig.php*,<br/>
Edit BASIC array, which contains mysql configure
### 5.Something you need know
>Please edit the argument *domain*(.tjupt.org) to your domain name or just delete it in function logincookie(), set_langfolder_cookie() and logoutcookie() (You can find them in include/functions.php), otherwise you will not be able to log in.
### 6.Login
Username: NexusPHP<br/>
Password: nexusphp
