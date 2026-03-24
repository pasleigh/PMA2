<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once($STAFF_DIR_VAL . "/config.php")) OR die("Cannot find this file to include: config.php. $DEFAULT_ERR");
(@include_once($STAFF_DIR_VAL . "/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once($STAFF_DIR_VAL . "/connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once($STAFF_DIR_VAL . "/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once($STAFF_DIR_VAL . "/staff_functions.php")) OR die("Cannot include staff_function.php<BR>");
(@include_once($STAFF_DIR_VAL . "/show_minor_part_feedback_form.php")) OR die("Cannot include show_minor_part_feedback<BR>");
(@include_once($STAFF_DIR_VAL . "/show_mark_questions_bs.php")) OR die("Cannot include show_mark_questions2<BR>");
(@include_once($STAFF_DIR_VAL . "/pdo_query_functions.php")) OR die("Cannot include pdo_query_functions<BR>");

(@include_once("./admin_functions.php")) or die("Cannot include admin functions<BR>");

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once __BASE_DIR__ . '/vendor/autoload.php';
$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod;'>$access_phrase</h2>\n";
$post_html = "";

//error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL ^ E_NOTICE);

//var_dump($_POST);
if ($access) {
    $staff_names_table = $staff_names_default;

    $column = 'school_short_name';
    if(!doesColumnExists($db, $column, $staff_names_table) ) {
        // Add this column
        $query = "ALTER TABLE $staff_names_table ADD `$column` varchar(50) DEFAULT NULL";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
            exit();
        }
    }

    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }

    if($POST_OP == 'submit_edited'){
        $staffid = $_POST['staffid'];
        $firstname = $_POST['firstname'];
        $familyname = $_POST['familyname'];
        $fullname = "$firstname $familyname";
        $email = $_POST['email'];
        $username = $_POST['username'];
        $inactive = $_POST['inactive'];
        $mod_admin_level = $_POST['admin_level'];
        $delete = $_POST['delete'];

        if($delete != 1) {
            // Update the database
            $query = "update $staff_names_table set 
                 firstname='$firstname',
                 familyname='$familyname',
                 fullname='$fullname',
                 email='$email',
                 username='$username',
                 inactive='$inactive',
                 admin_level='$mod_admin_level' 
             where staffid=$staffid";
            try {
                $results = $db->query($query);
                $msg = "<h3>The database has been updated</h3>";
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                echo("Unable to update data to the table $staff_names2<BR>\n");
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                $msg = "<h3>The database was <em>NOT</em> updated</h3>";
            }

            $post_html = "<h2>Result of submit</h2>";
            $post_html .= "Staff id: $staffid<BR>";
            $post_html .= "First name: $firstname<BR>";
            $post_html .= "Family name: $familyname<BR>";
            $post_html .= "Email: $email<BR>";
            $post_html .= "Username: $username<BR>";
            $post_html .= "Inactive: $inactive<BR>";
            $post_html .= "Admin level: $mod_admin_level<BR>";
            $post_html .= "Delete: $delete<BR>";
            $post_html .= "$msg";
            $post_html .= "<HR>";
        }else{
            //$query = "DELETE FROM $staff_names_table WHERE staffid=$staffid";
            //$results = $db->query($query);
            $msg = "<h3 style='color:red;'>Deleting of staff details has not yet been implemented. The database was <em>NOT</em> updated</h3>";
            $post_html = "<h2>Result of submit</h2>";
            $post_html .= "Staff id: $staffid<BR>";
            $post_html .= "First name: $firstname<BR>";
            $post_html .= "Family name: $familyname<BR>";
            $post_html .= "Email: $email<BR>";
            $post_html .= "Username: $username<BR>";
            $post_html .= "Inactive: $inactive<BR>";
            $post_html .= "Admin level: $mod_admin_level<BR>";
            $post_html .= "Delete: $delete<BR>";
            $post_html .= "$msg";
            $post_html .= "<HR>";
        }

    }
    elseif ($POST_OP == 'submit_new'){
        $firstname = $_POST['firstname'];
        $familyname = $_POST['familyname'];
        $fullname = "$firstname $familyname";
        $email = $_POST['email'];
        $username = $_POST['username'];

        $good_email = false;
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $good_email = true;
        }
        if($firstname != null AND $familyname != null AND $good_email AND $username != null) {

            $mod_admin_level = 0;
            $room = "101";
            $initials = "XXX";
            $title = "Dr";

            $query = "INSERT INTO $staff_names_table (initials,title,firstname,familyname,fullname,email,username,room,admin_level) 
                    VALUES ('$initials','$title','$firstname','$familyname','$fullname','$email','$username',$room,'$mod_admin_level')";
            try {
                $results = $db->query($query);
                $msg = "<h3>The database has been updated with the new staff details</h3>";
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                echo(" Query = $query<BR> \n");
                echo(" Err message: " . $ex->getMessage() . "<BR>\n");

                $msg = "<h3>The database was <em>NOT</em> updated with the new staff details</h3>";
            }
        }else{
            // One of the entries is null
            $msg = "<h3 style='color:red;'>All fields are required. And the email must be valid. <BR>The database was <em>NOT</em> updated with the new staff details</h3>";
        }

        $post_html = "<h2>Result of submit</h2>";
        $post_html .= "First name: $firstname<BR>";
        $post_html .= "Family name: $familyname<BR>";
        $post_html .= "Email: $email<BR>";
        $post_html .= "Username: $username<BR>";
        $post_html .= "$msg";
        $post_html .= "<HR>";
    }
    elseif ($POST_OP == 'submit_upload_xlsx'){
        $first_name_col = 1;
        $family_name_col = 2;
        $email_col = 3;
        $username_col = 4;
        $school_short_name_col = 5;

        $xlsx_file_name = $_FILES['csv_filename']['name'];
        $ext = pathinfo($xlsx_file_name, PATHINFO_EXTENSION);
        $xlsx_file_size = $_FILES['csv_filename']['size'];
        $xlsx_file_tmp = $_FILES['csv_filename']['tmp_name'];
        $xlsx_file_type = $_FILES['csv_filename']['type'];
        $xlsx_file_error = $_FILES['csv_filename']['error'];

        $file_info = "File name $xlsx_file_name<BR>";
        $file_info .= "File size $xlsx_file_size<BR>";
        $file_info .= "File name on server (tmp) $xlsx_file_tmp<BR>";
        $file_info .= "File type $xlsx_file_type<BR>";
        $file_info .= "File read earror message $xlsx_file_error<BR>";

        $msg = "";
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
                    $col = $first_name_col;
                    $this_first_name = $worksheet->getCell([$col, $row])->getValue();
                    $col = $family_name_col;
                    $this_family_name = $worksheet->getCell([$col, $row])->getValue();
                    $col = $email_col;
                    $this_email = $worksheet->getCell([$col, $row])->getValue();
                    $col = $username_col;
                    $this_username = $worksheet->getCell([$col, $row])->getValue();
                    $col = $school_short_name_col;
                    $this_staff_school_short_name = $worksheet->getCell([$col, $row])->getValue();

                    $this_fullname = $this_first_name . " " . $this_family_name;

                    $msg .= $this_first_name . ' ' . $this_family_name . ', ' . $this_username . ', ' . $this_email . ', ' . $this_staff_school_short_name . '<BR>';
                    $this_initials = "XXX";
                    $this_title = "Dr";
                    $this_room = "101";
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
                        $query .= "inactive='" . addslashes($this_inactive) . "', ";
                        $query .= "school_short_name='" . addslashes($this_staff_school_short_name) . "' ";
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
                        $query .= "school_short_name='" . addslashes($this_staff_school_short_name) . "', ";
                        $query .= "admin_level='" . addslashes($this_admin_level) . "', ";
                        $query .= "username='$this_username'";
                    }

                    //$msg .= $query . '<BR>';
                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo("An Error occured accessing the database in function: $this_function <BR>\n");
                        echo(" Query = $query<BR> \n");
                        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                        exit();
                    }

                }

            } else {
                $msg = "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a XLSX/CSV file to upload.</H3>";
            }
        } else {
            $msg = "<H3 style='color: red; background-color: #f1c40f'>You need to browse and select a XLSX/CSV file to upload.</H3>";
        }
        $post_html = "<h2>Result of submit</h2>";
        $post_html .= "Filename: $xlsx_file_name<BR>";
        $post_html .= "$msg";
        $post_html .= "<HR>";
    }
    elseif ($POST_OP == 'submit_download_xlsx'){
        error_reporting(0);
        $csv_filename = "staff_names_data.csv";

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
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
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
            $row[0] = "Staff ID";
            $row[1] = "First name";
            $row[2] = "Family name";
            $row[3] = "Email";
            $row[4] = "username";
            $row[5] = "allocation name format";
            $row[6] = "school short name";

            $ret = @fputcsv($fp, $row);

            $rows = $results->fetchAll();
            foreach ($rows as $recs) {
                $staffid = $recs['staffid'];
                $initials = $recs['initials'];
                $title = $recs['title'];
                $firstname = $recs['firstname'];
                $familyname = $recs['familyname'];
                $email = $recs['email'];
                $username = $recs['username'];
                $this_school_short_name = $recs['school_short_name'];

                $row[0] = $staffid;
                $row[1] = $firstname;
                $row[2] = $familyname;
                $row[3] = $email;
                $row[4] = $username;
                $row[5] = "$familyname, $firstname";
                $row[6] = $this_school_short_name;

                $ret = @fputcsv($fp, $row);
            }

            @fclose($fp);

            header('Content-type: application/excel');
            header("Content-Disposition: attachment; filename=$csv_filename");
            @readfile("$tmpcsvfile");

            exit();
            $display_block = "<h2> Staff data saved as XLSX/CSV file to: $csv_filename</h2>";
        }
    }
    else if($POST_OP == 'topdf'){
        $md_html = $_POST['md_html'];
        require_once __DIR__ . '/../../vendor/autoload.php';

        $system_temp_dir = sys_get_temp_dir();
        $mpdf = new \Mpdf\Mpdf(['tempDir'=>$system_temp_dir]);


        // Set a simple Footer including the date and page number
        $some_text = "MD to HTML to PDF test output";
        $mpdf->setFooter("$some_text Date printed: {DATE j M Y}.   page {PAGENO}");
        $stylesheet = file_get_contents('./../css/pagedesc.css');
        //$stylesheet = file_get_contents('./../css/print.css');
        //$stylesheet = file_get_contents('./boostrap.min.css');
        $stylesheet = file_get_contents('./pagedesc.css');
        $mpdf->WriteHTML($stylesheet, 1); // 1 means stylesheet only
        $mpdf->WriteHTML($md_html);
        $mypdf = $mpdf->OutputHttpDownload("test.pdf");
    }

    // Get the staff data

    $staff_table_head =  <<< "THEAD"
    <tr>
        <th>ID</th>
        <th>First name</th>
        <th>Family name</th>
        <th>Display name</th>
        <th>Email</th>
        <th>Username</th>
        <th>Inactive</th>
        <th>Admin level</th>
        <th>Delete</th>
        <th>Submit</th>
    </tr>
    THEAD;

    $query = "select * from $staff_names_table order by familyname";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }
    $staff_count = 0;
    $rows = $results->fetchAll();
    $staff_array = array();

    $staff_table_html = "<thead style='background-color: #5FBC4A; color: white'>";
    $staff_table_html .= $staff_table_head;
    $staff_table_html .= "</thead>";
    $staff_table_body = "<tbody>";

    foreach ($rows as $recs) {
        $staffid = $recs['staffid'];
        $initials = $recs['initials'];
        $title = $recs['title'];
        $firstname = stripslashes($recs['firstname']);
        $familyname = stripslashes($recs['familyname']);
        $fullname = stripslashes($recs['fullname']);
        $email = stripslashes($recs['email']);
        $username = stripslashes($recs['username']);
        $room = stripslashes($recs['room']);
        $inactive = stripslashes($recs['inactive']);
        $mod_admin_level = stripslashes($recs['admin_level']);
        $this_staff =  array(
            'staff_id' => $staffid,
            'initials' => $initials,
            'title' => $title,
            'firstname' => $firstname,
            'familyname' => $familyname,
            'fullname' => $fullname,
            'email' => $email,
            'username' => $username,
            'room' => $room,
            'inactive' => $inactive,
            'admin_level' => $mod_admin_level
        );
        $staff_array[] = $this_staff;
        $staff_count++;

        /*
        $staff_table_row = "";
        $staff_table_row .= "<TR>\n";
        $staff_table_row .= "<TD><input type='hidden' name='staffid$staff_count' value='$staffid'> $staffid</TD>";
        $staff_table_row .= "<TD><input type='text' name='firstname$staff_count' value='$firstname'  size=10 maxlength=30></TD>";
        $staff_table_row .= "<TD><input type='text' name='familyname$staff_count' value='$familyname'  size=30 maxlength=40></TD>";
        $staff_table_row .= "<TD>$fullname</TD>";
        $staff_table_row .= "<TD><input type='text' name='email$staff_count' value='$email'  size=30 maxlength=40></TD>";
        $staff_table_row .= "<TD><input type='text' name='username$staff_count' value='$username'  size=8 maxlength=20></TD>";
        $checked = "";
        if ($inactive == 1) {
            $checked = "checked";
        }
        $staff_table_row .= "<TD><input type='checkbox' name='inactive$staff_count'id='inactive$staff_count' value='1' $checked></TD>\n";
        $staff_table_row .= "<TD>" . GetAdminLevelSelect("admin_level$staff_count", $mod_admin_level) . "</TD>";
        $staff_table_row .= "<TD><input type='checkbox' name='delete$staff_count' id='delete$staff_count' value='1'></TD>\n";
        $mybtn = "<button type='button' class='btn btn-primary btn-sm' name='name$staff_count' id='id$staffid' onclick='changestaff(this);'>Submit</button>";
        $staff_table_row .= "<TD>$mybtn</TD>\n";
        $staff_table_row .= "</TR>\n";a
        */
        //$staff_table_body .= $staff_table_row;
    }
    $staff_table_body .= "</tbody>";

    $staff_table_html .= $staff_table_body;
    $staff_table_html .= "<tfoot>";
    $staff_table_html .= $staff_table_head;
    $staff_table_html .= "</tfoot>";
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
(@include_once("./$mobile_head_admin_bs")) OR die("Cannot find this file to include: $mobile_head_admin_bs<BR>");
//(@include_once("admin_desktop_head.php")) or die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<script>

</script>
<BODY>
<div id="wrapper">
    <div id="header">
        <div class="header">Edit Staff details - Staff details are the same for ALL modules on this system </div>
    </div><!-- /header -->

    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <?php echo $post_html; ?>
                <?php echo $display_block; ?>
                <div class="card card-body top-30">
                    <H2>Use this table to check and edit staff details</H2>
                    <table id="allStaffDataTable" class="display" style="width:100%; font-size: 85%">
                        <thead>
                        <?php echo $staff_table_head; ?>
                        </thead>
                        <?php echo $staff_table_body; ?>
                        <tfoot>
                        <?php echo $staff_table_head; ?>
                        </tfoot>
                    </table>
                    <P><sup>*</sup><em>Inactive</em> means that the name does not appear on dropdown lists</P>
                </div>
                <BR>
                <div class="card card-body top-30">
                    <H2>Enter details for a new member of staff</H2>
                    <p>All fields are required. The system will not work without all fields.</p>
                    <table>
                        <tr><td>First name</td><td><input type='text' id='new_firstname' size=30 maxlength=40></td></tr>
                        <tr><td>Family name</td><td><input type='text' id='new_familyname' size=30 maxlength=40></td></tr>
                        <tr><td>Email (it must be a valid email)</td><td><input type='text' id='new_email' size=30 maxlength=40></td></tr>
                        <tr><td>Username</td><td><input type='text' id='new_username' size=30 maxlength=40></td></tr>
                        <tr><td></td><td><button type='button' class='btn btn-success' onclick='newstaff();'>Submit new memeber of staff</button></td></tr>
                    </table>
                </div>
                <BR>
                <div class="card card-body top-30">
                    <H2>Upload a xlsx/csv file of staff names</H2>
                    <p>Format: <strong>First name, Family name, Email, Username, [School (e.g. chem, civil, comp, elec, maths, mech, phas, scape)]</strong></p>
                    <p>All 4 fields before the square bracket are <strong>required</strong>. The system will not work without all fields.<BR>
                        If a username already exists in the database then the other data will be updated/over-written</p>
                    <!--<p><input name="csv_filename" type="file" id="csv_filename" /></p>-->
                    <!--<button type='button' class='btn btn-warning' onclick='uploadxlsx();'>Upload the selected file of users</button>-->

                    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" enctype="multipart/form-data">
                        <BR>
                        <input name="csv_filename" type="file" id="csv_filename" /><BR>
                        <input type="hidden" name="op" value="submit_upload_xlsx">
                        <p>
                            <input type="submit" id="submitbutton_csv" name="submit" value="Upload XLSX/CSV of staff details">
                        </p>
                    </form>
                </div>
                <BR>
                <div class="card card-body top-30">
                    <H2>Download a file of all staff details</H2>
                    <button type='button' class='btn btn-secondary' onclick='downloadxlsx();'>Download the file of users</button>
                </div>

            </div>
        </div>


    </div><!-- /content -->
<BR>
    <div class="footer_link" id="footer">
        <BR>
        <P>Return to the
            <a href="../index.php">Year index page</a> -
            <a href="./index.php">Main admin page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
<script>
    function setup_staff_table(){
        //$('#allCityDataTable').empty();
        var all_staff = <?php echo json_encode($staff_array); ?>;
        console.log(all_staff);

        $('#allStaffDataTable').DataTable(
            {
                "paging": true,
                "lengthMenu": [ [5, 10, 30, 100, -1], [5, 10, 30, 100, "All"] ],
                "pageLength": 10,
                //"ordering": false,
                //"info":     false,
                "searching": true,
                columnDefs: [
                    {width: '3%', targets: 0},
                    {width: '10%', targets: 1},
                    {width: '10%', targets: 2},
                    //{width: '15%', targets: 3},
                    {width: '10%', targets: 4},
                    {width: '6%', targets: 5},
                    {width: '5%', targets: 6},
                    {width: '5%', targets: 7},
                    {width: '5%', targets: 8},
                    {width: '6%', targets: 9}
                ],
                columns: [
                    {data: 'staffid'},
                    {data: 'firstname'},
                    {data: 'familyname'},
                    {data: 'fullname'},
                    {data: 'email'},
                    {data: 'username'},
                    {data: 'inactive'},
                    {data: 'admin_level'},
                    {data: 'delete'},
                    {data: 'action'}
                ]

            }
        )

        let staff_table_body_data = get_staff_table_body_data(all_staff)
        //console.log(module_table_body_data)

        var table = $('#allStaffDataTable').DataTable();
        table.clear(); // Clear existing data
        table.rows.add(staff_table_body_data); // Add new data
        table.draw(); // Redraw the table

    }
    function get_staff_table_body_data(all_staff){
        let body_data = []
        for(const index in all_staff) {
            let this_staff = all_staff[index]
            let row_data = get_staff_table_row_data(this_staff,index);
            body_data.push(row_data)
        }
        return body_data
    }
    function get_staff_table_row_data(this_staff,staff_count){
        let staffid = this_staff.staff_id
        let firstname = this_staff.firstname
        let familyname = this_staff.familyname
        let fullname = `${firstname} ${familyname}`
        let email = this_staff.email
        let username= this_staff.username
        let inactive = this_staff.inactive
        let admin_level = this_staff.admin_level
        /*
        {data: 'staffid'},
        {data: 'firstname'},
        {data: 'familyname'},
        {data: 'fullname'},
        {data: 'email'},
        {data: 'username'},
        {data: 'inactive'},
        {data: 'admin_level'},
        {data: 'delete'},
        {data: 'action'}
        */
        let staffid_col = `<input type='hidden' name='staffid${staff_count}' value='${staffid}'> ${staffid}`
        let firstname_col = `<input type='text' name='firstname${staff_count}' value='${firstname}'  size=10 maxlength=30>`
        let familyname_col = `<input type='text' name='familyname${staff_count}' value='${familyname}'  size=30 maxlength=40>`
        let fullname_col = fullname
        let email_col = `<input type='text' name='email${staff_count}' value='${email}'  size=30 maxlength=40>`
        let username_col = `<input type='text' name='username${staff_count}' value='${username}'  size=8 maxlength=20>`
        let checked = "";
        if (inactive == 1) {
            checked = "checked";
        }
        let inactive_col = `<input type='checkbox' name='inactive${staff_count}'id='inactive${staff_count}' value='1' ${checked}>`
        let admin_level_col = get_admin_level_select(`admin_level${staff_count}`, admin_level)
        let delete_col = `<input type='checkbox' name='delete${staff_count}' id='delete${staff_count}' value='1'>`
        let action_col = `<button type='button' class='btn btn-primary btn-sm' name='name${staff_count}' id='id${staffid}' onclick='changestaff(this);'>Submit</button>`

        /*
        {data: 'staffid'},
        {data: 'firstname'},
        {data: 'familyname'},
        {data: 'fullname'},
        {data: 'email'},
        {data: 'username'},
        {data: 'inactive'},
        {data: 'admin_level'},
        {data: 'delete'},
        {data: 'action'}
        */
        let row_data = {
            'staffid': staffid_col,
            'firstname': firstname_col,
            'familyname' : familyname_col,
            'fullname': fullname_col,
            'email': email_col,
            'username': username_col,
            'inactive': inactive_col,
            'admin_level': admin_level_col,
            'delete': delete_col,
            'action': action_col
        }
        return row_data
    }
    function get_admin_level_select(NameKey,admin_level_match){
        let levels = Array( "-1", "0", "1", "2", "3")

        let select_block = `<select name='${NameKey}'>`
        let selected
        for (let i = 1; i <= 4; i++) {
            let Level = levels[i]

            if (admin_level_match == Level) {
                selected = "selected"
            } else {
                selected = ""
            }

            select_block += `<option value='${Level}' ${selected}>${Level}</option>`
        }
        select_block += "</select>"

        return select_block;
    }
    /**
     * sends a request to the specified url from a form. this will change the window location.
     * @param {string} path the path to send the post request to
     * @param {object} params the parameters to add to the url
     * @param {string} [method=post] the method to use on the form
     */
    function post(path, params, method='post') {
        // The rest of this code assumes you are not using a library.
        // It can be made less verbose if you use one.
        const form = document.createElement('form');
        form.method = method;
        form.action = path;

        for (const key in params) {
            if (params.hasOwnProperty(key)) {
                const hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.name = key;
                hiddenField.value = params[key];

                form.appendChild(hiddenField);
            }
        }

        document.body.appendChild(form);
        form.submit();
    }
    function changestaff(obj){
        let staffid = obj.id.substring(2);
        let staff_count = obj.name.substring(4);
        
        let item_name = 'initials'+staff_count.toString();
        let selector = '[name='+item_name+']';
        let initials = $(selector).val();
        
        item_name = 'title'+staff_count.toString();
        selector = '[name='+item_name+']';
        let title = $(selector).val();
        
        item_name = 'firstname'+staff_count.toString();
        selector = '[name='+item_name+']';
        let firstname = $(selector).val();
        
        item_name = 'familyname'+staff_count.toString();
        selector = '[name='+item_name+']';
        let familyname = $(selector).val();
        
        item_name = 'email'+staff_count.toString();
        selector = '[name='+item_name+']';
        let email = $(selector).val();
        
        item_name = 'username'+staff_count.toString();
        selector = '[name='+item_name+']';
        let username = $(selector).val();
        
        item_name = 'inactive'+staff_count.toString();
        selector = '#'+item_name;
        let inactive = 0
        if($(selector).is(':checked')){
            inactive = 1
        }

        item_name = 'admin_level'+staff_count.toString();
        selector = '[name='+item_name+']';
        let admin_level = $(selector).val();

        item_name = 'delete'+staff_count.toString();
        selector = '#'+item_name;
        let del = 0
        if($(selector).is(':checked')){
            del = 1
        }
        
        let action = 'submit_edited'
        let paramsObj = {
            staffid: staffid,
            firstname: firstname,
            familyname: familyname,
            email: email,
            username: username,
            inactive: inactive,
            admin_level: admin_level,
            delete: del,
            op: action
        }
        let target = <?php echo json_encode($_SERVER["PHP_SELF"]);?>;
        post(target,paramsObj)
        //post('./edit_staff_table_bs.php',paramsObj)
        //alert('Staff ID: ' + staffid + ' Staff count: ' + staff_count + ' ' + firstname + ' ' + familyname);
    }
    function newstaff(){
        let selector = '#new_firstname'
        let firstname = $(selector).val().trim();
        selector = '#new_familyname'
        let familyname = $(selector).val().trim();
        selector = '#new_username'
        let username =  $(selector).val().trim();
        selector = '#new_email'
        let email =  $(selector).val().trim();

        let action = 'submit_new'
        let paramsObj = {
            firstname: firstname,
            familyname: familyname,
            email: email,
            username: username,
            op: action
        }
        let target = <?php echo json_encode($_SERVER["PHP_SELF"]);?>;
        post(target,paramsObj)
    }
    function uploadxlsx(){
        let selector = '#csv_filename'
        let csv_filename = $(selector).val().trim();
        let action = 'submit_upload_xlsx'
        let paramsObj = {
            op: action
        }
        let target = <?php echo json_encode($_SERVER["PHP_SELF"]);?>;
        post(target,paramsObj)
    }
    function downloadxlsx(){
        let action = 'submit_download_xlsx'
        let paramsObj = {
            op: action
        }
        let target = <?php echo json_encode($_SERVER["PHP_SELF"]);?>;
        post(target,paramsObj)
    }


</script>
<script>
    // This gets all of the data from the database
    $(document).ready(function () {
        setup_staff_table();
    });
</script>
</HTML>
