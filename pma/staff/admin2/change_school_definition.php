<?php
$first_use = true;
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) OR die("Cannot find this file to include: connect_pdo.php<BR>");
(@include_once("$STAFF_DIR_VAL/table_functions.php")) OR die("Cannot include table_functions.php<BR>");

if (array_key_exists('op', $_POST) == false) {
    $_POST['op'] = null;
}
// if the form was submitted then enter it all into the database
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}

// see all the POST values
//foreach ($_POST as $key => $value){
//}

$display_block = "";

// Does the definition table exist?
$definition_table_name = "definition";
try{
    $db->query("DESCRIBE $definition_table_name");
}catch (PDOException $ex) {
    $display_block .= "Definition table coulld not be accessed<BR>\n";

}

$display_block .= "<h1>Edit details for your School and select the header image you would like to use</h1>";

//$display_block = "<P>Database definitions table: $definition_table_name</P>";

if ($POST_OP == "") {
    $query = "select * from $definition_table_name where id=1";
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
    $header_image_name = $res['header_image_name'];
    $school_running_title = $res['running_title'];

    $display_block .= "<h2>School details</h2>";

    $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]'>";

    $display_block .= "<table border='0'>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>School name in title: (e.g. Civil Engineering) </TD>\n";
    $display_block .= "<TD><input type='text' name='school_title'  value='$school_title' size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>School short name: (e.g. civil) </TD>\n";
    $display_block .= "<TD><input type='text' name='school_short_name' value='$school_short_name' size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>School default module code: (e.g. CIVE) </TD>\n";
    $display_block .= "<TD><input type='text' name='school_module_code' value='$school_module_code' size=6 maxlength=6></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>Running title of this marking system: (e.g. Project Marking App) </TD>\n";
    $display_block .= "<TD><input type='text' name='school_running_title' value='$school_running_title' size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>Select a header image:</TD>\n";
    $display_block .= "<TD></TD>\n";
    $display_block .= "</TR>\n";
    // get the images
    $handle = opendir(dirname(realpath(__FILE__)) . '/../images/');
    while ($file = readdir($handle)) {
        if ($file !== '.' && $file !== '..') {
            if (strpos($file, 'header.png')) {
                $checked="";
                if(strpos($file, 'default')){
                    $checked = "checked";
                }
                if($file == $header_image_name){
                    $checked = "checked";
                }
                //$display_block .= "<div><input type='radio' name='header_image' value='$file' $checked><img src='../images/" . $file . "' border='2' /> $file<BR> </div>\n";
                $img_file = file_get_contents("../images/$file");
                //$img_file_base64 = base64_encode($img_file);
                $display_block .= "<TR>\n";
                $display_block .= "<TD>\n";
                $display_block .= "$file\n";
                $display_block .= "</TD>\n";
                $display_block .= "<TD>\n";
                $display_block .= "<input type='radio' name='header_image' value='$file' $checked>";
                //$display_block .= '<img src="data:image/png;base64,' . $img_file_base64 .'" style="border-style: solid; border-width: 1px;" />' . "\n";
                $display_block .= '<img src="../images/' . $file .'" style="border-style: solid; border-width: 1px;" />' . "\n";
                //$display_block .= "<div><textarea rows='10' cols='50'>$img_file_base64</textarea></div>";
                $display_block .= "</TD>\n";
                $display_block .= "</TR>\n";
            }
        }
    }
    //$display_block .= "</TD>\n";
    //$display_block .= "</TR>\n";
    $display_block .= "</table>\n";

    $display_block .= "\n<input type='hidden' name='op' value='submit_new'>";

    $display_block .= "<p><input class='btn btn-primary' type='submit' id='submitbutton_new' name='submit' value='Submit Changes to School Settings.'></p>";

    $display_block .= "</form>";

    $display_block .= "<hr>\n";
} elseif ($POST_OP == "submit_new") {

    $school_title = $_POST['school_title'];
    $school_module_code = $_POST['school_module_code'];
    $school_short_name = $_POST['school_short_name'];
    $school_running_title = $_POST['school_running_title'];
    $header_image = $_POST['header_image'];

    $query = "UPDATE $definition_table_name SET school_short_name='$school_short_name', school_title='$school_title',
                              default_module_prefix='$school_module_code',
                              header_image_name='$header_image', running_title='$school_running_title'
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
<!DOCTYPE HTML>
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">School details</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Admin index page</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
        <?php echo $display_block; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
</html>
