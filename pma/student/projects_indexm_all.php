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
    $display_block .= "<div data-role='controlgroup'>\n";
    $display_block .= "<li><a href='selection.php?pm_id=$pm_id' rel='external' data-role='button' data-theme='e'>Select your project</a></li>\n";
    $display_block .= "</div>\n";

//$query = "select * from $projects_setup where id=$pm_id";
    $program_variants = GetProgramVariants($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $display_block .= "<div>List projects by programme <BR>\n";
    $display_block .= "<fieldset data-role=\"controlgroup\">\n";
    for ($i = 0; $i < $num_prog_variants; $i++) {
        $prog_short_name = $program_variants[$i];
        $display_block .= "<a href='projects-list-mobile_prog.php?pm_id=$pm_id&prog_var=$i' rel='external'
                   data-role='button' data-theme='b'>$prog_short_name</a>\n";
    }
    $display_block .= "</fieldset>\n";
    $display_block .= "</div>\n";

    $display_block .= "<p>Full list of projects</p>\n";
    $display_block .= "<div data-role='controlgroup'>\n";
    $display_block .= "    <li><a href='projects-list-mobile_prog.php?pm_id=$pm_id&prog_var=-1'
                   rel='external' data-role='button' data-theme='b'>List all projects</a></li>";
    $display_block .= "</div>";

    $display_block .= "<div data-role='controlgroup'>\n";
    $display_block .= "    <li><a href='projects-list-mobile_staff.php?pm_id=$pm_id' rel='external'
                   data-role='button' data-theme='b'>List all by staff name</a></li>\n";
    $display_block .= "</div>\n";

    $display_block .= "<div data-role='controlgroup'>";
    $display_block .= "<li><a href='../staff/proposals/list_projects_csv.php?pm_id=$pm_id' rel='external'
                   data-role='button' data-theme='c'>Download an Excel list of projects </a></li>\n";
    $display_block .= "</div>\n";

    $display_block .= "<span style='font-size: small;'>This gives you an excel file that is functional but not pretty. Format it yourself if you want it to look nice.\n";
  	$display_block .= "<BR>Excel may ask you if it is safe to open this file. The risk that it is not safe is low, I suggest you click the \"Yes\" button.</span>\n";

}
?>

<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html>
<?php (@include_once("../staff/$mobile_head")) OR die("Cannot find this file to include: $mobile_head<BR>"); ?>

<body>

<!--home page -->
<div data-role="page" data-theme="a" id="home">

    <!---------------------START of HEADER------------------------------------->
    <div id="jqm-homeheader" data-role="header">
        <!--<h1 id="jqm-logo"><img src="<?php echo("../staff/images/$header_image"); ?>" alt="Logo" width="320" height="87"/></h1>-->
        <h1 id="jqm-logo"><img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/></h1>
        <p><?php echo("$school_title $running_title"); ?></p>
    </div>
    <!---------------------END of HEADER------------------------------------->

    <!--++++Main page+++++++-->
    <div data-role="content">

        <?php echo("$display_block") ?>

    </div> <!-- content -->

    <div data-role="footer" data-position="fixed">
        <H1 style="color: dimgray;"><a target="_self" href="./index.php" style="color: dimgray;">Index</a></H1>
    </div><!-- /footer -->

</div> <!-- page -->
<!--++++----------End of page-----------+++++++-->
</body>
</html>
