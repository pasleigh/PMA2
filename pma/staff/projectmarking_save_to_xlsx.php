<?php
(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./config.php")) OR die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once("./setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./connect_pdo.php")) OR die("Cannot connect to the database<BR>");

//if (array_key_exists('pm_id', $_REQUEST)==false){ $_REQUEST['pm_id']=null; }
//$pm_id=$_REQUEST['pm_id'];

(@include_once("./setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./staff_functions.php")) OR die("Cannot include staff_function.php<BR>");
(@include_once("./show_minor_part_feedback_form.php")) OR die("Cannot include show_minor_part_feedback<BR>");
(@include_once("./show_mark_questions.php")) OR die("Cannot include show_mark_questions<BR>");
(@include_once("./markstore_functions.php")) OR die("Cannot include markstore_functions<BR>");

set_include_path( get_include_path().PATH_SEPARATOR."..");

if (PHP_VERSION_ID <= 50637) {
    (@include_once("./xlsxwriter.class.php")) OR die("Cannot include xlsxwriter.class<BR>");
}else{
    require_once 'vendor/autoload.php';
}
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


$xlsx_filename = "project_marks_" . $module_prefix . $module1 . $module_postfix . "_" . $short_code . "_" . $year_begin . "-" . $year_end . ".xlsx";
$xlsx_sheet_name = $module_prefix . $module1 . $module_postfix . "_" . $year_begin . "-" . $year_end;

if ( !function_exists('sys_get_temp_dir')) {
    function sys_get_temp_dir() {
        if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
        if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
        if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
        $tempfile=tempnam(__FILE__,'');
        if (file_exists($tempfile)) {
            unlink($tempfile);
            return realpath(dirname($tempfile));
        }
        return null;
    }
}

//$tmpxlsxfile = tempnam("dummy", "");
$tmpxlsxfile = tempnam(sys_get_temp_dir(), "");
$path = dirname($tmpxlsxfile);
//echo "$tmpxlsxfile $path <br />";
//$xlsx_filename = $tmpxlsxfile;
//unlink($tmpxlsxfile);

$display_block = "<h1>Excel file format (.xlsx) list of Projects Marks for $module_prefix$module1 $short_code $year_begin/$year_end</h1>\n";

$query = "select * from $project_allocated order by projectid";
//$get_student_list_res = mysql_query($get_student_list) or die(" Unable to create student list from $project_alloc table");
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    echo("Unable to update data to the table $project_allocated<BR>\n");
    echo(" Query = $query<BR> \n");
    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
    exit();
}
$num = $results->rowCount();
if ($num < 1) {
    //no records
    $display_block .= "<p><em>Sorry, there are no records to select!</em></p>";
} else {
    //has records
    // So create the xls writer
    if (PHP_VERSION_ID <= 50637) {
        $writer = new XLSXWriter();
    }else{
        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        // delete the default active sheet
        $spreadsheet->removeSheetByIndex(0);
        $summary_worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, "Module Summary");
        $spreadsheet->addSheet($summary_worksheet, 0);
        $final_marks_worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, "Final_Marks_");
        $spreadsheet->addSheet($final_marks_worksheet, 1);
        $sheetIndex = 2;
        for ($i = 0; $i < $num_parts; $i++) {
            $filename = "Part " . ($i+1) . " Qs";
            $part_Qs_worksheets[$i] = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $filename);
            $spreadsheet->addSheet($part_Qs_worksheets[$i], $sheetIndex);
            $sheetIndex++;
        }
        for ($i = 0; $i < $num_parts; $i++) {
            $filename = "Part " . ($i+1) . " Marks";
            $part_Marks_worksheets[$i] = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $filename);
            $spreadsheet->addSheet($part_Marks_worksheets[$i], $sheetIndex);
            $sheetIndex++;
        }

    }
    //-------------------------------------------------------------
    // The module summary sheet
    $all_rows_for_output = array();
    // blank row
    $row[0] = "";
    $row[1] = "";
    $row[2] = "";
    $all_rows_for_output[] = $row;

    $row[0] = "Module name";
    $row[1] = "$module_short_description";
    $all_rows_for_output[] = $row;
    $row[0] = "Module code";
    $row[1] = "$module_prefix$module1$module_postfix";
    $row[2] = "$short_code";
    $all_rows_for_output[] = $row;
    $row[0] = "Year begin";
    $row[1] = $year_begin;
    $row[2] = "";
    $all_rows_for_output[] = $row;
    $row[0] = "Year end";
    $row[1] = $year_end;
    $all_rows_for_output[] = $row;

    $row[0] = "Module leader";
    $row[1] = $module_leader_fullname;
    $all_rows_for_output[] = $row;
    $module_leader_email = GetStaffEmailFromFullname($db, $module_leader_fullname, $staff_names);
    $row[0] = "Module leader email";
    $row[1] = $module_leader_email;
    $all_rows_for_output[] = $row;

    // blank row
    $row[0] = "";
    $row[1] = "";
    $all_rows_for_output[] = $row;

    $row[0] = "Number of assessment parts";
    $row[1] = $num_parts;
    $all_rows_for_output[] = $row;
    $row[0] = "Acceptable variation";
    $row[1] = $accept_var;
    $all_rows_for_output[] = $row;
    $row[0] = "Pass mark";
    $row[1] = $pass_mark;
    $all_rows_for_output[] = $row;
    $row[0] = "Min number of feedback words";
    $row[1] = $min_feedback_words;
    $all_rows_for_output[] = $row;

    // blank row
    $row[0] = "";
    $row[1] = "";
    $all_rows_for_output[] = $row;
    $row[0] = "Assessment parts";
    $row[1] = "";
    $all_rows_for_output[] = $row;
    for ($i = 1; $i <= $num_parts; $i++) {
        $row[0] = "Part";
        $row[1] = $i;
        $all_rows_for_output[] = $row;

        $row[0] = "Description";
        $row[1] = $p_description[$i];
        $all_rows_for_output[] = $row;

        $row[0] = "% of module";
        $row[1] = $p_percent[$i];
        $all_rows_for_output[] = $row;

        $row[0] = "Maximum mark";
        $row[1] = $p_max[$i];
        $all_rows_for_output[] = $row;

        $row[0] = "Number of markers";
        $row[1] = $p_num_markers[$i];
        $all_rows_for_output[] = $row;

        $row[0] = "Used in the assessment";
        if ($p_use[$i] == 1) {
            $row[1] = "Yes";
        } else {
            $row[1] = "No";
        }
        $all_rows_for_output[] = $row;

        $row[0] = "Response type";
        if ($p_numeric[$i] == 0) {
            $row[1] = "Phrase";
        } else {
            $row[1] = "Numeric";
        }
        $all_rows_for_output[] = $row;

        // blank row
        $row[0] = "";
        $row[1] = "";
        $all_rows_for_output[] = $row;
        //echo("<BR>part$i<BR>Submission date: $p_submission_date[$i]<BR>");
        //echo("Marking date: $p_marking_date[$i]<BR>");
        //echo("Num markers: $p_num_markers[$i]<BR>");
        //echo("Use: $p_use[$i]<BR>");
        //echo("Percent: $p_percent[$i]<BR>");
        //echo("Max: $p_max[$i]<BR>");
        //echo("Description: $p_description[$i]<BR>");
        //echo("Numeric Qs: $p_numeric[$i]<BR>");
    }

    // Write the summary sheet to the xlsxwriter

    //$writer->writeSheetHeader($xlsx_sheet_name, $header);
    $rowIndex = 1;
    $colIndex = 1;
    foreach ($all_rows_for_output as $row_text){
        if (PHP_VERSION_ID <= 50637) {
            $writer->writeSheetRow("Module Summary", $row_text);
        }else{
            for($colIndex = 1; $colIndex <= count($row_text); $colIndex++) {
                $summary_worksheet->setCellValue([$colIndex, $rowIndex], $row_text[$colIndex-1]);
            }
            $rowIndex++;
        }
    }
    //================================================================================================

    //-------------------------------------------------------------
    // The module marks sheet
    $all_rows_for_output = array();
    $row[0] = "";
    $row[1] = "";
    $row[2] = "";
    $row[3] = "";
    $row[4] = "";
    $row[5] = "";
    $row[6] = "";
    $row[7] = "";
    $count = 8;
    for ($i = 1; $i <= $num_parts; $i++) {
        $part = "p$i" . "_num_markers";
        $num_markers = $setup_res[$part];
        if ($num_markers == 2) {
            $num_markers = 3;
        }
        for ($j = 1; $j <= $num_markers; $j++) {
            $pc = $p_percent[$i] / 100.0;
            if ($num_markers == 1) {
                $row[$count] = "$pc";
            } else {
                $row[$count] = "";
            }
            $count++;
        }
        if ($num_markers > 1) {
            $row[$count] = "$pc";
            $count++;
        }
    }
    $row[$count] = "";

    // add the row
    $all_rows_for_output[] = $row;
    //$ret = fputcsv($fp, $row);

    // Title row
    $row[0] = "Student name";
    $row[1] = "SID";
    $row[2] = "Username";
    $row[3] = "Programme";
    $row[4] = "Project ID";
    $row[5] = "Project title";
    $row[6] = "Supervisor";
    $row[7] = "2nd Marker";
    $count = 8;
    for ($i = 1; $i <= $num_parts; $i++) {
        $part = "p$i" . "_num_markers";
        $num_markers = $setup_res[$part];
        if ($num_markers == 2) {
            $num_markers = 3;
        }
        for ($j = 1; $j <= $num_markers; $j++) {
            $row[$count] = "$p_description[$i] mark $j";
            $count++;
        }
        if ($num_markers > 1) {
            $row[$count] = "$p_description[$i] total";
            $count++;
        }
    }
    $row[$count] = "Final Mark";

    // add the row
    $all_rows_for_output[] = $row;

    $rows = $results->fetchAll();
    foreach ($rows as $recs) {
        $project_id = $recs['projectid'];
        $sid = $recs['sid'];
        $title = stripslashes($recs['title']);

        $supervisor_id = $recs['supervisor_id'];
        $second_marker_id = $recs['second_marker_id'];

        $supervisor_name = GetStaffNameFromID($db, $supervisor_id, $staff_names2, "0");
        $second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff_names2, "0");

        $query = "select * from $students where sid=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $students<BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
            exit();
        }
        $student_recs = $results->fetch();

        if($student_recs) {
            $student_name = $student_recs['name'];
            $username = $student_recs['username'];
            $programme = $student_recs['programme'];
            $sel = $student_recs['sel'];
        }else{
            $student_name = "unable to find studnet name from SID";
            $username = "unable to find username from SID";
            $programme = "unknown";
            $sel = "";
        }


        $row[0] = $student_name;
        $row[1] = $sid;
        $row[2] = $username;
        $row[3] = $programme;
        $row[4] = $project_id;
        $row[5] = $title;
        $row[6] = $supervisor_name;
        $row[7] = $second_marker_name;

        $count = 8;
        // get the part marks from SID
        for ($i = 1; $i <= $num_parts; $i++) {
            $part = "p$i" . "_num_markers";
            $num_markers = $setup_res[$part];
            if ($num_markers == 2) {
                $num_markers = 3;
            }

            for ($j = 1; $j <= $num_markers; $j++) {
                $markstore = $markstores[$i][$j];
                $query = "select * from $markstore where sid=$sid";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    echo(" Query = $query<BR> \n");
                    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                    exit();
                }
                $marks_recs = $results->fetch();
                $total = 0.0;
                if($marks_recs){
                    $total = $marks_recs['total'];
                }
                $row[$count] = $total;
                $count++;
            }
            if ($num_markers > 1) {
                $total = GetTotalForPart($db, $sid, $i, $markstore_totals);
                $row[$count] = $total;
                //echo("Count: $count. Total=$total<BR>");
                $count++;
            }
        }

        $markstore = $markstore_totals;
        $query = "select * from $markstore where sid=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
            exit();
        }
        $marks_recs = $results->fetch();

        $total = 0.0;
        if($marks_recs){
            $total = $marks_recs['total'];
        }
        $row[$count] = $total;

        // add the row
        $all_rows_for_output[] = $row;
    }

    // Write the module marks to the xlsxwriter
    $rowIndex = 1;
    foreach ($all_rows_for_output as $row_text){
        if (PHP_VERSION_ID <= 50637) {
            $writer->writeSheetRow("Final_Marks_", $row_text);
        }else{
            for($colIndex = 1; $colIndex <= count($row_text); $colIndex++) {
                $final_marks_worksheet->setCellValue([$colIndex, $rowIndex], $row_text[$colIndex-1]);
            }
            $rowIndex++;
        }
    }
    //==================================================================================================

    // Part Questions
    $row = array();
    // Initialise to the longest number of responses
    // It seems like the row need to be the same length for all output
    $row_len = 250;
    $some_data = '';
    for ($i = 0; $i < $row_len; $i++)
    {
        // one of these
        //array_push($row, $some_data);
        $row[] = $some_data;
    }

    $cat_weights = array();
    $cat_factors = array();

    for ($i = 1; $i <= $num_parts; $i++) {
        // empty the row
        for ($j = 0; $j < count($row); $j++) {
            $row[$j] = '';
        }
        $q_count = 0;
        $xlsx_part_sheet_name = "Part $i Qs";
        $all_rows_for_output = array();

        $row[0] = "Part";
        $row[1] = $i;
        $row[2] = "";
        $row[3] = "";
        $row[4] = "";
        $all_rows_for_output[] = $row;

        $row[0] = "Description";
        $row[1] = $p_description[$i];
        $all_rows_for_output[] = $row;

        $row[0] = "";
        $row[1] = "";
        $all_rows_for_output[] = $row;

        $row[0] = "Question No.";
        $row[1] = "Section No.";
        $row[2] = "Section title";
        $row[3] = "Question";
        $row[4] = "Section Q No.";
        $row[5] = "Weight %";
        $row[6] = "Factor";
        $all_rows_for_output[] = $row;

        // Questions table
        $assessqs_year = $assessqs[$i];
        // Questions categories table
        $assess_cats = $assessqs_cats[$i];

        // Get number of categories by counting the rows in $assess_cats
        $query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");

            exit();
        }
        $num_categories = $results->rowCount();
        $cat_count = $num_categories;

        $cat_weights[$i] = array();
        $cat_factors[$i] = array();

        for ($cat = 1; $cat <= $cat_count; $cat++) {
            // Get the category titles
            $query = "select description, weight_percent, factor from $assess_cats where cat=$cat";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");

                exit();
            }
            $cat_data = $results->fetch();
            $weight = $cat_data['weight_percent'];
            $factor = $cat_data['factor'];
            $description = $cat_data['description'];

            $cat_weights[$i][$cat] = $weight;
            $cat_factors[$i][$cat] = $factor;

            $query = "select * from $assessqs_year where cat=$cat";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");

                exit();
            }
            $num = $results->rowCount();

            if ($num < 1) {
                //no questions
                $display_block .= "<em>Sorry, there are no questions in this catagory select!</em>";
            } else {
                //has questions
                for ($count = 1; $count <= $num; $count++) {
                    // empty the row
                    for ($j = 0; $j < count($row); $j++) {
                        $row[$j] = '';
                    }

                    $q_count++;
                    $row[0] = $q_count;
                    $row[1] = $cat;
                    $row[2] = $description;


                    $query = "select * from $assessqs_year where cat=$cat and qid=$count";
                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo "An Error occured accessing the database in function: $this_function <BR>\n";
                        echo(" Query = $query<BR> \n");
                        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

                        exit();
                    }
                    $qs_list = $results->fetch();
                    $question = $qs_list['question'];
                    $entries = $qs_list['entries'];

                    $row[3] = $question;
                    $row[4] = $entries;

                    $row[5] = $weight;
                    $row[6] = $factor;

                    for ($j = 1; $j <= $entries; $j++) {
                        $text_id = 'text' . $j;
                        $mark_id = 'mark' . $j;
                        $text =  $qs_list[$text_id];
                        $mark =  $qs_list[$mark_id];
                        $row[7+($j-1)*2] = $text;
                        $row[7+($j-1)*2+1] = $mark;
                    }
                    $all_rows_for_output[] = $row;
                    //print_r3('all_rows_for_output',$all_rows_for_output);
                }
            }
        }

        // Write this part questions to the xlsxwriter
        $rowIndex = 1;
        foreach ($all_rows_for_output as $row_text){
            if (PHP_VERSION_ID <= 50637) {
                $writer->writeSheetRow($xlsx_part_sheet_name, $row_text);
            }else{
                for($colIndex = 1; $colIndex <= count($row_text); $colIndex++) {
                    $part_Qs_worksheets[$i-1]->setCellValue([$colIndex, $rowIndex], $row_text[$colIndex-1]);
                }
                $rowIndex++;
            }
        }

    }

    //-------------------------------------------------------------
    // The part marks sheets

    for($k = 1 ; $k <= $num_parts; $k++) {
        // empty the row
        for ($j = 0; $j < count($row); $j++) {
            $row[$j] = '';
        }
        $q_count = 0;
        $xlsx_part_sheet_name = "Part $k Marks";
        $all_rows_for_output = array();

        $row[0] = "Part";
        $row[1] = $k;
        $row[2] = "marks";
        $all_rows_for_output[] = $row;

        $row[0] = "Description";
        $row[1] = "$p_description[$k]";
        $row[2] = "";
        $all_rows_for_output[] = $row;

        $part = "p$k" . "_num_markers";
        $num_markers = $setup_res[$part];
        if ($num_markers == 2) {
            $num_markers = 3;
        }

        // add the row
        $row[0] = "Num markers";
        $row[1] = $num_markers;
        $row[2] = '';
        $all_rows_for_output[] = $row;


        // Get the number of categories
        // then
        // Get the number of questions in each category

        // Questions table
        $assessqs_year = $assessqs[$k];
        // Questions categories table
        $assess_cats = $assessqs_cats[$k];

        // Get number of categories by counting the rows in $assess_cats
        $query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");

            exit();
        }
        $num_categories = $results->rowCount();
        $cat_count = $num_categories;

        $row[0] = 'Num sections';
        $row[1] = $num_categories;
        $row[2] = '';
        $all_rows_for_output[] = $row;

        $row[0] = 'Qs per section';

        $cat_q_count = array();
        for ($cat = 1; $cat <= $cat_count; $cat++) {
            $query = "select * from $assessqs_year where cat=$cat";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");

                exit();
            }
            $num = $results->rowCount();

            $cat_q_count[$cat] = $num;
            $row[$cat] = $num;
        }
        $all_rows_for_output[] = $row;

        // blank row
        // empty the row
        for ($j = 0; $j < count($row); $j++) {
            $row[$j] = '';
        }
        $all_rows_for_output[] = $row;

        // All projects list query
        $query = "select * from $project_allocated order by projectid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $project_allocated<BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
            exit();
        }
        $rows = $results->fetchAll();

        // Title row
        $row[0] = "Student name";
        $row[1] = "SID";
        $row[2] = "Username";
        $row[3] = "Project ID";
        $count = 4;
        for($m = 1; $m <= $num_markers; $m++) {
            for ($cat = 1; $cat <= $cat_count; $cat++) {
                for ($j = 1; $j <= $cat_q_count[$cat]; $j++) {
                    $label = "Sec" . $cat . "Q" . $j . "(m$m)";
                    if ($j == $cat_q_count[$cat]) {
                        $label = "Sec" . $cat . "Feedback" . "(m$m)";
                    }
                    $row[$count] = $label;
                    $count++;
                }
            }
        }
        $all_rows_for_output[] = $row;

        // empty the row
        for ($j = 0; $j < count($row); $j++) {
            $row[$j] = '';
        }

        // Loop through each project / student
        foreach ($rows as $recs) {
            $project_id = $recs['projectid'];
            $sid = $recs['sid'];
            //$title = stripslashes($recs['title']);

            $query = "select * from $students where sid=$sid";
            //$get_list_res = mysql_query($get_list) or die("Unable to get student details from database. Query: $get_list");
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                echo("Unable to update data to the table $students<BR>\n");
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                exit();
            }
            $student_recs = $results->fetch();

            if($student_recs) {
                $student_name = $student_recs['name'];
                $username = $student_recs['username'];
                $programme = $student_recs['programme'];
                $sel = $student_recs['sel'];
            }else{
                $student_name = "unable to find studnet name from SID";
                $username = "unable to find username from SID";
                $programme = "unknown";
                $sel = "";
            }

            $row[0] = $student_name;
            $row[1] = $sid;
            $row[2] = $username;
            $row[3] = $project_id;

            $count = 4;

            // get the part marks from SID
            for ($j = 1; $j <= $num_markers; $j++) {
                $markstore = $markstores[$k][$j];
                $query = "select * from $markstore where sid=$sid";
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    echo(" Query = $query<BR> \n");
                    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                    exit();
                }
                $marks_recs = $results->fetch();

                for ($cat = 1; $cat <= $cat_count; $cat++) {
                    for ($n = 1; $n <= $cat_q_count[$cat]; $n++) {
                        $label = "C" . $cat . "Q" . $n;
                        if ($n == $cat_q_count[$cat]) {
                            $label = "C" . $cat . "Feedback";
                        }
                        $mark = 0.0;
                        if($marks_recs){
                            $mark = $marks_recs[$label];
                        }
                        $real_mark = $mark;
                        if ($n != $cat_q_count[$cat]) {
                            continue;
                            // The real mark for any question is factored and weighted
                            //$real_mark = $mark * $cat_weights[$k][$cat] * $cat_factors[$k][$cat] / 100.0;
                            //$real_mark = $mark ." : " . $cat_weights[$k][$cat] ." : " . $cat_factors[$k][$cat];
                        }
                        $row[$count] = $real_mark;
                        $count++;
                    }
                }
            }
            // add the row
            $all_rows_for_output[] = $row;

        }

        // Write the module marks to the xlsxwriter
        $rowIndex = 1;
        foreach ($all_rows_for_output as $row_text){
            if (PHP_VERSION_ID <= 50637) {
                $writer->writeSheetRow($xlsx_part_sheet_name, $row_text);
            }else{
                for($colIndex = 1; $colIndex <= count($row_text); $colIndex++) {
                    $part_Marks_worksheets[$k-1]->setCellValue([$colIndex, $rowIndex], $row_text[$colIndex-1]);
                }
                $rowIndex++;
            }
        }
    }
    //==================================================================================================

    if (PHP_VERSION_ID <= 50637) {
        // ======================
        // Output to the browser
        // ======================
        header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($xlsx_filename).'"');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        $writer->writeToStdOut();
    }else{
        $spreadsheet_writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        //$spreadsheet_writer->save($xlsx_filename);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($xlsx_filename).'"');
        ob_clean();
        flush();
        $spreadsheet_writer->save('php://output');
    }
    $display_block = "<h2> Project marks saved as Excel (.xlsx) file to: $xlsx_filename</h2>";
}
?>
