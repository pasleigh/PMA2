<?php
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("../setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");

(@include_once("../connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("../setup_tables.php")) OR die("Cannot setup database tables<BR>");
(@include_once("./utility_functions.php")) OR die("Cannot read utility_functions file<BR>");
(@include_once("../markstore_functions.php")) OR die("Cannot read markstore_functions file<BR>");
(@include_once("../staff_functions.php")) OR die("Cannot read markstore_functions file<BR>");

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$access = false;
$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod;'>$access_phrase</h2>\n";

if($access) {

    if (array_key_exists('op', $_POST) == false) {
        $_POST['op'] = null;
    }

    if (array_key_exists('op', $_POST)) {
        $POST_OP = $_POST['op'];
    } else {
        $POST_OP = "";
    }

// see all the POST values
//foreach ($_POST as $key => $value){
//}

    $display_block = "";
    $display_block .= $connect_msg;
    $display_block .= "<h1>View Staff Project Load</h1>";

    $staff_names_table = $staff_names2;
#project_allocated_staff

    $display_block .= "<h3>Database staff table: $staff_names_table<BR>allocation load table: $project_allocated_staff</h3>";

// Does $project_alllocated_staff exist?
    try {
        $result = $db->query("SELECT 1 FROM $project_allocated_staff LIMIT 1");
    } catch (Exception $ex) {
        // We got an exception == table not found
        // create it
        $query = <<<SQL
    CREATE TABLE IF NOT EXISTS `$project_allocated_staff` (
    `staffid` int(11) NOT NULL,
      `firstname` varchar(25) DEFAULT NULL,
      `familyname` varchar(50) DEFAULT NULL,
      `fullname` varchar(75) DEFAULT NULL,
      `num_supervise` int(11) DEFAULT '0',
      `num_second_mark` int(11) DEFAULT '0',
      `use` int(11) DEFAULT '1',
      PRIMARY KEY (`staffid`)
    ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
SQL;
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
    }

################# Update the "use to plot" data #####################################
    if ($POST_OP == "submit_edited") {
        $staff_count = $_POST['staff_count'];
        for ($i = 1; $i <= $staff_count; $i++) {
            $postid = "staffid$i";
            $staffid = $_POST[$postid];
            $postid = "use$i";
            if (array_key_exists($postid, $_POST) == false) {
                $_POST[$postid] = null;
            }
            $use_chk = $_POST[$postid];
            $query = "update $project_allocated_staff set `use`='$use_chk' where staffid=$staffid";

            try {
                $results = $db->query($query);
            } catch (PDOException $ex) {
                $this_function = __FUNCTION__;
                echo "An Error occured accessing the database in function: $this_function <BR>\n";
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";

                exit();
            }
        }
    }

    $display_block .= "Hide/Show table <input type='checkbox' id='hideshow_checkbox' ><BR>";
# $display_block .= "<button id='hide'>Hide table</button>\n";
# $display_block .= "<button id='show'>Show table</button>\n";
################## Update table form ################################################
    $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

    $display_block .= "\n<input type='hidden' name='op' value='update_table'>";
    $display_block .= "<p align='right'>Careful, this resets all 'use' data =><input type='submit' id='update_table' name='submit' value='Update table.'> </p>";

    $display_block .= "</form>";

    if ($POST_OP == "update_table") {

# Clear the table
        $query = "TRUNCATE $project_allocated_staff";
        //mysql_query($query) or die(" Unable to empty (truncate) $project_allocated_staff table . <BR>Error msg: " . mysql_error() . "<BR>Query: $query");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function <BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

# Get the list of staff and count each ones activity

#    $table = array();
#    $table['cols'] = array(
#        array('label' => 'Supervisor', 'type' => 'string'),
#        array('label' => 'Number supervised', 'type' => 'number')
#    );
        $query = "select * from $staff_names_table order by familyname";
        //$get_list_res = mysql_query($get_list) or die(" Unable to create staff name list from database. <BR>Error msg: " . mysql_error() . "<BR>Query: $get_list");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function " . " line: " . __LINE__ . "<BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }
        $rows = $results->fetchAll();
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
            if ($inactive == 0) {

                $query = "select * from $project_allocated where supervisor_id = $staffid";
                //$query_res = mysql_query($query) or die(" Unable to interrogate staff table for supervisor. <BR>Error msg: " . mysql_error() . "<BR>Query: $query");
                //$num_supervise = mysql_num_rows($query_res);
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }
                $num_supervise = $results->rowCount();

                $query = "select * from $project_allocated where second_marker_id = $staffid";
                //$query_res = mysql_query($query) or die(" Unable to interrogate staff table for second marker. <BR>Error msg: " . mysql_error() . "<BR>Query: $query");
                //$num_second_mark = mysql_num_rows($query_res);
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function <BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }
                $num_second_mark = $results->rowCount();

                # Write to the $project_allocated_staff table
                $query = "INSERT INTO $project_allocated_staff (staffid,firstname,familyname,fullname,num_supervise,num_second_mark) VALUES ('$staffid','$firstname','$familyname','$fullname','$num_supervise','$num_second_mark')  ";
                //mysql_query($query) or die(" Unable to insert data to the table $project_allocated_staff . <BR>Error msg: " . mysql_error() . "<BR>Query: $query");
                try {
                    $results = $db->query($query);
                } catch (PDOException $ex) {
                    $this_function = __FUNCTION__;
                    echo "An Error occured accessing the database in function: $this_function " . " line: " . __LINE__ . "<BR>\n";
                    error_log($ex->getMessage());
                    echo "A database error occurred. Please contact the system administrator.<BR>\n";

                    exit();
                }

#           $temp = array();
#           // Name
#           $temp[] = array('v' => (string)$fullname);
#           // value
#           $temp[] = array('v' => (int)$num_supervise);
#
#            $rows[] = array('c' => $temp);
            }
        }
#    $table['rows'] = $rows;
        #print_r2($table);
        #print($table);
        #echo($table);
#    if (!function_exists('json_encode')) {
#        echo("json_encode does not exist<BR>");
#    } else {
#        #echo("json_encode DOES exist<BR>");
#    }
#    $jsonTable = json_encode($table);
        #print($jsonTable);

    }// Recaculate table

#######################################################################
# Draw the table and prep the chart date_add()
#######################################################################
    $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

// Get the list of staff from the staff_names2 table
    $query = "select * from $project_allocated_staff order by familyname";
//$get_list_res = mysql_query($get_list) or die(" Unable to create staff load list from database. <BR>Error msg: " . mysql_error() . "<BR>Query: $get_list");
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        $this_function = __FUNCTION__;
        echo "An Error occured accessing the database in function1: $this_function " . " line: " . __LINE__ . "<BR>\n";
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";

        exit();
    }

//$display_block .= "<select name='staffname_select'>\n";

//$display_block = "<TABLE  class='tableSimple' style='border:1px solid black;border-collapse:collapse;'>\n";
    $display_block .= "<div id='alloc_table_div' style='display:none;'>\n";
    $display_block .= "<TABLE id='staffinfo' class='hover compact cell-border' cellspacing='0' width='100%'>\n";
    $display_block .= "
    <thead>
      <tr>
        <th>ID</th>
        <th>First name</th>
        <th>Family name</th>
        <th>Full name</th>
        <th>Number Supervising</th>
        <th>Number second marking</th>
        <th>Total involved in</th>
        <th>Use to plot</th>
      </tr>
    </thead>";
    $display_block .= "<tbody>\n";
    $staff_count = 0;
    if ($results->rowCount() > 0) {
        $table = array();
        $table['cols'] = array(
            array('label' => 'Supervisor', 'type' => 'string'),
            array('label' => 'Number supervised', 'type' => 'number')
        );
        $rows = array();
        $staff_count = 0;
        $results_rows = $results->fetchAll();
        foreach ($results_rows as $recs) {
            print_r($recs);
            $staffid = $recs['staffid'];
            #$initials = $recs['initials'];
            #$title = $recs['title'];
            $firstname = stripslashes($recs['firstname']);
            $familyname = stripslashes($recs['familyname']);
            $fullname = stripslashes($recs['fullname']);
            $num_supervise = stripslashes($recs['num_supervise']);
            $num_second_mark = stripslashes($recs['num_second_mark']);
            $use = stripslashes($recs['use']);
            $num_total = $num_supervise + $num_second_mark;
            #$email = stripslashes($recs['email']);
            #$username = stripslashes($recs['username']);
            #$room = stripslashes($recs['room']);
            #$inactive = stripslashes($recs['inactive']);
            $staff_count++;
            $display_block .= "<TR>\n";
            $display_block .= "<TD><input type='hidden' name='staffid$staff_count' value='$staffid'> $staffid</TD>";
            #$display_block .= "<TD><input type='text' name='initials$staff_count' value='$initials'  size=5 maxlength=30></TD>";
            #$display_block .= "<TD>". GetTitleSelect("title$staff_count",$title) . "</TD>";
            $display_block .= "<TD>$firstname</TD>";
            $display_block .= "<TD>$familyname</TD>";
            $display_block .= "<TD>$fullname</TD>";
            #$display_block .= "<TD><input type='text' name='email$staff_count' value='$email'  size=30 maxlength=40></TD>";
            #$display_block .= "<TD><input type='text' name='username$staff_count' value='$username'  size=8 maxlength=20></TD>";
            #$display_block .= "<TD><input type='text' name='room$staff_count' value='$room'  size=5 maxlength=10></TD>";
            #$checked = "";
            #if($inactive==1){
            #  $checked = "checked";
            #}
            #$display_block .= "<TD><input type='checkbox' name='use$staffid' value='1' $checked></TD>\n";
            $display_block .= "<TD>$num_supervise</TD>";
            $display_block .= "<TD>$num_second_mark</TD>";
            $display_block .= "<TD>$num_total</TD>";
            # $display_block .= "<TD>$use</TD>";
            $checked = "";
            if ($use == 1) {
                $checked = "checked";
            }
            $display_block .= "<TD><input type='checkbox' name='use$staff_count' value='1' $checked></TD>\n";

            $display_block .= "</TR>\n";

            if ($use == 1) {
                $temp = array();
                // Name
                $temp[] = array('v' => (string)$fullname);
                // value
                $temp[] = array('v' => (int)$num_supervise);

                $rows[] = array('c' => $temp);
            }
        }
        $table['rows'] = $rows;
        #print_r2($table);
        #print($table);
        #echo($table);
        if (!function_exists('json_encode')) {
            echo("json_encode does not exist<BR>");
        } else {
            #echo("json_encode DOES exist<BR>");
        }
        $jsonTable = json_encode($table);
        #print($jsonTable);
    }
    $display_block .= "</tbody>\n";
    $display_block .= "</TABLE>\n";

    $display_block .= "\n</div>\n";//alloc_table_div

    $display_block .= "\n<input type='hidden' name='op' value='submit_edited'>";
    $display_block .= "\n<input type='hidden' name='staff_count' value='$staff_count'>";

    $display_block .= "<p align='right'><input type='submit' id='submitbutton' name='submit' value='Update 'Use to plot.''></p>";

    $display_block .= "</form>";

# <!--this is the div that will hold the pie chart-->
    $display_block .= "\n<div class='bordered' id='chart_div2'></div>\n";
    $display_block .= "\n<div id='chart2_url_div'></div>\n";

    $display_block .= "\n<div class='bordered' id='chart_div'></div>\n";
    $display_block .= "\n<div id='chart_url_div'></div>\n";
//---------------------------------------------------------------------------------
    $display_block .= "<hr><h2>Save table to CSV...</H2>\n";
    $display_block .= "<form method='post' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";
    $display_block .= "\n<input type='hidden' name='op' value='savecsv'>";

    $display_block .= "<p><input type='submit' id='submitbutton_new' name='submit' value='Save table to CSV file.'></p>";

    $display_block .= "</form>";

// Save CSV of $project_allocated_staff";
    if ($POST_OP == "savecsv") {
        $tmpcsvfile = tempnam("dummy", "");
        $path = dirname($tmpcsvfile);
        //$csv_filename = $tmpcsvfile;
        //unlink($tmpcsvfile);

        $query = "select * from $project_allocated_staff order by familyname";
        //$get_list_res = mysql_query($get_list) or die(" Unable to create staff load list from database. <BR>Error msg: " . mysql_error() . "<BR>Query: $get_list");
        try {
            $results = $db->query($query);
        } catch (PDOException $ex) {
            $this_function = __FUNCTION__;
            echo "An Error occured accessing the database in function: $this_function " . " line: " . __LINE__ . "<BR>\n";
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";

            exit();
        }

        $fp = fopen($tmpcsvfile, 'w');

        $row[0] = "";
        $row[1] = "";
        $row[2] = "";
        $row[3] = "";
        $row[4] = "";
        $row[5] = "";
        $row[0] = "First name";
        $row[1] = "Family name";
        $row[2] = "Full name";
        $row[3] = "Number Supervising";
        $row[4] = "Number Second marking";
        $row[5] = "Total";
        $ret = fputcsv($fp, $row);

        $results_rows = $results->fetchAll();
        foreach ($results_rows as $recs) {

            $staffid = $recs['staffid'];
            $firstname = stripslashes($recs['firstname']);
            $familyname = stripslashes($recs['familyname']);
            $fullname = stripslashes($recs['fullname']);
            $num_supervise = stripslashes($recs['num_supervise']);
            $num_second_mark = stripslashes($recs['num_second_mark']);
            $num_total = $num_supervise + $num_second_mark;
            $staff_count++;
            $row[0] = "$firstname";
            $row[1] = "$familyname";
            $row[2] = "$fullname";
            $row[3] = "$num_supervise";
            $row[4] = "$num_second_mark";
            $row[5] = "$num_total";
            $ret = fputcsv($fp, $row);
        }
        fclose($fp);
        $csv_filename = $project_allocated_staff . ".csv";

        header('Content-type: application/excel');
        header("Content-Disposition: attachment; filename=$csv_filename");
        readfile("$tmpcsvfile");

        $display_block = "<h2> Project marks saved as CSV file to: $csv_filename</h2>";

        header('Content-type: application/excel');
        header("Content-Disposition: attachment; filename=$csv_filename");
        readfile("$tmpcsvfile");

        $display_block = "<h2> Staff project allocation saved as CSV file to: $csv_filename</h2>";

    }

}

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once("mobile_head_admin_bs.php")) OR die("Cannot find this file to include: mobile_head_admin_bs.php<BR>");
?>
<style>
    div.bordered {
        border-style: solid;
        border-width: 3px;
    }
</style>
<!--Load the Ajax API-->
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.2/jquery.min.js"></script>
<script type="text/javascript">

    // Load the Visualization API and the piechart package.
    google.load('visualization', '1', {'packages': ['corechart']});

    // Set a callback to run when the Google Visualization API is loaded.
    google.setOnLoadCallback(drawChart);

    function drawChart() {

        // Create our data table out of JSON data loaded from server.
        var data = new google.visualization.DataTable(<?=$jsonTable?>);

        var options = {
            title: 'Projects Supervised',
            is3D: 'true',
            width: 800,
            height: 600
        };
        // Instantiate and draw our chart, passing in some options.
        // Do not forget to check your div ID
        var my_chart_url = document.getElementById('chart_url_div');
        var my_chart = new google.visualization.PieChart(document.getElementById('chart_div'));
        google.visualization.events.addListener(my_chart, 'ready', function () {
            //my_chart_url.innerHTML = '<img src="' + my_chart.getImageURI() + '">';
            my_chart_url.innerHTML = '<a href=' + my_chart.getImageURI() + '>download image</A>';
        });
        my_chart.draw(data, options);

        var options2 = {
            title: 'Projects Supervised',
            is3D: 'true',
            width: 800,
            height: 1200
        };
        // Instantiate and draw our chart, passing in some options.
        // Do not forget to check your div ID
        var my_chart2_url = document.getElementById('chart2_url_div');
        var my_chart2 = new google.visualization.BarChart(document.getElementById('chart_div2'));
        google.visualization.events.addListener(my_chart2, 'ready', function () {
            //my_chart2_url.innerHTML = '<img src="' + my_chart2.getImageURI() + '">';
            my_chart2_url.innerHTML = '<a href=' + my_chart2.getImageURI() + '>download image</A>';
        });
        my_chart2.draw(data, options2);

    }
</script>

<script>
    $(document).ready(function () {
        $('#hideshow_checkbox').change(function () {
            //alert("hide/show check");
            if ($(this).prop("checked")) {
                $('#alloc_table_div').show();
            } else {
                $('#alloc_table_div').hide();
            }
        });
        /*
         $("#hide").click(function(){
         $("#alloc_table_div").hide();
         });
         $("#show").click(function(){
         $("#alloc_table_div").show();
         });
         */
    });
</script>

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
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Staff load details - <?php echo("$module_short_description ($short_code) $module_prefix$module1 $year_begin ") ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../projectmarking_select_bs.php?pm_id=<?php echo("$pm_id") ?>" class="nav-link px-2 text-white-50">Module page</a></li>
            <li class="nav-item"><a target="_self" href="../session_modules_bs.php?year_begin= <?php echo("$year_begin") ?>" class="nav-link px-2 text-white-50">This year module list</a></li>
            <li class="nav-item"><a target="_self" href="./edit_module_settings.php?pm_id=<?php echo("$pm_id") ?>" class="nav-link px-2 text-white-50">Module admin page</a></li>
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Main admin page</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
        <?php echo $display_block; ?>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
</html>
