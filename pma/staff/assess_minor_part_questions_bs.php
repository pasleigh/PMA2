<?php
ob_start(); // Buffer all output — required so mPDF can set Content-Type headers even if something echoes earlier
// Turn off all error reporting
//error_reporting(0);
(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once($STAFF_DIR_VAL . "/config.php")) OR die("Cannot find this file to include: config.php. $DEFAULT_ERR");
(@include_once($STAFF_DIR_VAL . "/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once($STAFF_DIR_VAL . "/connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once($STAFF_DIR_VAL . "/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once($STAFF_DIR_VAL . "/staff_functions.php")) OR die("Cannot include staff_function.php<BR>");
(@include_once($STAFF_DIR_VAL . "/show_minor_part_feedback_form.php")) OR die("Cannot include show_minor_part_feedback<BR>");
(@include_once($STAFF_DIR_VAL . "/show_mark_questions_bs.php")) OR die("Cannot include show_mark_questions2<BR>");
(@include_once($STAFF_DIR_VAL . "/pdo_query_functions.php")) OR die("Cannot include pdo_query_functions<BR>");

echo __BASE_DIR__ . "<BR>";
require_once __BASE_DIR__ . '/vendor/autoload.php';

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


// if the form was submitted then enter it all into the database
//echo($_POST['op'] . "<BR>\n");
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}
//echo($POST_OP . "<BR>\n");


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

// $string parameter can be base64 encoded or not
function is_base64_encoded($string) {
    // This will check if $string is base64 encoded and return true, if it is.
    return base64_decode($string, true) !== false;
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
$markstore = $markstores[$assesspart][$who];
$mark_max = $p_max[$assesspart];
$mark_pc = $p_percent[$assesspart];

//echo("Assess_cats table = $assess_cats<BR>\n");
//echo("Assessqs_year table = $assessqs_year<BR>\n");
//echo("Assesstype_text table = $assesstype_text<BR>\n");
//echo("max mark for this part = $mark_max<BR>");
//echo("percent of final assesment = $mark_pc<BR>");
//echo("Who = $who<BR>\n");
//echo("markstore = $markstore<BR>\n");
//echo("Part = $assesspart<BR>\n");

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

$marker1 = "";
$marker2 = "";

if ($who == 1) {
    $marker1 = " (marker)";
} else if ($who == 2) {
    $marker2 = " (marker)";
} else {
    // there is no marker specified so it is by default the supervisor
    $marker1 = " (marker)";
}

// Get title and supervisor

//$get_list = "select id, f_title, f_supervisor from $master_name where id = $project_id";
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

$i = $assesspart;
$j = $who;
$marker_entry = "marker_p$i" . "m$j" . "_id";
$marker_id = $recs[$marker_entry];
$supervisor = GetStaffNameFromID($db, $supervisor_id, $staff2, NULL);
$marker_name = GetStaffNameFromID($db, $marker_id, $staff2, NULL);//. " ($marker_entry, $marker_id)";
$second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff2, NULL);


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
$query = "select * from $markstore where sid = $sid";
//$get_list_res = mysql_query($get_list) or die(" Unable to create list from $markstore table. Query: $get_list");
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
if($recs){
    $mark = $recs['total'];
    $locked = $recs['locked'];
    $marks_stored = $recs;
}else{
    $mark = 0;
    $locked = 0;
    $marks_stored = null;
}

if ($POST_OP == "commit") {
    $display_block .= "<h2>Submitting the changes to the database ...</H2>";

    // the insert to the database code goes here

    // Get the stuff data from the form
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

        //$id = "sec" . $cat;
        //$section_q[$cat] = addslashes($_POST[$id]);
        //$id = "weightpc_name" . $cat;
        //$section_weight[$cat] = $_POST[$id];
        //$id = "factor_name" . $cat;
        //$section_factor[$cat] = $_POST[$id];
        //echo("Section: $cat : $section_q[$cat]<BR>\n");
        //echo("  weight: $section_weight[$cat]<BR>\n");
        //echo("  factor: $section_factor[$cat]<BR>\n");

        // initialise the marks array
        for ($i = 1; $i <= 10; $i++) {
            $rmarks[$i] = 0;
        }

        $id = "numqs" . $cat;
        $numqs = $_POST[$id];
        $marktot = 0.0;
        $mark_query_string = "";
        $marks_array = array();
        for ($i = 1; $i < $numqs; $i++) {
            //$id = "q$cat-$i";
            //$question = addslashes($_POST[$id]);
            //echo("$id : $question<BR>");

            $q_weight = 1.0;
            $is_numeric_name = $_POST['is_numeric_name'];
            if($is_numeric_name = 1) {
                $id = "qw$cat-$i";
                $q_weight_pc = $_POST[$id];
                $q_weight = $q_weight_pc * 0.01;
            }

            $id = "r$cat-$i";
            $rmark = $_POST[$id];
            $rmark = $rmark * $q_weight;

            $rmarks[$i] = $rmark;
            //echo("$id : $rmark<BR>");

            $marktot = $marktot + $rmark;

            $mark_query_string .= "C$cat" . "Q$i=$rmark, ";

            $key = "C$cat" . "Q$i";
            $marks_array[$key] = $rmark;
        }
        //echo($mark_query_string . "<BR>");
        $id = "feedback" . $cat;
        if(array_key_exists($id,$_POST) and $show_cat_feedback == 1) {
            $feedback_text = $_POST[$id];
            $feedback_text = convert_smart_quotes($feedback_text);
            $feedbacks[$cat] = addslashes($feedback_text);
            $mark_query_string .= "C$cat" . "Feedback='$feedbacks[$cat]',";
        }else{
            $feedback_text = "";
        }

        $marktot = $marktot * $factor;
        $mark_total_all_cats += $marktot;

        $query = "SELECT COUNT(SID) FROM $markstore WHERE SID=$sid";
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
            $query = "INSERT $markstore SET SID='$sid', $mark_query_string
                       C$cat" . "total" . "=$marktot, total=$mark_total_all_cats
                       ";
            $CXtotal = "C$cat" . "total";
            $Ctotal = "total";
            $CXFeedback = "C$cat" . "Feedback";
            $query2 = "INSERT $markstore SET SID=:sid, C$cat" . "total=:cat_total, total=:all_cats_total, C$cat" . "Feedback=:cat_feedback";
            $query2 = "INSERT $markstore SET SID=:sid, $CXtotal=:cat_total, total=:all_cats_total, $CXFeedback=:cat_feedback";
            $data =array(
                $CXtotal => $marktot,
                $Ctotal => $mark_total_all_cats,
                $CXFeedback => $feedback_text,
                'sid' => $sid
            );
            // tag on the marks
            foreach ($marks_array as $key => $value) {
                $data[$key] = $value;
            }
            $all_data =array(
                'sid' => $sid,
                'cat_total' => $marktot,
                'all_cats_total' => $mark_total_all_cats,
                'cat_feedback' => $feedback_text
            );
            //print_r3('data array',$data);
            $results = perform_insert_prepared_query($db, $markstore, $data);
        } else {
            $query = "UPDATE $markstore SET $mark_query_string
                       C$cat" . "total" . "=$marktot, total=$mark_total_all_cats
                       WHERE SID='$sid'";
            $CXtotal = "C$cat" . "total";
            $Ctotal = "total";
            $CXFeedback = "C$cat" . "Feedback";
            $query2 = "UPDATE $markstore SET C$cat" . "total=:cat_total, total=:all_cats_total, C$cat" . "Feedback=:cat_feedback WHERE SID=:sid";
            $query2 = "UPDATE $markstore SET $CXtotal=:cat_total, total=:all_cats_total, $CXFeedback=:cat_feedback WHERE SID=:sid ";
            $data =array(
                $CXtotal => $marktot,
                $Ctotal => $mark_total_all_cats,
                $CXFeedback => $feedback_text
            );
            // tag on the marks
            foreach ($marks_array as $key => $value) {
                $data[$key] = $value;
            }
            $where_data =array(
                'sid' => $sid
            );
            $all_data =array(
                'sid' => $sid,
                'cat_total' => $marktot,
                'all_cats_total' => $mark_total_all_cats,
                'cat_feedback' => $feedback_text
            );
            $results = perform_update_prepared_query($db, $markstore, $data, $where_data);
        }

        // PDO prepare test
        //$results = perform_update_prepared_query($db, $markstore, $data, $where_data);

        //echo $query . "<BR>";
        //echo $query2 . "<BR>";
        //print_r2($results);
        //exit;
        //$prep = $db->prepare($query2);
        //$results = $prep->execute($all_data);

        //print_r2($prep);
        //print_r2($results);
        //exit();

        try {
            //$results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

        // If there is only one marker then this  mark should be written into the totals table
        //echo("Num markers = $p_num_markers[$assesspart]<BR>");
        if ($p_num_markers[$assesspart] == 1) {
            $query = "select * from $markstore_totals where SID=$sid";
            //echo $get_marks . "<BR>";
            //$get_list_marks = mysql_query($get_marks) or die(" Unable to create list from table $markstore_totals.<BR> Query = $get_marks");
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }

            //$get_marks_res = mysql_fetch_array($get_list_marks);
            $num_marks_in_list = $results->rowCount();
            //echo $num_marks_in_list . "<BR>";
            if ($num_marks_in_list == 0) {
                $query = "insert $markstore_totals set SID='$sid', retmark$assesspart=$mark_total_all_cats, mark_method$assesspart='0001'";
            } else {
                $query = "update $markstore_totals set retmark$assesspart=$mark_total_all_cats, mark_method$assesspart='0001' where SID='$sid' ";
            }
            //echo $action . "<BR>";
            //mysql_query($action) or die(" Unable to add mark data to the table $markstore_totals. <BR /> Query = $action");
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


    }

    $overall_feedback_id = "overall_feedback";

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

    $display_block = "<div class='container'>";
    $display_block .= "<div class='container-fluid'>";

    $display_block .= "<h1>Assessment of $module_short_description : $assesstype_text, by $student_name</h1>";
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
          <td><b>Mark: </b>$mark_total_all_cats%</td>
          </tr>
          <tr>
          <td><b>Supervisor: </b>&nbsp; $supervisor&nbsp;&nbsp;</td>
          </tr>
          <tr>
          <td><b>Marker: </b>&nbsp; $marker_name&nbsp;&nbsp;</td>
          </tr>
         </table><br />";
    $display_block .= "<span><BR>
            <a target=\"_self\" href=\"./$project_marking_page?pm_id=$pm_id#$username\" style=\"color: dimgray;\">Back to full marking list</a>
            or
            <a target=\"_self\" href=\"./$project_marking_page?pm_id=$pm_id&user=1\" style=\"color: dimgray;\">User marking list</a>
            </span>\n";
    //$display_block .="<h2>Done ... carry on making changes if you like</H2>";
    $display_block .= "</div></div></div>";// close class='container-fluid'
} else {// end of if ($POST_OP == "commit")
    //$locked=1;
    //echo("Locked = $locked<BR>");
    if ($locked == 1 || $force_pdf == 1) {
        //$display_block = "<h1>Project Report Mark Sheet (<span class=\"red\">Marked and Locked</span>)</h1>";
        $display_block = " ";
        //echo "$sid,$markstore1,$markstore2,$markstore3,$assess_cats,$assessqs,$student_name,$username,$sid,$title,$supervisor,$marker_name,$project_id<BR>";
        $display_block .= show_minor_part_feedback_form($db, $sid, $markstore, $assessqs_year, $student_name, $username, $title, $supervisor, $second_marker_name, $marker_name, $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $assesspart, $assesstype_text, $module_short_description, $is_numeric);
        if($show_overall_feedback_flag == 1) {
            $display_block .= show_minor_part_overall_feedback_form($db, $assesstype_text, $markstore, $sid, $min_feedback_words);
        }
        $POST_OP = "donothing";
        //echo("About to show html <BR>");
        //echo("$display_block");
        //echo("Finished show html <BR>");
        //echo("about to include MPDF<BR>");
        //echo("about to start the pdf producing. topdf =$topdf<BR>");
        //$display_block = "<p>This is a test</p>";
        if ($topdf > 0 || $force_pdf == 1) {
            ob_end_clean(); // Discard any buffered HTML so mPDF can send PDF headers cleanly
            $mpdf = new \Mpdf\Mpdf(['tempDir' => __DIR__ . '/../tmp']);
            $mpdf->use_kwt = true;    // DEFAULT=false. Try to keep tables from crossing pages
            // Set a simple Footer including the date and page number
            $mpdf->setFooter("$student_name. Printed: {DATE j M Y}.   page {PAGENO} of {nb}");
            $stylesheet = file_get_contents('./css/pagedesc.css');
            $mpdf->WriteHTML($stylesheet, 1);    // The parameter 1 tells that this is css/style only and no body/html/text
            $mpdf->WriteHTML($display_block);

            //echo("about to show pdf. topdf =$topdf force_pdf =$force_pdf<BR>");
            if ($topdf == 1 || $topdf == 2) {
                $safe_filename = preg_replace('/[^A-Za-z0-9_\- ]/', '_', $student_name) . '.pdf';
                $mpdf->Output($safe_filename, 'D');
                exit();
            } else if ($topdf == 3) {
                // Email to student
                // Done in file projectmarking_civl_email_feedback.php
                //exit;
            }
            error_reporting(-1);
        }
        $display_block .= "<a href=\"$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$assesspart&who=$who&marker_id=$marker_id&topdf=1&force_pdf=1&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag\">Save to PDF</A><BR />";

        //echo "Locked = $locked <BR>";
        //echo "POST_OP = $POST_OP<BR>";
        //echo "$display_block";


    } else {// end of if locked

        // No need to have an else as the rest is always done.
        $forwhom = "the supervisor only: " . $supervisor;
        if ($who == 1) {
            $forwhom = "the marker: " . $supervisor;
        } else if ($who == 2) {
            $forwhom = "the marker : " . $marker_name;
        }
        $instructions = "<h4>This form is for attention of  $marker_name</h4>";
        $instructions .= "<div><h6>You should answer the following questions by choosing what appropriately relates to the student's work for the <em>$assesstype_text</em>.</h6></div>";

        //$display_block .= "<p><b>This form is used for assessing the:</B></p>";
        $display_block .= "<div class='container'>";
        $display_block .= "<div class='container-fluid'>";
        $display_block .= "<h2>Assessment of $module_short_description ($module_prefix$module1):<BR> $assesstype_text, by $student_name, for the session $year_begin/$year_end</h2>";

        $marker1 = "";
        $marker2 = "";

        //if($who == 1){
        //  $marker1 = " (marker)" ;
        //}else if($who == 2){
        //  $marker2 = " (marker)" ;
        //}else{
        //  // there is no marker specified so it is by default the supervisor
        //  $marker1 = " (marker)" ;
        //}
        $display_block .= "<div class='card mb-3 text-bg-light' >";
        $display_block .= "<div class='card-body'>";

        $display_block .= "<div class='mb-3'>$instructions</div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Student:</strong></div><div class='col-sm-6'> $student_name</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Supervisor:</strong></div><div class='col-sm-6'> $supervisor</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Marker:</strong></div><div class='col-sm-6'> $marker_name</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Programme:</strong></div><div class='col-sm-6'> $programme</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Title:</strong></div><div class='col-sm-6'> $title</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Project ID:</strong></div><div class='col-sm-6'> $project_id</div></div>";
        $display_block .= "<div class='row mt-2'><strong>This assessment contributes $mark_pc% to the modue mark</strong></div>";

        $display_block .= "</div>";// close of card-body
        $display_block .= "</div>";// close of card

        $display_block .= "<div style='z-index: 100;' id='sticker'>0.0</div>";

        //$display_block .= "<p>Questions table: $assessqs_year,  Question Categories: $assess_cats</p>";

        //$display_block .="<form method=\"post\" action=\"$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&module1=$module1&year_begin=$year_begin&assesstype=$assesstype\" \">\n";
        //This one has the validation
















        $display_block .= "<form method=\"post\" onsubmit=\"return validate_form();\" action=\"$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&pm_id=$pm_id&part=$assesspart&who=$who&marker_id=$marker_id&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag\">\n";
        // Get number of categories by counting the rows in $assess_cats
        $query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
        //$myquery_res = mysql_query($myquery)or die(" Unable to get assess_cats data from $assess_cats table. <BR>Query: $myquery");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $myquery_res = $results->fetch();
        $num_categories = $results->rowCount();
        //$display_block .="<input type=\"hidden\" name=\"numcats\" value=\"$num_categories\" />";

        // enter the max mark and percent of this part for use on the form
        $display_block .= "<span id=\"markmax_span\">
    \n<input type=\"hidden\" id=\"markmax\" name=\"markmax\" value=\"$mark_max\" size=\"3\"></span>";
        $display_block .= "<span id=markpc>
    \n<input type=\"hidden\" id=\"markpc_id\" name=\"markpc\" value=\"$mark_pc\" size=\"3\"></span>";
        $display_block .= "\n<span id=min_feedback_words>
    <input type=\"hidden\" id=\"min_feedback_words_id\" name=\"min_feedback_words\" value=\"$min_feedback_words\" size=\"3\"></span>";
        $display_block .= "\n<input type=\"hidden\" id=\"is_numeric_id\" name=\"is_numeric_name\" value=\"$is_numeric\" size=\"3\">";

        for ($cat = 1; $cat <= $num_categories; $cat++) {

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
                //echo("q weight $qweight<BR>");
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
            //echo("is numeric = $is_numeric <BR>");
            if ($is_numeric == 1) {
                $display_block .= show_mark_questions_numeric_bs($db, $cat, $description, $weight_pc, $factor, $show_cat_feedback, $num_questions, $questions, $num_entries, $q_weights, $response_texts, $response_marks, $markstore, $sid, $min_feedback_words);
            } else {
                $display_block .= show_mark_questions_bs($db, $cat, $description, $weight_pc, $factor, $show_cat_feedback, $num_questions, $questions, $num_entries, $response_texts, $response_marks, $markstore, $sid, $min_feedback_words);
            }
            $display_block .= "<BR>\n";
        }



        //$show_overall_feedback_flag = 1;
        $overall_feedback_visibility = "none";
        if($show_overall_feedback_flag == 1) {
            $display_block .= show_overall_feedback_bs($db, $assesstype_text, $markstore, $sid, $min_feedback_words);
        }
        //$display_block .= "</div>\n";
        $display_block .= "<div class='row'>\n";
        $display_block .= "<span class=\"feedbackSection\">Total $assesstype_text marks = <span class=\"feedbackTotalMark\" id=totalmarks> </span>\n.";
        //$display_block .= "</span>\n";
        $display_block .= "\n<span class=\"feedbackQ\">(This is $mark_pc% of the module mark)</span>\n</div>\n";

        //$display_block .= "<P id='show_pdf_id'><a href=\"$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&pm_id=$pm_id&module1=$module1&year_begin=$year_begin&part=$assesspart&topdf=1&force_pdf=1&who=$who&isnumeric=$is_numeric\">Show the PDF feedback sheet that the student will see</A></P>\n";

        $display_block .= "<div class='container-fluid'><div class='row'>";

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
        //$display_block .= "<BR>\n<input class='btn btn-sm btn-outline-secondary' type=\"button\" id=\"validate\" name=\"btn_validate\" value=\"Check all selected\" >\n";
        $display_block .= "<input type=\"hidden\" name=\"op\" value=\"commit\">\n";
        $display_block .= "<input type=\"hidden\" name=\"nc\" value=\"$num_categories\">\n";
        //$display_block .= "<p>\n";
        if($assess_criteria_visible == 1){
            $ac_visibility = "block";
        }else{
            $ac_visibility = "none";
        }
        //$display_block .= "<div id='assess_criteria' style='border: thin solid black; padding-left: 5px; background-color: #fff5cc; display: $ac_visibility;' >\n";
        //$display_block .= "<H2>Before submitting this mark please consider whether it corresponds to the University's / School's <em>Assessment criteria</em> descriptor: </H2>\n";
        //$display_block .= "<span id='assess_criteria_text2'></span>";
        //$display_block .= "</div><BR>\n";
        //$display_block .= "</p>\n";

        $display_block .= "<div class='card mt-3 mb-3 border-success text-bg-light style='display: $ac_visibility;'>\n";
        $display_block .= "<div class='card-header'><h5>Assessment critera consideration</h5></div>\n";
        $display_block .= "<div class='card-body'>\n";
        $display_block .= "<H4>Before submitting this mark please consider whether it corresponds to the University's / School's <em>Assessment criteria</em> descriptor: </H4>\n";
        $display_block .= "<span id='assess_criteria_text'></span>";
        $display_block .= "</div>\n";
        $display_block .= "</div>\n";

        $display_block .= "<div class='mb-5'>\n";
        $display_block .= "<input class='btn btn-lg btn-warning' type='submit' id='submitbutton' name='submit' value='Submit the marks and feedback to the database' >\n";
        $display_block .= "<B><i class='bi bi-arrow-left'></i> Click this or else all your marks and feedback will be lost</B>\n";
        $display_block .= "</div>\n";
        $display_block .= "</form>\n";

        $display_block .= "</div>";// Close class='container-fluid'
        $display_block .= "</div>";// Close class='container'
    }
}
// End of main

?>
<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html>
<?php
(@include_once("$mobile_head_bs")) OR die("Cannot find this file to include: $mobile_head_bs<BR>");
?>

<script type="text/javascript">
    $(document).ready(function () {
        SumSections();
        if (getTotal() < 0.01){
            // hide the show pdf link
            document.getElementById('show_pdf_id').style.visibility = 'hidden';
        }
        var s = $("#sticker");
        var pos = s.position();
        $(window).scroll(function () {
            var windowpos = $(window).scrollTop();
            //s.html("Distance from top:" + pos.top + "<br />Scroll position: " + windowpos);
            if (windowpos >= pos.top) {
                s.addClass("stick");
            } else {
                s.removeClass("stick");
            }
        });
        $("[id^=section] input[type='radio']").click(function () {
            var section = Number($(this).attr('name').substring(1, 2));
            //alert("Section: " + section);

            var mark_id = "idm" + ((this).id).substring(3);
            //alert("markid: " + mark_id);
            $("#sectionmark" + section).html($("#" + mark_id).val());

            var section_total = 0.0;
            $("#table" + section + " input[type='radio']:checked").each(function () {
                //alert($(this).val());
                section_total += Number($(this).val());
                //alert(section_total);
            });

            var factor = $("#factor_id" + section).val();
            //$("#sectionmarktext" + section).html( "(" + factor + " x " + section_total + " = )");
            $("#sectionmark" + section).html((factor * section_total).toFixed(1));
            // hide it
            //$("#sectionmark" + section).css("display: none;");

            SumSections();
        });

        $("[id^=section]  input[type='number']").bind('keyup change click', function () {
            var section = Number($(this).attr('name').substring(1, 2));
            //alert("Number Section: " + section);

            var question_num = Number($(this).attr('name').substring(3));
            //alert("Question num: " + question_num);
            var q_weight_id = "qw" + String(section) + "-" + String(question_num);
            var q_weight = $("#" + q_weight_id).val();
            q_weight *= 0.01;
            //alert("Q: " + question_num + " weight: " + q_weight);

            var mark_id = "idm" + ((this).id).substring(2);
            //alert("markid: " + mark_id);
            $("#sectionmark" + section).html($("#" + mark_id).val());

            var section_total = 0.0;
            $("#table" + section + " input[type='number']").each(function () {
                var question_val = $(this).val();
                question_num = Number($(this).attr('name').substring(3));
                q_weight_id = "qw" + String(section) + "-" + String(question_num);
                q_weight = $("#" + q_weight_id).val();
                question_val = question_val * q_weight * 0.01;
                section_total += Number(question_val);

                //alert($(this).val());
                //section_total += Number($(this).val());
                //alert(section_total);
            });

            var factor = $("#factor_id" + section).val();
            //$("#sectionmarktext" + section).html( "(" + factor + " x " + section_total + " = )");
            $("#sectionmark" + section).html((factor * section_total).toFixed(1));
            // hide it
            //$("#sectionmark" + section).css("display: none;");

            SumSections();
        });


        function SumSections() {
            var markmax = Number($("#markmax").val());
            //alert(document.getElementById("markmax").value);
            //var s= document.getElementById('markmax');
            //var markmax2 = s.value;
            //alert("mark max = " + markmax);
            //markmax=100;
            var markpc = $("#markpc_id").val();
            var mark1 = Number($("#sectionmark1").text());
            var mark2 = Number($("#sectionmark2").text());
            var mark3 = Number($("#sectionmark3").text());
            var mark4 = Number($("#sectionmark4").text());
            var mark5 = Number($("#sectionmark5").text());
            //mark1=1;
            //mark2=1;
            //mark3=1;
            //mark4=1;
            $("#totalmarks").html(((mark1 + mark2 + mark3 + mark4 + mark5) * 100 / markmax).toFixed(1) + "%");
            $("#sticker").html(((mark1 + mark2 + mark3 + mark4 + mark5) * 100 / markmax).toFixed(1) + "%");

            var total_int = Math.round((mark1 + mark2 + mark3 + mark4 + mark5) * 100 / markmax);
            var assessment_criteria_text = getAssessmentCriteriaText(total_int);
            $("#assess_criteria_text").html(assessment_criteria_text);
        }
        function getAssessmentCriteriaText(intMark){
            if(isNaN(intMark)){
                return  '';
            }
            var assessment_criteria_text_array = <?php echo json_encode($assessment_criteria_text); ?>;
            var assessment_criteria_flags_array = <?php echo json_encode($assess_criteria_flags); ?>;

            //console.log(JSON.stringify(assessment_criteria_text_array));
            var assessment_criteria_text_array_obj = assessment_criteria_text_array[intMark];
            //console.log(JSON.stringify(assessment_criteria_text_array_obj));

            var class_text = assessment_criteria_text_array_obj[0];
            var grade_text = assessment_criteria_text_array_obj[1];
            var gpa_text = assessment_criteria_text_array_obj[2];
            var university_text = assessment_criteria_text_array_obj[3];
            var school_text = assessment_criteria_text_array_obj[4];

            my_text = '';
            if(assessment_criteria_flags_array[3] == 1) {
                my_text += '<H3>University descriptor: </H3><P>' + university_text + '</P>';
            }
            if(assessment_criteria_flags_array[4] == 1) {
                my_text += '<H3>School descriptor: </H3><P>' + school_text + '</P>';
            }

            if((assessment_criteria_flags_array[2] == 1) || (assessment_criteria_flags_array[0] == 1) || (assessment_criteria_flags_array[1] == 1)) {
                my_text += '<H3>Other grade descriptions corresponding to this mark: </H3>';
                if (assessment_criteria_flags_array[2] == 1) {
                    my_text += '<B>GPA value: </B>' + gpa_text;
                }
                if (assessment_criteria_flags_array[0] == 1) {
                    my_text += '<BR><B>Class description: </B>' + class_text;
                }
                if (assessment_criteria_flags_array[1] == 1) {
                    my_text += '<BR><B>Grade letter: </B>' + grade_text;
                }
                my_text += '<BR>';
            }
            return my_text;
        }

        function getTotal() {
            var markmax = Number($("#markmax").val());
            //alert(document.getElementById("markmax").value);
            //var s= document.getElementById('markmax');
            //var markmax2 = s.value;
            //alert("mark max = " + markmax);
            //markmax=100;
            var markpc = $("#markpc_id").val();
            var mark1 = Number($("#sectionmark1").text());
            var mark2 = Number($("#sectionmark2").text());
            var mark3 = Number($("#sectionmark3").text());
            var mark4 = Number($("#sectionmark4").text());
            var mark5 = Number($("#sectionmark5").text());
            //mark1=1;
            //mark2=1;
            //mark3=1;
            //mark4=1;
            var myTotal = (mark1 + mark2 + mark3 + mark4 + mark5) * 100 / markmax;
            return myTotal;
        }

        $("#validate").click(function () {
            var num = CheckAllSelected();
            if (num > 0) {
                alert(num + " questions have not been given responses. Fix this.");
            } else {
                alert(" All questions answered and feedback entered.");
            }
            //validate_form();
            var num_not_enough_feedback = Number(CountAllFeedback());
        });


        function SubmitChangesToDatabase() {

            return true;
        }
    });

    function countwords(txtarea) {
        var Num = txtarea.name.substring(8, txtarea.name.length);
        //alert("counting " + Num);
        var val = $.trim($(txtarea).val());
        words = val.replace(/\s+/gi, ' ').split(' ').length;
        chars = val.length;
        if (!chars)words = 0;
        $('#feedbackcounter' + Num).html(words + ' words and ' + chars + ' characters');
    }

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
    function CountAllFeedback() {
        var MinWordCount = 10;
        MinWordCount = $("#min_feedback_words_id").val();
        //alert(MinWordCount);
        var not_enough_feedback_words = 0;
        $("textarea").each(function count() {
            var val = $.trim($(this).val()),
                words = val.replace(/\s+/gi, ' ').split(' ').length,
                chars = val.length;
            if (!chars)words = 0;
            //$('#counter').html('<br>'+words+' words and '+ chars +' characters');
            if (words < MinWordCount) {
                var Num = this.name.substring(8, this.name.length);
                alert("Your number of word in your feedback for section " + Num + " is only " + words + " words.\nThe minimum required is " + MinWordCount + " words.");
                not_enough_feedback_words++;
                $(this).css("background-color", "pink");
            }
        });
        return not_enough_feedback_words;
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
        var num_not_enough_feedback = Number(CountAllFeedback());
        //alert("Num not seleectd = " + num_not_selected );
        //return false;
        if (num_not_selected == 0 && num_not_enough_feedback == 0) {
            //alert("All done.");
            // need to do a base64 conversion on feedback text
            ConvertAllFeedbackToBase64();
            return true;
        } else {
            //alert(num_not_selected + " questions and " +  num_not_enough_feedback + " feedback still need to be completed.");
            alert(num_not_selected + " question(s) need answering.\n" + num_not_enough_feedback + " feedback section(s) do(es) not have enough words entered.");
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
            class="header">Modify individual project details :- <?PHP echo("$module_short_description  $module_prefix$module1  $year_begin/$year_end") ?>
        </span>
    </div><!-- /header -->

    <div id="content">
        <div>
            <?php echo("$display_block"); ?>
        </div>
    </div><!-- /content -->



    <footer class="py-3 my-4 bg-dark text-white">
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="<?php echo("$project_marking_page?pm_id=$pm_id") ?>" class="nav-link px-2 text-muted">Full marking list</a></li>
            <li class="nav-item"><a target="_self" href="<?php echo("$project_marking_page?pm_id=$pm_id&user=1") ?>" class="nav-link px-2 text-muted">User marking list</a></li>
            <li class="nav-item"><a href="./index.php" class="nav-link px-2 text-muted">Year index page</a></li>
        </ul>
    </footer>

</div><!-- /wrapper -->
</BODY>
</HTML>
