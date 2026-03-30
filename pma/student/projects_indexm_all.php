<?php
(@include_once("./set_dir_student.php")) OR die("Cannot find this file to include: set_dir_student.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./project_display_functions.php")) OR die("Cannot include project_display_functions.php");
(@include_once("./student_functions.php")) OR die("Cannot include student_function.php<BR>");

$year_end_short = $year_end - 2000;
$display_block = "";
$display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center'>Project Selection</div>";
$display_block .= "<div style='font-size: large; font-weight: bold; text-align: center'>$module_short_description - $module_prefix$module1$module_postfix - $short_code, $year_begin/$year_end_short</div>";

$student_username = get_server_username();
$is_staff = isStaff($db, $student_username);
$is_student_current = false;

if($is_staff == false) {
    $is_student_current = isStudent_on_this_module($db, $pm_id, $student_username, $students);
}

$staff_text = "";
if ($is_staff) {
    $staff_text = "(staff)";
    $student_name = GetStaffNameFromUsername($db, $student_username, $staff_names_default, null);
    $student_programme = "(N/A)";
} else {
    $student_name = GetStudentNameFromUsername($db, $student_username, $students);
    $student_programme = GetStudentProgrammeFromUsername($db, $student_username, $students);
}

if($is_student_current == false and $is_staff == false){
    $display_block .= GetStudentNotOnModulePhrase();
}else {
    $display_block .= "<div>You are $student_name ($student_username) .<BR> On programme: $student_programme</div>";

//$display_block .= "<p>$module_short_description : project selection</p>\n";
    $display_block .= "<div class='text-center'>\n";
    $display_block .= "<div class='btn-group-vertical mt-2'>\n";
    $display_block .= "<a href='selection.php?pm_id=$pm_id' class='btn btn-warning'>Select your project</a>\n";
    $display_block .= "</div></div>\n";

//$query = "select * from $projects_setup where id=$pm_id";
    $program_variants = GetProgramVariants($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $display_block .= "<p>List projects by programme</p>\n";
    $display_block .= "<div class='text-center'><div class='btn-group-vertical mt-1'>\n";
    for ($i = 0; $i < $num_prog_variants; $i++) {
        $prog_short_name = $program_variants[$i];
        $display_block .= "<a href='projects-list-mobile_prog.php?pm_id=$pm_id&prog_var=$i' class='btn btn-success'>$prog_short_name</a>\n";
    }
    $display_block .= "</div></div>\n";

    $display_block .= "<p class='mt-3'>Full list of projects</p>\n";
    $display_block .= "<div class='text-center'><div class='btn-group-vertical mt-1'>\n";
    $display_block .= "<a href='projects-list-mobile_prog.php?pm_id=$pm_id&prog_var=-1' class='btn btn-primary'>List all projects</a>\n";
    $display_block .= "<a href='projects-list-mobile_staff.php?pm_id=$pm_id' class='btn btn-primary'>List all by staff name</a>\n";
    $display_block .= "<a href='../staff/proposals/list_projects_csv.php?pm_id=$pm_id' class='btn btn-secondary'>Download an Excel list of projects</a>\n";
    $display_block .= "</div></div>\n";


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
            <a class="navbar-brand" href="index.php"><i class="bi bi-arrow-left"></i> Module list</a>
        </div>
    </div>
</nav>

<div class="container bg-light text-dark">
    <div class="text-center props-logo mt-0">
        <img src="../staff/images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>
    <hr>
    <div class="text-center">
        <?php echo("$display_block"); ?>
    </div>
    <p class="text-center text-muted mt-2"><small>This gives you an excel file that is functional but not pretty. Format it yourself if you want it to look nice.<BR>Excel may ask you if it is safe to open this file. The risk that it is not safe is low, I suggest you click the &quot;Yes&quot; button.</small></p>
</div>

</body>
</html>
