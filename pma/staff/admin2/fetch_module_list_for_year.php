<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 19/02/2018
 * Time: 10:39
 */

(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("$STAFF_DIR_VAL/markstore_functions.php")) OR die("Cannot get markstore functions<BR>");
(@include_once("./utility_functions.php")) OR die("Cannot get utility_functions<BR>");

if(isset($_POST['get_year_begin'])) {
    $year_begin = $_POST['get_year_begin'];
    $year_end = $_POST['get_year_end'];

    $query = "SELECT * FROM $projects_setup WHERE year_begin='$year_begin'";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }
    $count = 0;
    $rows = $results->fetchAll();
    $module_allocation_tables = array();
    $modules = array();
    $ids = array();
    $modules_desc = array();
    $staff_id_name = array();
    $staff_supervision = array();
    foreach ($rows as $recs) {
        $id = $recs['id'];
        $module = $recs['module'];
        $module_prefix = $recs['module_prefix'];
        $module_short_desc = $recs['module_short_desc'];
        $short_code = $recs['short_code'];
        $short_code_lc = strtolower($short_code);
        $my_key = "chk_id$count";

        $prefix = "";
        $module_allocated_table = $prefix . "allocated_" . $short_code_lc . "_" . $year_begin;

        $modules[] = "$module_prefix$module";// $module_short_desc: $short_code ";
        $modules_desc["$id"] = $module_short_desc;
        $module_allocation_tables[] = $module_allocated_table ;
        $ids[] = $id;
        $count++;
    }

    // Loop through staff list
    // Get the list of staff from the staff_names2 table
    $query = "select * from $staff_names order by familyname";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $staff_count = 0;
    $rows = $results->fetchAll();
    foreach ($rows as $recs) {
        $staffid = $recs['staffid'];
        $initials = $recs['initials'];
        $title = $recs['title'];
        $firstname = stripslashes($recs['firstname']);
        $familyname = stripslashes($recs['familyname']);
        $fulllname = stripslashes($recs['fullname']);
        $email = stripslashes($recs['email']);
        $username = stripslashes($recs['username']);
        $room = stripslashes($recs['room']);
        $inactive = stripslashes($recs['inactive']);
        $mod_admin_level = stripslashes($recs['admin_level']);
        $staff_count++;

        $staff_id_name["$staffid"] = array($fulllname, $familyname, $firstname, $inactive);
        // Now get all of the super and second supervisions that this staff is doing on each module
        for ($i = 0; $i < sizeof($ids); $i++) {
            $module_id = $ids[$i];
            $module_allocated_table = $module_allocation_tables[$i];
            // count the supervising and second marking for this staff on this module
            // supervisors
            $query = "SELECT * FROM $module_allocated_table WHERE supervisor_id=$staffid";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            $supervisor_count = $results->rowCount();
            // Second markers
            $query = "SELECT * FROM $module_allocated_table WHERE second_marker_id=$staffid";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            $second_marker_count = $results->rowCount();
            $staff_supervision["$staffid"]["$module_id"] = array($supervisor_count, $second_marker_count);
        }

    }
    $module_data = array('modules'=>$modules, 'ids'=>$ids, 'modules_desc'=>$modules_desc, 'staff_id_name'=>$staff_id_name, 'staff_supervision'=>$staff_supervision);

    echo json_encode($module_data);

}
exit;