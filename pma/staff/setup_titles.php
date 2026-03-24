<?php
$DB_ADMIN_USER_VAR = DB_ADMIN_USER;

define("__BASE_DIR__", BASE_DIR);
$hostname = DB_SERVER;
$dbName = DB_NAME;
$dbusername = DB_ADMIN_USER;
$password = DB_ADMIN_PASS;

$num_cookie_days = 30;
setcookie('BASE_DIR',__BASE_DIR__,time() + (86400 * $num_cookie_days), "/");
setcookie('ADMIN_DIR',__ADMIN_DIR__,time() + (86400 * $num_cookie_days), "/");
setcookie('STAFF_DIR',__STAFF_DIR__,time() + (86400 * $num_cookie_days), "/");
setcookie('STUDENT_DIR',__STUDENT_DIR__,time() + (86400 * $num_cookie_days), "/");
setcookie('PROPS_DIR',__PROPS_DIR__,time() + (86400 * $num_cookie_days), "/");

if($debug){
    echo '<pre>';
    echo 'setup_titles<BR>';
    echo '$_COOKIE<BR>';
    print_r($_COOKIE);
    echo '</pre>';
}
$setup_titles = "setup_titles.php";

date_default_timezone_set('Europe/London');

if (empty($db)) {
    require(__STAFF_DIR__ . '/connect_pdo.php');
    //require('./connect_pdo.php');
}

// Needed later
$mobile_head = "mobile_head.php";
$desktop_head = "desktop_head.php";
$mobile_head_bs = "mobile_head_bs.php";
$mobile_head_admin_bs = "mobile_head_admin_bs.php";
$desktop_head_bs = "desktop_head_bs.php";

$connect_msg = "";

// Does the definition table exist?
$table_name = "definition";
try {
    // this will raise an exception if the table does not exist
    $query = "DESCRIBE $table_name";
    $db->query($query);

    //echo "$table_name exists in database $dbName <BR>\n";
    $query = "SELECT * from $table_name LIMIT 1"; // Only want the first (there should only be one!)
    $setup = $db->prepare($query);
    $setup->execute();
    $result = $setup->fetch();

    $id = $result['id'];
    $school = $result['school_short_name'];
    $header_image = $result['header_image_name'];
    $header_image_id = $result['header_image_id'];
    $school_title = $result['school_title'];
    $school_module_prefix = $result['default_module_prefix'];
    $running_title = $result['running_title'];
    $running_subtitle = "";//$result['running_subtitle'];
    //echo("School: $school<BR>\n");
    //echo("Header: $header_image<BR>\n");
    //echo("School title: $school_title<BR>\n");
    //echo("School module prefix: $school_module_prefix<BR>\n");
    //echo("Running title: $running_title<BR>\n");

} catch (PDOException $ex) {

}
