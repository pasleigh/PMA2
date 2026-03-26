<?php
$dbName = "";
$dbusername = "";
$password = "";

$hostname = "localhost";
$base_dir = "/var/www/html/pma";
$base_dir = "C:\\xampp\\htdocs\\PMA2\\pma";
// mac
//$base_dir = "/Applications/XAMPP/xamppfiles/htdocs/PMA2/pma";
// own server
//$base_dir = "/var/www/vhosts/sleigh-munoz.co.uk/httpdocs/pma/ProjectMarkingApp-main/pma";

$_creds_file = __DIR__ . '/../db_credentials.php';
if (!file_exists($_creds_file)) {
    die("Database credentials file not found. Copy db_credentials.example.php to db_credentials.php and fill in values.");
}
$_creds = require $_creds_file;

switch ($school) {
  case "mech":
    $dbName = $_creds['mech']['db'];
    $dbusername = $_creds['mech']['user'];
    $password   = $_creds['mech']['pass'];
    break;
  case "chem":
    $dbName = $_creds['chem']['db'];
    $dbusername = $_creds['chem']['user'];
    $password   = $_creds['chem']['pass'];
    break;
  case "civil":
    $dbName = $_creds['civil']['db'];
    $dbusername = $_creds['civil']['user'];
    $password   = $_creds['civil']['pass'];
    break;
  case "elec":
    $dbName = $_creds['elec']['db'];
    $dbusername = $_creds['elec']['user'];
    $password   = $_creds['elec']['pass'];
    break;
  case "scape":
    $dbName = $_creds['scape']['db'];
    $dbusername = $_creds['scape']['user'];
    $password   = $_creds['scape']['pass'];
    break;
  case "swjtu_civil":
    $dbName = $_creds['swjtu_civil']['db'];
    $dbusername = $_creds['swjtu_civil']['user'];
    $password   = $_creds['swjtu_civil']['pass'];
    break;
  case "swjtu_mech":
    $dbName = $_creds['swjtu_mech']['db'];
    $dbusername = $_creds['swjtu_mech']['user'];
    $password   = $_creds['swjtu_mech']['pass'];
    break;
  case "swjtu_elec":
    $dbName = $_creds['swjtu_elec']['db'];
    $dbusername = $_creds['swjtu_elec']['user'];
    $password   = $_creds['swjtu_elec']['pass'];
    break;
  case "its":
    $dbName = $_creds['its']['db'];
    $dbusername = $_creds['its']['user'];
    $password   = $_creds['its']['pass'];
    break;
  case "env":
    $dbName = $_creds['env']['db'];
    $dbusername = $_creds['env']['user'];
    $password   = $_creds['env']['pass'];
    break;
  default:
    echo("School not defined. <BR>Go to the <A href='../index_pma.html'>index</A>");
    exit();
}

define("DB_SCHOOL", $school);
define("DB_NAME", $dbName);
define("DB_ADMIN_USER", $dbusername);
define("DB_ADMIN_PASS", $password);

if(defined("DB_SERVER")){
   // do nothing
}else{
   define("DB_SERVER", $hostname);
   define("BASE_DIR", $base_dir);
   define("BASE_URL", 'unused');

   define("__BASE_DIR__", BASE_DIR);
   define("__ADMIN_DIR__",__BASE_DIR__ . "/staff/admin2");
   define("__STAFF_DIR__",__BASE_DIR__ . "/staff");
   define("__STUDENT_DIR__",__BASE_DIR__ . "/student");
   define("__PROPS_DIR__",__BASE_DIR__ . "/staff/proposals");
}

if (!defined('PMA_TMP_DIR')) {
    // Determine a writable temp directory for mPDF, CSV/XLSX exports, ZIP creation, etc.
    //
    // Override by setting the PMA_TMP_DIR environment variable on the server, e.g.:
    //   Apache:  SetEnv PMA_TMP_DIR /var/www/pma/tmp      (in httpd.conf or .htaccess)
    //   Linux:   export PMA_TMP_DIR=/var/www/pma/tmp      (in the web-server user's profile)
    //   Windows: set PMA_TMP_DIR=C:\xampp\htdocs\PMA2\pma\tmp  (system environment variables)
    //
    // If not set, falls back to sys_get_temp_dir() when writable (works on Linux/RedHat/Windows),
    // otherwise falls back to pma/tmp/ which is committed with chmod 777.
    $_pma_env_tmp = getenv('PMA_TMP_DIR');
    if ($_pma_env_tmp && is_dir($_pma_env_tmp) && is_writable($_pma_env_tmp)) {
        define('PMA_TMP_DIR', rtrim($_pma_env_tmp, '/\\'));
    } elseif (is_writable(sys_get_temp_dir())) {
        define('PMA_TMP_DIR', rtrim(sys_get_temp_dir(), '/\\'));
    } else {
        define('PMA_TMP_DIR', __DIR__ . '/tmp');
    }
    unset($_pma_env_tmp);
}

if($dbName){
   $num_days = 30;
   setcookie('PMA_SCHOOL', $school, [
       'expires'  => time() + (86400 * $num_days),
       'path'     => '/',
       'secure'   => true,
       'httponly' => true,
       'samesite' => 'Lax',
   ]);
   if (session_status() !== PHP_SESSION_NONE) {
       $_SESSION['PMA_SCHOOL'] = $school;
   }
}

if($debug){
    echo("__ADMIN_DIR__ ".__ADMIN_DIR__."<BR>");
    echo("__STAFF_DIR__ ".__STAFF_DIR__."<BR>");
    echo("__STUDENT_DIR__ ".__STUDENT_DIR__."<BR>");
    echo("__PROPS_DIR__ ".__PROPS_DIR__."<BR>");
    echo("DB_SCHOOL ".DB_SCHOOL."<BR>");
    echo("<PRE>");
    print_r($_COOKIE);
    echo("</PRE>");
}

