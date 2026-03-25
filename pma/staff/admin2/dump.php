<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 15/05/2016
 * Time: 22:16
 */
 ini_set("max_execution_time", 300); // 5 mins
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./backup_class.php")) OR die("Cannot find this file to include: backup_class.php<BR>");

(@include_once("../setup_tables.php")) OR die("Cannot setup database tables");
(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

(@include_once("./PasswordHash.php")) OR die("Cannot open PasswordHash.php<BR>");

if (array_key_exists('op', $_REQUEST) == false) {$_REQUEST['op'] = null;}
$OP = $_REQUEST['op'];

if (array_key_exists('submit_pwd', $_REQUEST) == false) {
    $_REQUEST['submit_pwd'] = null;
}
$submit_pwd = $_REQUEST['submit_pwd'];

$my_date_now = date("d-m-Y-H:i:s");
$my_school = str_replace(' ', '_', $school_title);
$sql_dump_filename = "database_dump_$my_school-$my_date_now.sql";

$tmpsqlfile = tempnam(PMA_TMP_DIR, "X");
if(!$tmpsqlfile){
    echo("Not able to create temporary file.<BR>");
}
$path = dirname($tmpsqlfile);

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($access) {

    if ($OP == "dump") {
        $t_hasher = new PasswordHash(8, TRUE);
        $check_pwd = $t_hasher->CheckPassword($submit_pwd, $hash_to_test_against);
        $check_pwd = true;
        if ($check_pwd) {

            $dbb = new DBBackup(array(
                'driver' => 'mysql',
                'host' => $hostname,
                'user' => $dbusername,
                'password' => $password,
                'database' => $dbName
            ));

            $backup = $dbb->backup();

            $fp = fopen($tmpsqlfile, 'w');

            if (!$backup['error']) {
                // If there isn't errors, show the content
                // The backup will be at $var['msg']
                // You can do everything you want to. Like save in a file.
                // $fp = fopen('file.sql', 'a+');fwrite($fp, $backup['msg']);fclose($fp);

                $dumped = $backup['msg'];
                echo($dumped);

                fwrite($fp, $dumped);
                fclose($fp);
                header('Content-type: application/excel');
                header("Content-Disposition: attachment; filename=$sql_dump_filename");
                readfile("$tmpsqlfile");

                $display_block = "<h2> Module database tables dumped to file: $sql_dump_filename</h2>";
            } else {
                echo 'An error has ocurred.';
            }
        } else {
            echo 'This will only work for authenticated users.';
        }
    } else {
        // Form code
        //$display_block = "<!DOCTYPE HTML>\n<HTML>\n<BODY>\n";
        $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]'>";
        //$display_block .= "Enter your password <input type='password' name='pwd' size=10 maxlength=30>\n";
        $display_block .= "<P>Enter the submit permission password <input type='password' name='submit_pwd' size=30 maxlength=50></P>\n";

        $display_block .= "\n<input type='hidden' name='op' value='dump'>";

        $display_block .= "<p><input type='submit' id='submitbutton_dump' name='submit' value='Dump the database'></p>";

        $display_block .= "</form>";
        //$display_block .= "\n</BODY>\n</HTML>\n";

    }
}else{
    $display_block .= "<h2>You must have full Super Admin level to access the functions of this page</h2><BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}
?>
<HTML>
<HEAD>
    <?php
    (@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
    ?>
</HEAD>
<BODY>
<div id="wrapper">
    <div id="header">
        <span class="header">Submit a database request (<?php echo("$this_staff_username"); ?>)</span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div id="footer">
        <P class="footer_link">Go to the
            <a href="../index.php<?php echo("?year_begin=$year_begin") ?>">Year index page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>