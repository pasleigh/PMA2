<?php
// Turn on/off all error reporting
error_reporting(E_ALL);
(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("./setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("./setup_tables.php")) OR die("Cannot setup database tables<BR>");

(@include_once("./staff_functions.php")) OR die("Cannot include staff_functions.php<BR>");
(@include_once("./markstore_functions.php")) OR die("Cannot include markstore_functions.php<BR>");
(@include_once("./admin2/markstore_functions_admin.php")) OR die("Cannot include markstore_functions_admin.php<BR>");
(@include_once("./admin2/admin_functions.php")) OR die("Cannot include admin functions<BR>");

/*
foreach ($_REQUEST as $key => $value){
    echo "{$key} = {$value}<BR>\r\n";
}
*/
if (array_key_exists('sid', $_REQUEST) == false) {
    $_REQUEST['sid'] = null;
}
$sid = $_REQUEST["sid"];

$query = "select * from $project_allocated where sid=$sid";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$num = $results->rowCount();

// if it does not exist then create an empty entry and carry on
if ($num == 0) {
    $query = "insert $project_allocated set SID='$sid'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $query = "select * from $project_allocated where sid=$sid";
    echo("query = $query<BR>");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
}
$recs = $results->fetch();
$project_id = $recs['projectid'];
$sid = $recs['sid'];
$project_title = $recs['title'];
$project_master_id = $recs['masterid'];
$supervisor_id = $recs['supervisor_id'];
$second_marker_id = $recs['second_marker_id'];
$markers = array();
$markers_id = array();
for ($i = 1; $i <= 5; $i++) {
    $markers[$i] = array();
    $markers_id[$i] = array();
    for ($j = 1; $j <= 3; $j++) {
        $marker_entry = "marker_p$i" . "m$j" . "_id";
        $marker_id = $recs[$marker_entry];
        $markers_id[$i][$j] = $marker_id;
        $markers[$i][$j] = GetStaffNameFromID($db,$marker_id, $staff2, NULL);
        //$name = $markers_id[$i][$j];
    }
}
$inactive = $recs['inactive'];

// This is in the $markstore_totals table
$query = "select * from $markstore_totals where sid=$sid";
//$get_student_list_res = mysql_query($get_student_list) or die(" Unable to create student list from $markstore_totals table: <BR>$get_student_list");
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$num = $results->rowCount();
$locked_status = 0;
$third_marker_part = array();
//if ($num > 0) {
//    ;
    $recs = $results->fetch();
    if($recs) {
        $locked_status = $recs['locked'];
        for ($i = 1; $i <= 5; $i++) {
            $field = "third_marker_req$i";
            $third_marker_part[$i] = $recs[$field];;
        }
    }
//}

// Get the student name from SID
$query = "select * from $students where sid = $sid";
//$get_list_res = mysql_query($get_list) or die(" Unable to create list from $students table with query: $get_list");
//$recs = mysql_fetch_array($get_list_res);
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$recs = $results->fetch();
$student_name = $recs['name'];
$username = $recs['username'];
$programme = $recs['programme'];
$sel = $recs['sel'];

if (array_key_exists('supervisor_s', $_POST) == false) {
    $_POST['supervisor_s'] = null;
}
if (array_key_exists('chk_supervisor_all', $_POST) == false) {
    $_POST['chk_supervisor_all'] = null;
}
if (array_key_exists('second_marker_s', $_POST) == false) {
    $_POST['second_marker_s'] = null;
}
if (array_key_exists('chk_second_marker_all', $_POST) == false) {
    $_POST['chk_second_marker_all'] = null;
}
if (array_key_exists('chkInActive', $_POST) == false) {
    $_POST['chkInActive'] = null;
}
if (array_key_exists('chkLocked', $_POST) == false) {
    $_POST['chkLocked'] = null;
}
if (array_key_exists('txtNewTitle', $_POST) == false) {
    $_POST['txtNewTitle'] = null;
}

$new_second_marker_id = $_POST['second_marker_s'];
$second_marker_name = GetStaffNameFromID($db, $new_second_marker_id, $staff2, false);
$new_supervisor_id = $_POST['supervisor_s'];
$new_supervisor_name = GetStaffNameFromID($db, $new_supervisor_id, $staff2, false);
$chkInActive_status = $_POST['chkInActive'];
$chkLocked_status = $_POST['chkLocked'];
$txtNewTitle = $_POST['txtNewTitle'];
$chkSupervisorAll = $_POST['chk_supervisor_all'];
$chkSecondMarkerAll = $_POST['chk_second_marker_all'];
//if ( $chkLocked_status == null ){
//}else{
//}

$changed_message = "";

if ($new_supervisor_id != null) {
    // update the table of supervisor for this project
    $query = "update $project_allocated set supervisor_id='$new_supervisor_id' where sid = $sid";
    //mysql_query($mod_details) or die(" Unable to modify data in the $project_allocated table.<BR>Query: $mod_details");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $changed_message .= "Supervisor changed to $new_supervisor_id ($new_supervisor_name)<BR>";
} else {
    $new_supervisor_id = $supervisor_id;
}

if ($new_second_marker_id != null) {
    // update the table of second marker for this student
    $query = "update $project_allocated set second_marker_id='$new_second_marker_id' where sid = $sid";
    //mysql_query($mod_details) or die(" Unable to modify data in the $project_allocated table.<BR>Query: $mod_details");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $changed_message .= "Second marker changed to $new_second_marker_id ($second_marker_name)<BR>";
} else {
    $new_second_marker_id = $second_marker_id;
}

// Change the inactive status
if ($chkInActive_status != null) {
    if ($chkInActive_status == "on") {
        $inactive = 1;
        $changed_message .= "Changing inactive status to INACTIVE<br>\n";
    }
} else {
    if ($inactive == 1) {
        $inactive = 0;
        $changed_message .= "Changing inactive status to ACTIVE<br>\n";
    }
}
$query = "update $project_allocated set inactive=$inactive where sid = $sid";
//mysql_query($mod_details) or die(" Unable to modify the (in)active status in the $project_allocated table.<BR>Query: $mod_details");
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}

// Change the locked status
$locked_checked_status = "";
if ($chkLocked_status != null) {
    $locked = 0;
    if ($chkLocked_status == "on") {
        $locked = 1;
        $locked_checked_status = "checked";
    }
    $changed_message .= "Changing locked status<br>\n";
    SetLockedStatusOverall($sid, $locked, $markstore_totals);
    //$mod_details = "update $markstore_totals set locked=$locked where sid = $sid";
    //mysql_query($mod_details) or die(" Unable to modify the locked status in the $markstore_status table.<BR>Query: $mod_details");
}

// If the title has changed update it
if ($txtNewTitle != null) {
    if ($txtNewTitle != $project_title) {
        $changed_message .= "Updating title with : $txtNewTitle<BR>\n";
        $query = "update $project_allocated set title='$txtNewTitle' where sid = $sid";
        //mysql_query($mod_details) or die(" Unable to modify the title in the $project_allocated table. <BR>Query: $mod_details");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $project_title = $txtNewTitle;
    }
}

// Change the markers 
for ($i = 1; $i <= $num_parts; $i++) {
    for ($j = 1; $j <= 3; $j++) {
        $marker_select_id = "marker_p$i" . "m$j" . "_id";
        if (array_key_exists($marker_select_id, $_POST)) {
            if (($j == 1) && ($chkSupervisorAll)) {
                $marker_id = $new_supervisor_id;
            } elseif (($j == 2) && ($chkSecondMarkerAll)) {
                $marker_id = $new_second_marker_id;
            } else {
                $marker_id = $_POST[$marker_select_id];
            }
            $changed_message .= "Updating marker $j of part $i to $marker_id " . GetStaffNameFromID($db, $marker_id, $staff2, false) . "<BR>\n";
            $query = "update $project_allocated set $marker_select_id='$marker_id' where sid = $sid";
            //mysql_query($mod_details) or die(" Unable to modify the title in the $project_allocated table. <BR>Query: $mod_details");
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
        }
    }
}

// Change the locked status - each marker will have the same locked state

for ($i = 1; $i <= $num_parts; $i++) {

    $locked_part_id = "locked_p$i" . "_id";
    $is_part_locked = 0;

    if (isset($_POST[$locked_part_id])) {
        $is_part_locked = 1;
    }
    $changed_message .= "Updating locked status: all markers of part $i to $is_part_locked <BR>\n";
    SetLockedStatusForPart($db, $sid, $is_part_locked, $i, $markstores);

}

// Change the third marker required status

for ($i = 1; $i <= $num_parts; $i++) {
    $third_marker_part_id = "third_marker_p$i" . "_id";
    $is_third_marker_required_for_part = 0;

    if (isset($_POST[$third_marker_part_id])) {
        $is_third_marker_required_for_part = 1;
    }
    $changed_message .= "Updating 'third marker required' status of part $i to $is_third_marker_required_for_part <BR>\n";
    SetThirdMarkerStatusForPart($db, $sid, $is_third_marker_required_for_part, $i, $markstore_totals);

    // update the third marker array
    $third_marker_part[$i] = $is_third_marker_required_for_part;
}

//for($j=1;$j<=3;$j++){
//        // Same for each marker
//        $locked_part_id = "locked_p$i"."_id";
//        $is_part_locked = 0;
//        if (isset($_POST[$locked_part_id])){
//            $is_part_locked = 1;
//            $this_markstore = $markstores[$i][$j];
//            //$locked_id = $is_part_locked;
//            $changed_message .= "Updating locked status: marker $j of part $i to $is_part_locked <BR>\n";
//
//            //$mod_details = "update $this_markstore set locked='$is_part_locked' where sid = $sid";
//            //mysql_query($mod_details) or die(" Unable to modify the title in the $this_markstore table. <BR>Query: $mod_details");
//        }
//    }
//}

// May have changed this so recalc from the database
$query = "select * from $project_allocated where sid=$sid";
//$get_student_list_res = mysql_query($get_student_list) or die(" Unable to create student list from Project Alloc table: $project_alloc with query: $get_student_list");
//$recs = mysql_fetch_array($get_student_list_res);
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    $this_function = __FUNCTION__;
    echo "An Error occured accessing the database in function: $this_function <BR>\n";
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";

    exit();
}
$recs = $results->fetch();
$project_id = $recs['projectid'];
$sid = $recs['sid'];
$project_title = $recs['title'];
$project_master_id = $recs['masterid'];
$supervisor_id = $recs['supervisor_id'];
$second_marker_id = $recs['second_marker_id'];
$markers = array();
$markers_id = array();

for ($i = 1; $i <= 5; $i++) {
    $markers[$i] = array();
    $markers_id[$i] = array();
    for ($j = 1; $j <= 3; $j++) {
        $marker_entry = "marker_p$i" . "m$j" . "_id";
        $marker_id = $recs[$marker_entry];
        $markers_id[$i][$j] = $marker_id;
        $markers[$i][$j] = GetStaffNameFromID($db, $marker_id, $staff2, NULL);
        //$name = $markers_id[$i][$j];
    }
}

$locked_part = array();
for ($i = 1; $i <= 5; $i++) {
    $locked_part[$i] = 0;
    $marker_index = 1; // just do 1st as otehrs will be the same
    $locked_part[$i] = GetLockedStatusForMarker($db, $sid, $i, $marker_index, $markstores);

    /*
        $this_markstore = $markstores[$i][1];
        $query = "select * from $this_markstore where sid=$sid";
        //$get_markstore_list_res = mysql_query($get_markstore_list) or die(" Unable to create mark list from markstore table: $this_markstore with query: $get_markstore_list");
        //$recs = mysql_fetch_array($get_markstore_list_res);
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $recs = $results->fetch();
        if (isset($recs['locked'])) {
            $locked_part[$i] = $recs['locked'];
        }
    */
}

$inactive = $recs['inactive'];

// Check inactive
$inactive_status = "active";
$checked_status = "";
if ($inactive == 1) {
    $inactive_status = "inactive";
    $checked_status = "checked";
}

$instructions = "<h3>This form is for amending project details for an individual student</h3>";
$instructions .= "<div class='feedbackInstructions'>Select the new second marker, change the title or 'unlock' the project for
revision of marking ... and then click the modify button <br />
    </div>";

$display_block = $changed_message;
//$display_block .= $instructions;
$display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id&sid=$sid'>\n";

$display_block .= "<h1>$module_prefix$module1 Projects: Individual Student Details</h1>
             <div align='left'>";

$display_block .= "<table class='tableSimple' >";

$display_block .= "<tr><td colspan='2'>$instructions</td</tr>";
$display_block .= "<tr><td colspan='2'>";
$display_block .= "<span><a class='btn btn-primary' target='_self' href='./projectmarking_bs.php?pm_id=$pm_id&modify=1'> Go back to Marking Sheet (with modify functions)</a></span>";
$display_block .= "</tr></td>";
$display_block .= "<tr><td colspan='2'>";
$display_block .= "<input class='btn btn-info' type='submit' name='submit' value='Submit the modified data'>";
$display_block .= "</tr></td>";

$display_block .= "<tr><td><b>Student:</b></td><td>$student_name&nbsp;&nbsp;</td></tr>";

$display_block .= "<tr><td><b>Supervisor:</b></td><td>";
$display_block .= GetStaffSelectID($db, $staff2, "supervisor_s", $new_supervisor_id);
$display_block .= "use for all supervisor fields <INPUT TYPE='CHECKBOX' NAME='chk_supervisor_all' >";
$display_block .= "</td></tr>";

$display_block .= "<tr><td><b>2nd Marker:</b></td><td>";
$display_block .= GetStaffSelectID($db, $staff2, "second_marker_s", $new_second_marker_id);
$display_block .= "use for all second markerfields <INPUT TYPE='CHECKBOX' NAME='chk_second_marker_all' >";
$display_block .= "</td></tr>";

$display_block .= "<tr><td><b>Programme:</b></td><td> $programme&nbsp;&nbsp;</td></tr>";

$display_block .= "<tr><td><b>Title:</b>&nbsp; <i>$project_title</i></td><td> ";
$display_block .= "New title: <input type='text' size='110' name='txtNewTitle' value='$project_title'></td></tr>";

$display_block .= "<tr><td><b>Project ID:</b></td><td><i>$project_id</i></td></tr>";

$display_block .= "<tr><td><b>Student active status:</b>&nbsp; <i>$inactive_status</i></td><td>";
$display_block .= "Toggle inactive state <INPUT TYPE='CHECKBOX' NAME='chkInActive'' $checked_status> (It would NORMALLY be unchecked.)</td></tr>";

$display_block .= "<tr><td><b>Student locked marking status:</b>&nbsp; <i>Locked status</i></td><td>";
$display_block .= "change (toggle)<INPUT TYPE='CHECKBOX' NAME='chkLocked'' $locked_checked_status> (This would NORMALLY be unchecked.)</td></tr>";

// Do all the parts locked status
$display_block .= "<TR><td><b>Set the locked status for all parts.</b></td><td>(This will lock / unlock all three markers.) [You can only lock part already marked]</td><tr>";

for ($i = 1; $i <= $num_parts; $i++) {
    $display_block .= "<TR><td>Locked status for Part $i: </td>";
    $checked_status = "";
    if ($locked_part[$i] == 1) {
        $checked_status = "checked";
    }
    $locked_part_id = "locked_p$i" . "_id";
    $display_block .= "<td>change (toggle)<INPUT TYPE='CHECKBOX' NAME='$locked_part_id' $checked_status>$locked_checked_status</td><tr>";
}

// Do all the parts markers 1, 2, 3
$display_block .= "<TR><td><b>Select markers for each assessment part.</b></td><td>(It is not necessary to have all three marker, if three markers are not being used.)</td><tr>";

for ($i = 1; $i <= $num_parts; $i++) {
    $display_block .= "<TR><td><b>Marker for Part $i: </B></td><td><b>$p_description[$i]</b></td><tr>";

    for ($j = 1; $j <= 3; $j++) {
        $marker_name = $markers[$i][$j];
        $marker_id = $markers_id[$i][$j];
        $marker_select_id = "marker_p$i" . "m$j" . "_id";
        if ($marker_id <= 0) {
            $marker_name = "Not set.";
            $marker_id = -1;
        }
        $display_block .= "<TR><td>&nbsp;Marker $j: $marker_name ($marker_id) </td><td>" . GetStaffSelectID($db, $staff2, $marker_select_id, $marker_id) . "</td><tr>";
    }
}

// Do all the parts third marker required status
// Only allow to toggle off (not to switch on)
$display_block .= "<TR><td><b>Set 'third marker required' flags as necessary.</b></td><td></td><tr>";

for ($i = 1; $i <= $num_parts; $i++) {
    $display_block .= "<TR><td>Third marker status part $i: </td>";
    $checked_status = "";
    $third_marker_status_text = "";
    if ($third_marker_part[$i] == 1) {
        $checked_status = "checked";
        $third_marker_status_text = "Third marker required";
    }
    $third_marker_part_id = "third_marker_p$i" . "_id";
    $display_block .= "<td>change (toggle)<INPUT TYPE='CHECKBOX' NAME='$third_marker_part_id' $checked_status> $third_marker_status_text</td><tr>";
    //else {
    //    $display_block .= "<td>Not required<input type='hidden' name='$third_marker_part_id' value='0'></td><tr>";
    //}
}

$display_block .= "<TR><td colspan='2'><input class='btn btn-info' type='submit' name='submit' value='Submit the modified data'>
                                  </form></td></TR>";

//mysql_close();
$admin_link_text = "";
$show_admin_link = "none";
$admin_link = "";
if (IsAdmin($db, $this_staff_username)) {
    $admin_link = "./admin2/";
    $admin_link_text = "Super Admin";
    $show_admin_link = "block";
} else if (IsModuleAdmin($db, $this_staff_username, 'all', null, null)) {
    $admin_link = "./admin2/";
    $admin_link_text = "Admin";
    $show_admin_link = "block";
}
?>
<!DOCTYPE HTML>
<html>
<?php (@include_once("$mobile_head_bs")) OR die("Cannot find this file to include: $mobile_head_bs<BR>"); ?>
<!-- jQuery UI needed for staff selection autocomplete -->
<link rel="stylesheet" href="https://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css"/>
<script src="https://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Modify individual project details :- <?PHP echo("$module_short_description  $module_prefix$module1  $year_begin/$year_end") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./projectmarking_bs.php<?PHP echo("?pm_id=$pm_id&modify=1"); ?>" class="nav-link px-2 text-white-50">Marking sheet</a></li>
            <li class="nav-item"><a target="_self" href="./projectmarking_select_bs.php<?PHP echo("?pm_id=$pm_id"); ?>" class="nav-link px-2 text-white-50"><?PHP echo("$module_prefix$module1 index"); ?></a></li>
            <li class="nav-item"><a target="_self" href="./admin2/index.php<?PHP echo("?pm_id=$pm_id"); ?>" class="nav-link px-2 text-white-50">Admin index</a></li>
            <li class="nav-item"><a target="_self" href="./admin2/adhoc_student_project_allocation.php<?PHP echo("?pm_id=$pm_id"); ?>" class="nav-link px-2 text-white-50">Modify Student/Project</a></li>
        </ul>
        <div class="navbar-brand ms-auto" style="display: <?php echo($show_admin_link);?>">
            <a target='_top' class="navbar-brand" href='<?php echo("$admin_link"); ?>'>
                <i class="bi bi-gear"></i>
                <?php echo("$admin_link_text"); ?>
            </a>
        </div>
    </div>
</nav>
<div id="wrapper">

    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class='card'>
                    
                    <div class="card-body">

                <?php echo("$display_block"); ?>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- content -->
</div><!-- /wrapper -->
</body>

</HTML>

