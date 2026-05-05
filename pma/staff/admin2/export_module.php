<?php
// Buffer all output so the ZIP download can send headers cleanly even if includes produce notices
ob_start();

(@include_once('./set_dir_admin.php')) or die('Cannot find set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find config.php<BR>');
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) or die('Cannot find setup_titles.php<BR>');
(@include_once('./ADMINconnect_pdo.php')) or die('Cannot connect to database<BR>');
(@include_once("$STAFF_DIR_VAL/csrf_functions.php")) or die('Cannot find csrf_functions.php<BR>');

$projects_setup = 'projects_setup';
$projects_setup_proposals = 'projects_setup_proposals';

/**
 * Fetch one table's CREATE statement and (optionally) all its rows.
 * Returns null if the table does not exist.
 */
function pma_export_table(PDO $db, string $table_name, string $name_template, bool $copy_data): ?array
{
    $check = $db->query("SHOW TABLES LIKE " . $db->quote($table_name));
    if ($check->rowCount() === 0) {
        return null;
    }
    $row = $db->query("SHOW CREATE TABLE `$table_name`")->fetch(PDO::FETCH_NUM);
    // Replace the real table name with a placeholder so the import can substitute the new name
    $create_sql = str_replace("`$table_name`", '`__PLACEHOLDER__`', $row[1]);

    $rows = [];
    if ($copy_data) {
        $rows = $db->query("SELECT * FROM `$table_name`")->fetchAll(PDO::FETCH_ASSOC);
    }
    return [
        'name_template' => $name_template,
        'original_name' => $table_name,
        'create_sql'    => $create_sql,
        'copy_data'     => $copy_data,
        'rows'          => $rows,
    ];
}

// -----------------------------------------------------------------------
// Handle the export POST before any HTML is output
// -----------------------------------------------------------------------
if (isset($_POST['op']) && $_POST['op'] === 'export') {
    require_valid_csrf();

    $pm_id = (int)($_POST['pm_id_export'] ?? 0);
    if ($pm_id <= 0) {
        ob_end_clean();
        die('Invalid module selection.');
    }

    $stmt = $db->prepare("SELECT * FROM $projects_setup WHERE id = ?");
    $stmt->execute([$pm_id]);
    $setup_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$setup_row) {
        ob_end_clean();
        die('Module not found.');
    }

    $sc    = $setup_row['short_code'];
    $yb    = (int)$setup_row['year_begin'];
    $ye    = $yb + 1;
    $sc_lc = strtolower($sc);

    // Strip the auto-increment id — the destination will assign a new one
    $setup_export = $setup_row;
    unset($setup_export['id']);

    // Proposals row (strip the foreign key; import will relink to new pm_id)
    $stmt = $db->prepare("SELECT * FROM $projects_setup_proposals WHERE proj_id = ?");
    $stmt->execute([$pm_id]);
    $proposals_row    = $stmt->fetch(PDO::FETCH_ASSOC);
    $proposals_export = $proposals_row ?: [];
    unset($proposals_export['proj_id']);

    // --- Collect all module tables ---
    $tables = [];

    // Project proposal tables (with data — these define the projects staff can offer)
    foreach ([
        ["master_name_{$sc_lc}_{$yb}",    'master_name_{sc}_{yb}',    true],
        ["project_details_{$sc_lc}_{$yb}", 'project_details_{sc}_{yb}', true],
    ] as [$tbl, $tpl, $data]) {
        if ($t = pma_export_table($db, $tbl, $tpl, $data)) {
            $tables[] = $t;
        }
    }

    // Assessment mark-store tables (schema only — no marks to copy)
    for ($p = 1; $p <= 5; $p++) {
        for ($m = 1; $m <= 3; $m++) {
            $t = pma_export_table($db, "assess_{$sc_lc}_{$ye}_p{$p}_m{$m}", "assess_{sc}_{ye}_p{$p}_m{$m}", false);
            if ($t) {
                $tables[] = $t;
            }
        }
    }

    // Other schema-only tables
    foreach ([
        ["assess_total_{$sc_lc}_{$ye}",        'assess_total_{sc}_{ye}',    false],
        ["allocated_{$sc_lc}_{$yb}",            'allocated_{sc}_{yb}',       false],
        ["allocated_{$sc_lc}_{$yb}_staff",      'allocated_{sc}_{yb}_staff', false],
        ["students_{$sc_lc}_{$yb}",             'students_{sc}_{yb}',        false],
        ["select_{$sc_lc}_{$yb}",               'select_{sc}_{yb}',          false],
    ] as [$tbl, $tpl, $data]) {
        if ($t = pma_export_table($db, $tbl, $tpl, $data)) {
            $tables[] = $t;
        }
    }

    // Tables with data
    if ($t = pma_export_table($db, "students_programmes_{$sc_lc}_{$yb}", 'students_programmes_{sc}_{yb}', true)) {
        $tables[] = $t;
    }

    // Assessment questions and categories (with data — these define the marking rubric)
    for ($p = 1; $p <= 5; $p++) {
        foreach ([
            ["assessqs_{$sc_lc}_{$ye}_p{$p}",      "assessqs_{sc}_{ye}_p{$p}",      true],
            ["assessqs_cats_{$sc_lc}_{$ye}_p{$p}", "assessqs_cats_{sc}_{ye}_p{$p}", true],
        ] as [$tbl, $tpl, $data]) {
            if ($t = pma_export_table($db, $tbl, $tpl, $data)) {
                $tables[] = $t;
            }
        }
    }

    if ($t = pma_export_table($db, "projects_areas_{$sc_lc}_{$yb}", 'projects_areas_{sc}_{yb}', true)) {
        $tables[] = $t;
    }

    // Build the export payload
    $export_data = [
        'export_version'      => 1,
        'export_date'         => date('Y-m-d H:i:s'),
        'original_short_code' => $sc,
        'original_year_begin' => $yb,
        'original_year_end'   => $ye,
        'module_setup'        => $setup_export,
        'module_proposals'    => $proposals_export,
        'tables'              => $tables,
    ];

    $json = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    // Pack the JSON into a ZIP
    $safe_sc      = preg_replace('/[^a-zA-Z0-9_-]/', '_', $sc);
    $zip_filename = "pma_module_{$safe_sc}_{$yb}.zip";
    $temp_zip     = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $zip_filename;

    $zip = new ZipArchive();
    if ($zip->open($temp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        ob_end_clean();
        die('Cannot create ZIP file — check server temp directory permissions.');
    }
    $zip->addFromString('module_data.json', $json);
    $zip->close();

    // Discard any buffered output and send the file
    ob_end_clean();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($temp_zip));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    readfile($temp_zip);
    unlink($temp_zip);
    exit();
}

// -----------------------------------------------------------------------
// Build the selection form
// -----------------------------------------------------------------------
$connect_msg_block = "<div class='text-center'><h1 style='font-size: x-large; color: darkgoldenrod;'>" . htmlspecialchars($connect_msg ?? '') . "</h1></div>\n";

$stmt = $db->query("SELECT * FROM $projects_setup ORDER BY year_begin DESC, module");
$all_modules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$modules_list = '';
foreach ($all_modules as $rec) {
    $pid    = (int)$rec['id'];
    $mcode  = htmlspecialchars($rec['module_prefix'] . $rec['module'] . $rec['module_postfix']);
    $mdesc  = htmlspecialchars($rec['module_short_desc']);
    $msc    = htmlspecialchars($rec['short_code']);
    $myb    = (int)$rec['year_begin'];
    $mye    = $myb + 1;
    $modules_list .= "<option value='$pid'>[$pid] $mcode $mdesc ($msc) : $myb/$mye</option>\n";
}

$self = htmlspecialchars($_SERVER['PHP_SELF']);

$display_block = $connect_msg_block;
$display_block .= "<h1>Export module to another instance</h1>";
$display_block .= "<p>Select a module below. Clicking <strong>Download</strong> will generate a ZIP file containing:</p>";
$display_block .= "<ul>
  <li>All module settings (dates, weights, part descriptions, etc.)</li>
  <li>The project list (titles and details)</li>
  <li>Assessment question templates (categories and questions for each part)</li>
  <li>Programme definitions</li>
  <li>Project area definitions</li>
</ul>";
$display_block .= "<p class='text-muted'><strong>Not included:</strong> student records, marks, feedback, and staff assignments — those belong to the source instance only.</p>";
$display_block .= "<form method='post' action='$self'>\n";
$display_block .= "<div class='row mb-3'><div class='col-sm-8'>";
$display_block .= "<label for='pm_id_export' class='form-label'><strong>Module to export</strong></label>";
$display_block .= "<select class='form-select' id='pm_id_export' name='pm_id_export'>$modules_list</select>";
$display_block .= "</div></div>";
$display_block .= "<input type='hidden' name='op' value='export'>";
$display_block .= "<p><input class='btn btn-success' type='submit' value='Download module export ZIP'></p>";
$display_block .= "</form>";
$display_block .= "<hr><p><a href='./import_module.php' class='btn btn-outline-primary'>Go to Import page &rarr;</a></p>";

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Export Module to Another Instance</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Main admin page</a></li>
            <li class="nav-item"><a target="_self" href="./import_module.php" class="nav-link px-2 text-white-50">Import a module</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card mb-2">
                    <div class="card-header"><strong>Export module</strong></div>
                    <div class="card-body">
                        <?php echo $display_block; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
