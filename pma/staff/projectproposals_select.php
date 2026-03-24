<?php
(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./config.php")) OR die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once("./setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("./setup_tables.php")) OR die("Cannot setup database tables");

(@include_once("./admin2/adminusers.php")) OR die("Cannot setup admin users");


if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];


$query = "select * from $projects_setup where id=$pm_id";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    echo("Unable to update data to the table $projects_setup<BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$res = $results->fetch();
$module1 = $res['module'];
$year_begin = $res['year_begin'];
$year_end = $res['year_end'];

$module_prefix = $res['module_prefix'];
$module_short_description = $res['module_short_desc'];
$short_code = $res['short_code'];

// Get list of project by this staff member
$query = "select * from $master_name where id=$pm_id";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    echo("Unable to update data to the table $master_name<BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$res = $results->fetch();


$display_block = "<P>You are logged in as $this_staff_full_name ($this_staff_username)</P>";

$display_block = "<h3>Project Proposals for: <BR>$module_short_description - $module_prefix$module1 - $short_code</h3>";

//$display_block .= "<div>";
$display_block .= "<A href=\"./projectproposals.php?pm_id=$pm_id&user=1\" rel=\"external\" data-role=\"button\" data-theme=\"b\">Show only projects for $this_staff_full_name</A>\n";

$display_block .= "<div data-role=\"controlgroup\">";
$display_block .= "<A href=\"./projectproposals.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"c\">Show projects for all staff  </A>\n";
$display_block .= "</div>";

/*
// If admin user then display this
if (IsAdmin($db, $this_staff_username) or IsProjectAdmin($db, $this_staff_username, $staff_names)) {
    $display_block .= "<P>You see these buttons because you are a project level administrator. Be careful when using them.</P>";
    $display_block .= "<div data-role=\"controlgroup\">";
    $display_block .= "<A href=\"./projectmarking_save_to_csv.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"a\">Save list of project marks to CSV</A>\n";
    $display_block .= "<A href=\"./pdf_functions.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"a\">Create, Save and email PDFs of feedback</A>\n";
    $display_block .= "</div>";
}
*/
//$display_block .= "</div>";

?>
<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html>
<?php
(@include_once("$mobile_head")) OR die("Cannot find this file to include: $mobile_head<BR>");
?>
<body>

<!--home page -->
<div data-role="page" data-theme="a" id="home">

    <div id="jqm-homeheader" data-role="header">
        <!--<h1 id="jqm-logo"><img src="./images/<?php echo($header_image) ?>" alt="Leeds Civil Logo" width="320" height="87"/></h1>-->
        <h1 id="jqm-logo"><img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/></h1>

        <p><?php echo("$school_title - $running_title ") ?><?php echo("$year_begin/$year_end") ?></p>
    </div>
    <!---------------------END of HEADER FILE------------------------------------->

    <!--++++Main page+++++++-->
    <div data-role="content">
        <?php echo("$display_block"); ?>

    </div> <!-- content -->

    <div data-role="footer" data-position="fixed">
        <H1><a target="_self" href="./session_modules.php<?PHP echo("?year_begin=$year_begin"); ?>"
               style="color: dimgray;"><?PHP echo("$year_begin/$year_end"); ?> projects index</a></h1>
    </div><!-- /footer -->

</div> <!-- page -->
<!--++++----------End of page-----------+++++++-->
</body>
</html>
