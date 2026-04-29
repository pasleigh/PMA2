<?php
// connect to the database
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) or die('Cannot connect to the database<BR>');
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) or die('Cannot setup database tables<BR>');

(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

if (array_key_exists('sid_s', $_REQUEST) == false) {
    $_REQUEST['sid_s'] = null;
}
if (array_key_exists('op', $_REQUEST) == false) {
    $_REQUEST['op'] = null;
}

$sid = (int)$_REQUEST['sid_s'];
$POST_OP = $_REQUEST['op'];


$display_block = '';
$display_block .= $connect_msg;

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = (int)$_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if ($is_module_admin || $admin_access) {
    $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

    $display_block .= "<h1>Select a student to add an adhoc (self defined) project</h1>
             <div align='left'>
             <table  class='tableSimple'> 
             <tr>
             <td>Select the student by SID</td>
             </tr>
             <tr>
             <td><b>SID:</b>&nbsp; ";

    $sid_select = "<select name='sid_s'>\n";

    $query = "select * from $students order by name";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $rows = $results->fetchAll();
    foreach ($rows as $recs) {
        $sid_s = $recs['sid'];
        $student_name = stripslashes($recs['name']);

        if ($sid_s == $sid) {
            $sid_select .= "<option value='$sid_s' selected='selected'>$student_name ($sid_s)</option>\n";
        } else {
            $sid_select .= "<option value='$sid_s'>$student_name ($sid_s)</option>\n";
        }
    }
    $sid_select .= '</select>';

    $display_block .= $sid_select;
    $display_block .= '&nbsp;&nbsp;</td></tr>';

    $display_block .= "<TR><td><input class='btn btn-primary' type='submit' name='submit' value='Select this student'></td></TR>\n";

    $display_block .= "\n<input type='hidden' name='op' value='selected'>";
    $display_block .= "</form>\n";

    $display_block .= "<HR>\n";

    if ($POST_OP == 'selected') {
        // add the student to the $project_allocated list if not already in it
        $query = "select count(SID) from $project_allocated where SID=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $get_allocated_res = $results->fetch();
        if ($get_allocated_res[0] == 0) {
            $query = "insert $project_allocated set SID='$sid'";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
        }

        $query = "select * from $project_allocated where SID=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $get_allocated_res = $results->fetch();
        $project_id = $get_allocated_res['projectid'];

        $query = "select * from $students where sid=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $recs = $results->fetch();

        $student_name = stripslashes($recs['name']);
        $display_block .= "<BR>Click this buton to modify/ allocate the project for the named student<BR>\n";
        $display_block .= "<A class='btn btn-success' href='../modify_student_project_details.php?pm_id=$pm_id&sid=$sid&pid=$project_id'>$student_name ($sid)</A>";
        $display_block .= "<BR><BR><HR>\n";
    }
} else {
    $display_block .= 'You do not have the appropriate admin level<BR>';
    $display_block .= "<A href='../index.php'>Go back here</A><BR>";
}
?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Create New Project Entry in the database</span>
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

