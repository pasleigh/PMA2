<?php
$dbName = "";
$dbusername = "";
$password = "";

$hostname = "localhost";
$base_dir = "/var/www/html/pma";
$base_dir = "C:\\xampp\\htdocs\\PMA2\\pma";
// mac
//$base_dir = "/Applications/XAMPP/xamppfiles/htdocs/ProjectMarkingApp/pma";
// own server
//$base_dir = "/var/www/vhosts/sleigh-munoz.co.uk/httpdocs/pma/ProjectMarkingApp-main/pma";

switch ($school) {
  case "mech":
    //echo("$school");
    $dbName = "projects_mech";
    $dbName = "mechprojTCAO";
    $dbusername = "projects_mech";
    $password = "G=M1aNN78&5e";
    break;
  case "chem":
    //echo("$school");
    $dbName = "projects_chem";
    $dbusername = "projects_chem";
    $password = "7C.9zt46X,[s";
    break;
  case "civil":
    //echo("$school");
    $dbName = "projects_civil";
    //$dbName = "civilproyRoM";
    $dbusername = "projects_civil";
    $password = "E3<-0XRzh;,6";
    break;
  case "elec":
    //echo("$school");
    $dbName = "projects_elec";
    $dbusername = "projects_elec";
    $password = '0FJ9"0mxuq\(h';
    $password = 'vR7#kP9z!Lm2';
    break;
  case "scape":
    //echo("$school");
    $dbName = "projects_scape";
    $dbusername = "projects_scape";
    $password = "57F.O95NOUn5";
    break;
  case "swjtu_civil":
    //echo("$school");
    $dbName = "projects_swjtcivil";
    $dbusername = "projects_swjtcivil";
    $password = '~Y4"#>70Ky£&';
    break;
  case "swjtu_mech":
    //echo("$school");
    $dbName = "projects_swjtmech";
    $dbusername = "projects_swjtmech";
    $password = "L+3eW11B2i9U";
    break;
  case "swjtu_elec":
    //echo("$school not yet setup");
      $dbName = "projects_swjtelec";
      $dbusername = "projects_swjtelec";
      $password = 'gT4mX9bQ2vL1';
    break;
  case "its":
    //echo("$school");
    $dbName = "projects_its";
    $dbusername = "projects_its";
    $password = "),72/fQWLo9S";
    break;
  case "env":
    //echo("$school");
    $dbName = "projects_env";
    $dbusername = "projects_env";
    $password = "2f`d7GSC@6vV";
    break;
  case "kmap2":
    //echo("$school");
    $dbName = "eps_curriculum";
    $dbusername = "eps_curriculum";
    $password = "Z6w6bmG8X8@N";
    break;
  default:
    // School not defined
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


if($dbName){
   $num_days = 30;
   setcookie('PMA_SCHOOL',$school,time() + (86400 * $num_days), "/");
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

