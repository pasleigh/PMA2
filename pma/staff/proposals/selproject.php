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
(@include_once("../staff_student_functions.php")) OR die("Cannot find staff_student_functions.php");

$staff_username = "unknown";
if(array_key_exists('REMOTE_USER',$_SERVER)){
    $staff_username = get_server_username();
}else{
    if(isset($_SESSION['USERNAME'])){
        $staff_username = $_SESSION['USERNAME'];
    }
}
if(IsStaff($db, $staff_username, $staff_names) == false){
    echo "You do not have access to this page. If you think this is an error please contact the system administrator.<BR>";
    echo "<p><A target='self' href='../..'>Return to the home page</a>.</P>";
    exit();
}

$staff_close_date = $staff_close;
$str_staff_close_date = date("j F Y", strtotime($staff_close_date));
$cldate = $str_staff_close_date;

$now_datetime = date("Y-m-d H:i:s");
$date_diff = day_diff($now_datetime, $staff_close_date);

$display_block = "";


//
// Display the list of staff
//

$display_block .= "<h1><span class='text-white'>Display project proposals by all staff for: </span>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";

//$display_block .= "<h3>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";
if ($date_diff > 1.0) {
    $display_block .= "<H1>The deadline for entering project proposals has passed.</H1>";
}
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

    //$display_block .= "<div class='d-grid gap-2'>\n";
    $display_block .= "<div class='btn-group-vertical mt-2'>";
    $count = 1;
    foreach ($rows as $recs) {
        $master_id = $recs['id'];
        $project_title = $recs['f_title'];
        $supervisor_name = $recs['f_supervisor'];

        $display_block .= "<a href='viewproject2.php?pm_id=$pm_id&p_id=$master_id' class='btn btn-primary'>$count: ($master_id) $project_title - $supervisor_name</a>\n";

        $count++;

    }
    $display_block .= "</div>\n";
}

?>
<!DOCTYPE HTML>
<html>
<?php (@include_once("./mobile_head_bs.php")) OR die("Cannot find this file to include: mobile_head_bs.php<BR>"); ?>
<body class="bg-dark text-white">

<nav class="navbar navbar-expand-sm fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <a class="navbar-brand" href="projects_indexm2.php?pm_id=<?php echo($pm_id); ?>"><?php echo("$school_title $running_title"); ?></a>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../../.." class="nav-link px-2 text-white-50">Root options</a></li>
        </ul>
        <div class="navbar-brand ms-auto">
            <a class="navbar-brand" href="./projects_indexm2.php?pm_id=<?php echo($pm_id); ?>">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</nav>

<div class="container bg-dark text-white">
    <div class="text-center props-logo">
        <img src="../images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>
    <hr>
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <?php echo $display_block; ?>
        </div>
    </div>
</div>

</body>
</html>
