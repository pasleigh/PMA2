<?php

// connect to the datbase
(@include_once('./set_dir_props.php')) or die('Cannot find this file to include: set_dir_props.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables');
(@include_once('../staff_functions.php')) or die('Cannot setup database tables');

(@include_once('../admin2/utility_functions.php')) or die('Cannot include utility_functions.php');
(@include_once('../admin2/adminusers.php')) or die('Cannot setup admin users');
(@include_once('../staff_student_functions.php')) or die('Cannot find staff_student_functions.php');

$staff_username = get_server_username();
if (IsStaff($db, $staff_username, $staff_names) == false) {
    echo 'You do not have access to this page. If you think this is an error please contact the system administrator.<BR>';
    echo "<p><A target='self' href='../..'>Return to the home page</a>.</P>";
    exit();
}

if (array_key_exists('stid', $_REQUEST) == false) {
    $_REQUEST['stid'] = null;
}
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
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$setup_data = $results->fetch();

$nowarray = getdate();
$close = mktime(17, 0, 0, $setup_data['month(staff_close)'], $setup_data['dayofmonth(staff_close)'], $setup_data['year(staff_close)']);
$cldate = '5pm, ' . $setup_data['dayname(staff_close)'] . ' ' . $setup_data['dayofmonth(staff_close)'] . ' ' . $setup_data['monthname(staff_close)'] . ' ' . $setup_data['year(staff_close)'];
if ($nowarray[0] > $close && !IsAdmin($db, $this_staff_username) && !IsProjectAdmin($db, $this_staff_username, $staff_names)) {
    $display_block = '<H1>The deadline for entering project proposals has passed.</H1>';
    echo($display_block);
    exit;
}

//
// Display the list of projects for this staff
//
//$display_block = "<H3>Display and edit project proposals</H3>";
$display_block = '';
$module_ref_block = "<h3>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";

$query = "select * from $staff_names2 WHERE staffid=:staffid";
try {
    $results = $db->prepare($query);
    $results->execute([':staffid' => $staffid]);
} catch (PDOException $ex) {
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

    $display_block .= "<div class='d-grid gap-2 col-md-6'>";

    // List projects by this staff
    $query = "select * from $master_name where f_supervisor_id = :staffid";
    try {
        $results = $db->prepare($query);
        $results->execute([':staffid' => $staffid]);
    } catch (PDOException $ex) {
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
        $display_block .= "<a href='modproject2.php?pm_id=$pm_id&p_id=$master_id' class='btn btn-primary'>$count: ($master_id) $project_title</a>\n";

        $count++;
    }
    $display_block .= "</div>\n";
}

$display_block .= "<div class='mt-3'>\n";
$display_block .= "<a href='addproject2.php?pm_id=$pm_id&stid=$staffid' class='btn btn-warning'>Add a new project</a>\n";
$display_block .= "</div>\n";

?>
<!DOCTYPE HTML>
<html>
<?php (@include_once('./mobile_head_bs.php')) or die('Cannot find this file to include: mobile_head_bs.php<BR>'); ?>
<body class="bg-dark text-white">

<nav class="navbar navbar-expand-sm fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <a class="navbar-brand" href="projects_indexm2.php?pm_id=<?php echo($pm_id); ?>"><?php echo("$school_title $running_title"); ?></a>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../../.." class="nav-link px-2 text-white-50">Root options</a></li>
        </ul>
        <div class="navbar-brand ms-auto">
            <a class="navbar-brand" href="./modproject_bystaff2.php?pm_id=<?php echo($pm_id); ?>">
                <i class="bi bi-arrow-left"></i> Staff list
            </a>
        </div>
    </div>
</nav>

<div class="container bg-dark text-white">
    <div class="text-center props-logo">
        <img src="../images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>
    <hr>
    <?php echo $display_block; ?>
</div>

</body>
</html>
