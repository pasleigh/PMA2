<?php
// connect to the datbase
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot include markstore_functions.php<BR>");

(@include_once("./markstore_functions_admin.php")) OR die("Cannot include markstore_functions_admin.php<BR>");
(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
//$access = false;
//$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
//$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

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
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if($is_module_admin || $admin_access) {

if (array_key_exists('chkInActive', $_POST)==false){ $_POST['chkInActive']=null; }
if (array_key_exists('chkLocked', $_POST)==false){ $_POST['chkLocked']=null; }
if (array_key_exists('chkLocked_p1', $_POST)==false){ $_POST['chkLocked_p1']=null; }
if (array_key_exists('chkLocked_p2', $_POST)==false){ $_POST['chkLocked_p2']=null; }
if (array_key_exists('chkLocked_p3', $_POST)==false){ $_POST['chkLocked_p3']=null; }
if (array_key_exists('chkLocked_p4', $_POST)==false){ $_POST['chkLocked_p4']=null; }
if (array_key_exists('chkLocked_p5', $_POST)==false){ $_POST['chkLocked_p5']=null; }

if (array_key_exists('op', $_POST)) {
    $POST_OP = $_POST['op']  ;
}else{
    $POST_OP = "";
}

// see all the POST values
/*
foreach ($_POST as $key => $value){
  echo "{$key} = {$value}<BR>\r\n";
}
*/
for ($i = 1; $i <= 5; $i++) {
    $chkLocked[$i] = 0;
}

if ($POST_OP == "commit") {
    // Use the check box values
    $chkLocked[0] = 0;
    if ($_POST['chkLocked'] == "1") {
        $chkLocked[0] = 1;
    }
    for ($i = 1; $i <= 5; $i++) {
        $chkLocked[$i] = 0;
        $key_text = "chkLocked_p$i";
        if ($_POST[$key_text] == "1") {
            $chkLocked[$i] = 1;
        }
    }
}else{
    // Not called from itself so need to check the database for what is set
    // Loop through all students and check if any NOT locked
    $chkLocked[0] = 1;
    $num = CountAllocationTable($db, $project_allocated);

    if($num>0) {
        $query = "select * from $project_allocated";
        //$get_student_list_res = mysql_query($get_student_list) or die(" Unable to create student list from Project Alloc table: $project_alloc with query: $get_student_list");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

        $chkLocked[0] = 1;
        $rows = $results->fetchAll();
        foreach ($rows as $record) {
            $sid = $record['sid'];
            $is_locked = GetLockedStatusOverall($db, $sid, $markstore_totals);
            if ($is_locked == 0) {
                // if ANY non-locked found then flag as not locked
                $chkLocked[0] = 0;
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            foreach ($rows as $record) {
                $sid = $record['sid'];
                for ($j = 1; $j <= 3; $j++) {
                    $is_marker_locked = GetLockedStatusForMarker($db, $sid, $i, $j, $markstores);
                    if ($is_marker_locked == 0) {
                        // if ANY non-locked found then flag as not locked
                        break;
                    }
                }
                if ($is_marker_locked == 0) {
                    break;
                }
            }
            $chkLocked[$i] = $is_marker_locked;
        }
    }
}
$changed_message = "";

if ($POST_OP == "commit") {
// Change the locked status for all students
    $changed_message .= "Updating locked status<BR>\n";

    $num = CountAllocationTable($db, $project_allocated);
    if($num > 0) {
        $query = "select * from $project_allocated";
        //$get_student_list_res = mysql_query($get_student_list) or die(" Unable to create student list from Project Alloc table: $project_alloc with query: $get_student_list");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $rows = $results->fetchAll();
        foreach ($rows as $record) {
            $sid = $record['sid'];

            // Change the locked status for all students
            SetLockedStatusOverall($db, $sid, $chkLocked[0], $markstore_totals);

            // Change the locked status - each marker will have the same locked state
            for ($i = 1; $i <= $num_parts; $i++) {
                SetLockedStatusForPart($db, $sid, $chkLocked[$i], $i, $markstores);
            }

        }
    }
}

$instructions= "<h3>This form is for setting the Overall locked status of a project as well as the Separate Parts</h3>";
$instructions .="<div class=\"feedbackInstructions\">If you want the programme to be locked you MUST select it.
If was previously locked and you DO NOT select it <strong>- it will be unlocked</strong>... <BR>and then click the modify button <br />
    </div>";

   $display_block .= $changed_message;

   $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

   $display_block .= "<h1>$module_prefix$module1 $module_short_description </h1>
             <div align=\"left\">\n";
             
   $display_block .= "<table class=\"tableSimple\" >\n";
   
   $display_block .= "<tr><td colspan=\"2\">$instructions</tr>\n";

   $display_block .= "<tr><td><b>Overall locked status:</b>&nbsp; <i>Locked status</i></td><td>";
   $display_block .= "change (toggle)<INPUT TYPE=\"CHECKBOX\" NAME=\"chkLocked\" VALUE=\"1\" > </td></tr>";

    // Do all the parts locked status
    $display_block .= "<TR><td><b>Set the locked status for all parts.</b></td><td>(This will lock / unlock all three markers.)</td><tr>\n";

    for($i=1;$i<=$num_parts;$i++){
        $display_block .= "<TR><td>Locked status for Part $i: </td>";
        $checked_status = "";
        if($chkLocked[$i]==1){
            $checked_status = "checked";
        }
        $locked_part_id = "chkLocked_p$i";
        $display_block .= "<td>change (toggle)<INPUT TYPE=\"CHECKBOX\" NAME=\"$locked_part_id\" VALUE=\"1\" $checked_status></td><tr>\n";
    }

    $display_block .="\n<input type=\"hidden\" name=\"op\" value=\"commit\">\n";

   $display_block .= "<TR><td colspan=\"2\"><input type=\"submit\" name=\"submit\" value=\"Modify\"></td></TR>\n
                                  </form>\n";

} else {
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
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
        <span class="navbar-brand">Modify individual project details :- <?PHP echo("$module_short_description  $module_prefix$module1  $year_begin/$year_end") ?></span>
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
      <?php echo("$display_block"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
</html>

