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
//(@include_once("./show_mark_questions.php")) OR die("Cannot include show_mark_questions<BR>");
(@include_once("./markstore_functions.php")) OR die("Cannot include markstore_functions<BR>");

$csv_filename = "project_marks_" . $module_prefix . $module1 . $module_postfix . "_" . $short_code . "_" . $year_begin . "-" . $year_end . ".csv";

//$tmpcsvfile = tempnam("dummy", "");
$tmpcsvfile = tempnam(PMA_TMP_DIR, "");
$path = dirname($tmpcsvfile);
//$csv_filename = $tmpcsvfile;
//unlink($tmpcsvfile);

$display_block = "<h1>CSV list of Projects Marks for $module_prefix$module1 $short_code $year_begin/$year_end</h1>\n";

$query = "select * from $project_allocated order by projectid";
//$get_student_list_res = mysql_query($get_student_list) or die(" Unable to create student list from $project_alloc table");
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    echo("Unable to update data to the table $project_allocated<BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$num = $results->rowCount();
if ($num < 1) {
    //no records
    $display_block .= "<p><em>Sorry, there are no records to select!</em></p>";
} else {
    //has records
    $fp = fopen($tmpcsvfile, 'w');

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

    $ret = fputcsv($fp, $row);

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

    $ret = fputcsv($fp, $row);
    //fclose($fp);

    $rows = $results->fetchAll();
    foreach ($rows as $recs) {
        $project_id = $recs['projectid'];
        $sid = $recs['sid'];
        $title = stripslashes($recs['title']);

        $supervisor_id = $recs['supervisor_id'];
        $second_marker_id = $recs['second_marker_id'];

        $supervisor_name = GetStaffNameFromID($db,$supervisor_id, $staff_names2, "0");
        $second_marker_name = GetStaffNameFromID($db,$second_marker_id, $staff_names2, "0");

        $query = "select * from $students where sid=$sid";
        //$get_list_res = mysql_query($get_list) or die("Unable to get student details from database. Query: $get_list");
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
        $student_recs = $results->fetch();

        if($student_recs){
            $student_name = $student_recs['name'];
            $username = $student_recs['username'];
            $programme = $student_recs['programme'];
            $sel = $student_recs['sel'];
        }else{
            $student_name = "Name not found from SID";
            $username = "Username not found from SID";
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
                //if (IsThirdMarkerRequired($sid, $i, $markstore_totals) ){
                $num_markers = 3;
                //}
            }

            for ($j = 1; $j <= $num_markers; $j++) {
                $markstore = $markstores[$i][$j];
                $query = "select * from $markstore where sid=$sid";
                //$get_list_res = mysql_query($get_list) or die("Unable to get marks from database. Query: $get_list");
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";
                    exit();
                }
                $marks_recs = $results->fetch();
                $total = 0.0;
                if($marks_recs) {
                    $total = $marks_recs['total'];
                }
                $row[$count] = $total;
                $count++;
            }
            if ($num_markers > 1) {
                $total = GetTotalForPart($db, $sid, $i, $markstore_totals);
                $row[$count] = $total;
                $count++;
            }
        }

        $markstore = $markstore_totals;
        $query = "select * from $markstore where sid=$sid";
        //$get_list_res = mysql_query($get_list) or die("Unable to get marks from database. Query: $get_list");
        //$marks_recs = mysql_fetch_array($get_list_res);
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $marks_recs = $results->fetch();

        $total = 0.0;
        if($marks_recs){
            $total = $marks_recs['total'];
        }
        $row[$count] = $total;

        //$fp = fopen('file.csv', 'w');
        $ret = fputcsv($fp, $row);
    }

    fclose($fp);

    header('Content-type: application/excel');
    header("Content-Disposition: attachment; filename=$csv_filename");
    readfile("$tmpcsvfile");

    $display_block = "<h2> Project marks saved as CSV file to: $csv_filename</h2>";
}
//mysql_close();
?>
