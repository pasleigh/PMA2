<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 19/02/2018
 * Time: 10:39
 */

(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("./ADMINconnect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot get markstore functions<BR>");
(@include_once("./utility_functions.php")) OR die("Cannot get utility_functions<BR>");

if(isset($_POST['module_id'])) {
    $module_id = $_POST['module_id'];

    $query = "SELECT * FROM $projects_setup WHERE id='$module_id'";

    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function: $this_function <BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");

        exit();
    }
    $count = 0;
    $rows = $results->fetchAll();
    $modules = array();
    $ids = array();
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

    $module_data = array('module_code'=>$module_code, 'module_short_desc'=>$module_short_desc);

    echo json_encode($module_data);

}else{
    echo json_encode("Error message: Error, module id not set");
}
exit;