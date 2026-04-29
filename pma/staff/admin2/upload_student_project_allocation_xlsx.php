<?php
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');

(@include_once('./utility_functions.php')) or die('Cannot read utility_functions file<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot read markstore_functions file<BR>');
(@include_once('../staff_functions.php')) or die('Cannot read markstore_functions file<BR>');
(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __BASE_DIR__ . '/vendor/autoload.php';

$display_block = '';
$display_block .= $connect_msg;

$assess_part_description_text = '';

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = (int)$_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($is_module_admin || $admin_access) {
    $display_block .= '<h1>Upload Student Project Allocation</h1>';
    $display_block .= "<h2>Database table to be updated '$project_allocated'</h2>";

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    // if the form was submitted then enter it all into the database
    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = '';
    }

    //if ($access) {
    // see all the POST values
    //foreach ($_POST as $key => $value){
    //}
    //$allowed_tyle_ext = array('csv', 'CSV');

    $sid_col = 1;
    $pid_col = 2;
    $title_col = 3;
    $supervisor_col = 4;
    $second_marker_col = 5;

    $data_array = [];

    if ($POST_OP == 'upload_csv') {
        $xlsx_file_name = $_FILES['csv']['name'];
        $ext = pathinfo($xlsx_file_name, PATHINFO_EXTENSION);
        $xlsx_file_size = $_FILES['csv']['size'];
        $xlsx_file_tmp = $_FILES['csv']['tmp_name'];
        $xlsx_file_type = $_FILES['csv']['type'];
        $xlsx_file_error = $_FILES['csv']['error'];

        $file_info = "File name $xlsx_file_name<BR>";
        $file_info .= "File size $xlsx_file_size<BR>";
        $file_info .= "File name on server (tmp) $xlsx_file_tmp<BR>";
        $file_info .= "File type $xlsx_file_type<BR>";
        $file_info .= "File read earror message $xlsx_file_error<BR>";

        if (!$xlsx_file_error) {
            if ($xlsx_file_name != '') {
                $PHP_EXCEL_filetype = IOFactory::identify($xlsx_file_tmp);
                $file_info .= "PHPExcel file type: $PHP_EXCEL_filetype<BR>";

                $spreadsheet = IOFactory::load($xlsx_file_tmp);

                $worksheet = $spreadsheet->getSheet(0);

                $worksheet_name = $worksheet->getTitle();
                $highestRow = $worksheet->getHighestRow();
                $highestCol = $worksheet->getHighestColumn();
                $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
                $nrColumns = ord($highestCol) - 64;

                for ($row = 2; $row <= $highestRow ; $row++) {
                    $data_array = [];

                    $col = $sid_col;
                    $sid = $worksheet->getCell([$col, $row])->getValue();
                    $col = $pid_col;
                    $master_id = $worksheet->getCell([$col, $row])->getValue();
                    $col = $title_col;
                    $title = $worksheet->getCell([$col, $row])->getValue();
                    $col = $supervisor_col;
                    $supervisor_family_name = $worksheet->getCell([$col, $row])->getValue();
                    $col = $second_marker_col;
                    $second_marker_family_name = $worksheet->getCell([$col, $row])->getValue();
                    $supervisor_id = GetStaffIDFromFamilyNameAndInitial2($db, $supervisor_family_name, $staff_names2);
                    $second_marker_id = GetStaffIDFromFamilyNameAndInitial2($db, $second_marker_family_name, $staff_names2);
                    $num = CountAllocationTable($db, $project_allocated);

                    // Does this student have an entry in the allocations table
                    $query = "select * from $project_allocated where sid=:sid";
                    try {
                        $results = $db->prepare($query);
                        $results->execute([':sid' => $sid]);
                    } catch (PDOException $ex) {
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                    $num = $results->rowCount();

                    // REPLACE does a UPDATE or INSERT (Actually if exists it does a delete)
                    if ($num == 0) {
                        $query = "INSERT INTO $project_allocated (sid,masterid,title,supervisor_id,second_marker_id) VALUES (:sid, :master_id, :title, :supervisor_id, :second_marker_id)";
                        try {
                            $results = $db->prepare($query);
                            $results->execute([':sid' => $sid, ':master_id' => $master_id, ':title' => $title, ':supervisor_id' => $supervisor_id, ':second_marker_id' => $second_marker_id]);
                        } catch (PDOException $ex) {
                            error_log($ex->getMessage());
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                    } else {
                        $query = "UPDATE $project_allocated SET masterid=:master_id, title=:title, supervisor_id=:supervisor_id, second_marker_id=:second_marker_id WHERE sid=:sid";
                        try {
                            $results = $db->prepare($query);
                            $results->execute([':master_id' => $master_id, ':title' => $title, ':supervisor_id' => $supervisor_id, ':second_marker_id' => $second_marker_id, ':sid' => $sid]);
                        } catch (PDOException $ex) {
                            error_log($ex->getMessage());
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                    }
                    $query = "UPDATE $project_allocated SET `marker_p1m1_id`=:supervisor_id, `marker_p1m2_id`=:second_marker_id WHERE sid=:sid";
                    try {
                        $results = $db->prepare($query);
                        $results->execute([':supervisor_id' => $supervisor_id, ':second_marker_id' => $second_marker_id, ':sid' => $sid]);
                    } catch (PDOException $ex) {
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }

                    $query = "UPDATE $project_allocated SET `marker_p2m1_id`=:supervisor_id, `marker_p2m2_id`=:second_marker_id WHERE sid=:sid";
                    try {
                        $results = $db->prepare($query);
                        $results->execute([':supervisor_id' => $supervisor_id, ':second_marker_id' => $second_marker_id, ':sid' => $sid]);
                    } catch (PDOException $ex) {
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                    $query = "UPDATE $project_allocated SET `marker_p3m1_id`=:supervisor_id, `marker_p3m2_id`=:second_marker_id WHERE sid=:sid";
                    try {
                        $results = $db->prepare($query);
                        $results->execute([':supervisor_id' => $supervisor_id, ':second_marker_id' => $second_marker_id, ':sid' => $sid]);
                    } catch (PDOException $ex) {
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                    $query = "UPDATE $project_allocated SET `marker_p4m1_id`=:supervisor_id, `marker_p4m2_id`=:second_marker_id WHERE sid=:sid";
                    try {
                        $results = $db->prepare($query);
                        $results->execute([':supervisor_id' => $supervisor_id, ':second_marker_id' => $second_marker_id, ':sid' => $sid]);
                    } catch (PDOException $ex) {
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                    $query = "UPDATE $project_allocated SET `marker_p5m1_id`=:supervisor_id, `marker_p5m2_id`=:second_marker_id WHERE sid=:sid";
                    try {
                        $results = $db->prepare($query);
                        $results->execute([':supervisor_id' => $supervisor_id, ':second_marker_id' => $second_marker_id, ':sid' => $sid]);
                    } catch (PDOException $ex) {
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                }
            } else {
                $display_block .= "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a XLSX/CSV file to upload.</H3>";
            }
        }
    }// end of if ($POST_OP == "upload_csv")

    if ($POST_OP == 'upload_detailed_csv') {
        $xlsx_file_name = $_FILES['csv']['name'];
        $ext = pathinfo($xlsx_file_name, PATHINFO_EXTENSION);
        $xlsx_file_size = $_FILES['csv']['size'];
        $xlsx_file_tmp = $_FILES['csv']['tmp_name'];
        $xlsx_file_type = $_FILES['csv']['type'];
        $xlsx_file_error = $_FILES['csv']['error'];

        $file_info = "File name $xlsx_file_name<BR>";
        $file_info .= "File size $xlsx_file_size<BR>";
        $file_info .= "File name on server (tmp) $xlsx_file_tmp<BR>";
        $file_info .= "File type $xlsx_file_type<BR>";
        $file_info .= "File read earror message $xlsx_file_error<BR>";

        if (!$xlsx_file_error) {
            if ($xlsx_file_name != '') {
                $PHP_EXCEL_filetype = IOFactory::identify($xlsx_file_tmp);
                $file_info .= "PHPExcel file type: $PHP_EXCEL_filetype<BR>";

                $spreadsheet = IOFactory::load($xlsx_file_tmp);

                $worksheet = $spreadsheet->getSheet(0);

                $worksheet_name = $worksheet->getTitle();
                $highestRow = $worksheet->getHighestRow();
                $highestCol = $worksheet->getHighestColumn();
                $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
                $nrColumns = ord($highestCol) - 64;

                for ($row = 2; $row <= $highestRow ; $row++) {
                    $data_array = [];

                    $col = $sid_col;
                    $sid = $worksheet->getCell([$col, $row])->getValue();
                    $col = $pid_col;
                    $master_id = $worksheet->getCell([$col, $row])->getValue();
                    $col = $title_col;
                    $title = $worksheet->getCell([$col, $row])->getValue();

                    for ($i = 0; $i <= $num_parts; $i++) {
                        $col = $supervisor_col + $i * 2;
                        $supervisor_family_name = $worksheet->getCell([$col, $row])->getValue();
                        $col = $second_marker_col + $i * 2;
                        $second_marker_family_name = $worksheet->getCell([$col, $row])->getValue();
                        $supervisor_ida[$i] = GetStaffIDFromFamilyNameAndInitial2($db, $supervisor_family_name, $staff_names2);
                        $second_marker_ida[$i] = GetStaffIDFromFamilyNameAndInitial2($db, $second_marker_family_name, $staff_names2);
                    }

                    $num = CountAllocationTable($db, $project_allocated);

                    // Does this student have an entry in the allocations table
                    $query = "select * from $project_allocated where sid=:sid";
                    try {
                        $results = $db->prepare($query);
                        $results->execute([':sid' => $sid]);
                    } catch (PDOException $ex) {
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }
                    $num = $results->rowCount();

                    // REPLACE does a UPDATE or INSERT (Actually if exists it does a delete)
                    if ($num == 0) {
                        $query = "INSERT INTO $project_allocated (sid,masterid,title,supervisor_id,second_marker_id) VALUES (:sid, :master_id, :title, :supervisor_id, :second_marker_id)";
                        try {
                            $results = $db->prepare($query);
                            $results->execute([':sid' => $sid, ':master_id' => $master_id, ':title' => $title, ':supervisor_id' => $supervisor_ida[0], ':second_marker_id' => $second_marker_ida[0]]);
                        } catch (PDOException $ex) {
                            error_log($ex->getMessage());
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                    } else {
                        $query = "UPDATE $project_allocated SET masterid=:master_id, title=:title, supervisor_id=:supervisor_id, second_marker_id=:second_marker_id WHERE sid=:sid";
                        try {
                            $results = $db->prepare($query);
                            $results->execute([':master_id' => $master_id, ':title' => $title, ':supervisor_id' => $supervisor_ida[0], ':second_marker_id' => $second_marker_ida[0], ':sid' => $sid]);
                        } catch (PDOException $ex) {
                            error_log($ex->getMessage());
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                    }

                    for ($i = 1; $i <= $num_parts; $i++) {
                        $m1_entry = "marker_p$i" . 'm1_id';
                        $m2_entry = "marker_p$i" . 'm2_id';
                        $marker1_id = $supervisor_ida[$i];
                        $marker2_id = $second_marker_ida[$i];
                        $query = "UPDATE $project_allocated SET `$m1_entry`=:marker1_id, `$m2_entry`=:marker2_id WHERE sid=:sid";

                        try {
                            $results = $db->prepare($query);
                            $results->execute([':marker1_id' => $marker1_id, ':marker2_id' => $marker2_id, ':sid' => $sid]);
                        } catch (PDOException $ex) {
                            error_log($ex->getMessage());
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                    }
                }
            } else {
                $display_block .= "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a XLSX/CSV file to upload.</H3>";
            }
        }
    }// end of if ($POST_OP == "upload_detailed_csv")

    $num = CountAllocationTable($db, $project_allocated);
    if ($num == 0) {
        $display_block .= "<P>There are no entries in the $project_allocated table. This might indicate that the table has not yet been created. Upload a XLSX/CSV file to creat it.</P>";
    } else {
        // Get the list of allcoations from the students table
        $query = "select * from $project_allocated order by projectid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $rows = $results->fetchAll();
        $display_block .= "<TABLE id='studentallocation' class='hover compact cell-border' cellspacing='0' width='100%'>\n";
        $display_block .= '
    <thead>
      <tr>
        <th>Project ID</th>
        <th>SID</th>
        <th>Name</th>
        <th>Title</th>
        <th>Supervisor</th>
        <th>Second marker</th>
        <th>P1 marker</th>
        <th>P1 2nd marker</th>
        <th>P2 marker</th>
        <th>P2 2nd marker</th>
        <th>P3 marker</th>
        <th>P3 2nd marker</th>
        <th>P4 marker</th>
        <th>P4 2nd marker</th>
        <th>P5 marker</th>
        <th>P5 2nd marker</th>
      </tr>
    </thead>';
        $display_block .= "<tbody>\n";
        $student_count = 0;
        foreach ($rows as $recs) {
            $pid = $recs['projectid'];
            $sid = $recs['sid'];
            $title = $recs['title'];
            $supervisor_id = $recs['supervisor_id'];
            $supervisor_name = GetStaffNameFromID($db, $supervisor_id, $staff_names2, null);
            $second_marker_id = $recs['second_marker_id'];
            $second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff_names2, null);

            $s_get_list_query = "select * from $students where sid=:sid";
            try {
                $s_get_list_stmt = $db->prepare($s_get_list_query);
                $s_get_list_stmt->execute([':sid' => $sid]);
                $results = $s_get_list_stmt;
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            $s_recs = $results->fetch();
            $student_name = '';
            if ($s_recs) {
                $student_name = stripslashes($s_recs['name']);
            }

            $student_count++;
            $display_block .= "<TR>\n";
            $display_block .= "<TD><input type='text' name='pid$student_count' value='$pid'  size=4 maxlength=9 readonly></TD>";
            $display_block .= "<TD><input type='text' name='sid$student_count' value='$sid'  size=10 maxlength=10 readonly></TD>";
            $display_block .= "<TD><input type='text' name='name$student_count' value='$student_name'  size=20 maxlength=20 readonly></TD>";
            $display_block .= "<TD><input type='text' name='title$student_count' value='$title'  size=50 maxlength=100 readonly></TD>";
            $display_block .= "<TD><input type='text' name='supervisor$student_count' value='$supervisor_name'  size=20 maxlength=30 readonly></TD>";
            $display_block .= "<TD><input type='text' name='second_marker$student_count' value='$second_marker_name'  size=20 maxlength=30 readonly></TD>";

            for ($i = 1; $i <= 5; $i++) {
                $m1_entry = "marker_p$i" . 'm1_id';
                $m2_entry = "marker_p$i" . 'm2_id';
                $marker1_id = $recs[$m1_entry];
                $marker2_id = $recs[$m2_entry];
                $marker1_name = GetStaffNameFromID($db, $marker1_id, $staff_names2, null);
                $marker2_name = GetStaffNameFromID($db, $marker2_id, $staff_names2, null);
                $display_block .= "<TD style='font-size: small;'>$marker1_name</TD>";
                $display_block .= "<TD style='font-size: small;'>$marker2_name</TD>";
            }
            $display_block .= "</TR>\n";
        }
        $display_block .= "</tbody>\n";
        $display_block .= "</TABLE>\n";

        $display_block .= "\n<input type='hidden' name='op' value='submit_edited'>";
        $display_block .= "\n<input type='hidden' name='student_count' value='$student_count'>";
    }

    //---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Upload a Simple XLSX/CSV... (with the same marker/second marker for each part)</H2>\n";
    $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id' enctype='multipart/form-data'>\n";
    $display_block .= "<P> You can alternatively upload a XLSX/CSV file to update and <B>add</B> students' allocations (you cannot delete students) </P>";
    $display_block .= "<P> Create a XLSX/CSV with the headings:<B>SID</B>,<B>Project ID</B>, <B>Title</B>,  <B>Supervisor Surname (plus ', Initial' [e.g. Smith, A] if necessary)</B>, <B>Second marker Surname (plus ', Initial' if necessary)</B> and load here.</P>";
    $display_block .= '<P>(The Project ID is not used, but must exist in the file.)</P>';
    $display_block .= "<P></P><input class='btn btn-outline-primary' name='csv' type='file' id='csv' /></P>";
    $display_block .= "\n<input type='hidden' name='op' value='upload_csv'>";

    $display_block .= "<p><input class='btn btn-primary' type='submit' id='submitbutton_csv' name='submit' value='Upload a simple XLSX/CSV '></p>";

    $display_block .= "</form>\n";
    //---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Upload a detailed XLSX/CSV... (with different marker for each part)</H2>\n";
    $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id' enctype='multipart/form-data'>\n";
    $display_block .= "<P> You can alternatively upload a XLSX/CSV file to update and <B>add</B> students' allocations (you cannot delete students) </P>";
    $display_block .= '<P> The detailed breakdown of the imported allocation of markers will be show on this page ... you cannot edit them.</P>';
    $display_block .= "<P> Create a XLSX/CSV with the headings:<B>SID</B>, <B>Project ID</B>, <B>Title</B>,  <B>Supervisor Surname (plus ', Initial' [e.g. Smith, A] if necessary)</B>, <B>Second marker Surname (plus ', Initial' if necessary)</B>, <B>P1M1</B>, <B>P1M2</B>, <B>P2M1</B>, <B>P2M2</B>, <B>P3M1</B>, <B>P3M2</B>, <B>P4M1</B>, <B>P4M2</B>, <B>P5M1</B>, <B>P5M2</B>. These are the repeated pairs of markers' surnames for each part -  (NOTE you need BOTH the first and second marker - even if a part does not have a second marker) (You only need upto the number of parts i.e. if there are 2 parts, you will need 6 columns of staff names).</P>";
    $display_block .= '<P>(The Project ID is not used, but must exist in the file.)</P>';

    $display_block .= "<P></P><input class='btn btn-outline-success' name='csv' type='file' id='csv' /></P>";
    $display_block .= "\n<input type='hidden' name='op' value='upload_detailed_csv'>";

    $display_block .= "<p><input class='btn btn-success' type='submit' id='submitbutton_full_csv' name='submit' value='Upload a detailed XLSX/CSV '></p>";

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
        <span class="navbar-brand">Edit Student details - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
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
