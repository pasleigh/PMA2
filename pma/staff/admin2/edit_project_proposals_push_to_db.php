<?php

/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 03/04/2018
 * Time: 10:46
 */
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');

(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('./utility_functions.php')) or die('Cannot read utility_functions file<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot read markstore_functions file<BR>');
(@include_once('../staff_functions.php')) or die('Cannot read staff_functions file<BR>');

(@include_once('../proposals/project_display_functions_staff.php')) or die('Cannot include project_display_functions_staff.php');
(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

//exit();

$errors = [];      // array to hold validation errors
$data = [];      // array to pass back data

// validate the variables ======================================================
// if any of these variables don't exist, add an error to our $errors array
// well just look for the projects_list
if (empty($_POST['projects_list'])) {
    $errors['projects_list'] = 'projects_list data is required.';
}
/*
if (empty($_POST['project_details'])){
    $errors['project_details'] = 'project_details name is required.';
}
if (empty($_POST['master_name'])){
    $errors['master_name'] = 'master_name name is required.';
}
*/
$project_list = json_decode($_POST['projects_list'], true);
//$project_details_table = $_POST['project_details'];
//$master_name_table = $_POST['master_name'];
$project_details_table = $project_details;
$master_name_table = $master_name;

// return a response ===========================================================
// if there are any errors in our errors array, return a success boolean of false
if (!empty($errors)) {
    // if there are items in our errors array, return those errors
    $data['success'] = false;
    $data['message'] = 'Failed to save project changes!';
    $data['errors'] = $errors;
} else {
    // if there are no errors process our form, then return a message

    for ($i = 0; $i < sizeof($project_list); $i++) {
        $this_project_details = $project_list[$i];

        $master_id = $this_project_details['master_id'];
        $title = $this_project_details['title'];
        $title = addslashes($title);
        $supervisor_name = $this_project_details['supervisor'];
        $supervisor_id = $this_project_details['supervisor_id'];
        //$supervisor_name = GetStaffNameFromID($db, $supervisor_id, $staff_names, false);
        $second_supervisor_name = $this_project_details['second_supervisor'];
        $second_supervisor_id = $this_project_details['second_supervisor_id'];
        //$second_supervisor_name = GetStaffNameFromID($db, $second_supervisor_id, $staff_names, false);
        $number_of_projects = $this_project_details['number_projects'];

        $prog_variant_str = $this_project_details['progs_str'];
        $delete_chk = intval($this_project_details['delete']);

        //$display_block .= "$master_id, $supervisor_id, $second_supervisor_name, $second_supervisor_id, $second_supervisor_name, $number_of_projects, $prog_variant_str, $delete_chk .... <BR>\n";
        try {
            if ($delete_chk == 0) {
                $stmt_details = $db->prepare("UPDATE $project_details_table SET f_assocstaff=?, f_assocstaff_id=?, f_PGprojects=?, f_progs=? WHERE master_id=?");
                $stmt_details->execute([$second_supervisor_name, $second_supervisor_id, $number_of_projects, $prog_variant_str, $master_id]);
                $stmt_master = $db->prepare("UPDATE $master_name_table SET f_title=?, f_supervisor=?, f_supervisor_id=? WHERE id=?");
                $stmt_master->execute([$title, $supervisor_name, $supervisor_id, $master_id]);
            } else {
                $stmt_details = $db->prepare("DELETE FROM $project_details_table WHERE master_id=?");
                $stmt_details->execute([$master_id]);
                $stmt_master = $db->prepare("DELETE FROM $master_name_table WHERE id=?");
                $stmt_master->execute([$master_id]);
            }
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
    }
}

// return all our data to an AJAX call
echo json_encode($data);
exit;
