<?php
(@include_once('./set_dir_props.php')) or die('Cannot find this file to include: set_dir_props.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables');

(@include_once('../admin2/utility_functions.php')) or die('Cannot setup utility functions');
(@include_once('../admin2/adminusers.php')) or die('Cannot setup admin users');
(@include_once('../staff_student_functions.php')) or die('Cannot find staff_student_functions.php');

$staff_username = get_server_username();
if (IsStaff($db, $staff_username, $staff_names) == false) {
    echo 'You do not have access to this page. If you think this is an error please contact the system administrator.<BR>';
    echo "<p><A target='self' href='../..'>Return to the home page</a>.</P>";
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
    error_log($ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
    exit();
}
$res = $results->fetch();

$module1 = $res['module'];
$year_begin = $res['year_begin'];
$year_end = $res['year_end'];
$module_prefix = $res['module_prefix'];
$module_short_description = $res['module_short_desc'];
$short_code = $res['short_code'];

$staff_close_date = $res['staff_close'] . ' 23:59';
$str_staff_close_date = date('j F Y, H:i', strtotime($staff_close_date));
$student_allocation_date = $res['alloc_date'];
$str_student_allocation_date = date('j F Y', strtotime($student_allocation_date));
$today = date('Y-m-d');

$now_datetime = date('Y-m-d H:i:s');

$display_block = '';
$display_block_edit_buttons = '';

$date_diff = day_diff($now_datetime, $staff_close_date);
//$display_block .= "Date diff (day) = $date_diff, Close: $staff_close_date, Now: $now_datetime<BR>\n";

$display_block .= "<div class='text-center'>\n";
$display_block .= "<h1><span class='text-white'>Project Proposals for: </span>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";

$display_block .= "
<h2>View/edit project themes</h2>
<p>(You will not be able to add/change projects after <span class='text-deadline'> $str_staff_close_date </span>)</p>";

$display_block_edit_buttons .= "
        <div class='btn-group-vertical mt-2'>
            <a href='addproject2.php?pm_id=$pm_id' class='btn btn-warning'>Add a Project Theme</a>
            <a href='modproject_bystaff2.php?pm_id=$pm_id' class='btn btn-primary'>View / Edit / Delete an existing Project</a>
        </div>
        ";

if ($date_diff > 1.0) {
    $display_block .= '<H1>The deadline for entering project proposals has passed.</H1>';
    if (IsAdmin($db, $this_staff_username) or IsProjectAdmin($db, $this_staff_username, $staff_names)) {
        $display_block .= "<P class='text-admin-ok'>As module level administrator you have access to modifying projects past the deadline date. <span class='text-passed'>Do this sparingly.</span></P>";
        $display_block .= $display_block_edit_buttons;
    }
} else {
    $display_block .= $display_block_edit_buttons;
}

$display_block .= "
<p>View Projects from full list</p>
<div class='btn-group-vertical mt-2'>
    <a href='selproject.php?pm_id=$pm_id' class='btn btn-secondary'>List of projects</a>
</div>
";

$display_block .= "
  <p>Downloadable list of projects</p>
  <div class='btn-group-vertical mt-2'>
      <a href='list_projects_csv.php?pm_id=$pm_id' class='btn btn-secondary'>Excel file of projects</a>
  </div>
  <div><span style='font-size: small;'>This gives you an excel file that is functional but not pretty. Format it yourself if you want it to look nice.
      <BR>Excel may ask you if it is safe to open this file. The risk that it is not safe is low, I suggest you click the &quot;Yes&quot; button.</span></div>
  ";
$display_block .= '</div>';
?>
<!DOCTYPE HTML>
<html>
<?php (@include_once('./mobile_head_bs.php')) or die('Cannot find this file to include: mobile_head_bs.php<BR>'); ?>
<body class="bg-dark text-white">

<nav class="navbar navbar-expand-sm fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <a class="navbar-brand" href="projects_indexm2.php?pm_id=<?php echo($pm_id); ?>"><?php echo("$school_title $running_title"); ?></a>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../../.." class="nav-link px-2 text-white-50">Root options</a></li>
        </ul>
        <div class="navbar-brand ms-auto">
            <a target="_self" class="navbar-brand" href="../session_modules_bs.php?year_begin=<?php echo($year_begin); ?>">
                <i class="bi bi-arrow-left"></i> <?php echo("$year_begin/$year_end"); ?> index
            </a>
        </div>
    </div>
</nav>

<div class="container bg-dark text-white">
    <div class="text-center props-logo mt-0">
        <img src="../images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>
    <hr>
    <div class="text-center">
        <?php echo("$display_block"); ?>
    </div>
</div>

</body>
</html>
