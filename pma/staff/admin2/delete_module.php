<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./utility_functions.php")) OR die("Cannot read utility_functions file<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot read markstore_functions file<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot read staff_functions file<BR>");

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

$assess_part_description_text = "";

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db,$this_staff_username, $this_staff_full_name, $staff_names,$access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase );
if($is_module_admin){
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
    $access_phrase = "Although you have module level access, deletion of a module is restricted to system level admin users. <BR> Please contact a system level admin user who will be able to delete this (and all the students and mark data)  module for you";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if($admin_access) {

    $display_block .= "<h1>Be careful you are near to deleting a module entry </h1>";
    $display_block .= "<DIV><span style='font-size: x-large; color: black'>You appear to want to delete any record including all the data tables of: </span>";
    $display_block .= "<span style='font-size: x-large; color: red'>$module_short_description ($short_code) $module_prefix$module1 $year_begin</span></DIV><BR>";

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
    $POST_OP = $_POST['op'];
    
    // Compile an array of the tables in this project
    $project_tables = array();

    $index = 0;
    $project_tables[$index] = $students;
    $index++;
    $project_tables[$index] = $students_programmes;
    $index++;
    $project_tables[$index] = $selection;
    $index++;
    $project_tables[$index] = $project_alloc;
    $index++;
    $project_tables[$index] = $project_allocated;
    $index++;
    $project_tables[$index] = $project_allocated_staff;
    $index++;
    $project_tables[$index] = $project_allocated_staff_all;

    for ($i = 1; $i <= 5; $i++) {
        for ($j = 1; $j <= 3; $j++) {
            $index++;
            $project_tables[$index] = $markstores[$i][$j];
        }
        $index++;
        $project_tables[$index] = $assessqs_cats[$i];
        $index++;
        $project_tables[$index] = $assessqs[$i];
    }
    $index++;
    $project_tables[$index] = $markstore_totals;

    $index++;
    $project_tables[$index] = $master_name;
    $index++;
    $project_tables[$index] = $project_details;
    
    $index++;
    $project_tables[$index] = $staff_inactive;
    $index++;
    $project_tables[$index] = $staff_admin;

    $index++;
    $project_tables[$index] = $projects_areas;

    $display_block .= "<h2>These are the tables in this module that will be deleted:</h2>\n";
    foreach($project_tables as $table_name){
        $display_block .= "$table_name <BR>\n";
    }

    if ($POST_OP == "delete_project") {
        $display_block .= "<H1>Delete confirmation credentials</H1>\n";
        $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

        $display_block .= "Tick to confirm: ";
        $display_block .= "<input type='checkbox' name='confirm_delete'><br>\n";
        $display_block .= "\n<input type='hidden' name='op' value='delete_confirmed'>\n";
        $display_block .= "<p><input class='btn btn-danger' type='submit' id='submitbutton' name='submit' value='I confirm that I wish to delete all these tables - I KNOW THAT I CANNOT GET THEM BACK.'></p>\n";
        $display_block .= "</form>\n";

    }elseif($POST_OP == "delete_confirmed"){
        //$display_block .= "Delete confirmed<BR>\n";
        $confirm_delete = false;
        if (array_key_exists('confirm_delete', $_POST) == true ){ $confirm_delete = true; }
        // Check against the logged in values
            $display_block .= "Username is OK<BR>\n";
            if( $confirm_delete)
            {
                $display_block .= "<BR><h2>Delete confirmed</h2>\n";
                $display_block .= "Check Confirm delete OK<BR>\n";
                $display_block .= "let's delete<BR>\n";

                // Delete all tables:
                $display_block .= "<h2>These are the tables in this module that will be deleted:</h2>\n";
                foreach($project_tables as $table_name){
                    $display_block .= "<span style='font-size: large; color: black'>$table_name: </span>";
                    $query = "DROP TABLE $table_name";
                    try {
                        $results = $db->query($query);
                        $display_block .= "<span style='font-size: large; color: red'> deleted </span><BR>";
                    } catch (PDOException $ex) {
                        $display_block .= " Err message: " . $ex->getMessage() . "<BR>\n";
                    }
                }
                // delete entry from the $projects_setup table
                //$query = "DELETE FROM $projects_setup WHERE short_code='$short_code'";
                $query = "DELETE FROM $projects_setup WHERE id='$pm_id'";
                $display_block .= "<span style='font-size: large; color: black'>Projects setup table entry: </span>";
                try {
                    $results = $db->query($query);
                    $display_block .= "<span style='font-size: large; color: red'> deleted </span><BR>";
                } catch (PDOException $ex) {
                    $display_block .= " Query: $query<BR>\n";
                    $display_block .= " Err message: " . $ex->getMessage() . "<BR>\n";
                }

                $query = "DELETE FROM $projects_setup_proposals WHERE proj_id='$pm_id'";
                $display_block .= "<span style='font-size: large; color: black'>Projects setup proposals table entry: </span>";
                try {
                    $results = $db->query($query);
                    $display_block .= "<span style='font-size: large; color: red'> deleted </span><BR>";
                } catch (PDOException $ex) {
                    $display_block .= " Query: $query<BR>\n";
                    $display_block .= " Err message: " . $ex->getMessage() . "<BR>\n";
                }

            }else{
                $display_block .= "<BR><h2>Delete cancelled</h2>\n";
                $display_block .= "<BR><B>The confirmation of delete was not checked. No tables were deleted</B><BR><BR>";
            }

    }else{
        $display_block .= "\n<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";
        $display_block .= "\n<input type='hidden' name='op' value='delete_project'>";
        $display_block .= "<p><input class='btn btn-danger' type='submit' id='submitbutton' name='submit' value='Delete the tables in the project - FOR EVER.'></p>";
        $display_block .= "</form>\n";
    }

} else {
    //$display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href='../index.php'>Go back here</A><BR>";
}

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Delete module - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../projectmarking_select_bs.php?pm_id=<?php echo("$pm_id") ?>" class="nav-link px-2 text-white-50">Module page</a></li>
            <li class="nav-item"><a target="_self" href="../session_modules_bs.php?year_begin= <?php echo("$year_begin") ?>" class="nav-link px-2 text-white-50">This year module list</a></li>
            <li class="nav-item"><a target="_self" href="./edit_module_settings.php?pm_id=<?php echo("$pm_id") ?>" class="nav-link px-2 text-white-50">Module admin page</a></li>
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Main admin page</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
        <?php echo $display_block; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
</html>
