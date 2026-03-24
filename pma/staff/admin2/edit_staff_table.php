<?php
(@include_once("./set_dir_admin.php")) or die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) or die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) or die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) or die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) or die("Cannot setup database tables<BR>");
(@include_once("./utility_functions.php")) or die("Cannot read utility_functions file<BR>");
(@include_once("../markstore_functions.php")) or die("Cannot read markstore_functions file<BR>");
(@include_once("../staff_functions.php")) or die("Cannot read markstore_functions file<BR>");

(@include_once("./admin_functions.php")) or die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod;'>$access_phrase</h2>\n";

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

//error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL ^ E_NOTICE);
require_once __BASE_DIR__ . '/vendor/autoload.php';

if ($access) {

    if (array_key_exists('default_table', $_REQUEST) == false) {
        $_REQUEST['default_table'] = null;
    }
    $default_table = $_REQUEST['default_table'];

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }
// if the form was submitted then enter it all into the database
//echo("OP=$_POST['op'] . <BR>\n");
    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }
//echo("POST_OP=$POST_OP <BR>\n");

// see all the POST values
//foreach ($_POST as $key => $value){
//  echo "{$key} = {$value}<BR>\r\n";
//}

    $display_block .= "<h1>Edit Staff details for all modules</h1>";

    if ($default_table == 1) {
        $staff_names_table = $staff_names_default;
    } else {
        $staff_names_table = $staff_names2;
    }
    $display_block .= "<h2>Database table $staff_names_table</h2>";

    if ($POST_OP == "new") {
        $display_block .= "<P>A New member of staff.</P>";

        if ($default_table == 1) {
            $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?default_table=1\">";
        } else {
            $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">";
        }
        $display_block .= "<table>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>Title</TD>\n";
        $display_block .= "<TD><select name=\"title_select\">\n";
        $selected = "";
        $display_block .= "<option value=\"Prof\" $selected>Prof</option>\n";
        $display_block .= "<option value=\"Dr\" $selected>Dr</option>\n";
        $display_block .= "<option value=\"Mrs\" $selected>Mrs</option>\n";
        $display_block .= "<option value=\"Ms\" $selected>Ms</option>\n";
        $display_block .= "<option value=\"Miss\" $selected>Miss</option>\n";
        $display_block .= "<option value=\"Mr\" $selected>Mr</option>\n";
        $display_block .= "</select></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>Initials</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"initials\"  size=10 maxlength=30></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>First name</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"firstname\" size=30 maxlength=30></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>Family name</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"familyname\" size=30 maxlength=40></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>email</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"email\" size=30 maxlength=40></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>username</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"username\" size=30 maxlength=40></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>Room</TD>\n";
        $display_block .= "<TD><input type=\"text\" name=\"roomX\" size=10 maxlength=30></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "<TR>\n";
        $display_block .= "<TD>Inactive </TD>\n";
        $display_block .= "<TD><input type=\"checkbox\" name=\"inactive_chk\" value=\"1\" >(1 or checked = not taking part in projects)</TD>\n";

        $display_block .= "</TR>\n";

        $display_block .= "<TD>Admin level</TD>\n";
        $display_block .= "<TD><select name=\"admin_level_select\">\n";
        $selected = "";
        $display_block .= "<option value=\"0\" selected>0</option>\n";
        $display_block .= "<option value=\"1\" $selected>1</option>\n";
        $display_block .= "<option value=\"2\" $selected>2</option>\n";
        $display_block .= "<option value=\"3\" $selected>3</option>\n";
        $display_block .= "</select></TD>\n";
        $display_block .= "</TR>\n";

        $display_block .= "</table>\n";

        $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_new\">";

        $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Submit details of this new staff member.\"></p>";

        $display_block .= "</form>";


        $display_block .= "<hr>\n";
    }
    elseif ($POST_OP == "submit_edited") {
        $staff_count = $_POST['staff_count'];
        for ($i = 1; $i <= $staff_count; $i++) {

            //$display_block .= "Submitting: <BR>\n";
            $postid = "staffid$i";
            $staffid = $_POST[$postid];
            $postid = "initials$i";
            $initials = $_POST[$postid];
            $postid = "title$i";
            $title = $_POST[$postid];
            $postid = "firstname$i";
            $firstname = $_POST[$postid];
            $postid = "familyname$i";
            $familyname = $_POST[$postid];
            $fullname = "$firstname $familyname";

            $postid = "email$i";
            $email = $_POST[$postid];
            $postid = "username$i";
            $username = $_POST[$postid];
            $postid = "room$i";
            $room = $_POST[$postid];

            //$inactive = $_POST['inactive_select'];

            $postid = "inactive$i";
            if (array_key_exists($postid, $_POST) == false) {
                $_POST[$postid] = 0;
            }
            $inactive_chk = $_POST[$postid];

            $postid = "admin_level$i";
            $mod_admin_level = $_POST[$postid];

            $postid = "delete$i";
            if (array_key_exists($postid, $_POST) == false) {
                $_POST[$postid] = 0;
            }
            $delete_chk = $_POST[$postid];

            //$display_block .= "$staffid, $fullname, $firstname, $familyname, $initials, $title, $email, $username, $room, $inactive_chk .... <BR>\n";
            if ($delete_chk == 0) {
                $query = "update $staff_names_table set initials='$initials',title='$title',firstname='$firstname',familyname='$familyname',fullname='$fullname',email='$email',username='$username',room='$room',inactive='$inactive_chk',admin_level='$mod_admin_level' where staffid=$staffid";
            } else {
                // Delete entry
                $query = "DELETE FROM $staff_names_table WHERE staffid=$staffid";
            }
            //$display_block .= "$query <BR>";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }

        }
        $display_block .= "<B>All updated</B><BR><hr>\n";
    }
    elseif ($POST_OP == "submit_new") {
        $display_block .= "Submitting: <BR>\n";

        //$staffid = $_POST['staffid'];
        $title = $_POST['title_select'];
        $initials = $_POST['initials'];
        $firstname = $_POST['firstname'];
        $familyname = $_POST['familyname'];
        $fullname = "$firstname $familyname";
        $email = $_POST['email'];
        $username = $_POST['username'];
        $room = $_POST['roomX'];
        $inactive = 0;
        if (isset($_POST['inactive_chk'])) {
            $inactive = 1;
        }
        $mod_admin_level = $_POST['admin_level_select'];

        $display_block .= "$fullname, $firstname, $familyname, $initials, $title, $email, $username, $room, $inactive, $mod_admin_level .... <BR>\n";
        $query = "INSERT INTO $staff_names_table (initials,title,firstname,familyname,fullname,email,username,room,inactive,admin_level) VALUES ('$initials','$title','$firstname','$familyname','$fullname','$email','$username','$room','$inactive','$mod_admin_level')  ";

        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $display_block .= "<B>Done</B><BR><hr>\n";
    }
    elseif ($POST_OP == "upload_csv") {
        $initials_col = 1;
        $title_col = 2;
        $first_name_col = 3;
        $family_name_col = 4;
        $email_col = 5;
        $username_col = 6;

        $xlsx_file_name = $_FILES['csv']['name'];
        $ext = pathinfo($xlsx_file_name, PATHINFO_EXTENSION);
        $xlsx_file_size = $_FILES['csv']['size'];
        $xlsx_file_tmp = $_FILES['csv']['tmp_name'];
        $xlsx_file_type = $_FILES['csv']['type'];
        $xlsx_file_error = $_FILES['csv']['error'];

        $file_info = "File name $xlsx_file_name<BR>";
        $file_info .= "File size $xlsx_file_size<BR>";
        $file_info .= "File name on server (tmp) $xlsx_file_tmp<BR>";
        $file_info .= "File type $xlsx_file_type<BR>";
        $file_info .= "File read earror message $xlsx_file_error<BR>";

        if (!$xlsx_file_error) {
            if ($xlsx_file_name != "") {
                $PHP_EXCEL_filetype = IOFactory::identify($xlsx_file_tmp);
                $file_info .= "PHPExcel file type: $PHP_EXCEL_filetype<BR>";

                $spreadsheet = IOFactory::load($xlsx_file_tmp);

                $worksheet = $spreadsheet->getSheet(0);

                $worksheet_name = $worksheet->getTitle();
                $highestRow = $worksheet->getHighestRow();
                $highestCol = $worksheet->getHighestColumn();
                $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
                $nrColumns = ord($highestCol) - 64;

                for ($row = 2; $row <= $highestRow; $row++) {
                    $col = $initials_col;
                    $this_initials = $worksheet->getCell([$col, $row])->getValue();
                    $col = $title_col;
                    $this_title = $worksheet->getCell([$col, $row])->getValue();
                    $col = $first_name_col;
                    $this_first_name = $worksheet->getCell([$col, $row])->getValue();
                    $col = $family_name_col;
                    $this_family_name = $worksheet->getCell([$col, $row])->getValue();
                    $col = $email_col;
                    $this_email = $worksheet->getCell([$col, $row])->getValue();
                    $col = $username_col;
                    $this_username = $worksheet->getCell([$col, $row])->getValue();

                    $this_fullname = $this_first_name . " " . $this_family_name;

                    $this_room = "";
                    $this_inactive = 0;
                    $this_admin_level = 0;

                    // NOTE: can't use RAPLACE INTO as this changes the index of the staff (=staffid)
                    // Must detect if the username exists (this is unique and not null) then
                    // if username exists  do an UPDATE
                    // if it does not exist do an INSERT INTO
                    if (DoesStaffExistByUsername($db, $this_username, $staff_names_table)) {
                        $query = "UPDATE $staff_names_table SET ";

                        $query .= "initials='" . addslashes($this_initials) . "', ";
                        $query .= "title='" . addslashes($this_title) . "', ";
                        $query .= "firstname='" . addslashes($this_first_name) . "', ";
                        $query .= "familyname='" . addslashes($this_family_name) . "', ";
                        $query .= "fullname='" . addslashes($this_fullname) . "', ";
                        $query .= "email='" . addslashes($this_email) . "', ";
                        $query .= "room='" . addslashes($this_room) . "', ";
                        $query .= "inactive='" . addslashes($this_inactive) . "' ";
                        $query .= "WHERE ";
                        $query .= "username='$this_username'";
                    } else {
                        $query = "INSERT INTO $staff_names_table SET ";

                        $query .= "initials='" . addslashes($this_initials) . "', ";
                        $query .= "title='" . addslashes($this_title) . "', ";
                        $query .= "firstname='" . addslashes($this_first_name) . "', ";
                        $query .= "familyname='" . addslashes($this_family_name) . "', ";
                        $query .= "fullname='" . addslashes($this_fullname) . "', ";
                        $query .= "email='" . addslashes($this_email) . "', ";
                        $query .= "room='" . addslashes($this_room) . "', ";
                        $query .= "inactive='" . addslashes($this_inactive) . "', ";
                        $query .= "admin_level='" . addslashes($this_admin_level) . "', ";
                        $query .= "username='$this_username'";
                    }

                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo("An Error occured accessing the database in function: $this_function <BR>\n");
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";
                        exit();
                    }

                }

            } else {
                $display_block .= "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a XLSX/CSV file to upload.</H3>";
            }
        } else {
            $display_block .= "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a XLSX/CSV file to upload.</H3>";
        }

    }
    elseif ($POST_OP == "download_csv") {
        error_reporting(0);
        $csv_filename = "staff_names_data_" . $module_prefix . $module1 . $module_postfix . "_" . $short_code . "_" . $year_begin . "-" . $year_end . ".csv";

        $sys_temp_dir = sys_get_temp_dir();
        $tmpcsvfile = @tempnam($sys_temp_dir, 'x');// @tempnam($sys_temp_dir, '');
        if ($tmpcsvfile === false) {
            echo("There seem to be a problem creating the temporary file for download.");
            exit();
        }

        $path = dirname($tmpcsvfile);

        $query = "SELECT * FROM $staff_names_table ORDER BY familyname";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo("Unable to update data to the table $students<BR>\n");
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
        $num = $results->rowCount();

        if ($num < 1) {
            //no records
            $display_block .= "<p><em>Sorry, there are no  records to download!</em></p>";
        } else {
            //has records
            $fp = @fopen($tmpcsvfile, 'w');

            // Title row
            $row[0] = "Initials";
            $row[1] = "Title";
            $row[2] = "First name";
            $row[3] = "Family name";
            $row[4] = "Email";
            $row[5] = "username";

            $ret = @fputcsv($fp, $row);
            //echo " fputcsv return = $ret .<BR />";
            //fclose($fp);

            $rows = $results->fetchAll();
            foreach ($rows as $recs) {
                $initials = $recs['initials'];
                $title = $recs['title'];
                $firstname = $recs['firstname'];
                $familyname = $recs['familyname'];
                $email = $recs['email'];
                $username = $recs['username'];

                $row[0] = $initials;
                $row[1] = $title;
                $row[2] = $firstname;
                $row[3] = $familyname;
                $row[4] = $email;
                $row[5] = $username;

                //$fp = fopen('file.csv', 'w');
                $ret = @fputcsv($fp, $row);
                //echo "$ret <br />";
            }

            @fclose($fp);

            header('Content-type: application/excel');
            header("Content-Disposition: attachment; filename=$csv_filename");
            @readfile("$tmpcsvfile");

            exit();
            $display_block = "<h2> Staff data saved as XLSX/CSV file to: $csv_filename</h2>";
        }

    }

    if ($default_table == 1) {
        $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?default_table=1\">";
    } else {
        $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">";
    }

// Get the list of staff from the staff_names2 table
    $query = "select * from $staff_names_table order by familyname";
//$get_list_res = mysql_query($get_list) or die(" Unable to create staff name list from database. <BR>Error msg: " . mysql_error() . "<BR>Query: $get_list");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
//$display_block .= "<select name=\"staffname_select\">\n";

//$display_block = "<TABLE  class=\"tableSimple\" style=\"border:1px solid black;border-collapse:collapse;\">\n";
    $display_block .= "<TABLE id=\"staffinfo\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
    $display_block .= "
    <thead>
      <tr>
        <th>ID</th>
        <th>Initials</th>
        <th>Title</th>
        <th>First name</th>
        <th>Family name</th>
        <th>Full name</th>
        <th>Email</th>
        <th>Username</th>
        <th>Room</th>
        <th>Inactive</th>
        <th>Admin level</th>
        <th>Delete</th>
      </tr>
    </thead>";
    $display_block .= "<tbody>\n";
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
        $display_block .= "<TR>\n";
        $display_block .= "<TD><input type=\"hidden\" name=\"staffid$staff_count\" value=\"$staffid\"> $staffid</TD>";
        $display_block .= "<TD><input type=\"text\" name=\"initials$staff_count\" value=\"$initials\"  size=5 maxlength=30></TD>";
        $display_block .= "<TD>" . GetTitleSelect("title$staff_count", $title) . "</TD>";
        $display_block .= "<TD><input type=\"text\" name=\"firstname$staff_count\" value=\"$firstname\"  size=10 maxlength=30></TD>";
        $display_block .= "<TD><input type=\"text\" name=\"familyname$staff_count\" value=\"$familyname\"  size=30 maxlength=40></TD>";
        $display_block .= "<TD>$fulllname</TD>";
        $display_block .= "<TD><input type=\"text\" name=\"email$staff_count\" value=\"$email\"  size=30 maxlength=40></TD>";
        $display_block .= "<TD><input type=\"text\" name=\"username$staff_count\" value=\"$username\"  size=8 maxlength=20></TD>";
        $display_block .= "<TD><input type=\"text\" name=\"room$staff_count\" value=\"$room\"  size=5 maxlength=10></TD>";
        $checked = "";
        if ($inactive == 1) {
            $checked = "checked";
        }
        $display_block .= "<TD><input type=\"checkbox\" name=\"inactive$staff_count\" value=\"1\" $checked></TD>\n";
        $display_block .= "<TD>" . GetAdminLevelSelect("admin_level$staff_count", $mod_admin_level) . "</TD>";
        $display_block .= "<TD><input type=\"checkbox\" name=\"delete$staff_count\" value=\"1\"></TD>\n";
        $display_block .= "</TR>\n";

    }
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_edited\">";
    $display_block .= "\n<input type=\"hidden\" name=\"staff_count\" value=\"$staff_count\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit changes to staff details.\"></p>";

    $display_block .= "</form>";

//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>An alternative staff edit page ... </H2>\n";
    $display_block .= "<P>When the above table gets very big it will not edit due to limits of the server. 
                        This alternative page will allow editing of staff details</P> ";
    $display_block .= "<A href='edit_staff.php'>Alternative Edit staff page</A>";
//---------------------------------------------------------------------------------

    $display_block .= "<hr><h2>Upload a XLSX/CSV of staff details... </H2>\n";
    if ($default_table == 1) {
        $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?default_table=1\" enctype=\"multipart/form-data\">";
    } else {
        $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\" enctype=\"multipart/form-data\">";
    }
    //$display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\" enctype=\"multipart/form-data\">\n";
    $display_block .= "<P> You can upload a XLSX/CSV file to update and <B>add</B> staff details (you cannot delete staff) </P>";
    $display_block .= "<P> Create a XLSX/CSV with the headings:<B>Initials</B>, <B>Title</B>,  <B>First name</B>, <B>Family name</B>, <B>Email</B>, <B>username</B>, <B>room</B> and load here.</P>";

    $display_block .= "<P></P><input name=\"csv\" type=\"file\" id=\"csv\" /></P>";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"upload_csv\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_csv\" name=\"submit\" value=\"Upload a XLSX/CSV of staff details\"></p>";

    $display_block .= "</form>\n";

//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Add a new member of staff...</H2>\n";
    if ($default_table == 1) {
        $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?default_table=1\">";
    } else {
        $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">";
    }
    //$display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id&default_table=$default_table\">\n";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"new\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_new\" name=\"submit\" value=\"Add details of a NEW member of staff.\"></p>";

    $display_block .= "</form>";

//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Download Staff data to CSV file ...</H2>\n";
    if ($default_table == 1) {
        $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?default_table=1\">";
    } else {
        $display_block .= "<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">";
    }
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"download_csv\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_download\" name=\"submit\" value=\"Download Staff Data to CSV.\"></p>";

    $display_block .= "</form>\n";
}


function GetTitleSelect($NameKey, $MatchTitle)
{
    $titles[1] = "Prof";
    $titles[2] = "Dr";
    $titles[3] = "Mrs";
    $titles[4] = "Ms";
    $titles[5] = "Miss";
    $titles[6] = "Mr";

    $select_block = "\n<select name=\"$NameKey\">\n";

    for ($i = 1; $i <= 6; $i++) {
        $Title = $titles[$i];

        if ($MatchTitle == $Title) {
            $selected = "selected";
        } else {
            $selected = "";
        }

        $select_block .= "<option value=\"$Title\" $selected>$Title</option>\n";
    }
    $select_block .= "</select>\n";

    return $select_block;
}

function GetAdminLevelSelect($NameKey, $MatchLevel)
{
    $levels[1] = "0";
    $levels[2] = "1";
    $levels[3] = "2";
    $levels[4] = "3";

    $select_block = "\n<select name=\"$NameKey\">\n";

    for ($i = 1; $i <= 4; $i++) {
        $Level = $levels[$i];

        if ($MatchLevel == $Level) {
            $selected = "selected";
        } else {
            $selected = "";
        }

        $select_block .= "<option value=\"$Level\" $selected>$Level</option>\n";
    }
    $select_block .= "</select>\n";

    return $select_block;
}

?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) or die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<script>
    $(function () {
        $("#staffinfo").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                //"ordering": false,
                //"info":     false,
                "searching": false
            }
        );
    })
</script>
<BODY>
<div id="wrapper">
    <div id="header">
        <span
                class="header">Edit Staff details - this applies to ALL modules in the system </span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>

    </div><!-- /content -->

    <div class="footer_link" id="footer">
        <P>Return to the
            <a href="../index.php">Year index page</a> -
            <a href="./index.php">Main admin page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
