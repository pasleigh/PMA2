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

// Builds a sid => name lookup for the students table so the preview can show
// which student each row refers to (or flag that the SID isn't on the module's
// student list).
function buildStudentNameLookup($db, $students)
{
    $lookup = [];
    try {
        $stmt = $db->query("SELECT sid, name FROM $students");
        foreach ($stmt->fetchAll() as $srow) {
            $lookup[(string)$srow['sid']] = $srow['name'];
        }
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
    }
    return $lookup;
}

// Renders the "Student name" preview cell: the matched student's name, a
// not-found warning, or a dash when the SID itself already failed validation.
function studentNameCellHtml($student_names_by_sid, $sid, $sid_valid)
{
    if (!$sid_valid) {
        return '-';
    }
    $key = (string)$sid;
    if (array_key_exists($key, $student_names_by_sid)) {
        return htmlspecialchars(stripslashes($student_names_by_sid[$key]));
    }
    return "<span class='badge bg-warning text-dark'>SID not found in student list</span>";
}

// Reads the "simple" allocation rows (one supervisor/second marker pair applied
// to every part) out of an uploaded XLSX/CSV. Staff names are kept as raw text
// here; they are resolved to staff ids at confirm time, same as before.
function parseSimpleAllocationRows($filePath, $sid_col, $pid_col, $title_col, $supervisor_col, $second_marker_col)
{
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getSheet(0);
    $highestRow = $worksheet->getHighestRow();

    $rows = [];
    for ($row = 2; $row <= $highestRow; $row++) {
        $sid = $worksheet->getCell([$sid_col, $row])->getValue();
        $master_id = $worksheet->getCell([$pid_col, $row])->getValue();
        $title = $worksheet->getCell([$title_col, $row])->getValue();
        $supervisor_family_name = $worksheet->getCell([$supervisor_col, $row])->getValue();
        $second_marker_family_name = $worksheet->getCell([$second_marker_col, $row])->getValue();

        $is_blank_row = trim((string)$sid) === '' && trim((string)$master_id) === '' && trim((string)$title) === ''
            && trim((string)$supervisor_family_name) === '' && trim((string)$second_marker_family_name) === '';
        if ($is_blank_row) {
            continue;
        }

        $rows[] = [
            'sid' => $sid,
            'master_id' => $master_id,
            'title' => $title,
            'supervisor_family_name' => $supervisor_family_name,
            'second_marker_family_name' => $second_marker_family_name,
            'sid_valid' => isIntegerSid($sid),
        ];
    }
    return $rows;
}

// Reads the "detailed" allocation rows (a distinct supervisor/second marker
// pair per part) out of an uploaded XLSX/CSV. $num_parts is 0-indexed here to
// match the P0..Pn column loop used by the original upload code.
function parseDetailedAllocationRows($filePath, $sid_col, $pid_col, $title_col, $supervisor_col, $second_marker_col, $num_parts)
{
    $spreadsheet = IOFactory::load($filePath);
    $worksheet = $spreadsheet->getSheet(0);
    $highestRow = $worksheet->getHighestRow();

    $rows = [];
    for ($row = 2; $row <= $highestRow; $row++) {
        $sid = $worksheet->getCell([$sid_col, $row])->getValue();
        $master_id = $worksheet->getCell([$pid_col, $row])->getValue();
        $title = $worksheet->getCell([$title_col, $row])->getValue();

        $parts = [];
        $any_marker_text = false;
        for ($i = 0; $i <= $num_parts; $i++) {
            $supervisor_family_name = $worksheet->getCell([$supervisor_col + $i * 2, $row])->getValue();
            $second_marker_family_name = $worksheet->getCell([$second_marker_col + $i * 2, $row])->getValue();
            if (trim((string)$supervisor_family_name) !== '' || trim((string)$second_marker_family_name) !== '') {
                $any_marker_text = true;
            }
            $parts[$i] = [
                'supervisor_family_name' => $supervisor_family_name,
                'second_marker_family_name' => $second_marker_family_name,
            ];
        }

        $is_blank_row = trim((string)$sid) === '' && trim((string)$master_id) === '' && trim((string)$title) === '' && !$any_marker_text;
        if ($is_blank_row) {
            continue;
        }

        $rows[] = [
            'sid' => $sid,
            'master_id' => $master_id,
            'title' => $title,
            'parts' => $parts,
            'sid_valid' => isIntegerSid($sid),
        ];
    }
    return $rows;
}

// The DB columns (per allocation row) that hold a resolved staff id.
function allocationStaffFieldWhitelist()
{
    $fields = ['supervisor_id', 'second_marker_id'];
    for ($p = 1; $p <= 5; $p++) {
        $fields[] = "marker_p{$p}m1_id";
        $fields[] = "marker_p{$p}m2_id";
    }
    return $fields;
}

// Records that $raw_name (from the uploaded file) did not match any staff member
// for the given student/column, grouped by the normalised name so every row using
// the same unmatched name can be fixed together. $groups is the per-pm_id session
// array, passed by reference so changes are written straight back into the session.
function recordUnmatchedStaffName(&$groups, $sid, $field, $raw_name)
{
    $raw_name = trim((string)$raw_name);
    if ($raw_name === '') {
        return;
    }
    $key = md5(strtolower($raw_name));
    if (!isset($groups[$key])) {
        $groups[$key] = ['raw_name' => $raw_name, 'entries' => []];
    }
    foreach ($groups[$key]['entries'] as $existing) {
        if ($existing['sid'] == $sid && $existing['field'] === $field) {
            return;
        }
    }
    $groups[$key]['entries'][] = ['sid' => $sid, 'field' => $field];
}

// Returns the pending unmatched raw name for this student/column, or null if
// that cell isn't currently flagged as unmatched.
function findUnmatchedRawName($groups, $sid, $field)
{
    foreach ($groups as $group) {
        foreach ($group['entries'] as $entry) {
            if ($entry['sid'] == $sid && $entry['field'] === $field) {
                return $group['raw_name'];
            }
        }
    }
    return null;
}

// Renders a name cell for the allocation table: the resolved staff name, or -
// if that cell is still pending resolution - the original unmatched text from
// the upload, highlighted so it's obvious it needs fixing.
function allocationNameCellHtml($resolved_name, $groups, $sid, $field)
{
    $raw = findUnmatchedRawName($groups, $sid, $field);
    if ($raw !== null) {
        return "<span class='badge bg-warning text-dark' title='Not matched to a staff member - see Unmatched staff names above'>" . htmlspecialchars($raw) . '</span>';
    }
    return htmlspecialchars((string)$resolved_name);
}

function staffSelectOptionsHtml($db, $staff_names2)
{
    $html = "<option value=''>-- select staff member --</option>";
    try {
        $stmt = $db->query("SELECT staffid, fullname FROM $staff_names2 ORDER BY fullname");
        foreach ($stmt->fetchAll() as $srow) {
            $name = htmlspecialchars(stripslashes($srow['fullname']));
            $id = htmlspecialchars((string)$srow['staffid']);
            $html .= "<option value='$id'>$name</option>";
        }
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
    }
    return $html;
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

// Names from an uploaded file that didn't match a staff member are remembered
// here (per module) until an admin resolves them, so the "unmatched" highlight
// and fix-all-at-once option survive across page loads within this session.
if (!isset($_SESSION['unmatched_staff_names'])) {
    $_SESSION['unmatched_staff_names'] = [];
}
if (!isset($_SESSION['unmatched_staff_names'][$pm_id])) {
    $_SESSION['unmatched_staff_names'][$pm_id] = [];
}
$unmatched_groups = &$_SESSION['unmatched_staff_names'][$pm_id];

$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($is_module_admin || $admin_access) {
    $display_block .= '<h1>Upload Student Project Allocation</h1>';
    //$display_block .= "<h2>Database table to be updated '$project_allocated'</h2>";

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
                    $preview_rows = parseSimpleAllocationRows($preview_path, $sid_col, $pid_col, $title_col, $supervisor_col, $second_marker_col);
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
                $display_block .= "<H3 style='color: red; background-color: #f1c40f'>No data rows were found in that file.</H3>";
            } else {
                $existing_sids = [];
                try {
                    $existing_stmt = $db->query("SELECT sid FROM $project_allocated");
                    $existing_sids = $existing_stmt->fetchAll(PDO::FETCH_COLUMN);
                } catch (PDOException $ex) {
                    error_log($ex->getMessage());
                }
                $student_names_by_sid = buildStudentNameLookup($db, $students);

                $invalid_count = 0;
                foreach ($preview_rows as $prow) {
                    if (!$prow['sid_valid']) {
                        $invalid_count++;
                    }
                }

                $display_block .= "<hr><h2>Preview of simple allocation data to be imported</h2>\n";
                $display_block .= '<p>Please check the data below before confirming. <B>' . count($preview_rows) . '</B> row(s) were found in <B>' . htmlspecialchars($xlsx_file_name) . "</B>.</p>\n";

                if ($invalid_count > 0) {
                    $display_block .= "<H3 style='color: red; background-color: #f1c40f'>$invalid_count row(s) failed validation: the SID (first column) must be a whole number. Fix the file and upload it again &mdash; nothing has been imported.</H3>\n";
                }

                $display_block .= "<TABLE class='table table-bordered table-striped table-sm w-auto'>\n";
                $display_block .= "<thead><tr><th>SID</th><th>Student name</th><th>Project ID</th><th>Title</th><th>Supervisor</th><th>Second marker</th><th>Status</th></tr></thead>\n<tbody>\n";
                foreach ($preview_rows as $prow) {
                    if (!$prow['sid_valid']) {
                        $status = "<span class='badge bg-danger'>fail: SID must be a whole number</span>";
                    } elseif (in_array($prow['sid'], $existing_sids)) {
                        $status = "<span class='badge bg-info'>update</span>";
                    } else {
                        $status = "<span class='badge bg-success'>new</span>";
                    }
                    $name_cell = studentNameCellHtml($student_names_by_sid, $prow['sid'], $prow['sid_valid']);
                    $display_block .= '<tr>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['sid']) . '</td>';
                    $display_block .= "<td>$name_cell</td>";
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['master_id']) . '</td>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['title']) . '</td>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['supervisor_family_name']) . '</td>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['second_marker_family_name']) . '</td>';
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
            $rows_to_import = parseSimpleAllocationRows($preview_path, $sid_col, $pid_col, $title_col, $supervisor_col, $second_marker_col);

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
                $unmatched_groups = [];
                foreach ($rows_to_import as $prow) {
                    $sid = $prow['sid'];
                    $master_id = $prow['master_id'];
                    $title = $prow['title'];
                    $supervisor_id = GetStaffIDFromFamilyNameAndInitial2($db, $prow['supervisor_family_name'], $staff_names2);
                    $second_marker_id = GetStaffIDFromFamilyNameAndInitial2($db, $prow['second_marker_family_name'], $staff_names2);

                    if ($supervisor_id === '0') {
                        recordUnmatchedStaffName($unmatched_groups, $sid, 'supervisor_id', $prow['supervisor_family_name']);
                        foreach ([1, 2, 3, 4, 5] as $p) {
                            recordUnmatchedStaffName($unmatched_groups, $sid, "marker_p{$p}m1_id", $prow['supervisor_family_name']);
                        }
                    }
                    if ($second_marker_id === '0') {
                        recordUnmatchedStaffName($unmatched_groups, $sid, 'second_marker_id', $prow['second_marker_family_name']);
                        foreach ([1, 2, 3, 4, 5] as $p) {
                            recordUnmatchedStaffName($unmatched_groups, $sid, "marker_p{$p}m2_id", $prow['second_marker_family_name']);
                        }
                    }

                    // Does this student have an entry in the allocations table
                    $query = "select * from $project_allocated where sid=:sid";
                    try {
                        $results = $db->prepare($query);
                        $results->execute([':sid' => $sid]);
                    } catch (PDOException $ex) {
                        error_log($ex->getMessage());
                        @unlink($preview_path);
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
                            @unlink($preview_path);
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
                            @unlink($preview_path);
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                    }

                    foreach ([1, 2, 3, 4, 5] as $part_num) {
                        $query = "UPDATE $project_allocated SET `marker_p{$part_num}m1_id`=:supervisor_id, `marker_p{$part_num}m2_id`=:second_marker_id WHERE sid=:sid";
                        try {
                            $results = $db->prepare($query);
                            $results->execute([':supervisor_id' => $supervisor_id, ':second_marker_id' => $second_marker_id, ':sid' => $sid]);
                        } catch (PDOException $ex) {
                            error_log($ex->getMessage());
                            @unlink($preview_path);
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                    }
                }
                @unlink($preview_path);
                $display_block .= '<B>' . count($rows_to_import) . " row(s) imported.</B><BR><hr>\n";
            }
        }
    } elseif ($POST_OP == 'cancel_upload_csv') {
        $preview_filename = basename((string)($_POST['preview_file'] ?? ''));
        $preview_path = PMA_TMP_DIR . '/' . $preview_filename;
        if (preg_match('/^upload_[a-f0-9]{32}\.[a-z0-9]*$/', $preview_filename) && is_file($preview_path)) {
            @unlink($preview_path);
        }
        $display_block .= "<p><em>Upload cancelled. No changes were made.</em></p><hr>\n";
    }// end of if ($POST_OP == "upload_csv")

    if ($POST_OP == 'upload_detailed_csv') {
        $xlsx_file_name = $_FILES['csv']['name'];
        $ext = strtolower(pathinfo($xlsx_file_name, PATHINFO_EXTENSION));
        $xlsx_file_tmp = $_FILES['csv']['tmp_name'];
        $xlsx_file_error = $_FILES['csv']['error'];

        if ($xlsx_file_error || $xlsx_file_name == '') {
            $display_block .= "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a XLSX/CSV file to upload.</H3>";
        } else {
            $preview_token = bin2hex(random_bytes(16));
            $preview_ext = preg_replace('/[^a-z0-9]/', '', $ext);
            $preview_filename = "upload_{$preview_token}.{$preview_ext}";
            $preview_path = PMA_TMP_DIR . '/' . $preview_filename;

            $preview_rows = null;
            if (move_uploaded_file($xlsx_file_tmp, $preview_path)) {
                try {
                    $preview_rows = parseDetailedAllocationRows($preview_path, $sid_col, $pid_col, $title_col, $supervisor_col, $second_marker_col, $num_parts);
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
                $display_block .= "<H3 style='color: red; background-color: #f1c40f'>No data rows were found in that file.</H3>";
            } else {
                $existing_sids = [];
                try {
                    $existing_stmt = $db->query("SELECT sid FROM $project_allocated");
                    $existing_sids = $existing_stmt->fetchAll(PDO::FETCH_COLUMN);
                } catch (PDOException $ex) {
                    error_log($ex->getMessage());
                }
                $student_names_by_sid = buildStudentNameLookup($db, $students);

                $invalid_count = 0;
                foreach ($preview_rows as $prow) {
                    if (!$prow['sid_valid']) {
                        $invalid_count++;
                    }
                }

                $display_block .= "<hr><h2>Preview of detailed allocation data to be imported</h2>\n";
                $display_block .= '<p>Please check the data below before confirming. <B>' . count($preview_rows) . '</B> row(s) were found in <B>' . htmlspecialchars($xlsx_file_name) . "</B>.</p>\n";

                if ($invalid_count > 0) {
                    $display_block .= "<H3 style='color: red; background-color: #f1c40f'>$invalid_count row(s) failed validation: the SID (first column) must be a whole number. Fix the file and upload it again &mdash; nothing has been imported.</H3>\n";
                }

                $display_block .= "<TABLE class='table table-bordered table-striped table-sm w-auto'>\n";
                $display_block .= '<thead><tr><th>SID</th><th>Student name</th><th>Project ID</th><th>Title</th>';
                for ($i = 0; $i <= $num_parts; $i++) {
                    $display_block .= "<th>P$i marker</th><th>P$i 2nd marker</th>";
                }
                $display_block .= "<th>Status</th></tr></thead>\n<tbody>\n";
                foreach ($preview_rows as $prow) {
                    if (!$prow['sid_valid']) {
                        $status = "<span class='badge bg-danger'>fail: SID must be a whole number</span>";
                    } elseif (in_array($prow['sid'], $existing_sids)) {
                        $status = "<span class='badge bg-info'>update</span>";
                    } else {
                        $status = "<span class='badge bg-success'>new</span>";
                    }
                    $name_cell = studentNameCellHtml($student_names_by_sid, $prow['sid'], $prow['sid_valid']);
                    $display_block .= '<tr>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['sid']) . '</td>';
                    $display_block .= "<td>$name_cell</td>";
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['master_id']) . '</td>';
                    $display_block .= '<td>' . htmlspecialchars((string)$prow['title']) . '</td>';
                    foreach ($prow['parts'] as $part) {
                        $display_block .= '<td>' . htmlspecialchars((string)$part['supervisor_family_name']) . '</td>';
                        $display_block .= '<td>' . htmlspecialchars((string)$part['second_marker_family_name']) . '</td>';
                    }
                    $display_block .= "<td>$status</td>";
                    $display_block .= "</tr>\n";
                }
                $display_block .= "</tbody></TABLE>\n";

                if ($invalid_count === 0) {
                    $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id' style='display:inline-block; margin-right: 10px;'>\n";
                    $display_block .= "<input type='hidden' name='op' value='confirm_upload_detailed_csv'>";
                    $display_block .= "<input type='hidden' name='preview_file' value='" . htmlspecialchars($preview_filename) . "'>";
                    $display_block .= "<input class='btn btn-success' type='submit' name='submit' value='Confirm and import this data'>";
                    $display_block .= "</form>\n";
                }

                $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id' style='display:inline-block;'>\n";
                $display_block .= "<input type='hidden' name='op' value='cancel_upload_detailed_csv'>";
                $display_block .= "<input type='hidden' name='preview_file' value='" . htmlspecialchars($preview_filename) . "'>";
                $display_block .= "<input class='btn btn-outline-secondary' type='submit' name='submit' value='Cancel'>";
                $display_block .= "</form>\n";

                $display_block .= "<hr>\n";
            }
        }
    } elseif ($POST_OP == 'confirm_upload_detailed_csv') {
        $preview_filename = basename((string)($_POST['preview_file'] ?? ''));
        $preview_path = PMA_TMP_DIR . '/' . $preview_filename;

        if (!preg_match('/^upload_[a-f0-9]{32}\.[a-z0-9]*$/', $preview_filename) || !is_file($preview_path)) {
            $display_block .= "<H3 style='color: red; background-color: #f1c40f'>That preview has expired. Please upload the file again.</H3>";
        } else {
            $rows_to_import = parseDetailedAllocationRows($preview_path, $sid_col, $pid_col, $title_col, $supervisor_col, $second_marker_col, $num_parts);

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
                $unmatched_groups = [];
                foreach ($rows_to_import as $prow) {
                    $sid = $prow['sid'];
                    $master_id = $prow['master_id'];
                    $title = $prow['title'];

                    $supervisor_ida = [];
                    $second_marker_ida = [];
                    foreach ($prow['parts'] as $i => $part) {
                        $supervisor_ida[$i] = GetStaffIDFromFamilyNameAndInitial2($db, $part['supervisor_family_name'], $staff_names2);
                        $second_marker_ida[$i] = GetStaffIDFromFamilyNameAndInitial2($db, $part['second_marker_family_name'], $staff_names2);

                        $supervisor_field = ($i == 0) ? 'supervisor_id' : "marker_p{$i}m1_id";
                        $second_marker_field = ($i == 0) ? 'second_marker_id' : "marker_p{$i}m2_id";
                        if ($supervisor_ida[$i] === '0') {
                            recordUnmatchedStaffName($unmatched_groups, $sid, $supervisor_field, $part['supervisor_family_name']);
                        }
                        if ($second_marker_ida[$i] === '0') {
                            recordUnmatchedStaffName($unmatched_groups, $sid, $second_marker_field, $part['second_marker_family_name']);
                        }
                    }

                    // Does this student have an entry in the allocations table
                    $query = "select * from $project_allocated where sid=:sid";
                    try {
                        $results = $db->prepare($query);
                        $results->execute([':sid' => $sid]);
                    } catch (PDOException $ex) {
                        error_log($ex->getMessage());
                        @unlink($preview_path);
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
                            @unlink($preview_path);
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
                            @unlink($preview_path);
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
                            @unlink($preview_path);
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                    }
                }
                @unlink($preview_path);
                $display_block .= '<B>' . count($rows_to_import) . " row(s) imported.</B><BR><hr>\n";
            }
        }
    } elseif ($POST_OP == 'cancel_upload_detailed_csv') {
        $preview_filename = basename((string)($_POST['preview_file'] ?? ''));
        $preview_path = PMA_TMP_DIR . '/' . $preview_filename;
        if (preg_match('/^upload_[a-f0-9]{32}\.[a-z0-9]*$/', $preview_filename) && is_file($preview_path)) {
            @unlink($preview_path);
        }
        $display_block .= "<p><em>Upload cancelled. No changes were made.</em></p><hr>\n";
    }// end of if ($POST_OP == "upload_detailed_csv")

    if ($POST_OP == 'resolve_unmatched_staff') {
        $group_key = (string)($_POST['group_key'] ?? '');
        $new_staff_id = (int)($_POST['staff_id'] ?? 0);
        $valid_fields = allocationStaffFieldWhitelist();

        if ($new_staff_id <= 0) {
            $display_block .= "<H3 style='color: red; background-color: #f1c40f'>Please select a staff member before confirming the fix.</H3>";
        } elseif (!isset($unmatched_groups[$group_key])) {
            $display_block .= "<H3 style='color: red; background-color: #f1c40f'>That unmatched name is no longer pending (it may already have been fixed).</H3>";
        } else {
            $fixed_raw_name = $unmatched_groups[$group_key]['raw_name'];
            $fixed_count = 0;
            foreach ($unmatched_groups[$group_key]['entries'] as $entry) {
                if (!in_array($entry['field'], $valid_fields, true)) {
                    continue;
                }
                try {
                    $stmt = $db->prepare("UPDATE $project_allocated SET `{$entry['field']}`=:staff_id WHERE sid=:sid");
                    $stmt->execute([':staff_id' => $new_staff_id, ':sid' => $entry['sid']]);
                    $fixed_count++;
                } catch (PDOException $ex) {
                    error_log($ex->getMessage());
                }
            }
            unset($unmatched_groups[$group_key]);
            $display_block .= '<B>' . $fixed_count . ' entr' . ($fixed_count == 1 ? 'y' : 'ies') . ' matching "' . htmlspecialchars($fixed_raw_name) . '" updated.</B><BR><hr>' . "\n";
        }
    }// end of if ($POST_OP == "resolve_unmatched_staff")

    if (!empty($unmatched_groups)) {
        $display_block .= "<hr><h2 style='color: darkred;'>Unmatched staff names</h2>\n";
        $display_block .= "<p>The names below from the uploaded file did not match anyone in the staff list. Pick the correct staff member for each name below &mdash; this will fix <B>every</B> row and column where that name was used.</p>\n";
        foreach ($unmatched_groups as $group_key => $group) {
            $count = count($group['entries']);
            $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id' style='margin-bottom: 8px;'>\n";
            $display_block .= "<input type='hidden' name='op' value='resolve_unmatched_staff'>";
            $display_block .= "<input type='hidden' name='group_key' value='" . htmlspecialchars($group_key) . "'>";
            $display_block .= "<span class='badge bg-warning text-dark' style='font-size: 1em;'>" . htmlspecialchars($group['raw_name']) . '</span> ';
            $display_block .= '<small>(' . $count . ' occurrence' . ($count == 1 ? '' : 's') . ')</small> ';
            $display_block .= "<select name='staff_id' class='form-control form-control-sm d-inline-block' style='width: auto;' required>" . staffSelectOptionsHtml($db, $staff_names2) . '</select> ';
            $display_block .= "<input class='btn btn-sm btn-primary' type='submit' value='Fix all'>";
            $display_block .= "</form>\n";
        }
        $display_block .= "<hr>\n";
    }

    $num = CountAllocationTable($db, $project_allocated);
    if ($num == 0) {
        $display_block .= "<P>There are no entries in the database table. This might indicate that the table has not yet been created. Upload a XLSX/CSV file to create it.</P>";
    } else {
        // Get the list of allocations from the students table
        $query = "select * from $project_allocated order by projectid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $rows = $results->fetchAll();
        $display_block .= "<TABLE id='studentallocation' class='table table-bordered table-striped table-hover table-sm compact cell-border' style='font-size: small;' cellspacing='0' width='100%'>\n";
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
        $table_rows = [];
        $table_cells = [];
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

            $table_cells = [];
            $table_cells[] = htmlspecialchars((string)$pid);
            $table_cells[] = htmlspecialchars((string)$sid);
            $table_cells[] = htmlspecialchars((string)$student_name);
            $table_cells[] = htmlspecialchars((string)$title);
            $table_cells[] = allocationNameCellHtml($supervisor_name, $unmatched_groups, $sid, 'supervisor_id');
            $table_cells[] = allocationNameCellHtml($second_marker_name, $unmatched_groups, $sid, 'second_marker_id');

            for ($i = 1; $i <= 5; $i++) {
                $m1_entry = "marker_p$i" . 'm1_id';
                $m2_entry = "marker_p$i" . 'm2_id';
                $marker1_id = $recs[$m1_entry];
                $marker2_id = $recs[$m2_entry];
                $marker1_name = GetStaffNameFromID($db, $marker1_id, $staff_names2, null);
                $marker2_name = GetStaffNameFromID($db, $marker2_id, $staff_names2, null);
                $table_cells[] = allocationNameCellHtml($marker1_name, $unmatched_groups, $sid, $m1_entry);
                $table_cells[] = allocationNameCellHtml($marker2_name, $unmatched_groups, $sid, $m2_entry);
            }

            $table_rows[] = $table_cells;
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
<script>
    $(document).ready(function () {
        let mydata = <?php echo json_encode($table_rows); ?>;
        //console.log(mydata);
        $('#studentallocation').DataTable({
            //data: project_marking_data.markers,
            data: mydata,
            "lengthMenu": [[10, 20, 30, 100, -1], [10, 20, 30, 100, "All"]],
            "scrollX": true,
            "autoWidth": false
        })
    });
</script>
</html>
