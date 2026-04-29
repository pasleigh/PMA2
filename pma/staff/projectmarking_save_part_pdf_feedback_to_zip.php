<?php

// Report all PHP errors (see changelog)
require_once '../vendor/autoload.php';
function write_a_test_zip($zip_filename) {
    $zip = new ZipStream\ZipStream(
        outputName: $zip_filename,
        // enable output of HTTP headers
        sendHttpHeaders: true
    );

    $pdf_filename = 'test.txt';
    $mpdf_output = 'test01';
    $zip->addFile(
        fileName: $pdf_filename,
        data: $mpdf_output
    );

    $zip->finish();
    exit();
}

(@include_once('./set_dir_staff.php')) or die('Cannot find this file to include: set_dir_staff.php<BR>');
(@include_once('./config.php')) or die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once('./setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('./connect_pdo.php')) or die('Cannot connect to the database<BR>');

(@include_once('./admin2/adminusers.php')) or die("Cannot find this file to include: $adminusers_php<BR>");
(@include_once('./connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('./setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('./staff_functions.php')) or die('Cannot include staff_function.php<BR>');
(@include_once('./show_feedback_form.php')) or die('Cannot find this file to include: show_feedback_form_php<BR>');
(@include_once('./EmailPDFAttachment.php')) or die('Cannot find this file to include: EmailPDFAttachment_php<BR>');
(@include_once('./show_minor_part_feedback_form.php')) or die('Cannot include show_minor_part_feedback<BR>');
(@include_once('./markstore_functions.php')) or die('Cannot include markstore_function.php<BR>');


function download_link($file_to_download, $saveas_filename) {
    $html = "<BR>\n";
    $self_link = htmlspecialchars($_SERVER['PHP_SELF']);
    $html .= "<form action='$self_link' method='post'><BR>\n";
    $html .= "File on server: <input type='text' name='nameonserver' value='$file_to_download'><br>\n";
    $html .= "Download name: <input type='text' name='saveas_name' value='$saveas_filename'> <br>\n";
    $html .= "<input type='hidden' name='file_to_download' value='$file_to_download'>\n";
    $html .= "<input type='hidden' name='saveas_filename' value='$saveas_filename'>\n";
    $html .= "<input type='hidden' name='op' value='download'>\n";
    $html .= "<input type='submit' value='download $saveas_filename'>\n";
    $html .= "</form>\n";
    return $html;
}
function download_zip($file_to_download, $saveas_filename) {
    header('Pragma: public');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: private', false);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename=' . $saveas_filename);
    header('Content-Length: ' . filesize($file_to_download));
    ob_clean();
    flush();
    readfile("$file_to_download");
}

$admin = 0;

$POST_OP = null;
if (isset($_POST['op'])) {
    $POST_OP = $_POST['op'];
}
if ($POST_OP == 'download') {
    $file_to_download = $_POST['file_to_download'];
    $saveas_filename = $_POST['saveas_filename'];
    download_zip($file_to_download, $saveas_filename);
    exit();
}
$project_marking_page = 'projectmarking_bs.php';
$project_marking_options_page = 'projectmarking_select_bs.php';

$display_block = '';
$files_to_zip = [];

if (array_key_exists('part', $_GET) == false) {
    $_GET['part'] = null;
}
$part = $_GET['part'];

// In the absence of other setting, default to 1
$who = 1;

$module_leader_email = GetStaffEmailFromFullname($db, $module_leader_fullname, $staff_names2);
$this_staff_email = GetStaffEmailFromID($db, $this_staff_id, $staff_names2);

$start_time = microtime(true);
///////////////////////////////////////////////////////////////////////////////////
// The writing of the mpdf file for each student is time consuming and memory heavy
// So must disable the timeout and increase memory limits
// It may be necessary to run this script from the command line.
///////////////////////////////////////////////////////////////////////////////////
// Disable PHP Timeout
//set php script timeout, 0 to disable
set_time_limit(0);
ini_set('memory_limit', '256M');

if (array_key_exists('save', $_GET) == false) {
    $_GET['save'] = null;
}
$saving = $_GET['save'];
$saving = 2;
$savezip = 1;
$save_folder = PMA_TMP_DIR;

$assess_description_text = $p_description[$part];
$zip_filename_prefix = "$module_prefix$module1-$year_begin-$year_end-$assess_description_text";
$zip_filename = "$module_prefix$module1-$year_begin-$year_end-$assess_description_text.zip";
$zip_filename = str_replace(' ', '_', $zip_filename);

$zip_full_pathname = "$save_folder/$zip_filename";
// Create a new ZipArchive class
$zip_file_name_tempnam = tempnam('', 'zip_marks_file');
//$zip = new ZipArchive;
// Open a zip file
//$zip_file_res = $zip->open($zip_file_name_tempnam, ZipArchive::CREATE|ZipArchive::OVERWRITE);

// Start to retrieve data on all projects allocated
$query = "select * from $project_allocated order by projectid";
//$get_student_list_res = mysql_query($get_student_list) or die(" Unable to create student list from $project_allocated table. <BR>Query: $get_student_list");
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}

$student_counter = 0;
$num_rows = $results->rowCount();
$mem_per_call_estimate = 5;
if ($mem_per_call_estimate * $num_rows > 256) {
    $mem_needed = $mem_per_call_estimate * $num_rows;
    ini_set('memory_limit', $mem_needed . 'M');
}

if ($num_rows < 1) {
    //no records
    $display_block = '<p><em>Sorry, there are no records to select!</em></p>';
} else {
    //has records
    // create the zip
    $rows = $results->fetchAll();
    $pdfs_count = 1;

    $zip = new ZipStream\ZipStream(
        outputName: $zip_filename,
        // enable output of HTTP headers
        sendHttpHeaders: true
    );

    foreach ($rows as $recs) {
        $project_id = $recs['projectid'];
        $sid = $recs['sid'];
        $title = stripslashes($recs['title']);

        $supervisor_id = $recs['supervisor_id'];
        $second_marker_id = $recs['second_marker_id'];

        // To do
        if ($p_num_markers[$part] == 1) {
            $who = 1;
        } else {
        }
        // To do
        $marker1 = '';
        $marker2 = '';

        $marker_entry = "marker_p$part" . "m$who" . '_id';
        $marker_id = $recs[$marker_entry];
        $supervisor_name = GetStaffNameFromID($db, $supervisor_id, $staff_names2, null);
        $marker_name = GetStaffNameFromID($db, $marker_id, $staff_names2, null);
        $second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff_names2, null);

        $query = "select * from $students where sid=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $student_recs = $results->fetch();

        if ($student_recs) {
            $student_name = $student_recs['name'];
            $username = $student_recs['username'];
            $programme = $student_recs['programme'];
            $sel = $student_recs['sel'];
        }
        $assessqs_year = $assessqs[$part];
        $assess_cats = $assessqs_cats[$part];
        $assesstype_text = $p_description[$part];
        $mark_max = $p_max[$part];
        $mark_pc = $p_percent[$part];

        $is_numeric = $p_numeric[$part];

        if (HaveAllMarkersMarked($db, $sid, $part, $p_num_markers[$part], $markstores, $markstore_totals)) {
            $student_counter += 1;
            $feedback_form_html = '';
            $marker_name_for_feedback = $marker_name;
            for ($i = 1; $i <= $p_num_markers[$part]; $i++) {
                $markstore = $markstores[$part][$i];
                set_time_limit(0);
                if ($i > 1) {
                    $marker_name_for_feedback = $second_marker_name;
                }
                $feedback_form_html .= show_minor_part_feedback_form($db, $sid, $markstore, $assessqs_year, $student_name, $username, $title, $supervisor_name, $second_marker_name, $marker_name_for_feedback, $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $part, $assesstype_text, $module_short_description, $is_numeric);
            }
            //$feedback_form_html = utf8_encode($feedback_form_html );

            unset($mpdf);
            error_reporting(0);
            $mpdf = new \Mpdf\Mpdf(['tempDir' => PMA_TMP_DIR]);
            $mpdf->useSubstitutions = true;
            //$mpdf->ignore_invalid_utf8 = true;
            // Set a simple Footer including the date and page number
            $mpdf->setFooter("$student_name Date printed: {DATE j M Y}.   page {PAGENO}");
            $stylesheet = file_get_contents('./css/pagedesc.css');
            $mpdf->WriteHTML($stylesheet, 1);  // The parameter 1 tells that this is css/style only and no body/html/text
            $mpdf->WriteHTML($feedback_form_html);

            // saving to a zip
            $student_name_cleaned = trim($student_name);
            $student_name_cleaned = str_replace(',', '', $student_name_cleaned);
            $student_name_cleaned = str_replace('.', '', $student_name_cleaned);
            $student_name_cleaned = str_replace('\\', '', $student_name_cleaned);
            $student_name_cleaned = str_replace("'", '', $student_name_cleaned);
            $student_name_cleaned = str_replace(' ', '_', $student_name_cleaned);
            $pdf_filename = "$student_name_cleaned-$username-$assess_description_text.pdf";
            $pdf_filename = str_replace(' ', '_', $pdf_filename);

            $mpdf_output = $mpdf->OutputBinaryData();

            $zip->addFile(
                fileName: $pdf_filename,
                data: $mpdf_output
            );
            $pdfs_count++;
        }
    }
    $zip->finish();
}
exit();
