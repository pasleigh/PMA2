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

if (array_key_exists('stid', $_REQUEST)==false){ $_REQUEST['stid'] = null; }
//if (array_key_exists('proj_id', $_POST)==false){ $_POST['proj_id'] = null; }
if (array_key_exists('op', $_POST)==false){ $_POST['op']=null; }
if (array_key_exists('sel_id', $_POST)==false){ $_POST['sel_id'] = null; }

$stid = $_REQUEST['stid'];
$proj_id = $_POST['sel_id'];

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

$staff_close_date = $staff_close;
$str_staff_close_date = date("j F Y", strtotime($staff_close_date));
$cldate = $str_staff_close_date;

$now_datetime = date("Y-m-d H:i:s");
$date_diff = day_diff($now_datetime, $staff_close_date);

//if (IsAdmin($db, $this_staff_username) or IsProjectAdmin($db, $this_staff_username, $staff_names)) {
if($date_diff > 1.0 && !IsAdmin($db, $this_staff_username) && !IsProjectAdmin($db, $this_staff_username, $staff_names))
{
    $display_block .= "<H1>The deadline for entering project proposals has passed.</H1>";
}else {
//
// Display the list of staff
//
    $display_block .= "<div class='text-center'>\n";
    $display_block .= "<h1><span class='text-white'>Display and edit project proposals by all staff for: </span>\n";

    $display_block .= "$module_short_description - $module_prefix$module1$module_postfix - $short_code</h1>\n";

    $display_block .= "<button type='button' class='btn btn-secondary btn-sm mb-2' onclick='toggle_zero_project_links();'>Toggle showing staff with zero projects</button>\n";

    $query = "select * from $staff_names2 order by familyname, firstname ";
//$get_list_res = mysql_query($get_list) or die(" Unable to create staff list from database:<BR> query = $get_list <BR>");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $rows = $results->fetchAll();

    if ($results->rowCount() < 1) {
        //no records
        $display_block .= "<p><em>Sorry, there are no staff names in the database!</em></p>";
    } else {
        //has records
        //$display_block .= "<div class='d-grid gap-1 col-6 mx-auto'>\n";
        $display_block .= "</P><div class='btn-group-vertical mt-2'>";
        foreach ($rows as $recs) {
            $initials = $recs['initials'];
            $staffname = stripslashes($recs['fullname']);
            $staffid = $recs['staffid'];
            $inactive = $recs['inactive'];
            $staffid_anchor = "sid" . $staffid;

            $num_supervised = CountSupervisedProjectsByID($db, $master_name, $staffid);
            $num_second_marked = CountSecondMarkedProjectsByID($db, $project_details, $staffid);
            if ($inactive == 0) {
                $display_block .= "<a id='$staffid_anchor' href='list_staff_projects.php?pm_id=$pm_id&stid=$staffid' class='btn btn-primary'>$staffname [$num_supervised]</a>\n";
            }
        }
        $display_block .= "</div>\n";
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

    <script type="text/javascript">
        $(document).ready(function () {
            toggle_zero_project_links();
        });

        function toggle_zero_project_links() {
            $("a:contains('[0]')").each(function () {
                $(this).toggle();
            });
        }
    </script>

    <?php echo $display_block; ?>
</div>

</body>
</html>
