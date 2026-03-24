<?php
/**
 * Created by PhpStorm.
 * User: cenpas
 * Date: 11/06/2016
 * Time: 20:28
 */
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./utility_functions.php")) OR die("Cannot read utility_functions file<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot read markstore_functions file<BR>");
//(@include_once("../staff_functions.php")) OR die("Cannot read markstore_functions file<BR>");

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
//error_reporting(E_ALL & ~E_NOTICE);
require_once __BASE_DIR__ . '/vendor/autoload.php';

$display_block = "";
$display_block .= $connect_msg;

$assess_part_description_text = "";

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db,$this_staff_username, $this_staff_full_name, $staff_names,$access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase );
if($is_module_admin){
    $access_phrase = $module_level_access_phrase;//"You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful how you use it.";
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if($is_module_admin || $admin_access) {


    $display_block .= "<h1>Upload Totals for a Part of the Poject </h1>\n";
    $display_block .= "<h2>Database table to be updated \"$markstore_totals\"</h2>\n";

    $display_block .= "<h1>Be VERY VERY carefull as this overwrites data and could ruin the database.</h1>\n";

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
    if (array_key_exists('radio_part_number', $_POST) == false) {
        $_POST['radio_part_number'] = null;
    }
    $radio_part_number = $_POST['radio_part_number'];

    if ($POST_OP == "upload_csv") {

        if (!is_null($radio_part_number)) {

            // For the part mark we will only import into the 1st marker
            $marker_num = 1;
            $markstore_part = $markstores[$radio_part_number][$marker_num];

            $sid_col = 1;
            $student_name_col = 2;
            $part_col = 3;
            $part_total_col = 4;

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
                        $col = $sid_col;
                        $sid = $worksheet->getCell([$col, $row])->getValue();
                        $col = $student_name_col;
                        $student_name = $worksheet->getCell([$col, $row])->getValue();
                        $col = $part_col;
                        $csv_part_num = $worksheet->getCell([$col, $row])->getValue();
                        $col = $part_total_col;
                        $part_total = $worksheet->getCell([$col, $row])->getValue();

                        if ($csv_part_num == $radio_part_number) {
                            // Put this in the totals marksheet
                            $query = "select * from $markstore_totals WHERE sid=$sid";
                            try {
                                $results = $db->query($query);
                            } catch (PDOException $ex) {
                                $this_function = __FUNCTION__;
                                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                                echo(" Query = $query<BR> \n");
                                echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                                exit();
                            }
                            $row = $results->fetch();
                            $num = $results->rowCount();
                            //echo("$num<BR>");

                            $part_header = "retmark" . $radio_part_number;
                            // REPLACE does a UPDATE or INSERT (Actually if exists it does a delete)
                            if ($num == 0) {
                                $query = "INSERT INTO $markstore_totals (SID,$part_header) VALUES (";
                                $query .= "'" . addslashes($sid) . "', ";
                                $query .= "'" . addslashes($part_total) . "') ";
                                echo("$query<BR>");
                                try {
                                    $results = $db->query($query);
                                } catch (PDOException $ex) {
                                    $this_function = __FUNCTION__;
                                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                                    echo(" Query = $query<BR> \n");
                                    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                                    exit();
                                }
                            } else {
                                $query = "UPDATE $markstore_totals SET ";
                                //$query .= "sid='" . addslashes($sid) . "', ";
                                $query .= "$part_header='" . addslashes($part_total) . "' WHERE sid=$sid ";
                                echo("$query<BR>");
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

                            // Put this in the part marksheet, under marker 1
                            $query = "select * from $markstore_part WHERE sid=$sid";
                            //echo("$query<BR>");
                            try {
                                $results = $db->query($query);
                            } catch (PDOException $ex) {
                                $this_function = __FUNCTION__;
                                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                                echo(" Query = $query<BR> \n");
                                echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                                exit();
                            }
                            $row = $results->fetch();
                            $num = $results->rowCount();
                            //echo("$num<BR>");

                            $part_header = "total";
                            $feedback_text = "There is no recorded feedback for this part as this was by a bulk uploaded mark list.\n";
                            $feedback_text .= "Feedback will be provided separately.";
                            // REPLACE does a UPDATE or INSERT (Actually if exists it does a delete)
                            if ($num == 0) {
                                $query = "INSERT INTO $markstore_part (SID,C1Feedback,$part_header) VALUES (";
                                $query .= "'" . addslashes($sid) . "', ";
                                $query .= "'" . addslashes($feedback_text) . "', ";
                                $query .= "'" . addslashes($part_total) . "') ";
                                echo("$query<BR>");
                                try {
                                    $results = $db->query($query);
                                } catch (PDOException $ex) {
                                    $this_function = __FUNCTION__;
                                    echo("An Error occured accessing the database in function: $this_function <BR>\n");
                                    echo(" Query = $query<BR> \n");
                                    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
                                    exit();
                                }
                            } else {
                                $query = "UPDATE $markstore_part SET ";
                                $query .= "C1Feedback='" . addslashes($feedback_text) . "', ";
                                $query .= "$part_header='" . addslashes($part_total) . "' WHERE sid=$sid ";
                                echo("$query<BR>");
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
                            echo("Part numbers do not match in the XLSX/CSV file and the radio button. for $sid, $student_name <BR\n");
                        }

                    }
                }
            }
        } else {
            echo("You must select a part number by clicking the appropriate radio button. <BR>\n");
        }
    }// end of if ($POST_OP == "upload_csv")


//$display_block .="\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\">\n";

// Get the list of allcoations from the students table
    $query = "select * from $markstore_totals";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }
    $rows = $results->fetchAll();
    $num = $results->rowCount();
//$display_block = "<TABLE  class=\"tableSimple\" style=\"border:1px solid black;border-collapse:collapse;\">\n";
    $display_block .= "\n<TABLE id=\"marktotals\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
    $display_block .= "
    <thead>
      <tr>
        <th>SID</th>
        <th>Name</th>
        <th>Total: Part 1</th>
        <th>Total: Part 2</th>
        <th>Total: Part 3</th>
        <th>Total: Part 4</th>
        <th>Total: Part 5</th>
        <th>Final Total</th>
      </tr>
    </thead>";
    $display_block .= "<tbody>\n";
    $student_count = 0;
    foreach ($rows as $recs) {
        $sid = $recs['SID'];
        $retmark1 = $recs['retmark1'];
        $retmark2 = $recs['retmark2'];
        $retmark3 = $recs['retmark3'];
        $retmark4 = $recs['retmark4'];
        $retmark5 = $recs['retmark5'];
        $total_mark = $recs['total'];
        //print_r2($recs);
        $query = "select * from $students where sid=$sid";
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo("An Error occured accessing the database in function: $this_function <BR>\n");
            echo(" Query = $query<BR> \n");
            echo(" Err message: " . $ex->getMessage() . "<BR>\n");
            exit();
        }
        $s_recs = $results->fetch();
        $student_name = stripslashes($s_recs['name']);

        $student_count++;
        $display_block .= "<TR>\n";
        /*
            $display_block .= "<TD><input type=\"text\" name=\"sid$student_count\" value=\"$sid\"  size=10 maxlength=10 readonly></TD>";
            $display_block .= "<TD><input type=\"text\" name=\"name$student_count\" value=\"$student_name\"  size=20 maxlength=20 readonly></TD>";
            $display_block .= "<TD><input type=\"text\" name=\"retmark1$student_count\" value=\"$retmark1\"  size=10 maxlength=100 readonly></TD>";
            $display_block .= "<TD><input type=\"text\" name=\"retmark2$student_count\" value=\"$retmark2\"  size=10 maxlength=100 readonly></TD>";
            $display_block .= "<TD><input type=\"text\" name=\"retmark3$student_count\" value=\"$retmark3\"  size=10 maxlength=100 readonly></TD>";
            $display_block .= "<TD><input type=\"text\" name=\"retmark4$student_count\" value=\"$retmark4\"  size=10 maxlength=100 readonly></TD>";
            $display_block .= "<TD><input type=\"text\" name=\"retmark5$student_count\" value=\"$retmark5\"  size=10 maxlength=100 readonly></TD>";
            $display_block .= "<TD><input type=\"text\" name=\"total$student_count\" value=\"$total_mark\"  size=10 maxlength=30 readonly></TD>";
        */
        $display_block .= "<TD>$sid</TD>";
        $display_block .= "<TD>$student_name</TD>";
        $display_block .= "<TD>$retmark1</TD>";
        $display_block .= "<TD>$retmark2</TD>";
        $display_block .= "<TD>$retmark3</TD>";
        $display_block .= "<TD>$retmark4</TD>";
        $display_block .= "<TD>$retmark5</TD>";
        $display_block .= "<TD>$total_mark</TD>";

        //print_r2($recs);

        $display_block .= "</TR>\n";

    }
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"submit_edited\">";
    $display_block .= "\n<input type=\"hidden\" name=\"student_count\" value=\"$student_count\">\n";

//$display_block .="<p><input type=\"submit\" id=\"submitbutton\" name=\"submit\" value=\"Submit changes to student details.\"></p>";

//$display_block .="</form>\n";

//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Upload XLSX/CSV... </H2>\n";
    $display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]?pm_id=$pm_id\" enctype=\"multipart/form-data\">\n";
    $display_block .= "<P> Upload a XLSX/CSV file to enter totals data for a part <B>overwriting and data there</B></P>\n";
    $display_block .= "<P> Create a XLSX/CSV with the headings:<B>SID</B>, <B>Student Name</B>, <B>Part Num</B>,  <B>Total Mark for part</B> and load here.</P>\n";
    $display_block .= "<input type='radio' name='radio_part_number' value=1> part 1<br>\n";
    $display_block .= "<input type='radio' name='radio_part_number' value=2> part 2<br>\n";
    $display_block .= "<input type='radio' name='radio_part_number' value=3> part 3<br>\n";
    $display_block .= "<input type='radio' name='radio_part_number' value=4> part 4<br>\n";
    $display_block .= "<input type='radio' name='radio_part_number' value=5> part 5<br>\n";

    $display_block .= "<P><B> Note 1: From the XLSX/CSV  file the student name is not used.</B><br>\n";
    $display_block .= "<B> Note 2: The part number in the XLSX/CSV must match the part number selected above.</B></P>\n";

    $display_block .= "<P></P><input name=\"csv\" type=\"file\" id=\"csv\" /></P>\n";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"upload_csv\">\n";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_csv\" name=\"submit\" value=\"Upload the XLSX/CSV \"></p>\n";

    $display_block .= "</form>\n";

//---------------------------------------------------------------------------------
}else{
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}

?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<script>
    $(function () {
        $("#marktotals").dataTable(
            {
                //"bSort":    false,
                "paging": false,
                "ordering": true,
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
            class="header">Upload Total data for Part - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div id="footer">
        <P>Return to the
            <a href="../projectmarking_select.php?pm_id=<?php echo("$pm_id") ?>">Module page</a> -
            <a href="../session_modules.php?year_begin= <?php echo("$year_begin") ?>">Year index page</a> -
            <a href="./edit_module_settings.php?pm_id=<?php echo("$pm_id") ?>">Module admin page</a> -
            <a href="./index.php">Main admin page</a>.
        </P>
    </div><!-- /footer -->

</div><!-- /wrapper -->
</BODY>
</HTML>
