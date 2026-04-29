<?php
// connect to the datbase
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('../staff_functions.php')) or die('Cannot include staff_function.php<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot include markstore_function.php<BR>');
(@include_once('../table_functions.php')) or die('Cannot include table_function.php<BR>');
(@include_once('../show_feedback_form.php')) or die('Cannot include show_feedback<BR>');
(@include_once('../show_minor_part_feedback_form.php')) or die('Cannot include show_minor_part_feedback<BR>');
(@include_once('../EmailPDFAttachment.php')) or die('Cannot find this file to include: EmailPDFAttachment_php<BR>');
(@include_once('../../student/student_functions.php')) or die('Cannot find this file to include: student_functions<BR>');
(@include_once('./EmailBodyString.php')) or die('Cannot find this file to include: EmailBodyString<BR>');
(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

require_once __BASE_DIR__ . '/vendor/autoload.php';

function GetAllocatedNumber($db, $sid, $selection_table, $master_id_of_allocated) {
    $num = 0;
    if ($sid == '') {
        return $num;
    }
    $get_list_query = "select * from $selection_table where sid=$sid";
    try {
        $get_list_res = $db->query($get_list_query);
    } catch (PDOException $ex) {
        pma_log_db_error($ex, 'email_project_allocation_to_staff');

        return $num;
    }

    $recs = $get_list_res->fetch();

    $choice1 = $recs['choice1'];
    $choice2 = $recs['choice2'];
    $choice3 = $recs['choice3'];
    $choice4 = $recs['choice4'];
    $choice5 = $recs['choice5'];

    if ($choice1 == $master_id_of_allocated) {
        $num = 1;
    } elseif ($choice2 == $master_id_of_allocated) {
        $num = 2;
    } elseif ($choice3 == $master_id_of_allocated) {
        $num = 3;
    } elseif ($choice4 == $master_id_of_allocated) {
        $num = 4;
    } elseif ($choice5 == $master_id_of_allocated) {
        $num = 5;
    }
    return $num;
}

$display_block = '';
$display_block .= $connect_msg;

// Check the admin level
//$access = false;
//$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
//$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

$assess_part_description_text = '';

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];

$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($is_module_admin || $admin_access) {
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
    //$activestaff = $_REQUEST['activestaff'];
    $part = $_REQUEST['part'];
    $makepdf = $_REQUEST['makepdf'];
    $list = $_REQUEST['list'];
    $summary_mark = $_REQUEST['summary_mark'];

    //  foreach ($_REQUEST as $key => $value){
    //  }

    // Get the feedback_record table
    // Create it is if does not exist

    $query = "SHOW TABLES LIKE '$feedback_records'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_rows = $results->rowCount();
    if ($num_rows == 0) {
        // No table so create it
        create_feedback_records_table_structure($db, $feedback_records);
    }

    ///////////////////////////////////////////////////////////////////////////////////
    // The writing of the mpdf file for each student is time consuming and memory heavy
    // So must disable the timeout and increase memory limits
    // It may be necessary to run this script from the command line.
    ///////////////////////////////////////////////////////////////////////////////////
    // Disable PHP Timeout
    //set php script timeout, 0 to disable
    set_time_limit(0);
    ini_set('memory_limit', '144M');

    /*
    $assess_part_description_text = $p_description[$part];
    $summary_desc = "";
    if ($summary_mark == 1) {
        $summary_desc = "(with summary marks)";
    }
    */
    $query = "select * from $projects_setup where id=$pm_id";
    //$get_setup_list = mysql_query($get_setup) or die(" Unable to get database configuration.<BR>Query = $get_setup");
    //$setup_data = mysql_fetch_array($get_setup_list);
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $setup_data = $results->fetch();

    $nowarray = getdate();
    $staff_close_str = $setup_data['staff_close'];
    $staff_close_date = strtotime($staff_close_str);
    $month_i = date('m', $staff_close_date);
    $day_i = date('d', $staff_close_date);
    $year_i = date('Y', $staff_close_date);
    $close = mktime(17, 0, 0, $month_i, $day_i, $year_i);
    $cldate = '5pm, ' . date('D', $staff_close_date) . ' ' . $day_i . ' ' . date('M', $staff_close_date) . ' ' . $year_i;
    $module1 = $setup_data['module'];
    $year_begin = $setup_data['year_begin'];
    $module_short_code = $setup_data['short_code'];
    $module_leader_fullname = $setup_data['module_leader'];
    $module_leader_id = GetStaffIDFromName($db, $module_leader_fullname, $staff2);
    $module_leader_email = GetStaffEmailFromID($db, $module_leader_id, $staff2);

    $display_block .= "<h1>Emailing Project Allocation to STAFF for the $module_short_description ($module_short_code), $module_prefix$module1$module_postfix,  for year $year_begin</h1>";

    // see all the POST values
    /*
    foreach ($_REQUEST as $key => $value){
      echo "{$key} = {$value}<BR>\r\n";
    }
    */
    //$BASE_URL = getenv('BASE_URL');// "https://apps.engineering.leeds.ac.uk";

    // Email body example
    $lb = "\n";
    //$lb = "<BR>\n";
    $sp = ' ';
    $sp = '&nbsp;';
    $email_body = "This email is to notify you of the students that you have been allocated to your projects for the $module_short_description.$lb$lb";
    $email_body .= "They will be told soon about this allocation and should contact you in the next couple of days to arrange an initial meeting.$lb$lb";
    $email_body .= "If you have any questions about your allocation please contact the module leader <a href='mailto:$module_leader_email'> $module_leader_fullname</A>.$lb";
    //$email_body .= "$BASE_URL";

    if ($POST_OP == 'select') {
        $display_block .= '<P>In the box below type the covering email text you want to send to staff. Put in HTML code if necessary.<BR>';

        $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id&part=$part'>
                      <P><textarea class='form-control' rows='20' cols='100' name='email_body_text' placeholder='Type the text you want to send to all staff...'>$email_body</textarea></P>\n";
        $display_block .= "<P>Email Subject line <input size='80' class='form-control'  type='text' name='subjectline' value='Student Allocation for $module_short_description. '></P>\n";

        $display_block .= "<p><input type='checkbox' name='reallysend' value='reallysend'>Tick this box to confirm you REALLY want to send the emails.</p>\n
                     <p><input type='checkbox' name='makepdf' value='active'>Tick this box as well to be sure you really really want to send the emails.</p>\n
                     <p><B>BOTH of the above must be ticked for the email  to be sent to staff.</B></p>\n";

        //$display_block .= $summary_block;

        $display_block .= "\n<input type='hidden' name='module1' value='$module1'>
                     <input type='hidden' name='year_begin' value='$year_begin'>
                     <input type='hidden' name='op' value='send'>
                     
                     <P><input class='btn btn-primary' type='submit' name='submit' value='Tick the boxes and click to here to email allocations NOW.'></P>\n";
        $display_block .= "<p><B>From the table below all names that are ticked will have their allocation emailed (usually this is ALL students)<BR><span style='font-size:70%;'>(Only allocated students will be included.)</span></B></p>";
        //$display_block .= "<div>Select/Un-select all<input type='checkbox' id='select-all' nmae='selectAll' checked value=''/></div>\n";
        $display_block .= GetStudentListTable($db, $project_allocated, $students, $staff2, $feedback_records, $part);
        $display_block .= "\n</form><BR><HR>";
    } elseif ($POST_OP == 'send') {
        //$link = $BASE_URL;

        //$display_block .= "<b>Updates to the project area database closes at $cldate</b>";

        $display_block .= "<BR>Sending this:$lb$lb";
        //$display_block .= $email_block;

        $query = "select * from $staff_names2 order by familyname, firstname ";
        //$get_list_res = mysql_query($get_list) or die(" Unable to create staff list from database:<BR> query = $get_list <BR>");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $get_list_res = $results->fetchAll();

        foreach ($get_list_res as $staff_rec) {
            $staff_id = $staff_rec['staffid'];
            $inactive = $staff_rec['inactive'];
            $staff_fullname = $staff_rec['fullname'];
            $staff_email = $staff_rec['email'];

            // now count the number of students allocated
            $query = "select * from $project_allocated WHERE supervisor_id=$staff_id";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
            $num_rows = $results->rowCount();//mysql_num_rows($get_student_list_res);

            if ($num_rows == 1) {
                $staff_email_text = "You have been allocated $num_rows student $lb$lb";
            } else {
                $staff_email_text = "You have been allocated $num_rows students $lb$lb";
            }
            $student_rows = $results->fetchAll();
            foreach ($student_rows as $recs) {
                $student_counter++;
                $project_id = $recs['projectid'];
                $master_id = $recs['masterid'];
                $sid = $recs['sid'];
                $title = stripslashes($recs['title']);
                $student_username = GetStudentUsernameFromID($db, $sid, $students);
                $student_name = GetStudentNameFromUsername($db, $student_username, $students);
                $student_email = $student_username . $local_email_domain;

                $supervisor_id = $recs['supervisor_id'];
                $second_marker_id = $recs['second_marker_id'];
                $staff_email_text .= "$student_name ($student_email): ($project_id) $title $lb";
            }

            $total_mark_for_part = 1;
            $checked_to_send = 'off';
            //$checked_name = "check_$sid";
            $checked_to_send = 'on';//$_POST[$checked_name];

            if ($inactive == 0) {
                $email_full = $staff_fullname . "$lb";
                $email_full .= $email_body_text;
                $email_full .= $staff_email_text;

                //$display_block .= "$student_name$lb$lb";
                $display_block .= nl2br($email_full);
                $display_block .= "<HR>$lb";

                if ($subjectline == '') {
                    $subject = 'Project Allocation';
                } else {
                    $subject = $subjectline;
                }

                $email_manager = $module_leader_email;//"p.a.sleigh@leeds.ac.uk";

                $to = $staff_email;//"$username@leeds.ac.uk";
                //$myemail_to = "p.a.sleigh@leeds.ac.uk"; //<< REMOVE IN THE FINAL VERSION
                $myemail_from = "$email_manager (Project Module Leader)";
                $myemail_subject = $subject;
                $myemail_message = nl2br($email_full);

                $from = "$this_staff_username$local_email_domain";
                //$to = $student_email;
                //$to = $from;

                if ($reallysend == null) {
                    $display_block .= "<em>NOT SENT: $subject to $to<HR><BR></em>";
                } else {
                    EmailBodyString($to, $from, $subject, $email_full);
                    $display_block .= "<em>Sending: $subject to $to<BR></em>";
                }

                flush();
                ob_flush();
            } else {
                // Not sent as flagged for not sending.
                $display_block .= "<HR>Flagged not to send. Not sent. $staff_fullname ($staff_email). <HR><BR>$lb";
            }

            $student_counter = 0;
        }
    }// end of if ($POST_OP == "select")
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
        <span class="navbar-brand">Email Student Feedback for: <?php echo(" $module_short_description ($module1), $year_begin")?></span>
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
</html>
