<?php

// connect to the database
(@include_once("./set_dir_props.php")) OR die("Cannot find this file to include: set_dir_props.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables");
(@include_once("../staff_functions.php")) OR die("Cannot setup database tables");
(@include_once("../markstore_functions.php")) OR die("Cannot open markstore_functions.php");

(@include_once("../admin2/utility_functions.php")) OR die("Cannot include utility_functions.php");
(@include_once("../admin2/adminusers.php")) OR die("Cannot setup admin users");

$staff_close_date = $staff_close;
$str_staff_close_date = date("j F Y", strtotime($staff_close_date));
$cldate = $str_staff_close_date;

$now_datetime = date("Y-m-d H:i:s");
$date_diff = day_diff($now_datetime, $staff_close_date);

$display_block = "";

if ($date_diff > 1.0) {
    $display_block .= "<H1>The deadline for entering project proposals has passed.</H1>";
}
//
// Display the list of staff
//
$display_block .= "<H3>Display project proposals by all staff</H3>";
$display_block .= "<h3>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";

$query = "select * from $master_name order by id, f_supervisor, f_title";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo("An Error occured accessing the database in function: $this_function <BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$num_projects = $results->rowCount();

if ($num_projects < 1) {
    //no records
    $display_block .= "<p><em>Sorry, there are no projects!</em></p>";
} else {
    //has records
    $display_block .= "\n <P><strong>Select a Project to view:</strong><br>\n";
    $rows = $results->fetchAll();

    $display_block .= "<div data-role='controlgroup'>\n";
    $count = 1;
    foreach ($rows as $recs) {
        $master_id = $recs['id'];
        $project_title = $recs['f_title'];
        $supervisor_name = $recs['f_supervisor'];

        //$display_block .= "$master_id: $project_title<BR>";
        $display_block .= "<a href='viewproject2.php?pm_id=$pm_id&p_id=$master_id' rel='external' data-role='button' data-theme=\"b\">$count:  ($master_id) $project_title - $supervisor_name</a>\n";

        $count++;

    }
    $display_block .= "</div>\n";
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
            <a href="./projects_indexm2.php<?php echo("?pm_id=$pm_id") ?>" data-role="button">Back</a>
        </H1>
    </div><!-- /footer -->

</div> <!-- page -->
<!--++++----------End of page-----------+++++++-->

</body>
</html>
