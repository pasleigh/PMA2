<?php

/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 19/02/2018
 * Time: 10:39
 */
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');

(@include_once('./ADMINconnect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot get markstore functions<BR>');
(@include_once('./utility_functions.php')) or die('Cannot get utility_functions<BR>');

if (isset($_POST['module_id'])) {
    $module_id = $_POST['module_id'];

    $query = "SELECT * FROM $projects_setup WHERE id='$module_id'";

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
    $modules = [];
    $ids = [];
    foreach ($rows as $recs) {
        $module = $recs['module'];
        $module_prefix = $recs['module_prefix'];
        $module_postfix = $recs['module_prostfix'];
        $module_code = $module_prefix . $module . $module_postfix;

        $module_short_desc = $recs['module_short_desc'];
        $short_code = $recs['short_code'];
        $year_begin = $recs['year_begin'];
        $year_end = $recs['year_end'];
        $my_key = "chk_id$count";
    }

    $module_data = ['module_code' => $module_code, 'module_short_desc' => $module_short_desc];

    echo json_encode($module_data);
} else {
    echo json_encode('Error message: Error, module id not set');
}
exit;
