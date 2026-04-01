<?php

// connect to the database
(@include_once("./set_dir_student.php")) OR die("Cannot find this file to include: set_dir_student.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables");
(@include_once("$STAFF_DIR_VAL/staff_functions.php")) OR die("Cannot include staff_functions.php");
(@include_once("$STAFF_DIR_VAL/markstore_functions.php")) OR die("Cannot open markstore_functions.php");
(@include_once("./project_display_functions.php")) OR die("Cannot include project_display_function.php<BR>");
(@include_once("./student_functions.php")) OR die("Cannot include student_function.php<BR>");

(@include_once("$STAFF_DIR_VAL/admin2/utility_functions.php")) OR die("Cannot include utility_functions.php");

$refering_page=$_SERVER['HTTP_REFERER'];

$staff_close_date = $staff_close;
$str_staff_close_date = date("j F Y", strtotime($staff_close_date));
$cldate = $str_staff_close_date;

$now_datetime = date("Y-m-d H:i:s");
$date_diff = day_diff($now_datetime, $staff_close_date);

$display_block = "";

if($is_staff == true)
{
    if ($date_diff > 1.0) {
        $display_block .= "<H1>The deadline for entering project proposals has passed.</H1>";
    }
}

if (!isset($_REQUEST['pm_id'])) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];

if (!isset($_REQUEST['p_id'])) {
    $_REQUEST['p_id'] = null;
}
$master_id = $_REQUEST['p_id'];

if (!isset($_REQUEST['prog_var'])) {
    $_REQUEST['prog_var'] = null;
}
$prog_var = $_REQUEST['prog_var'];

$student_username = get_server_username();
$is_staff = isStaff($db, $student_username);
$is_student_current = false;

if($is_staff == false) {
    $is_student_current = isStudent_on_this_module($db, $pm_id, $student_username, $students);
}

if($is_student_current == false and $is_staff == false){
    $display_block .= GetStudentNotOnModulePhrase();
}else {
//
// Display the project
//
    $display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center'>View project proposal</div>";
    $display_block .= "<div style='font-size: large; font-weight: bold; text-align: center'>$module_short_description - $module_prefix$module1$module_postfix - $short_code, $year_begin/$year_end_short</div>";

    $query = "select * from $master_name WHERE id=:master_id";
    try {
        $results = $db->prepare($query);
        $results->execute([':master_id' => $master_id]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }
    $row = $results->fetch();
    $f_supervisor = $row['f_supervisor'];
    $supervisor_name = $f_supervisor;
    $f_supervisor_id = $row['f_supervisor_id'];
    $f_title = $row['f_title'];

    $query = "select * from $project_details WHERE master_id=:master_id";
    try {
        $results = $db->prepare($query);
        $results->execute([':master_id' => $master_id]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        exit();
    }
    $row = $results->fetch();

//`id`, `master_id`, `date_added`, `date_modified`, `f_assocstaff`, `f_assocstaff_id`,
// `f_outline`, `f_relmods`, `f_backread`, `f_relsites`,
// `f_skills`, `f_software`, `f_softsupport`, `f_labs`, `f_works`, `f_programmes`,
// `f_progs`, `f_UGprojects`, `f_PGprojects`, `f_furtherinfo
    $date_added = $row['date_added'];
    $date_modified = $row['date_modified'];
    $second_marker_name = $row['f_assocstaff'];
    $f_assocstaff = $row['f_assocstaff'];
    $f_assocstaff_id = $row['f_assocstaff_id'];
    $f_outline = $row['f_outline'];
    $f_relmods = $row['f_relmods'];
    $f_backread = $row['f_backread'];

    $f_relsites = $row['f_relsites'];
    $f_skills = $row['f_skills'];
    $f_software = $row['f_software'];
    $f_softsupport = $row['f_softsupport'];
    $f_labs = $row['f_labs'];
    $f_works = $row['f_works'];
    $f_programmes = $row['f_programmes'];
    $f_progs = $row['f_progs'];
    $f_UGprojects = $row['f_UGprojects'];
    $f_PGprojects = $row['f_PGprojects'];
    $f_furtherinfo = $row['f_furtherinfo'];

    $option_value[0] = $f_relsites;
    $option_value[1] = $f_relmods;
    $option_value[2] = $f_backread;
    $option_value[3] = $f_skills;
    $option_value[4] = $f_software;
    $option_value[5] = $f_softsupport;
    $option_value[6] = $f_labs;
    $option_value[7] = $f_works;
    $option_value[8] = $f_furtherinfo;

    $program_variants = GetProgramVariants($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $program_variant_flags = GetProgramVariantFlags($db, $project_details, $master_id);
    $display_block .= DisplayProjectBlock($db, $master_id, $staff_names2, $f_title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flag, $option_flag_v, $option_flag_m, $program_variants, $program_variant_flags);
}
?>
<!DOCTYPE HTML>
<html>
<?php (@include_once("./mobile_head_bs.php")) OR die("Cannot find this file to include: mobile_head_bs.php<BR>"); ?>
<body class="bg-light text-dark">

<nav class="navbar navbar-expand-sm fixed-top navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="index.php"><?php echo("$school_title $running_title"); ?></a>
        <div class="navbar-brand ms-auto">
            <a class="navbar-brand" href="<?php echo($refering_page); ?>"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>
</nav>

<div class="container bg-light text-dark">
    <div class="text-center props-logo mt-0">
        <img src="../staff/images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>
    <hr>
    <div>
        <?php echo $display_block; ?>
    </div>
</div>

</body>
</html>
