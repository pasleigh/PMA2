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

//----------------------------------------------------------------------------------------------------
//$display_block .= "<h1 style='background-color: #f1c40f; text-align: center;  font-size: xx-large'>THIS PAGE IS STILL UNDER CONSTRUCTION<BR>DO NOT CLICK THE BUTTONS</h1>";
//$access = false;
//----------------------------------------------------------------------------------------------------

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


    $display_block .= "<h1>Upload and Downloading of projects </h1>";
    $display_block .= "<h2>Database tables: $master_name and $project_details</h2>";

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    $POST_OP = $_POST['op'];

    if (array_key_exists('proposal_count', $_POST) == false) {
        $_POST['proposal_count'] = null;
    }

// see all the POST values
//foreach ($_POST as $key => $value){
//  echo "{$key} = {$value}<BR>\r\n";
//}

    if ($POST_OP == "upload_csv") {
        $allowed_tyle_ext = array('csv','CSV');
        //print_r3("_REQUEST",$_REQUEST);
        //print_r3("_FILES",$_FILES[csv]);
        $csv_file_name = $_FILES['csv']['name'];
        $ext = pathinfo($csv_file_name, PATHINFO_EXTENSION);
        $csv_file_size = $_FILES['csv']['size'];
        $csv_file_tmp = $_FILES['csv']['tmp_name'];
        $csv_file_type = $_FILES['csv']['type'];
        //echo("File name: $csv_file_name<BR>");
        //echo("File size: $csv_file_size<BR>");
        //echo("File tmp: $csv_file_tmp<BR>");
        //echo("File type: $csv_file_type<BR>");

        $is_csv = true;
        if(!in_array($ext,$allowed_tyle_ext)){
            $is_csv = false;
            $display_block .= "<H3>The file: $csv_file_name is NOT .csv file. <BR>Proposals were not uploaded. Choose another file.</H3>";
        }

        if($csv_file_name != "" && $is_csv) {
            //echo("File name: $csv_file_name<BR>");
            $importer = new CSVparse();
            $CSV_DATA = $importer->parse_file($csv_file_tmp);
            //echo("File tmp: $csv_file_tmp<BR>");
            //print_r2($CSV_DATA);
            foreach ($CSV_DATA as $each_proposal_entry) {
                $csv_keys = array_keys($each_proposal_entry);

                $query = "INSERT INTO $master_name (date_added,date_modified,f_title,f_supervisor,f_supervisor_id) VALUES (";
                //$query .= "'" . addslashes($each_proposal_entry[$csv_keys[0]]) . "', "; // id
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[1]]) . "', ";
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[2]]) . "', ";
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[3]]) . "', ";
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[4]]) . "', ";
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[5]]) . "') ";
                //echo("$query<BR>");
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }
                // get the last record ID
                $new_pid = $db->lastInsertId();

                $query = "INSERT INTO $project_details (master_id, f_assocstaff,f_assocstaff_id,f_outline,f_progs,f_PGprojects) VALUES (";
                $query .= "'" . addslashes($new_pid) . "', ";
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[6]]) . "', ";
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[7]]) . "', ";
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[8]]) . "', ";
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[9]]) . "', ";
                $query .= "'" . addslashes($each_proposal_entry[$csv_keys[10]]) . "') ";
                //echo("$query<BR>");
                //mysql_query($query) or die(" Unable to insert data to the table $project_details . <BR>Query: $query");
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
            $display_block .= "<H3>The csv file: $$csv_file_name has been uploaded.</H3>";
            $display_block .= "Please visit the <A href='./edit_project_proposals_quick.php?pm_id=$pm_id'>Minor modification and delrtiion of project proposals<A> to check and fix any upload errors.<BR><HR><BR>";
        }else{
            $display_block .= "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a CSV file to upload.</H3>";
        }
    }elseif ($POST_OP == "download_csv") {
        $csv_filename = "project_proposals_" . $module_prefix . $module1 . $module_postfix . "_" . $short_code . "_" . $year_begin . "-" . $year_end . ".csv";

        // Construct the list of proposals to NOT download
        $num_proposals = $_POST['proposal_count'];
        $num_not_exported = 0;

        for($i = 0; $i < $num_proposals ; $i++){
            // Check the export flag
            $chk_name = "export$i";
            $pid_name = "pid$i";
            $chk_value = 0;
            if(isset($_POST[$chk_name])){
                $chk_value = $_POST[$chk_name];
                $pid_value = $_POST[$pid_name];
            }
            $export_flag[$i] = $chk_value;
            if($chk_value != 1){
                $num_not_exported++;
            }
        }

        $sys_temp_dir = sys_get_temp_dir();
        $temp_dir = '/tmp';
        $temp_dir = $sys_temp_dir;
        $tmpcsvfile = tempnam($temp_dir,'');
        $path = dirname($tmpcsvfile);
        //$sys_temp_dir = sys_get_temp_dir();
        //echo("temp filename $tmpcsvfile<BR>\n");
        //echo("path to temp file: $path<BR>\n");
        //echo("path to sys temp dir: $sys_temp_dir<BR>\n");
        //echo  is_writable($sys_temp_dir) ? "Temp dir is writable" : "Temp dir is not writable";
        //exit();

        $query = "SELECT * FROM $master_name ORDER BY id";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $master_name<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $num = $results->rowCount();
        if ($num < 1) {
            //no records
            $display_block .= "<p><em>Sorry, there are no project proposals to download!</em></p>";
        } else {
            //has records
            $fp = fopen($tmpcsvfile, 'w');

            // Title row
            $row[0] = "PID";
            $row[1] = "Date entered";
            $row[2] = "Date modified";
            $row[3] = "Title";
            $row[4] = "Supervisor";
            $row[5] = "Supervisor ID";
            $row[6] = "Second marker";
            $row[7] = "Second marker ID";
            $row[8] = "Project Description";
            $row[9] = "Programmes associated";
            $row[10] = "Number of places for this project";

            $ret = fputcsv($fp, $row);
            //echo " fputcsv return = $ret .<BR />";
            //fclose($fp);

            $rows = $results->fetchAll();
            $i_count = 0;
            foreach ($rows as $recs) {
                if($export_flag[$i_count] == 1) {
                    $pid = $recs['id'];
                    $date_added = $recs['date_added'];
                    $date_modified = $recs['date_modified'];
                    $project_title = $recs['f_title'];
                    $supervisor = stripslashes($recs['f_supervisor']);
                    $supervisor_id = stripslashes($recs['f_supervisor_id']);

                    $row[0] = $pid;
                    $row[1] = $date_added;
                    $row[2] = $date_modified;
                    $row[3] = $project_title;
                    $row[4] = $supervisor;
                    $row[5] = $supervisor_id;

                    // get the project details
                    $query = "SELECT * FROM $project_details WHERE master_id=$pid";
                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo("An Error occured accessing the database in function: $this_function <BR>\n");
                        echo("Unable to update data to the table $project_details<BR>\n");
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                    $num = $results->rowCount();
                    $recs_details = $results->fetch();
                    if ($num < 1) {
                        //no records
                        $display_block .= "<p><em>Sorry, there are no project details to download for project $pid, $project_title!</em></p>";
                    } else {
                        $assocstaff = $recs_details['f_assocstaff'];
                        $assocstaff_id = $recs_details['f_assocstaff_id'];
                        $outline = $recs_details['f_outline'];
                        $assoc_progs = $recs_details['f_progs'];
                        $num_projects_offered = $recs_details['f_PGprojects'];

                        $row[6] = $assocstaff;
                        $row[7] = $assocstaff_id;
                        $row[8] = $outline;
                        $row[9] = $assoc_progs;
                        $row[10] = $num_projects_offered;
                    }

                    //$fp = fopen('file.csv', 'w');
                    $ret = fputcsv($fp, $row);
                    //echo "$ret <br />";
                }
                $i_count++;
            }

            fclose($fp);

            header('Content-type: application/excel');
            header("Content-Disposition: attachment; filename=$csv_filename");
            readfile("$tmpcsvfile");

            exit();
            $display_block = "<h2> Proposals saved as CSV file to: $csv_filename</h2>";
        }
    }// end of if ($POST_OP == "submint_new")


    $display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

// Get the list of proposals from the $master_name table
    $query = "SELECT * FROM $master_name ORDER BY id";
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
    $display_block .= "<TABLE id=\"proposalinfo\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
    $display_block .= "
    <thead>
      <tr>
        <th>Project id</th>
        <th>Title</th>
        <th>Supervisor</th>
        <th>Checked=Export</th>
      </tr>
    </thead>";
    $display_block .= "<tbody>\n";
    $proposal_count = 0;
    $rows = $results->fetchAll();
//echo("$query<BR>\n");
//print_r2($results);
//print_r2($rows);
//if ($results->fetch(PDO::FETCH_NUM) > 0 ) {
    foreach ($rows as $recs) {

        $pid = $recs['id'];
        $project_title = $recs['f_title'];
        $supervisor_name = $recs['f_supervisor'];
        $supervisor_id = $recs['f_supervisor_id'];
        $display_block .= "<TR>\n";
        $display_block .= "<TD><input type=\"hidden\" name=\"pid$proposal_count\" value=\"$pid\">$pid</TD>";
        $display_block .= "<TD>$project_title</TD>";
        $display_block .= "<TD>$supervisor_name</TD>";

        $checked = "";
        $sel = 1;
        if ($sel == 1) {
            $checked = "checked";
        }
        $display_block .= "<TD><input type=\"checkbox\" name=\"export$proposal_count\" value=\"1\" $checked></TD>\n";

        $display_block .= "</TR>\n";

        $proposal_count++;
    }
//}
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"download_csv\">";
    $display_block .= "\n<input type=\"hidden\" name=\"proposal_count\" value=\"$proposal_count\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Export proposals to CSV.\"></p>";

    $display_block .= "</form>\n";

//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Upload a CSV...</H2>\n";
    $display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\" enctype=\"multipart/form-data\">\n";
    $display_block .= "<P> You can upload a CSV file to update and <B>add</B> proposals (you cannot delete proposals this way.) </P>";
    $display_block .= "<P> The CSV should be the same format as the one downloaded from this same page. <BR>It is useful, for example,  for copying project proposals from a previous year to the current.</P>";

    $display_block .= "<P></P><input name=\"csv\" type=\"file\" id=\"csv\" /></P>";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"upload_csv\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_csv\" name=\"submit\" value=\"Upload a CSV \"></p>";

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
        $("#proposalinfo").dataTable(
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
            class="header">Up/Down load proposals - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
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
