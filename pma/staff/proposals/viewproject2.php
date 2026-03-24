<?php

// connect to the database
(@include_once("./set_dir_props.php")) OR die("Cannot find this file to include: set_dir_props.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables");
(@include_once("../staff_functions.php")) OR die("Cannot setup database tables");
(@include_once("../markstore_functions.php")) OR die("Cannot open markstore_functions.php");

(@include_once("../proposals/project_display_functions_staff.php")) OR die("Cannot include project_display_functions_staff.php");
(@include_once("../admin2/utility_functions.php")) OR die("Cannot include utility_functions.php");
(@include_once("../admin2/adminusers.php")) OR die("Cannot setup admin users");

//print_r3("_POST",$_POST);
//print_r3("_REQUEST",$_REQUEST);

$staff_close_date = $staff_close;
$str_staff_close_date = date("j F Y", strtotime($staff_close_date));
$cldate = $str_staff_close_date;

$now_datetime = date("Y-m-d H:i:s");
$date_diff = day_diff($now_datetime, $staff_close_date);

$display_block = "";

if ($date_diff > 1.0) {
    $display_block .= "<H1>The deadline for entering project proposals has passed.</H1>";
}

if (!isset($_REQUEST['pm_id'])) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];

if (!isset($_REQUEST['p_id'])) {
    $_REQUEST['p_id'] = null;
}
$master_id = $_REQUEST['p_id'];
//
// Display the list of staff
//
$display_block .= "<H3>View project proposal</H3>";
$display_block .= "<h3>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";


$query = "select * from $master_name WHERE id='$master_id' ";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    echo(" Query = $query<BR> \n");
    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
    exit();
}
$row = $results->fetch();
$f_supervisor = $row['f_supervisor'];
$supervisor_name = $f_supervisor;
$f_supervisor_id = $row['f_supervisor_id'];
$f_title = $row['f_title'];

$query = "select * from $project_details WHERE master_id='$master_id' ";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    echo(" Query = $query<BR> \n");
    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
    exit();
}
$row = $results->fetch();

//`id`, `master_id`, `date_added`, `date_modified`, `f_assocstaff`, `f_assocstaff_id`,
// `f_outline`, `f_relmods`, `f_backread`, `f_relsites`,
// `f_skills`, `f_software`, `f_softsupport`, `f_labs`, `f_works`, `f_programmes`,
// `f_progs`, `f_UGprojects`, `f_PGprojects`, `f_furtherinfo
$date_added=$row['date_added'];
$date_modified=$row['date_modified'];
$second_marker_name=$row['f_assocstaff'];
$f_assocstaff=$row['f_assocstaff'];
$f_assocstaff_id=$row['f_assocstaff_id'];
$f_outline=$row['f_outline'];
$f_relmods=$row['f_relmods'];
$f_backread=$row['f_backread'];

$f_relsites=$row['f_relsites'];
$f_skills=$row['f_skills'];
$f_software=$row['f_software'];
$f_softsupport=$row['f_softsupport'];
$f_labs=$row['f_labs'];
$f_works=$row['f_works'];
$f_programmes=$row['f_programmes'];
$f_progs=$row['f_progs'];
$f_UGprojects=$row['f_UGprojects'];
$f_PGprojects=$row['f_PGprojects'];
$f_furtherinfo=$row['f_furtherinfo'];

$option_value[0] = $f_relsites;
$option_value[1] = $f_relmods;
$option_value[2] = $f_backread;
$option_value[3] = $f_skills;
$option_value[4] = $f_software;
$option_value[5] = $f_softsupport;
$option_value[6] = $f_labs;
$option_value[7] = $f_works;
$option_value[8] = $f_furtherinfo;

$program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
$program_variant_flags = GetProgramVariantFlagsStaff($db, $project_details, $master_id);

$display_block =  DisplayProjectBlockStaff($db, $master_id, $staff_names2, $f_title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flag,$option_flag_v,$option_flag_m, $program_variants, $program_variant_flags);

?>
<!DOCTYPE HTML>
<html>

<?php require("./$mobile_head"); ?>

<body>

<!--home page -->
<div data-role="page" data-theme="a" id="home">
    <div id="jqm-homeheader" data-role="header">
        <!--<h1 id="jqm-logo"><img src="../images/<?php echo($header_image) ?>" alt="Leeds Eng Logo" width="320" height="87"/></h1>-->
        <h1 id="jqm-logo"><img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/></h1>
        <p><?php echo("$school_title - $running_title ") ?><?php echo("$year_begin/$year_end") ?></p>

    </div>
    <!---------------------END of HEADER FILE------------------------------------->
    <!--++++Main page+++++++-->
    <div data-role="content">

        <?php echo $display_block; ?>

    </div> <!-- content -->

    <div data-role="footer" data-position="fixed">
        <H1>
            <a href="./projects_indexm2.php<?php echo("?pm_id=$pm_id") ?>" data-role="button">Back</a>
        </H1>
    </div><!-- /footer -->

</div> <!-- page -->
<!--++++----------End of page-----------+++++++-->

</body>
</html>
