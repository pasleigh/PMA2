<?php
// connect to the datbase
(@include_once('./set_dir_props.php')) or die('Cannot find this file to include: set_dir_props.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables');
(@include_once('../staff_functions.php')) or die('Cannot setup database tables');

(@include_once('../proposals/project_display_functions_staff.php')) or die('Cannot include project_display_functions_staff.php');
(@include_once('../admin2/utility_functions.php')) or die('Cannot include utility_functions.php');
(@include_once('../admin2/adminusers.php')) or die('Cannot setup admin users');

(@include_once('../staff_student_functions.php')) or die('Cannot find staff_student_functions.php');

$staff_username = 'unknown';
if (array_key_exists('REMOTE_USER', $_SERVER)) {
    $staff_username = get_server_username();
} else {
    if (isset($_SESSION['USERNAME'])) {
        $staff_username = $_SESSION['USERNAME'];
    }
}
if (IsStaff($db, $staff_username, $staff_names) == false) {
    echo 'You do not have access to this page. If you think this is an error please contact the system administrator.<BR>';
    echo "<p><A target='self' href='../..'>Return to the home page</a>.</P>";
    exit();
}

if (!isset($_REQUEST['pm_id'])) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];

if (!isset($_REQUEST['stid'])) {
    $_REQUEST['stid'] = null;
}
$staffid = $_REQUEST['stid'];

if (!isset($_REQUEST['op'])) {
    $_REQUEST['op'] = null;
}
$OP = $_REQUEST['op'];

if (!isset($_REQUEST['f_title'])) {
    $_REQUEST['f_title'] = null;
}
if (!isset($_REQUEST['f_supervisor_id'])) {
    $_REQUEST['f_supervisor_id'] = null;
}
if (!isset($_REQUEST['f_assocstaff_id'])) {
    $_REQUEST['f_assocstaff_id'] = null;
}
if (!isset($_REQUEST['f_outline'])) {
    $_REQUEST['f_outline'] = null;
}
if (!isset($_REQUEST['f_UGprojects'])) {
    $_REQUEST['f_UGprojects'] = null;
}
if (!isset($_REQUEST['f_PGprojects'])) {
    $_REQUEST['f_PGprojects'] = null;
}

for ($i = 0 ; $i < sizeof($option) ; $i++) {
    $fname = 'f' . $option[$i];
    if (!isset($_REQUEST[$fname])) {
        $_REQUEST[$fname] = null;
    }
}

/*
echo("_REQUEST<BR>\n");
foreach ($_REQUEST as $key=>$val )
{
    echo "Key: ".$key." val: " .$val."<BR>\n";
}
*/

$staff_close_date = $staff_close;
$str_staff_close_date = date('j F Y', strtotime($staff_close_date));
$cldate = $str_staff_close_date;

$display_block = '';
$module_ref_block = "$module_short_description - $module_prefix$module1$module_postfix - $short_code";

$display_block .= "<h1><span class='text-white'>Add a Project Theme for: </span>\n";
$display_block .= "$module_ref_block\n";

if ($_REQUEST['op'] != 'add') {
    $display_block .= "<H3>Updates to the project theme database closes at <span class='text-deadline'> midnight $cldate</span></H3>\n";
    $display_block .= "<form method='post' onsubmit='return validate_form();' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

    for ($i = 0 ; $i < sizeof($option) ; $i++) {
        $option_value[$i] = '';
    }

    $program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $program_variant_flags = GetProgramVariantFlagsStaff($db, $project_details, null);

    $display_block .= DisplayProjectSelectBlockStaff($db, null, $staff_names2, null, $staffid, null, null, null, $option_value, $option_text, $option, $option_flag, $option_flag_v, $option_flag_m, $program_variants, $program_variant_flags);

    $display_block .= "<input type='hidden' name='op' value='add'>\n";
    $display_block .= "<p><input type='submit' class='btn btn-primary' name='submit' value='Add this project theme'></p>\n";
    $display_block .= '</form>';
} elseif ($_REQUEST['op'] == 'add') {
    // Get the prog variants
    for ($i = 0; $i < 7 ; $i++) {
        $p_name_id = "prog$i";
        if (isset($_REQUEST[$p_name_id])) {
            $prog[$i] = 1;
        } else {
            $prog[$i] = 0;
        }
    }
    $f_progs = join('', $prog);

    $f_title = addslashes($_REQUEST['f_title']);
    $f_title = convert_smart_quotes($f_title);
    $f_outline = $_REQUEST['f_outline'];
    $f_outline = addslashes($f_outline);
    $f_outline = convert_smart_quotes($f_outline);
    // Some tidying up of things that should not have ben put there from the project entry process
    $f_outline = str_replace('<br /><br />', '<br />', $f_outline);
    $f_supervisor_id = addslashes($_REQUEST['f_supervisor_id']);
    $f_assocstaff_id = addslashes($_REQUEST['f_assocstaff_id']);
    $f_PGprojects = addslashes($_REQUEST['f_PGprojects']);

    for ($i = 0 ; $i < sizeof($option) ; $i++) {
        $fname = 'f' . $option[$i];
        $option_value[$i] = $_REQUEST[$fname];
    }

    $program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $program_variant_flags = $prog;//GetProgramVariantFlagsStaff($db, $project_details, $master_id);

    $display_block .= DisplayProjectBlockStaff($db, null, $staff_names2, $f_title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flag, $option_flag_v, $option_flag_m, $program_variants, $program_variant_flags);

    $supervisor_name = GetStaffNameFromID($db, $f_supervisor_id, $staff_names2, false);
    $second_marker_name = GetStaffNameFromID($db, $f_assocstaff_id, $staff_names2, false);

    //    $display_block .= "Title: $f_title<BR>\n";
    //    $display_block .= "Outline: $f_outline<BR>\n";
    //    $display_block .= "Supervisor: $supervisor_name ($f_supervisor_id)<BR>\n";
    //    $display_block .= "Second marker: $second_marker_name ($f_assocstaff_id)<BR>\n";
    //    $display_block .= "Sites: $f_relsites<BR>\n";
    //    $display_block .= "Number of projects: $f_PGprojects<BR>\n";

    $date_now = date('Y-m-d H:i:s'); //'2016-03-01 00:00:00';// date_now();
    $date_modified = date('Y-m-d H:i:s'); //'2016-03-01 00:00:00';// date_now();

    try {
        $results = $db->prepare("INSERT INTO $master_name SET date_added=?, date_modified=?, f_title=?, f_supervisor=?, f_supervisor_id=?");
        $results->execute([$date_now, $date_modified, $f_title, $supervisor_name, $f_supervisor_id]);
    } catch (PDOException $ex) {
        echo("An Error occured accessing the database <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $master_id = $db->lastInsertId();

    $f_UGprojects = '0';

    // Build option columns/params — column names are server-side constants, only values are parameterised
    $option_cols = [];
    $option_vals = [];
    for ($i = 0; $i < sizeof($option); $i++) {
        $fname = 'f' . $option[$i];
        if ($option_flag[$i] == 1) {
            $value = convert_smart_quotes($_REQUEST[$fname] ?? '');
        } else {
            $value = '';
        }
        $option_cols[] = "$fname=?";
        $option_vals[] = $value;
    }
    $options_sql = implode(', ', $option_cols);

    $detail_params = array_merge(
        [$master_id, $second_marker_name, $f_assocstaff_id, $f_progs, $f_outline, $f_UGprojects, $f_PGprojects],
        $option_vals
    );
    try {
        $results = $db->prepare("INSERT INTO $project_details SET master_id=?, f_assocstaff=?, f_assocstaff_id=?, f_progs=?, f_outline=?, f_UGprojects=?, f_PGprojects=?, $options_sql");
        $results->execute($detail_params);
    } catch (PDOException $ex) {
        echo("An Error occured accessing the database <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
}
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
            <a class="navbar-brand" href="./projects_indexm2.php?pm_id=<?php echo($pm_id); ?>">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>
</nav>

<div class="container bg-dark text-white">
    <div class="text-center props-logo">
        <img src="../images/<?php echo($header_image) ?>" alt="Logo" width="320" height="87"/>
    </div>
    <hr>
    <script src="./validate_project_entry.js"></script>
    <script type="text/javascript">
        function popup(mylink, windowname) {
            if (!window.focus) return true;
            var href;
            if (typeof(mylink) == 'string')
                href = mylink;
            else
                href = mylink.href;
            window.open(href, windowname, 'width=550,height=500,scrollbars=yes');
            return false;
        }
    </script>

    <?php echo $display_block; ?>
</div>

</body>
</html>
