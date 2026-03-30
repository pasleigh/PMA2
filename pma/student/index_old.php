<?php
(@include_once("./set_dir_student.php")) OR die("Cannot find this file to include: set_dir_student.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./student_functions.php")) OR die("Cannot include student_function.php<BR>");

(@include_once("$ADMIN_DIR_VAL/utility_functions.php")) OR die("Cannot include utility_functions.php");
// Select the years available in the projects setup table
$display_block = "";
$display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center'>Project Selection</div>";

$student_username = $_SERVER['REMOTE_USER'];

$student_username = "unknown";
if(array_key_exists('REMOTE_USER',$_SERVER)){
    $student_username = get_server_username();
}else{
    if(isset($_SESSION['USERNAME'])){
        $student_username = $_SESSION['USERNAME'];
    }
}

$is_staff = isStaff($db, $student_username);

$staff_text = "";
if ($is_staff) {
    $staff_username = $student_username;
    $staff_name = GetStaffNameFromUsername($db, $staff_username, $staff_names_default, null);
    $staff_text = "($staff_name, staff)";

    $display_welcome_block = "<div>You are $staff_name ($staff_username) $staff_text.</div>";
}else{
    // cant get student name unless search all possible student name tables... TODO
    //$student_name = GetStudentNameFromUsername($db, $student_username, $students);
    //$display_block .= "<div>You are $student_name ($student_username) .</div>";
    $display_welcome_block = "<div>You are logged in with username: $student_username.";
}

$count_modules = 0;

// Get the full list of modules from the project_setup table
$query = "select * from $projects_setup ORDER BY year_begin DESC, module";
try {
    $results = $db->query($query);
} catch (PDOException $ex) {
    echo "An Error occured accessing the $projects_setup table<BR>\n";
    echo(" Query = $query<BR> \n");
    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
    exit();
}
$res = $results->fetchAll();

if($is_staff) {
    // Start the collapsible
    $display_block .= "<div data-role='collapsible-set' data-theme='e' data-content-theme='d'>";
    $display_block .= "    <div data-role='collapsible'>";
    $display_block .= "        <h3>Click the \"+\" to see Module list</h3>";
}

// Go through the projects with that year_begin and see if this user is in it
foreach ($res as $rec) {
    $pm_id = $rec['id'];

    $module1 = $rec['module'];
    $module_prefix = $rec['module_prefix'];
    $module_postfix = $rec['module_postfix'];
    $module_short_description = $rec['module_short_desc'];
    $short_code = $rec['short_code'];
    $year_begin = $rec['year_begin'];
    $year_end = $rec['year_end'];
    $year_end_short = $year_end - 2000;
    $short_code_lc = strtolower($short_code);

    $staff_close = $rec['staff_close'];
    $sel_open = $rec['sel_open'];
    $sel_close = $rec['sel_close'];
    $alloc_date = $rec['alloc_date'];

    $prefix = "";
    // Students and their allocations
    $students = $prefix . "students_" . $short_code_lc . "_" . $year_begin;


    // Get the details of this project pm_id
    $query = "SELECT * FROM $students WHERE username='$student_username'";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $students table<BR>\n";
        echo(" Query = $query<BR> \n");
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }


    if ($results->rowCount() == 1 or $is_staff) {
        // The student is in this module
        // Get the student name
        $rec = $results->fetch();
        if($rec){
            $student_fullname = $rec['name'];
        }else{
            $student_fullname = "Not a student";
        }

        // Is the date past?
        $staff_close_date = $staff_close;
        $str_staff_close_date = date("j F Y", strtotime($staff_close_date));

        $sel_close_date = $sel_close . " 23:59";
        $str_sel_close_date = date("j F Y, H:i", strtotime($sel_close_date));

        $sel_open_date = $sel_open . " 00:00";
        $str_sel_open_date = date("j F Y, H:i", strtotime($sel_open_date));

        $now_datetime = date("Y-m-d H:i:s");
        $close_date_diff = day_diff($now_datetime, $sel_close_date);
        $open_date_diff = day_diff($now_datetime, $sel_open_date);

        $days = "days";
        if ($close_date_diff > 0.0 or $open_date_diff < 0.0) {
            // Closed or not yet open
            if ($close_date_diff > 0.0) {
                // The close date has passed
                $display_block .= "<A href=\"./projects_indexm_all.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"e\" >$module_short_description - $module_prefix$module1$module_postfix - $short_code, $year_begin/$year_end_short<BR>(<span style='color: darkred;'>Selection closed on $str_sel_close_date</span>)</A>\n";
            } else {
                $str_sel_open_dhms = secondsToTime($open_date_diff * 24 * 60 * 60);
                $display_block .= "<A href=\"./projects_indexm_all.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"e\" class=\"ui-disabled\">$module_short_description - $module_prefix$module1$module_postfix - $short_code, $year_begin/$year_end_short<BR>(Opens: $str_sel_open_date in $str_sel_open_dhms <BR>Closes: $str_sel_close_date)</A>\n";

            }
        } else {
            // Still able to choose
            $int_close_date_diff = intval(-1 * $close_date_diff) ;
            if ($int_close_date_diff == 1) {
                $days = "day";
            }
            if($close_date_diff > 0) {
                //$str_close_date_diff = "in $close_date_diff "  . gmdate('H:i', floor(-1 * $close_date_diff * 24 * 3600)) . " Hours:mins";
                $str_close_date_diff = "in "  . secondsToTime(-1 * $close_date_diff * 24 * 3600);
            }else{
                $str_close_date_diff =  secondsToTime($close_date_diff * 24 * 60 * 60);
            }
            $display_block .= "<A href=\"./projects_indexm_all.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"e\" >$module_short_description - $module_prefix$module1$module_postfix - $short_code , $year_begin/$year_end_short<BR>Closes: $str_sel_close_date ($str_close_date_diff)</A>\n";
        }
        $count_modules++;
    }
}

if($is_staff) {
// Close the collapsible
    $display_block .= "  </div>";
    $display_block .= "</div>";
}

// add student name if a student
if(!$is_staff){
    $display_welcome_block = "<div>Hello, $student_fullname, you are logged in with username: $student_username.</div>";
}
// prefix this to the display block
$display_block = $display_welcome_block . $display_block;

$trailing_word = "modules";
$trailing_word2 = "exist";
if ($count_modules == 1) {
    $trailing_word = "module";
    $trailing_word2 = "exists";
}
if ($is_staff) {
    $display_block .= "<div><BR>As a member of staff you are seeing the $count_modules $trailing_word that $trailing_word2 in the database. Students will only see modules that they are named on.</div>";
} else {
    $display_block .= "<div><BR>You are named in $count_modules $trailing_word</div>";
}

// Is dash feedback available?
$query = "SELECT * FROM $dash_projects_setup";
try{
    $results = $db->query($query);
} catch (PDOException $ex) {
    echo "An Error occured accessing the $dash_projects_setup table<BR>\n";
    echo(" Err message: " . $ex->getMessage() . "<BR>\n");
    exit();
}
$recs = $results->fetchAll();

$dash_visible_student = $recs[0]['student'];

if($dash_visible_student == 1){
    //$display_block .= "<BR>";
    //$display_block .= "<P>These Feedback tools are available</P>";
    //$display_block .= "<A href='./dash_selection.php' rel='external' data-role='button' data-theme='b'>Feedback Tools Dashboard</A>\n";


    $display_block .= "<BR>";
    $display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center'>Feedback Dashboard</div>";
    $display_block .= "<P>These feedback tools are available</P>";

    //$display_block .= "<A href='./dash_selection.php' rel='external' data-role='button' data-theme='b'>Feedback Tools Dashboard</A>\n";
    // PEGA
    $id = $pega_id;
    $description = $recs[$id]['description'];
    $title = $recs[$id]['title'];
    $subtitle = $recs[$id]['subtitle'];
    $student_visible = $recs[$id]['student'];
    if($student_visible == 1) {
        $display_block .= "<A href='./pega/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
    }else{
        //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
    }
    // HAMID
    $id = $hamid_id;
    $description = $recs[$id]['description'];
    $title = $recs[$id]['title'];
    $subtitle = $recs[$id]['subtitle'];
    $student_visible = $recs[$id]['student'];
    if($student_visible == 1) {

        $display_block .= "<A href='./hamid/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
    }else{
        //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
    }
    // DIST
    $id = $dist_id;
    $description = $recs[$id]['description'];
    $title = $recs[$id]['title'];
    $subtitle = $recs[$id]['subtitle'];
    $student_visible = $recs[$id]['student'];
    if($student_visible == 1) {

        $display_block .= "<A href='./dist/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
    }else{
        //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
    }
    // CWT
    $id = $cwt_id;
    $description = $recs[$id]['description'];
    $title = $recs[$id]['title'];
    $subtitle = $recs[$id]['subtitle'];
    $student_visible = $recs[$id]['student'];
    if($student_visible == 1) {

        $display_block .= "<A href='./cwt/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
    }else{
        //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
    }
    // PEER
    $id = $peer_id;
    $description = $recs[$id]['description'];
    $title = $recs[$id]['title'];
    $subtitle = $recs[$id]['subtitle'];
    $student_visible = $recs[$id]['student'];
    if($student_visible == 1) {

        $display_block .= "<A href='./peer/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
    }else{
        //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
    }


}

$footer_link = date('l jS \of F Y, h:i A');



?>

<!DOCTYPE HTML>

<html>
<?php (@include_once("$STAFF_DIR_VAL/$mobile_head")) OR die("Cannot find this file to include: $mobile_head<BR>"); ?>

<body>

<div data-role="page" data-theme="a" id="home">

    <!---------------------START of HEADER------------------------------------->
    <div id="jqm-homeheader" data-role="header">
        <h1 id="jqm-logo"><img src="../staff/images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/></h1>
        <p><?php echo("$school_title $running_title"); ?></p>
    </div>
    <!---------------------END of HEADER------------------------------------->

    <!--++++Main page+++++++-->
    <div data-role="content">

        <div data-role="controlgroup">
            <?php echo("$display_block"); ?>
        </div>

    </div><!-- content -->


    <div data-role="footer" data-position="fixed">
        <H1 style="color: dimgray;">
            <?php echo("$footer_link"); ?>
        </H1>
    </div><!-- /footer -->

</div> <!-- page -->
<!--++++-------End of Main page---------+++++++-->


</body>
</html>

  