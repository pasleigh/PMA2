<?php

/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 15/05/2016
 * Time: 22:16
 */
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('./backup_class.php')) or die('Cannot find this file to include: backup_class.php<BR>');

(@include_once('../setup_tables.php')) or die('Cannot setup database tables');
(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

(@include_once('./PasswordHash.php')) or die('Cannot open PasswordHash.php<BR>');

if (array_key_exists('op', $_REQUEST) == false) {
    $_REQUEST['op'] = null;
}
$OP = $_REQUEST['op'];

if (array_key_exists('submit_pwd', $_REQUEST) == false) {
    $_REQUEST['submit_pwd'] = null;
}
$submit_pwd = $_REQUEST['submit_pwd'];

$gzipit = false;
if (array_key_exists('gzip_chk', $_REQUEST)) {
    $gzipit = true;
}

$my_date_now = date('d-m-Y-H:i:s');
$my_school = str_replace(' ', '_', $school_title);
$sql_dump_filename = "database_dump_$my_school-$my_date_now.sql";
if ($gzipit) {
    $sql_dump_filename .= '.gzip';
}

$tmpsqlfile = tempnam(PMA_TMP_DIR, '');
$path = dirname($tmpsqlfile);

$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

if ($access) {
    if ($OP == 'dump') {
        $t_hasher = new PasswordHash(8, true);
        $check_pwd = $t_hasher->CheckPassword($submit_pwd, $hash_to_test_against);
        $check_pwd = true;
        if ($check_pwd) {
            /* not windows */
            //$filename = "backup-" . date("d-m-Y") . ".sql";
            header('Content-type: application/x-gzip');
            header('Content-Disposition: attachment; filename="' . $sql_dump_filename . '"');

            if ($gzipit) {
                $cmd = "mysqldump -u $dbusername --password=$password $dbName | gzip --best";
            } else {
                $cmd = "mysqldump -u $dbusername --password=$password $dbName";
            }

            passthru($cmd);

            $display_block = "<h2> Module database tables dumped to file: $sql_dump_filename</h2>";
        } else {
            echo 'This will only work for authenticated users.';
        }
    } else {
        // Form code
        $display_block = "<!DOCTYPE HTML>\n<HTML>\n<BODY>\n";
        $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]'>";
        //$display_block .= "Enter your password <input type='password' name='pwd' size=10 maxlength=30>\n";
        $display_block .= "<P>Enter the submit permission password <input type='password' name='submit_pwd' size=30 maxlength=50></P>\n";
        $display_block .= "\n<input type='hidden' name='op' value='dump'>";

        $display_block .= "<input type='checkbox' name='gzip_chk' id='gzip_chk' checked>Gzip output (Linux)";

        $display_block .= "<p><input type='submit' id='submitbutton_dump name='submit' value='Dump the database'></p>";

        $display_block .= '</form>';
        $display_block .= "\n</BODY>\n</HTML>\n";

        echo($display_block);
    }
} else {
    echo('You must be an admin user<BR>');
}
