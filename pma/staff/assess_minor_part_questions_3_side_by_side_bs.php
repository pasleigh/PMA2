<?php
ob_start(); // Buffer all output — required so mPDF can set Content-Type headers even if something echoes earlier
// Turn off all error reporting
(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./config.php")) OR die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once("./setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./connect_pdo.php")) OR die("Cannot connect to the database<BR>");

//if (array_key_exists('pm_id', $_REQUEST)==false){ $_REQUEST['pm_id']=null; }
//$pm_id=$_REQUEST['pm_id'];

(@include_once("./setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./staff_functions.php")) OR die("Cannot include staff_function.php<BR>");
(@include_once("./show_feedback_form.php")) OR die("Cannot include show_feedback<BR>");
(@include_once("./show_minor_part_feedback_form.php")) OR die("Cannot include show_minor_part_feedback<BR>");
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

// if the form was submitted then enter it all into the database
if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op'];
} else {
    $POST_OP = "";
}
if (isset($_POST['disagree'])) {
    $POST_OP = "disagree";
}
if (array_key_exists('agreed', $_POST)) {
    $agreed_combination = $_POST['agreed'];
    $overwritten_mark = $_POST['overwritten'];
    $reason_text = $_POST['reasontext'];
    $reason_text = htmlentities($reason_text);
    $reason_text = addslashes($reason_text);
} else {
    $agreed_combination = "9999";
    $overwritten_mark = -1;
    $reason_text = "";

    $agreed_combination = GetMarkMethodString($db, $sid, $assesspart, $markstore_totals);
    $marker_contrib = GetMarkMethodAsArray($agreed_combination);
    if($marker_contrib[4] == 1){
        // Get the mark
        $overwritten_mark =GetReturnedMark($db, $sid, $assesspart, $markstore_totals);
    }
    $reason_text = GetMarkReasonString($db, $sid, $assesspart, $markstore_totals);
}
// Get how the mark split has been done (if it  has)
$marker_contrib = GetMarkMethodAsArray($agreed_combination);

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

// Get number of categories by counting the rows in $assess_cats
$query = "SELECT cat FROM $assess_cats WHERE cat IS NOT NULL";
//$myquery_res = mysql_query($myquery) or die(" Unable to get assess_cats data from $assess_cats table. <BR>Query: $myquery");
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
$marker_names[$j] = $marker_name;

$j = 2;
$marker_entry = "marker_p$i" . "m$j" . "_id";
$second_marker_id = $recs[$marker_entry];
$second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff2, NULL);
$marker_names[$j] = $second_marker_name;

$j = 3;
$marker_entry = "marker_p$i" . "m$j" . "_id";
$third_marker_id = $recs[$marker_entry];
$third_marker_name = GetStaffNameFromID($db, $third_marker_id, $staff2, NULL);//. " ($marker_entry, $marker_id)";
$marker_names[$j] = $third_marker_name;

//
// Get the student name from SID
$query = "select * from $students where sid = $sid";
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

// Get the third marker marks
$query = "select * from $markstore3 where sid = $sid";
//$get_list_res = mysql_query($get_list) or die(" Unable to create list from $markstore3 table. Query: $get_list");
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
    $mark3 = $recs['total'];
    $locked = $recs['locked'];
}else{
    $mark3 = 0;
    $locked = 0;
}

if ($POST_OP == "commit") {
    $display_block .= "<h2>Submitting the changes to the database ...</H2>";

    // Get the data from the form
    for ($who = 1; $who <= 3; $who++) {
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
            //$id = "sec" . $who . $cat;
            //$section_q[$cat] = addslashes($_POST[$id]);
            //$id = "weightpc_name" . $who . $cat;
            //$section_weight[$cat] = $_POST[$id];
            //$id = "factor_name" . $who . $cat;
            //$section_factor[$cat] = $_POST[$id];

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
                $mark_query_string .= "C$cat" . "Feedback='$feedbacks[$cat]',";
            }else{
                $feedback_text = "";
            }

            $marktot = $marktot * $factor;
            $mark_total_all_cats += $marktot;

            if ($who == 1) {
                $markstore = $markstore1;
            } elseif ($who == 2) {
                $markstore = $markstore2;
            } else {
                $markstore = $markstore3;
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
        $mark_total[$who] = $mark_total_all_cats;
        $tot = $mark_total[$who];

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

    $marker_contrib = GetMarkMethodAsArray($agreed_combination);
    $mark_agreed_total = 0.0;
    if ($marker_contrib[4] == 0) {
        $count = 0;
        for ($who = 1; $who <= 3; $who++) {
            $mark_agreed_total += $mark_total[$who] * $marker_contrib[$who];
            if ($marker_contrib[$who] > 0) {
                $count += 1;
            }
        }
        $mark_agreed_total /= $count;
    } else {
        // Overwritten marks so get this from the text / number box
        //
        $mark_agreed_total = $overwritten_mark;
    }
    $mark_agreed_total_s = sprintf("%5.2f", (float)$mark_agreed_total);

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

    $locked_stat = $res['locked'];

    if ($results->rowCount() > 0) {
        // exists, so update
        $query = "update $markstore_totals set retmark$assesspart=$mark_agreed_total,  locked=0, mark_method$assesspart='$agreed_combination', mark_reason$assesspart='$reason_text' where SID='$sid'";
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
        $query = "insert $markstore_totals set SID='$sid', retmark$assesspart=$mark_agreed_total,  locked=0, mark_method$assesspart='$agreed_combination', mark_reason$assesspart='$reason_text' ";
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

    for ($who = 1; $who <= 3; $who++) {
        $mark_total_s[$who] = sprintf("%5.1f", $mark_total[$who]);
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
          <td><b>Report Mark: </b>$mark_agreed_total_s%</td>
          </tr>
          <tr>
          <td><b>Supervisor: </b>&nbsp; $supervisor&nbsp;&nbsp;</td>
          </tr>
          <tr>
          <td><b>1st Marker: </b>&nbsp; $marker_names[1]&nbsp;$mark_total_s[1] ($marker_contrib[1])&nbsp;</td>
          </tr>
          <tr>
          <td><b>2nd Marker: </b>&nbsp; $marker_names[2]&nbsp;$mark_total_s[2] ($marker_contrib[2])&nbsp;</td>
          </tr>
          <tr>
          <td><b>3rd Marker: </b>&nbsp; $marker_names[3]&nbsp;$mark_total_s[3] ($marker_contrib[3])&nbsp;</td>
          </tr>";
    //$current_mark =  GetTotalForPart($sid,$assesspart,$markstore_totals);
    //if ( $current_mark > 0 ) {
    //    $display_block .= "\n <tr>
    //      <td><b>Current agreed mark: </b>&nbsp; $current_mark &nbsp; ($agreed_combination)</td>
    //      </tr>";
    //}
    $display_block .= "\n</table><br />\n";
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
    if ($locked == 1 || $force_pdf == 1) {
        //$display_block = "<h1>Project Report Mark Sheet (<span class=\"red\">Marked and Locked</span>)</h1>";
        $display_block = " ";
        //$display_block .= show_minor_part_feedback_form($sid,$markstore,$assessqs_year,$student_name,$username,$title,$supervisor,$second_marker_name,$project_id,$level,$assesstype_text,$assess_cats,$mark_max,$mark_pc);
        //$display_block .= show_feedback_form($sid,$markstore1,$markstore2,$markstore3,$markstore_totals,$assessqs_year,$student_name,$username,$title,$supervisor,$second_marker_name,$marker_name,$project_id,$assesstype_text,$assess_cats,$mark_max,$mark_pc,$programme,$assesspart,$assesstype_text, $module_short_description, $is_numeric);
        $current_mark = GetTotalForPart($db, $sid, $assesspart, $markstore_totals);
        if ($current_mark > 0) {
            $agreed_combination = GetMarkMethodString($db, $sid, $assesspart, $markstore_totals);
        }
        $markstore_list = GetMarkStoreListFromMarkMethodString($agreed_combination, $markstore1, $markstore2, $markstore3);
        $marker_list = GetMarkerListFromMarkMethodString($agreed_combination, $supervisor, $second_marker_name, $third_marker_name);
        $num_markstores = count($markstore_list) - 1;
        if ($num_markstores == 1) {
            $display_block .= show_feedback_form1($db, $sid, $markstore_list[1], $assessqs_year, $student_name, $username, $title, $supervisor, $marker_list[1], $marker_list[1], $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $assesspart, $assesstype_text, $module_short_description, $is_numeric);
        } elseif ($num_markstores == 2) {
            $display_block .= show_feedback_form2($db, $sid, $markstore_list[1], $markstore_list[2], $markstore3, $markstore_totals, $assessqs_year, $student_name, $username, $title, $marker_list[1], $marker_list[2], $marker_name, $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $assesspart, $assesstype_text, $module_short_description, $is_numeric);
        } else {
            $display_block .= show_feedback_form3($db, $sid, $markstore_list[1], $markstore_list[2], $markstore_list[3], $markstore_totals, $assessqs_year, $student_name, $username, $title, $supervisor, $second_marker_name, $third_marker_name, $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $assesspart, $assesstype_text, $module_short_description, $is_numeric);
        }
        if($show_overall_feedback_flag == 1) {
            if ($num_markstores == 2) {
                $display_block .= show_overall_feedback_form2($db, $sid, $markstore_list[1], $markstore_list[2], $markstore3, $assesstype_text, $min_feedback_words);
            }else{
                $display_block .= show_overall_feedback_form3($db, $sid, $markstore_list[1], $markstore_list[2], $markstore_list[3], $assesstype_text, $min_feedback_words);
            }}
        $POST_OP = "donothing";

        if ($topdf > 0 || $force_pdf == 1) {
            ob_end_clean(); // Discard any buffered HTML so mPDF can send PDF headers cleanly
            $mpdf = new \Mpdf\Mpdf(['tempDir' => PMA_TMP_DIR]);
            // Set a simple Footer including the date and page number
            $mpdf->setFooter("$student_name. Printed: {DATE j M Y}.   page {PAGENO} of {nb}");
            $stylesheet = file_get_contents('./css/pagedesc.css');
            $mpdf->WriteHTML($stylesheet, 1);    // The parameter 1 tells that this is css/style only and no body/html/text
            $mpdf->WriteHTML($display_block);

            $safe_filename = preg_replace('/[^A-Za-z0-9_\- ]/', '_', $student_name) . '.pdf';
            if ($topdf == 1 || $topdf == 2) {
                $mpdf->Output($safe_filename, 'D');
                exit();
            } else if ($topdf == 3) {
                // Email to student
                // Done in file projectmarking_civl_email_feedback.php
            }
        }
        $display_block .= "<a href=\"$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&module1=$module1&year_begin=$year_begin&assesstype=$assesstype&topdf=1&force_pdf=1&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag\">Save to PDF</A><BR />";

    } else {// end of if locked

        // No need to have an else as the rest is always done.
        $forwhom = "the supervisor only: " . $supervisor;
        if ($who == 1) {
            $forwhom = "the supervisor : " . $supervisor;
        } else if ($who == 2) {
            $forwhom = "the second marker : " . $second_marker_name;
        } else if ($who == 3) {
            $forwhom = "both the subervisor: " . $supervisor . " and second marker : " . $second_marker_name;
        }

        $instructions = "<h4>This form is for comparing marks from THREE markers - only when the orginal two could not agree.</h4>";
        $instructions .= "\n<h6>\nThis form should be used to compare the three markers' responses to the questions and to ensure that the final mark is acceptable for the <em>$assesstype_text</em>.</h6> \n";
        $final_total_diff = sprintf("%5.1f", max(abs($mark1 - $mark2), abs($mark1 - $mark3), abs($mark2 - $mark3)));
        $mark1_s = sprintf("%5.1f", $mark1);
        $mark2_s = sprintf("%5.1f", $mark2);
        $mark3_s = sprintf("%5.1f", $mark3);
        //$instructions .= "<BR>1st Marker mark: $mark1_s% ";
        //$instructions .= "<BR>2nd Marker mark: $mark2_s% ";
        //$instructions .= "<BR>3rd Marker mark: $mark3_s% ";
        //$instructions .= "<BR>Maximum difference: $final_total_diff%<BR>";
        //$instructions .= "The desired acceptable difference is: $accept_var%";

        $instructions .= "<div class='row'><div class='col-sm-2'><strong>1st Marker mark:</strong></div><div class='col-sm-6'> $mark1_s%</div></div>";
        $instructions .= "<div class='row'><div class='col-sm-2'><strong>2nd Marker mark:</strong></div><div class='col-sm-6'> $mark2_s%</div></div>";
        $instructions .= "<div class='row'><div class='col-sm-2'><strong>3rd Marker mark:</strong></div><div class='col-sm-6'> $mark3_s%</div></div>";
        $instructions .= "<div class='row mt-2'><div class='col-sm-2'><strong>Maximum difference: </strong></div><div class='col-sm-6'> $final_total_diff%</div></div>";
        $instructions .= "<div class='row mt-2'><div class='col-sm-2'><strong>The desired acceptable difference is:</strong></div><div class='col-sm-6'> $accept_var%</div></div>";

        //if($final_total_diff <= $accept_var){
        //  $instructions .= "\n<div style=\"background-color:springgreen;\">\n&nbsp;You are <strong>within the acceptable range</strong> of marks. Go to the bottom of the page and submit.\n</div>";
        //}else{
        //  $instructions .= "\n<div style=\"background-color:red;color:white;\">\n&nbsp;You are <strong>outside the acceptable range of marks</strong> you must address this before marks can be submitted.\n</div>";
        //}

        $display_block .= "<div class='container'>";
        $display_block .= "<div class='container-fluid'>";
        //$display_block .= "<p><b>This form is used for assessing the:</B></p>";
        //$display_block .= "<h1>$assesstype_text section  of module $module1, for the session $year_begin/$year_end</h1>";
        $display_block .= "<h2>Assessment of $module_short_description ($module_prefix$module1): $assesstype_text, by $student_name, for the session $year_begin/$year_end</h2>";

        $display_block .= "<div class='card mb-3 text-bg-light' >";
        $display_block .= "<div class='card-body'>";

        $current_mark = GetTotalForPart($db, $sid, $assesspart, $markstore_totals);
        if ($current_mark > 0) {
            $agreed_combination = GetMarkMethodString($db, $sid, $assesspart, $markstore_totals);
            $marker_contrib = GetMarkMethodAsArray($agreed_combination);
            $agreed_combination_reason = GetMarkReasonString($db, $sid, $assesspart, $markstore_totals);
        }

        $display_block .= "<div class='mb-3'>$instructions</div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Student:</strong></div><div class='col-sm-6'> $student_name</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Supervisor:</strong></div><div class='col-sm-6'> $supervisor</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>1st Marker:</strong></div><div class='col-sm-6'> $marker_names[1]</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>2nd Marker:</strong></div><div class='col-sm-6'> $marker_names[2]</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>3rd Marker:</strong></div><div class='col-sm-6'> $marker_names[3]</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Programme:</strong></div><div class='col-sm-6'> $programme</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Title:</strong></div><div class='col-sm-6'> $title</div></div>";
        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Project ID:</strong></div><div class='col-sm-6'> $project_id</div></div>";
        $display_block .= "<div class='row mt-2'><strong>This assessment contributes $mark_pc% to the modue mark</strong></div>";

        $display_block .= "<div class='row'><div class='col-sm-2'><strong>Current agreed mark:</strong></div><div class='col-sm-6'>$current_mark &nbsp;($agreed_combination)</div></div>";

        $display_block .= "</div>";// close of card-body
        $display_block .= "</div>";// close of card

        $display_block .= "<div style='z-index: 100;' id=\"sticker_1\">0.0</div>";
        $display_block .= "<div style='z-index: 100;' id=\"sticker_2\">0.0</div>";
        $display_block .= "<div style='z-index: 100;' id=\"sticker_3\">0.0</div>\n";

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

        // enter the max mark and percent of this part for use on the form
        $display_block .= "<span id=\"markmax_span\">
    \n<input type=\"hidden\" id=\"markmax\" name=\"markmax\" value=\"$mark_max\" size=\"3\">\n</span>";
        $display_block .= "\n<span id=\"markpc\">
    \n<input type=\"hidden\" id=\"markpc_id\" name=\"markpc\" value=\"$mark_pc\" size=\"3\">\n</span>";
        $display_block .= "\n<input type=\"hidden\" id=\"acceptable_variation\" value=\"$accept_var\">";

        // left column in a table
        $display_block .= "\n<div class='row'>";
        $display_block .= "\n<div class='col-4'><span class=\"markerName\">$marker_name</span><BR>";
        for ($whois = 1; $whois <= 3; $whois++) {
            for ($cat = 1; $cat <= $num_categories; $cat++) {
                // Get the category titles
                $query = "select * from $assess_cats where cat=$cat";
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
                for ($q_count = 1; $q_count < $num_questions; $q_count++) {
                    $query = "select * from $assessqs_year where cat=$cat and qid=$q_count";
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
                    if(!is_numeric($q_weight)){
                        $q_weight = 100;
                    }
                    $q_weights[$q_count] = $q_weight;

                    for ($r_count = 1; $r_count <= $num_entries[$q_count]; $r_count++) {
                        $r_text = "text" . $r_count;
                        $response_texts[$q_count][$r_count] = $qs_list[$r_text];
                        $value = $response_texts[$q_count][$r_count];
                        $r_mark = "mark" . $r_count;
                        $response_marks[$q_count][$r_count] = $qs_list[$r_mark];
                        //$display_block .= "[q_count][r_count] = [$q_count][$r_count]<BR>";
                    }
                }

                $markstore = $markstores[$assesspart][$whois];

                if ($is_numeric == 1) {
                    $display_block .= show_mark_questions_numeric2_bs($db, $cat, $description, $weight_pc, $factor, $show_cat_feedback, $num_questions,  $questions, $num_entries, $q_weights,$response_texts, $response_marks, $markstore, $sid, $whois, $min_feedback_words);
                } else {
                    $display_block .= show_mark_questions2_bs($db, $cat, $description, $weight_pc, $factor, $show_cat_feedback, $num_questions, $questions, $num_entries, $response_texts, $response_marks, $markstore, $sid, $whois, $min_feedback_words);
                }

                $display_block .= "<BR>\n";
            }
            //$show_overall_feedback_flag = 1;
            if($show_overall_feedback_flag == 1) {
                $display_block .= show_overall_feedback2_bs($db, $assesstype_text, $markstore, $sid, $whois, $min_feedback_words);
            }

            // Right Display block
           // $display_block .= "</div>\n"; // End of left / right cell
            //if($whois < 3){
            //    $marker_num = $whois+1;
            //    $display_block .= "<div class=\"cell\" id=\"cell3\">\n<span class=\"markerName\">$marker_num $second_marker_name</span> <BR>";
            //}
            // Right Display block
            $display_block .= "</div>\n"; // End of left / right col-6
            if ($whois < 3) {
                $next_marker_name = $marker_names[$whois+1];
                $display_block .= "<div class='col-4'>\n<span class=\"markerName\">$next_marker_name</span> <BR>";
            }
        } // $whois
        //=============================End of Second Marker stuff

        $display_block .= "</div>\n<!-- end of container -->\n"; // End of container

/*
        $display_block .= "<div class='row feedbackSection'>";
        $display_block .= "<div class='col-2'>$assesstype_text marks:</div>";
        $display_block .= "<div class='col-2'>First Marker: <span class='feedbackTotalMark' id='totalmarks1'> </span></div>";
        $display_block .= "<div class='col-2'>Second Marker: <span class='feedbackTotalMark' id='totalmarks2'> </span></div>";
        $display_block .= "<div class='col-2'>Second Marker: <span class='feedbackTotalMark' id='totalmarks3'> </span></div>";
        $display_block .= "<div class='col-4 feedbackSection'>Maximum difference: <span class='feedbackTotalMark' id='totalmarksdiff'> </span></div>";
        $display_block .= "</div>"; // close row
        $display_block .= "\n<span class=\"combinationAgreed\">\n(This is $mark_pc% of the module mark)\n</span>\n</div><BR>\n";
*/
        $is_checked = "checked";
        $is_checked = "";

        $display_block .= "<div class='card mb-3 border-danger text-bg-light'>\n";
        $display_block .= "<div class='card-header'><h5>Options for three markers of the Report :</h5></div>\n";
        $display_block .= "<div class='card-body'>\n";

        $display_block .= "<div class='row feedbackSection'>";
        $display_block .= "<div class='col-2'>First Marker: <span class='feedbackTotalMark' id='totalmarks1'> </span></div>";
        $display_block .= "<div class='col-2'>Second Marker: <span class='feedbackTotalMark' id='totalmarks2'> </span></div>";
        $display_block .= "<div class='col-2'>Second Marker: <span class='feedbackTotalMark' id='totalmarks3'> </span></div>";
        $display_block .= "<div class='col-4' style='color:#0c4128'>Maximum difference: <span class='feedbackTotalMark' id='totalmarksdiff'> </span></div>";
        $display_block .= "</div>"; // close row
        $display_block .= "<p><span class='combinationAgreed'>\n(This is $mark_pc% of the module mark)\n</span>\n</p>\n";

        $display_block .= "<div class='row combinationHeader'>";
        $display_block .= "<div class='col-3'>Markers combination</div>";
        $display_block .= "<div class='col-2'>Difference</div>";
        $display_block .= "<div class='col-2'>Mean</div>";
        $display_block .= "<div class='col-2'>Select</div>";
        $display_block .= "</div>"; // close row

        // 1 & 2
        $display_block .= "<div class='row combinationAgreed'>";
        $display_block .= "<div class='col-3'>Markers 1&2</div>";
        $display_block .= "<div class='col-2'><span class='feedbackTotalMarkX' id=diff1> </span></div>";
        $display_block .= "<div class='col-2'><span class='feedbackTotalMarkX' id=mean1> </span></div>";
        if (($marker_contrib[1] == 1) && ($marker_contrib[2] == 1)) {
            $is_checked = "checked";
        } else {
            $is_checked = "";
        }
        $select_block   = "<input type='radio' id='idagreed1' name='agreed' value='0011' $is_checked>";
        $display_block .= "<div class='col-2'>$select_block</div>";
        $display_block .= "</div>"; // close row

        // 1 & 3
        $display_block .= "<div class='row combinationAgreed'>";
        $display_block .= "<div class='col-3'>Markers 1&3</div>";
        $display_block .= "<div class='col-2'><span class='feedbackTotalMarkX' id=diff2> </span></div>";
        $display_block .= "<div class='col-2'><span class='feedbackTotalMarkX' id=mean2> </span></div>";
        if (($marker_contrib[1] == 1) && ($marker_contrib[3] == 1)) {
            $is_checked = "checked";
        } else {
            $is_checked = "";
        }
        $select_block   = "<input type='radio' id='idagreed2' name='agreed' value='0101' $is_checked>";
        $display_block .= "<div class='col-2'>$select_block</div>";
        $display_block .= "</div>"; // close row

        // 2 & 3
        $display_block .= "<div class='row combinationAgreed'>";
        $display_block .= "<div class='col-3'>Markers 2&3</div>";
        $display_block .= "<div class='col-2'><span class='feedbackTotalMarkX' id=diff3> </span></div>";
        $display_block .= "<div class='col-2'><span class='feedbackTotalMarkX' id=mean3> </span></div>";
        if (($marker_contrib[2] == 1) && ($marker_contrib[3] == 1)) {
            $is_checked = "checked";
        } else {
            $is_checked = "";
        }
        $select_block   = "<input type='radio' id='idagreed3' name='agreed' value='0110' $is_checked>";
        $display_block .= "<div class='col-2'>$select_block</div>";
        $display_block .= "</div>"; // close row

        // 1 & 2 & 3
        $display_block .= "<div class='row combinationAgreed'>";
        $display_block .= "<div class='col-3'>Markers 1&2&3</div>";
        $display_block .= "<div class='col-2'><span class='feedbackTotalMarkX' id=diff4> </span></div>";
        $display_block .= "<div class='col-2'><span class='feedbackTotalMarkX' id=mean4> </span></div>";
        if (($marker_contrib[1] == 1) && ($marker_contrib[2] == 1) && ($marker_contrib[3] == 1)) {
            $is_checked = "checked";
        } else {
            $is_checked = "";
        }
        $select_block   = "<input type='radio' id='idagreed4' name='agreed' value='0111' $is_checked>";
        $display_block .= "<div class='col-2'>$select_block</div>";
        $display_block .= "</div>"; // close row

        // Overwrite
        if ($marker_contrib[4] == 1) {
            $is_checked = "checked";
            $overwrite_value = $current_mark;
        } else {
            $is_checked = "";
            $overwrite_value = 0;
        }
        $display_block .= "<div class='row combinationAgreed'>";
        $display_block .= "<div class='col-3'>Overwrite Mark</div>";
        $display_block .= "<div class='col-2'>Value:</div>";
        $overwrite_block = "\n<input type='number' id='idoverwritten' name='overwritten' value='$overwrite_value' min='0' max='100' step='0.1'> \n";
        $display_block .= "<div class='col-2'>$overwrite_block</div>";
        if ($marker_contrib[4] == 1) {
            $is_checked = "checked";
        } else {
            $is_checked = "";
        }
        $select_block   = "<input type='radio' id='idagreed4' name='agreed' value='1000' $is_checked>";
        $display_block .= "<div class='col-2'>$select_block</div>";
        $display_block .= "</div>"; // close row

        $display_block .= "<label for='reason_textarea' class='form-label'><h5>Reason for the decision:</h5></label>\n";
        $display_block .= "<textarea class='form-control' id='reasontext' name='reasontext' rows='5' placeholder='Please give a reason for the decision made in awarding the marks based on three markers.'>$reason_text</textarea>\n";
        $display_block .= "</div>\n";
        $display_block .= "</div>\n";

        //$display_block .= "<P><a href=\"$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&pm_id=$pm_id&module1=$module1&year_begin=$year_begin&part=$assesspart&topdf=1&force_pdf=1&who=$who&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag\">Show the PDF feedback sheet that the student will see</A></P>\n";
        $display_block .= "</div>";

        //$display_block .= "<div class='container-fluid'>";
        $display_block .= "<div class='row mt-3'>";
        $display_block .= "  <div class='col-sm-3'>";
        $display_block .= "    <input class='btn btn-sm btn-outline-secondary' type=\"button\" id=\"validate\" name=\"btn_validate\" value=\"Check all responses completed\" >\n";
        $display_block .= "  </div>";
        $display_block .= "  <div class='col-sm-3'></div>";

        $display_block .= "  <div class='col-sm-6'>";
        $display_block .= "    <span id='show_pdf_id'>";
        $display_block .= "      <A class='btn btn-sm btn-info float-end' target='_blank' href='$_SERVER[PHP_SELF]?sid=$sid&pid=$project_id&pm_id=$pm_id&module1=$module1&year_begin=$year_begin&part=$assesspart&topdf=1&force_pdf=1&who=$who&isnumeric=$is_numeric&o_f=$show_overall_feedback_flag'>Show the PDF feedback sheet that the student will see</A>";
        $display_block .= "    </span>";
        $display_block .= "  </div>";
        $display_block .= "</div>";
        //$display_block .= "</div>";

        //$display_block .= "<div class='row mt-3'>";
        //$display_block .= "  <div class='col-6'>";
        $display_block .= "    <input class='btn btn-lg btn-warning' type='submit' id='submitbutton' name='submit' value='Submit the selected combination of marks to the database' >\n";
        //$display_block .= "  </div>";
        //$display_block .= "  <div class='col-6'>";
        //$display_block .= "  </div>";
        //$display_block .= "</div>";

        //$display_block .= "<BR>\n<input type=\"button\" id=\"validate\" name=\"btn_validate\" value=\"Check all selected\" >";
        $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"commit\">";
        $display_block .= "\n<input type=\"hidden\" name=\"nc\" value=\"$num_categories\">";

        //$display_block .= "\n<p><input type=\"submit\" id=\"disagreebutton\" name=\"disagree\" value=\"Markers Cannot Agree. Request a third marker's opinion.\" style=\"float: left;\"></p>\n";

        //$display_block .= "\n<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit the selected combination of marks to the database\" ></p>\n";

        //$display_block .= "<div class='row mb-5'>\n";
        //$display_block .= "<input class='btn btn-lg btn-warning' type='submit' id='submitbutton' name='submit' value='Submit the selected combination of marks to the database' >\n";
        //$display_block .= "</div>\n";
        $display_block .= "</form>";

        //$display_block .= "\n<p><span class=\"feedbackSectionMark\" id=\"submittext\">The difference is too great. (> $accept_var%). Correct this to display the Submit button.\n</span></p>\n";

    }

}
// End of main

?>

<!DOCTYPE html>
<html>
<?php
(@include_once("$mobile_head_bs")) OR die("Cannot find this file to include: $desktop_head<BR>");
?>

<script type="text/javascript">
    $(document).ready(function () {
        //alert("Doing Sum2 " );
        Sum2Sections();
        //alert("Done Sum2 " );
        var s_1 = $("#sticker_1");
        var pos_1 = s_1.position();
        var s_2 = $("#sticker_2");
        var s_3 = $("#sticker_3");
        var pos_2 = pos_1;
        var pos_3 = pos_1;
        $(window).scroll(function () {
            var windowpos = $(window).scrollTop();
            //s.html("Distance from top:" + pos_1.top + "<br />Scroll position: " + windowpos);
            if (windowpos >= pos_1.top) {
                s_1.addClass("stick");
                s_2.addClass("stick");
                s_3.addClass("stick");
            } else {
                s_1.removeClass("stick");
                s_2.removeClass("stick");
                s_3.removeClass("stick");
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

        $("[id^=section] input[type='number']").bind('keyup change click', function () {
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
            var mark31 = Number($("#sectionmark31").text());
            var mark32 = Number($("#sectionmark32").text());
            var mark33 = Number($("#sectionmark33").text());
            var mark34 = Number($("#sectionmark34").text());
            var mark35 = Number($("#sectionmark35").text());

            //mark1=1;
            //mark2=1;
            //mark3=1;
            //mark4=1;
            var mark_1 = (mark11 + mark12 + mark13 + mark14 + mark15) * 100 / markmax;
            var mark_2 = (mark21 + mark22 + mark23 + mark24 + mark25) * 100 / markmax;
            var mark_3 = (mark31 + mark32 + mark33 + mark34 + mark35) * 100 / markmax;
            var mark_diff_1 = Math.abs(mark_1 - mark_2);
            var mark_diff_2 = Math.abs(mark_1 - mark_3);
            var mark_diff_3 = Math.abs(mark_2 - mark_3);
            var mark_diff_4 = Math.max(mark_diff_1, mark_diff_2, mark_diff_3);
            var mark_mean_1 = (mark_1 + mark_2) * 0.5;
            var mark_mean_2 = (mark_1 + mark_3) * 0.5;
            var mark_mean_3 = (mark_2 + mark_3) * 0.5;
            var mark_mean_4 = (mark_1 + mark_2 + mark_3) / 3.0;

            var mark_diff = Math.abs(mark_1 - mark_2);
            $("#totalmarks1").html(mark_1.toFixed(1) + "%");
            $("#totalmarks2").html(mark_2.toFixed(1) + "%");
            $("#totalmarks3").html(mark_3.toFixed(1) + "%");
            $("#totalmarksdiff").html(mark_diff_4.toFixed(1) + "%");

            $("#diff1").html(mark_diff_1.toFixed(1) + "%");
            $("#diff2").html(mark_diff_2.toFixed(1) + "%");
            $("#diff3").html(mark_diff_3.toFixed(1) + "%");
            $("#diff4").html(mark_diff_4.toFixed(1) + "%");

            $("#mean1").html(mark_mean_1.toFixed(1) + "%");
            $("#mean2").html(mark_mean_2.toFixed(1) + "%");
            $("#mean3").html(mark_mean_3.toFixed(1) + "%");
            $("#mean4").html(mark_mean_4.toFixed(1) + "%");

            $("#sticker_1").html(mark_1.toFixed(1) + "%");
            $("#sticker_2").html(mark_2.toFixed(1) + "%");
            $("#sticker_3").html(mark_3.toFixed(1) + "%");

            if (mark_diff <= 200) {
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

</div>
    <footer class="py-3 my-4 bg-dark text-white">
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./<?php echo("$project_marking_page?pm_id=$pm_id") ?>" class="nav-link px-2 text-muted">Back marking list</a></li>
            <li class="nav-item"><a href="./index.php" class="nav-link px-2 text-muted">Year index page</a></li>
        </ul>
    </footer>
    <!-- footer -->

</div>
<!-- /wrapper -->
</BODY>
</HTML>
