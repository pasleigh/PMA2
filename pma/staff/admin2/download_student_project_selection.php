<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 04/05/2016
 * Time: 20:56
 */
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) or die('Cannot connect to the database<BR>');
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) or die('Cannot setup database tables<BR>');
(@include_once('./utility_functions.php')) or die('Cannot read utility_functions file<BR>');
(@include_once("$STAFF_DIR_VAL/markstore_functions.php")) or die('Cannot read markstore_functions file<BR>');

(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

$display_block = '';
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
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($is_module_admin || $admin_access) {
    $display_block .= "<h1>Downloading of students' selections</h1>";
    $display_block .= "<h2>Database tables: $master_name, $project_details and $selection</h2>";

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    $POST_OP = $_POST['op'];

    // see all the POST values
    //foreach ($_POST as $key => $value){
    //}

    if ($POST_OP == 'download_selection_csv') {
        //--- Download the student selection table ------------------------------//
        $csv_filename = 'student_selection_' . $module_prefix . $module1 . $module_postfix . '_' . $short_code . '_' . $year_begin . '-' . $year_end . '.csv';

        $tmpcsvfile = tempnam(PMA_TMP_DIR, '');
        $path = dirname($tmpcsvfile);
        //$sys_temp_dir = sys_get_temp_dir();
        //exit();

        $query = "SELECT * FROM $selection WHERE 1";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            echo("Unable to update data to the table $master_name<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            //exit();
        }

        $num = $results->rowCount();

        if ($num < 1) {
            //no records
            $display_block .= '<p><em>Sorry, there are no project selections to download!</em></p>';
        } else {
            //has records
            $fp = fopen($tmpcsvfile, 'w');

            // Title row
            $row[0] = 'sid';
            $row[1] = 'Name';
            $row[2] = 'Username';
            $row[3] = 'Choice 1';
            $row[4] = 'Choice 2';
            $row[5] = 'Choice 3';
            $row[6] = 'Choice 4';
            $row[7] = 'Choice 5';

            $ret = fputcsv($fp, $row);
            //fclose($fp);

            $rows = $results->fetchAll();
            $i_count = 0;
            foreach ($rows as $recs) {
                $row[0] = $recs['sid'];
                $row[1] = $recs['name'];
                $row[2] = $recs['username'];
                $row[3] = $recs['choice1'];
                $row[4] = $recs['choice2'];
                $row[5] = $recs['choice3'];
                $row[6] = $recs['choice4'];
                $row[7] = $recs['choice5'];

                //$fp = fopen('file.csv', 'w');
                $ret = fputcsv($fp, $row);
            }

            fclose($fp);

            header('Content-type: application/excel');
            header("Content-Disposition: attachment; filename=$csv_filename");
            readfile("$tmpcsvfile");

            exit();
            $display_block = "<h2> Student selections saved as CSV file to: $csv_filename</h2>";
        }
    } elseif ($POST_OP == 'download_staff_csv') {
        //------- Download the staff names file -------------------

        $csv_filename = 'staff_names_data_' . $module_prefix . $module1 . $module_postfix . '_' . $short_code . '_' . $year_begin . '-' . $year_end . '.csv';

        $tmpcsvfile = tempnam('dummy', '');
        $path = dirname($tmpcsvfile);

        $query = "SELECT * FROM $staff_names2 ORDER BY familyname";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            echo("Unable to update data to the table $students<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $num = $results->rowCount();
        if ($num < 1) {
            //no records
            $display_block .= '<p><em>Sorry, there are no student records to download!</em></p>';
        } else {
            //has records
            $fp = fopen($tmpcsvfile, 'w');

            // Title row
            $row[0] = 'Staff database ID';
            $row[1] = 'Title';
            $row[2] = 'First name';
            $row[3] = 'Family name';
            $row[4] = 'Email';
            $row[5] = 'username';

            $ret = fputcsv($fp, $row);
            //fclose($fp);

            $rows = $results->fetchAll();
            foreach ($rows as $recs) {
                $initials = $recs['staffid'];
                $title = $recs['title'];
                $firstname = $recs['firstname'];
                $familyname = $recs['familyname'];
                $email = $recs['email'];
                $username = $recs['username'];

                $row[0] = $initials;
                $row[1] = $title;
                $row[2] = $firstname;
                $row[3] = $familyname;
                $row[4] = $email;
                $row[5] = $username;

                //$fp = fopen('file.csv', 'w');
                $ret = fputcsv($fp, $row);
            }

            fclose($fp);

            header('Content-type: application/excel');
            header("Content-Disposition: attachment; filename=$csv_filename");
            readfile("$tmpcsvfile");

            exit();
        }
    } elseif ($POST_OP == 'download_projects_csv') {
    }

    $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

    // Display Choice table if a choice has previously been made
    $query = "SELECT * FROM $selection WHERE 1";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $rows = $results->fetchAll();
    //$display_block = "<TABLE  class='tableSimple' style='border:1px solid black;border-collapse:collapse;'>\n";
    $display_block .= "<TABLE id='selection' class='hover compact cell-border' cellspacing='0' width='100%'>\n";
    $display_block .= '
    <thead>
      <tr>
        <th>Name</th>
        <th>Username</th>
        <th>SID</th>
        <th>Choice 1</th>
        <th>Choice 2</th>
        <th>Choice 3</th>
        <th>Choice 4</th>
        <th>Choice 5</th>
      </tr>
    </thead>';
    $display_block .= "<tbody>\n";
    $proposal_count = 0;

    //if ($results->fetch(PDO::FETCH_NUM) > 0 ) {
    foreach ($rows as $recs) {
        $sid = $recs['sid'];
        $username = $recs['username'];
        $student_name = $recs['name'];
        $choice1 = $recs['choice1'];
        $choice2 = $recs['choice2'];
        $choice3 = $recs['choice3'];
        $choice4 = $recs['choice4'];
        $choice5 = $recs['choice5'];
        $display_block .= "<TR>\n";
        $display_block .= "<TD>$student_name</TD>";
        $display_block .= "<TD style='text-align: center;'>$username</TD>";
        $display_block .= "<TD style='text-align: center;'>$sid</TD>";
        $display_block .= "<TD style='text-align: center;'>$choice1</TD>";
        $display_block .= "<TD style='text-align: center;'>$choice2</TD>";
        $display_block .= "<TD style='text-align: center;'>$choice3</TD>";
        $display_block .= "<TD style='text-align: center;'>$choice4</TD>";
        $display_block .= "<TD style='text-align: center;'>$choice5</TD>";
        $display_block .= "</TR>\n";
    }
    //}
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "<h2>Download this Selection data to CSV file ...</H2>\n";
    $display_block .= "\n<input type='hidden' name='op' value='download_selection_csv'>";

    $display_block .= "<p><input class='btn btn-primary'  type='submit' id='submitbutton' name='submit' value='Download Selection Data to CSV.'></p>";

    $display_block .= "</form>\n";

    //---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Download Staff data to CSV file ...</H2>\n";
    $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>";
    $display_block .= "\n<input type='hidden' name='op' value='download_staff_csv'>";

    $display_block .= "<p><input class='btn btn-success' type='submit' id='submitbutton_download_staff' name='submit' value='Download Staff Data to CSV.'></p>";

    $display_block .= "</form>\n";

    //---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Download Project data to CSV file ...</H2>\n";
    $display_block .= "<form method='post' action='upload_download_project_proposals.php?pm_id=$pm_id'>";
    //$display_block .= "\n<input type='hidden' name='op' value='download_projects_csv'>";

    $display_block .= "<p><input class='btn btn-warning' type='submit' id='submitbutton_download_projects' name='submit' value='Go to page to download Project Data to CSV.'></p>";

    $display_block .= "</form>\n";
} else {
    $display_block .= 'You do not have the appropriate admin level<BR>';
    $display_block .= "<A href='../index.php'>Go back here</A><BR>";
}

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Download student selections - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../projectmarking_select_bs.php?pm_id=<?php echo("$pm_id") ?>" class="nav-link px-2 text-white-50">Module page</a></li>
            <li class="nav-item"><a target="_self" href="../session_modules_bs.php?year_begin= <?php echo("$year_begin") ?>" class="nav-link px-2 text-white-50">This year module list</a></li>
            <li class="nav-item"><a target="_self" href="./edit_module_settings.php?pm_id=<?php echo("$pm_id") ?>" class="nav-link px-2 text-white-50">Module admin page</a></li>
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Main admin page</a></li>
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
