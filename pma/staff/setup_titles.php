<?php
$DB_ADMIN_USER_VAR = DB_ADMIN_USER;

define("__BASE_DIR__", BASE_DIR);
$hostname = DB_SERVER;
$dbName = DB_NAME;
$dbusername = DB_ADMIN_USER;
$password = DB_ADMIN_PASS;

$num_cookie_days = 30;
$_cookie_opts = [
    'expires'  => time() + (86400 * $num_cookie_days),
    'path'     => '/',
    'secure'   => true,   // Requires HTTPS in production
    'httponly' => true,
    'samesite' => 'Lax',
];
//setcookie('BASE_DIR',    __BASE_DIR__,    $_cookie_opts);
//setcookie('ADMIN_DIR',   __ADMIN_DIR__,   $_cookie_opts);
//setcookie('STAFF_DIR',   __STAFF_DIR__,   $_cookie_opts);
//setcookie('STUDENT_DIR', __STUDENT_DIR__, $_cookie_opts);
//setcookie('PROPS_DIR',   __PROPS_DIR__,   $_cookie_opts);


$setup_titles = "setup_titles.php";

date_default_timezone_set('Europe/London');

if (empty($db)) {
    require(__STAFF_DIR__ . '/connect_pdo.php');
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
    // HTML-safe variants for direct output into page titles / head content
    $school_title_h  = htmlspecialchars($school_title  ?? '', ENT_QUOTES, 'UTF-8');
    $running_title_h = htmlspecialchars($running_title ?? '', ENT_QUOTES, 'UTF-8');

} catch (PDOException $ex) {

}
