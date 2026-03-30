<?php
(@include_once("./set_dir_student.php")) OR die("Cannot find this file to include: set_dir_student.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./project_display_functions.php")) OR die("Cannot include project_display_functions.php<BR>");
(@include_once("$STAFF_DIR_VAL/staff_functions.php")) OR die("Cannot include staff_functions.php<BR>");
(@include_once("./student_functions.php")) OR die("Cannot include student_function.php<BR>");

// Select the years available in the projects setup table
$year_end_short = $year_end - 2000;
$display_block = "";
$display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center'>Project Selection</div>";
$display_block .= "<div style='font-size: large; font-weight: bold; text-align: center'>$module_short_description - $module_prefix$module1$module_postfix - $short_code, $year_begin/$year_end_short</div>";

//
// Display the list of staff
//
$student_username = get_server_username();
$is_staff = isStaff($db, $student_username);
$is_student_current = false;
if($is_staff == false) {
    $is_student_current = isStudent_on_this_module($db, $pm_id, $student_username, $students);
}

if($is_student_current == false and $is_staff == false){
    $display_block .= GetStudentNotOnModulePhrase();
}else {

    $staff_text = "";
    if ($is_staff) {
        $staff_text = "(staff)";
        $student_name = GetStaffNameFromUsername($db, $student_username, $staff_names_default, null);
    } else {
        $student_name = GetStudentNameFromUsername($db, $student_username, $students);
    }

    $display_block .= "<div>You are $student_name ($student_username)</div>";
    $count_modules = 0;

    $display_block .= "<h3>List of $module_short_description projects listed by staff name</h3>\n";

    $query = "select * from $staff_names order by familyname, firstname ";
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

    $display_block .= "<button class='btn btn-outline-secondary btn-sm mb-2' onclick='toggle_zero_project_links();'>Toggle staff with zero projects</button>";

    if ($results->rowCount() < 1) {
        //no records
        $display_block .= "<p><em>Sorry, there are no staff names in the database!</em></p>";
    } else {
        //has records
        $display_block .= "<div class='text-center'><div class='btn-group-vertical mt-2'>";
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
        $display_block .= "</div></div>\n";
    }
}
?>

<!DOCTYPE HTML>
<html>
<?php (@include_once("./mobile_head_bs.php")) OR die("Cannot find this file to include: mobile_head_bs.php<BR>"); ?>
<body class="bg-light text-dark">

<nav class="navbar navbar-expand-sm fixed-top navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="index.php"><?php echo("$school_title $running_title"); ?></a>
        <div class="navbar-brand ms-auto">
            <a class="navbar-brand" href="projects_indexm_all.php?pm_id=<?php echo($pm_id); ?>"><i class="bi bi-arrow-left"></i> Module index</a>
        </div>
    </div>
</nav>

<div class="container bg-light text-dark">
    <div class="text-center props-logo mt-0">
        <img src="../staff/images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>
    <hr>
    <div class="text-center">
        <?php echo $display_block; ?>
    </div>
</div>

<script>
    $(document).ready(function () {
        toggle_zero_project_links();
    });

    function toggle_zero_project_links()
    {
        $("a:contains('[0]')").each(function () {
            $(this).toggle();
        });
    }
</script>
</body>
</html>
