<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// connect to the datbase
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot include staff_function.php<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot include markstore_function.php<BR>");
(@include_once("../table_functions.php")) OR die("Cannot include table_function.php<BR>");
(@include_once("../show_feedback_form.php")) OR die("Cannot include show_feedback<BR>");
(@include_once("../show_minor_part_feedback_form.php")) OR die("Cannot include show_minor_part_feedback<BR>");
(@include_once("../EmailPDFAttachment.php")) OR die("Cannot find this file to include: EmailPDFAttachment_php<BR>");

require_once __BASE_DIR__ . '/vendor/autoload.php';

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

$assess_part_description_text = "";

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db,$this_staff_username, $this_staff_full_name, $staff_names,$access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase );
if($is_module_admin){
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$access_block = "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if($is_module_admin || $admin_access) {

//if ($access) {
    if (array_key_exists('module1', $_POST) == false) {
        $_POST['module1'] = null;
    }
    if (array_key_exists('year_begin', $_POST) == false) {
        $_POST['year_begin'] = null;
    }
    if (array_key_exists('send', $_POST) == false) {
        $_POST['send'] = null;
    }
    if (array_key_exists('messagetype', $_POST) == false) {
        $_POST['messagetype'] = null;
    }
    if (array_key_exists('reallysend', $_POST) == false) {
        $_POST['reallysend'] = null;
    }
    if (array_key_exists('email_body_text', $_POST) == false) {
        $_POST['email_body_text'] = null;
    }
    if (array_key_exists('subjectline', $_POST) == false) {
        $_POST['subjectline'] = null;
    }
    if (array_key_exists('pm_id', $_REQUEST) == false) {
        $_REQUEST['pm_id'] = null;
    }
    if (array_key_exists('op', $_REQUEST) == false) {
        $_REQUEST['op'] = null;
    }
    if (array_key_exists('part', $_REQUEST) == false) {
        $_REQUEST['part'] = null;
    }
    if (array_key_exists('makepdf', $_REQUEST) == false) {
        $_REQUEST['makepdf'] = null;
    }
    if (array_key_exists('list', $_REQUEST) == false) {
        $_REQUEST['list'] = null;
    }
    if (array_key_exists('summary_mark', $_REQUEST) == false) {
        $_REQUEST['summary_mark'] = null;
    }
    $send = $_POST['send'];
    $reallysend = $_POST['reallysend'];
    $module1 = $_POST['module1'];
    $year_begin = $_POST['year_begin'];
    $messagetype = $_POST['messagetype'];
    $email_body_text = $_POST['email_body_text'];
    $subjectline = $_POST['subjectline'];
    $pm_id = $_REQUEST['pm_id'];
    $POST_OP = $_REQUEST['op'];

    $part = $_REQUEST['part'];
    $makepdf = $_REQUEST['makepdf'];
    $list = $_REQUEST['list'];
    $summary_mark = $_REQUEST['summary_mark'];


    // Get the feedback_record table
    // Create it is if does not exist
    $query = "SHOW TABLES LIKE '$feedback_records'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_rows = $results->rowCount();
    if($num_rows == 0){
        // No table so create it
        create_feedback_records_table_structure($db, $feedback_records);
    }

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

    $assess_part_description_text = $p_description[$part];
    $summary_desc = "";
    if ($summary_mark == 1) {
        $summary_desc = "(with summary marks)";
    }
    $query = "select * from $projects_setup where id=$pm_id";
//$get_setup_list = mysql_query($get_setup) or die(" Unable to get database configuration.<BR>Query = $get_setup");
//$setup_data = mysql_fetch_array($get_setup_list);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $setup_data = $results->fetch();

    $nowarray = getdate();
    $staff_close_str = $setup_data['staff_close'];
    $staff_close_date = strtotime($staff_close_str);
    $month_i = date("m", $staff_close_date);
    $day_i = date("d", $staff_close_date);
    $year_i = date("Y", $staff_close_date);
    $close = mktime(17, 0, 0, $month_i, $day_i, $year_i);
    $cldate = "5pm, " . date("D", $staff_close_date) . " " . $day_i . " " . date("M", $staff_close_date) . " " . $year_i;
    $module1 = $setup_data['module'];
    $year_begin = $setup_data['year_begin'];
    $module_short_code = $setup_data['short_code'];

    $display_block .= "<h1>Use this for to email feedback to students on the module:</h1>";
    $display_block .= "<H2>$module_short_description ($module_short_code), $module_prefix$module1$module_postfix,  for year $year_begin</h1>";


    $display_block .= "<h2>Feedback for the <span style=text-decoration:underline;>$assess_part_description_text</span> section $summary_desc</h2>";

    $is_numeric = GetIsNumeric($db, $pm_id, $part, $projects_setup);
// see all the POST values
    /*
    foreach ($_REQUEST as $key => $value){
      echo "{$key} = {$value}<BR>\r\n";
    }
    */


// Email body example
    $lb = "\n";
//$lb = "<BR>\n";
    $sp = " ";
    $sp = "&nbsp;";
    $email_body = "Attached to this email is the feedback for the $assess_part_description_text part of the $module_short_description $summary_desc.$lb$lb";
    $email_body .= "You should read this feedback, taking in the comments made and try to use these as advice to help improve similar future work.$lb$lb";
    $email_body .= "If you have any questions about this feedback (or any other aspects of the project) please contact the supervisor, marker or module leader and they will be pleased to discuss it further.$lb";

    $summary_block = "";
    if ($summary_mark == 1) {
        // selection of what goes in the summary block
        $summary_block .= "<div style=\"border-style: solid; border-width:1px; width:50%\" >Select which marks to return to the students<BR>\n";
        $summary_block .= "<UL>\n";
        for ($i = 1; $i <= $num_parts; $i++) {
            $summary_block .= "<LI>Part $i: $p_description[$i] ($p_percent[$i]%)";
            $summary_block .= "<input type=\"checkbox\" name=\"show_mark_$i\" value=\"show_mark_$i\" checked>";
            $summary_block .= "</LI>\n";
        }
        $summary_block .= "<LI>Plus the overall mark.</LI>\n";
        $summary_block .= "</UL>\n";
        $summary_block .= "</div>";
    }

    if ($POST_OP == "select") {

        $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id&part=$part'>";
        $display_block .= "<div class='form-group m-2'>";
            $display_block .= "<label for='email_body_text' class='fw-bold' >In the box below type the covering email text you want to send to students. Put in HTML code if necessary.</label>";
            $display_block .= "<textarea class='form-control' rows='20' cols='100' name='email_body_text' id = 'email_body_text'>$email_body</textarea>";
        $display_block .= "</div>\n";

        $display_block .= "<div class='form-group m-2'>";
        $display_block .= "<label for='subjectline' class='fw-bold' >Email Subject line</label>";
        $display_block .= "<input class='form-control' size='80' type='text' name='subjectline' id='subjectline' value='Feedback for $module_short_description. $assess_part_description_text part $summary_desc'></P>\n";
        $display_block .= "</div>\n";

        $display_block .= "<div class='form-group'>";
        $display_block .= "<input type='checkbox' name='reallysend' id='reallysend' value='reallysend'>\n";
        $display_block .= "<label for='reallysend'> Tick this box to confirm you REALLY want to send the emails. </label>";
        $display_block .= "</div>\n";

        $display_block .= "<div class='form-group'>";
        $display_block .= "<input type='checkbox' name='makepdf' id='makepdf' value='active'>\n";
        $display_block .= "<label for='makepdf'> Tick this box to actually produce the PDFs. </label>";
        $display_block .= "</div>\n";

        $display_block .= "<p><B>BOTH of the above must be ticked for the email AND attachment to be sent to students.</B></p>\n";

        $display_block .= $summary_block;

        $display_block .= "\n<input type='hidden' name='module1' value='$module1'>
                     <input type='hidden' name='year_begin' value='$year_begin'>
                     <input type='hidden' name='op' value='send'>
                     
                     <P><button type='submit' class='btn btn-primary' name='submit'>Tick the boxes and click to here to email feedback NOW.</button></P>\n";
        $display_block .= "<p><B>From the table below all names that are ticked will have their results emailed<BR><span style=\"font-size:70%;\">(Only results from completed work that has been marked will be sent.)</span></B></p>";
        //$display_block .= "<div>Select/Un-select all<input type=\"checkbox\" id=\"select-all\" nmae=\"selectAll\" checked value=\"\"/></div>\n";
        $display_block .= GetStudentListTable($db, $project_allocated, $students, $staff2, $feedback_records, $part);
        $display_block .= "\n</form><BR><HR>";
    } elseif ($POST_OP == "send") {

        $display_block .= "<BR>Sending this:$lb$lb";

        $query = "select * from $staff_names2 order by familyname, firstname ";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $get_list_res = $results->fetch();

        $query = "select * from $project_allocated order by projectid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $student_rows = $results->fetchAll();

        $student_counter = 0;
        $num_rows = $results->rowCount();

        if ($num_rows < 1) {
            //no records
            $display_block .= "<p><em>Sorry, there are no students listed to send to!</em></p>";
        } else {
            //has records
            foreach ($student_rows as $recs) {
                $student_counter++;
                $project_id = $recs['projectid'];
                $sid = $recs['sid'];
                $title = stripslashes($recs['title']);

                $supervisor_id = $recs['supervisor_id'];
                $second_marker_id = $recs['second_marker_id'];

                $checked_to_send = "off";
                $checked_name = "check_$sid";
                if (array_key_exists($checked_name, $_POST) == true) {
                    $checked_to_send = $_POST[$checked_name];
                }
                //echo("<BR>checked_id=$checked_name, checked_to_send=$checked_to_send.<BR>");
                // To do
                if ($p_num_markers[$part] == 1) {
                    $who = 1;
                } else {
                    //echo("Multiple markers has not been set up for PDF output.<BR>");
                    $who = 1;
                }

                //print_r3("sid",$sid);
                //print_r3("part",$part);
                //print_r3("Allocated table name",$project_allocated);
                //print_r3("Staff table",$staff_names2);
                $marker_names = GetMarkerNamesArray($db, $sid, $part, $project_allocated, $staff_names2);
                //print_r3("Marker names array",$marker_names);

                //print_r3("Mark store totals",$markstore_totals);
                $mark_method = GetMarkMethodString($db, $sid, $part, $markstore_totals);
                //print_r3("Mark method string",$mark_method);

                //print_r3("markstores",$markstores);

                $marker_entry = "marker_p$part" . "m$who" . "_id";

                $marker_id = $recs[$marker_entry];
                $supervisor_name = GetStaffNameFromID($db, $supervisor_id, $staff_names2, NULL);
                $marker_name = GetStaffNameFromID($db, $marker_id, $staff_names2, NULL);
                $second_marker_name = GetStaffNameFromID($db, $second_marker_id, $staff_names2, NULL);
                ;

                $query = "select * from $students where sid=$sid";
                //$get_list_res = mysql_query($get_list) or die("Unable to get student details from database. <BR>Query: $get_list");
                //$student_recs = mysql_fetch_array($get_list_res);
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }
                $student_recs = $results->fetch();

                if($student_recs) {
                    $student_name = $student_recs['name'];
                    $username = $student_recs['username'];
                    $programme = $student_recs['programme'];
                    $sel = $student_recs['sel'];
                }else{
                    $student_name = "";
                    $username = "";
                    $programme = "";
                    $sel = "";
                }

                $assessqs_year = $assessqs[$part];
                $assess_cats = $assessqs_cats[$part];
                $assesstype_text = $p_description[$part];
                $mark_max = $p_max[$part];
                $mark_pc = $p_percent[$part];

                $student_email = "$username$local_email_domain";

                $email_fullname = "$student_name, $lb$lb";
                // if not marked then do not email student
                $total_mark_for_part = GetTotalForPart($db, $sid, $part, $markstore_totals);
                //echo("total mark for part: $total_mark_for_part<BR>");
                if ($checked_to_send == "on") {
                    if ($total_mark_for_part >= 0) //if( HaveAllMarkersMarked($sid, $part, $p_num_markers[$part], $markstores, $markstore_totals) )
                    {
                        $email_full = $email_fullname;
                        $email_full .= $email_body_text;

                        $display_block .= "$student_name$lb$lb";
                        $display_block .= nl2br($email_full);
                        $display_block .= "<HR>$lb";

                        // Get the feedback form
                        $feedback_form_html = "";
                        $markstore = $markstores[$part][$who];
                        set_time_limit(0);

                        //print_r3("mark_method",$mark_method);
                        $markstore_list = GetMarkStoreListFromMarkMethodString($mark_method, $markstores[$part][1], $markstores[$part][2], $markstores[$part][3]);
                        //print_r3("Markstore_list",$markstore_list);

                        $marker_list = GetMarkerListFromMarkMethodString($mark_method, $marker_names[1], $marker_names[2], $marker_names[3]);
                        $third_marker_name = $marker_names[3];

                        $num_markstores = count($markstore_list) - 1;
                        //echo("markers $mark_method <BR>");
                        //echo("num markstores = $num_markstores <BR>");
                        //print_r2($markstore_list);
                        //$markstore3 = "";
                        if ($num_markstores == 1) {
                            $feedback_form_html .= show_feedback_form1($db, $sid, $markstore_list[1], $markstore_list[1], $markstore_list[1], $markstore_totals, $assessqs_year, $student_name, $username, $title, $marker_list[1], $marker_list[1], $marker_name, $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $part, $assesstype_text, $module_short_description, $is_numeric);
                        } elseif ($num_markstores == 2) {
                            $feedback_form_html .= show_feedback_form2($db, $sid, $markstore_list[1], $markstore_list[2], $markstore_list[2], $markstore_totals, $assessqs_year, $student_name, $username, $title, $marker_list[1], $marker_list[2], $marker_name, $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $part, $assesstype_text, $module_short_description, $is_numeric);
                        } elseif ($num_markstores == 3) {
                            $feedback_form_html .= show_feedback_form3($db, $sid, $markstore_list[1], $markstore_list[2], $markstore_list[3], $markstore_totals, $assessqs_year, $student_name, $username, $title, $marker_list[1], $marker_list[2], $marker_name, $project_id, $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme, $part, $assesstype_text, $module_short_description, $is_numeric);
                        }else{
                            continue;
                        }
                        //$feedback_form_html = utf8_encode($feedback_form_html); // deprecated
                        $feedback_form_html = mb_convert_encoding($feedback_form_html, 'UTF-8', mb_list_encodings());

                        //$feedback_form_html .= show_minor_part_feedback_form($sid,$markstore,$assessqs_year,$student_name,$username,$title,$supervisor_name,$second_marker_name,$marker_name,$project_id,$assesstype_text,$assess_cats,$mark_max,$mark_pc,$programme,$part      ,$assesstype_text, $module_short_description);
                        //echo($feedback_form_html);
                        //exit(0);
                        // MPDF functions
                        if ($makepdf == null) {
                            $fileattached_pdf = "";
                            $name_of_file = "";
                            $do_not_attach = 1;
                        } else {
                            unset($mpdf);
                            error_reporting(0);
                            $system_temp_dir = sys_get_temp_dir();
                            $mpdf = new \Mpdf\Mpdf(['tempDir'=>$system_temp_dir]);
                            // Set a simple Footer including the date and page number
                            $mpdf->setFooter("$student_name Date printed: {DATE j M Y}.   page {PAGENO}");
                            $stylesheet = file_get_contents('../css/pagedesc.css');
                            $mpdf->WriteHTML($stylesheet, 1);  // The parameter 1 tells that this is css/style only and not body/html/text
                            $mpdf->WriteHTML($feedback_form_html);

                            // Email to student
                            $fileattached_pdf = $mpdf->Output('', 'S');
                            $name_of_file = "Feedback-$module_short_description-$assess_part_description_text.pdf";
                            $do_not_attach = 0;
                            error_reporting(-1);
                        }

                        if ($subjectline == "") {
                            $subject = "Project Feedback";
                        } else {
                            $subject = $subjectline;
                        }

                        $email_manager = $module_leader_email;

                        $myemail_to = "$username$local_email_domain";
                        //$myemail_to = "p.a.sleigh@leeds.ac.uk"; //<< REMOVE IN THE FINAL VERSION
                        $myemail_from = "$email_manager (Project feedback)";
                        $myemail_subject = $subject;
                        $myemail_message = nl2br($email_full);


                        //$from = "p.a.sleigh@leeds.ac.uk";
                        $from = $this_staff_email;
                        $cc_to = $email_manager;
                        //$to = $student_email;
                        //$to = $from;
                        if ($reallysend == null) {
                            $display_block .= "<em>NOT SENT: $subject to $myemail_to<HR><BR></em>";
                            //echo("Not sent $student_name ($username). Num: $student_counter<BR>\n");
                        } else {
                            $ok = EmailPDFAttachment($myemail_to, $myemail_from, $fileattached_pdf, $myemail_subject, $myemail_message, $name_of_file, $do_not_attach, $cc_to);
                            //EmailBodyString($to, $from, $subject, $email_full);
                            $ok  = 1;
                            $display_block .= "<em>Sending: $subject to $myemail_to<BR></em>";
                            if ($ok == "1") {
                                echo("Success emailing $student_name ($username). Num: $student_counter<BR>\n");
                                RecordFeedbackSent($db, $sid, $part, $feedback_records);
                            } else {
                                echo("Problems emailing $student_name ($username). Num: $student_counter. Email NOT SENT<BR>\n");
                            }
                        }
                        flush();
                        ob_flush();

                    } else {
                        // Not sent as not marked.
                        $display_block .= "<HR>Not marked. Not sent. $student_name ($username).  Num: $student_counter<HR><BR>$lb";
                        //echo("Not marked. Not sent. $student_name ($username): Num: $student_counter<BR>$lb");
                    }
                } else {
                    // Not sent as flagged for not sending.
                    $display_block .= "<HR>Flagged not to send. Not sent. $student_name ($username).  Num: $student_counter<HR><BR>$lb";
                    //echo("Flagged not to send. Not sent. $student_name ($username): Num: $student_counter<BR>$lb");
                }

            }
        }
    }// end of if ($POST_OP == "select")


}else{
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}
?>
<!DOCTYPE html>
<HTML>
<HEAD>
    <?php
    (@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
    ?>
<script type="text/javascript">
function submitForm(myValue)
{
	//action=\"modproject.php?module1=$module1&year_begin=$year_begin\"
  var elem = document.getElementById("myop");
  elem.value = myValue;
  //alert("Submitted" + myValue);
  //document.projectlist.submit();
  if(myValue=="mod")
  {
    document.projectlist.action = 'modproject.php?<?php echo("module1=$module1&year_begin=$year_begin") ?>';
  }else{
    document.projectlist.action = 'delproject.php?<?php echo("module1=$module1&year_begin=$year_begin") ?>';
  }
  document.projectlist.submit();
}
$(function(){
    $("#student_email_list").dataTable(
        {
            //"bSort":    false,
            "paging":   false,
            //"ordering": false,
            //"info":     false,
            "searching":false
        }
    );
})
$(function() {
    $("#select-all").on("click", function () {
        var all = $(this);
        $('#students_select_table').find('input:checkbox').each(function () {
            $(this).prop("checked", all.prop("checked"));
        });
    });
});
</script>
</HEAD>
<BODY link="#0000FF" vlink="#800080">
<div id="wrapper">
  <div id="header"> 
    <span class="header">Email Student Feedback for: <?php echo(" $module_short_description ($module1), $assess_part_description_text, $year_begin")?></span>
  </div><!-- /header -->

    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <?php echo("$access_block"); ?>
                <div class="card card-body top-30">
                <?php echo("$display_block"); ?>
                </div>
            </div>
        </div>
    </div> <!-- content -->

  <div id="footer">
      <P>Return to the
          <a href="../projectmarking_select_bs.php?pm_id=<?php echo("$pm_id") ?>">Module page</a> -
          <a href="../session_modules_bs.php?year_begin= <?php echo("$year_begin") ?>">Year index page</a> -
          <a href="./edit_module_settings.php?pm_id=<?php echo("$pm_id") ?>">Module admin page</a> -
          <a href="./index.php">Main admin page</a>.
      </P>
  </div><!-- /footer -->
    
</div><!-- /wrapper -->
</BODY>
</HTML>
