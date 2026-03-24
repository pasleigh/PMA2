<?php
ob_start(); // Buffer all output — required so mPDF can set Content-Type headers even if something echoes earlier
// Turn off all error reporting
//error_reporting(1);
(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./config.php")) OR die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once("./setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./connect_pdo.php")) OR die("Cannot connect to the database<BR>");

//if (array_key_exists('pm_id', $_REQUEST)==false){ $_REQUEST['pm_id']=null; }
//$pm_id=$_REQUEST['pm_id'];

(@include_once("./setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./staff_functions.php")) OR die("Cannot include staff_function.php<BR>");
(@include_once("./show_feedback_form.php")) OR die("Cannot include show_feedback<BR>");
(@include_once("./show_mark_questions2_bs.php")) OR die("Cannot include show_mark_questions2<BR>");
(@include_once("./markstore_functions.php")) OR die("Cannot include markstore_function.php<BR>");
(@include_once("./pdo_query_functions.php")) OR die("Cannot include pdo_query_functions<BR>");

require_once __BASE_DIR__ . '/vendor/autoload.php';

//if (array_key_exists('assesstype', $_GET)==false){ $_GET['assesstype']=null; }
//if (array_key_exists('module1', $_GET)==false){ $_GET['module1']=null; }
//if (array_key_exists('year_begin', $_GET)==false){ $_GET['year_begin']=null; }
if (array_key_exists('sid', $_GET) == false) {
    $_GET['sid'] = null;
}
if (array_key_exists('pid', $_GET) == false) {
    $_GET['pid'] = null;
}
if (array_key_exists('part', $_GET) == false) {
    $_GET['part'] = null;
}
if (array_key_exists('who', $_GET) == false) {
    $_GET['who'] = null;
}
if (array_key_exists('marker_id', $_GET) == false) {
    $_GET['marker_id'] = null;
}
if (array_key_exists('topdf', $_GET) == false) {
    $_GET['topdf'] = null;
}
if (array_key_exists('force_pdf', $_GET) == false) {
    $_GET['force_pdf'] = null;
}
if (array_key_exists('isnumeric', $_GET) == false) {
    $_GET['isnumeric'] = 0;
}
if (array_key_exists('o_f', $_GET) == false) {
    $_GET['overall_feedback'] = 0;
}
//$module1 = $_GET['module1'];
//$year_begin = $_GET['year_begin'];
$display_block = "";
$sid = $_GET["sid"];
$project_id = $_GET["pid"];
$assesspart = $_GET['part'];
$who = $_GET['who'];
$marker_id = $_GET['marker_id'];
$topdf = $_GET['topdf'];
$force_pdf = $_GET['force_pdf'];
$is_numeric = $_GET['isnumeric'];
$show_overall_feedback_flag = $_GET['o_f'];
//echo "sid = ". $sid . "<br>";
//echo "pid = ". $project_id . "<br>";
//echo "assesspart = ". $assesspart . "<br>";
//echo "who = ". $who . "<br>";
//echo "marker_id = ". $marker_id . "<br>";
//echo "topdf = ". $topdf . "<br>";
//echo "force_pdf = ". $force_pdf . "<br>";
//echo "Is numeric = " . $is_numeric . "<BR>";

// if the form was submitted then enter it all into the database
//echo("_POST[op]=" . $_POST['op'] . "<BR>\n");
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}
if (isset($_POST['disagree'])) {
    $POST_OP = "disagree";
}
//echo("POST_OP =" . $POST_OP . "<BR>\n");

// Check all the POST values
// Check if feedback text is base64 encoded, and decode as necessary
foreach ($_POST as $key => $value){
    if(str_contains($key, "feedback")){
        // decode, returns "false" if it is NOT a base 64 string
        $decoded_value = base64_decode($value, true);
        if($decoded_value !== false){
            // use the decoded string
            // replace the $_POST value
            $_POST[$key] = $decoded_value;
        }
        //echo "{$key} = {$value} {$decoded_value}<BR>\r\n";
    }
}

$project_marking_page = "projectmarking_bs.php";

// definitions for each part
// Questions table
$assessqs_year = $assessqs[$assesspart];
// Questions categories table
$assess_cats = $assessqs_cats[$assesspart];
// description 
$assesstype_text = $p_description[$assesspart];
// markbook
$who = 1;
$markstore1 = $markstores[$assesspart][$who];
$who = 2;
$markstore2 = $markstores[$assesspart][$who];
$who = 3;
$markstore3 = $markstores[$assesspart][$who];
$mark_max = $p_max[$assesspart];
$mark_pc = $p_percent[$assesspart];

//echo("Assess_cats table = $assess_cats<BR>\n");
//echo("Assessqs_year table = $assessqs_year<BR>\n");
//echo("Assesstype_text = $assesstype_text<BR>\n");
//echo("max mark for this part = $mark_max<BR>");
//echo("percent of final assesment = $mark_pc<BR>");
//echo("Who = $who<BR>\n");
//echo("markstore1 = $markstore1<BR>\n");
//echo("markstore2 = $markstore2<BR>\n");

// Get number of categories by counting the rows in $assess_cats
$query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
//$myquery_res = mysql_query($myquery )or die(" Unable to get assess_cats data from $assess_cats table. <BR>Query: $myquery");
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}

$num_categories = $results->rowCount();
//echo("num categories = $num_categories<BR>\n");

$marker1 = " (marker)";
$marker2 = " (marker)";

//
// Get title and supervisor
$query = "select * from $project_allocated where projectid = $project_id";
//$get_list_res = mysql_query($get_list) or die(" Unable to create list from $project_allocated table. Query: $get_list");
//$recs = mysql_fetch_array($get_list_res);
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$recs = $results->fetch();

$master_id = $recs['masterid'];
if($master_id != null) {
    $master_id = stripslashes($master_id);
}
$title = stripslashes($recs['title']);
$supervisor_id = stripslashes($recs['supervisor_id']);
$second_marker_id = stripslashes($recs['second_marker_id']);
$student_inactive = $recs['inactive'];

$supervisor = GetStaffNameFromID($db, $supervisor_id, $staff2, NULL);

$i = $assesspart;
$j = 1;
$marker_entry = "marker_p$i" . "m$j" . "_id";
$marker_id = $recs[$marker_entry];
$marker_name = GetStaffNameFromID($db, $marker_id, $staff2, NULL);//. " ($marker_entry, $marker_id)";

$j = 2;
$marker_entry = "marker_p$i" . "m$j" . "_id";
$second_marker_id = $recs[$marker_entry];
$second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff2, NULL);

//echo("supervisor = $supervisor<BR>\n");
//echo("marker name = $marker_name<BR>\n");
//echo("second marker name = $second_marker_name<BR>\n");
//
// Get the student name from SID
$query = "select * from $students where sid = $sid";
//echo $get_list . "<BR>";
//$get_list_res = mysql_query($get_list) or die(" Unable to create list from $students table. Query: $get_list");
//$recs = mysql_fetch_array($get_list_res);
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$recs = $results->fetch();

$student_name = $recs['name'];
$username = $recs['username'];
$programme = $recs['programme'];
$sel = $recs['sel'];

// get the mark for this part from SID
// Get the supervisor marks
$query = "select * from $markstore1 where sid = $sid";
//echo $get_list . "<BR>";
//$get_list_res = mysql_query($get_list) or die(" Unable to create list from $markstore1 table. Query: $get_list");
//$recs = mysql_fetch_array($get_list_res);
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$recs = $results->fetch();

if($recs) {
    $mark1 = $recs['total'];
    $locked = $recs['locked'];
}else{
    $mark1 = 0;
    $locked = 0;
}
//echo("Mark 1: $mark1 <BR>");

// Get the second marker marks
$query = "select * from $markstore2 where sid = $sid";
//$get_list_res = mysql_query($get_list) or die(" Unable to create list from $markstore2 table. Query: $get_list");
//$recs = mysql_fetch_array($get_list_res);
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$recs = $results->fetch();

if($recs) {
    $mark2 = $recs['total'];
    $locked = $recs['locked'];
}else{
    $mark2 = 0;
    $locked = 0;
}
//echo("Mark 2: $mark2 <BR>");

if ($POST_OP == "commit") {
    $display_block .= "<h2>Submitting the changes to the database ...</H2>";

    // Get the data from the form
    $mark_total = 0.0;

    for ($who = 1; $who <= 2; $who++) {
        $mark_total_all_cats = 0.0;

        for ($cat = 1; $cat <= $num_categories; $cat++) {
            // Get the category marks split and weight
            $query = "select * from $assess_cats where cat=$cat";
            //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. <BR>Query: $get_cat_details");
            //$cat_data = mysql_fetch_array($get_cat_details_list);
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
            $cat_data = $results->fetch();

            $weight_pc = $cat_data['weight_percent'];
            $factor = $cat_data['factor'];
            if(array_key_exists('get_feedback',$cat_data)==false){
                $show_cat_feedback = 1;
            }else{
                $show_cat_feedback = $cat_data['get_feedback'];
            }
            /*
            $id = "sec" . $who . $cat;
            $section_q[$cat] = addslashes($_POST[$id]);
            $id = "weightpc_name" . $who . $cat;
            $section_weight[$cat] = $_POST[$id];
            $id = "factor_name" . $who . $cat;
            $section_factor[$cat] = $_POST[$id];
            */
            //echo("Section: $cat : $section_q[$cat]<BR>\n");
            //echo("  weight: $section_weight[$cat]<BR>\n");
            //echo("  factor: $section_factor[$cat]<BR>\n");

            // initialise the marks array
            for ($i = 1; $i <= 10; $i++) {
                $rmarks[$i] = 0;
            }

            $id = "numqs" . $who . $cat;
            $numqs = $_POST[$id];
            $marktot = 0.0;
            $mark_query_string = "";
            $marks_array = array();
            for ($i = 1; $i < $numqs; $i++) {
                //$id = "q$who$cat-$i";
                //$question = addslashes($_POST[$id]);
                //echo("$id : $question<BR>");

                $q_weight = 1.0;
                $is_numeric_name = $is_numeric;//$_POST['is_numeric_name'];
                if($is_numeric_name = 1) {
                    $id = "qw$who$cat-$i";
                    $q_weight_pc = $_POST[$id];
                    $q_weight = $q_weight_pc * 0.01;
                }

                $id = "r$who$cat-$i";
                $rmark = $_POST[$id];
                $rmark_w = $rmark * $q_weight;
                $rmarks[$i] = $rmark_w;
                //echo("$id : $rmark<BR>");
                $marktot = $marktot + $rmark_w;

                $mark_query_string .= "C$cat" . "Q$i=$rmark_w, ";
                $key = "C$cat" . "Q$i";
                $marks_array[$key] = $rmark_w;
            }
            $id = "feedback" . $who . $cat;
            if(array_key_exists($id,$_POST) and $show_cat_feedback == 1) {
                $feedback_text = $_POST[$id];
                $feedback_text = convert_smart_quotes($feedback_text);
                $feedbacks[$cat] = addslashes($feedback_text);
                //echo("Feedback: $feedbacks[$cat]<BR>");
                $mark_query_string .= "C$cat" . "Feedback='$feedbacks[$cat]',";
            }else{
                $feedback_text = "";
            }

            $marktot = $marktot * $factor;
            $mark_total_all_cats += $marktot;
            //echo("factor: $factor <BR>");
            //echo("marktot : $marktot<BR>");
            //echo("mark_total_all_cats : $mark_total_all_cats<BR>");

            //echo("Mark query: $mark_query_string<BR>");
            if ($who == 1) {
                $markstore = $markstore1;
            } else {
                $markstore = $markstore2;
            }

            $query = "select count(SID) from $markstore where SID=$sid";
            //$get_list_marks = mysql_query($get_marks) or die(" Unable to create list from table $markstore. Query = $get_marks");
            //$get_marks_res = mysql_fetch_array($get_list_marks,MYSQL_NUM);
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
            $get_marks_res = $results->fetch();
            // Clean the feedback
            //$feedback_text = $feedbacks[$cat] ;
            //$feedback_text = convert_smart_quotes($feedback_text);
            //$feedbacks[$cat] = addslashes($feedback_text);

            if ($get_marks_res[0] == 0) {
                $query = "insert $markstore set SID='$sid', $mark_query_string
                         C$cat" . "total" . "=$marktot, total=$mark_total_all_cats
                         ";

                $CXtotal = "C$cat" . "total";
                $Ctotal = "total";
                //$CXFeedback = "C$cat" . "Feedback";
                $query2 = "INSERT $markstore SET SID=:sid, $CXtotal=:cat_total, total=:all_cats_total";

                $data =array(
                    $CXtotal => $marktot,
                    $Ctotal => $mark_total_all_cats,
                    //$CXFeedback => $feedback_text,
                    'sid' => $sid
                );
                // tag on the marks
                foreach ($marks_array as $key => $value) {
                    $data[$key] = $value;
                }
                $results = perform_insert_prepared_query($db, $markstore, $data);
            } else {
                $query = "update $markstore set $mark_query_string
                         C$cat" . "total" . "=$marktot, total=$mark_total_all_cats
                         where SID='$sid'";

                $CXtotal = "C$cat" . "total";
                $Ctotal = "total";
                //$CXFeedback = "C$cat" . "Feedback";
                $query2 = "UPDATE $markstore SET $CXtotal=:cat_total, total=:all_cats_total WHERE SID=:sid ";
                $data =array(
                    $CXtotal => $marktot,
                    $Ctotal => $mark_total_all_cats,
                    //$CXFeedback => $feedback_text
                );
                // tag on the marks
                foreach ($marks_array as $key => $value) {
                    $data[$key] = $value;
                }
                $where_data =array(
                    'sid' => $sid
                );
                $results = perform_update_prepared_query($db, $markstore, $data, $where_data);
            }
            //echo $action . "<BR>";
            //mysql_query($action) or die(" Unable to add mark data to the table $markstore. <BR /> Query = $action");
            try {
                //$results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
        }// $cat
        $mark_total += $mark_total_all_cats;
        //echo("mark total $mark_total  total all cats $mark_total_all_cats<BR>");

        $overall_feedback_id = "overall_feedback$who";

        if(array_key_exists($overall_feedback_id,$_POST) and $show_overall_feedback_flag == 1){
            $overall_feedback_text = $_POST[$overall_feedback_id];
            $overall_feedback_text = convert_smart_quotes($overall_feedback_text);
            $overall_feedback_text = addslashes($overall_feedback_text);
            // Push it to the db
            $query = "UPDATE $markstore SET OverallFeedback='$overall_feedback_text' WHERE SID='$sid'";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
        }
    }// $who

    $mark_total *= 0.5;
    $mark_total_s = sprintf("%5.2f", (float)$mark_total);

    // Enter the marks in markstore totals
    $query = "select locked from $markstore_totals where SID='$sid'";
    //$list_res = mysql_query($query);
    //$res = mysql_fetch_array($list_res);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $res = $results->fetch();

    if($res){
        $locked_stat = $res['locked'];
    }

    //echo("Enter markstore totals data<BR>");
    $num = $results->rowCount();
    if ($num > 0) {
        // exists, so update
        $query = "update $markstore_totals set retmark$assesspart=$mark_total,  locked=0, mark_method$assesspart='0011' where SID='$sid'";
        //echo "<BR> Action = " . $action . "<br>";
        //mysql_query($action) or die(" Unable to add mark data to the database $markstore_totals. <BR>Query: $action");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
    } else {
        $query = "insert $markstore_totals set SID='$sid', retmark$assesspart=$mark_total,  locked=0, mark_method$assesspart='0011'";
        //echo "<BR> Action = " . $action . "<br>";
        //mysql_query($action) or die(" Unable to add mark data to the database $markstore_totals. <BR>Query: $action");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
    }

    $display_block = "<div class='container'>";
    $display_block .= "<div class='container-fluid'>";

    $display_block = "<h1>Assessment of $module_short_description : $assesstype_text, by $student_name</h1>";
    $display_block .= "<table class=\"tableGradeTop\" >
          <tr>
          <td><b>Student: </b>&nbsp; $student_name&nbsp;&nbsp;</td>
          </tr>
          <tr>
          <td><b>SID: </b>$sid</td>
          </tr>
          <tr>
          <td><b>Title: </b>&nbsp; <i>$title</i></td>
          </tr>
          <tr>
          <td><b>Project ID: </b>&nbsp; <i>$project_id</i></td>
          </tr>
          <tr>
          <td><b>Report Mark: </b>$mark_total_s%</td>
          </tr>
          <tr>
          <td><b>Supervisor: </b>&nbsp; $supervisor&nbsp;&nbsp;</td>
          </tr>
          <tr>
          <td><b>1st Marker: </b>&nbsp; $marker_name&nbsp;&nbsp;</td>
          </tr>
          <tr>
          <td><b>2nd Marker: </b>&nbsp; $second_marker_name&nbsp;&nbsp;</td>
          </tr>
         </table><br />";
    //$display_block .="<h2>Done ... carry on making changes if you like</H2>";
    $display_block .= "<span><BR>
            <a target=\"_self\" href=\" ./$project_marking_page?pm_id=$pm_id#$username\" style=\"color: dimgray;\">Back to full marking list</a>
      or
            <a target = \"_self\" href = \"./$project_marking_page?pm_id=$pm_id&user=1\" style = \"color: dimgray;\" > User marking list</a >
            </span>\n";
    $display_block .= "</div>";// close class='container-fluid'
} else if ($POST_OP == "disagree") {// end of if ($POST_OP == "commit")
    $display_block .= "<h2>Markers cannot agree - setting-up requirement for third marker ...</H2>";
    $display_block .= SetThirdMarkerRequired($db, $sid, $assesspart, $markstore_totals);
    $display_block .= "<span><BR><a target=\"_self\" href=\"./$project_marking_page?pm_id=$pm_id#$username\" style=\"color: dimgray;\">Back to marking list</a></span>\n";

} else {// end of if ($POST_OP == "disagree")

    //$locked=1;
    //echo("Locked = $locked<BR>");
    if ($locked == 1 || $force_pdf == 1) {
        //$display_block = "<h1>Project Report Mark Sheet (<span class=\"red\">Marked and Locked</span>)</h1>";
        $display_block = " ";
        //echo "$sid,$markstore1,$markstore2,$markstore3,$assess_cats,$assessqs,$student_name,$username,$sid,$title,$supervisor,$second_marker_name,$project_id<BR>";
        //$display_block .= show_minor_part_feedback_form($sid,$markstore,$assessqs_year,$student_name,$username,$title,$supervisor,$second_marker_name,$project_id,$level,$assesstype_text,$assess_cats,$mark_max,$mark_pc);
        $display_block .= show_feedback_form2($db, $sid, $markstore1, $markstore2, $markstore3, $markstore_totals, $assessqs_year, $student_name, $username, $title, $supervisor, $second_marker_name, $marker_name, $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $assesspart, $assesstype_text, $module_short_description, $is_numeric);
        if($show_overall_feedback_flag == 1) {
            $display_block .= show_overall_feedback_form2($db, $sid, $markstore1, $markstore2, $markstore3, $assesstype_text, $min_feedback_words);
        }
        $POST_OP = "donothing";

        //echo("about to include MPDF<BR>");
        //echo("about to start the pdf producing. topdf =$topdf<BR>");
        if ($topdf > 0 || $force_pdf == 1) {
            // Discard any buffered HTML so mPDF can send PDF headers cleanly
            ob_end_clean();
            $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../tmp']);
            // Set a simple Footer including the date and page number
            $mpdf->setFooter("$student_name. Printed: {DATE j M Y}.   page {PAGENO} of {nb}");
            $stylesheet = file_get_contents('./css/pagedesc.css');
            $mpdf->WriteHTML($stylesheet, 1);    // The parameter 1 tells that this is css/style only and no body/html/text
            $mpdf->WriteHTML($display_block);

            $safe_filename = preg_replace('/[^A-Za-z0-9_\- ]/', '_', $student_name) . '.pdf';
            if ($topdf == 1 || $topdf == 2) {
                // Stream as a download — user opens with their own PDF viewer
                $mpdf->Output($safe_filename, 'D');
                exit();
            } else if ($topdf == 3) {
                // Email to student
                // Done in file projectmarking_civl_email_feedback.php
            }
        }
        $display_block .= "<a href=\"$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&module1=$module1&year_begin=$year_begin&assesstype=$assesstype&topdf=1&force_pdf=1&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag\">Save to PDF</A><BR />";

        //echo "Locked = $locked <BR>";
        //echo "POST_OP = $POST_OP<BR>";
        //echo "$display_block";


    } else {// end of if locked

        //echo("Showing table<BR>");
        // No need to have an else as the rest is always done.
        $forwhom = "the supervisor only: " . $supervisor;
        if ($who == 1) {
            $forwhom = "the supervisor : " . $supervisor;
        } else if ($who == 2) {
            $forwhom = "the second marker : " . $second_marker_name;
        } else if ($who == 3) {
            $forwhom = "both the subervisor: " . $supervisor . " and second marker : " . $second_marker_name;
        }
        //echo($forwhom ."<BR>");

        $instructions = "<h4>This form is for comparing marks from each marker.</h4>";
        $instructions .= "<div><h6>This form should be used to compare each markers' responses to the questions and to ensure that the final mark is within the agreed acceptable difference for the <em>$assesstype_text</em></h6></div>";

        $final_total_diff = sprintf("%5.1f", abs($mark1 - $mark2));
        $mark1_s = sprintf("%5.1f", $mark1);
        $mark2_s = sprintf("%5.1f", $mark2);
        $instructions .= "<p>1st Marker mark: $mark1_s%</p> <p>2nd Marker mark: $mark2_s%</p> <p>Difference: $final_total_diff%</p>";
        $instructions .= "<p>The acceptable difference is: $accept_var%</P>";
        if ($final_total_diff <= $accept_var) {
            $instructions .= "\n<div style=\"background-color:springgreen;\">\n&nbsp;You are <strong>within the acceptable range</strong> of marks. Go to the bottom of the page and submit.\n</div>";
        } else {
            $instructions .= "\n<div style=\"background-color:red;color:white;\">\n&nbsp;You are <strong>outside the acceptable range of marks</strong> you must address this before marks can be submitted.\n</div>";
        }
        //echo($instructions);

        //$display_block .= "<p><b>This form is used for assessing the:</B></p>";
        //$display_block .= "<h1>$assesstype_text section  of module $module1, for the session $year_begin/$year_end</h1>";
        //$display_block .= "<h1>Assessment of $module_short_description ($module_prefix$module1): $assesstype_text, by $student_name, for the session $year_begin/$year_end</h1>";

        $display_block .= "<div class='container'>";
        $display_block .= "<div class='container-fluid'>";
        $display_block .= "<h2>Assessment of $module_short_description ($module_prefix$module1):<BR> $assesstype_text, by $student_name, for the session $year_begin/$year_end</h2>";


        $display_block .= "<div class='card mb-3 text-bg-light' >";
        $display_block .= "<div class='card-body'>";

        $display_block .= "<div class='mb-3'>$instructions</div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Student:</strong></div><div class='col-sm-6'> $student_name</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Supervisor:</strong></div><div class='col-sm-6'> $supervisor</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>1st Marker:</strong></div><div class='col-sm-6'> $marker_name</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>2nd Marker:</strong></div><div class='col-sm-6'> $second_marker_name</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Programme:</strong></div><div class='col-sm-6'> $programme</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Title:</strong></div><div class='col-sm-6'> $title</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Project ID:</strong></div><div class='col-sm-6'> $project_id</div></div>";
        $display_block .= "<div class='row mt-2'><strong>This assessment contributes $mark_pc% to the modue mark</strong></div>";

        $display_block .= "</div>";// close of card-body
        $display_block .= "</div>";// close of card


        $display_block .= "<div style='z-index: 100;' id=\"sticker\">0.0</div>";
        $display_block .= "<div style='z-index: 100;' id=\"sticker_mid\">0.0</div>\n";
        //$display_block .= "<p>Questions table: $assessqs_year,  Question Categories: $assess_cats</p>";

        //echo($display_block);

        //$display_block .="<form method=\"post\" action=\"$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&module1=$module1&year_begin=$year_begin&assesstype=$assesstype\" \">\n";
        //This one has the validation
        $display_block .= "<form method=\"post\" onsubmit=\"return validate_form();\" action=\"$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$assesspart&who=$who&marker_id=$marker_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag\" \">\n";

        // Get number of categories by counting the rows in $assess_cats
        $query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $num_categories = $results->rowCount();
        //$display_block .="<input type=\"hidden\" name=\"numcats\" value=\"$num_categories\" />";

        // enter the max mark and percent of this part for use on the form
        $display_block .= "<span id=\"markmax_span\">
    \n<input type=\"hidden\" id=\"markmax\" name=\"markmax\" value=\"$mark_max\" size=\"3\">\n</span>";
        $display_block .= "\n<span id=\"markpc\">
    \n<input type=\"hidden\" id=\"markpc_id\" name=\"markpc\" value=\"$mark_pc\" size=\"3\">\n</span>";
        $display_block .= "\n<input type=\"hidden\" id=\"acceptable_variation\" value=\"$accept_var\">";

        // left column in a table
        $display_block .= "\n<div class='row'>";
        $display_block .= "\n<div class='col-6'><span class=\"markerName\">$marker_name</span><BR>";
        //echo($display_block);
        for ($whois = 1; $whois <= 2; $whois++) {
            //echo("Who is it: $whois <BR>");
            for ($cat = 1; $cat <= $num_categories; $cat++) {
                //echo("Cat: $cat <BR>");

                // Get the category titles
                $query = "select * from $assess_cats where cat=$cat";
                //$get_cat_details_list = mysql_query($get_cat_details) or die(" Unable to get database $assess_cats. <BR>Query: $get_cat_details");
                //$cat_data = mysql_fetch_array($get_cat_details_list);
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }
                $cat_data = $results->fetch();
                $description = $cat_data['description'];
                $weight_pc = $cat_data['weight_percent'];
                $factor = $cat_data['factor'];
                if(array_key_exists('get_feedback',$cat_data)==false){
                    $show_cat_feedback = 1;
                }else{
                    $show_cat_feedback = $cat_data['get_feedback'];
                }

                // Get the questions for this category
                $query = "select * from $assessqs_year where cat=$cat";
                //$get_list_qstot = mysql_query($get_qstot) or die(" Unable to create list from $assessqs_year table. <BR>Query: $get_qstot");
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }

                $num_questions = $results->rowCount();
                //echo("num question = $num_questions<BR>");
                //$display_block .= "num question = $num_questions<BR>";
                for ($q_count = 1; $q_count < $num_questions; $q_count++) {
                    $query = "select * from $assessqs_year where cat=$cat and qid=$q_count";
                    //$get_qs_res = mysql_query($get_qs);
                    //$qs_list = mysql_fetch_array($get_qs_res);
                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo "An Error occured accessing the database in function: $this_function <BR>\n";
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";

                        exit();
                    }
                    $qs_list = $results->fetch();
                    $questions[$q_count] = $qs_list['question'];
                    $num_entries[$q_count] = $qs_list['entries'];
                    $q_weight = $qs_list['weight'];
                    //echo("q weight $qweight<BR>");
                    if(!is_numeric($q_weight)){
                        $q_weight = 100;
                    }
                    //echo("q weight $q_weight<BR>");
                    $q_weights[$q_count] = $q_weight;

                    for ($r_count = 1; $r_count <= $num_entries[$q_count]; $r_count++) {
                        $r_text = "text" . $r_count;
                        $response_texts[$q_count][$r_count] = $qs_list[$r_text];
                        $value = $response_texts[$q_count][$r_count];
                        //echo("$q_count-$r_count $value<br>");
                        $r_mark = "mark" . $r_count;
                        $response_marks[$q_count][$r_count] = $qs_list[$r_mark];
                        //$display_block .= "[q_count][r_count] = [$q_count][$r_count]<BR>";
                    }
                }

                //print_r($response_marks);
                if ($whois == 1) {
                    $markstore = $markstore1;
                } else {
                    $markstore = $markstore2;
                }
                //echo("About to show questions<BR>");
                if ($is_numeric == 1) {
                    $display_block .= show_mark_questions_numeric2_bs($db, $cat, $description, $weight_pc, $factor, $show_cat_feedback, $num_questions, $questions, $num_entries, $q_weights, $response_texts, $response_marks, $markstore, $sid, $whois, $min_feedback_words);
                } else {
                    $display_block .= show_mark_questions2_bs($db, $cat, $description, $weight_pc, $factor, $show_cat_feedback, $num_questions, $questions, $num_entries, $response_texts, $response_marks, $markstore, $sid, $whois, $min_feedback_words);
                }
                //$display_block .= show_mark_questions($cat, $description, $weight_pc, $factor,$num_questions, $questions,$num_entries,$response_texts,$response_marks, $markstore, $sid, $min_feedback_words);

                $display_block .= "<BR>\n";
            }
            //$show_overall_feedback_flag = 1;
            if($show_overall_feedback_flag == 1) {
                $display_block .= show_overall_feedback2_bs($db, $assesstype_text, $markstore, $sid, $whois, $min_feedback_words);
            }

            // Right Display block
            $display_block .= "</div>\n"; // End of left / right col-6
            if ($whois == 1) {
                $display_block .= "<div class='col-6'>\n<span class=\"markerName\">$second_marker_name</span> <BR>";
            }
        } // $whois
        //=============================End of Second Marker stuff

        //$display_block .= "</div>\n<!-- end of cell -->\n";// End of right cell
        $display_block .= "</div>\n<!-- end of row container -->\n"; // End of container
        $display_block .= "</div>\n<!-- end of row container -->\n"; // End of container



        //echo($display_block);

        $display_block .= "\n<div>\n<span class=\"feedbackSection\">\nTotal $assesstype_text marks: ";
        $display_block .= " Supervisor: <span class=\"feedbackTotalMark\" id=totalmarks1> </span>\n";
        $display_block .= " Second Marker: <span class=\"feedbackTotalMark\" id=totalmarks2> </span>\n";
        $display_block .= " Difference: <span class=\"feedbackTotalMark\" id=totalmarksdiff> </span>\n";
        $display_block .= "\n</span>\n";
        $display_block .= "\n<BR>\n<h5>(This is $mark_pc% of the module mark)\n</h5></div>\n";

        $display_block .= "<div class='container-fluid'><div class='row mt-3'>";
        $display_block .= "<div class='col-sm-3'>";
        $display_block .= "\n<input class='btn btn-sm btn-outline-secondary' type=\"button\" id=\"validate\" name=\"btn_validate\" value=\"Check all responses completed\" >\n";
        $display_block .= "</div>";
        $display_block .= "<div class='col-sm-3'></div>";

        $display_block .= "<div class='col-sm-6'>";
        $display_block .= "<span id='show_pdf_id'>";
        $display_block .= "<A class='btn btn-sm btn-info float-end' target='_blank' href='$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&pm_id=$pm_id&module1=$module1&year_begin=$year_begin&part=$assesspart&topdf=1&force_pdf=1&who=$who&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag'>Show the PDF feedback sheet that the student will see</A>";
        $display_block .= "</span>";
        $display_block .= "</div>";
        $display_block .= "</div></div>";

        //$display_block .= "<BR>\n<input type=\"button\" id=\"validate\" name=\"btn_validate\" value=\"Check all selected\" >";
        $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"commit\">";
        $display_block .= "\n<input type=\"hidden\" name=\"nc\" value=\"$num_categories\">";


        //$display_block .= "\n<p><input type=\"submit\" id=\"disagreebutton\" name=\"disagree\" value=\"The markers Cannot Agree. Request a third marker's opinion.\" style=\"float: left;\"></p>\n";
        //$display_block .= "\n<p><span class=\"feedbackSectionMark\" id=\"submittext\">The difference is too great. (> $accept_var%). Correct this to display the Submit button.\n</span></p>\n";

        $display_block .= "<div class='m-3'>\n";
        $display_block .= "<input class='btn btn-danger' type='submit' id='disagreebutton' name='disagree' value='The markers Cannot Agree. Request a third marker's opinion.' >\n";
        $display_block .= "<span class='feedbackSectionMark' id='submittext'><B><i class='bi bi-arrow-left'></i> The difference is too great (> $accept_var%). Either: (a) correct this to display the Submit button, or (b) Press the button to request a third marker.</B></span>\n";
        $display_block .= "</div>\n";

        //$display_block .= "\n<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit the marks to the database\" ></p>\n</form>";


        $display_block .= "<div class='mb-5'>\n";
        $display_block .= "<input class='btn btn-lg btn-warning' type='submit' id='submitbutton' name='submit' value='Submit the marks and feedback to the database' >\n";
        $display_block .= "</div>\n";


        $display_block .= "</form>\n";

        $display_block .= "</div>";// Close class='container-fluid'
        $display_block .= "</div>";// Close class='container'

    }


}
// End of main


?>

<!DOCTYPE html>
<html>
<?php
(@include_once("$mobile_head_bs")) OR die("Cannot find this file to include: $mobile_head_bs<BR>");
?>

<script type="text/javascript">
    $(document).ready(function () {
        Sum2Sections();
        var s = $("#sticker");
        var pos = s.position();
        var s_mid = $("#sticker_mid");
        var pos_mid = s.position();
        $(window).scroll(function () {
            var windowpos = $(window).scrollTop();
            //s.html("Distance from top:" + pos.top + "<br />Scroll position: " + windowpos);
            if (windowpos >= pos.top) {
                s.addClass("stick");
                s_mid.addClass("stick");
            } else {
                s.removeClass("stick");
                s_mid.removeClass("stick");
            }
        });
        $("[id^=section] input[type='radio']").click(function () {
            var who = Number($(this).attr('name').substring(1, 2));
            //alert("who = " + who);
            //alert("this radio: " + $(this).attr('name'));
            var section = Number($(this).attr('name').substring(2, 3));
            //alert("Section: " + section);

            var mark_id = "idm" + ((this).id).substring(3);
            //alert("markid: " + mark_id);
            $("#sectionmark" + section).html($("#" + mark_id).val());

            var section_total = 0.0;
            $("#table" + who + section + " input[type='radio']:checked").each(function () {
                //alert($(this).val());
                section_total += Number($(this).val());
                //alert(section_total);
            });

            var factor = $("#factor_id" + who + section).val();
            //$("#sectionmarktext" + section).html( "(" + factor + " x " + section_total + " = )");
            $("#sectionmark" + who + section).html((factor * section_total).toFixed(1));
            // hide it
            //$("#sectionmark" + section).css("display: none;");

            Sum2Sections();
        });

        $("[id^=section]  input[type='number']").bind('keyup change click', function () {
            var who = Number($(this).attr('name').substring(1, 2));
            //alert("who = " + who);
            //alert("this radio: " + $(this).attr('name'));
            var section = Number($(this).attr('name').substring(2, 3));
            //alert("Section: " + section);

            var question_num = Number($(this).attr('name').substring(4));
            //alert("Question num: " + question_num);
            var q_weight_id = "qw" + String(who) + String(section) + "-" + String(question_num);
            var q_weight = $("#" + q_weight_id).val();
            q_weight *= 0.01;
            //alert("Q: " + question_num + " weight: " + q_weight);

            var mark_id = "idm" + ((this).id).substring(3);
            //alert("markid: " + mark_id);
            $("#sectionmark" + section).html($("#" + mark_id).val());

            var section_total = 0.0;
            $("#table" + who + section + " input[type='number']").each(function () {
                //alert($(this).val());
                //section_total += Number($(this).val());
                //alert(section_total);

                var question_val = $(this).val();
                question_num = Number($(this).attr('name').substring(4));
                q_weight_id = "qw" + String(who) + String(section) + "-" + String(question_num);
                q_weight = $("#" + q_weight_id).val();
                question_val = question_val * q_weight * 0.01;
                section_total += Number(question_val);
            });

            var factor = $("#factor_id" + who + section).val();
            //$("#sectionmarktext" + section).html( "(" + factor + " x " + section_total + " = )");
            $("#sectionmark" + who + section).html((factor * section_total).toFixed(1));
            // hide it
            //$("#sectionmark" + section).css("display: none;");

            Sum2Sections();
        });


        function Sum2Sections() {
            var markmax = Number($("#markmax").val());
            //var acceptable_variation = new Number(document.getElementById('acceptable_variation').value);
            var accept_var = Number($("#acceptable_variation").val());

            //alert(document.getElementById("markmax").value);
            //var s= document.getElementById('markmax');
            //var markmax2 = s.value;
            //alert("mark max = " + markmax);
            //markmax=100;
            var markpc = $("#markpc_id").val();
            var mark11 = Number($("#sectionmark11").text());
            var mark12 = Number($("#sectionmark12").text());
            var mark13 = Number($("#sectionmark13").text());
            var mark14 = Number($("#sectionmark14").text());
            var mark15 = Number($("#sectionmark15").text());
            var mark21 = Number($("#sectionmark21").text());
            var mark22 = Number($("#sectionmark22").text());
            var mark23 = Number($("#sectionmark23").text());
            var mark24 = Number($("#sectionmark24").text());
            var mark25 = Number($("#sectionmark25").text());

            //mark1=1;
            //mark2=1;
            //mark3=1;
            //mark4=1;
            var mark_sup = (mark11 + mark12 + mark13 + mark14 + mark15) * 100 / markmax;
            var mark_2nd = (mark21 + mark22 + mark23 + mark24 + mark25) * 100 / markmax;
            var mark_diff = Math.abs(mark_sup - mark_2nd);
            $("#totalmarks1").html(mark_sup.toFixed(1) + "%");
            $("#totalmarks2").html(mark_2nd.toFixed(1) + "%");
            $("#totalmarksdiff").html(mark_diff.toFixed(1) + "%");

            $("#sticker_mid").html(mark_sup.toFixed(1) + "%");
            $("#sticker").html(mark_2nd.toFixed(1) + "%");

            if (mark_diff <= accept_var) {
                $("#submitbutton").css("visibility", "visible");
                $("#submittext").css("visibility", "hidden");
                $("#disagreebutton").css("visibility", "hidden");
            } else {
                $("#submitbutton").css("visibility", "hidden");
                $("#submittext").css("visibility", "visible");
                $("#disagreebutton").css("visibility", "visible");
            }
        }

        $("#validate").click(function () {
            var num = CheckAllSelected();
            if (num > 0) {
                alert(num + " questions have not been given responses. Fix this.");
            } else {
                alert(" All questions answered and feedback entered.");
            }
            //validate_form();
        });


        function SubmitChangesToDatabase() {

            return true;
        }
    });

    function CheckAllSelected() {
        //alert("Checking for unclicked radio buttons");
        var groups = {};

        $(":radio").each(function () {
            groups[this.name] = true;
            //alert("Group:" + this.name);
        });
        //alert("Num Groups: " + Object.keys(groups).length);

        var num_not_selected = 0;

        for (group in groups) {
            var radioButttons = $(":radio[name=" + group + "]:checked");
            isChecked = !!radioButttons.length;
            if (!isChecked) {
                // change the color of the question. It happens to have the same name
                //alert(group);
                $("span[name=q" + group.substring(1) + "]").css("background-color", "pink");
                num_not_selected++;
            }
            else {
                $("span[name=q" + group.substring(1) + "]").css("background-color", "#F6F6F6");
            }
        }

        return num_not_selected;
    }
    function CheckAllFeedback() {
        var num_no_feedback = 0;
        $("textarea").each(function () {
            //alert("textarea name:" + this.name);
            //alert("text length:" + this.value.length);
            if (this.value.length == 0) {
                $(this).css("background-color", "pink");
                num_no_feedback++;
            } else {
                $(this).css("background-color", "#F6F6F6");
            }
        });
        return num_no_feedback;
    }
    function ConvertAllFeedbackToBase64() {
        $("textarea").each(function () {
            //alert("textarea name:" + this.name);
            this.value = btoa(this.value);
        });
    }
    function validate_form() {
        //alert("validating form");
        var num_not_selected = Number(CheckAllFeedback());
        num_not_selected += Number(CheckAllSelected());
        //alert("Num not seleectd = " + num_not_selected );
        //return false;
        if (num_not_selected == 0) {
            //alert("All done.");
            // need to do a base64 conversion on feedback text
            ConvertAllFeedbackToBase64();
            return true;
        } else {
            alert(num_not_selected + " questions or feedback still need to be completed.");
        }
        return false;

    }
    function validate_form2() {
        alert("validating form2");
    }
</script>

<BODY>
<div id="wrapper">
    <div id="header">
        <span
            class="header">Modify individual project details :- <?PHP echo("$module_short_description  $module_prefix$module1  $year_begin/$year_end") ?></span>
    </div>
    <!-- /header -->

    <div id="content">
        <!-- /display_block start -->
            <?php echo("$display_block"); ?>
        <!-- /display_block end -->
    </div>
    <!-- /content -->


    <!-- /footer -->
    <footer class="py-3 my-4 bg-dark text-white">
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="<?php echo("$project_marking_page?pm_id=$pm_id") ?>" class="nav-link px-2 text-muted">Full marking list</a></li>
            <li class="nav-item"><a href="<?php echo("$project_marking_page?pm_id=$pm_id&user=1") ?>" class="nav-link px-2 text-muted">User marking list</a></li>
            <li class="nav-item"><a href="./index.php" class="nav-link px-2 text-muted">Year index page</a></li>
        </ul>
    </footer>
</div>
<!-- /wrapper -->
</BODY>
</HTML>
