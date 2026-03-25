<?php

// connect to the datbase
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

if (!isset($_REQUEST['pm_id'])) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];

if (!isset($_REQUEST['p_id'])) {
    $_REQUEST['p_id'] = null;
}
$proj_id = $_REQUEST['p_id'];

if (!isset($_REQUEST['op'])) {
    $_REQUEST['op'] = null;
}
$POST_OP = $_REQUEST['op'];

//
// Check the date for editiing has not passed
//
$staff_close_date = $staff_close;
$str_staff_close_date = date("j F Y", strtotime($staff_close_date));
$cldate = $str_staff_close_date;

$now_datetime = date("Y-m-d H:i:s");
$date_diff = day_diff($now_datetime, $staff_close_date);

$display_block = "";

if ($date_diff > 1.0) {
    $display_block .= "<H1>The deadline for entering / editing project proposals has passed.</H1>";
    exit();
}

if ($POST_OP == "") {
    //check for required fields
    if ($proj_id == "") {
        $display_block = "<P>You must select a project. Return to ";
        $display_block .= "<a href=\"list_staff_projects.php?pm_id=$pm_id\"> staff project list</a></P>";
    } else {
        // get master_info
        $query = "select * from $master_name where id = $proj_id";
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
        $id = $recs['id'];
        $f_title = stripslashes($recs['f_title']);
        $f_supervisor_name = stripslashes($recs['f_supervisor']);
        $f_supervisor_id = stripslashes($recs['f_supervisor_id']);
        $display_name = $f_title . " - " . $f_supervisor_name;

        $query = "select * from $project_details WHERE master_id='$proj_id' ";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $row = $results->fetch();

//`id`, `master_id`, `date_added`, `date_modified`, `f_assocstaff`, `f_assocstaff_id`,
// `f_outline`, `f_relmods`, `f_backread`, `f_relsites`,
// `f_skills`, `f_software`, `f_softsupport`, `f_labs`, `f_works`, `f_programmes`,
// `f_progs`, `f_UGprojects`, `f_PGprojects`, `f_furtherinfo
        $date_added = $row['date_added'];
        $date_modified = $row['date_modified'];
        $f_assocstaff_name = $row['f_assocstaff'];
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
//    $option_text[0]='Related sites'; $option[0]='_relsites';
//    $option_text[1]='Related modules'; $option[1]='_relmods';
//    $option_text[2]='Background reading'; $option[2]='_backread';
//    $option_text[3]='Skills necessary'; $option[3]='_skills';
//    $option_text[4]='Software requirements'; $option[4]='_software';
//    $option_text[5]='Software support required'; $option[5]='_softsupport';
//    $option_text[6]='Lab work description'; $option[6]='_labs';
//    $option_text[7]='Workshop requirements'; $option[7]='_works';
//    $option_text[8]='Furthur information'; $option[8]='_furtherinfo';
        $option_value[0] = $f_relsites;
        $option_value[1] = $f_relmods;
        $option_value[2] = $f_backread;
        $option_value[3] = $f_skills;
        $option_value[4] = $f_software;
        $option_value[5] = $f_softsupport;
        $option_value[6] = $f_labs;
        $option_value[7] = $f_works;
        $option_value[8] = $f_furtherinfo;

        $display_block .= "<form data-ajax=\"false\" method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id&p_id=$proj_id\">\n";

        $program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_program_variants);
        $program_variant_flags = GetProgramVariantFlagsStaff($db, $project_details, $master_id);

        $display_block .= DisplayProjectBlockStaff($db, $master_id, $staff_names2, $f_title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flag,$option_flag_v,$option_flag_m, $program_variants, $program_variant_flags);

        $display_block .= "<br>\n";
        $display_block .= "<input type=\"hidden\" name=\"op\" value=\"del\">\n";
        $display_block .= "<input type=\"hidden\" name=\"p_id\" value=\"$proj_id\">\n";
        $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Really Delete this project\"></p>\n";
        $display_block .= "</form>\n";

    }
} else if ($POST_OP== "del") {
    //check for required fields
    if ($proj_id == "") {
        $display_block = "<P>You must select a project. Return to ";
        $display_block .= "<a href=\"./projects_indexm2.php?pm_id=$pm_id\">Main project entry</a></P>";

        //exit;
    } else {
        // get master_info
        $query = "select * from $master_name where id = $proj_id";
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
        $id = $recs['id'];
        $f_title = stripslashes($recs['f_title']);
        $f_supervisor_name = stripslashes($recs['f_supervisor']);
        $f_supervisor_id = stripslashes($recs['f_supervisor_id']);
        $display_name = $f_title . " - " . $f_supervisor_name;

        // delete
        $query = "delete from $master_name where id = $proj_id";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }

        $query = "delete from $project_details where master_id = $proj_id";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }

        $display_block = "<h1>Project $display_name has been deleted</h1>
      ";
    }
}

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
            <a href="./modproject_bystaff2.php<?php echo("?pm_id=$pm_id") ?>" data-role="button">Back</a>
        </H1>
    </div><!-- /footer -->

</div> <!-- page -->
<!--++++----------End of page-----------+++++++-->

</body>
</html>