<?php
$dbName = "";
$dbusername = "";
$password = "";

$hostname = "localhost";
//############################################################
// Set the root folder of the system
//############################################################
// windows XAMPP
$base_dir = "C:\\xampp\\htdocs\\PMA2\\pma";

// mac
$base_dir = "/Applications/XAMPP/xamppfiles/htdocs/PMA2/pma";

// linux / RHEL
//$base_dir = "/var/www/html/pma";
//############################################################

//############################################################
// Set the path to the db credentiuals file
// This should be outside the web server folder
//############################################################
$_creds_file = __DIR__ . '/../db_credentials_pma.php';
$_creds_file = __DIR__ . '/../../db_credentials_pma.php';
//############################################################

if (!file_exists($_creds_file)) {
  echo($_creds_file );
    die("Database credentials file not found. Copy db_credentials.example.php to db_credentials_pma.php and fill in values.");
}
//echo("Include _creds file");
$_creds = require $_creds_file;
//var_dump($_creds);
switch ($school) {
  case "mech":
    $dbName = $_creds['mech']['db'];
    $dbusername = $_creds['mech']['user'];
    $password   = $_creds['mech']['pass'];
    $session_name = $_creds['mech']['sess'];
    break;
  case "chem":
    $dbName = $_creds['chem']['db'];
    $dbusername = $_creds['chem']['user'];
    $password   = $_creds['chem']['pass'];
      $session_name = $_creds['chem']['sess'];
    break;
  case "civil":
    $dbName = $_creds['civil']['db'];
    $dbusername = $_creds['civil']['user'];
    $password   = $_creds['civil']['pass'];
      $session_name = $_creds['civil']['sess'];
    break;
  case "elec":
    $dbName = $_creds['elec']['db'];
    $dbusername = $_creds['elec']['user'];
    $password   = $_creds['elec']['pass'];
      $session_name = $_creds['elec']['sess'];
    break;
  case "scape":
    $dbName = $_creds['scape']['db'];
    $dbusername = $_creds['scape']['user'];
    $password   = $_creds['scape']['pass'];
      $session_name = $_creds['scape']['sess'];
    break;
  case "swjtu_civil":
    $dbName = $_creds['swjtu_civil']['db'];
    $dbusername = $_creds['swjtu_civil']['user'];
    $password   = $_creds['swjtu_civil']['pass'];
      $session_name = $_creds['swjtu_civil']['sess'];
    break;
  case "swjtu_mech":
    $dbName = $_creds['swjtu_mech']['db'];
    $dbusername = $_creds['swjtu_mech']['user'];
    $password   = $_creds['swjtu_mech']['pass'];
      $session_name = $_creds['swjtu_mech']['sess'];
    break;
  case "swjtu_elec":
    $dbName = $_creds['swjtu_elec']['db'];
    $dbusername = $_creds['swjtu_elec']['user'];
    $password   = $_creds['swjtu_elec']['pass'];
      $session_name = $_creds['swjtu_elec']['sess'];
    break;
  case "its":
    $dbName = $_creds['its']['db'];
    $dbusername = $_creds['its']['user'];
    $password   = $_creds['its']['pass'];
      $session_name = $_creds['its']['sess'];
    break;
  case "env":
    $dbName = $_creds['env']['db'];
    $dbusername = $_creds['env']['user'];
    $password   = $_creds['env']['pass'];
      $session_name = $_creds['env']['sess'];
    break;
  default:
    echo("The School is not defined. <BR>Go to the <A href='../index_pma.html'>index</A>");
    exit();
}

define("DB_SCHOOL", $school);
define("DB_NAME", $dbName);
define("DB_ADMIN_USER", $dbusername);
define("DB_ADMIN_PASS", $password);

putenv("SESSION_NAME=$session_name");

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

$PMA_SCHOOL_COOKIE_NAME = 'PMA_SCHOOL';
if($dbName){
   $num_days = 30;

    if (session_status() === PHP_SESSION_NONE) {
        //ini_set('session.name', getenv('SESSION_NAME') ?: 'PMA');
        session_start();
    }
   if (session_status() !== PHP_SESSION_NONE) {
       $_SESSION[$PMA_SCHOOL_COOKIE_NAME] = $school;
   }
}

//############################################################
// Outgoing email settings (used by EmailPDFAttachmentPHPMailer() in staff/EmailPDFAttachment.php)
//############################################################
// MAIL_TRANSPORT:
//   'smtp'     - send via an SMTP server (recommended, e.g. University relay / Office 365 / Azure Communication Services)
//   'mail'     - use PHP's built-in mail() (needs a local MTA, usually not available on Azure)
//   'sendmail' - use the local sendmail binary
// MAIL_SMTP_SECURE: 'tls' (STARTTLS, usually port 587), 'ssl' (SMTPS, usually port 465) or '' (none, usually port 25)
// MAIL_SMTP_AUTH: true if the SMTP server needs a username / password (an internal relay often does not)
//
// The SMTP username / password are NOT stored here. Add them to the credentials file (outside the web folder):
//   'mail' => ['user' => 'noreply@example.ac.uk', 'pass' => 'CHANGE_ME'],
//
// MAIL_FROM_ADDRESS: most SMTP servers (Office 365 in particular) will only send "From" the mailbox you log in as.
//   If set, every email is sent From this address, and the sender passed by the app (e.g. the module leader)
//   is used as the Reply-To instead, so replies still go to the right person.
//   Leave as '' to send From the address passed by the app (only works if the server allows it).
// MAIL_SMTP_DEBUG: 0 = off, 2 = log the SMTP conversation to the PHP error log (useful when setting up)
if (!defined('MAIL_TRANSPORT')) {
    define('MAIL_TRANSPORT', 'smtp');
    define('MAIL_SMTP_HOST', 'smtp.gmail.com');
    define('MAIL_SMTP_PORT', 587);
    define('MAIL_SMTP_SECURE', 'tls');
    define('MAIL_SMTP_AUTH', true);
    define('MAIL_SMTP_USER', $_creds['mail']['user'] ?? '');
    define('MAIL_SMTP_PASS', $_creds['mail']['pass'] ?? '');
    define('MAIL_SMTP_TIMEOUT', 30);
    define('MAIL_FROM_ADDRESS', 'pma.noreply@gmail.com');
    define('MAIL_FROM_NAME', 'Project Marking App');
    define('MAIL_SMTP_DEBUG', 2);
}
//############################################################

// SQL Console — every statement run through admin_sql_console.php / run_admin_sql_console.php
// requires this password (checked via password_verify()) in addition to admin access + CSRF.
// To change it: php -r "echo password_hash('yourpassword', PASSWORD_DEFAULT) . PHP_EOL;" and paste the result here.
$hash_to_test_sql_console_against = '$2y$12$l9ZaY4S4JQqEKF2LZgo74Otw/qccCUiTG3ktNmo2O7zwuWQl3G4Ai';



