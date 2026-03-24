<?php
(@include_once("./set_dir_student.php")) OR die("Cannot find this file to include: set_dir_student.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./project_display_functions.php")) OR die("Cannot include project_display_function.php<BR>");
(@include_once("./student_functions.php")) OR die("Cannot include student_function.php<BR>");

// Select the years available in the projects setup table
$year_end_short = $year_end - 2000;
$display_block = "";
$display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center'>Project Selection</div>";
$display_block .= "<div style='font-size: large; font-weight: bold; text-align: center'>$module_short_description - $module_prefix$module1$module_postfix - $short_code, $year_begin/$year_end_short</div>";

if (array_key_exists('prog_var', $_REQUEST) == false) {
    $_REQUEST['prog_var'] = -1;
}
$prog_var = $_REQUEST['prog_var'];

$student_username = $_SERVER['REMOTE_USER'];
$is_staff = isStaff($db, $student_username);
$is_student_current = false;
if($is_staff == false) {
    $is_student_current = isStudent_on_this_module($db, $pm_id, $student_username, $students);
}

if($is_student_current == false and $is_staff == false){
    $display_block .= GetStudentNotOnModulePhrase();
}else {
    $staff_text = "";
    if ($is_staff) {
        $staff_text = "(staff)";
        $student_name = GetStaffNameFromUsername($db, $student_username, $staff_names_default, null);
    } else {
        $student_name = GetStudentNameFromUsername($db, $student_username, $students);
    }

    $display_block .= "<div>You are $student_name ($student_username)</div>";
    $count_modules = 0;


    $program_variants = GetProgramVariants($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $program_variant_flags = GetProgramVariantFlags($db, $project_details, null);

    if ($prog_var < 0) {
        // list all programmes
        $prog_name = "all programmes";
    } else {
        $prog_name = $program_variants[$prog_var];
    }
//echo("Prog name: $prog_name<BR>\n");
//echo("Prog var: $prog_var<BR>\n");

//$display_block .= "<p>This page lists $module_short_description projects that are on offer for the session $year_begin-$year_end.</p>";
    $display_block .= "<div><h3>List of all  $module_short_description projects for $prog_name</h3>\n";

    $display_block .= "<fieldset data-role='controlgroup'>\n";

//Loop through all project themes
    $query = "SELECT * FROM $master_name ORDER BY id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $num_total_projects = $results->rowCount();
    $rows = $results->fetchAll();

    $count = 1;
    foreach ($rows as $recs) {
        $project_id = $recs['id'];
        $f_title = $recs['f_title'];
        $f_supervisor_name = $recs['f_supervisor'];
        $f_supervisor_id = $recs['f_supervisor_id'];

        $query = "SELECT * FROM $project_details WHERE master_id = $project_id and f_PGprojects > 0";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: \" . $ex->getMessage() . \"<BR>\n");

            exit();
        }

        $num_projects = $results->rowCount();

        $program_variant_flags = GetProgramVariantFlags($db, $project_details, $project_id);

        $show_this_project = false;
        if ($prog_var == -1) {
            $show_this_project = true;
        } else if ($program_variant_flags[$prog_var] == 1) {
            $show_this_project = true;
        }

        if ($num_projects > 0 and $show_this_project) {
            $display_block .= "      <a href=\"./viewproject2.php?pm_id=$pm_id&p_id=$project_id&prog_var=$prog_var\" rel='external'
                   data-role='button' data-theme='a'>$project_id: $f_title</A>\n";
        }
    }
    $display_block .= "</fieldset>\n";

    $display_block .= "</div>
<!--++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++-->";
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


            <?php echo("$display_block"); ?>


    </div><!-- content -->


    <div data-role="footer" data-position="fixed">
        <H1 style="color: dimgray;"><a target="_self" href="./projects_indexm_all.php?pm_id=<?php echo("$pm_id"); ?>" style="color: dimgray;">Index</a></H1>
    </div><!-- /footer -->


</div> <!-- page -->
<!--++++-------End of Main page---------+++++++-->


</body>
</html>
