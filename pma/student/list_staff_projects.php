<?php
(@include_once("./set_dir_student.php")) OR die("Cannot find this file to include: set_dir_student.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./project_display_functions.php")) OR die("Cannot include project_display_functions.php<BR>");
(@include_once("$STAFF_DIR_VAL/staff_functions.php")) OR die("Cannot include staff_functions.php<BR>");
(@include_once("./student_functions.php")) OR die("Cannot include student_function.php<BR>");

if (array_key_exists('stid', $_REQUEST) == false) {
    $_REQUEST['stid'] = null;
}
$stid = $_REQUEST['stid'];
$staffid = $stid;

if (array_key_exists('prog_var', $_REQUEST) == false) {
    $_REQUEST['prog_var'] = -1;
}
$prog_var = $_REQUEST['prog_var'];

$student_username = get_server_username();
$is_staff = isStaff($db, $student_username);
$is_student_current = false;
if($is_staff == false) {
    $is_student_current = isStudent_on_this_module($db, $pm_id, $student_username, $students);
}

// Select the years available in the projects setup table
$year_end_short = $year_end - 2000;
$display_block = "";
$display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center'>Project Selection</div>";
$display_block .= "<div style='font-size: large; font-weight: bold; text-align: center'>$module_short_description - $module_prefix$module1$module_postfix - $short_code, $year_begin/$year_end_short</div>";

if($is_student_current == false and $is_staff == false){
    $display_block .= GetStudentNotOnModulePhrase();
}else {
//
// Display the list of projects for this  staff
//
    $student_username = get_server_username();
    $is_staff = isStaff($db, $student_username);

    $staff_text = "";
    if ($is_staff) {
        $staff_text = "(staff)";
        $student_name = GetStaffNameFromUsername($db, $student_username, $staff_names_default, null);
    } else {
        $student_name = GetStudentNameFromUsername($db, $student_username, $students);
    }

    $display_block .= "<div>You are $student_name ($student_username)</div>";
    $count_modules = 0;

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

        $display_block .= "<H3>Display project proposals by $staffname</H3>";
        $display_block .= "$module_ref_block";

        $display_block .= "<div class='text-center'><div class='btn-group-vertical mt-2'>";

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

            $display_block .= "<a href='viewproject2.php?pm_id=$pm_id&p_id=$master_id&prog_var=$prog_var' class='btn btn-primary'>$count: ($master_id) $project_title</a>\n";

            $count++;

        }
        $display_block .= "</div></div>\n";
    }

    $display_block .= "<div>\n";

    $display_block .= "</div>\n";

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
            <a class="navbar-brand" href="projects-list-mobile_staff.php?pm_id=<?php echo($pm_id); ?>"><i class="bi bi-arrow-left"></i> Staff list</a>
        </div>
    </div>
</nav>

<div class="container bg-light text-dark">
    <div class="text-center props-logo mt-0">
        <img src="../staff/images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>
    <hr>
    <div class="text-center">
        <?php echo("$display_block"); ?>
    </div>
</div>

</body>
</html>

