<?PHP
(@include_once("./set_dir_props.php")) OR die("Cannot find this file to include: set_dir_props.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../staff_student_functions.php")) OR die("Cannot find this file to include: staff_student_functions.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables");

(@include_once("../admin2/utility_functions.php")) OR die("Cannot setup utility functions");
(@include_once("../admin2/adminusers.php")) OR die("Cannot setup admin users");

$display_block = "";

$is_staff = IsStaff($db,$this_staff_username,$staff_names);

if ($is_staff) {
    $main_body_visible = "block";
    $warning_visible = "none";
    $staff_full_name = $this_staff_full_name;

    if (array_key_exists('pm_id', $_REQUEST) == false) {
        $_REQUEST['pm_id'] = null;
    }
    $pm_id = $_REQUEST['pm_id'];


    $query = "select * from $projects_setup where id=$pm_id";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $res = $results->fetch();

    $module1 = $res['module'];
    $year_begin = $res['year_begin'];
    $year_end = $res['year_end'];
    $year_end_short = $year_end-2000;
    $module_prefix = $res['module_prefix'];
    $module_short_description = $res['module_short_desc'];
    $module_short_code = $res['short_code'];

    $staff_close_date = $res['staff_close'] . " 23:59";
    $str_staff_close_date = date("j F Y, H:i", strtotime($staff_close_date));
    $student_allocation_date = $res['alloc_date'];
    $str_student_allocation_date = date("j F Y", strtotime($student_allocation_date));
    $today = date("Y-m-d");

    $now_datetime = date("Y-m-d H:i:s");

    $display_block = "";
    $display_block_edit_buttons = "";

    $date_diff = day_diff($now_datetime, $staff_close_date);

    $project_heading = "$module_short_description $year_begin/$year_end_short - $module_prefix$module1$module_postfix - $module_short_code";

    // The closing date string

    // Check the dates
    $date_passed_display = "none";
    $date_passed_display_admin = "none";
    $active_button = "";
    if($date_diff > 1.0 ){
        $date_passed_display = "block";
        $active_button = "disabled";
        //$display_block .= "<H1>The deadline for entering project proposals has passed.</H1>";
        if (IsAdmin($db, $this_staff_username) or IsModuleAdmin($db, $this_staff_username, $pm_id, $module_short_code, $year_begin)) {
            $date_passed_display_admin = "block";
            $active_button = "";
        }
    }

    // Now get all the projects
    $project_rows = "";

    $query = "SELECT * FROM $master_name ORDER BY id, f_supervisor, f_title";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo("An Error occured accessing the database in function: $this_function <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $num_projects = $results->rowCount();

    if ($num_projects < 1) {
        //no records
    } else {
        //has records
        $rows = $results->fetchAll();

        $count = 1;
        foreach ($rows as $recs) {
            $master_id = $recs['id'];
            $project_title = $recs['f_title'];
            $supervisor_name = $recs['f_supervisor'];

            // Get the details
            $query = "SELECT * FROM $project_details WHERE master_id='$master_id' ";
            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo("An Error occured accessing the database in function: $this_function <BR>\n");
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
            $row = $results->fetch();
            $date_added=$row['date_added'];
            $date_modified=$row['date_modified'];
            $second_marker_name=$row['f_assocstaff'];
            $f_assocstaff=$row['f_assocstaff'];
            $f_assocstaff_id=$row['f_assocstaff_id'];
            $f_outline=$row['f_outline'];
            $f_relmods=$row['f_relmods'];
            $f_backread=$row['f_backread'];

            $f_relsites=$row['f_relsites'];
            $f_skills=$row['f_skills'];
            $f_software=$row['f_software'];
            $f_softsupport=$row['f_softsupport'];
            $f_labs=$row['f_labs'];
            $f_works=$row['f_works'];
            $f_programmes=$row['f_programmes'];
            $f_progs=$row['f_progs'];
            $f_UGprojects=$row['f_UGprojects'];
            $f_PGprojects=$row['f_PGprojects'];
            $f_furtherinfo=$row['f_furtherinfo'];

            //$display_block .= "$master_id: $project_title<BR>";
            $display_block .= "<a href='viewproject2.php?pm_id=$pm_id&p_id=$master_id' rel='external' data-role='button' data-theme=\"b\">$count:  ($master_id) $project_title - $supervisor_name</a>\n";

            // count, index, Supervisor, title, places, Edit button, delete button
            $edit_link = "<a href='./proposal_edit.php?pm_id=$pm_id&p_id=$master_id' class='btn btn-success btn-block btn-sm $active_button'>View/Edit</a>";
            $project_rows .= "<TR><TD>$count</TD><TD>$master_id</TD><TD>$supervisor_name</TD><TD>$project_title</TD><TD>$f_PGprojects</TD><TD>$edit_link</TD></TR>\n";

            $count++;

        }
    }

}else{
    //$display_block .= "<H3>You do not have a staff username on this system. <A href='./'>Go Back to the main page</A></H3>";
    $main_body_visible = "none";
    $warning_visible = "block";
}

/* Set in setup_titles */
/*
$school = $result['school_short_name'];
$header_image = $result['header_image_name'];
$school_title = $result['school_title'];
$school_module_prefix = $result['default_module_prefix'];
$running_title = $result['running_title'];
$running_subtitle = "";//$result['running_subtitle'];
*/




$text_area_placement_text = "placeholder='Type yout project description here...'";

// If editing existing entry then this should have the text from the database
$html_text_area_text = "";//"<H2>this is a header</H2> <UL><li>first</li><li>second</li></UL><P>And a new parahraph</P>";

// Used if html text from DB is blank
$text_project_title = "Is Concrete is King?";
$text_area_text = <<< END
So you want to discovery the secret of concrete's success? This is the project for you.

You will:
*  Do experiments in the lab to test various mixes and rebar arrangements
*  Undertake finite element simulations to help design the experiments and predict strength

Conclusions will be drawn on the ability of simulation to predict prototype structural properties.
END;
//"";//"* first\n* second\n* third row";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>View/Edit Project Proposal</title>

    <?php (@include_once("./mobile_head_props.php")) OR die("Cannot find this file to include: mobile_head_props.php<BR>"); ?>

    <style type="text/css">
        html,
        body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 30px 0 0 0;
            font-family: Geneva, Verdana, sans-serif, helvetica;
        }

        .navbar-fixed-top {
            min-height: 10px;
        }

        .navbar-custom {
            color: #FFFFFF;
            background-color: #006600;
        }

        /* These two set the nav bar font colour */
        .navbar-inverse .navbar-brand {color: #FFFFFF;}
        .navbar-inverse .navbar-nav>li>a {color: #FFFFFF;}

        #info02_div {
            border: 1px solid #ddd;
            padding: 8px;
            background: mintcream;
        }

        .maroon_heading{
            color: maroon;
            font-weight: bold;
        }
    </style>
</head>
<body>
<nav id="myNavbar" class="navbar navbar-default navbar-custom  navbar-inverse navbar-fixed-top"
     role="navigation">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="container">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#navbarCollapse">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <A class="navbar-brand"
               href="#"><?php echo("$school_title - $running_title ") ?><?php echo("$year_begin/$year_end") ?></A>
        </div>
        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" style="background-color: #006600;">
            <ul class="nav navbar-nav">
                <li><a href="#" target="_top"><?php echo("$module_short_description : $module_prefix$module1$module_postfix") ?></a></li>
            </ul>
            <ul class="nav navbar-nav navbar-right">
                <li><a id="id_admin" href="../" target="_top"> back</a></li>

            </ul>
        </div>
        <!-- dropdown -->


    </div>
</nav>

<div class="container-fluid ">
    <div class="row">
        <div class="span4"></div>
        <div class="span4">
            <!--<img class="center-block" src='<?php echo("../images/$header_image") ?>'/>-->
            <img class="center-block" src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
        </div>
        <div class="span4"></div>
    </div>
    <HR/>
</div>

<div class="container-fluid" style='display: <?php echo("$warning_visible"); ?>'>
    <H2>You do not have a staff username on this system.</H2>

    <p>Please <a href="../index.php">go back to the main home page</a></p>

</div>

<!------------------------ Main body of page begins here ---------------------------------------------->
<div class="container-fluid" style='display: <?php echo("$main_body_visible"); ?>' id="main_body">

    <div class="container">
        <h3>Project proposals: <?php echo("$project_heading"); ?></h3>

        <h3><strong>Hello <?php echo("$staff_full_name"); ?></strong></h3>

        <p>This page is the main page for entering project proposals. Here you can see a list of all of the
            existing project for this module. Click the green button to view/edit the projects desctiption.
            (To delete a project go to the view/edit page and delete from there.)</P>
        <P> To add a new project choose the add new project button. To download a list of the projects in csv
            format select that button.
            (This list is not pretty, but is functional. Excel may ask you if it is safe to open it. The risk that it is not safe is low.)</P>

        <H4>You will not be able to edit projects after this date: <span class="emph"><?php echo("$str_staff_close_date")?></span></H4>

        <H4 class="maroon_heading" style='display: <?php echo("$date_passed_display")?>'>
            The deadline for entering project proposals has passed.</H4>
        <H4 style='color: #6bc43d; font-weight: bold; display: <?php echo("$date_passed_display_admin")?>'>
            As module level administrator you have access to modifying projects past the deadline date. <span style='color: red;'>Do this sparingly.</span></H4>


    </div>

    <div class="container">
    <div class="row">
        <div class="col-sm-3">
            <a href="./addproject.php?pm_id=<?php echo("$pm_id")?>" class="btn btn-primary btn-block <?php echo("$active_button")?>">Add a new project</a>
        </div>
        <div class="col-sm-6">
        </div>
        <div class="col-sm-3">
            <a href="./listproject_csv.php?pm_id=<?php echo("$pm_id")?>" class="btn btn-warning btn-block">Save project list to .csv file</a>
        </div>
    </div>
    </div>

    <BR/>

    <div class='container'>

        <div>
            <table id="proposalsTable" class="hover compact cell-border" cellspacing="0" width="100%">
                <thead>
                <tr><th>Count</th><th>Index</th><th>Supervisor</th><th>Title</th><th>Places</th><th>Edit</th></tr>
                </thead>
                <tbody>
                <?php echo("$project_rows")?>
                </tbody>
            </table>
        </div>

    </div>

</body>
<script>
    $("#proposalsTable").dataTable(
        {
            //"bSort":    false,
            "paging": false,
            //"ordering": false,
            //"info":     false,
            "searching": false
        }
    );
</script>
</html>
