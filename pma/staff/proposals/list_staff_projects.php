<?php

// connect to the datbase
(@include_once("./set_dir_props.php")) OR die("Cannot find this file to include: set_dir_props.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables");
(@include_once("../staff_functions.php")) OR die("Cannot setup database tables");

(@include_once("../admin2/utility_functions.php")) OR die("Cannot include utility_functions.php");
(@include_once("../admin2/adminusers.php")) OR die("Cannot setup admin users");

if (array_key_exists('stid', $_REQUEST)==false){ $_REQUEST['stid'] = null; }
$stid = $_REQUEST['stid'];
$staffid = $stid;

//
// Check the date for editiing has not passed
// 
$query = "select year_begin, dayname(staff_close), dayofmonth(staff_close), monthname(staff_close), month(staff_close), year(staff_close) from $projects_setup where module=$module1 and year_begin=$year_begin";
//$get_setup_list = mysql_query($get_setup) or die(" Unable to get database configuration.");
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$setup_data = $results->fetch();;

$nowarray = getdate();
$close = mktime(17, 0, 0, $setup_data['month(staff_close)'], $setup_data['dayofmonth(staff_close)'], $setup_data['year(staff_close)']);
$cldate = "5pm, " . $setup_data['dayname(staff_close)'] . " " . $setup_data['dayofmonth(staff_close)'] . " " . $setup_data['monthname(staff_close)'] . " " . $setup_data['year(staff_close)'];
if ($nowarray[0] > $close && !IsAdmin($db, $this_staff_username) && !IsProjectAdmin($db, $this_staff_username, $staff_names)) {
    $display_block = "<H1>The deadline for entering project proposals has passed.</H1>";
    echo($display_block);
   exit;
}

//
// Display the list of projects for this staff
//
//$display_block = "<H3>Display and edit project proposals</H3>";
$display_block = "";
$module_ref_block = "<h3>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";

$query = "select * from $staff_names2 WHERE staffid='$staffid' ";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}

if ($results->rowCount() < 1) {
   //no records
   $display_block .= "<p><em>Sorry, there are no staff in the database with this id $stid</em></p>";
    $display_block .= "$module_ref_block";
} else {
    //has records
    $recs = $results->fetch();
    $initials = $recs['initials'];
    $staffname = stripslashes($recs['fullname']);
    $staffid = $recs['staffid'];
    $inactive = $recs['inactive'];

    $display_block .= "<H3>Display and edit project proposals by $staffname</H3>";
    $display_block .= "$module_ref_block";

    $display_block .= "<div data-role='controlgroup'>";

    // List projects by this staff
    $query = "select * from $master_name where f_supervisor_id = $staffid";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $num_supervise = $results->rowCount();

    $rows = $results->fetchAll();

    $count = 1;
    foreach ($rows as $recs) {
        $master_id = $recs['id'];
        $project_title = $recs['f_title'];

        //$display_block .= "$master_id: $project_title<BR>";
        $display_block .= "<a href='modproject2.php?pm_id=$pm_id&p_id=$master_id' rel='external' data-role='button' data-theme=\"b\">$count:  ($master_id) $project_title</a>\n";

        $count++;

    }
    $display_block .= "</div>\n";
}

$display_block .= "<div>\n";
$display_block .= "<a href='addproject2.php?pm_id=$pm_id&stid=$staffid' rel='external' data-role='button' data-theme=\"e\">Add a new project</a>\n";
$display_block .= "</div>\n";

?>
<!DOCTYPE HTML>
<html>

<?php require("./$mobile_head"); ?>

<body>

<!--home page --> 
<div data-role="page"  data-theme="a" id="home"> 
<div id="jqm-homeheader" data-role="header"> 
	<!--<h1 id="jqm-logo"><img src="../images/<?php echo($header_image) ?>" alt="Leeds Eng Logo" width="320" height="87" /></h1>-->
    <h1 id="jqm-logo"><img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/></h1>
    <p><?php echo("$school_title - $running_title ") ?> <?php echo ("$year_begin/$year_end") ?></p>

</div>
<!---------------------END of HEADER FILE------------------------------------->
<!--++++Main page+++++++-->
<div data-role="content">
	
       <?php echo $display_block; ?>
       
 </div> <!-- content -->
 
 <div data-role="footer" data-position="fixed">
 	<H1>
 		<a href="./modproject_bystaff2.php<?php echo("?pm_id=$pm_id") ?>" data-role="button">Back to staff list</a>
 		</H1>
  </div><!-- /footer -->

</div> <!-- page -->
<!--++++----------End of page-----------+++++++-->

</body>
</html>
