<?php
(@include_once("./set_dir_props.php")) OR die("Cannot find this file to include: set_dir_props.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables");

(@include_once("../admin2/utility_functions.php")) OR die("Cannot setup utility functions");
(@include_once("../admin2/adminusers.php")) OR die("Cannot setup admin users");

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

$staff_close_date = $res['staff_close'] . " 23:59";
$str_staff_close_date = date("j F Y, H:i", strtotime($staff_close_date));
$student_allocation_date = $res['alloc_date'];
$str_student_allocation_date = date("j F Y", strtotime($student_allocation_date));
$today = date("Y-m-d");

$now_datetime = date("Y-m-d H:i:s");

$display_block = "";
$display_block_edit_buttons = "";

$date_diff = day_diff($now_datetime, $staff_close_date);
//$display_block .= "Date diff (day) = $date_diff, Close: $staff_close_date, Now: $now_datetime<BR>\n";

$display_block .= "<h3>Project Proposals for: <BR>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";

$display_block .= "
<p>View/edit project themes <BR>(You will not be able to add/change projects after  <span style='color: yellow; border-width: 1pt;'> $str_staff_close_date </span>)</p>";

$display_block_edit_buttons .= "
        <div data-role=\"controlgroup\">
            <a href=\"addproject2.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"e\">Add a Project Theme</a>
            <a href=\"modproject_bystaff2.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"b\">View / Edit / Delete an existing Project</a>
        </div>
        ";

if($date_diff > 1.0 ){
    $display_block .= "<H1>The deadline for entering project proposals has passed.</H1>";
    if (IsAdmin($db, $this_staff_username) or IsProjectAdmin($db, $this_staff_username, $staff_names)) {
        $display_block .= "<P style='color: #6bc43d;'>As module level administrator you have access to modifying projects past the deadline date. <span style='color: red;'>Do this sparingly.</span></P>";
        $display_block .= $display_block_edit_buttons;
    }
}else {
    $display_block .= $display_block_edit_buttons;
}

$display_block .= "

<p>View Projects from full list</p>
<div data-role=\"controlgroup\">
	<li><a href=\"selproject.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"c\">List of projects</a></li>
</div>
";


$display_block .= "
  <p>Downloadable list of projects</p>
  <div data-role=\"controlgroup\">
  	<li><a href=\"list_projects_csv.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"c\">Excel file of projects </a></li>
  </div>
  <span style=\"font-size: small;\">This gives you an excel file that is functional but not pretty. Format it youself if you want it to look nice.
  	<BR>Excel may ask you if it is safe to open this file. The risk that it is not safe is low, I suggest you click the \"Yes\" button.</span>
  ";

?>
<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html>
<?php
(@include_once("./$mobile_head")) OR die("Cannot find this file to include: $mobile_head<BR>");
?>
<body>

<!--home page -->
<div data-role="page" data-theme="a" id="home">

    <div id="jqm-homeheader" data-role="header">
        <!--<h1 id="jqm-logo"><img src="../images/<?php echo($header_image) ?>" alt="Leeds Civil Logo" width="320" height="87"/></h1>-->
        <h1 id="jqm-logo"><img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/></h1>
        <p><?php echo("$school_title - $running_title ") ?><?php echo("$year_begin/$year_end") ?></p>
    </div>
    <!---------------------END of HEADER FILE------------------------------------->

    <!--++++Main page+++++++-->
    <div data-role="content">
        <?php echo("$display_block"); ?>

    </div> <!-- content -->

    <div data-role="footer" data-position="fixed">
        <H1><a target="_self" href="../session_modules.php<?PHP echo("?year_begin=$year_begin"); ?>"
               style="color: dimgray;"><?PHP echo("$year_begin/$year_end"); ?> projects index</a></h1>
    </div><!-- /footer -->

</div> <!-- page -->
<!--++++----------End of page-----------+++++++-->
</body>
</html>
