<?php
// Detect AJAX save ops before includes so buffered include output can be discarded
$_pma_ajax_ops = ['modify_module_info', 'modify_dates', 'modify_assessment', 'modify_ac', 'modify_options', 'modify_selection'];
$_is_ajax      = in_array($_POST['op'] ?? '', $_pma_ajax_ops);
if ($_is_ajax) {
    ob_start();
}

(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');

(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');

(@include_once('./assess_table_template.php')) or die('Cannot include assess_table_template.php<BR>');
(@include_once('./assess_total_table_template.php')) or die('Cannot include assess_total_table_template.php<BR>');
(@include_once('./allocated_table_template.php')) or die('Cannot include allocated_table_template.php<BR>');
(@include_once('./students_table_template.php')) or die('Cannot include students_table_template.php<BR>');
(@include_once('./template_functions_pdo.php')) or die('Cannot include template_functions.php<BR>');
(@include_once('../staff_functions.php')) or die('Cannot include staff_functions.php<BR>');

(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

$admin_access = false;
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = (int)$_REQUEST['pm_id'];
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;
}

$original_referer = '';
if (isset($_REQUEST['referer'])) {
    $original_referer = $_REQUEST['referer'];
}
if (array_key_exists('HTTP_REFERER', $_SERVER)) {
    if ($_SERVER['PHP_SELF'] != $_SERVER['HTTP_REFERER']) {
        $original_referer = $_SERVER['HTTP_REFERER'];
    }
}

$page_data = [];

if ($is_module_admin || $admin_access) {
    $POST_OP = $_POST['op'] ?? '';
    // $option and $option_text are injected by setup_tables.php via its proposals query
    $option      ??= [];
    $option_text ??= [];

    // -----------------------------------------------------------------------
    // AJAX save handlers — return JSON and exit
    // -----------------------------------------------------------------------
    if ($_is_ajax) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        $result = ['success' => false, 'message' => 'Unknown operation.'];

        try {
            switch ($POST_OP) {

                case 'modify_module_info':
                    $v_module        = trim($_POST['module1_code']    ?? '');
                    $v_prefix        = trim($_POST['module_prefix']   ?? '');
                    $v_postfix       = trim($_POST['module_postfix']  ?? '');
                    $v_short_desc    = trim($_POST['module_short_desc'] ?? '');
                    $stmt = $db->prepare(
                        "UPDATE $projects_setup SET module=?, module_prefix=?, module_postfix=?, module_short_desc=? WHERE id=?"
                    );
                    $stmt->execute([$v_module, $v_prefix, $v_postfix, $v_short_desc, $pm_id]);
                    $result = ['success' => true];
                    break;

                case 'modify_dates':
                    $staff_close = trim($_POST['staff_close_date']               ?? '');
                    $sel_open    = trim($_POST['student_selection_open_date']     ?? '');
                    $sel_close   = trim($_POST['student_selection_close_date']    ?? '');
                    $alloc_date  = trim($_POST['allocation_date']                 ?? '');
                    $stmt = $db->prepare(
                        "UPDATE $projects_setup SET staff_close=?, sel_open=?, sel_close=?, alloc_date=? WHERE id=?"
                    );
                    $stmt->execute([$staff_close, $sel_open, $sel_close, $alloc_date, $pm_id]);
                    $result = ['success' => true];
                    break;

                case 'modify_assessment':
                    $num_parts_val      = max(1, min(5, (int)($_POST['num_parts']           ?? 1)));
                    $accept_var_val     = (int)($_POST['accept_var']          ?? 0);
                    $pass_mark_val      = (int)($_POST['pass_mark']           ?? 40);
                    $min_fw_val         = (int)($_POST['min_feedback_words']  ?? 0);
                    $leader_id          = (int)($_POST['staff_id']            ?? 0);
                    $leader_name        = GetStaffNameFromID($db, $leader_id, $staff_names2, 0);
                    $stmt = $db->prepare(
                        "UPDATE $projects_setup SET num_parts=?, accept_var=?, pass_mark=?, min_feedback_words=?, module_leader=? WHERE id=?"
                    );
                    $stmt->execute([$num_parts_val, $accept_var_val, $pass_mark_val, $min_fw_val, $leader_name, $pm_id]);

                    for ($part = 1; $part <= 5; $part++) {
                        $col_submit   = "p{$part}_submission_date";
                        $col_marking  = "p{$part}_marking_date";
                        $col_percent  = "p{$part}_percent";
                        $col_markers  = "p{$part}_num_markers";
                        $col_max      = "p{$part}_max";
                        $col_numeric  = "p{$part}_numeric";
                        $col_desc     = "p{$part}_desc";
                        $col_feedback = "p{$part}_feedback";

                        $v_submit   = trim($_POST[$col_submit]   ?? '');
                        $v_marking  = trim($_POST[$col_marking]  ?? '');
                        $v_percent  = (int)($_POST[$col_percent]  ?? 0);
                        $v_markers  = (int)($_POST[$col_markers]  ?? 1);
                        $v_max      = (int)($_POST[$col_max]      ?? 100);
                        $v_numeric  = (int)($_POST[$col_numeric]  ?? 0);
                        $v_desc     = trim($_POST[$col_desc]     ?? '');
                        $v_feedback = (int)($_POST[$col_feedback] ?? 0);

                        if (!DoesColumnExistInTable($db, $projects_setup, $col_feedback)) {
                            $db->query("ALTER TABLE $projects_setup ADD `$col_feedback` int DEFAULT 0");
                        }

                        $stmt = $db->prepare(
                            "UPDATE $projects_setup SET
                                `$col_submit`=?, `$col_marking`=?, `use_p{$part}`='1',
                                `$col_percent`=?, `$col_markers`=?, `$col_max`=?,
                                `$col_desc`=?, `$col_numeric`=?, `$col_feedback`=?
                             WHERE id=?"
                        );
                        $stmt->execute([
                            $v_submit, $v_marking, $v_percent, $v_markers, $v_max,
                            $v_desc, $v_numeric, $v_feedback, $pm_id,
                        ]);
                    }
                    $result = ['success' => true];
                    break;

                case 'modify_ac':
                    $visible    = (int)($_POST['ac_visible']    ?? 0);
                    $grade      = (int)($_POST['ac_grade']      ?? 0);
                    $class_f    = (int)($_POST['ac_class']      ?? 0);
                    $gpa        = (int)($_POST['ac_gpa']        ?? 0);
                    $university = (int)($_POST['ac_university'] ?? 0);
                    $school     = (int)($_POST['ac_school']     ?? 0);
                    $stmt = $db->prepare(
                        "UPDATE $assessment_criteria_flags_table
                         SET visible_flag=?, grade_text_flag=?, class_flag=?, gpa_flag=?,
                             descriptor_university_flag=?, descriptor_school_flag=?"
                    );
                    $stmt->execute([$visible, $grade, $class_f, $gpa, $university, $school]);
                    $result = ['success' => true];
                    break;

                case 'modify_options':
                    // Programme variants
                    $num_progs_list_max    = 7;
                    $prog_short_name_parts = [];
                    for ($i = 1; $i <= $num_progs_list_max; $i++) {
                        $name = trim($_POST["p_list$i"] ?? '');
                        if ($name === '') {
                            break;
                        }
                        $prog_short_name_parts[] = $name;
                    }
                    $num_progs_list         = count($prog_short_name_parts);
                    $prog_short_name_string = implode(';', $prog_short_name_parts);
                    $stmt = $db->prepare(
                        "UPDATE $projects_setup_proposals SET num_progs=?, prog_short_names=? WHERE proj_id=?"
                    );
                    $stmt->execute([$num_progs_list, $prog_short_name_string, $pm_id]);

                    // Extra info option flags
                    $set_cols = [];
                    $set_vals = [];
                    for ($i = 0; $i < count($option); $i++) {
                        $o = $option[$i];
                        $set_cols[] = "i{$o}=?";
                        $set_cols[] = "m{$o}=?";
                        $set_cols[] = "v{$o}=?";
                        $set_vals[] = (int)($_POST["i{$o}"] ?? 0);
                        $set_vals[] = (int)($_POST["m{$o}"] ?? 0);
                        $set_vals[] = (int)($_POST["v{$o}"] ?? 0);
                    }
                    if (!empty($set_cols)) {
                        $set_vals[] = $pm_id;
                        $stmt = $db->prepare(
                            "UPDATE $projects_setup_proposals SET " . implode(', ', $set_cols) . " WHERE proj_id=?"
                        );
                        $stmt->execute($set_vals);
                    }
                    $result = ['success' => true];
                    break;

                case 'modify_selection':
                    $ranked_choice         = (int)($_POST['ranked_choice']          ?? 0);
                    $choice_number         = (int)($_POST['choice_number']           ?? 3);
                    $use_project_area      = (int)($_POST['use_project_area']        ?? 0);
                    $use_project_selection = (int)($_POST['use_project_selection']   ?? 0);
                    $stmt = $db->prepare(
                        "UPDATE $projects_setup SET ranked_choice=?, choice_number=?, use_project_area=?, use_project_selection=? WHERE id=?"
                    );
                    $stmt->execute([$ranked_choice, $choice_number, $use_project_area, $use_project_selection, $pm_id]);
                    $result = ['success' => true];
                    break;
            }
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            $result = ['success' => false, 'message' => 'A database error occurred.'];
        }

        echo json_encode($result);
        exit();
    }

    // -----------------------------------------------------------------------
    // Handle the full-page modify POST — logic unchanged from original
    // -----------------------------------------------------------------------
    if ($POST_OP == 'modify') {
        $module1 = trim($_POST['module1_code']);
        $module_prefix = trim($_POST['module_prefix']);
        $module_postfix = trim($_POST['module_postfix']);
        $module_short_description = trim($_POST['module_short_desc']);

        $staff_close = trim($_POST['staff_close_date']);
        $sel_open = trim($_POST['student_selection_open_date']);
        $sel_close = trim($_POST['student_selection_close_date']);
        $alloc_date = trim($_POST['allocation_date']);

        $num_parts = trim($_POST['num_parts']);
        $accept_var = trim($_POST['accept_var']);
        $pass_mark = trim($_POST['pass_mark']);
        $module_leader_id = trim($_POST['staff_id']);
        $module_leader_fullname = GetStaffNameFromID($db, $module_leader_id, $staff_names2, 0);
        $min_feedback_words = trim($_POST['min_feedback_words']);

        $query = "update $projects_setup set module='$module1', module_prefix='$module_prefix', module_postfix='$module_postfix',
            module_short_desc='$module_short_description',
            staff_close='$staff_close', sel_open='$sel_open', sel_close='$sel_close', alloc_date='$alloc_date', num_parts='$num_parts',
            accept_var='$accept_var', pass_mark='$pass_mark', module_leader='$module_leader_fullname', min_feedback_words='$min_feedback_words'
             WHERE id='$pm_id'";
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }

        for ($part = 1; $part <= $num_parts; $part++) {
            $p_submit = "p$part" . '_submission_date';
            $submit_dates_part = $_POST[$p_submit];
            $p_marking = "p$part" . '_marking_date';
            $markby_dates_part = $_POST[$p_marking];

            $p_use = "use_p$part";
            $p_percent = "p$part" . '_percent';
            $weights_part = $_POST[$p_percent];
            $p_num_markers = "p$part" . '_num_markers';
            $num_markers_part = $_POST[$p_num_markers];
            $p_max = "p$part" . '_max';
            $max_marks_part = $_POST[$p_max];
            $p_is_numeric = "p$part" . '_numeric';
            $is_numeric_part = isset($_POST[$p_is_numeric]) ? 1 : 0;
            $p_desc = "p$part" . '_desc';
            $descs_part = $_POST[$p_desc];

            $p_show_feedback_id = "p$part" . '_feedback';
            $show_part_feedback_flag = isset($_POST[$p_show_feedback_id]) ? 1 : 0;

            if (!DoesColumnExistInTable($db, $projects_setup, $p_show_feedback_id)) {
                $db->query("ALTER TABLE $projects_setup ADD `$p_show_feedback_id` int DEFAULT 0");
            }

            $query = "update $projects_setup set $p_submit='$submit_dates_part', $p_marking='$markby_dates_part', $p_use='1', $p_percent='$weights_part',
                        $p_num_markers='$num_markers_part', $p_max='$max_marks_part', $p_desc='$descs_part', $p_is_numeric='$is_numeric_part', $p_show_feedback_id='$show_part_feedback_flag'  WHERE id='$pm_id'";
            try {
                $db->query($query);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
        }

        $choice_number = trim($_POST['choice_number']);
        $ranked_choice_int = isset($_POST['ranked_choice']) ? 1 : 0;
        $use_project_selection_int = isset($_POST['use_project_selection']) ? 1 : 0;
        $use_project_area_int = isset($_POST['use_project_area']) ? 1 : 0;

        $query = "update $projects_setup set ranked_choice='$ranked_choice_int', choice_number='$choice_number', use_project_area='$use_project_area_int', use_project_selection='$use_project_selection_int' WHERE id='$pm_id'";
        try {
            $db->query($query);
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }

        // Programme variants
        $num_progs_list_max = 7;
        $prog_short_name_string = '';
        $sep = ';';
        $num_progs_list = 0;
        for ($i = 1; $i <= $num_progs_list_max; $i++) {
            $prog_short_name = trim($_POST["p_list$i"] ?? '');
            if ($prog_short_name == '') {
                $num_progs_list = $i - 1;
                break;
            }
            $prog_short_name_string .= ($i == 1 ? '' : $sep) . $prog_short_name;
            $num_progs_list = $i;
        }
        if ($num_progs_list > 0) {
            $query = "update $projects_setup_proposals set num_progs='$num_progs_list', prog_short_names='$prog_short_name_string' WHERE proj_id='$pm_id'";
            try {
                $db->query($query);
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                echo "A database error occurred. Please contact the system administrator.<BR>\n";
                exit();
            }
        }

        // Assessment criteria visibility
        $ac_updates = [
            'visible_flag'               => isset($_POST['ac_visible'])     ? 1 : 0,
            'grade_text_flag'            => isset($_POST['ac_grade'])       ? 1 : 0,
            'class_flag'                 => isset($_POST['ac_class'])       ? 1 : 0,
            'gpa_flag'                   => isset($_POST['ac_gpa'])         ? 1 : 0,
            'descriptor_university_flag' => isset($_POST['ac_university'])  ? 1 : 0,
            'descriptor_school_flag'     => isset($_POST['ac_school'])      ? 1 : 0,
        ];
        $ac_set_parts = implode(', ', array_map(fn($col, $val) => "$col='$val'", array_keys($ac_updates), $ac_updates));
        try {
            $db->query("update $assessment_criteria_flags_table set $ac_set_parts");
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }

        // Extra optional info fields
        $opt_set_parts = [];
        for ($i = 0; $i < count($option); $i++) {
            $o = $option[$i];
            $opt_set_parts[] = "i{$o}='" . (isset($_POST["i{$o}"]) ? 1 : 0) . "'";
            $opt_set_parts[] = "m{$o}='" . (isset($_POST["m{$o}"]) ? 1 : 0) . "'";
            $opt_set_parts[] = "v{$o}='" . (isset($_POST["v{$o}"]) ? 1 : 0) . "'";
        }
        try {
            $db->query("update $projects_setup_proposals set " . implode(', ', $opt_set_parts) . " WHERE proj_id='$pm_id'");
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            echo "A database error occurred. Please contact the system administrator.<BR>\n";
            exit();
        }
    }// end POST modify

    // -----------------------------------------------------------------------
    // Fetch fresh data for display (always, even after a POST)
    // -----------------------------------------------------------------------
    $setup_stmt = $db->prepare("SELECT * FROM $projects_setup WHERE id = ?");
    $setup_stmt->execute([$pm_id]);
    $setup_res = $setup_stmt->fetch(PDO::FETCH_ASSOC);

    $module1                = $setup_res['module'];
    $module_prefix          = $setup_res['module_prefix'];
    $module_postfix         = $setup_res['module_postfix'];
    $module_short_description = $setup_res['module_short_desc'];
    $short_code             = $setup_res['short_code'];
    $year_begin             = $setup_res['year_begin'];
    $year_end               = $setup_res['year_end'];
    $staff_close            = $setup_res['staff_close'];
    $sel_open               = $setup_res['sel_open'];
    $sel_close              = $setup_res['sel_close'];
    $alloc_date             = $setup_res['alloc_date'];
    $num_parts              = $setup_res['num_parts'];
    $accept_var             = $setup_res['accept_var'];
    $pass_mark              = $setup_res['pass_mark'];
    $module_leader_fullname = $setup_res['module_leader'];
    $min_feedback_words     = $setup_res['min_feedback_words'];

    // Parts
    $parts_data = [];
    for ($i = 1; $i <= 5; $i++) {
        $parts_data[$i] = [
            'desc'         => $setup_res["p{$i}_desc"] ?? '',
            'num_markers'  => (int)($setup_res["p{$i}_num_markers"] ?? 1),
            'percent'      => (int)($setup_res["p{$i}_percent"] ?? 0),
            'max'          => (int)($setup_res["p{$i}_max"] ?? 100),
            'numeric'      => (int)($setup_res["p{$i}_numeric"] ?? 0),
            'feedback'     => (int)($setup_res["p{$i}_feedback"] ?? 0),
            'submit_date'  => $setup_res["p{$i}_submission_date"] ?? '',
            'marking_date' => $setup_res["p{$i}_marking_date"] ?? '',
        ];
    }

    // Assessment criteria
    $ac_stmt = $db->query("SELECT * FROM $assessment_criteria_flags_table WHERE id = 0");
    $ac_recs = $ac_stmt->fetch(PDO::FETCH_ASSOC);

    // Proposals (programme variants + extra options)
    $prop_stmt = $db->prepare("SELECT * FROM $projects_setup_proposals WHERE proj_id = ?");
    $prop_stmt->execute([$pm_id]);
    $prop_recs = $prop_stmt->fetch(PDO::FETCH_ASSOC);

    $prog_list = [];
    if ($prop_recs && !empty($prop_recs['prog_short_names'])) {
        $prog_list = explode(';', $prop_recs['prog_short_names']);
    }

    $options_data = [];
    for ($i = 0; $i < count($option); $i++) {
        $o = $option[$i];
        $options_data[] = [
            'label'   => $option_text[$i],
            'name'    => 'i' . $o,
            'mname'   => 'm' . $o,
            'vname'   => 'v' . $o,
            'checked' => (int)($prop_recs['i' . $o] ?? 0),
            'mand'    => (int)($prop_recs['m' . $o] ?? 0),
            'vis'     => (int)($prop_recs['v' . $o] ?? 0),
        ];
    }

    // Staff select HTML (server-rendered since it depends on staff_names2)
    $staff_select_html = GetStaffSelectID(
        $db, $staff_names2, 'staff_id',
        GetStaffIDFromName($db, $module_leader_fullname, $staff_names2)
    );

    // Build the JSON payload for JS
    $page_data = [
        'pm_id'               => $pm_id,
        'module_prefix'       => $module_prefix,
        'module1'             => $module1,
        'module_postfix'      => $module_postfix,
        'short_code'          => $short_code,
        'module_short_desc'   => $module_short_description,
        'year_begin'          => $year_begin,
        'year_end'            => $year_end,
        'staff_close'         => $staff_close,
        'sel_open'            => $sel_open,
        'sel_close'           => $sel_close,
        'alloc_date'          => $alloc_date,
        'num_parts'           => (int)$num_parts,
        'staff_select_html'   => $staff_select_html,
        'accept_var'          => $accept_var,
        'pass_mark'           => $pass_mark,
        'min_feedback_words'  => $min_feedback_words,
        'parts'               => $parts_data,
        'ac' => [
            'visible'    => (int)($ac_recs['visible_flag'] ?? 0),
            'class'      => (int)($ac_recs['class_flag'] ?? 0),
            'grade'      => (int)($ac_recs['grade_text_flag'] ?? 0),
            'gpa'        => (int)($ac_recs['gpa_flag'] ?? 0),
            'university' => (int)($ac_recs['descriptor_university_flag'] ?? 0),
            'school'     => (int)($ac_recs['descriptor_school_flag'] ?? 0),
        ],
        'prog_list'             => $prog_list,
        'options'               => $options_data,
        'ranked_choice'         => (int)($setup_res['ranked_choice'] ?? 0),
        'choice_number'         => (int)($setup_res['choice_number'] ?? 3),
        'use_project_selection' => (int)($setup_res['use_project_selection'] ?? 1),
        'use_project_area'      => (int)($setup_res['use_project_area'] ?? 0),
    ];
}

$self_url = htmlspecialchars($_SERVER['PHP_SELF']) . '?pm_id=' . (int)$pm_id;
?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<style>
.table-sm > thead > tr > th,
.table-sm > tbody > tr > td { padding: 2px 6px; }
</style>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Edit module entry &mdash; <?php echo htmlspecialchars("$module_short_description ($short_code) $module_prefix$module1$module_postfix $year_begin"); ?></span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../projectmarking_select_bs.php?pm_id=<?php echo (int)$pm_id; ?>" class="nav-link px-2 text-white-50">Module page</a></li>
            <li class="nav-item"><a target="_self" href="../session_modules_bs.php?year_begin=<?php echo (int)$year_begin; ?>" class="nav-link px-2 text-white-50">This year module list</a></li>
            <li class="nav-item"><a target="_self" href="./edit_module_settings.php?pm_id=<?php echo (int)$pm_id; ?>" class="nav-link px-2 text-white-50">Module admin page</a></li>
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

<?php if ($is_module_admin || $admin_access): ?>

<h2 style="font-size: x-large; color: darkgoldenrod;"><?php echo htmlspecialchars($access_phrase ?? ''); ?></h2>

<!-- ===== Module and year details ===== -->
<section class="pma-ajax-section">
<h2>Module and year details</h2>
<table id="tbl-module-info" class="table table-bordered table-sm" style="width:700px">
    <thead><tr><th>Setting</th><th>Value</th></tr></thead>
    <tbody></tbody>
</table>
<p class="mt-2">
    <button type="button" class="btn btn-success btn-sm pma-save"
        data-op="modify_module_info" data-src="tbl-module-info">Save module info</button>
    <span class="pma-fb ms-2 small"></span>
</p>
</section>

<!-- ===== Key dates ===== -->
<section class="pma-ajax-section mt-4">
<h2>Key dates</h2>
<table id="tbl-dates" class="table table-bordered table-sm" style="width:700px">
    <thead><tr><th>Setting</th><th>Value</th></tr></thead>
    <tbody></tbody>
</table>
<p class="mt-2">
    <button type="button" class="btn btn-success btn-sm pma-save"
        data-op="modify_dates" data-src="tbl-dates">Save dates</button>
    <span class="pma-fb ms-2 small"></span>
</p>
</section>

<!-- ===== Assessment overview + part tables ===== -->
<section class="pma-ajax-section mt-4">
<h2>Assessment overview</h2>
<table id="tbl-assessment" class="table table-bordered table-sm" style="width:700px">
    <thead><tr><th>Setting</th><th>Value</th></tr></thead>
    <tbody></tbody>
</table>

<div id="parts-container"></div>

<p class="mt-2">
    <button type="button" class="btn btn-success btn-sm pma-save"
        data-op="modify_assessment" data-src="tbl-assessment,parts-container">Save assessment &amp; parts</button>
    <span class="pma-fb ms-2 small"></span>
</p>
</section>

<!-- ===== Assessment criteria ===== -->
<section class="pma-ajax-section mt-4">
<h2>Assessment criteria visibility to staff</h2>
<p class="text-muted">Controls whether assessment criteria descriptors are shown to staff on the marking page.</p>
<table id="tbl-ac" class="table table-bordered table-sm" style="width:700px">
    <thead><tr><th>Which element</th><th>Visible&nbsp;= checked</th></tr></thead>
    <tbody></tbody>
</table>
<p class="mt-2">
    <button type="button" class="btn btn-success btn-sm pma-save"
        data-op="modify_ac" data-src="tbl-ac">Save criteria visibility</button>
    <span class="pma-fb ms-2 small"></span>
</p>
</section>

<!-- ===== Programme variants + extra info options (saved together) ===== -->
<section class="pma-ajax-section mt-4">
<h2>Programme variants</h2>
<p class="text-muted">Use short names, e.g. Arch, Aero, Civil &amp; Struct, Chem Eng. Often you won't need any.</p>
<table id="tbl-progs" class="table table-bordered table-sm" style="width:700px">
    <thead><tr><th>Option</th><th>Short name</th></tr></thead>
    <tbody></tbody>
</table>

<h2 class="mt-4">Extra information to capture</h2>
<p class="text-muted">May be used by the module manager to organise resources and/or be visible to students.</p>
<table id="tbl-options" class="table table-bordered table-sm" style="width:700px">
    <thead><tr><th>Field</th><th>Visible</th><th>Mandatory for staff</th><th>Students see it</th></tr></thead>
    <tbody></tbody>
</table>
<p class="mt-2">
    <button type="button" class="btn btn-success btn-sm pma-save"
        data-op="modify_options" data-src="tbl-progs,tbl-options">Save programmes &amp; options</button>
    <span class="pma-fb ms-2 small"></span>
</p>
</section>

<!-- ===== Selection settings ===== -->
<section class="pma-ajax-section mt-4">
<h2>Project / area selection settings</h2>
<p class="text-muted">Determines how students select projects.</p>
<table id="tbl-selection" class="table table-bordered table-sm" style="width:700px">
    <thead><tr><th>Setting</th><th>Value</th></tr></thead>
    <tbody></tbody>
</table>
<p class="mt-2">
    <button type="button" class="btn btn-success btn-sm pma-save"
        data-op="modify_selection" data-src="tbl-selection">Save selection settings</button>
    <span class="pma-fb ms-2 small"></span>
</p>
</section>

<?php else: ?>
<p>You do not have the appropriate admin level. <a href="../index.php">Go back here</a></p>
<?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($is_module_admin || $admin_access): ?>
<script>
const PMA_DATA       = <?php echo json_encode($page_data ?: new stdClass(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
const PMA_ACTION_URL = <?php echo json_encode($self_url); ?>;
</script>
<script>
$(function () {
    const d = PMA_DATA;

    /* ---- shared DataTables config: plain, no controls ---- */
    const dtCfg = {
        paging:    false,
        searching: false,
        ordering:  false,
        info:      false,
        dom:       't',
        autoWidth: false,
    };

    /* ---- helpers ---- */
    function esc(s) {
        return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function txtInput(name, val, size, maxlen) {
        return `<input type="text" class="form-control form-control-sm d-inline-block" style="width:auto" name="${name}" value="${esc(val)}" size="${size}" maxlength="${maxlen}">`;
    }
    function numInput(name, val, min, max) {
        return `<input type="number" class="form-control form-control-sm d-inline-block" style="width:80px" name="${name}" value="${val}" min="${min}" max="${max}">`;
    }
    function chk(name, checked) {
        return `<input type="checkbox" name="${name}" value="1"${checked ? ' checked' : ''}>`;
    }
    function dateInput(name, val) {
        return `<input type="text" class="form-control form-control-sm d-inline-block" style="width:130px" name="${name}" value="${esc(val)}" maxlength="15">`;
    }

    /* ---- module info (inside form, full-page POST) ---- */
    $('#tbl-module-info').DataTable(Object.assign({}, dtCfg, {
        data: [
            ['Module ID',        String(d.pm_id)],
            ['Year begin',       String(d.year_begin)],
            ['Year end',         String(d.year_end)],
            ['Module code',      txtInput('module_prefix', d.module_prefix, 5, 5) + ' ' +
                                 txtInput('module1_code',  d.module1,        4, 4) + ' ' +
                                 txtInput('module_postfix', d.module_postfix, 1, 1)],
            ['Module short name (fixed)', esc(d.short_code)],
            ['Short description',         txtInput('module_short_desc', d.module_short_desc, 30, 25) +
                                          ' <small class="text-muted">up to 25 chars</small>'],
        ],
        columns: [{ width: '260px' }, {}],
    }));

    /* ---- key dates ---- */
    $('#tbl-dates').DataTable(Object.assign({}, dtCfg, {
        data: [
            ['Project proposal close for staff',
                dateInput('staff_close_date', d.staff_close) + ' <small class="text-muted">at 23:59</small>'],
            ['Student selection opens',
                dateInput('student_selection_open_date', d.sel_open) + ' <small class="text-muted">at 00:00</small>'],
            ['Student selection closes',
                dateInput('student_selection_close_date', d.sel_close) + ' <small class="text-muted">at 23:59</small>'],
            ['Project to be allocated by',
                dateInput('allocation_date', d.alloc_date) + ' <small class="text-muted">at 00:00</small>'],
        ],
        columns: [{ width: '260px' }, {}],
    }));

    /* ---- assessment overview ---- */
    $('#tbl-assessment').DataTable(Object.assign({}, dtCfg, {
        data: [
            ['Number of assessment parts',           numInput('num_parts',           d.num_parts,           1, 5)],
            ['Module leader',                         d.staff_select_html],
            ['Acceptable variation between markers',  numInput('accept_var',          d.accept_var,          0, 100) + ' %'],
            ['Pass mark',                             numInput('pass_mark',           d.pass_mark,           0, 100) + ' %'],
            ['Minimum number of feedback words',      numInput('min_feedback_words',  d.min_feedback_words,  0, 5000)],
        ],
        columns: [{ width: '260px' }, {}],
    }));

    /* ---- per-part tables (all 5 created; shown/hidden by num_parts input) ---- */
    function updatePartVisibility(n) {
        for (let p = 1; p <= 5; p++) {
            const sec = document.getElementById(`part-section-${p}`);
            if (sec) sec.style.display = (p <= n) ? '' : 'none';
        }
    }

    const partsEl = document.getElementById('parts-container');
    for (let p = 1; p <= 5; p++) {
        const pt = d.parts[p];

        const sec = document.createElement('div');
        sec.id = `part-section-${p}`;
        partsEl.appendChild(sec);

        const h3 = document.createElement('h3');
        h3.className = 'mt-4';
        h3.textContent = `Assessment part ${p}`;
        sec.appendChild(h3);

        const tbl = document.createElement('table');
        tbl.id = `tbl-part-${p}`;
        tbl.className = 'table table-bordered table-sm';
        tbl.style.width = '700px';
        tbl.innerHTML = '<thead><tr><th>Setting</th><th>Value</th></tr></thead><tbody></tbody>';
        sec.appendChild(tbl);

        $(`#tbl-part-${p}`).DataTable(Object.assign({}, dtCfg, {
            data: [
                [`Description of part ${p}`,
                    txtInput(`p${p}_desc`, pt.desc, 30, 25)],
                [`Number of markers for part ${p}`,
                    numInput(`p${p}_num_markers`, pt.num_markers, 1, 3)],
                [`% weight of part ${p}`,
                    numInput(`p${p}_percent`, pt.percent, 0, 100)],
                [`Maximum mark for part ${p}`,
                    numInput(`p${p}_max`, pt.max, 1, 100)],
                [`Response format for part ${p} <small class="text-muted d-block">Checked = numeric; unchecked = phrases. Must match the questions setup.</small>`,
                    chk(`p${p}_numeric`, pt.numeric === 1)],
                [`Collect overall feedback for part ${p} <small class="text-muted d-block">Checked = show <em>overall</em> feedback text box on the marking page.</small>`,
                    chk(`p${p}_feedback`, pt.feedback === 1)],
                [`Submission date for part ${p}`,
                    dateInput(`p${p}_submission_date`, pt.submit_date)],
                [`Mark-by date for part ${p}`,
                    dateInput(`p${p}_marking_date`, pt.marking_date)],
            ],
            columns: [{ width: '320px' }, {}],
        }));
    }

    // Set initial visibility and react to changes
    updatePartVisibility(d.num_parts);
    $(document).on('input change', 'input[name="num_parts"]', function () {
        const n = Math.max(1, Math.min(5, parseInt(this.value, 10) || 1));
        updatePartVisibility(n);
    });

    /* ---- assessment criteria ---- */
    $('#tbl-ac').DataTable(Object.assign({}, dtCfg, {
        data: [
            ['Show any checked below <strong>(must be checked to show anything)</strong>',
                chk('ac_visible',    d.ac.visible    > 0)],
            ['Show the University text descriptor',      chk('ac_university', d.ac.university > 0)],
            ['Show the School specific text descriptor', chk('ac_school',     d.ac.school     > 0)],
            ['Show the Class (1st, 2.ii etc.)',          chk('ac_class',      d.ac.class      > 0)],
            ['Show the Grade (A+, B&minus; etc.)',       chk('ac_grade',      d.ac.grade      > 0)],
            ['Show the GPA (max = 4.25)',                chk('ac_gpa',        d.ac.gpa        > 0)],
        ],
        columns: [{ width: '360px' }, {}],
    }));

    /* ---- programme variants ---- */
    const progRows = [];
    for (let i = 1; i <= 7; i++) {
        progRows.push([`Option ${i}`, txtInput(`p_list${i}`, d.prog_list[i - 1] || '', 30, 30)]);
    }
    $('#tbl-progs').DataTable(Object.assign({}, dtCfg, {
        data:    progRows,
        columns: [{ width: '100px' }, {}],
    }));

    /* ---- extra info options ---- */
    const optRows = d.options.map(o => [
        esc(o.label),
        `<div class="text-center">${chk(o.name,  o.checked > 0)}</div>`,
        `<div class="text-center">${chk(o.mname, o.mand    > 0)}</div>`,
        `<div class="text-center">${chk(o.vname, o.vis     > 0)}</div>`,
    ]);
    $('#tbl-options').DataTable(Object.assign({}, dtCfg, {
        data:    optRows,
        columns: [{}, { width: '70px' }, { width: '140px' }, { width: '130px' }],
    }));

    /* ---- selection settings ---- */
    $('#tbl-selection').DataTable(Object.assign({}, dtCfg, {
        data: [
            ['Use <strong>Project Selection</strong>?',
                chk('use_project_selection', d.use_project_selection > 0)],
            ['Use project <strong>Area</strong> selection? <small class="text-muted">(not yet functional)</small>',
                chk('use_project_area', d.use_project_area > 0)],
            ['<em>Settings for Project Selection:</em>', ''],
            ['Ranked choice of project?',
                chk('ranked_choice', d.ranked_choice > 0)],
            ['Number of choices of project',
                numInput('choice_number', d.choice_number, 1, 5)],
        ],
        columns: [{ width: '360px' }, {}],
    }));

    /* ---- generic AJAX save handler ---- */
    $('.pma-save').on('click', function () {
        const btn       = $(this);
        const op        = btn.data('op');
        const sourceIds = String(btn.data('src')).split(',');
        const fbEl      = btn.siblings('.pma-fb');
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

        const data = { op, pm_id: d.pm_id, csrf_token: csrfToken };

        sourceIds.forEach(id => {
            const el = document.getElementById(id.trim());
            if (!el) return;
            // Pre-zero every checkbox so unchecked ones are explicitly 0
            el.querySelectorAll('input[type="checkbox"][name]').forEach(cb => {
                data[cb.name] = 0;
            });
            // Collect all named form controls
            el.querySelectorAll('input[name], select[name], textarea[name]').forEach(inp => {
                data[inp.name] = (inp.type === 'checkbox') ? (inp.checked ? 1 : 0) : inp.value;
            });
        });

        const origText = btn.text();
        btn.prop('disabled', true).text('Saving…');
        fbEl.text('').removeClass('text-success text-danger');

        $.ajax({
            type:     'POST',
            url:      PMA_ACTION_URL,
            data:     data,
            dataType: 'json',
            success(r) {
                if (r.success) {
                    fbEl.addClass('text-success').text('Saved ✓');
                    setTimeout(() => fbEl.text(''), 4000);
                } else {
                    fbEl.addClass('text-danger').text(r.message || 'Error saving.');
                }
            },
            error(xhr) {
                fbEl.addClass('text-danger').text('Server error (' + xhr.status + ')');
            },
            complete() {
                btn.prop('disabled', false).text(origText);
            },
        });
    });
});
</script>
<?php endif; ?>
</body>
</html>
