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

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

//error_reporting(E_ALL & ~E_NOTICE);
require_once __BASE_DIR__ . '/vendor/autoload.php';

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

$display_block .= "<h1>Upload / View Assessment Criteria</h1>";
//$display_block .= "<h2>Database table to be updated \"$assessment_criteria_table\"</h2>";

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

if ($access) {

    if ($POST_OP == "upload_csv") {
        $mark_col = 1;
        $class_col = 2;
        $grade_col = 3;
        $gpa_col = 4;
        $university_col = 5;
        $school_col = 6;

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
                    $col = $mark_col;
                    $mark = $worksheet->getCell([$col, $row])->getValue();
                    $col = $class_col;
                    $class = $worksheet->getCell([$col, $row])->getValue();
                    $col = $grade_col;
                    $grade = $worksheet->getCell([$col, $row])->getValue();
                    $col = $gpa_col;
                    $gpa = $worksheet->getCell([$col, $row])->getValue();
                    $col = $university_col;
                    $university = $worksheet->getCell([$col, $row])->getValue();
                    $col = $school_col;
                    $school = $worksheet->getCell([$col, $row])->getValue();

                    // Does this mark exist (it should always exist unless this is a new setup)
                    $query = "select * from $assessment_criteria_table where mark='$mark'";
                    try {
                        $results = $db->query($query);
                    } catch (PDOException $ex) {
                        $this_function = __FUNCTION__;
                        echo "An Error occured accessing the database in function: $this_function <BR>\n";
                        error_log($ex->getMessage());
                        echo "A database error occurred. Please contact the system administrator.<BR>\n";

                        exit();
                    }
                    $num = $results->rowCount();

                    // REPLACE does a UPDATE or INSERT (Actually if exists it does a delete)
                    if ($num == 0) {
                        $query = "INSERT INTO $assessment_criteria_table (mark,class,grade_text,gpa,descriptor_university,descriptor_school) VALUES (";
                        $query .= "'" . addslashes($mark) . "', ";
                        $query .= "'" . addslashes($class) . "', ";
                        $query .= "'" . addslashes($grade) . "', ";
                        $query .= "'" . addslashes($gpa) . "', ";
                        $query .= "'" . addslashes($university) . "', ";
                        $query .= "'" . addslashes($school) . "') ";
                        //echo("$query<BR>");
                        //mysql_query($query) or die(" Unable to insert data to the table $assessment_criteria_table . <BR>Query: $query");
                        try {
                            $results = $db->query($query);
                        } catch (PDOException $ex) {
                            $this_function = __FUNCTION__;
                            echo("An Error occured accessing the database in function: $this_function <BR>\n");
                            error_log($ex->getMessage());
                            echo "A database error occurred. Please contact the system administrator.<BR>\n";
                            exit();
                        }
                    } else {
                        $query = "UPDATE $assessment_criteria_table SET ";
                        //$query .= "sid='" . addslashes($sid) . "', ";
                        $query .= "class='" . addslashes($class) . "', ";
                        $query .= "grade_text='" . addslashes($grade) . "', ";
                        $query .= "gpa='" . addslashes($gpa) . "', ";
                        $query .= "descriptor_university='" . addslashes($university) . "', ";
                        $query .= "descriptor_school='" . addslashes($school) . "' WHERE mark=$mark ";
                        //echo("$query<BR>");
                        //mysql_query($query) or die(" Unable to insert data to the table $assessment_criteria_table . <BR>Query: $query");
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

                }
            }
        }

    }// end of if ($POST_OP == "submint_new")


// Get the list of allcoations from the students table
    $query = "SELECT * FROM $assessment_criteria_table ORDER BY mark DESC";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $rows = $results->fetchAll();
//$display_block = "<TABLE  class=\"tableSimple\" style=\"border:1px solid black;border-collapse:collapse;\">\n";
    $display_block .= "<TABLE id=\"assessmentcriteria\" class=\"hover compact cell-border\" cellspacing=\"0\" width=\"100%\">\n";
    $display_block .= "
    <thead>
      <tr>
        <th>Mark</th>
        <th>Class</th>
        <th>Grade</th>
        <th>GPA</th>
        <th>University Descriptor</th>
        <th>School Descriptor</th>
      </tr>
    </thead>";
    $display_block .= "<tbody>\n";
    $student_count = 0;
    foreach ($rows as $recs) {
        $mark = $recs['mark'];
        $class = $recs['class'];
        $grade = $recs['grade_text'];
        $gpa = $recs['gpa'];
        $university_text = $recs['descriptor_university'];
        $school_text = $recs['descriptor_school'];

        $display_block .= "<TR>\n";
        $display_block .= "<TD style='font-size: small;'>$mark</TD>";
        $display_block .= "<TD style='font-size: small;'>$class</TD>";
        $display_block .= "<TD style='font-size: small;'>$grade</TD>";
        $display_block .= "<TD style='font-size: small;'>$gpa</TD>";
        $display_block .= "<TD style='font-size: small;'>$university_text</TD>";
        $display_block .= "<TD style='font-size: small;'>$school_text</TD>";
        $display_block .= "</TR>\n";

    }
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Upload a XLSX/CSV of the Assessment Criteria</H2>\n";
    $display_block .= "\n<form method=\"post\" action=\"$_SERVER[PHP_SELF]\" enctype=\"multipart/form-data\">\n";
    $display_block .= "<P> Create a XLSX/CSV with the headings:<B>Mark</B>, <B>Class</B>, <B>Grade</B>,  <B>GPA</B>, <B>University Descriptor</B> , <B>School Descriptor</B> and load here.</P>";
    $display_block .= "<P>(This should have in all (integer) marks from 0 to 100)</P>";
    $display_block .= "<P></P><input name=\"csv\" type=\"file\" id=\"csv\" /></P>";
    $display_block .= "\n<input type=\"hidden\" name=\"op\" value=\"upload_csv\">";

    $display_block .= "<p><input type=\"submit\" id=\"submitbutton_csv\" name=\"submit\" value=\"Upload a XLSX/CSV of the Assessment Criteria\"></p>";

    $display_block .= "</form>\n";
//---------------------------------------------------------------------------------
} else {
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}
?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) or die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<script>
    $(function () {
        $("#assessmentcriteria").dataTable(
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
                class="header">View / Load Assessment Criteria - (this is common to all modules)</span>
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
