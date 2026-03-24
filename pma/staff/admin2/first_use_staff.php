<?php
$first_use = true;

$debug = false;
if (array_key_exists('d', $_GET) == true || array_key_exists('debug', $_GET) == true ) {
    $debug = true;
}
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
if($debug){echo("titles stetup<BR>");}
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot find this file to include: setup_tables.php<BR>");
if($debug){echo("tables stetup<BR>");}

(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) OR die("Cannot find this file to include: connect_pdo.php<BR>");
(@include_once("$STAFF_DIR_VAL/table_functions.php")) OR die("Cannot include table_functions.php<BR>");

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

// Check that the images have been loaded
$query = "SELECT COUNT(*) FROM $header_images_table";
if($debug){echo("Query: $query<BR>");}

try{
    $row_count = $db->query($query)->fetchColumn();
    //echo("$header_images_table has $row_count rows<B>");
    if($row_count == 0){
        // Load the images from disc
        $header_images_dir = '/../images/';
        $header_images_full_dir = dirname(realpath(__FILE__)) . $header_images_dir;
        if($debug){echo("image file dir: $header_images_full_dir<BR>");}
        $handle = opendir($header_images_full_dir);
        while ($file_name = readdir($handle)) {
            if($debug){echo("image file: $file_name<BR>");}
            if ($file_name !== '.' && $file_name !== '..') {
                if (strpos($file_name, 'header.png')){
                    $full_file_name = ".$header_images_dir$file_name";
                    $img_file = file_get_contents($full_file_name);
                    $img_file_base64 = base64_encode($img_file);
                    $query = "INSERT INTO $header_images_table (header_image, filename) VALUES ('$img_file_base64','$file_name')";
                    try {
                        $result = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        $this_file = __FILE__;
                        $this_line = __LINE__;
                        echo "An Error occured accessing the database in function: $this_function<BR>\n";
                        echo "File: $this_file  near line: $this_line<BR>\n";
                        echo "An Error occured!<BR>";
                        $err_msg = $ex->getMessage();
                        echo("$err_msg <BR>");
                        exit();
                    }
                }
            }
        }
    }
}catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    $this_file = __FILE__;
    $this_line = __LINE__;
    echo "An Error occured accessing the database in function: $this_function<BR>\n";
    echo "File: $this_file  near line: $this_line<BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
// Does the definition table exist?
$definition_table_name = "definition";
try{
    $db->query("DESCRIBE $definition_table_name");
}catch (PDOException $ex) {
    //echo "$definition_table_name doesn't exist in database $dbName\n";
    //
    // Create it
    $create_table_query = "
                --
                -- Table structure for table `$definition_table_name`
                --

                CREATE TABLE IF NOT EXISTS `$definition_table_name` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `school_short_name` varchar(10) NOT NULL,
                  `school_title` varchar(50) NOT NULL,
                  `default_module_prefix` varchar(5) NOT NULL,
                  `header_image_name` varchar(50) NOT NULL,
                  `header_image_id` INT(11) DEFAULT 0,
                  `running_title` varchar(50) NOT NULL,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
            ";

    $initial_data_query = "INSERT INTO `$definition_table_name` (`id`, `school_short_name`, `school_title`, `default_module_prefix`, `header_image_name`, `running_title`) VALUES
            (1, 'civil', 'Civil Engineering', 'CIVE', 'leeds_eng_header.png', 'Project Marking App');";

    try {
        $setup = $db->prepare($create_table_query);
        $setup->execute();

        $setup = $db->prepare($initial_data_query);
        $setup->execute();
        $display_block .= "$definition_table_name table created<BR>\n";
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    // Probably the header image table does not exist
    // Check
    $table_name = "header_images";
    try{
        $db->query("DESCRIBE $table_name");
    }   catch (PDOException $ex) {     // Does not exist
        // Create it
        create_header_images_table_structure($db);
        $display_block .= "$table_name table created<BR>\n";
    }

    // Probably the projects_setup file does not exist
    // Check
    //echo("here<BR>\n");
    $table_name = "projects_setup";
    try{
        $db->query("DESCRIBE $table_name");
    }   catch (PDOException $ex) {     // Does not exist
        // Create it
        create_projects_setup_table_structure($db);
        $display_block .= "$table_name table created<BR>\n";
    }

    // Probably the staff_names table does not exist
    // Check
    //echo("here<BR>\n");
    $table_name = "staff_names";
    try{
        $db->query("DESCRIBE $table_name");
    }   catch (PDOException $ex) {     // Does not exist
        // Does not exist
        // Create it
        create_staff_names_table_structure($db);
        $display_block .= "$table_name table created<BR>\n";
    }

    // probably the admin users does not exist
    create_admin_users_table_structure($db);
    $display_block .= "Admin users table created<BR>\n";

    // Probably the template question tables do not exist
    create_assessqs_templates_egs($db);
    $display_block .= "Assessment table templates created<BR>\n";
    create_assessqs_cats_templates_egs($db);
    $display_block .= "Assessment table category templates created<BR>\n";

    create_assessment_criteria_tables($db);
    $display_block .= "Assessment criteria tables created<BR>\n";

    create_admin_users_module_table_structure($db);
    $display_block .= "Admin users module table created<BR>\n";

}


$display_block .= "<h1>Edit details for your School and yourself - the first member of staff to be entered in the database</h1>";
$staff_names_table = "staff_names";
$admin_users_table = "admin_users";

//$display_block = "<P>Database definitions table: $definition_table_name</P>";

if ($POST_OP == "") {
    $display_block .= "<h2>School details</h2>";

    $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]\">";

    $display_block .= "<table border='0'>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>School name in title: (e.g. Civil Engineering) </TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"school_title\"  value=\"Civil Engineering\" size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>School short name: (e.g. civil) </TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"school_short_name\" value=\"civil\" size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>School default module code: (e.g. CIVE) </TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"school_module_code\" value='CIVE' size=6 maxlength=6></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>Running title of this marking system: (e.g. Projects Marking App) </TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"school_running_title\" value='Projects Marking App' size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";
    $display_block .= "<TR>\n";
    $display_block .= "<TD>Select a header image:</TD>\n";
    $display_block .= "<TD>\n";
    // get the images
    /*
    $handle = opendir(dirname(realpath(__FILE__)) . '/../images/');
    while ($file = readdir($handle)) {
        if ($file !== '.' && $file !== '..') {
            if (strpos($file, 'header.png')) {
                $checked="";
                if(strpos($file, 'default')){
                    $checked = "checked";
                }
                $display_block .= "<div><input type='radio' name='header_image' value='$file' $checked><img src='../images/" . $file . "' border='2' /> $file<BR> </div>\n";
            }
        }
    }
*/
    // get the images
    $query = "SELECT * FROM $header_images_table WHERE 1";
    $school_header_image_id = 0;
    try {
        $results = $db->query($query);
        $rows = $results->fetchAll();
        $i = 1;
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

    $display_block .= "<h2>Your details</h2>";

    $display_block .= "<table>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Title</TD>\n";
    $display_block .= "<TD><select name=\"title_select\">\n";
    $selected = "selected";
    $display_block .= "<option value=\"Prof\" $selected>Prof</option>\n";
    $selected = "";
    $display_block .= "<option value=\"Dr\" $selected>Dr</option>\n";
    $display_block .= "<option value=\"Mrs\" $selected>Mrs</option>\n";
    $display_block .= "<option value=\"Ms\" $selected>Ms</option>\n";
    $display_block .= "<option value=\"Miss\" $selected>Miss</option>\n";
    $display_block .= "<option value=\"Mr\" $selected>Mr</option>\n";
    $display_block .= "</select></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Initials you could be refered to <BR>- e.g. Tom Selwyn Jones might be TSJ</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"initials\"  size=10 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>First name</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"firstname\" size=30 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Family name</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"familyname\" size=30 maxlength=40></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>email</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"email\" size=30 maxlength=40></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>username</TD>\n";
    $username = $_SERVER['REMOTE_USER'];
    $display_block .= "<TD><input type=\"text\" name=\"username\" value='$username' size=30 maxlength=40></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "<TR>\n";
    $display_block .= "<TD>Room</TD>\n";
    $display_block .= "<TD><input type=\"text\" name=\"roomX\" size=10 maxlength=30></TD>\n";
    $display_block .= "</TR>\n";

    $display_block .= "</TR>\n";

    $display_block .= "</table>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_new\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Submit these details.\"></p>";


    $display_block .= "</form>";


    $display_block .= "<hr>\n";
} elseif ($POST_OP == "submit_new") {

    $school_title = $_POST['school_title'];
    $school_module_code = $_POST['school_module_code'];
    $school_short_name = $_POST['school_short_name'];
    $school_running_title = $_POST['school_running_title'];

    $header_image_selected = $_POST['header_image_selected'];
    $header_image_info = explode("|",$header_image_selected);
    $school_header_image_name = $header_image_info[0];
    $school_header_image_id = $header_image_info[1];

    /*
        $initial_data_query = "INSERT INTO `$definition_table_name` ( `school_short_name`, `school_title`, `default_module_prefix`, `header_image_name`, `running_title`) VALUES
                ($school_short_name', '$school_title', '$school_module_code', '$header_image', '$school_running_title');";
    */
    $initial_data_query = "UPDATE $definition_table_name SET school_short_name='$school_short_name', school_title='$school_title',
                              default_module_prefix='$school_module_code',
                              header_image_name='$school_header_image_name', running_title='$school_running_title', header_image_id='$school_header_image_id'
                              WHERE id=1";
    //$display_block .= $query;
    try {
        $db->query($initial_data_query);

        $display_block .= "Submitted: <BR><BR>\n";
        $display_block .= "School short name: $school_short_name<BR>\n";
        $display_block .= "School title: $school_title <BR>\n";
        $display_block .= "Running title: $school_running_title<BR>\n";
        $display_block .= "Default module code: $school_module_code<BR>\n";
        $display_block .= "header image file: $school_header_image_name<BR>\n";
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        $this_file = __FILE__;
        $this_line = __LINE__;
        echo "An Error occured accessing the database in function: $this_function<BR>\n";
        echo "File: $this_file  near line: $this_line<BR>\n";
        echo(" Query = $initial_data_query<BR> \n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    //$staffid = $_POST['staffid'];
    $title = $_POST['title_select'];
    $initials = $_POST['initials'];
    $firstname = $_POST['firstname'];
    $familyname = $_POST['familyname'];
    $fullname = "$firstname $familyname";
    $email = $_POST['email'];
    $username = $_POST['username'];
    $room = $_POST['roomX'];
    $inactive = 0;

    $query = "INSERT INTO $staff_names_table (initials,title,firstname,familyname,fullname,email,username,room,inactive,admin_level) VALUES ('$initials','$title','$firstname','$familyname','$fullname','$email','$username','$room','$inactive',3)  ";
    //$display_block .= $query;
    try {
        $db->query($query);
        $display_block .= "Submitted: <BR><BR>\n";

        $display_block .= "Title: $title <BR>\n";
        $display_block .= "Full Name: $fullname <BR>\n";
        $display_block .= "First name: $firstname<BR>\n";
        $display_block .= "Family Name: $familyname<BR>\n";
        $display_block .= "Reference Initials: $initials<BR>\n";
        $display_block .= "Email: $email<BR>\n";
        $display_block .= "username: $username<BR>\n";
        $display_block .= "Room: $room<BR>\n";
        $display_block .= "Inactive flag: $inactive  <BR>\n";
    } catch (PDOException $ex) {
        echo "An Error occured!<BR>\n";
        $err_msg = $ex->getMessage();
        echo("$err_msg <BR>\n");
    }

    // And make this user a super admin
    $query = "INSERT INTO $admin_users_table (firstname,familyname,fullname,email,username,admin_level) VALUES ('$firstname','$familyname','$fullname','$email','$username','3')  ";
    //$display_block .= $query;
    try {
        $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured!<BR>\n";
        $err_msg = $ex->getMessage();
        echo("$err_msg <BR>\n");
    }

    $display_block .= "<BR><hr>\n";

    $display_block .= "<P>If the above looks right, let's go on to create the first module entry  </P><P><A href=\"./first_use_create_new_module_entry.php\">First use Module Setup</A></P><BR><hr>\n";
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
        <!--<P>Return to the
            <a href="./index.php">Admin index page</a>.-->
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
