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

$staff_username = get_server_username();
if (IsStaff($db, $staff_username, $staff_names) == false) {
    echo 'You do not have access to this page. If you think this is an error please contact the system administrator.<BR>';
    echo "<p><A target='self' href='../..'>Return to the home page</a>.</P>";
    exit();
}

if (!isset($_REQUEST['pm_id'])) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = (int)$_REQUEST['pm_id'];

if (!isset($_REQUEST['p_id'])) {
    $_REQUEST['p_id'] = null;
}
$master_id = $_REQUEST['p_id'];

if (!isset($_REQUEST['op'])) {
    $_REQUEST['op'] = null;
}
if (!isset($_REQUEST['p_aero'])) {
    $_REQUEST['p_aero'] = null;
}
if (!isset($_REQUEST['p_auto'])) {
    $_REQUEST['p_auto'] = null;
}
if (!isset($_REQUEST['p_mech'])) {
    $_REQUEST['p_mech'] = null;
}
if (!isset($_REQUEST['p_medi'])) {
    $_REQUEST['p_medi'] = null;
}
if (!isset($_REQUEST['p_oilc'])) {
    $_REQUEST['p_oilc'] = null;
}
if (!isset($_REQUEST['p_othr'])) {
    $_REQUEST['p_othr'] = null;
}
if (!isset($_REQUEST['p_mtcr'])) {
    $_REQUEST['p_mtcr'] = null;
}

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
//$option_text[0]='Related sites'; $option[0]='_relsites';
//$option_text[1]='Related modules'; $option[1]='_relmods';
//$option_text[2]='Background reading'; $option[2]='_backread';
//$option_text[3]='Skills necessary'; $option[3]='_skills';
//$option_text[4]='Software requirements'; $option[4]='_software';
//$option_text[5]='Software support required'; $option[5]='_softsupport';
//$option_text[6]='Lab work description'; $option[6]='_labs';
//$option_text[7]='Workshop requirements'; $option[7]='_works';
//$option_text[8]='Furthur information'; $option[8]='_furtherinfo';

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

$now_datetime = date('Y-m-d H:i:s');
$date_diff = day_diff($now_datetime, $staff_close_date);

$display_block = '';
$module_ref_block = "<h3>$module_short_description - $module_prefix$module1$module_postfix - $short_code</h3>";

$display_block .= "$module_ref_block";

if ($_REQUEST['op'] == 'save') {
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
    //$display_block .= "f_progs=$f_progs<BR>";

    $master_id = addslashes($_REQUEST['master_id']);

    $f_title = addslashes($_REQUEST['f_title']);
    $f_title = convert_smart_quotes($f_title);
    $f_outline = $_REQUEST['f_outline'];
    $f_outline = addslashes($f_outline);
    $f_outline = convert_smart_quotes($f_outline);
    // Some tidying up of things that should not have ben put there from the project entry process
    $f_outline = str_replace('<br /><br />', '<br />', $f_outline);
    //$f_outline = str_replace("&gt;", ">", $f_outline);
    //$f_outline = str_replace("&lt;", "<", $f_outline);
    //$f_outline = str_replace("&quot;", '"', $f_outline);
    $f_supervisor_id = addslashes($_REQUEST['f_supervisor_id']);
    $f_assocstaff_id = addslashes($_REQUEST['f_assocstaff_id']);
    $f_PGprojects = addslashes($_REQUEST['f_PGprojects']);

    for ($i = 0 ; $i < sizeof($option) ; $i++) {
        $fname = 'f' . $option[$i];
        $option_value[$i] = addslashes($_REQUEST[$fname]);
        $option_value[$i] = convert_smart_quotes($option_value[$i]);
    }

    $program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $program_variant_flags = $prog;//GetProgramVariantFlagsStaff($db, $project_details, $master_id);

    $display_block .= DisplayProjectBlockStaff($db, $master_id, $staff_names2, $f_title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flag, $option_flag_v, $option_flag_m, $program_variants, $program_variant_flags);

    $supervisor_name = GetStaffNameFromID($db, $f_supervisor_id, $staff_names2, false);
    $f_supervisor_name = $supervisor_name ;
    $second_marker_name = GetStaffNameFromID($db, $f_assocstaff_id, $staff_names2, false);

    $date_now = date('Y-m-d H:i:s'); //'2016-03-01 00:00:00';// date_now();
    $date_modified = date('Y-m-d H:i:s'); //'2016-03-01 00:00:00';// date_now();

    try {
        $results = $db->prepare("UPDATE $master_name SET f_title=?, f_supervisor=?, f_supervisor_id=? WHERE id=?");
        $results->execute([$f_title, $supervisor_name, $f_supervisor_id, $master_id]);
    } catch (PDOException $ex) {
        echo("An Error occured accessing the database <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }

    $f_UGprojects = '0';

    // Build option columns/params — column names are server-side constants, only values are parameterised
    $option_cols = [];
    $option_vals = [];
    for ($i = 0; $i < sizeof($option); $i++) {
        $fname = 'f' . $option[$i];
        if ($option_flag[$i] == 1) {
            $value = convert_smart_quotes(addslashes($_REQUEST[$fname] ?? ''));
        } else {
            $value = '';
        }
        $option_cols[] = "$fname=?";
        $option_vals[] = $value;
    }
    $options_sql = implode(', ', $option_cols);

    $detail_params = array_merge(
        [$second_marker_name, $f_assocstaff_id, $f_progs, $f_outline, $f_UGprojects, $f_PGprojects],
        $option_vals,
        [$master_id]
    );
    try {
        $results = $db->prepare("UPDATE $project_details SET f_assocstaff=?, f_assocstaff_id=?, f_progs=?, f_outline=?, f_UGprojects=?, f_PGprojects=?, $options_sql WHERE master_id=?");
        $results->execute($detail_params);
    } catch (PDOException $ex) {
        echo("An Error occured accessing the database <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $display_block .= '<h1>Saved ...</h1>';
} else {//display for modification
    $display_block = "<h3>Modify this Project Title/Theme</h3>\n";
    $display_block .= "$module_ref_block";
    $display_block .= "<H3>Updates to the project theme database closes at midnight $cldate</H3>\n";

    try {
        $results = $db->prepare("SELECT * FROM $master_name WHERE id=?");
        $results->execute([$master_id]);
    } catch (PDOException $ex) {
        echo("An Error occured accessing the database <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $row = $results->fetch();
    $f_supervisor_name = $row['f_supervisor'];
    $f_supervisor_id = $row['f_supervisor_id'];
    $f_title = $row['f_title'];

    try {
        $results = $db->prepare("SELECT * FROM $project_details WHERE master_id=?");
        $results->execute([$master_id]);
    } catch (PDOException $ex) {
        echo("An Error occured accessing the database <BR>\n");
        error_log($ex->getMessage());
        echo "A database error occurred. Please contact the system administrator.<BR>\n";
        exit();
    }
    $row = $results->fetch();

    //`id`, `master_id`, `date_added`, `date_modified`, `f_assocstaff`, `f_assocstaff_id`,
    // `f_outline`, `f_relmods`, `f_backread`, `f_relsites`,
    // `f_skills`, `f_software`, `f_softsupport`, `f_labs`, `f_works`, `f_programmes`,
    // `f_progs`, `f_UGprojects`, `f_PGprojects`, `f_furtherinfo
    $date_added = $row['date_added'];
    $date_modified = $row['date_modified'];
    $f_assocstaff_name = $row['f_assocstaff'];
    $f_assocstaff_id = $row['f_assocstaff_id'];

    $f_outline = $row['f_outline'];
    $f_outline = stripcslashes($f_outline);
    $f_outline = nl2br($f_outline);

    $f_relmods = $row['f_relmods'];
    $f_backread = $row['f_backread'];

    $f_relsites = $row['f_relsites'];
    $f_skills = $row['f_skills'];
    $f_software = $row['f_software'];
    $f_softsupport = $row['f_softsupport'];
    $f_labs = $row['f_labs'];
    $f_works = $row['f_works'];
    $f_programmes = $row['f_programmes'];
    $f_progs = $row['f_progs'];
    $f_UGprojects = $row['f_UGprojects'];
    $f_PGprojects = $row['f_PGprojects'];
    $f_furtherinfo = $row['f_furtherinfo'];

    $option_value[0] = $f_relsites;
    $option_value[1] = $f_relmods;
    $option_value[2] = $f_backread;
    $option_value[3] = $f_skills;
    $option_value[4] = $f_software;
    $option_value[5] = $f_softsupport;
    $option_value[6] = $f_labs;
    $option_value[7] = $f_works;
    $option_value[8] = $f_furtherinfo;

    $display_block .= "<form method='post' onsubmit='return validate_form();' action='$_SERVER[PHP_SELF]?pm_id=$pm_id'>\n";

    $program_variants = GetProgramVariantsStaff($db, $projects_setup_proposals, $pm_id, $num_prog_variants);
    $program_variant_flags = GetProgramVariantFlagsStaff($db, $project_details, $master_id);

    $display_block .= DisplayProjectSelectBlockStaff($db, $master_id, $staff_names2, $f_title, $f_supervisor_id, $f_assocstaff_id, $f_outline, $f_PGprojects, $option_value, $option_text, $option, $option_flag, $option_flag_v, $option_flag_m, $program_variants, $program_variant_flags);

    $display_block .= "<input type='hidden' name='op' value='save'>\n";
    $display_block .= "<input type='hidden' name='p_id' value='$master_id'>\n";
    $display_block .= "<input type='hidden' name='master_id' value='$master_id'>\n";
    $display_block .= "<p><input type='submit' class='btn btn-primary' name='submit' value='Save Changes'></p>\n";
    $display_block .= '</form>';

    $display_block .= "<div class='mt-3'>\n";
    $display_block .= "<a href='delproject2.php?pm_id=$pm_id&p_id=$master_id' class='btn btn-danger'>Delete this project</a>\n";
    $display_block .= "</div>\n";
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
            <li class="nav-item"><a href="./modproject_bystaff2.php?pm_id=<?php echo($pm_id); ?>" class="nav-link px-2 text-white-50">Staff list</a></li>
            <li class="nav-item"><a href="./list_staff_projects.php?pm_id=<?php echo($pm_id); ?>&stid=<?php echo($f_supervisor_id); ?>" class="nav-link px-2 text-white-50">
                <?php echo($f_supervisor_name); ?>'s projects</a></li>
        </ul>
        <div class="navbar-brand ms-auto">
            <a class="navbar-brand" href="./modproject_bystaff2.php?pm_id=<?php echo($pm_id); ?>">
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
