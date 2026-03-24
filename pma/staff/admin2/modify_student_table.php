<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./utility_functions.php")) OR die("Cannot read utility_functions file<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot read markstore_functions file<BR>");

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
//$access = false;
//$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
//$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

$assess_part_description_text = "";

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db,$this_staff_username, $this_staff_full_name, $staff_names,$access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase );
if($is_module_admin){
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if($is_module_admin || $admin_access) {


    $display_block .= "<h1>Modify student details</h1>";
    $display_block .= "<h2>Database table $students</h2>";

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


    if ($POST_OP == "new") {
        $display_block .= "<P>A New student.</P>";

        $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">";
        $display_block .= "<table>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>SID</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"sid\"  required=\"required\" size=10 maxlength=30></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>name (format \"Family name, firstname\")</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"name\" required=\"required\" size=30 maxlength=30></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>username</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"username\" required=\"required\" size=9 maxlength=9></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>programme</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"programme\" required=\"required\" size=30 maxlength=60></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>Selected</TD>\n";
        $display_block .= "<TD><input type=\"checkbox\" name=\"sel_chk\" value=\"1\" >(1 or checked = not yet selected a project)</TD>\n";

        $display_block .= "</TR>\n";

        $display_block .= "</table>\n";

        $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_new\">";

        $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Submit details of this new student.\"></p>";

        $display_block .= "</form>";


        $display_block .= "<hr>\n";
    } elseif ($POST_OP == "submit_edited") {
        $student_count = $_POST['student_count'];
        for ($i = 1; $i <= $student_count; $i++) {

            //$display_block .= "Submitting: <BR>\n";
            $postid = "sid$i";
            $sid = addslashes($_POST[$postid]);
            $postid = "name$i";
            $name = addslashes($_POST[$postid]);
            $postid = "username$i";
            $username = addslashes($_POST[$postid]);
            $postid = "programme$i";
            $programme = addslashes($_POST[$postid]);
            //$inactive = $_POST['inactive_select'];

            $postid = "sel$i";
            if (array_key_exists($postid, $_POST) == true) {
                $_POST[$postid] = 1;
            } else {
                $_POST[$postid] = 0;
            }
            $sel_chk = $_POST[$postid];

            $postid = "delete$i";
            if (array_key_exists($postid, $_POST) == true) {
                $_POST[$postid] = 1;
            } else {
                $_POST[$postid] = 0;
            }
            $delete_chk = $_POST[$postid];

            //$display_block .= "$sid, $name, $username, $programme, $sel.... <BR>\n";
            if ($delete_chk == 0) {
                $query = "update $students set name='$name',username='$username',programme='$programme',sel='$sel_chk' where sid=$sid";
            } else {
                // Delete entry
                $query = "DELETE FROM $students WHERE sid=$sid";
            }

            //$display_block .= $query . "<BR>\n";
            //mysql_query($query) or die(" Unable to update data to the table $students . <BR>Query: $query");
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
            //$display_block .= "Updated: $i $fullname <br>\n";
        }
        $display_block .= "<B>All updated</B><BR><hr>\n";
    } elseif ($POST_OP == "submit_new") {
        //$display_block .= "Submitting <BR>\n";

        if (!array_key_exists('sid', $_POST)) {
            $_POST['sid'] = null;
        }
        if (!array_key_exists('name', $_POST)) {
            $_POST['name'] = null;
        }
        if (!array_key_exists('username', $_POST)) {
            $_POST['username'] = null;
        }
        if (!array_key_exists('programme', $_POST)) {
            $_POST['programme'] = null;
        }
        if (!array_key_exists('sel_chk', $_POST)) {
            $sel = 0;
        } else {
            $sel = 1;
        }
        $sid = addslashes($_POST['sid']);
        $name = addslashes($_POST['name']);
        $username = addslashes($_POST['username']);
        $programme = addslashes($_POST['programme']);
        //$sel = addslashes($_POST['sel']);

        $display_block .= "$sid, $name, $username, $programme, $sel.... <BR>\n";
        $query = "REPLACE INTO $students (sid,name,username,programme,sel) VALUES ('$sid','$name','$username','$programme','$sel')  ";
        //$display_block .= $query . "<BR>\n";
        //mysql_query($query) or die(" Unable to insert data to the table $students . <BR>Query: $query");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $display_block .= "<B>Done</B><BR><hr>\n";
    } elseif ($POST_OP == "upload_csv") {
        //print_r3("_REQUEST",$_REQUEST);
        //print_r3("_FILES",$_FILES[csv]);
        $csv_file_name = $_FILES['csv']['name'];
        $csv_file_size = $_FILES['csv']['size'];
        $csv_file_tmp = $_FILES['csv']['tmp_name'];
        $csv_file_type = $_FILES['csv']['type'];
        //echo("File name: $csv_file_name<BR>");
        //echo("File size: $csv_file_size<BR>");
        //echo("File tmp: $csv_file_tmp<BR>");
        //echo("File type: $csv_file_type<BR>");

        if ($csv_file_name != "") {
            $importer = new CSVparse();
            $CSV_DATA = $importer->parse_file($csv_file_tmp);
            //echo("File tmp: $csv_file_tmp<BR>");
            //print_r2($CSV_DATA);
            foreach ($CSV_DATA as $each_student_entry) {
                $csv_keys = array_keys($each_student_entry);

                $username = $each_student_entry[$csv_keys[2]];
                // If this is an email extract the username part
                $aruba_pos = strpos($username, "@");
                if ($aruba_pos === false) {
                    $username = $username;
                } else {
                    // Its an email so take everying in from of the aruba
                    $username = substr($username, 0, $aruba_pos);
                }

                $query = "REPLACE INTO $students (sid,name,username,programme) VALUES (";
                $query .= "'" . addslashes($each_student_entry[$csv_keys[0]]) . "', ";
                $query .= "'" . addslashes($each_student_entry[$csv_keys[1]]) . "', ";
                $query .= "'" . addslashes($username) . "', ";
                $query .= "'" . addslashes($each_student_entry[$csv_keys[3]]) . "') ";
                //echo("$query<BR>");
                //mysql_query($query) or die(" Unable to insert data to the table $students . <BR>Query: $query");
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }
                //print_r2($csv_keys);
                /*while (list($key, $value) = each ($each_member)) {

                    echo "$key: $value<br />";

                }*/

            }
        } else {
            $display_block .= "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a CSV file to upload.</H3>";
        }
    } elseif ($POST_OP == "download_csv") {
        $csv_filename = "student_data_" . $module_prefix . $module1 . $module_postfix . "_" . $short_code . "_" . $year_begin . "-" . $year_end . ".csv";

        $tmpcsvfile = tempnam("dummy", "");
        $path = dirname($tmpcsvfile);

        $query = "SELECT * FROM $students ORDER BY name";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $students<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $num = $results->rowCount();
        if ($num < 1) {
            //no records
            $display_block .= "<p><em>Sorry, there are no student records to download!</em></p>";
        } else {
            //has records
            $fp = fopen($tmpcsvfile, 'w');

            // Title row
            $row[0] = "SID";
            $row[1] = "Student name";
            $row[2] = "Username";
            $row[3] = "Programme";

            $ret = fputcsv($fp, $row);
            //echo " fputcsv return = $ret .<BR />";
            //fclose($fp);

            $rows = $results->fetchAll();
            foreach ($rows as $recs) {
                $sid = $recs['sid'];
                $student_name = $recs['name'];
                $email = $recs['username'];
                $is_username = strpos($email,'@');
                if($is_username === false){
                    $username = $email;
                }else{
                    $username = strstr($email, '@', true);
                }
                $programme = stripslashes($recs['programme']);

                $row[0] = $sid;
                $row[1] = $student_name;
                $row[2] = $username;
                $row[3] = $programme;

                //$fp = fopen('file.csv', 'w');
                $ret = fputcsv($fp, $row);
                //echo "$ret <br />";
            }

            fclose($fp);

            header('Content-type: application/excel');
            header("Content-Disposition: attachment; filename=$csv_filename");
            readfile("$tmpcsvfile");

            exit();
            $display_block = "<h2> Student data saved as CSV file to: $csv_filename</h2>";
        }
    }// end of if ($POST_OP == "submint_new")


    $display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

// Get the list of students from the students table
    $query = "select * from $students order by name";
//$get_list_res = mysql_query($get_list) or die(" Unable to create student name list from database. <BR>Error msg: " . mysql_error() . "<BR>Query: $get_list");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
//$display_block = "<TABLE  class=\"tableSimple\" style=\"border:1px solid black;border-collapse:collapse;\">\n";
    $display_block .= "<TABLE id=\"studentinfo\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
    $display_block .= "
    <thead>
      <tr>
        <th>sid</th>
        <th>name</th>
        <th>username</th>
        <th>programme</th>
        <th>sel</th>
        <th>delete</th>
      </tr>
    </thead>";
    $display_block .= "<tbody>\n";
    $student_count = 0;
    $rows = $results->fetchAll();
//echo("$query<BR>\n");
//print_r2($results);
//print_r2($rows);
//if ($results->fetch(PDO::FETCH_NUM) > 0 ) {
    foreach ($rows as $recs) {

        $sid = $recs['sid'];
        $name = $recs['name'];
        $username = $recs['username'];
        $programme = stripslashes($recs['programme']);
        $sel = stripslashes($recs['sel']);
        $student_count++;
        $display_block .= "<TR>\n";
        $display_block .= "<TD><input type=\"text\" name=\"sid$student_count\" required=\"required\" readonly value=\"$sid\"  size=9 maxlength=9></TD>";
        $display_block .= "<TD><input type=\"text\" name=\"name$student_count\" required=\"required\" value=\"$name\"  size=30 maxlength=30></TD>";
        $display_block .= "<TD><input type=\"text\" name=\"username$student_count\" required=\"required\" value=\"$username\"  size=10 maxlength=20></TD>";
        $display_block .= "<TD><input type=\"text\" name=\"programme$student_count\" required=\"required\" value=\"$programme\"  size=50 maxlength=60></TD>";

        $checked = "";
        if ($sel == 1) {
            $checked = "checked";
        }
        $display_block .= "<TD><input type=\"checkbox\" name=\"sel$student_count\" value=\"1\" $checked></TD>\n";

        $checked = "";
        $display_block .= "<TD><input type=\"checkbox\" name=\"delete$student_count\" value=\"1\" $checked></TD>\n";

        $display_block .= "</TR>\n";

    }
//}
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_edited\">";
    $display_block .= "\n<input type=\"hidden\" name=\"student_count\" value=\"$student_count\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit changes to student details.\"></p>";

    $display_block .= "</form>\n";

//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Make changes by uploading a CSV...</H2>\n";
    $display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\" enctype=\"multipart/form-data\">\n";
    $display_block .= "<P> You can alternatively upload a CSV file to update and <B>add</B> new students (you cannot delete students) </P>";
    $display_block .= "<P> Create a CSV with the headings:\"<B>SID</B>, <B>Name</B>,  <B>Username (or email address)</B>, <B>Programme</B>\" and load here.</P>";

    $display_block .= "<P></P><input name=\"csv\" type=\"file\" id=\"csv\" /></P>";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"upload_csv\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_csv\" name=\"submit\" value=\"Upload the selected CSV file\"></p>";

    $display_block .= "</form>\n";

//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Add a new student...</H2>\n";
    $display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"new\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Add details of a NEW student.\"></p>";

    $display_block .= "</form>\n";

//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Download Student data to CSV file ...</H2>\n";
    $display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"download_csv\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_download\" name=\"submit\" value=\"Download Student Data to CSV.\"></p>";

    $display_block .= "</form>\n";

} else {
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}

?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<script>
    $(function () {
        $("#studentinfo").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                //"ordering": false,
                //"info":     false,
                "searching": false
            }
        );
    })
</script>
<BODY>
<div id="wrapper">
    <div id="header">
        <span
            class="header">Edit Student details - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div id="footer">
        <P>Return to the
            <a href="../projectmarking_select.php?pm_id=<?php echo("$pm_id") ?>">Module page</a> -
            <a href="../session_modules.php?year_begin= <?php echo("$year_begin") ?>">Year index page</a> -
            <a href="./edit_module_settings.php?pm_id=<?php echo("$pm_id") ?>">Module admin page</a> -
            <a href="./index.php">Main admin page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
