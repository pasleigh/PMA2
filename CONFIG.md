# Configuration of PMA

## This document describes how to setup PMA on a new server

Requirements are
- MySQL database(s) [or cpmpatible e.g. MariaDB]
  - it has been tested on MariaDB 10.4 and MySQL 8.0
- PHP 
  - it has been tested on version 8.2

The code is on GitHub at https://github.com/pasleigh/PMA2

To install on a server:

1. Copy the `pma` folder to a new folder under the webserver path.
2. Copy the `db_credentials_pma.example.php` to `db_credentials_pma.php` a suitable location outside of the webserver path as it will contain databswe names and credentials. 
A typical place could be one place up the webserever file stucture. So issue the command

```
> cp db_credentials_pma.example.php ../db_credentials_pma.php 
```

3. Edit the `pma/config_main.php` file to specify local directory strucutre and loaction of the `db_credentials_pma.php` file.
4. Create the databases, one for each School. Put the names and credentials in the `db_credentials_pma.php` file


## Details
**Item 3:** In `pma/config_main.php`, in this block (lines 7-18) set the corretc `$base_dir`. (comment out or delete the ones not needed)

```
//############################################################
// Set the root folder of the system
//############################################################
// windows XAMPP
$base_dir = "C:\\xampp\\htdocs\\PMA2\\pma";
// mac
$base_dir = "/Applications/XAMPP/xamppfiles/htdocs/PMA2/pma";
// linux / RHEL
$base_dir = "/var/www/html/pma";
//############################################################
```


In the block lines 20-26, set the `$_creds_file` variable that points to the `db_credentials_pma.php` location. (Again, comment out or delete the lines not needed)


```
//############################################################
// Set the path to the db credentiuals file
// This should be outside the web server folder
//############################################################
$_creds_file = __DIR__ . '/../db_credentials_pma.php';
$_creds_file = __DIR__ . '/../../db_credentials_pma.php';
//############################################################
```

**Item 4:** 
Create the databases. You can use any names and usernames, but suggested ones are in the `db_credentials_pma.php` file. 
Enter the names and credentials in the `db_credentials_pma.php` file.

```
    'mech'        => ['db' => 'projects_mech',       'user' => 'projects_mech',       'pass' => 'CHANGE_ME', 'sess' => 'PMA_MECH'],
    'chem'        => ['db' => 'projects_chem',       'user' => 'projects_chem',       'pass' => 'CHANGE_ME', 'sess' => 'PMA_CHEM'],
    'civil'       => ['db' => 'projects_civil',      'user' => 'projects_civil',      'pass' => 'CHANGE_ME', 'sess' => 'PMA_CIVIL'],
    'elec'        => ['db' => 'projects_elec',       'user' => 'projects_elec',       'pass' => 'CHANGE_ME', 'sess' => 'PMA_ELEC'],
    'scape'       => ['db' => 'projects_scape',      'user' => 'projects_scape',      'pass' => 'CHANGE_ME', 'sess' => 'PMA_SCAPE'],
    'swjtu_civil' => ['db' => 'projects_swjtcivil',  'user' => 'projects_swjtcivil',  'pass' => 'CHANGE_ME', 'sess' => 'PMA_SWJTCIVIL'],
    'swjtu_mech'  => ['db' => 'projects_swjtmech',   'user' => 'projects_swjtmech',   'pass' => 'CHANGE_ME', 'sess' => 'PMA_SWJTMECH'],
    'swjtu_elec'  => ['db' => 'projects_swjtelec',   'user' => 'projects_swjtelec',   'pass' => 'CHANGE_ME', 'sess' => 'PMA_SWJTELEC'],
    'its'         => ['db' => 'projects_its',        'user' => 'projects_its',        'pass' => 'CHANGE_ME', 'sess' => 'PMA_ITS'],
    'env'         => ['db' => 'projects_env',        'user' => 'projects_env',        'pass' => 'CHANGE_ME', 'sess' => 'PMA_ENV'],
```


## Populate the Databases
While it is possible to leave the databases empty and let the app prompt for usernames and Schools etc. This is not the reccomended method in the current situation.

It is preferable that the databses are populated from .sql dumps from the existing databases. In a testing phase it is only necessaruy to populate one database.

Let's take the dump of the Civil Engineering School database `projects_cilil.sql`, and assume we are using the database names as suggested in the `db_credentials_pma.php` file. (The dump files do **NOT** contain a **USE database** or **CREATE DATABASE** statements so the database must exist and be specified in the mysql command.)
<small>
```
mysql -u root -p projects_civil < [path to the dumpfile folder]/projects_civil.sql
```
</small>
Test the PMA app as it should work for Civil Engineering now. If it does do the same to load dumps for the otehr schools.
