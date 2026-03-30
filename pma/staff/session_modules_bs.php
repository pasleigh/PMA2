<?php

(@include_once("./set_dir_staff.php")) OR die("Cannot find this file to include: set_dir_staff.php<BR>");
(@include_once("./config.php")) OR die("Cannot find this file to include: config.php. $DEFAULT_ERR<BR>");
(@include_once("./setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("./connect_pdo.php")) OR die("Cannot connect to the database<BR>");
(@include_once("./setup_tables.php")) OR die("Cannot setup database tables");
(@include_once("./markstore_functions.php")) OR die("Cannot find markstore_functions");

(@include_once("./admin2/utility_functions.php")) OR die("Cannot load utility_function.php");
(@include_once("./admin2/adminusers.php")) OR die("Cannot setup admin users");
(@include_once("./staff_student_functions.php")) OR die("Cannot find staff_student_functions.php");

$staff_username = "unknown";
if(array_key_exists('REMOTE_USER',$_SERVER)){
    $staff_username = get_server_username();
}else{
    if(isset($_SESSION['USERNAME'])){
        $staff_username = $_SESSION['USERNAME'];
    }
}
if(IsStaff($db, $staff_username, $staff_names) == false){
    echo "You do not have access to this page. If you think this is an error please contact the system administrator.<BR>";
    echo "<p><A target='self' href='..'>Return to the home page</a>.</P>";
    exit();
}

$display_block = "";
$display_block .= $connect_msg;

if (array_key_exists('year_begin', $_GET) == false) {
    $_GET['year_begin'] = null;
}
$year_begin = $_GET['year_begin'];
$year_end = $year_begin + 1;

$query = "select DISTINCT(module), short_code, module_short_desc, module_prefix, module_postfix, id, visible, staff_close from $projects_setup where year_begin=$year_begin order by module desc";
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

$greeting_block = "<h4>You are logged in as $this_staff_full_name ($this_staff_username)</h4>";
$greeting_block .= "<h4>Below is a list of modules for the academic session $year_begin/$year_end</h4>";
$display_block = $greeting_block;

$display_propose_block = "";
$visible_count = 0;
foreach ($rows as $res) {
    $visible = $res['visible'];

    $staff_close_date = $res['staff_close'];
    $staff_close_date .= " 23:59"; // MAke it en of the day

    $str_staff_close_date = date("j F Y, H:i", strtotime($staff_close_date));
    $now_datetime = date("Y-m-d H:i:s");
    $close_date_diff = day_diff($now_datetime, $staff_close_date);
    $close_text = "(<span style='color: whitesmoke; font-size: small;'>Proposals close: $str_staff_close_date</span>) ";
    //$display_block .= "-> " . $close_text . " : " . $close_date_diff . "<BR>";

    $staff_close_date = $res['staff_close'];
    $diff_seconds = strtotime($staff_close_date) - time();
    //$display_block .= "$staff_close_date, $diff_seconds <BR>";

    $open_for_proposals = 1;
    if($close_date_diff > 0.0 )
    {
        $open_for_proposals = 0;
    }
    if($visible == 1 && $open_for_proposals == 1) {
        $pm_id = $res['id'];
        $module1 = $res['module'];
        $module_prefix = $res['module_prefix'];
        $module_postfix = $res['module_postfix'];
        $module_short_description = $res['module_short_desc'];
        $short_code = $res['short_code'];
        //$display_propose_block .= "<A href=\"./projectproposals_select.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"b\">$module_short_description - $module_prefix$module1 - $short_code</A>\n";
        $display_propose_block .= "<A href=\"./proposals/projects_indexm2.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"b\">$module_short_description - $module_prefix$module1$module_postfix - $short_code<BR>$close_text</A>\n";
        $visible_count++;

    }
}

// and for the module admin get the same list but include closed proposals
$display_propose_block_admin = "";
$module_buttons_admin = "";
foreach ($rows as $res) {
    $visible = $res['visible'];
    $staff_close_date = $res['staff_close'];
    if($visible == 1 ) {
        $pm_id = $res['id'];
        $module1 = $res['module'];
        $module_prefix = $res['module_prefix'];
        $module_postfix = $res['module_postfix'];
        $module_short_description = $res['module_short_desc'];
        $short_code = $res['short_code'];
        //$display_propose_block .= "<A href=\"./projectproposals_select.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"b\">$module_short_description - $module_prefix$module1 - $short_code</A>\n";
        $display_propose_block_admin .= "<A href=\"./proposals/projects_indexm2.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"b\">$module_short_description - $module_prefix$module1$module_postfix - $short_code</A>\n";
        $module_buttons_admin .= "<A href='./proposals/projects_indexm2.php?pm_id=$pm_id' class='btn btn-primary' target='_top' >$module_short_description - $module_prefix$module1$module_postfix - $short_code</A>\n";
    }
}

if( $visible_count > 0)
{
    $display_block .= "<h3> Select one of these modules to propose project titles / themes:</h3>";
    $display_block .= $display_propose_block;
}

$display_block .= "<H3> Select one of these modules to mark:</H3>";
$not_visible_count = 0;
$visible_count = 0;
$module_buttons = "";
foreach ($rows as $res) {
    $visible = $res['visible'];
    if($visible == 1) {
        $pm_id = $res['id'];
        $module1 = $res['module'];
        $module_prefix = $res['module_prefix'];
        $module_postfix = $res['module_postfix'];
        $module_short_description = $res['module_short_desc'];
        $short_code = $res['short_code'];
        $display_block .= "<A href=\"./projectmarking_select.php?pm_id=$pm_id\" rel=\"external\" data-role=\"button\" data-theme=\"e\">$module_short_description - $module_prefix$module1$module_postfix - $short_code</A>\n";
        $module_buttons .= "<A href='./projectmarking_select_bs.php?pm_id=$pm_id' class='btn btn-success' target='_top'>$module_short_description - $module_prefix$module1$module_postfix - $short_code</A>";
        $visible_count++;
    }else{
        $not_visible_count++;
    }
}

$num = $results->rowCount();
if($not_visible_count > 0){
    if($not_visible_count == 1){
        $display_block .= "<P>$num modules ($not_visible_count is not visible)</P>";
    }else{
        $display_block .= "<P>$num modules ($not_visible_count are not visible)</P>";
    }
}else{
    $display_block .= "<P>All $num modules for marking are showing.</P>";
}

//---------------------------------
// If admin user then display this
$admin_link = "";
$admin_view = "none";
$admin_message = "";
if (IsAdmin($db, $this_staff_username) or IsProjectAdmin($db, $this_staff_username, $staff_names)) {
    $display_block .= "<HR><P>If you see buttons below it is because you are a module level administrator. <BR>They are available to you so that you can make changes to projects and settings after the general deadline. Make changes sparingly.</P>";
    $admin_message = "<h5>If you see buttons below it is because you are a module level administrator. <BR>They are available to you so that you can make changes to projects and settings after the general deadline. Make changes sparingly.</h5>";
    $admin_link = "<a href='./admin2/' target='_top'><span class=\"glyphicon glyphicon-cog\"></span> Admin</a>";
  if( $visible_count > 0) {
    $display_block .= "<div data-role=\"controlgroup\">";
    $display_block .= "<h3> Links to the project proposals titles / themes:</h3>";
    $display_block .= $display_propose_block_admin;
    $display_block .= "</div>";
    $admin_view = "block";
  }
}else{
    $module_buttons_admin = "";
}

if (IsAdmin($db, $this_staff_username)) {
    $footer_link = "<a target=\"_self\" href=\"./admin2/\" style=\"color: dimgray;\">You have <I>super</I> admin access</a> - use these powers VERY carefully!";
    $admin_link = "<a href='./admin2/' target='_top'><span class=\"glyphicon glyphicon-cog\"></span> Super Admin</a>";
    $admin_link = "./admin2/";
    $admin_link_text = "Super Admin";
}else if(IsModuleAdmin($db, $this_staff_username, 'all', null, null)){
    $footer_link = "<a target=\"_self\" href=\"./admin2/\" style=\"color: dimgray;\">You have <I>module level</I> admin access</a> - take care making changes!";
    $admin_link = "<a href='./admin2/' target='_top'><span class=\"glyphicon glyphicon-cog\"></span> Admin</a>";
    $admin_link = "./admin2/";
    $admin_link_text = "Module Admin";
}

?>

<!DOCTYPE HTML>
<html lang="en" class="h-100">
<?php (@include_once("./$mobile_head_bs")) OR die("Cannot find this file to include: $mobile_head_bs<BR>"); ?>

<body class="bg-dark text-white">
<!-- A grey horizontal navbar that becomes vertical on small screens -->
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <A class="navbar-brand" href="index.php"><?php echo("$school_title $running_title"); ?></A>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="index.php" class="nav-link px-2 text-white-50">Year index</a></li>
        </ul>
        <div class="navbar-brand ms-auto">
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
        <?php echo("$greeting_block"); ?>
        <div class="well wellpm text-center bg-dark text-white">
            <H4>Select one of these modules to mark</H4>
            <div class="btn-group-vertical btn-group-lg" role="group">
                <?php echo("$module_buttons"); ?>
            </div>
        </div>
    </div>

    <div class="container-fluid  text-center" style="display: <?php echo("$admin_view"); ?>">
        <hr class="style1">
        <?php echo("$admin_message"); ?>
        <div class="well wellfb text-center bg-dark text-white mt-4">
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
    </ul>
</footer>
-->
</body>
</html>
