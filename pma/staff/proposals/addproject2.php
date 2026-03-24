<?php
// connect to the datbase
(@include_once("./set_dir_props.php")) OR die("Cannot find this file to include: set_dir_props.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables");
(@include_once("../staff_functions.php")) OR die("Cannot setup database tables");

(@include_once("../proposals/project_display_functions_staff.php")) OR die("Cannot include project_display_functions_staff.php");
(@include_once("../admin2/utility_functions.php")) OR die("Cannot include utility_functions.php");
(@include_once("../admin2/adminusers.php")) OR die("Cannot setup admin users");
/*
echo("_REQUEST<BR>\n");
foreach ($_REQUEST as $key=>$val )
{
  echo "Key: ".$key." val: " .$val."<BR>\n";
}
echo("<pre>");
echo("GET");
print_r($_GET);
echo("</pre>");
*/
if (!isset($_REQUEST['pm_id'])) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];

if (!isset($_REQUEST['stid'])) {
    $_REQUEST['stid'] = null;
}
$staffid = $_REQUEST['stid'];

if (!isset($_REQUEST['op'])) {
    $_REQUEST['op'] = null;
}
$OP = $_REQUEST['op'];

if (!isset($_REQUEST['f_title'])) {
    $_REQUEST['f_title'] = null;
}
if (!isset($_REQUEST['f_supervisor_id'])) {
    $_REQUEST['f_supervisor_id'] = null;
}
if (!isset($_REQUEST['f_assocstaff_id'])) {
    $_REQUEST['f_assocstaff_id'] = null;
}
if (!isset($_REQUEST['f_outline'])) {
    $_REQUEST['f_outline'] = null;
}
if (!isset($_REQUEST['f_UGprojects'])) {
    $_REQUEST['f_UGprojects'] = null;
}
if (!isset($_REQUEST['f_PGprojects'])) {
    $_REQUEST['f_PGprojects'] = null;
}

for($i = 0 ; $i < sizeof($option) ; $i++){
    $fname = "f" . $option[$i];
    if (!isset($_REQUEST[$fname])) {
        $_REQUEST[$fname] = null;
    }
}

/*
echo("_REQUEST<BR>\n");
foreach ($_REQUEST as $key=>$val )
{
    echo "Key: ".$key." val: " .$val."<BR>\n";
}
*/

$staff_close_date = $staff_close;
$str_staff_close_date = date("j F Y", strtotime($staff_close_date));
$cldate = $str_staff_close_date;

$display_block = "";
$module_ref_block = "<h3>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";

$display_block .= "<h1>Add a Project Theme</h1>\n";
$display_block .= "$module_ref_block";

//echo("post op  = " . $_REQUEST['op']);
if ($_REQUEST['op'] != "add") {
    $display_block .= "<H3>Updates to the project theme database closes at midnight $cldate</H3>\n";
    $display_block .= "<form data-ajax=\"false\" method=\"post\" onsubmit=\"return validate_form();\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

    for($i = 0 ; $i < sizeof($option) ; $i++){
        $option_value[$i] = "";
    }

    $program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $program_variant_flags = GetProgramVariantFlagsStaff($db, $project_details, null);

    $display_block .= DisplayProjectSelectBlockStaff($db, null, $staff_names2, null, $staffid, null, null, null, $option_value, $option_text, $option, $option_flag,$option_flag_v,$option_flag_m, $program_variants, $program_variant_flags);

    $display_block .= "<input type=\"hidden\" name=\"op\" value=\"add\">\n";
    $display_block .= "<p><input type=\"submit\" name=\"submit\" value=\"Add Entry\"  data-theme='b'></p>\n";
    $display_block .= "</form>";

} else if ($_REQUEST['op'] == "add") {

    // Get the prog variants
    for($i = 0; $i < 7 ; $i++)
    {
        $p_name_id = "prog$i";
        if (isset($_REQUEST[$p_name_id])) {
            $prog[$i] = 1;
        }else{
            $prog[$i] = 0;
        }

    }
    $f_progs = join("", $prog);

    $f_title = addslashes($_REQUEST['f_title']);
    $f_title = convert_smart_quotes($f_title);
    $f_outline = $_REQUEST['f_outline'];
    $f_outline = addslashes($f_outline);
    $f_outline = convert_smart_quotes($f_outline);
    // Some tidying up of things that should not have ben put there from the project entry process
    $f_outline = str_replace("<br /><br />", "<br />", $f_outline);
    $f_supervisor_id = addslashes($_REQUEST['f_supervisor_id']);
    $f_assocstaff_id = addslashes($_REQUEST['f_assocstaff_id']);
    $f_PGprojects = addslashes($_REQUEST['f_PGprojects']);

    for($i = 0 ; $i < sizeof($option) ; $i++){
        $fname = "f" . $option[$i];
        $option_value[$i] = $_REQUEST[$fname];
    }

    $program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $program_variant_flags = $prog;//GetProgramVariantFlagsStaff($db, $project_details, $master_id);

    $display_block .= DisplayProjectBlockStaff($db, null, $staff_names2, $f_title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flag,$option_flag_v,$option_flag_m, $program_variants, $program_variant_flags);

    $supervisor_name = GetStaffNameFromID($db, $f_supervisor_id, $staff_names2, false);
    $second_marker_name = GetStaffNameFromID($db, $f_assocstaff_id, $staff_names2, false);

//    $display_block .= "Title: $f_title<BR>\n";
//    $display_block .= "Outline: $f_outline<BR>\n";
//    $display_block .= "Supervisor: $supervisor_name ($f_supervisor_id)<BR>\n";
//    $display_block .= "Second marker: $second_marker_name ($f_assocstaff_id)<BR>\n";
//    $display_block .= "Sites: $f_relsites<BR>\n";
//    $display_block .= "Number of projects: $f_PGprojects<BR>\n";

    $date_now = date("Y-m-d H:i:s"); //'2016-03-01 00:00:00';// date_now();
    $date_modified =  date("Y-m-d H:i:s"); //'2016-03-01 00:00:00';// date_now();

    try {
        $results = $db->prepare("INSERT INTO $master_name SET date_added=?, date_modified=?, f_title=?, f_supervisor=?, f_supervisor_id=?");
        $results->execute([$date_now, $date_modified, $f_title, $supervisor_name, $f_supervisor_id]);
    } catch (PDOException $ex) {
        echo("An Error occured accessing the database <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $master_id = $db->lastInsertId();

    $f_UGprojects = '0';

    // Build option columns/params — column names are server-side constants, only values are parameterised
    $option_cols = [];
    $option_vals = [];
    for ($i = 0; $i < sizeof($option); $i++) {
        $fname = "f" . $option[$i];
        if ($option_flag[$i] == 1) {
            $value = convert_smart_quotes($_REQUEST[$fname] ?? '');
        } else {
            $value = "";
        }
        $option_cols[] = "$fname=?";
        $option_vals[] = $value;
    }
    $options_sql = implode(', ', $option_cols);

    $detail_params = array_merge(
        [$master_id, $second_marker_name, $f_assocstaff_id, $f_progs, $f_outline, $f_UGprojects, $f_PGprojects],
        $option_vals
    );
    try {
        $results = $db->prepare("INSERT INTO $project_details SET master_id=?, f_assocstaff=?, f_assocstaff_id=?, f_progs=?, f_outline=?, f_UGprojects=?, f_PGprojects=?, $options_sql");
        $results->execute($detail_params);
    } catch (PDOException $ex) {
        echo("An Error occured accessing the database <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }


}
?>
<!DOCTYPE HTML>
<html>

<?php require("./$mobile_head"); ?>

<body>
<script src="./validate_project_entry.js"></script>

<SCRIPT TYPE="text/javascript">
    function popup(mylink, windowname) {
        if (!window.focus)return true;
        var href;
        if (typeof(mylink) == 'string')
            href = mylink;
        else
            href = mylink.href;
        window.open(href, windowname, 'width=550,height=500,scrollbars=yes');
        return false;
    }
</SCRIPT>
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
