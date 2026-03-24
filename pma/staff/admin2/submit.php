<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot open staff_functions.php<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot open staff_functions.php<BR>");

(@include_once("./PasswordHash.php")) OR die("Cannot open PasswordHash.php<BR>");

if (array_key_exists('op', $_REQUEST) == false) {
    $_REQUEST['op'] = null;
}
$OP = $_REQUEST['op'];

if (array_key_exists('submit_pwd', $_REQUEST) == false) {
    $_REQUEST['submit_pwd'] = null;
}
$submit_pwd = $_REQUEST['submit_pwd'];

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($access) {

    if ($OP == "submit_sql") {
        if (array_key_exists('text_input_sql', $_REQUEST) == false) {
            $_REQUEST['text_input_sql'] = null;
        }
        $query = $_REQUEST['text_input_sql'];

        $display_block .= "Query: $query<BR>\n";
        try {
            $result = $db->query($query);
            $msg = "Query sucessfuly performed<BR>\n";
            $rows = $result->fetchAll();
            foreach($rows as $row){
                foreach($row as $rec) {
                    $display_block .= $rec . "<BR>\n";
                }
            }

        } catch (PDOException $ex) {
            $display_block .= "An Error occured running this query<BR>\n";
            $err_msg = $ex->getMessage();
            $msg =  "Err message: " . $ex->getMessage() . "<BR>\n";
        }
        $display_block .= $msg;

    } else if ($OP == "submit_pwd") {
        $t_hasher = new PasswordHash(8, TRUE);
        $check_pwd = $t_hasher->CheckPassword($submit_pwd, $hash_to_test_against);
        if ($check_pwd) {
                // Form code for SQL entry
                $display_block .= "\n";
                $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]'>";

                //$display_block .= "Enter the sql query: <input type='text' name='input_sql' size=50>\n";
                $display_block .= "Enter the sql query: \n";
                $display_block .= " <br><textarea rows='25' cols='100' name='text_input_sql' ></textarea> \n";
                $display_block .= "\n<input type='hidden' name='op' value='submit_sql'>";

                $display_block .= "<p><input type='submit' id='submitbutton_sql' name='submit' value='Submit SQL'></p>";

                $display_block .= "</form>";
        } else {
            $display_block .= "You did not enter the correct password(s).<BR>\n";
        }
    } else {
        // Form code
        $display_block .= "\n";
        $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]'>";
        /*$display_block .= "<P>Enter your password <input type='password' name='pwd' size=30 maxlength=30>\n</P>";*/
        $display_block .= "<P>Enter the submit permission password <input type='password' name='submit_pwd' size=30 maxlength=50></P>\n";
        $display_block .= "\n<input type='hidden' name='op' value='submit_pwd'>";

        $display_block .= "<p><input type='submit' id='submitbutton_pwd' name='submit' value='Submit passwords'></p>";

        $display_block .= "</form>";
    }

} else {
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
