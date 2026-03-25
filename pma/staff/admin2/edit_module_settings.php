<?php
// connect to the datbase
(@include_once("./set_dir_admin.php")) OR die("Cannot find this file to include: set_dir_admin.php<BR>");
(@include_once("$STAFF_DIR_VAL/config.php")) OR die("Cannot find this file to include: config.php<BR>");
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) OR die("Cannot find this file to include: setup_titles.php<BR>");
(@include_once("$STAFF_DIR_VAL/connect_pdo.php")) OR die("Cannot connect to the database<BR>");

(@include_once("$STAFF_DIR_VAL/setup_tables.php")) OR die("Cannot setup database tables");
(@include_once("$STAFF_DIR_VAL/staff_functions.php")) OR die("Cannot setup database tables");
(@include_once("$STAFF_DIR_VAL/markstore_functions.php")) OR die("Cannot setup markstore functions");

(@include_once("./admin_functions.php")) OR die("Cannot include admin functions<BR>");

// Get the project
if (!isset($_REQUEST['pm_id'])) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];

$display_block = "";
$display_block .= $connect_msg;

// Check the admin level
$admin_access = false;
$admin_access = access_check_response($db,$this_staff_username, $this_staff_full_name, $staff_names,$access_phrase);
$is_module_admin = IsModuleAdmin($db, $this_staff_username, $pm_id, null, null);

if($is_module_admin) {
    $access_phrase = "You, $this_staff_full_name ($this_staff_username), have <em>module level</em> admin access. Be careful.";
}

$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

/*
if (IsAdmin($db, $this_staff_username) ){
    $access_phrase = "You, $this_staff_full_name ($this_staff_username), have <em>database level</em> admin access. Be very carefull.";
    $access = true;
}else if(IsProjectAdmin($db, $this_staff_username, $staff_names)){
    $access_phrase = "You, $this_staff_full_name ($this_staff_username), have <em>Project level</em> admin access. Be carefull.";
    $access = true;
}else{
    $access_phrase = "You, $this_staff_full_name ($this_staff_username), do not have admin access right. <BR> Please return to where you cam from.";
}
*/

if ($admin_access || $is_module_admin) {

    $display_block .= "<h1><span style='color: black'>Module Admin functions for:</span> $module_short_description - $module_prefix$module1$module_postfix. <BR><span style='color: black'>Starting </span>Sept $year_begin</h1>";

    $display_block .= admin_functions_block($db,$pm_id,$project_allocated,$num_parts,$p_description,$year_begin, $this_staff_username, $admin_access);

    $display_block .= "<BR>";
} else {
    $display_block .= "You do not have the appropriate admin level<BR>";
    $display_block .= "<A href=\"../index.php\">Go back here</A><BR>";
}
?>
<HTML>
<?php
(@include_once("admin_desktop_head.php")) OR die("Cannot find this file to include: admin_desktop_head.php<BR>");
?>
<BODY>
<div id="wrapper">
    <div id="header">
        <span
            class="header">Edit module details - <?php echo("$module_short_description ($short_code) $module_prefix$module1$module_postfix $year_begin ") ?></span>
    </div><!-- /header -->

    <div id="content">
        <?php echo $display_block; ?>
    </div><!-- /content -->

    <div class="footer_link" id="footer">
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
