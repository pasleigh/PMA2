<?php
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');

(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('./utility_functions.php')) or die('Cannot read utility_functions file<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot read markstore_functions file<BR>');

(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

use PhpOffice\PhpSpreadsheet\IOFactory;

require_once __BASE_DIR__ . '/vendor/autoload.php';

// True if $value represents a whole number (accepts int/float cells from the
// spreadsheet as well as numeric-looking strings from a CSV).
function isIntegerSid($value)
{
    if (is_int($value)) {
        return true;
    }
    if (is_float($value)) {
        return fmod($value, 1.0) === 0.0;
    }
    $trimmed = trim((string)$value);
    return $trimmed !== '' && ctype_digit($trimmed);
}

// Reads sid/name/username/programme rows out of an uploaded student XLSX/CSV file.
// Shared by the preview step and the confirm step so both parse the file identically.
function parseStudentSpreadsheetFile($filePath)
{
    $sid_col = 1;
    $student_name_col = 2;
    $username_col = 3;
    $prog_col = 4;

    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getSheet(0);
    $highestRow = $worksheet->getHighestRow();

    $rows = [];
    for ($row = 2; $row <= $highestRow; $row++) {
        $username = $worksheet->getCell([$username_col, $row])->getValue();
        // If this is an email extract the username part
        $aruba_pos = strpos((string)$username, '@');
        if ($aruba_pos === false) {
            continue;
        }
        $username = substr($username, 0, $aruba_pos);

        $sid = $worksheet->getCell([$sid_col, $row])->getValue();
        $student_name = $worksheet->getCell([$student_name_col, $row])->getValue();
        $programme = $worksheet->getCell([$prog_col, $row])->getValue();

        $rows[] = [
            'sid' => $sid,
            'name' => $student_name,
            'username' => $username,
            'programme' => $programme,
            'sid_valid' => isIntegerSid($sid),
        ];
    }
    return $rows;
}

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
    $display_block .= '<h1>Modify student details</h1>';
    //$display_block .= "<h2>Database table $students</h2>";

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    // if the form was submitted then enter it all into the database
    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = '';
    }

    // see all the POST values
    //foreach ($_POST as $key => $value){
    //}

    if ($POST_OP == 'new') {
        $display_block .= '<P>A New student.</P>';

        $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>";
        $display_block .= "<table class='table table-bordered table-sm w-auto'>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD class='align-middle'>SID</TD>\n";
        $display_block .= "<TD><input type='text' name='sid' class='form-control' required='required' maxlength=30></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD class='align-middle'>Name (format 'Family name, Firstname')</TD>\n";
        $display_block .= "<TD><input type='text' name='name' class='form-control' required='required' maxlength=30></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD class='align-middle'>Username</TD>\n";
        $display_block .= "<TD><input type='text' name='username' class='form-control' required='required' maxlength=9></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD class='align-middle'>Programme</TD>\n";
        $display_block .= "<TD><input type='text' name='programme' class='form-control' required='required' maxlength=60></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD class='align-middle'>Selected</TD>\n";
        $display_block .= "<TD class='align-middle'><input type='checkbox' name='sel_chk' value='1'> <small class='text-muted'>(checked = not yet selected a project)</small></TD>\n";

        $display_block .= "</TR>\n";

        $display_block .= "</table>\n";

        $display_block .= "\n<input type='hidden' name='op' value='submit_new'>";

        $display_block .= "<p><input class='btn btn-primary' type='submit' id='submitbutton_new' name='submit' value='Submit details of this new student.'></p>";

        $display_block .= '</form>';

        $display_block .= "<hr>\n";
    } elseif ($POST_OP == 'submit_edited') {
        $student_count = (int)$_POST['student_count'];
        for ($i = 1; $i <= $student_count; $i++) {
            $postid = "sid$i";
            $sid = $_POST[$postid];
            $postid = "name$i";
            $name = $_POST[$postid];
            $postid = "username$i";
            $username = $_POST[$postid];
            $postid = "programme$i";
            $programme = $_POST[$postid];

            $postid = "sel$i";
            $sel_chk = array_key_exists($postid, $_POST) ? 1 : 0;

            $postid = "delete$i";
            $delete_chk = array_key_exists($postid, $_POST) ? 1 : 0;

            try {
                if ($delete_chk == 0) {
                    $stmt = $db->prepare("UPDATE $students SET name=:name, username=:username, programme=:programme, sel=:sel WHERE sid=:sid");
                    $stmt->execute([':name' => $name, ':username' => $username, ':programme' => $programme, ':sel' => $sel_chk, ':sid' => $sid]);
                } else {
                    $stmt = $db->prepare("DELETE FROM $students WHERE sid=:sid");
                    $stmt->execute([':sid' => $sid]);
                }
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
        }
        $display_block .= "<B>All updated</B><BR><hr>\n";
    } elseif ($POST_OP == 'submit_new') {
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
        $sid = $_POST['sid'];
        $name = $_POST['name'];
        $username = $_POST['username'];
        $programme = $_POST['programme'];

        try {
            $stmt = $db->prepare("REPLACE INTO $students (sid,name,username,programme,sel) VALUES (:sid, :name, :username, :programme, :sel)");
            $stmt->execute([':sid' => $sid, ':name' => $name, ':username' => $username, ':programme' => $programme, ':sel' => $sel]);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= "<B>Done</B><BR><hr>\n";
    } elseif ($POST_OP == 'upload_csv') {
        $xlsx_file_name = $_FILES['csv']['name'];
        $ext = strtolower(pathinfo($xlsx_file_name, PATHINFO_EXTENSION));
        $xlsx_file_tmp = $_FILES['csv']['tmp_name'];
        $xlsx_file_error = $_FILES['csv']['error'];

        if ($xlsx_file_error || $xlsx_file_name == '') {
            $display_block .= "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a XLSX/CSV file to upload.</H3>";
        } else {
            // Move the uploaded file to a holding area so it survives until the
            // admin confirms the preview (the original PHP upload tmp file is
            // deleted at the end of this request).
            $preview_token = bin2hex(random_bytes(16));
            $preview_ext = preg_replace('/[^a-z0-9]/', '', $ext);
            $preview_filename = "upload_{$preview_token}.{$preview_ext}";
            $preview_path = PMA_TMP_DIR . '/' . $preview_filename;

            $preview_rows = null;
            if (move_uploaded_file($xlsx_file_tmp, $preview_path)) {
                try {
                    $preview_rows = parseStudentSpreadsheetFile($preview_path);
                } catch (\Exception $ex) {
                    error_log($ex->getMessage());
                    $preview_rows = null;
                }
            }

            if ($preview_rows === null) {
                @unlink($preview_path);
                $display_block .= "<H3 style='color: red; background-color: #f1c40f'>Could not read that file. Please check it is a valid XLSX/CSV file.</H3>";
            } elseif (count($preview_rows) < 1) {
                @unlink($preview_path);
                $display_block .= "<H3 style='color: red; background-color: #f1c40f'>No valid student rows were found in that file (check the Username/email column).</H3>";
            } else {
                $existing_sids = [];
                try {
                    $existing_stmt = $db->query("SELECT sid FROM $students");
                    $existing_sids = $existing_stmt->fetchAll(PDO::FETCH_COLUMN);
                } catch (PDOException $ex) {
                    error_log($ex->getMessage());
                }

                $invalid_count = 0;
                foreach ($preview_rows as $prow) {
                    if (!$prow['sid_valid']) {
                        $invalid_count++;
                    }
                }

                $display_block .= "<hr><h2>Preview of data to be imported</h2>\n";
                $display_block .= '<p>Please check the data below before confirming. <B>' . count($preview_rows) . '</B> student row(s) were found in <B>' . htmlspecialchars($xlsx_file_name) . "</B>.</p>\n";

                if ($invalid_count > 0) {
                    $display_block .= "<H3 style='color: red; background-color: #f1c40f'>$invalid_count row(s) failed validation: the SID (first column) must be a whole number. Fix the file and upload it again &mdash; nothing has been imported.</H3>\n";
                }

                $display_block .= "<TABLE class='table table-bordered table-striped table-sm w-auto'>\n";
                $display_block .= "<thead><tr><th>SID</th><th>Name</th><th>Username</th><th>Programme</th><th>Status</th></tr></thead>\n<tbody>\n";
                foreach ($preview_rows as $prow) {
                    if (!$prow['sid_valid']) {
                        $status = "<span class='badge bg-danger'>fail: SID must be a whole number</span>";
                    } elseif (in_array($prow['sid'], $existing_sids)) {
                        $status = "<span class='badge bg-info'>update</span>";
                    } else {
                        $status = "<span class='badge bg-success'>new</span>";
                    }
                    $display_block .= '<tr>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['sid']) . '</td>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['name']) . '</td>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['username']) . '</td>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['programme']) . '</td>';
                    $display_block .= "<td>$status</td>";
                    $display_block .= "</tr>\n";
                }
                $display_block .= "</tbody></TABLE>\n";

                if ($invalid_count === 0) {
                    $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id' style='display:inline-block; margin-right: 10px;'>\n";
                    $display_block .= "<input type='hidden' name='op' value='confirm_upload_csv'>";
                    $display_block .= "<input type='hidden' name='preview_file' value='" . htmlspecialchars($preview_filename) . "'>";
                    $display_block .= "<input class='btn btn-success' type='submit' name='submit' value='Confirm and import this data'>";
                    $display_block .= "</form>\n";
                }

                $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id' style='display:inline-block;'>\n";
                $display_block .= "<input type='hidden' name='op' value='cancel_upload_csv'>";
                $display_block .= "<input type='hidden' name='preview_file' value='" . htmlspecialchars($preview_filename) . "'>";
                $display_block .= "<input class='btn btn-outline-secondary' type='submit' name='submit' value='Cancel'>";
                $display_block .= "</form>\n";

                $display_block .= "<hr>\n";
            }
        }
    } elseif ($POST_OP == 'confirm_upload_csv') {
        $preview_filename = basename((string)($_POST['preview_file'] ?? ''));
        $preview_path = PMA_TMP_DIR . '/' . $preview_filename;

        if (!preg_match('/^upload_[a-f0-9]{32}\.[a-z0-9]*$/', $preview_filename) || !is_file($preview_path)) {
            $display_block .= "<H3 style='color: red; background-color: #f1c40f'>That preview has expired. Please upload the file again.</H3>";
        } else {
            $rows_to_import = parseStudentSpreadsheetFile($preview_path);

            $invalid_count = 0;
            foreach ($rows_to_import as $prow) {
                if (!$prow['sid_valid']) {
                    $invalid_count++;
                }
            }

            if ($invalid_count > 0) {
                @unlink($preview_path);
                $display_block .= "<H3 style='color: red; background-color: #f1c40f'>Import aborted: $invalid_count row(s) still have a non-integer SID. Fix the file and upload it again.</H3>";
            } else {
                try {
                    foreach ($rows_to_import as $prow) {
                        $stmt = $db->prepare("REPLACE INTO $students (sid,name,username,programme) VALUES (:sid, :name, :username, :programme)");
                        $stmt->execute([':sid' => $prow['sid'], ':name' => $prow['name'], ':username' => $prow['username'], ':programme' => $prow['programme']]);
                    }
                } catch (PDOException $ex) {
                    error_log($ex->getMessage());
                    @unlink($preview_path);
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }
                @unlink($preview_path);
                $display_block .= '<B>' . count($rows_to_import) . " student row(s) imported.</B><BR><hr>\n";
            }
        }
    } elseif ($POST_OP == 'cancel_upload_csv') {
        $preview_filename = basename((string)($_POST['preview_file'] ?? ''));
        $preview_path = PMA_TMP_DIR . '/' . $preview_filename;
        if (preg_match('/^upload_[a-f0-9]{32}\.[a-z0-9]*$/', $preview_filename) && is_file($preview_path)) {
            @unlink($preview_path);
        }
        $display_block .= "<p><em>Upload cancelled. No changes were made.</em></p><hr>\n";
    } elseif ($POST_OP == 'download_csv') {
        $csv_filename = 'student_data_' . $module_prefix . $module1 . $module_postfix . '_' . $short_code . '_' . $year_begin . '-' . $year_end . '.csv';

        $tmpcsvfile = tempnam(PMA_TMP_DIR, '');
        $path = dirname($tmpcsvfile);

        $query = "SELECT * FROM $students ORDER BY name";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
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
            $row[0] = 'SID';
            $row[1] = 'Student name';
            $row[2] = 'Username';
            $row[3] = 'Programme';

            $ret = fputcsv($fp, $row);

            $rows = $results->fetchAll();
            foreach ($rows as $recs) {
                $sid = $recs['sid'];
                $student_name = $recs['name'];
                $email = $recs['username'];
                $is_username = strpos($email, '@');
                if ($is_username === false) {
                    $username = $email;
                } else {
                    $username = strstr($email, '@', true);
                }
                $programme = stripslashes($recs['programme']);

                $row[0] = $sid;
                $row[1] = $student_name;
                $row[2] = $username;
                $row[3] = $programme;

                $ret = fputcsv($fp, $row);
            }

            fclose($fp);

            header('Content-type: application/excel');
            header("Content-Disposition: attachment; filename=$csv_filename");
            readfile("$tmpcsvfile");

            exit();
            $display_block = "<h2> Student data saved as CSV file to: $csv_filename</h2>";
        }
    }// end of if ($POST_OP == "submint_new")

    $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

    // Get the list of students from the students table
    $query = "select * from $students order by name";
    //$get_list_res = mysql_query($get_list) or die(" Unable to create student name list from database. <BR>Error msg: " . mysql_error() . "<BR>Query: $get_list");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    //$display_block = "<TABLE  class='tableSimple' style='border:1px solid black;border-collapse:collapse;'>\n";
    $display_block .= "<TABLE id='studentinfo' class='table table-bordered table-striped table-hover table-sm cell-border' cellspacing='0' width='100%'>\n";
    $display_block .= '
    <thead>
      <tr>
        <th>sid</th>
        <th>name</th>
        <th>username</th>
        <th>programme</th>
        <th>sel</th>
        <th>delete</th>
      </tr>
    </thead>';
    $display_block .= "<tbody>\n";
    $student_count = 0;
    $rows = $results->fetchAll();
    //if ($results->fetch(PDO::FETCH_NUM) > 0 ) {
    foreach ($rows as $recs) {
        $sid = $recs['sid'];
        $name = $recs['name'];
        $username = $recs['username'];
        $programme = stripslashes($recs['programme']);
        $sel = stripslashes($recs['sel']);
        $student_count++;
        $display_block .= "<TR>\n";
        $display_block .= '<TD>' . htmlspecialchars((string)$sid) . "<input type='hidden' name='sid$student_count' value='" . htmlspecialchars((string)$sid) . "'></TD>";
        $display_block .= "<TD><input type='text' name='name$student_count' class='form-control form-control-sm' required='required' value='$name' maxlength=30></TD>";
        $display_block .= "<TD><input type='text' name='username$student_count' class='form-control form-control-sm' required='required' value='$username' maxlength=20></TD>";
        $display_block .= "<TD><input type='text' name='programme$student_count' class='form-control form-control-sm' required='required' value='$programme' maxlength=60></TD>";

        $checked = '';
        if ($sel == 1) {
            $checked = 'checked';
        }
        $display_block .= "<TD><input type='checkbox' name='sel$student_count' value='1' $checked></TD>\n";

        $checked = '';
        $display_block .= "<TD><input type='checkbox' name='delete$student_count' value='1' $checked></TD>\n";

        $display_block .= "</TR>\n";
    }
    //}
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type='hidden' name='op' value='submit_edited'>";
    $display_block .= "\n<input type='hidden' name='student_count' value='$student_count'>";

    $display_block .= "<p><input class='btn btn-success' type='submit' id='submitbutton' name='submit' value='Submit changes to the student details'></p>";

    $display_block .= "</form>\n";

    //---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Make changes by uploading a XLSX/CSV...</H2>\n";
    $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id' enctype='multipart/form-data'>\n";
    $display_block .= '<P> You can alternatively upload a XLSX/CSV file to update and <B>add</B> new students (you cannot delete students) </P>';
    $display_block .= "<P> Create a XLSX/CSV with the headings:'<B>SID</B>, <B>Name</B>,  <B>Username (or email address)</B>, <B>Programme</B>' and load here.</P>";

    $display_block .= "<P></P><input class='btn btn-outline-warning' name='csv' type='file' id='csv' /></P>";
    $display_block .= "\n<input type='hidden' name='op' value='upload_csv'>";

    $display_block .= "<p><input class='btn btn-warning' type='submit' id='submitbutton_csv' name='submit' value='Upload the selected XLSX/CSV file'></p>";

    $display_block .= "</form>\n";

    //---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Add a new student...</H2>\n";
    $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

    $display_block .= "\n<input type='hidden' name='op' value='new'>";

    $display_block .= "<p><input class='btn btn-primary' type='submit' id='submitbutton_new' name='submit' value='Add details of a NEW student.'></p>";

    $display_block .= "</form>\n";

    //---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Download Student data to CSV file ...</H2>\n";
    $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

    $display_block .= "\n<input type='hidden' name='op' value='download_csv'>";

    $display_block .= "<p><input class='btn btn-info' type='submit' id='submitbutton_download' name='submit' value='Download Student Data to CSV.'></p>";

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
<script>
$(document).ready(function () {
    $('#studentinfo').DataTable({
        paging: true,
        ordering: true,
        info: true,
        searching: true,
        lengthMenu: [[10, 20, 30, 100, -1], [10, 20, 30, 100, "All"]]
    });
});
</script>
</body>
</html>
