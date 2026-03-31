<?php
// connect to the datbase
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");

(@include_once("./assess_table_template.php")) OR die("Cannot include assess_table_template.php<BR>");
(@include_once("./assess_total_table_template.php")) OR die("Cannot include assess_total_table_template.php<BR>");
(@include_once("./allocated_table_template.php")) OR die("Cannot include allocated_table_template.php<BR>");
(@include_once("./students_table_template.php")) OR die("Cannot include students_table_template.php<BR>");
(@include_once("./template_functions_pdo.php")) OR die("Cannot include template_functions.php<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot include staff_functions.php<BR>");

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
//$access = false;
//$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
//$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

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
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if($is_module_admin || $admin_access) {
// if the form was submitted then enter it all into the database
    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }

    if (!isset($_REQUEST['pm_id'])) {
        $_REQUEST['pm_id'] = null;
    }
    $pm_id = $_REQUEST['pm_id'];

    $original_referer = "";
    if (isset($_REQUEST['referer'])) {
        $original_referer = $_REQUEST['referer'];
    }
    if(array_key_exists('HTTP_REFERER', $_SERVER)){
        if ($_SERVER['PHP_SELF'] != $_SERVER['HTTP_REFERER']) {
            $original_referer = $_SERVER['HTTP_REFERER'];
        }
    }
// see all the POST values
//foreach ($_POST as $key => $value){
//}
    if ($POST_OP == "modify") {
        $display_block .= "<h2>Updating the project data $module_prefix$module1$module_postfix ($short_code) $year_begin ...</h2>";
        $module1 = trim($_POST['module1_code']);
        $module_prefix = trim($_POST['module_prefix']);
        $module_postfix = trim($_POST['module_postfix']);
        $module_short_description = trim($_POST['module_short_desc']);

        $staff_close = trim($_POST['staff_close_date']);
        $sel_open = trim($_POST['student_selection_open_date']);
        $sel_close = trim($_POST['student_selection_close_date']);
        $alloc_date = trim($_POST['allocation_date']);

        $num_parts = trim($_POST['num_parts']);
        $accept_var = trim($_POST['accept_var']);
        $pass_mark = trim($_POST['pass_mark']);
        $module_leader_id = trim($_POST['staff_id']);
        $module_leader_fullname = GetStaffNameFromID($db, $module_leader_id, $staff_names2, 0);
        $min_feedback_words = trim($_POST['min_feedback_words']);

        $query = "update $projects_setup set module='$module1', module_prefix='$module_prefix',  module_postfix='$module_postfix',
            module_short_desc='$module_short_description',
            staff_close='$staff_close', sel_open='$sel_open', sel_close='$sel_close', alloc_date='$alloc_date', num_parts='$num_parts',
            accept_var='$accept_var', pass_mark='$pass_mark', module_leader='$module_leader_fullname', min_feedback_words='$min_feedback_words'
             WHERE id='$pm_id'";
        //mysql_query($action) or die(" Unable to update  project entry to the table $projects_setup. <BR /> Query = $action");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        for ($part = 1; $part <= $num_parts; $part++) {
            $p_submit = "p$part" . "_submission_date";
            $submit_dates_part = $_POST[$p_submit];
            $p_marking = "p$part" . "_marking_date";
            $markby_dates_part = $_POST[$p_marking];

            $p_use = "use_p$part";
            $p_percent = "p$part" . "_percent";
            $weights_part = $_POST[$p_percent];
            $p_num_markers = "p$part" . "_num_markers";
            $num_markers_part = $_POST[$p_num_markers];
            $p_max = "p$part" . "_max";
            $max_marks_part = $_POST[$p_max];
            $p_is_numeric = "p$part" . "_numeric";
            $is_numeric_part = 0;
            if (isset($_POST[$p_is_numeric])) {
                $is_numeric_part = 1;
            }
            $p_desc = "p$part" . "_desc";
            $descs_part = $_POST[$p_desc];

            $p_show_feedback_id = "p$part" . "_feedback";
            $show_part_feedback_flag = 0;
            if (isset($_POST[$p_show_feedback_id])) {
                $show_part_feedback_flag = 1;
            }

            $query = "ALTER TABLE $projects_setup ADD `$p_show_feedback_id` int DEFAULT 0";
            if(!DoesColumnExistInTable($db,$projects_setup,$p_show_feedback_id)) {
                $db->query($query);
            } else {
            }
            //$name = "p$part"."_template";
            //$template_tables[$part] = $_POST[$name];
            $query = "update  $projects_setup set $p_submit='$submit_dates_part', $p_marking='$markby_dates_part', $p_use='1', $p_percent='$weights_part',
                        $p_num_markers='$num_markers_part', $p_max='$max_marks_part', $p_desc='$descs_part', $p_is_numeric='$is_numeric_part', $p_show_feedback_id='$show_part_feedback_flag'  WHERE id='$pm_id'";
            //mysql_query($action) or die(" Unable to update project entry to the table $projects_setup. <BR /> Query = $action");
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

        // Update the project choice parameters
        $choice_number = trim($_POST['choice_number']);
        $ranked_choice_int = 0;
        if (isset($_POST['ranked_choice'])) {
            $ranked_choice_int = 1;
        }
        // default this on
        $use_project_selection_int = 1;
        if (!isset($_POST['use_project_selection'])) {
            $use_project_selection_int = 0;
        }
        $use_project_area_int = 0;
        if (isset($_POST['use_project_area'])) {
            $use_project_area_int = 1;
        }

        $query = "update  $projects_setup set ranked_choice='$ranked_choice_int', choice_number='$choice_number', use_project_area='$use_project_area_int',  use_project_selection='$use_project_selection_int' WHERE id='$pm_id'";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

        // Refresh the project_setup data for display below
        $query = "select * from $projects_setup where id=$pm_id";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        //$get_setup_list = mysql_query($get_setup);// or die(" Unable to get database configuration.");
        //if (mysql_errno() != 0) {
        //   $errornum = mysql_errno();
        //   $errormessage = mysql_error();
        //   printf("ERROR: $errornum<br>\n$errormessage<BR>\n$get_setup\n");
        //   exit;
        //}

        //$setup_res = mysql_fetch_array($get_setup_list);
        $setup_res = $results->fetch();
        $module1 = $setup_res['module'];
        $module_prefix = $setup_res['module_prefix'];
        $module_postfix = $setup_res['module_postfix'];
        $module_short_description = $setup_res['module_short_desc'];
        $short_code = $setup_res['short_code'];
        $year_begin = $setup_res['year_begin'];
        $year_end = $setup_res['year_end'];
        $short_code = $setup_res['short_code'];

        $staff_close = $setup_res['staff_close'];
        $sel_open = $setup_res['sel_open'];
        $sel_close = $setup_res['sel_close'];
        $alloc_date = $setup_res['alloc_date'];

        $num_parts = $setup_res['num_parts'];
        $accept_var = $setup_res['accept_var'];
        $pass_mark = $setup_res['pass_mark'];
        $module_leader_fullname = $setup_res['module_leader'];
        $min_feedback_words = $setup_res['min_feedback_words'];

        $ranked_choice_int = $setup_res['ranked_choice'];
        $ranked_choice = true;
        if($ranked_choice_int != 1){
            $ranked_choice = false;
        }
        $choice_number = $setup_res['choice_number'];

        $use_project_area_int = $setup_res['use_project_area'];
        $use_project_area = true;
        if($use_project_area_int != 1){
            $use_project_area = false;
        }
        $use_project_selection_int = $setup_res['use_project_selection'];
        $use_project_selection = true;
        if($use_project_selection_int != 1){
            $use_project_selection = false;
        }

        $p_submission_date = array();
        $p_marking_date = array();
        $p_num_markers = array();
        $p_use = array();
        $p_percent = array();
        $p_max = array();
        $p_description = array();
        $p_numeric = array();
        $p_show_feedback = array();

        for ($i = 1; $i <= 5; $i++) {
            $submissin_date = "p$i" . "_submission_date";
            $marking_date = "p$i" . "_marking_date";
            $num_markers = "p$i" . "_num_markers";
            $use = "use_p$i";
            $percent = "p$i" . "_percent";
            $max = "p$i" . "_max";
            $desc = "p$i" . "_desc";
            $p_is_numeric = "p$i" . "_numeric";
            $p_show_feedback_id = "p$i" . "_feedback";

            $p_submission_date[$i] = $setup_res[$submissin_date];
            $p_marking_date[$i] = $setup_res[$marking_date];
            $p_num_markers[$i] = $setup_res[$num_markers];
            $p_use[$i] = $setup_res[$use];
            $p_percent[$i] = $setup_res[$percent];
            $p_max[$i] = $setup_res[$max];
            $p_description[$i] = $setup_res[$desc];
            $p_numeric[$i] = $setup_res[$p_is_numeric];
            $p_show_feedback[$i] = $setup_res[$p_show_feedback_id];
        }

        // --------------------------------------------------------
        // Get the project associated programmes
        $name = "p_list$i";
        $num_progs_list_max = 7;
        $prog_short_name_string = "";
        $sep = ";";
        for ($i = 1; $i <= $num_progs_list_max; $i++) {
            $prog_short_name_id = "p_list$i";
            $prog_short_name = trim($_POST[$prog_short_name_id]);
            if ($prog_short_name == "") {
                $num_progs_list = $i - 1;
                break;
            } else {
                if ($i == 1) {
                    $prog_short_name_string = $prog_short_name;
                } else {
                    $prog_short_name_string .= ($sep . $prog_short_name);
                }
                $num_progs_list = $i;
            }
        }

        if ($num_progs_list > 0) {
            $query = "update  $projects_setup_proposals set num_progs='$num_progs_list', prog_short_names='$prog_short_name_string' WHERE proj_id='$pm_id'";

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

        // --------------------------------------------------------
        // Get the assessment criteria check boxes
        $query = "update  $assessment_criteria_flags_table set ";

        $name = "ac_visible";
        $db_name = "visible_flag";
        $is_checked = 0;
        if (isset($_POST[$name])) {
            $is_checked = 1;
        }
        $query .= "$db_name='$is_checked' ";
        $query .= ", ";

        $name = "ac_grade";
        $db_name = "grade_text_flag";
        $is_checked = 0;
        if (isset($_POST[$name])) {
            $is_checked = 1;
        }
        $query .= "$db_name='$is_checked' ";
        $query .= ", ";

        $name = "ac_class";
        $db_name = "class_flag";
        $is_checked = 0;
        if (isset($_POST[$name])) {
            $is_checked = 1;
        }
        $query .= "$db_name='$is_checked' ";
        $query .= ", ";

        $name = "ac_gpa";
        $db_name = "gpa_flag";
        $is_checked = 0;
        if (isset($_POST[$name])) {
            $is_checked = 1;
        }
        $query .= "$db_name='$is_checked' ";
        $query .= ", ";

        $name = "ac_university";
        $db_name = "descriptor_university_flag";
        $is_checked = 0;
        if (isset($_POST[$name])) {
            $is_checked = 1;
        }
        $query .= "$db_name='$is_checked' ";
        $query .= ", ";

        $name = "ac_school";
        $db_name = "descriptor_school_flag";
        $is_checked = 0;
        if (isset($_POST[$name])) {
            $is_checked = 1;
        }
        $query .= "$db_name='$is_checked' ";

        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        // --------------------------------------------------------
        // Get the optional info to capture
        $query = "update  $projects_setup_proposals set ";
        for ($i = 0; $i < sizeof($option); $i++) {
            $name = 'i' . $option[$i];
            $fname = 'f' . $option[$i];
            $vname = 'v' . $option[$i];
            $mname = 'm' . $option[$i];
            $is_checked = 0;
            $is_checked_v = 0;
            $is_checked_m = 0;
            if (isset($_POST[$name])) {
                $is_checked = 1;
            }
            if (isset($_POST[$vname])) {
                $is_checked_v = 1;
            }
            if (isset($_POST[$mname])) {
                $is_checked_m = 1;
            }

            $query .= "$name='$is_checked' ";
            $query .= ", ";

            $query .= "$mname='$is_checked_m' ";
            $query .= ", ";

            $query .= "$vname='$is_checked_v' ";
            if ($i + 1 != sizeof($option)) {
                $query .= ", ";
            }

        }

        $query .= " WHERE proj_id='$pm_id'";

        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        // --------------------------------------------------------

        $display_block .= "<h2> ... Done.</h2>";
        $display_block .= "<HR>";

    }// end of if ($POST_OP == "select")

// Display the dropdowns to select the module and year
    $display_block .= "<h1>Modify module entry : <span id='headline'>$module_short_description - $module_prefix$module1$module_postfix.</span> Starting year <span id='headline'>$year_begin</span></h1>";

    $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

    $display_block .= "<table class='tableSimple m-3'>\n";
    $display_block .= "<tr><td colspan='2'><H2>Some project/module details</H2></td></tr>";
    $display_block .= "<tr><td>Module ID</td><td>$pm_id</td></tr>\n";
    $display_block .= "<tr><td>Year begin</td><td>$year_begin</td></tr>\n";
    $display_block .= "<tr><td>Year end</td><td>$year_end</td></tr>\n";
    $display_block .= "<tr><td>Module code</td><td>\n";
    $display_block .= "<input type='text' id='module_prefix' name='module_prefix' value='$module_prefix' size='5' maxlength='5'>\n";
    $display_block .= "<input type='text' id='module1_code' name='module1_code' value='$module1' size='4' maxlength='4'>\n";
    $display_block .= "<input type='text' id='module_postfix' name='module_postfix' value='$module_postfix' size='1' maxlength=1>\n";
    $display_block .= "</td></tr>\n";
    $display_block .= "<tr><td>Module short name</td><td>$short_code</td></tr>\n";
    $display_block .= "<tr><td>Short description</td><td><input type='text' id='module_short_desc' name='module_short_desc' value='$module_short_description' size='25'> (Up to 25 characters, not blank)</td></tr>\n";

    $display_block .= "<tr><td colspan='2'><H2>Some key dates:</H2></td></tr>";

    $name = "staff_close_date";
    $display_block .= "<TR><TD>Project proposal date close for staff</td><td><input type='text' id='$name' name='$name'  value='$staff_close'size=20 maxlength=15> at time 23:59</td></tr>\n";
    $name = "student_selection_open_date";
    $display_block .= "<TR><TD>Student selection opens</td><td><input type='text' id='$name' name='$name'  value='$sel_open'size=20 maxlength=15> at time 00:00</td></tr>\n";
    $name = "student_selection_close_date";
    $display_block .= "<TR><TD>Student selection closes</td><td><input type='text' id='$name' name='$name'  value='$sel_close'size=20 maxlength=15> at time 23:59</td></tr>\n";
    $name = "allocation_date";
    $display_block .= "<TR><TD>Project to be allocated by</td><td><input type='text' id='$name' name='$name'  value='$alloc_date'size=20 maxlength=15> at time 00:00</td></tr>\n";

    $display_block .= "<tr><td colspan='2'><H2>Assessment Parts:</H2></td></tr>";

    $display_block .= "<tr><td>Number of assessment parts: </td><td><input type='number' name='num_parts' value='$num_parts' min='1' max='5' size=3 maxlength=3></td></tr>\n";
    $display_block .= "<tr><td>Module leader:  </td><td>" . GetStaffSelectID($db, $staff_names2, "staff_id", GetStaffIDFromName($db, $module_leader_fullname, $staff_names2)) . "</td></tr>\n";
    $display_block .= "<tr><td>Acceptable variation between markers : </td><td><input type='number' name='accept_var' value='$accept_var' min='0' max='100' size='3'> %</td></tr>\n";
    $display_block .= "<tr><td>Pass mark:  </td><td><input type='number' name='pass_mark' value='$pass_mark' min='0' max='100' size='3'> %</td></tr>\n";
    $display_block .= "<tr><td>Minimum number of feedback words:  </td><td><input type='number' name='min_feedback_words' value='$min_feedback_words' min='0' max='100' size='3'></td></tr>\n";
    $display_block .= "</table>\n";

    for ($part = 1; $part <= $num_parts; $part++) {
        $i = $part;
        $display_block .= "<table class='tableSimple m-3'>\n";
        $display_block .= "<TR><TD colspan='2'><H3>Part $part:</H3></td></tr>\n";

        $name = "p$part" . "_desc";
        $desc = $p_description[$i];//$_POST[$name];
        $display_block .= "<TR><TD>Description of assesment part $part: </td><td><input type='text' name='$name'  value='$desc'size=25 maxlength=25></td></tr>\n";
        //$name = "p$part"."_template";
        //$display_block .="<P> Select a questions template for  part $part: " . GetQsTemplatesSelect($name,$template_tables[$part]) ."</P>";

        $name = "p$part" . "_num_markers";
        $num_markers = $p_num_markers[$i];//$_POST[$name];
        $display_block .= "<TR><TD>Number of markers for part $part: </td><td><input type='number' name='$name' value='$num_markers' min='1' max='3' size=6 maxlength=5></td></tr>\n";

        $name = "p$part" . "_percent";
        $weight = $p_percent[$i];//$_POST[$name];;
        $display_block .= "<TR><TD>% weight of part $part: </td><td><input type='number' name='$name' value='$weight' min='0' max='100' size=6 maxlength=5></td></tr>\n";

        $name = "p$part" . "_max";
        $max_mark = $p_max[$i];//$_POST[$name];
        $display_block .= "<TR><TD>Maximum mark for part $part: </td><td><input type='number' name='$name' value='$max_mark' min='1' max='100' size=6 maxlength=5></td></tr>\n";

        $name = "p$part" . "_numeric";
        $is_numeric = $p_numeric[$i];//$_POST[$name];
        $is_checked = "";
        if ($is_numeric == 1) {
            $is_checked = "checked";
        }
        $display_block .= "<TR><TD>Numeric (checked) or phrases (not checked) response format for part $part:<BR>(Questions format must match this choice.) </td><td><input type='checkbox' name='$name' value='$is_numeric' $is_checked></td></tr>\n";

        $name = "p$part" . "_feedback";
        $show_part_feedback_flag = $p_show_feedback[$i];
        $is_checked = "";
        if ($show_part_feedback_flag == 1) {
            $is_checked = "checked";
        }
        $display_block .= "<TR><TD>Collect overall feedback for part $part (as well, or instead of, section feedback):<BR>(Checked means show the feedback box on the marking page.) </td><td><input type='checkbox' name='$name' value='$name' $is_checked></td></tr>\n";

        $name = "p$part" . "_submission_date";
        $date = $p_submission_date[$i];//$_POST[$name];
        $display_block .= "<TR><TD>Submission date for part $part: </td><td><input type='text' id='$name' name='$name'  value='$date'size=20 maxlength=15></td></tr>\n";

        $name = "p$part" . "_marking_date";
        $date = $p_marking_date[$i];//$_POST[$name];
        $display_block .= "<TR><TD>Mark-by date for part $part: </td><td><input type='text' id='$name' name='$name'  value='$date'size=20 maxlength=15></td></tr>\n";
        $display_block .= "</table>\n";
    }
    //------------------------------------------------------------
// Assessment criteria visibility
// Which part if any of the Assessment criteria should be shown on the assessment pages for staff to see
    $query = "select * from $assessment_criteria_flags_table where `id`=0";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $recs = $results->fetch();
    $ac_visible = $recs['visible_flag'];
    $ac_class = $recs['class_flag']; // class, Excellent 1st etc
    $ac_grade = $recs['grade_text_flag']; // Grade, A+ etc
    $ac_gpa = $recs['gpa_flag']; // GPA , MAx = 4.25
    $ac_university = $recs['descriptor_university_flag']; // university descriptor
    $ac_school = $recs['descriptor_school_flag']; // School descriptor

    $display_block .= "<table class='tableSimple m-3' style='width:400px'>\n";
    $display_block .= "<TR><TD colspan='2'><H3>Assessment Criteria Visibility to Staff</H3></td></tr>\n";
    $display_block .= "<TR><TD colspan='2'>These settings control whether all or any of the various Assessment Criteria are shown to staff to help them judge whether the mark given is appropriate</td></tr>\n";
    $display_block .= "<TR><TD>Which element</td><TD>Visible = checked</td></tr>\n";

    $name = "ac_visible";
    $option_value = $ac_visible;
    $is_checked = "";
    if ($option_value > 0) {
        $is_checked = "checked";
    }
    $display_block .= "<TR><td>Show any checked below<BR>(This <b>must</b> be checkd to show anything) </td><td><input type='checkbox' name='$name' value='$option_value' $is_checked></td></tr>\n";

    $name = "ac_university";
    $option_value = $ac_university;
    $is_checked = "";
    if ($option_value > 0) {
        $is_checked = "checked";
    }$display_block .= "<TR><td>Show the University text descriptor</td><td><input type='checkbox' name='$name' value='$option_value' $is_checked></td></tr>\n";

    $name = "ac_school";
    $option_value = $ac_school;
    $is_checked = "";
    if ($option_value > 0) {
        $is_checked = "checked";
    }$display_block .= "<TR><td>Show the School specific text descriptor</td><td><input type='checkbox' name='$name' value='$option_value' $is_checked></td></tr>\n";

    $name = "ac_class";
    $option_value = $ac_class;
    $is_checked = "";
    if ($option_value > 0) {
        $is_checked = "checked";
    }    $display_block .= "<TR><td>Show the Class (1st, 2.ii etc.)</td><td><input type='checkbox' name='$name' value='$option_value' $is_checked></td></tr>\n";

    $name = "ac_grade";
    $option_value = $ac_grade;
    $is_checked = "";
    if ($option_value > 0) {
        $is_checked = "checked";
    }$display_block .= "<TR><td>Show the Grade (A+, B- etc.)</td><td><input type='checkbox' name='$name' value='$option_value' $is_checked></td></tr>\n";

    $name = "ac_gpa";
    $option_value = $ac_gpa;
    $is_checked = "";
    if ($option_value > 0) {
        $is_checked = "checked";
    }$display_block .= "<TR><td>Show the GPA (Max = 4.25)</td><td><input type='checkbox' name='$name' value='$option_value' $is_checked></td></tr>\n";

    $display_block .= "</table>\n";

//------------------------------------------------------------
// Program variant options
// Can have up to 7 Variants
// Get the number for this prog from the database
    $query = "SELECT * FROM $projects_setup_proposals WHERE proj_id='$pm_id'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo("Unable to update data to the table $projects_setup_proposals<BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $num = $results->rowCount();
    $prog_list_count = 0;
    if ($num > 0) {
        $recs = $results->fetch();
        $num_progs = $recs['num_progs'];
        $prog_short_names_string = $recs['prog_short_names'];
        $prog_list = [];
        if($prog_short_names_string){
            $prog_list = explode(";", $prog_short_names_string);
        }
        $prog_list_count = sizeof($prog_list);
    }
    $display_block .= "<table class='tableSimple m-3'>\n";
    $display_block .= "<TR><TD colspan='2'><H3>Programme Variants</H3></td></tr>\n";
    $display_block .= "<TR><TD colspan='2'>Use short names, e.g. Arch, Aero, Civil & Struct, Chem Eng. <BR> And often you won't need to use any.</td></tr>\n";
    $num_parts = 7;
    for ($part = 1; $part <= $num_parts; $part++) {
        $i = $part;
        if ($i <= $prog_list_count) {
            $prog_short_name = $prog_list[$i - 1];
        } else {
            $prog_short_name = "";
        }
        $name = "p_list$i";
        $display_block .= "<TR><td>Prog option $i</td><td><input type='text' name='$name' value='$prog_short_name' size=30 maxlength=30></td></tr>\n";
    }
    $display_block .= "</table>\n";

//------------------------------------------------------------------------

    $display_block .= "<table class='tableSimple m-3'>\n";
    $display_block .= "<TR><TD colspan='4'><H3>Extra information to capture</H3></td></tr>\n";
    $display_block .= "<TR><TD colspan='4'>This might be used by the module manager to organise resources<BR>It may also be useful to the students.</td></tr>\n";
    $display_block .= "<TR><TD>&nbsp;</td><TD>Visible</td><TD>Mandatory for staff</td><TD>Students see it</td></tr>\n";
    for ($i = 0; $i < sizeof($option); $i++) {
        $option_label = $option_text[$i];

        //----- Visible at all
        $name = 'i' . $option[$i];
        $option_value = $recs[$name];
        $is_checked = "";
        if ($option_value > 0) {
            $is_checked = "checked";
        }

        //----- Visibility to students
        $name_vis = 'v' . $option[$i];
        $option_value_vis = $recs[$name_vis];
        $is_checked_vis = "";
        if ($option_value_vis > 0) {
            $is_checked_vis = "checked";
        }

        //----- Mandatory to complete
        $name_mand = 'm' . $option[$i];
        $option_value_mand = $recs[$name_mand];
        $is_checked_mand = "";
        if ($option_value_mand > 0) {
            $is_checked_mand = "checked";
        }

        $display_block .= "<TR><td>$option_label</td>\n";
        $display_block .= "    <td style='text-align: center'><input type='checkbox' name='$name' value='$option_value' $is_checked></td>\n";
        $display_block .= "    <td style='text-align: center'><input type='checkbox' name='$name_mand' value='$option_value_mand' $is_checked_mand></td>\n";
        $display_block .= "    <td style='text-align: center'><input type='checkbox' name='$name_vis' value='$option_value_vis' $is_checked_vis></td>\n";
        $display_block .= "</tr>\n";

    }
    $display_block .= "</table>\n";

//------------------------------------------------------------------------
// Info on what and how the students select
    // Refresh the project_setup data for display below
    $query = "select * from $projects_setup where id=$pm_id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }

    $setup_res = $results->fetch();

    // get theses two from the DB
    //$ranked_choice = true;
    //$choice_number = 3;
    $ranked_choice_int = $setup_res['ranked_choice'];
    $ranked_choice = true;
    if($ranked_choice_int != 1){
        $ranked_choice = false;
    }
    $choice_number = $setup_res['choice_number'];

    $display_block .= "<table class='tableSimple m-3'>\n";
    $display_block .= "<TR><TD colspan='2'><H3>Project/Area Selection settings</H3></td></tr>\n";
    $display_block .= "<TR><TD colspan='2'>These setting determine how we want students to select projects.</td></tr>\n";
    $display_block .= "<TR><TD>Description</td><TD>Value</td></tr>\n";

    $ranked_checked = "";
    $ranked_value = 0;
    if($ranked_choice){
        $ranked_checked = "checked";
        $ranked_value = 1;
    }
    $use_project_selection_checked = "";
    $use_project_selection_value = 0;
    $use_project_selection_int = $setup_res['use_project_selection'];
    if($use_project_selection_int){
        $use_project_selection_checked = "checked";
        $use_project_selection_value = 1;
    }
    $use_project_area_checked = "";
    $use_project_area_value = 0;
    $use_project_area_int = $setup_res['use_project_area'];
    if($use_project_area_int){
        $use_project_area_checked = "checked";
        $use_project_area_value = 1;
    }
    $display_block .= "<TR><TD>Use <strong>Project Selection</strong> selection?</td><td><input type='checkbox' name='use_project_selection' id='use_project_selection' value='$use_project_selection_value' $use_project_selection_checked></td>\n</tr>\n";
    $display_block .= "<TR><TD>Use project <strong>Area</strong> selection? (not yet functional)</td><td><input type='checkbox' name='use_project_area' id='use_project_area' value='$use_project_area_value' $use_project_area_checked></td>\n</tr>\n";

    $display_block .= "<TR><TD colspan='2'><strong>Settings for Project Selection</strong></td>\n</tr>\n";

    $display_block .= "<TR><TD>Ranked choice of project?</td><td><input type='checkbox' name='ranked_choice' id='ranked_choice' value='$ranked_value' $ranked_checked></td>\n</tr>\n";
    $display_block .= "<TR><TD>Number of choices of project</td><td><input type='number' name='choice_number' id='choice_number' value='$choice_number' min='1' max='5' size=4 maxlength=5></td></tr>\n";

    $display_block .= "</table>\n";
//------------------------------------------------------------------------

    $display_block .= "\n<input type='hidden' name='referer' value='$original_referer'>";
    $display_block .= "\n<input type='hidden' name='op' value='modify'>";
    $display_block .= "<p><input class='btn btn-primary' type='submit' id='submitbutton' name='submit' value='Submit the modified values for $module_prefix$module1$module_postfix, $year_begin '></p>\n";

    $display_block .= "</form>\n";

} else {
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href='../index.php'>Go back here</A><BR>";
}
?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Edit module entry - <?php echo("$module_short_description ($short_code) $module_prefix$module1$module_postfix $year_begin ") ?></span>
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
