<?php
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot find this file to include: markstore_functions.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('../show_feedback_form.php')) or die('Cannot get show_feedback_form.php<BR>');
(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

if (!isset($_REQUEST['pm_id'])) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = (int)$_REQUEST['pm_id'];
$assesspart = isset($_GET['part']) ? (int) $_GET['part'] : null;

// Tables for the destination (current module/part)
$dest_qs_table = $assessqs[$assesspart];
$dest_cats_table = $assessqs_cats[$assesspart];
$assesstype_text = $p_description[$assesspart];

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Given a projects_setup row, return the assessqs table name for a given part.
 */
function qs_table_for_row($row, $part) {
    $sc = strtolower($row['short_code']);
    $ye = (int) $row['year_end'];
    return "assessqs_{$sc}_{$ye}_p{$part}";
}
function cats_table_for_row($row, $part) {
    $sc = strtolower($row['short_code']);
    $ye = (int) $row['year_end'];
    return "assessqs_cats_{$sc}_{$ye}_p{$part}";
}

// ─────────────────────────────────────────────────────────────────────────────
// AJAX: get_projects  — returns JSON list of projects for a year
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_projects') {
    header('Content-Type: application/json');
    $year = (int) ($_GET['year'] ?? 0);
    $stmt = $db->prepare(
        'SELECT id, module_prefix, module, module_postfix, module_short_desc, short_code, year_begin, year_end, num_parts,
                use_p1, use_p2, use_p3, use_p4, use_p5,
                p1_desc, p2_desc, p3_desc, p4_desc, p5_desc
         FROM projects_setup WHERE year_begin = :y ORDER BY short_code, module'
    );
    $stmt->execute([':y' => $year]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $out = [];
    foreach ($rows as $row) {
        $out[] = [
            'id' => $row['id'],
            'label' => trim($row['module_prefix'] . $row['module'] . $row['module_postfix'])
                       . ' — ' . $row['module_short_desc']
                       . ' (' . $row['short_code'] . ')',
            'num_parts' => (int) $row['num_parts'],
            'parts' => array_map(function ($p) use ($row) {
                return [
                    'part' => $p,
                    'desc' => $row["p{$p}_desc"],
                    'use' => (int) $row["use_p{$p}"],
                ];
            }, range(1, 5)),
            'short_code' => $row['short_code'],
            'year_end' => $row['year_end'],
        ];
    }
    echo json_encode($out);
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// AJAX: get_preview  — returns HTML summary of categories + questions
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_preview') {
    header('Content-Type: application/json');
    $src_pm_id = (int) ($_GET['src_pm_id'] ?? 0);
    $src_part = (int) ($_GET['src_part'] ?? 0);

    if ($src_pm_id < 1 || $src_part < 1) {
        echo json_encode(['ok' => false, 'html' => 'Invalid selection.']);
        exit();
    }

    // Fetch source project row
    $stmt = $db->prepare('SELECT * FROM projects_setup WHERE id = :id');
    $stmt->execute([':id' => $src_pm_id]);
    $src_row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$src_row) {
        echo json_encode(['ok' => false, 'html' => 'Project not found.']);
        exit();
    }

    $src_cats = cats_table_for_row($src_row, $src_part);
    $src_qs = qs_table_for_row($src_row, $src_part);

    // Check tables exist
    $cats_exist = false;
    $qs_exist = false;
    try {
        $db->query("SELECT 1 FROM $src_cats LIMIT 1");
        $cats_exist = true;
    } catch (PDOException $e) {
    }
    try {
        $db->query("SELECT 1 FROM $src_qs   LIMIT 1");
        $qs_exist = true;
    } catch (PDOException $e) {
    }

    if (!$cats_exist || !$qs_exist) {
        echo json_encode(['ok' => false,
            'html' => "<div class='alert alert-warning'>No question data found for this selection
                       (<code>$src_cats</code> / <code>$src_qs</code>).</div>"]);
        exit();
    }

    // Build readonly summary
    $cats_rows = $db->query("SELECT * FROM $src_cats ORDER BY cat")->fetchAll(PDO::FETCH_ASSOC);

    $html = "<div class='table-responsive'>";
    foreach ($cats_rows as $cat_row) {
        $cat = (int) $cat_row['cat'];
        $html .= "<div class='card mb-2'>";
        $html .= "<div class='card-header py-1'><strong>Section $cat: "
               . htmlspecialchars($cat_row['description'], ENT_QUOTES, 'UTF-8')
               . '</strong> &nbsp; '
               . htmlspecialchars($cat_row['weight_percent'] ?? '', ENT_QUOTES, 'UTF-8') . '% &nbsp; '
               . 'factor: ' . htmlspecialchars($cat_row['factor'] ?? '', ENT_QUOTES, 'UTF-8')
               . '</div>';
        $html .= "<div class='card-body py-1'>";

        // Questions for this cat (exclude feedback placeholder)
        $stmt = $db->prepare(
            "SELECT * FROM $src_qs WHERE cat = :cat AND (entries > 0 OR question != 'Feedback Comments:') ORDER BY qid"
        );
        $stmt->execute([':cat' => $cat]);
        $q_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($q_rows) {
            $html .= "<table class='table table-sm table-bordered mb-0'>";
            $html .= "<thead class='table-light'><tr><th>#</th><th>Question</th><th>Responses</th></tr></thead><tbody>";
            foreach ($q_rows as $qr) {
                $qid = (int) $qr['qid'];
                $entries = (int) $qr['entries'];
                $resps = [];
                for ($r = 1; $r <= $entries; $r++) {
                    $t = htmlspecialchars($qr["text$r"] ?? '', ENT_QUOTES, 'UTF-8');
                    $m = htmlspecialchars($qr["mark$r"] ?? '', ENT_QUOTES, 'UTF-8');
                    if ($t !== '') {
                        $resps[] = "$t ($m)";
                    }
                }
                $html .= '<tr>';
                $html .= "<td>$qid</td>";
                $html .= '<td>' . htmlspecialchars($qr['question'], ENT_QUOTES, 'UTF-8') . '</td>';
                $html .= '<td><small>' . implode('<br>', $resps) . '</small></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table>';
        } else {
            $html .= "<em class='text-muted'>No questions found.</em>";
        }

        $html .= '</div></div>';
    }
    $html .= '</div>';

    echo json_encode([
        'ok' => true,
        'html' => $html,
        'src_cats' => $src_cats,
        'src_qs' => $src_qs,
        'src_label' => trim($src_row['module_prefix'] . $src_row['module'] . $src_row['module_postfix'])
                       . ' ' . $src_row['module_short_desc']
                       . ' (' . $src_row['short_code'] . ') '
                       . $src_row['year_begin'] . '/' . $src_row['year_end']
                       . ' part ' . $src_part,
    ]);
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// POST: do_copy  — performs the actual table copy after confirmation
// ─────────────────────────────────────────────────────────────────────────────
$copy_done = false;
$copy_msg = '';
if (isset($_POST['op']) && $_POST['op'] === 'do_copy') {
    $confirmed = isset($_POST['confirm_copy']) && $_POST['confirm_copy'] === '1';
    $src_qs_post = $_POST['src_qs'] ?? '';
    $src_cats_post = $_POST['src_cats'] ?? '';

    // Validate: table names must match the expected pattern and exist
    $valid_pattern = '/^assessqs(_cats)?_[a-z0-9]+_\d{4}_p[1-5]$/';

    if (!$confirmed) {
        $copy_msg = "<div class='alert alert-warning'>Copy cancelled — confirmation box was not ticked.</div>";
    } elseif (!preg_match($valid_pattern, $src_qs_post) || !preg_match($valid_pattern, $src_cats_post)) {
        $copy_msg = "<div class='alert alert-danger'>Invalid source table names. No changes were made.</div>";
    } else {
        try {
            $db->query("SELECT 1 FROM $src_cats_post LIMIT 1");
            $db->query("SELECT 1 FROM $src_qs_post   LIMIT 1");

            // Truncate destination and copy from source
            $db->query("TRUNCATE $dest_cats_table");
            $db->query("INSERT INTO $dest_cats_table SELECT * FROM $src_cats_post");
            $db->query("TRUNCATE $dest_qs_table");
            $db->query("INSERT INTO $dest_qs_table SELECT * FROM $src_qs_post");

            $copy_msg = "<div class='alert alert-success'><strong>Done.</strong> "
                      . "Questions copied from <code>$src_qs_post</code> / <code>$src_cats_post</code> "
                      . "into <code>$dest_qs_table</code> / <code>$dest_cats_table</code>.</div>"
                      . "<p><a href='./edit_module_settings.php?pm_id=$pm_id' class='btn btn-primary'>Go to module admin page</a></p>";
            $copy_done = true;
        } catch (PDOException $ex) {
            error_log($ex->getMessage());
            $copy_msg = "<div class='alert alert-danger'>Database error: "
                      . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// PAGE BUILD
// ─────────────────────────────────────────────────────────────────────────────
$display_block = $connect_msg;

$admin_access = false;
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if (!$is_module_admin && !$admin_access) {
    $display_block .= 'You do not have the appropriate admin level<BR>';
    $display_block .= "<a href='../index.php'>Go back here</a><BR>";
} else {
    $display_block .= "
<div class='alert alert-danger'>
  <strong>Warning:</strong> This page will <strong>permanently overwrite</strong> the existing questions
  for <em>$assesstype_text</em> of this module. This cannot be undone.
</div>";

    $display_block .= "
<div class='card mb-3'>
  <div class='card-header'><strong>Destination (will be overwritten)</strong></div>
  <div class='card-body'>
    <p><strong>Module:</strong> $module_short_description $module_prefix$module1$module_postfix ($short_code) $year_begin/$year_end</p>
    <p><strong>Assessment part:</strong> $assesstype_text (Part $assesspart)</p>
    <p class='mb-0 text-muted small'>
      Categories table: <code>$dest_cats_table</code><br>
      Questions table: <code>$dest_qs_table</code>
    </p>
  </div>
</div>";

    if ($copy_msg) {
        $display_block .= $copy_msg;
    }

    if (!$copy_done) {
        // Distinct years available
        $years = $db->query('SELECT DISTINCT year_begin FROM projects_setup ORDER BY year_begin DESC')
                    ->fetchAll(PDO::FETCH_COLUMN);

        $display_block .= "
<div class='card mb-3'>
  <div class='card-header'><strong>Step 1 — Choose the source</strong></div>
  <div class='card-body'>

    <div class='row g-3 align-items-end mb-3'>
      <div class='col-auto'>
        <label class='form-label'><strong>Year</strong></label>
        <select id='sel_year' class='form-select' onchange='loadProjects()'>
          <option value=''>— select year —</option>";
        foreach ($years as $y) {
            $display_block .= "<option value='" . (int) $y . "'>" . (int) $y . '/' . ((int) $y + 1) . '</option>';
        }
        $display_block .= "
        </select>
      </div>
      <div class='col-auto'>
        <label class='form-label'><strong>Module</strong></label>
        <select id='sel_project' class='form-select' onchange='loadParts()' disabled>
          <option value=''>— select module —</option>
        </select>
      </div>
      <div class='col-auto'>
        <label class='form-label'><strong>Part</strong></label>
        <select id='sel_part' class='form-select' onchange='loadPreview()' disabled>
          <option value=''>— select part —</option>
        </select>
      </div>
    </div>

    <div id='preview_area'></div>

  </div>
</div>

<!-- Confirmation form — hidden until preview loaded -->
<div id='confirm_section' style='display:none'>
  <div class='card border-danger mb-3'>
    <div class='card-header bg-danger text-white'><strong>Step 2 — Confirm copy</strong></div>
    <div class='card-body'>
      <p>You are about to replace the questions for <strong>$assesstype_text</strong> of
         <strong>$module_short_description ($short_code)</strong> with those from:</p>
      <p id='confirm_src_label' class='fw-bold'></p>
      <form method='post' action='" . htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')
          . "?pm_id=$pm_id&part=$assesspart'>
        <input type='hidden' name='op'        value='do_copy'>
        <input type='hidden' name='src_qs'    id='hidden_src_qs'   value=''>
        <input type='hidden' name='src_cats'  id='hidden_src_cats' value=''>
        <div class='form-check mb-3'>
          <input class='form-check-input' type='checkbox' name='confirm_copy' value='1' id='chk_confirm'>
          <label class='form-check-label text-danger fw-bold' for='chk_confirm'>
            I understand the existing questions will be permanently overwritten and cannot be recovered.
          </label>
        </div>
        <button type='submit' class='btn btn-danger'>Copy questions now</button>
        <a href='./edit_module_settings.php?pm_id=$pm_id' class='btn btn-secondary ms-2'>Cancel</a>
      </form>
    </div>
  </div>
</div>";
    } // end if !$copy_done

    $display_block .= "<p class='mt-3'><a href='./edit_module_settings.php?pm_id=$pm_id'>← Back to module admin page</a></p>";
} // end access check
?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Copy Assessment Questions</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="../projectmarking_select_bs.php?pm_id=<?php echo htmlspecialchars($pm_id, ENT_QUOTES, 'UTF-8') ?>" class="nav-link px-2 text-white-50">Module page</a></li>
            <li class="nav-item"><a target="_self" href="../session_modules_bs.php?year_begin=<?php echo htmlspecialchars($year_begin, ENT_QUOTES, 'UTF-8') ?>" class="nav-link px-2 text-white-50">Year module list</a></li>
            <li class="nav-item"><a target="_self" href="./edit_module_settings.php?pm_id=<?php echo htmlspecialchars($pm_id, ENT_QUOTES, 'UTF-8') ?>" class="nav-link px-2 text-white-50">Module admin</a></li>
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Main admin</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper"><div id="content"><div class="container"><div class="container-fluid">
  <div class="card"><div class="card-body">
    <?php echo $display_block; ?>
  </div></div>
</div></div></div></div>

<script>
var SELF_URL = <?php echo json_encode(
    htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8')
    . '?pm_id=' . urlencode($pm_id ?? '')
    . '&part=' . urlencode($assesspart ?? '')
); ?>;

// Stores project rows returned from server
var g_projects = [];

function loadProjects() {
    var year = document.getElementById('sel_year').value;
    var selP = document.getElementById('sel_project');
    var selPt= document.getElementById('sel_part');

    selP.innerHTML  = '<option value="">— loading… —</option>';
    selP.disabled   = true;
    selPt.innerHTML = '<option value="">— select part —</option>';
    selPt.disabled  = true;
    clearPreview();

    if (!year) { selP.innerHTML = '<option value="">— select module —</option>'; return; }

    fetch(SELF_URL + '&ajax=get_projects&year=' + encodeURIComponent(year))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            g_projects = data;
            selP.innerHTML = '<option value="">— select module —</option>';
            data.forEach(function(proj) {
                var opt = document.createElement('option');
                opt.value       = proj.id;
                opt.textContent = proj.label;
                selP.appendChild(opt);
            });
            selP.disabled = false;
        })
        .catch(function() {
            selP.innerHTML = '<option value="">Error loading modules</option>';
        });
}

function loadParts() {
    var pm_id = document.getElementById('sel_project').value;
    var selPt = document.getElementById('sel_part');
    selPt.innerHTML = '<option value="">— select part —</option>';
    selPt.disabled  = true;
    clearPreview();

    if (!pm_id) return;

    var proj = g_projects.find(function(p) { return String(p.id) === String(pm_id); });
    if (!proj) return;

    proj.parts.forEach(function(p) {
        if (p.use) {
            var opt = document.createElement('option');
            opt.value       = p.part;
            opt.textContent = 'Part ' + p.part + (p.desc ? ' — ' + p.desc : '');
            selPt.appendChild(opt);
        }
    });
    selPt.disabled = false;
}

function loadPreview() {
    var pm_id = document.getElementById('sel_project').value;
    var part  = document.getElementById('sel_part').value;
    clearPreview();
    document.getElementById('confirm_section').style.display = 'none';

    if (!pm_id || !part) return;

    var area = document.getElementById('preview_area');
    area.innerHTML = '<p class="text-muted">Loading preview…</p>';

    fetch(SELF_URL + '&ajax=get_preview&src_pm_id=' + encodeURIComponent(pm_id)
                   + '&src_part=' + encodeURIComponent(part))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.ok) {
                area.innerHTML = data.html;
                return;
            }
            area.innerHTML = '<h6 class="mt-2">Preview of source questions:</h6>' + data.html;

            // Populate confirm section
            document.getElementById('hidden_src_qs').value    = data.src_qs;
            document.getElementById('hidden_src_cats').value  = data.src_cats;
            document.getElementById('confirm_src_label').textContent = data.src_label;
            document.getElementById('confirm_section').style.display = '';
        })
        .catch(function(err) {
            area.innerHTML = '<div class="alert alert-danger">Error loading preview: ' + err + '</div>';
        });
}

function clearPreview() {
    var area = document.getElementById('preview_area');
    if (area) { area.innerHTML = ''; }
    document.getElementById('confirm_section').style.display = 'none';
    document.getElementById('hidden_src_qs').value   = '';
    document.getElementById('hidden_src_cats').value = '';
}
</script>
</body>
</html>
