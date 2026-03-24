<?php
// Turn error reportiing on/off
//error_reporting(E_ALL);
//ini_set('display_errors', '1');
//error_reporting(0);
//ini_set('display_errors', '0');

//(@include_once("./setup_titles.php")) OR die("Cannot open setup_titles.php<BR>");
$STAFF_ROOT = getenv('BASE_DIR') . "/staff";
$STAFF_ROOT = $STAFF_DIR_VAL;
(@include_once("$STAFF_ROOT/staff_functions.php")) OR die("Cannot open $STAFF_ROOT/staff_functions.php from setup_tables<BR>");
(@include_once("$STAFF_ROOT/table_functions.php")) OR die("Cannot open $STAFF_ROOT/table_functions.php from setup_tables<BR>");
(@include_once("$STAFF_ROOT/markstore_functions.php")) OR die("Cannot open $STAFF_ROOT/markstore_functions.php from setup_tables<BR>");

$projects_setup = "projects_setup";
$projects_setup_proposals = $projects_setup . "_proposals" ;

$dash_projects_setup = "dash_" . $projects_setup;

$assessment_criteria_table = "assessment_criteria";
$assessment_criteria_flags_table = "assessment_criteria_flags";

$header_images_table = "header_images";

// get the header image from the db
create_header_images_table_structure($db);
$query = "SELECT * FROM $header_images_table WHERE id='$header_image_id'";
$results = $db->query($query);
$recs = $results->fetch();
if($recs){
    $header_image_base64 = $recs['header_image'];
}else{
    $header_image_base64 = "";
}

$header_images[0] = "leeds_generic_header.png";
$header_images[1] = "leeds_civil_header.png";
$header_images[2] = "leeds_eng_header.png";
$header_images[3] = "leeds_generic_header.png";
$header_images[4] = "leeds_mech_eng_header.png";
$header_images[5] = "leeds_scape_header.png";
$header_images[6] = "leeds_swjtu_joint_school_header.png";
$header_images[7] = "leeds_transport_header.png";

if (!DoesTableExist($db, $projects_setup)) {
   create_projects_setup_table_structure($db);
}

// Makesure dash projects setup has been created
// Check the table exists
if (DoesTableExist($db, $dash_projects_setup)) {
    update_dash_projects_setup_tables($db, $dash_projects_setup);
}else{
    create_dash_projects_setup_table($db, $dash_projects_setup);
}

// Get the feedback_record table
// Create it is if does not exist
$query = "SHOW TABLES LIKE '$assessment_criteria_table'";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    $this_file = __FILE__;
    $this_line = __LINE__;
    echo "An Error occured accessing the database in function: $this_function<BR>\n";
    echo "File: $this_file  near line: $this_line<BR>\n";
    echo(" Query = $query<BR> \n");
    echo(" Err message: " . $ex->getMessage() . "<BR>\n");

    exit();
}
$num_rows = $results->rowCount();
if($num_rows == 0){
    // No table so create it
    create_assessment_criteria_tables($db);
}


if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];

// We need to do something should the module not be specified
// Take the first (last?) entry in the  $project_setup table
if ($pm_id == "") {
    // Get last entry SELECT * FROM mytable ORDER BY id DESC LIMIT 1
    $query = "SELECT * FROM $projects_setup ORDER BY id DESC LIMIT 1";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        $this_file = __FILE__;
        $this_line = __LINE__;
        echo "An Error occured accessing the database in function: $this_function<BR>\n";
        echo "File: $this_file  near line: $this_line<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $result_data = $results->fetch();
    if($result_data){
        $module1 = $result_data['module'];
        $year_begin = $result_data['year_begin'];
        $pm_id = $result_data['id'];
    }
    if($pm_id == ""){
        echo("The query for project id did not work. <BR>You probably should be running the function for first setup.<BR>");
        echo("This is the query that did not return anything: <BR>$query.");
        echo("<BR>The database: $dbName.");
        echo("<BR>The username: $dbusername.");
    }
}

// if $pm_id is still not defined then it's probably on first setup so jump  out
if ($pm_id != "") {
    $query = "SELECT * FROM $projects_setup WHERE id=$pm_id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        $this_file = __FILE__;
        $this_line = __LINE__;
        echo "An Error occured accessing the database in function: $this_function<BR>\n";
        echo "File: $this_file  near line: $this_line<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }

    $setup_res = $results->fetch();

    $module1 = $setup_res['module'];
    $module_prefix = $setup_res['module_prefix'];
    $module_postfix = $setup_res['module_postfix'];
    $module_short_description = $setup_res['module_short_desc'];
    $short_code = $setup_res['short_code'];
    $year_begin = $setup_res['year_begin'];
    $year_end = $setup_res['year_end'];
    $year_end_short = $year_end - 2000;
    $short_code_lc = strtolower($short_code);

    $staff_close = $setup_res['staff_close'];
    $sel_open = $setup_res['sel_open'];
    $sel_close = $setup_res['sel_close'];
    $alloc_date = $setup_res['alloc_date'];

    $num_parts = $setup_res['num_parts'];
    $accept_var = $setup_res['accept_var'];
    $pass_mark = $setup_res['pass_mark'];
    $module_leader_fullname = $setup_res['module_leader'];
    $min_feedback_words = $setup_res['min_feedback_words'];

    $p_submission_date = array();
    $p_marking_date = array();
    $p_use = array();
    $p_percent = array();
    $p_max = array();
    $p_description = array();
    $p_numeric = array();
    $p_show_feedback = array();

// Some limits on the system
// Questions
    $g_max_num_cats = 5; //  = max num sections ina ny one set of questions for a part
    $g_max_num_questions_per_cat = 10;
    $g_max_num_responses_per_question = 11;
// Parts and markers
    $g_max_num_parts = 5;
    $g_max_num_markers = 3;

    for ($i = 1; $i <= 5; $i++) {
        $submissin_date = "p$i" . "_submission_date";
        $marking_date = "p$i" . "_marking_date";
        $num_markers = "p$i" . "_num_markers";
        $use = "use_p$i";
        $percent = "p$i" . "_percent";
        $max = "p$i" . "_max";
        $desc = "p$i" . "_desc";
        $numeric = "p$i" . "_numeric";
        $numeric = "p$i" . "_numeric";
        $show_feedback_id = "p$i" . "_feedback";

        $p_submission_date[$i] = $setup_res[$submissin_date];
        $p_marking_date[$i] = $setup_res[$marking_date];
        $p_num_markers[$i] = $setup_res[$num_markers];
        $p_use[$i] = $setup_res[$use];
        $p_percent[$i] = $setup_res[$percent];
        $p_max[$i] = $setup_res[$max];
        $p_description[$i] = $setup_res[$desc];
        $p_numeric[$i] = $setup_res[$numeric];
        $p_show_feedback[$i] = $setup_res[$show_feedback_id];
    }

    $prefix = "";

// Students and their allocations
    $students = $prefix . "students_" . $short_code_lc . "_" . $year_begin;
    $students_programmes = $prefix . "students_programmes_" . $short_code_lc . "_" . $year_begin;
    $selection = $prefix . "select_" . $short_code_lc . "_" . $year_begin;
    $project_alloc = $prefix . "alloc_" . $short_code_lc . "_" . $year_begin;
    $project_allocated = $prefix . "allocated_" . $short_code_lc . "_" . $year_begin;
    $project_allocated_staff = $prefix . "allocated_" . $short_code_lc . "_" . $year_begin . "_staff";
    $project_allocated_staff_all = $prefix . "allocated_" . "all" . "_" . $year_begin . "_staff";

// Report mark stores
// Assessment categories and questions
    $markstores = array(array());
    for ($i = 1; $i <= 5; $i++) {
        for ($j = 1; $j <= 3; $j++) {
            $markstores[$i][$j] = $prefix . "assess_" . $short_code_lc . "_" . $year_end . "_p$i" . "_m$j";
        }
        $assessqs_cats[$i] = $prefix . "assessqs_cats_" . $short_code_lc . "_" . $year_end . "_p$i";
        $assessqs[$i] = $prefix . "assessqs_" . $short_code_lc . "_" . $year_end . "_p$i";
    }
    $markstore_totals = "assess_total_" . $short_code_lc . "_" . $year_end;
    $feedback_records = "feedback_records_" . $short_code_lc . "_" . $year_end;

// Project description
    $master_name = "master_name_" . $short_code_lc . "_" . $year_begin;
    $project_details = "project_details_" . $short_code_lc . "_" . $year_begin;

    $projects_areas = "projects_areas_" . $short_code_lc . "_" . $year_begin;

// Staff lists
    $staff = "staff_names"; // Generic list (will get old)
    $staff_names_default = $staff;
    $staff2 = $staff_names_default;
    $staff_names = $staff2;
    $staff_names2 = $staff2;

    $staff_inactive = "staff_inactive_" . $short_code_lc . "_" . $year_begin;
    $staff_admin = "staff_admin_" . $short_code_lc . "_" . $year_begin;
    $staff_names_module = "staff_names_" . $short_code_lc . "_" . $year_begin;

    $admin_users_table = 'admin_users';
    $admin_users_module_table = 'admin_users_module';
    /*
    echo("<pre>");
    print_r($_SERVER);
    echo("</pre>");
    */
    //$this_staff_username = $_SERVER['REMOTE_USER'];
    $this_staff_username = $_COOKIE['USERNAME'];
    $this_staff_full_name = GetStaffNameFromUsername($db, $this_staff_username, $staff_names2, false);
    $this_staff_id = GetStaffIDFromUsername($db, $this_staff_username, $staff_names2);

// email
    $local_email_domain = "@leeds.ac.uk";

    $root_page = "index.php";

//proposal option_flags
    $query = "SELECT * FROM $projects_setup_proposals WHERE proj_id=$pm_id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $recs = $results->fetch();

    $option_text[0] = 'Related web sites';
    $option[0] = '_relsites';
    $option_text[1] = 'Related modules';
    $option[1] = '_relmods';
    $option_text[2] = 'Background reading';
    $option[2] = '_backread';
    $option_text[3] = 'Skills necessary';
    $option[3] = '_skills';
    $option_text[4] = 'Software requirements';
    $option[4] = '_software';
    $option_text[5] = 'Software support required';
    $option[5] = '_softsupport';
    $option_text[6] = 'Lab work description';
    $option[6] = '_labs';
    $option_text[7] = 'Workshop requirements';
    $option[7] = '_works';
    $option_text[8] = 'Further information';
    $option[8] = '_furtherinfo';

    $num_proposal_options = sizeof($option);
    for ($i = 0; $i < $num_proposal_options; $i++) {
        $name_id = 'i' . $option[$i];
        $option_flag[$i] = $recs[$name_id];
        $name_id = 'v' . $option[$i];
        $option_flag_v[$i] = $recs[$name_id];
        $name_id = 'm' . $option[$i];
        $option_flag_m[$i] = $recs[$name_id];
    }

// Assessment criteria
    $query = "select * from $assessment_criteria_table where 1";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }

    $rows = $results->fetchAll();

    $assessment_criteria_text = array(array());
    foreach ($rows as $recs) {
        $i = (int)$recs['mark'];
        $assessment_criteria_text[$i][0] = $recs['class']; // Excellent 1st
        $assessment_criteria_text[$i][1] = $recs['grade_text']; // A+
        $assessment_criteria_text[$i][2] = $recs['gpa']; // Max = 4.25
        $assessment_criteria_text[$i][3] = $recs['descriptor_university'];
        $assessment_criteria_text[$i][4] = $recs['descriptor_school'];
    }

// Which assessment criteria texts to show
    $query = "select * from $assessment_criteria_flags_table where `id`=0";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $recs = $results->fetch();
    $assess_criteria_visible = $recs['visible_flag'];
    $assess_criteria_flags[0] = $recs['class_flag']; // class, Excellent 1st etc
    $assess_criteria_flags[1] = $recs['grade_text_flag']; // Grade, A+ etc
    $assess_criteria_flags[2] = $recs['gpa_flag']; // GPA , MAx = 4.25
    $assess_criteria_flags[3] = $recs['descriptor_university_flag']; // university descriptor
    $assess_criteria_flags[4] = $recs['descriptor_school_flag']; // School descriptor

    $pega_id = 1;
    $hamid_id = 2;
    $dist_id = 3;
    $cwt_id = 4;
    $kmap_id = 5;
    $peer_id = 6;
    $pega_pre = "pega_";
    $pega2_pre = "pega2_";
    $hamid_pre = "ha_";
    $dist_pre = "di_";
    $cwt_pre = "cwt_";
    $kmap_pre = "kmap_";
    $peer_pre = "peer_";

// PWD Hint Damned
    $hash_to_test_against = '$P$B8fEI06dpUSjAwfKJ2sYGZWLev9PCs1';
}else {
    echo("pm_id = $pm_id .<BR>");
}
?>