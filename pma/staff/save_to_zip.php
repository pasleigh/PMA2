<?php

// Report all PHP errors (see changelog)
//error_reporting(E_ALL | E_STRICT | E_WARNING | E_PARSE);
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
(@include_once("./set_dir_staff.php")) or die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./config.php")) or die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once("./setup_titles.php")) or die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./connect_pdo.php")) or die("Cannot connect to the database<BR>");

(@include_once("./admin2/adminusers.php")) or die("Cannot find this file to include: $adminusers_php<BR>");
(@include_once("./connect_pdo.php")) or die("Cannot connect to the database<BR>");
(@include_once("./setup_tables.php")) or die("Cannot setup database tables<BR>");
(@include_once("./staff_functions.php")) or die("Cannot include staff_function.php<BR>");
(@include_once("./show_feedback_form.php")) or die("Cannot find this file to include: show_feedback_form_php<BR>");
(@include_once("./EmailPDFAttachment.php")) or die("Cannot find this file to include: EmailPDFAttachment_php<BR>");
(@include_once("./show_minor_part_feedback_form.php")) or die("Cannot include show_minor_part_feedback<BR>");
(@include_once("./markstore_functions.php")) or die("Cannot include markstore_function.php<BR>");

require_once __BASE_DIR__ . '/vendor/autoload.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$files_to_zip = array();

$part = $_POST['part'];
$pm_id = $_POST['pm_id'];

// In the absence of other setting, default to 1
$who = 1;

$module_leader_email = GetStaffEmailFromFullname($db, $module_leader_fullname, $staff_names2);
$this_staff_email = GetStaffEmailFromID($db, $this_staff_id, $staff_names2);

///////////////////////////////////////////////////////////////////////////////////
// The writing of the mpdf file for each student is time consuming and memory heavy
// So must disable the timeout and increase memory limits
// It may be necessary to run this script from the command line.
///////////////////////////////////////////////////////////////////////////////////
// Disable PHP Timeout
//set php script timeout, 0 to disable
set_time_limit(0);
ini_set("memory_limit", "144M");


$savezip = 1;
$save_folder = sys_get_temp_dir();

$assess_description_text = $p_description[$part];
$zip_filename = "$module_prefix$module1-$year_begin-$year_end-$assess_description_text.zip";
$zip_filename = str_replace(' ', '_', $zip_filename);

$zip_full_pathname = "$save_folder/$zip_filename";

$action = "saving part $part ($assess_description_text) feedback PDFs as zip to $save_folder/$zip_filename";
$zip_info[] = $action;

// Start to retrieve data on all projects allocated
$query = "select * from $project_allocated order by projectid";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    echo(" Query = $query<BR> \n");
    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
    exit();
}

$student_counter = 0;
$num_rows = $results->rowCount();
$zip_info[] = "number of rows in $project_allocated $num_rows";

if ($num_rows < 1) {
    //no records
    $zip_info[] = "Sorry, there are no records to select!";
} else {
    //has records
    $rows = $results->fetchAll();
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
            //echo("Multiple markers will be returned in one PDF file.<BR>");
        }
        // To do
        $marker1 = "";
        $marker2 = "";

        $marker_entry = "marker_p$part" . "m$who" . "_id";
        $marker_id = $recs[$marker_entry];
        $supervisor_name = GetStaffNameFromID($db, $supervisor_id, $staff_names2, NULL);
        $marker_name = GetStaffNameFromID($db, $marker_id, $staff_names2, NULL);
        $second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff_names2, NULL);

        $query = "select * from $students where sid=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
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

        $locked = 0;
        //if( $locked == 0 )
        if (HaveAllMarkersMarked($db, $sid, $part, $p_num_markers[$part], $markstores, $markstore_totals)) {
            $student_counter += 1;
            $feedback_form_html = "";
            $marker_name_for_feedback = $marker_name;
            for ($i = 1; $i <= $p_num_markers[$part]; $i++) {
                $markstore = $markstores[$part][$i];
                set_time_limit(0);
                if ($i > 1) {
                    $marker_name_for_feedback = $second_marker_name;
                }
                $feedback_form_html .= show_minor_part_feedback_form($db, $sid, $markstore, $assessqs_year, $student_name, $username, $title, $supervisor_name, $second_marker_name, $marker_name_for_feedback, $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $part, $assesstype_text, $module_short_description, $is_numeric);
            }

            unset($mpdf);
            error_reporting(0);
            $system_temp_dir = sys_get_temp_dir();
            $mpdf = new \Mpdf\Mpdf(['tempDir'=>$system_temp_dir]);
            $mpdf->useSubstitutions = true;
            //$mpdf->ignore_invalid_utf8 = true;

            // Set a simple Footer including the date and page number
            $mpdf->setFooter("$student_name Date printed: {DATE j M Y}.   page {PAGENO}");
            $stylesheet = file_get_contents('./css/pagedesc.css');
            $mpdf->WriteHTML($stylesheet, 1);  // The parameter 1 tells that this is css/style only and no body/html/text
            $mpdf->WriteHTML($feedback_form_html);

            // saving to a zip
            // first save all the individual files to the temp folder, saving the names
            $student_name_cleaned = trim($student_name);
            $student_name_cleaned = str_replace(",", "", $student_name_cleaned);
            $student_name_cleaned = str_replace(".", "", $student_name_cleaned);
            $student_name_cleaned = str_replace("\\", "", $student_name_cleaned);
            $student_name_cleaned = str_replace("'", "", $student_name_cleaned);
            $student_name_cleaned = str_replace(" ", "_", $student_name_cleaned);
            $pdf_filename = "$student_name_cleaned-$username-$assess_description_text.pdf";
            $pdf_filename = str_replace(' ', '_', $pdf_filename);

            $pdf_fullpath_name = "$save_folder".DIRECTORY_SEPARATOR."$pdf_filename";
            //ob_start();
            $zip_info[] = "$pdf_fullpath_name";
            //$mpdf->Output("$pdf_fullpath_name", 'F');
            $mpdf->OutputFile($pdf_fullpath_name);

            $files_to_zip[] = $pdf_fullpath_name;
            $files_in_zip[] = $pdf_filename;
            $zip_info[] = "Success saving. Count: $student_counter. $pdf_filename $pdf_fullpath_name";
            //ob_end_flush();
            //flush();

            error_reporting(-1);

            $zip_info[] = "Finished sending/saving feedback emails to students";
            //exit;
        }
    }

}

// make the zip and download it
$assess_description_text = str_replace(' ', '_', $assess_description_text);
$zip_command = "zip -j $save_folder/$zip_filename $save_folder/*-$assess_description_text.pdf  ";
$zip_info[] = "Shell exec this command: $zip_command";
$shell_exec_return = shell_exec($zip_command);
$zip_info[] = "Shell exec command: $zip_command. <BR>Return value: $shell_exec_return.";

//$zip_command = 'echo %path%';
//$shell_exec_return = shell_exec($zip_command);
//echo("<BR>Shell exec command: $zip_command. Return value: $shell_exec_return.<BR>");

//for ($i = 0; $i < count($files_to_zip); ++$i) {
//    unlink($files_to_zip[$i]);
//}

$archiveName = "$save_folder/$zip_filename";
$zip_info[] = $archiveName;
$html = "<form action='download_zip.php' method='POST'>";
$html .= "<input type='hidden' name='zip_filename' value='$archiveName'>";
$html .= "<input type='hidden' name='saveas_zip_filename' value='$zip_filename'>";
$html .= "<input type='submit' value='Click to download zip of PDFs'>";
$html .= "</form>";

$return_zip_info = array(
    'part' => $part,
    'zip_filename_on_server' => $archiveName,
    'saveas_zip_filename' => $zip_filename,
    'zip_info' => $zip_info,
    'html' => $html
);

// clean all levels of output buffer
while (ob_get_level()) {
    ob_end_clean();
}
/*
header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: private",false);
header("Content-Type: application/zip");
header("Content-Disposition: attachment; filename=".$zip_filename );
header("Content-Length: ".filesize($archiveName));
readfile("$archiveName");
*/
// return all our data to an AJAX call
echo json_encode($return_zip_info);
exit;

