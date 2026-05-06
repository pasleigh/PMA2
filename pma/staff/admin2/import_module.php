<?php
(@include_once('./set_dir_admin.php')) or die('Cannot find set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find config.php<BR>');
(@include_once("$STAFF_DIR_VAL/setup_titles.php")) or die('Cannot find setup_titles.php<BR>');
(@include_once('./ADMINconnect_pdo.php')) or die('Cannot connect to database<BR>');
(@include_once("$STAFF_DIR_VAL/csrf_functions.php")) or die('Cannot find csrf_functions.php<BR>');

$projects_setup = 'projects_setup';
$projects_setup_proposals = 'projects_setup_proposals';

/**
 * Convert a name template like 'master_name_{sc}_{yb}' into the real table name
 * for the given short code and year values.
 */
function pma_resolve_table_name(string $template, string $sc_lc, int $yb, int $ye): string
{
    return str_replace(['{sc}', '{yb}', '{ye}'], [$sc_lc, (string)$yb, (string)$ye], $template);
}

/**
 * Return true only if a table name contains only safe characters.
 * Prevents a malicious export file from injecting SQL via a crafted name_template.
 */
function pma_is_safe_table_name(string $name): bool
{
    return (bool)preg_match('/^[a-zA-Z0-9_]{1,64}$/', $name);
}

$connect_msg_block = "<div class='text-center'><h1 style='font-size: x-large; color: darkgoldenrod;'>" . htmlspecialchars($connect_msg ?? '') . "</h1></div>\n";
$display_block = $connect_msg_block;

// -----------------------------------------------------------------------
// Handle the import POST
// -----------------------------------------------------------------------
if (isset($_POST['op']) && $_POST['op'] === 'import') {
    require_valid_csrf();

    $new_short_code = strtoupper(trim($_POST['new_short_code'] ?? ''));
    $new_year_begin = (int)($_POST['new_year_begin'] ?? 0);
    $new_year_end   = $new_year_begin + 1;
    $new_sc_lc      = strtolower($new_short_code);

    // --- Input validation ---
    $errors = [];
    //if (strlen($new_short_code) < 2 || strlen($new_short_code) > 5 || !ctype_alpha($new_short_code)) {
    //    $errors[] = 'Short code must be 2–5 letters (A–Z only).';
    //}
    if (!preg_match('/^[A-Z]{2,5}\d{0,3}$/', $new_short_code) || strlen($new_short_code) > 5) {
        $errors[] = 'Short code must be 2–5 characters, letters first, numbers only at the end.';
    }
    if ($new_year_begin < 2000 || $new_year_begin > 2100) {
        $errors[] = 'Year begin must be a valid year between 2000 and 2100.';
    }
    $upload_ok = isset($_FILES['module_zip']) && $_FILES['module_zip']['error'] === UPLOAD_ERR_OK;
    if (!$upload_ok) {
        $upload_error_codes = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds the server upload_max_filesize limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds the form MAX_FILE_SIZE limit.',
            UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write the file to disk.',
            UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
        ];
        $err_code = $_FILES['module_zip']['error'] ?? UPLOAD_ERR_NO_FILE;
        $errors[] = $upload_error_codes[$err_code] ?? 'Unknown upload error.';
    }

    if (!empty($errors)) {
        $display_block .= "<div class='alert alert-danger'><strong>Please fix the following:</strong><ul>";
        foreach ($errors as $e) {
            $display_block .= '<li>' . htmlspecialchars($e) . '</li>';
        }
        $display_block .= '</ul></div>';
    } else {
        // --- Check uniqueness ---
        $stmt = $db->prepare("SELECT COUNT(*) FROM $projects_setup WHERE short_code = ? AND year_begin = ?");
        $stmt->execute([$new_short_code, $new_year_begin]);
        if ((int)$stmt->fetchColumn() > 0) {
            $display_block .= "<div class='alert alert-danger'>A module with short code <strong>"
                . htmlspecialchars($new_short_code) . "</strong> and year <strong>$new_year_begin</strong>"
                . " already exists. Choose a different combination.</div>";
        } else {
            // --- Extract and parse the ZIP ---
            $zip = new ZipArchive();
            if ($zip->open($_FILES['module_zip']['tmp_name']) !== true) {
                $display_block .= "<div class='alert alert-danger'>Cannot open the uploaded file as a ZIP archive. "
                    . "Make sure you are uploading a valid PMA module export ZIP.</div>";
            } else {
                $json_content = $zip->getFromName('module_data.json');
                $zip->close();

                if ($json_content === false) {
                    $display_block .= "<div class='alert alert-danger'>The ZIP does not contain "
                        . "<code>module_data.json</code>. Is this a valid PMA module export?</div>";
                } else {
                    $export_data = json_decode($json_content, true);
                    if (!is_array($export_data)) {
                        $display_block .= "<div class='alert alert-danger'>Cannot parse <code>module_data.json</code>. "
                            . "The file may be corrupt.</div>";
                    } else {
                        // ---- Perform the import ----
                        $log = '';
                        $new_pm_id = 0;
                        $fatal = false;

                        // 1. Insert projects_setup row
                        $setup = $export_data['module_setup'] ?? [];
                        // Override identity fields with the user-supplied values
                        $setup['short_code'] = $new_short_code;
                        $setup['year_begin'] = $new_year_begin;
                        $setup['year_end']   = $new_year_end;
                        // Strip id if it somehow sneaked in
                        unset($setup['id']);

                        $cols_list   = implode(', ', array_map(fn($c) => "`$c`", array_keys($setup)));
                        $ph_list     = implode(', ', array_fill(0, count($setup), '?'));
                        try {
                            $stmt = $db->prepare("INSERT INTO $projects_setup ($cols_list) VALUES ($ph_list)");
                            $stmt->execute(array_values($setup));
                            $log .= "projects_setup row inserted\n";
                        } catch (PDOException $ex) {
                            pma_log_db_error($ex, 'import_module:projects_setup');
                            $log .= "FAILED inserting projects_setup row: " . htmlspecialchars($ex->getMessage()) . "\n";
                            $fatal = true;
                        }

                        if (!$fatal) {
                            // Retrieve the new pm_id
                            $stmt = $db->prepare("SELECT id FROM $projects_setup WHERE short_code = ? AND year_begin = ?");
                            $stmt->execute([$new_short_code, $new_year_begin]);
                            $new_pm_id = (int)$stmt->fetchColumn();
                            $log .= "New module ID: $new_pm_id\n";

                            // 2. Insert proposals row
                            $proposals = $export_data['module_proposals'] ?? [];
                            unset($proposals['proj_id']);
                            $proposals['proj_id'] = $new_pm_id;

                            if (!empty($proposals)) {
                                $cols_list = implode(', ', array_map(fn($c) => "`$c`", array_keys($proposals)));
                                $ph_list   = implode(', ', array_fill(0, count($proposals), '?'));
                                try {
                                    $stmt = $db->prepare("INSERT INTO $projects_setup_proposals ($cols_list) VALUES ($ph_list)");
                                    $stmt->execute(array_values($proposals));
                                    $log .= "projects_setup_proposals row inserted\n";
                                } catch (PDOException $ex) {
                                    pma_log_db_error($ex, 'import_module:proposals');
                                    $log .= "WARNING: proposals insert failed (non-fatal) — "
                                        . htmlspecialchars($ex->getMessage()) . "\n";
                                }
                            } else {
                                // Create a minimal proposals row so the rest of the app doesn't break
                                try {
                                    $stmt = $db->prepare("INSERT INTO $projects_setup_proposals SET proj_id = ?");
                                    $stmt->execute([$new_pm_id]);
                                    $log .= "projects_setup_proposals default row inserted\n";
                                } catch (PDOException $ex) {
                                    $log .= "WARNING: default proposals insert failed\n";
                                }
                            }

                            // 3. Create module tables and insert data
                            foreach ($export_data['tables'] ?? [] as $table_entry) {
                                $template  = $table_entry['name_template'] ?? '';
                                $new_table = pma_resolve_table_name($template, $new_sc_lc, $new_year_begin, $new_year_end);

                                // Reject any table name that isn't safe alphanumeric+underscore
                                if (!pma_is_safe_table_name($new_table)) {
                                    $log .= "SKIPPED unsafe table name: " . htmlspecialchars($new_table) . "\n";
                                    continue;
                                }

                                // Check the table doesn't already exist
                                $exists = $db->query("SHOW TABLES LIKE " . $db->quote($new_table))->rowCount() > 0;
                                if ($exists) {
                                    $log .= "$new_table ... already exists, skipped\n";
                                    continue;
                                }

                                // Substitute the placeholder with the real new table name
                                $create_sql = str_replace('`__PLACEHOLDER__`', "`$new_table`", $table_entry['create_sql'] ?? '');
                                // Strip any AUTO_INCREMENT counter so it starts fresh
                                $create_sql = preg_replace('/AUTO_INCREMENT=\d+\s*/i', '', $create_sql);

                                try {
                                    $db->exec($create_sql);
                                    $log .= "$new_table ... created\n";
                                } catch (PDOException $ex) {
                                    $log .= "$new_table ... FAILED to create: " . htmlspecialchars($ex->getMessage()) . "\n";
                                    continue;
                                }

                                // Insert rows for tables that carry data
                                if (!empty($table_entry['copy_data']) && !empty($table_entry['rows'])) {
                                    $rows      = $table_entry['rows'];
                                    $first_row = reset($rows);
                                    $cols_list = implode(', ', array_map(fn($c) => "`$c`", array_keys($first_row)));
                                    $ph_list   = implode(', ', array_fill(0, count($first_row), '?'));
                                    $ins_stmt  = $db->prepare("INSERT INTO `$new_table` ($cols_list) VALUES ($ph_list)");
                                    $count     = 0;
                                    foreach ($rows as $row) {
                                        try {
                                            $ins_stmt->execute(array_values($row));
                                            $count++;
                                        } catch (PDOException $ex) {
                                            $log .= "  row insert failed: " . htmlspecialchars($ex->getMessage()) . "\n";
                                        }
                                    }
                                    $log .= "  $count row(s) inserted\n";
                                }
                            }
                        }

                        // Show results
                        if ($fatal) {
                            $display_block .= "<div class='alert alert-danger'><strong>Import failed.</strong> "
                                . "See the log below. No module entry was created.</div>";
                        } else {
                            $display_block .= "<div class='alert alert-success'><strong>Import complete.</strong> "
                                . "<a href='./edit_module_settings.php?pm_id=$new_pm_id'>"
                                . "Go to the new module's admin page to review and adjust settings &rarr;</a></div>";
                        }
                        $display_block .= "<pre style='background:#f8f9fa;padding:10px;font-size:0.85em;border:1px solid #dee2e6;border-radius:4px;'>"
                            . $log . "</pre>";
                    }
                }
            }
        }
    }
}

// -----------------------------------------------------------------------
// Show the upload form (always shown, even after a successful import)
// -----------------------------------------------------------------------
$self = htmlspecialchars($_SERVER['PHP_SELF']);

$display_block .= "<hr class='my-4'>";
$display_block .= "<h1>Import module from export file</h1>";
$display_block .= "<p>Upload a <code>.zip</code> file created by the "
    . "<a href='./export_module.php'>Export Module</a> page on any PMA instance.</p>";
$display_block .= "<p>The import will recreate:</p><ul>
  <li>Module settings (you choose the new short code and year — everything else is copied from the export)</li>
  <li>Project list and details</li>
  <li>Assessment question templates</li>
  <li>Programme definitions and project areas</li>
</ul>";
$display_block .= "<p class='text-muted'><strong>Not imported:</strong> student records, existing marks, and feedback.</p>";

$display_block .= "<form method='post' action='$self' enctype='multipart/form-data'>\n";
$display_block .= "<div class='mb-3'>"
    . "<label class='form-label'><strong>Module export ZIP file</strong></label>"
    . "<input type='file' class='form-control' name='module_zip' accept='.zip' required></div>";

$display_block .= "<div class='row mb-3'>";
$display_block .= "<div class='col-sm-3 mb-2'>"
    . "<label class='form-label'><strong>New short code</strong><br><small class='text-muted'>2–5 letters, unique for the year</small></label>"
    . "<input type='text' class='form-control' name='new_short_code' maxlength='5' placeholder='e.g. MENG' required></div>";
$display_block .= "<div class='col-sm-3 mb-2'>"
    . "<label class='form-label'><strong>New year begin</strong><br><small class='text-muted'>e.g. 2025 for 2025/26</small></label>"
    . "<select class='form-select' name='new_year_begin'>";
for ($y = 2025; $y <= 2040; $y++) {
    $display_block .= "<option value='$y'>$y</option>";
}
$display_block .= "</select></div></div>";

$display_block .= "<input type='hidden' name='op' value='import'>";
$display_block .= "<p><input class='btn btn-primary' type='submit' value='Import module from ZIP'></p>";
$display_block .= "</form>";

?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Import Module from Another Instance</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Main admin page</a></li>
            <li class="nav-item"><a target="_self" href="./export_module.php" class="nav-link px-2 text-white-50">Export a module</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card mb-2">
                    <div class="card-header"><strong>Import module</strong></div>
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
