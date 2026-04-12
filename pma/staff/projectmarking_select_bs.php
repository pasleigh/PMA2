<?php
(@include_once('./set_dir_staff.php')) or die('Cannot find this file to include: set_dir_staff.php<BR>');
(@include_once('./config.php')) or die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once('./setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('./connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('./setup_tables.php')) or die('Cannot setup database tables');

(@include_once('./admin2/adminusers.php')) or die('Cannot setup admin users');

$staff_username = 'unknown';
if (array_key_exists('REMOTE_USER', $_SERVER)) {
    $staff_username = get_server_username();
} else {
    if (isset($_SESSION['USERNAME'])) {
        $staff_username = $_SESSION['USERNAME'];
    }
}
if (IsStaffB($db, $staff_username, $staff_names) == false) {
    echo 'You do not have access to this page. If you think this is an error please contact the system administrator.<BR>';
    echo "<p><A target='self' href='..'>Return to the home page</a>.</P>";
    exit();
}

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
    echo("Unable to update data to the table $projects_setup <BR>\n");
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$res = $results->fetch();
if ($res) {
    $module1 = $res['module'];
    $year_begin = $res['year_begin'];
    $year_end = $res['year_end'];

    $module_prefix = $res['module_prefix'];
    $module_postfix = $res['module_postfix'];
    $module_short_description = $res['module_short_desc'];
    $short_code = $res['short_code'];
} else {
    $module1 = '';
    $year_begin = '';
    $year_end = '';

    $module_prefix = '';
    $module_postfix = '';
    $module_short_description = '';
    $short_code = '';
}

$greeting_block = "<h4>$this_staff_full_name ($this_staff_username), click a button to take you to the marking list for this module:</h4>";

//$display_block .= "<div>";
$module_buttons = "<A class='btn btn-primary btn-lg' onclick='wait_cursor();' href='./projectmarking_bs.php?pm_id=$pm_id&user=1'>$module_short_description - $module_prefix$module1$module_postfix - $short_code<BR>Marking sheet for $this_staff_full_name</A>\n";
$module_buttons .= "<A class='btn btn-outline-primary btn-lg' onclick='wait_cursor();' href=\"./projectmarking_bs.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"c\">Full marking sheet for $module_short_description</A>\n";
//$display_block .= "<A href=\"./projectmarking.php?pm_id=$pm_id&modify=1\" rel=\"external\" data-role=\"button\" data-theme=\"c\">Full Marking sheet with modify functions</A>\n";

// If admin user then display this
$admin_message = '';
$module_button_full = '';
$module_buttons_admin = '';
$admin_view = 'none';
if (IsAdmin($db, $this_staff_username) or IsModuleAdmin($db, $this_staff_username, $pm_id, null, null)) {
    $admin_message = '<h4>You see these buttons because you are a module level administrator. Be careful when using them.</h4>';
    $module_button_full = "<A class='btn btn-outline-primary btn-lg' onclick='wait_cursor();' href='./projectmarking_bs.php?pm_id=$pm_id&modify=1'>Full marking sheet with modify functions for $module_short_description</A>\n";
    $module_buttons_admin = "<A class='btn btn-secondary btn-lg' href=\"./projectmarking_save_to_csv.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"a\">Save list of project marks to CSV</A>\n";
    $module_buttons_admin .= "<A class='btn btn-secondary btn-lg' href=\"./projectmarking_save_to_xlsx.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"a\">Save list of project marks to Excel file (.xlsx)</A>\n";
    $module_buttons_admin .= "<A class='btn btn-secondary btn-lg' href=\"./pdf_functions.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"a\">Create, Save and email PDFs of feedback</A>\n";
    $module_buttons_admin .= "<A class='btn btn-secondary btn-lg' href=\"./admin2/edit_module_settings.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"a\">Module level admin functions</A>\n";
    $admin_view = 'block';
}
$admin_link_text = '';
$show_admin_link = 'none';
$admin_link = '';
if (IsAdmin($db, $this_staff_username)) {
    //$admin_link = "<a href='./admin2/' target='_top'><span class=\"glyphicon glyphicon-cog\"></span> Super Admin</a>";
    $admin_link = './admin2/';
    $admin_link_text = 'Super Admin';
    $show_admin_link = 'block';
} elseif (IsModuleAdmin($db, $this_staff_username, 'all', null, null)) {
    //$admin_link = "<a href='./admin2/' target='_top'><span class=\"glyphicon glyphicon-cog\"></span> Admin</a>";
    $admin_link = './admin2/';
    $admin_link_text = 'Admin';
    $show_admin_link = 'block';
}
//$display_block .= "</div>";

?>
<!DOCTYPE HTML>
<!--<html manifest="app.manifest">-->
<html>
<?php (@include_once("./$mobile_head_bs")) or die("Cannot find this file to include: $mobile_head_bs<BR>"); ?>

<body class="bg-dark text-white">
<!-- A grey horizontal navbar that becomes vertical on small screens -->
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <A class="navbar-brand" href="index.php"><?php echo("$school_title $running_title"); ?></A>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="index.php" class="nav-link px-2 text-white-50">Year index</a></li>
            <li class="nav-item"><a target="_self" href="./session_modules_bs.php<?php echo("?year_begin=$year_begin"); ?>" class="nav-link px-2 text-white-50"><?php echo("$year_begin/$year_end modules index"); ?></a></li>
        </ul>
        <div class="navbar-brand ms-auto" style="display: <?php echo($show_admin_link);?>">
            <a  target='_top' class="navbar-brand" href='<?php echo("$admin_link"); ?>'>
                <i class="bi bi-gear"></i>
                <?php echo("$admin_link_text"); ?>
            </a>
        </div>

    </div>
</nav>

<div class="container">
    <div class="jumbotron jumbotron-header  bg-dark text-white">
        <img src="./images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>
    <hr class="style1">
    <div class="container-fluid text-center">
        <h4><?php echo($this_staff_full_name) ?> (<?php echo($this_staff_username) ?>),
            click a button to take you to the marking list for this module:</h4>
        <div class="well wellpm text-center bg-dark text-white">
            <div class="btn-group-vertical btn-group-lg" role="group">
                <?php echo("$module_buttons"); ?>
            </div>
        </div>
    </div>
    <div class="container-fluid text-center" style="display: <?php echo("$admin_view"); ?>">
        <hr class="style1">
        <?php echo("$admin_message"); ?>
        <div class="well wellfb text-center bg-dark text-white mt-4">
            <div class="btn-group-vertical  btn-group-lg" role="group">
                <?php echo("$module_button_full"); ?>
            </div>
            <BR>
            <div class="btn-group-vertical  btn-group-lg" role="group">
                <?php echo("$module_buttons_admin"); ?>
            </div>
        </div>
    </div>
</div>
<!--
<footer class="footer py-3 my-4 bg-secondary text-white">
    <ul class="nav justify-content-center">
        <li class="nav-item"><a target="_self" href="index.php" class="nav-link px-2 text-white-50">Year index</a></li>
        <li class="nav-item"><a target="_self" href="./session_modules_bs.php<?php echo("?year_begin=$year_begin"); ?>" class="nav-link px-2 text-white-50"><?php echo("$year_begin/$year_end modules index"); ?></a></li>
        <li class="nav-item"><a href="#" class="nav-link px-2 text-white-50">This year module list</a></li>
    </ul>
</footer>
-->
</body>
<script>
    function wait_cursor(){
        $('html,body,a').css('cursor','progress');
    }
</script>
</html>
