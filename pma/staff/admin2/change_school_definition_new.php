<?php
$first_use = true;
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) OR die("Cannot find this file to include: connect_pdo.php<BR>");
(@include_once("$STAFF_DIR_VAL/table_functions.php")) OR die("Cannot include table_functions.php<BR>");
(@include_once("$STAFF_DIR_VAL/markstore_functions.php")) OR die("Cannot include markstore_functions.php<BR>");

if (array_key_exists('op', $_POST) == false) {
    $_POST['op'] = null;
}
// if the form was submitted then enter it all into the database
//echo("OP=$_POST['op'] . <BR>\n");
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}
//echo("POST_OP=$POST_OP <BR>\n");

// see all the POST values
//foreach ($_POST as $key => $value){
//  echo "{$key} = {$value}<BR>\r\n";
//}

$display_block = "";

// Does the definition table exist?
$definition_table_name = "definition";
try{
    $db->query("DESCRIBE $definition_table_name");
}catch (PDOException $ex) {
    $display_block .= "Definition table coulld not be accessed<BR>\n";

}

try {
    // Check that the column does not exist
    //$query = "SHOW COLUMNS FROM $definition_table_name LIKE 'header_image_id'";
    //$res = $db->query($query);
    //$count = $res->rowCount();
    //echo("count = $count<BR>");
    //if($count == 0){
    if(!DoesColumnExistInTable($db,$definition_table_name,'header_image_id')){
        $query = "ALTER TABLE $definition_table_name ADD `header_image_id` INT(11) DEFAULT 0";
        $db->query($query);
    }
} catch (Exception $e) {
    echo("\nWarning $definition_table_name header_image_id already exists<BR>\n");
    echo("\nError message: $e\n");
}

$display_block .= "<h1>Edit details for your School and select the header image you would like to use</h1>";

//$display_block = "<P>Database definitions table: $definition_table_name</P>";

if ($POST_OP == "") {
    $query = "SELECT * FROM $definition_table_name WHERE id=1";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database table $definition_table_name in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $res = $results->fetch();
    $school_short_name = $res['school_short_name'];
    $school_module_code = $res['default_module_prefix'];
    $school_title = $res['school_title'];
    $school_header_image_id = $res['header_image_id'];
    $school_running_title = $res['running_title'];

    $display_block .= "<h2>School details</h2>";

    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">";

    $display_block .= "<table border='0'>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>School name in title: (e.g. Civil Engineering) </TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"school_title\"  value=\"$school_title\" size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>School short name: (e.g. civil) </TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"school_short_name\" value=\"$school_short_name\" size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>School default module code: (e.g. CIVE) </TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"school_module_code\" value='$school_module_code' size=6 maxlength=6></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>Running title of this marking system: (e.g. Project Marking App) </TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"school_running_title\" value='$school_running_title' size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>Select a header image:</TD>\n";
    $display_block .= "<TD>\n";
    // get the images
    $query = "SELECT * FROM $header_images_table WHERE 1";
    try {
        $results = $db->query($query);
        $rows = $results->fetchAll();
        $i=1;
        foreach ($rows as $recs) {
            $header_image = $recs['header_image'];
            $header_image_id = $recs['id'];
            $header_image_filename = $recs['filename'];
            //$img_file_base64 = base64_encode($header_image);
            $checked = "";
            if($school_header_image_id == $header_image_id){$checked = "checked";}
            $display_block .= "<div>\n<input type='radio' name='header_image_selected' value='$header_image_filename|$header_image_id' $checked> id = $header_image_id\n";
            //$display_block .= '<img src="data:image/png;base64,' . $header_image .'" style="border-style: solid; border-width: 1px;" />';
            $display_block .= '<img src="../images/' . $header_images[$i] .'" style="border-style: solid; border-width: 1px;" />';
            $display_block .= '</div><BR>';
            $i++;
        }

    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database table  $definition_table_name in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $display_block .= "</TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "</table>\n";

    //$display_block .= "<p>Or select a new image for upload: <a href='upload_header_image.php'>Upload a new header image</a></p>";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_new\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Submit School Settings.\"></p>";


    $display_block .= "</form>";


    $display_block .= "<hr>\n";
} elseif ($POST_OP == "submit_new") {

    $school_title = $_POST['school_title'];
    $school_module_code = $_POST['school_module_code'];
    $school_short_name = $_POST['school_short_name'];
    $school_running_title = $_POST['school_running_title'];
    $header_image_selected = $_POST['header_image_selected'];
    $header_image_info = explode("|",$header_image_selected);

    $query = "UPDATE $definition_table_name SET school_short_name='$school_short_name', school_title='$school_title',
                              default_module_prefix='$school_module_code',
                              header_image_name='$header_image_info[0]', running_title='$school_running_title', header_image_id='$header_image_info[1]'
                              WHERE id=1";

    //$display_block .= $query;
    try {
        $db->query($query);

        $display_block .= "Submitted: <BR><BR>\n";
        $display_block .= "School short name: $school_short_name<BR>\n";
        $display_block .= "School title: $school_title <BR>\n";
        $display_block .= "Running title: $school_running_title<BR>\n";
        $display_block .= "Default module code: $school_module_code<BR>\n";
        $display_block .= "header image file: $header_image<BR>\n";
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database table  $definition_table_name in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }


    $display_block .= "<BR>\n";

}// end of if ($POST_OP == "edit")


?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>

<BODY>
<div id="wrapper">
    <div id="header">
        <span class="header">First use details</span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div id="footer">
        <P>Return to the
            <a href="./index.php">Admin index page</a>.
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
