<?php
(@include_once("./set_dir_staff.php")) or die("Cannot find this file to include: set_dir_staff.php<BR>");
/**
 * Created by PhpStorm.
 * User: andy
 * Date: 24/02/2019
 * Time: 17:40
 */
$debug = false;
if (array_key_exists('debug', $_GET)) {
    $debug = true;
}

$dev_mode = false;
$dev_user = null;
if (array_key_exists('devmode', $_GET)) {
    $devmode_value = $_GET['devmode'];
    if ($devmode_value == 'cenpas24') {
        $dev_user = 'cenpas';
        $dev_mode = true;
    }elseif ($devmode_value == 'cenpas24_noadmin') {
        $dev_user = 'cenpas_noadmin';
        $dev_mode = true;
    }
}
if ($dev_mode) {
    //echo("dev_user = $dev_user.<BR>");
}
$num_cookie_days = 30;
if (isset($_SERVER['REMOTE_USER'])) {
    $user = $_SERVER['REMOTE_USER'];
    $user = get_server_username();
    setcookie('USERNAME', $user, time() + $num_cookie_days*24 * 3600, "/");
}
if ($dev_mode and $dev_user) {
    $GLOBALS['USERNAME'] = $dev_user;
    $MY_REMOTE_USER = $dev_user;
    setcookie('USERNAME', $dev_user, time() + $num_cookie_days*24 * 3600, "/");
}
(@include_once("./config.php")) or die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once("./setup_titles.php")) or die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./markstore_functions.php")) or die("Cannot include markstore_functions.php<BR>");
(@include_once("./connect_pdo.php")) or die("Cannot connect to the database<BR>");
(@include_once("./setup_tables.php")) or die("Cannot setup database tables<BR>");
(@include_once("./staff_functions.php")) or die("Cannot open staff_functions.php<BR>");
(@include_once("./admin2/adminusers.php")) or die("Cannot include adminusers<BR>");
(@include_once("./markstore_functions.php")) or die("Cannot include markstore_functions.php<BR>");
(@include_once("./table_functions.php")) or die("Cannot include table_functions.php<BR>");
// Select the years available in the projects setup table
// Check the table exists
if (DoesTableExist($db, $projects_setup)) {
    //echo("OK the $projects_setup  table exists<BR>\n");
} else {
    echo("Need to create the $projects_setup  table<BR>\n");
    exit();
}

$staff_username = "unknown";
if(array_key_exists('REMOTE_USER',$_SERVER)){
    $staff_username = $_SERVER['REMOTE_USER'];
}else{
    if(array_key_exists('USERNAME',$_COOKIE)){
        $staff_username = $_COOKIE['USERNAME'];
    }
}
$staff_full_name = GetStaffNameFromUsername($db, $staff_username, $staff_names_default, false);
$staff_full_name = trim($staff_full_name);

// Check access permission
$access = false;
$display_block = "";
if (empty($staff_full_name)) {

    $greeting_block = "<P>Hello $staff_username, you do not seem to have a staff username on this system.</P>";
    $greeting_block .= "<P>Please contact the manager of this system if you think there is a mistake.</P>";
    $greeting_block .= "<p><A target='self' href='..'>Return to the home page</a>.</P>";
} else {
    $greeting_block = "<h4>Hello $staff_full_name ($staff_username)</h4>";
    $access = true;
}

$display_pma_block = "block";
$display_feedback_block = "block";
if($school == "kmap2"){
    $display_pma_block = "none";
    $display_feedback_block = "block";
}
$display_block .= $greeting_block;

$session_buttons = "";
$session_buttons2 = "";
$dash_buttons = "";

// The main block
if ($access) {
    // Get the list of years and modules from the project_setup table
    $get_data_query = "select DISTINCT(`year_begin`) from $projects_setup where 1 AND visible=1 order by year_begin desc";

    try {
        $setup = $db->query($get_data_query);
        $results = $setup->fetchAll();
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $projects_setup table<BR>\n";
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }

    foreach ($results as $res) {
        $year_begin = $res['year_begin'];
        $year_end = $year_begin + 1;
        $display_block .= "<A href=\"./session_modules.php?year_begin=$year_begin\" rel=\"external\" data-role=\"button\" data-theme=\"e\">Session $year_begin/$year_end</A>\n";
        $session_buttons .= "<A href='./session_modules_bs.php?year_begin=$year_begin' class='btn btn-lg btn-warning' target='_top'>Academic session $year_begin/$year_end  &raquo;</A>\n";

    }

    // If dash feedback available?
    $query = "SELECT * FROM $dash_projects_setup";
    try {
        $results = $db->query($query);
    } catch (PDOException $ex) {
        echo "An Error occured accessing the $dash_projects_setup table<BR>\n";
        echo(" Err message: " . $ex->getMessage() . "<BR>\n");
        exit();
    }
    $recs = $results->fetchAll();

    $dash_visible_staff = $recs[0]['staff'];

    if ($dash_visible_staff == 1) {
        $staff_feedback_dash_visibility = "block";

        $display_block .= "<BR>";
        $display_block .= "<div style='font-size: x-large; font-weight: bold; text-align: center; margin-bottom: 0em'>Feedback & Tools Dashboard</div>";
        $display_block .= "<P>These feedback & education tools are available</P>";

        //$display_block .= "<A href='./dash_selection.php' rel='external' data-role='button' data-theme='b'>Feedback Tools Dashboard</A>\n";
        // PEGA
        $id = $pega_id;
        $description = $recs[$id]['description'];
        $title = $recs[$id]['title'];
        $subtitle = $recs[$id]['subtitle'];
        $staff_visible = $recs[$id]['staff'];
        if ($staff_visible == 1) {
            //$display_block .= "<A href='./pega/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
            $dash_buttons .= "<A href='./pega/index.php' class='btn btn-info custom-btn-width' target='_top'>$title<BR>$subtitle &raquo;</A>\n";
        } else {
            //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
        }
        // HAMID
        $id = $hamid_id;
        $description = $recs[$id]['description'];
        $title = $recs[$id]['title'];
        $subtitle = $recs[$id]['subtitle'];
        $staff_visible = $recs[$id]['staff'];
        if ($staff_visible == 1) {
            //$display_block .= "<A href='./hamid/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
            $dash_buttons .= "<A href='./hamid/index.php' class='btn btn-info custom-btn-width' target='_top' 
                    data-bs-toggle='tooltip' title='$subtitle' data-bs-placement='left'>$title</A>\n";
        } else {
            //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
        }
        // DIST
        $id = $dist_id;
        $description = $recs[$id]['description'];
        $title = $recs[$id]['title'];
        $subtitle = $recs[$id]['subtitle'];
        $staff_visible = $recs[$id]['staff'];
        if ($staff_visible == 1) {
            $display_block .= "<A href='./dist/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
            $dash_buttons .= "<A href='./dist/index.php' class='btn btn-info custom-btn-width' target='_top'
                    data-bs-toggle='tooltip' title='$subtitle' data-bs-placement='left'>$title</A>\n";
        } else {
            //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
        }
        // KAI-MAP
        $id = $kmap_id;
        $description = $recs[$id]['description'];
        $title = $recs[$id]['title'];
        $subtitle = $recs[$id]['subtitle'];
        $staff_visible = $recs[$id]['staff'];
        if ($staff_visible == 1) {
            //$display_block .= "<A href='./kmap/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
            $dash_buttons .= "<A href='./kmap/index.php' class='btn btn-info' target='_top'
                    data-bs-toggle='tooltip' title='$subtitle' data-bs-placement='left'>$title</A>\n";
        } else {
            //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
        }
        // CWT
        $id = $cwt_id;
        $description = $recs[$id]['description'];
        $title = $recs[$id]['title'];
        $subtitle = $recs[$id]['subtitle'];
        $staff_visible = $recs[$id]['staff'];
        if ($staff_visible == 1) {
            //$display_block .= "<A href='./cwt/index.php' rel='external' data-role='button' data-theme='b'>$title - $subtitle</A>\n";
            $dash_buttons .= "<A href='./cwt/index.php' class='btn btn-info' target='_top'
                    data-bs-toggle='tooltip' title='$subtitle' data-bs-placement='left'>$title</A>\n";
        } else {
            //$display_block .= "<P><em>$title</em>: $description - $subtitle. NOT AVAIABLE</P>\n";
        }

    } else {
        $staff_feedback_dash_visibility = "none";
    }


}

$footer_link = date('l jS \of F Y, h:i A');
$admin_link = date('l jS \of F Y, h:i A');
$admin_link = "<a href='#'> $admin_link</a>";
if (IsAdmin($db, $staff_username)) {
    $footer_link = "<a target=\"_self\" href=\"./admin2/\" style=\"color: dimgray;\">You have <I>super</I> admin access</a> - use these powers VERY carefully!";
    $admin_link = "<a href='./admin2/' target='_top'><span class=\"glyphicon glyphicon-cog\"></span> Super Admin</a>";
    $admin_link = "./admin2/";
    $admin_link_text = "Super Admin";
} else if (IsModuleAdmin($db, $staff_username, 'all', null, null)) {
    $footer_link = "<a target=\"_self\" href=\"./admin2/\" style=\"color: dimgray;\">You have <I>module level</I> admin access</a> - take care making changes!";
    $admin_link = "<a href='./admin2/' target='_top'><span class=\"glyphicon glyphicon-cog\"></span> Admin</a>";
    $admin_link = "./admin2/";
    $admin_link_text = "Admin";
} else {
    // Not admin
    $footer_link = date('l jS \of F Y, h:i A');
    $admin_link = "../..";
    $admin_link_text = "Back";
}
//$footer_link .= " <A target='self' href='..' style='color: dimgray;'>Root Options</A>";

?>
<!DOCTYPE HTML>
<html>
<?php (@include_once("./$mobile_head_bs")) or die("Cannot find this file to include: $mobile_head_bs<BR>"); ?>

<body class="bg-dark text-white">
<!-- A grey horizontal navbar that becomes vertical on small screens -->
<nav class="navbar navbar-expand-sm fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <A class="navbar-brand" href="index.php"><?php echo("$school_title $running_title"); ?></A>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../.." class="nav-link px-2 text-white-50">Root options</a>
            </li>
        </ul>
        <div class="navbar-brand ms-auto">
            <a target='_top' class="navbar-brand" href='<?php echo("$admin_link"); ?>'>
                <i class="bi bi-gear"></i>
                <?php echo("$admin_link_text"); ?>
            </a>
        </div>

    </div>
</nav>

<div class="container bg-dark text-white">
    <div class="jumbotron jumbotron-header bg-dark text-white">
        <img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>

    <hr class="style1">
    <div class="container-fluid text-center">
        <?php echo("$greeting_block"); ?>
        <div id="pma_div" style="display: <?php echo("$display_pma_block"); ?>">
            <div class="well wellfb text-center bg-dark text-white">
                <H4>Project Module Marking</H4>
                <h5>Select year/session:</h5>
                <div class="btn-group-vertical btn-group-lg mx-auto">
                    <?php echo("$session_buttons"); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid text-center" style="display: <?php echo($staff_feedback_dash_visibility) ?>;">
        <hr class="style1" style="display: <?php echo("$display_pma_block"); ?>">
        <div class="well wellfb text-center bg-dark text-white" style="display: <?php echo("$display_feedback_block"); ?>">
            <H4 style="display: <?php echo("$display_pma_block"); ?>">Feedback Dashboard</H4>
            <div class="btn-group-vertical  btn-group-lg" role="group">
                <!--<div class="d-grid gap-1 col-4 mx-auto">-->
                <?php echo("$dash_buttons"); ?>
            </div>
        </div>
    </div>
</div>

<!--
<footer class="py-3 my-4 bg-secondary text-white">
    <ul class="nav justify-content-center">
        <li class="nav-item"><a target="_self" href=".." class="nav-link px-2 text-white-50">Root options</a></li>
        <li class="nav-item"><a target="_self" href="../student" class="nav-link px-2 text-white-50">Students page</a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-white-50">Year index page</a></li>
    </ul>
</footer>
-->
<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
<!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.bundle.min.js"></script>-->
<script type="text/javascript" src="./frameworks/bootstrap-5.2.3-dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
