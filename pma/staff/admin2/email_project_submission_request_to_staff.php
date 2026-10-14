<?php

// connect to the datbase
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');

(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

$display_block = '';
$display_block .= $connect_msg;

(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

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
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($is_module_admin || $admin_access) {
    require('EmailBodyString.php');

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
    if (array_key_exists('activestaff', $_REQUEST) == false) {
        $_REQUEST['activestaff'] = null;
    }
    $send = $_POST['send'];
    $reallysend = $_POST['reallysend'];
    $module1 = $_POST['module1'];
    $year_begin = $_POST['year_begin'];
    $messagetype = $_POST['messagetype'];
    $email_body_text = $_POST['email_body_text'];
    $subjectline = $_POST['subjectline'];
    $pm_id = (int)$_REQUEST['pm_id'];
    $POST_OP = $_REQUEST['op'];
    $activestaff = $_REQUEST['activestaff'];

    //foreach ($_REQUEST as $key => $value){
    //}
    //
    // Check the date for editiing has not passed
    //

    //$get_setup = "select year_begin, module, short_code, dayname(staff_close), dayofmonth(staff_close), monthname(staff_close), month(staff_close), year(staff_close) from $projects_setup where id=$pm_id";
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

    $display_block .= "<h1>Emailing Staff about CIVE$module1 ($module_short_code) Projects Starting year $year_begin</h1>";

    if ($nowarray[0] > $close) {
        $display_block .= "<b>Updates to the project area database <font color=red>CLOSED</font> at $cldate</b>";
    } else {
        $display_block .= "<b>Updates to the project area database closes at $cldate</b>";
    }

    //if($module1 == "" || $year_begin == "")
    //{
    //	exit;
    //}
    //
    //if (array_key_exists('op', $_POST)==false){ $_POST['op']=null; }
    //
    //// if the form was submitted then enter it all into the database
    ////echo($_POST['op'] . "<BR>\n");
    //if (array_key_exists('op', $_POST)) {
    //  $POST_OP = $_POST['op']  ;
    //}else{
    //	$POST_OP = "";
    //}

    // see all the POST values
    /*
    foreach ($_POST as $key => $value){
      echo "{$key} = {$value}<BR>\r\n";
    }
    */
    // Email body example
    $lb = "\n";
    $lb = "<BR>\n";
    $sp = ' ';
    $sp = '&nbsp;';
    $email_body = "It is now time to propose project titles for Level 3 and 5 projects.$lb";
    $email_body .= "This should be done using the online system we have used for the past few years.$lb";
    $email_body .= "This year - to help you get started - I have  populated the list with last year's projects.$lb";
    $email_body .= "You should look at this list and add to, edit or delete projects as you require.$lb$lb";

    if ($POST_OP == 'select') {
        $display_block .= '<P>In the box below type the text you want to send out. Put in HTML code if necessary.<BR>';

        $display_block .= '<P>You can mail a <B>list of thier own projects</b> to staff by clicking the box below. <BR>The text you type  will then be followed by a list of the projects by each member of staff and instruction on how to access the data.</P>';

        $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>
                      <P><textarea class='form-control' rows='20' cols='100' name='email_body_text' placeholder='Type the text you want to send to all staff...'>$email_body</textarea></P>\n";
        $display_block .= "<P>Email Subject line <input class='form-control' type='text' name='subjectline'></P>\n";

        $display_block .= "<p><input type='checkbox' name='reallysend' value='reallysend'>Tick this box to confirm you want to send emails.</p>\n
                     <p><input type='checkbox' name='messagetype' value='free'>Tick this box to NOT INCLUDE a list of projects offered by the named supervisor.</p>\n
                     <p><input type='checkbox' name='activestaff' value='active' checked>Un-Tick this box to send to ALL staff. Ticked it goes to only to <B>active</b> staff.</p>\n

                     <input type='hidden' name='module1' value='$module1'>
                     <input type='hidden' name='year_begin' value='$year_begin'>
                     <input type='hidden' name='op' value='send'>
                     
                     <P><input class='btn btn-primary' type='submit' name='submit' value='Tick the box and click to this text to all staff NOW.'></P>
                     </form><BR><HR>";
    } elseif ($POST_OP == 'send') {
        //$link = "https://www2.mech-eng.leeds.ac.uk/civilprojects/staff/database/projects_index.php?year_begin=2014";

        $display_block .= "<b>Updates to the project area database closes at $cldate</b>";

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
        $num = $results->fetch();
        if ($num < 1) {
            //no records
            $display_block .= '<p><em>Sorry, there are no records to select!</em></p>';
        } else {
            //has records
            $rows = $results->fetchAll();
            foreach ($rows as $recs) {
                $title = $recs['title'];
                $firstname = $recs['firstname'];
                $familyname = $recs['familyname'];
                $fullname = $recs['fullname'];
                $staffid = $recs['staffid'];
                $staffemail = $recs['email'];
                $inactive = $recs['inactive'];

                $email_fullname = "$firstname, $lb$lb";

                // if not actvie email all staff (i.e. make all staff active
                if ($activestaff != 'active') {
                    $inactive = 0;
                }

                if ($inactive == 0) {
                    if ($messagetype != 'free') {
                        $query = "select * from $master_name where f_supervisor = '$fullname'";
                        try {
                            $results = $db->query($query);
                        } catch (PDOException $ex) {
                            error_log($ex->getMessage());
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";

                            exit();
                        }
                        //$get_list_res2 = mysql_query($get_list2) or die(" Unable to get projects from $master_name table:<BR> query = $get_list2 <BR>");
                        $email_block = '';

                        $num = $results->rowCount();

                        if ($num < 1) {
                            //$display_block .= "$sp$sp no projects$lb";
                            $email_block .= "<B>You currently have no projects in the database.</B>$lb";
                        } else {
                            $email_block .= "<B>This is the list of your projects that are in the database now:$lb</B>";
                            $rows = $results->fetchAll();
                            foreach ($rows as $recs2) {
                                $master_id = $recs2['id'];
                                $project_title = $recs2['f_title'];
                                $email_block .= "$sp$sp$master_id: $project_title $lb";
                            }
                        }
                        $email_block .= "$lb To edit projects or add-to/delete one go to the link shown and click on 'Add' or 'View/Edit/Delete':$lb$lb";
                        $email_full = $email_fullname;
                        $email_full .= nl2br($email_body_text);
                        $email_full .= $email_block;
                        //$email_full .= "<A href='$link'>$link<A>$lb$lb";
                        $email_full .= "Project entry needs to be completed by: $cldate $lb$lb";
                        //$email_full .= "Andy";
                        $email_full .= $this_staff_full_name;
                    } else {
                        $email_full = $email_fullname;
                        $email_full .= nl2br($email_body_text);
                    }

                    $display_block .= "$firstname $familyname$lb$lb";
                    $display_block .= ($email_full);
                    $display_block .= "<HR>$lb";

                    $from = $this_staff_username . $local_email_domain;
                    $to = $staffemail;
                    //$to = $from;
                    if ($subjectline == '') {
                        $subject = 'Level 3 / 5 Project email';
                    } else {
                        $subject = $subjectline;
                    }
                    if ($reallysend == null) {
                        $display_block .= "<em>NOT SENT: $subject to $to<HR><BR></em>";
                    } else {
                        $ok = EmailBodyStringPHPMailer($to, $from, $subject, $email_full);
                        if ($ok) {
                            $display_block .= "<em>Sent: $subject to $to<BR></em>";
                        } else {
                            $display_block .= "<em style='color: red;'>FAILED to send: $subject to $to (see the PHP error log for the reason)<BR></em>";
                        }
                    }
                } else {
                    // Not participating in projects this year.
                    $display_block .= "$firstname $familyname. $lb Not offering projects this year.$lb$lb<HR>$lb";
                }
            }
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
        <span class="navbar-brand">Edit questions and test marks scheme</span>
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
