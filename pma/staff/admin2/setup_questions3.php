<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot find this file to include: markstore_functions.php<BR>');
(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>');
(@include_once('../show_feedback_form.php')) or die('Cannot get show_feedback_form.php<BR>');
(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');

if (array_key_exists('pm_id', $_REQUEST) == false) {
    $_REQUEST['pm_id'] = null;
}
$pm_id = $_REQUEST['pm_id'];
$assesspart = isset($_GET['part']) ? $_GET['part'] : null;

$assessqs_year = $assessqs[$assesspart];
$assess_cats = $assessqs_cats[$assesspart];
$is_numeric = $p_numeric[$assesspart];

// ─────────────────────────────────────────────────────────────────────────────
// AJAX HANDLER  (op=save_num_sections)
// Deletes categories (and their questions) beyond the chosen count.
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_POST['op']) && $_POST['op'] === 'save_num_sections') {
    header('Content-Type: application/json');
    $new_count = (int) ($_POST['num_sections'] ?? 0);
    if ($new_count < 1 || $new_count > 5) {
        echo json_encode(['ok' => false, 'msg' => 'Section count must be 1–5.']);
        exit();
    }
    try {
        $stmt = $db->prepare("DELETE FROM $assess_cats WHERE cat > :n");
        $stmt->execute([':n' => $new_count]);
        $stmt = $db->prepare("DELETE FROM $assessqs_year WHERE cat > :n");
        $stmt->execute([':n' => $new_count]);
        echo json_encode(['ok' => true, 'msg' => "Section count set to $new_count."]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'Database error: ' . $ex->getMessage()]);
    }
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// AJAX HANDLER  (op=save_section)
// Saves a single category — keeps POST count well under max_input_vars.
// ─────────────────────────────────────────────────────────────────────────────
if (isset($_POST['op']) && $_POST['op'] === 'save_section') {
    header('Content-Type: application/json');

    $cat = (int) ($_POST['cat'] ?? 0);
    $new_numqs = (int) ($_POST["new_numqs$cat"] ?? 0);

    if ($cat < 1 || $cat > 5) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid category number.']);
        exit();
    }

    // ── Collect category-level fields ────────────────────────────────────────
    $section_title = isset($_POST["sec$cat"]) ? $_POST["sec$cat"] : '';
    $weight_pc = isset($_POST["weightpc_name$cat"]) ? (float) $_POST["weightpc_name$cat"] : 0;
    $factor = isset($_POST["factor_name$cat"]) ? (float) $_POST["factor_name$cat"] : 0;
    $show_feedback = isset($_POST["show_cat_feedback$cat"]) ? 1 : 0;

    // ── Collect question-level fields ────────────────────────────────────────
    $questions = [];
    $num_responses = [];
    $q_weights = [];
    $resp_texts = [];
    $resp_marks = [];

    for ($q = 1; $q <= $new_numqs; $q++) {
        $questions[$q] = isset($_POST["q$cat-$q"]) ? $_POST["q$cat-$q"] : '';
        $num_responses[$q] = isset($_POST["res$cat-$q"]) ? (int) $_POST["res$cat-$q"] : 1;
        $q_weights[$q] = isset($_POST["qw$cat-$q"]) ? (float) $_POST["qw$cat-$q"] : 100;

        for ($r = 1; $r <= $num_responses[$q]; $r++) {
            $resp_texts[$q][$r] = isset($_POST["t$cat-$q-$r"]) ? $_POST["t$cat-$q-$r"] : '';
            $resp_marks[$q][$r] = isset($_POST["m$cat-$q-$r"]) ? (float) $_POST["m$cat-$q-$r"] : 0;
        }
    }

    // ── Write to DB ──────────────────────────────────────────────────────────
    try {
        // Ensure get_feedback column exists
        if (!DoesColumnExistInTable($db, $assess_cats, 'get_feedback')) {
            $db->query("ALTER TABLE $assess_cats ADD `get_feedback` int DEFAULT 1");
        }

        // Remove existing rows for this category only
        $stmt = $db->prepare("DELETE FROM $assess_cats WHERE cat = :cat");
        $stmt->execute([':cat' => $cat]);

        $stmt = $db->prepare("DELETE FROM $assessqs_year WHERE cat = :cat");
        $stmt->execute([':cat' => $cat]);

        // Insert category row
        $stmt = $db->prepare(
            "INSERT INTO $assess_cats
               (cat, description, weight_percent, factor, get_feedback)
             VALUES (:cat, :desc, :wp, :factor, :fb)"
        );
        $stmt->execute([
            ':cat' => $cat,
            ':desc' => $section_title,
            ':wp' => $weight_pc,
            ':factor' => $factor,
            ':fb' => $show_feedback,
        ]);

        // Insert question rows
        for ($q = 1; $q <= $new_numqs; $q++) {
            // Build dynamic columns for responses (text1/mark1 … text8/mark8)
            $cols = 'cat, qid, question, entries, weight';
            $vals = ':cat, :qid, :question, :entries, :weight';
            $params = [
                ':cat' => $cat,
                ':qid' => $q,
                ':question' => $questions[$q],
                ':entries' => $num_responses[$q],
                ':weight' => $q_weights[$q],
            ];
            for ($r = 1; $r <= $num_responses[$q]; $r++) {
                $cols .= ", text$r, mark$r";
                $vals .= ", :text$r, :mark$r";
                $params[":text$r"] = $resp_texts[$q][$r];
                $params[":mark$r"] = $resp_marks[$q][$r];
            }
            $db->prepare("INSERT INTO $assessqs_year ($cols) VALUES ($vals)")
               ->execute($params);
        }

        // Feedback placeholder row
        $db->prepare(
            "INSERT INTO $assessqs_year (cat, qid, question, entries)
             VALUES (:cat, :qid, 'Feedback Comments:', 0)"
        )->execute([':cat' => $cat, ':qid' => $new_numqs + 1]);

        echo json_encode(['ok' => true, 'msg' => "Section $cat saved."]);
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        echo json_encode(['ok' => false, 'msg' => 'Database error: ' . $ex->getMessage()]);
    }
    exit();
}

// ─────────────────────────────────────────────────────────────────────────────
// PAGE BUILD
// ─────────────────────────────────────────────────────────────────────────────
$display_block = '';
$display_block .= $connect_msg;

$admin_access = false;
$admin_access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name, $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;
}
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>$access_phrase</h2>\n";

if (!$is_module_admin && !$admin_access) {
    $display_block .= 'You do not have the appropriate admin level<BR>';
    $display_block .= "<A href='../index.php'>Go back here</A><BR>";
} else {
    $assesstype_text = $p_description[$assesspart];
    $mark_max = $p_max[$assesspart];
    $mark_pc = $p_percent[$assesspart];

    $MAX_CATS = 5;
    $MAX_QUESTIONS = 10;
    $MAX_RESPONSES = 8;

    $display_block .= '<h1>View and edit questions, marks and section weightings</h1>';
    $display_block .= "<h2>$assesstype_text — $module_short_description $module_prefix$module1$module_postfix ($short_code) $year_begin/$year_end</h2>";
    $display_block .= "<p>Questions: <code>$assessqs_year</code> &nbsp; Categories: <code>$assess_cats</code></p>";

    $display_block .= "
<div class='feedbackInstructions'>
  Edit any field and click <strong>Save this section</strong> to store changes for that section only.
  <ul>
    <li>Change the question or response text in-place.</li>
    <li>Change a response mark and click a radio button to preview the section score.</li>
    <li>Increase/decrease <em>Number of questions</em> and save — rows appear or disappear.</li>
    <li>Increase/decrease <em>Number of responses</em> per question and save — response rows appear or disappear.</li>
    <li>Max: 5 sections, 10 questions per section, 8 responses per question.</li>
  </ul>
</div><BR>";

    if ($is_numeric) {
        $display_block .= "<div style='border: thin solid black; padding: 5px; background-color: #fceda7'>
<p>This assessment is <strong>numeric</strong>. Define only <strong>one response</strong> per question —
the text indicates what to look for. Change the type on the
<a href='./edit_module_settings.php?pm_id=$pm_id'>Module admin page</a> to switch.</p>
</div>";
    }

    $display_block .= "<div id='sticker'>Total: <span id='grand_total'>0.0</span></div>";

    // Count how many sections currently exist in DB
    $stmt_nc = $db->query("SELECT COUNT(*) FROM $assess_cats WHERE cat IS NOT NULL");
    $num_sections = (int) $stmt_nc->fetchColumn();
    if ($num_sections < 1) {
        $num_sections = 1;
    }

    $display_block .= "
<div class='mb-3 p-2 border rounded bg-light d-flex align-items-center flex-wrap gap-2'>
  <label class='mb-0'><strong>Number of sections:</strong>
    <input type='number' id='num_sections' value='$num_sections' min='1' max='$MAX_CATS'
           style='width:4em; margin-left:6px;'
           onchange='updateSectionVisibility()'>
  </label>
  <button type='button' class='btn btn-sm btn-secondary' onclick='saveNumSections()'>Save section count</button>
  <span id='num_sections_status' class='small'></span>
  <span class='text-muted small'>(max $MAX_CATS — reducing removes data from sections beyond the new count)</span>
</div>";

    // ── Build section panels ─────────────────────────────────────────────────
    for ($cat = 1; $cat <= $MAX_CATS; $cat++) {
        // Category metadata
        $query = "SELECT * FROM $assess_cats WHERE cat = :cat";
        $stmt = $db->prepare($query);
        $stmt->execute([':cat' => $cat]);
        $cat_data = $stmt->fetch();

        $description = $cat_data ? $cat_data['description'] : 'Enter a new section description';
        $weight_pc = $cat_data ? $cat_data['weight_percent'] : '20';
        $factor = $cat_data ? $cat_data['factor'] : '0.1';
        $show_feedback = ($cat_data && isset($cat_data['get_feedback'])) ? (int) $cat_data['get_feedback'] : 1;

        // Questions for this category
        $stmt = $db->prepare("SELECT * FROM $assessqs_year WHERE cat = :cat ORDER BY qid");
        $stmt->execute([':cat' => $cat]);
        $all_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Last row is the feedback placeholder (entries=0), exclude it
        $question_rows = array_filter($all_rows, fn ($r) => (int) $r['entries'] !== 0 || $r['question'] !== 'Feedback Comments:');
        $question_rows = array_values($question_rows);
        $num_q_in_db = count($question_rows);
        if ($num_q_in_db < 1) {
            $num_q_in_db = 1;
        }

        // Build data arrays indexed 1-based
        $questions = [];
        $num_responses = [];
        $q_weights_arr = [];
        $resp_texts = [];
        $resp_marks = [];

        for ($q = 1; $q <= $MAX_QUESTIONS; $q++) {
            if (isset($question_rows[$q - 1])) {
                $row = $question_rows[$q - 1];
                $questions[$q] = htmlspecialchars($row['question'], ENT_QUOTES, 'UTF-8');
                $nr = (int) $row['entries'];
                if ($is_numeric) {
                    $nr = 1;
                }
                $num_responses[$q] = $nr > 0 ? $nr : 1;
                $qw = is_numeric($row['weight']) ? (float) $row['weight'] : 100;
                $q_weights_arr[$q] = $qw;
                for ($r = 1; $r <= $MAX_RESPONSES; $r++) {
                    $resp_texts[$q][$r] = ($r <= $nr) ? htmlspecialchars($row["text$r"] ?? 'enter a response', ENT_QUOTES, 'UTF-8') : 'enter a response';
                    $resp_marks[$q][$r] = ($r <= $nr) ? ($is_numeric ? 100 : (float) ($row["mark$r"] ?? 0)) : (($r - 1) * 10);
                }
            } else {
                $questions[$q] = 'Enter a new question';
                $num_responses[$q] = 4;
                $q_weights_arr[$q] = 100;
                for ($r = 1; $r <= $MAX_RESPONSES; $r++) {
                    $resp_texts[$q][$r] = 'enter a response';
                    $resp_marks[$q][$r] = ($r - 1) * 10;
                }
            }
        }

        // ── Render section panel ─────────────────────────────────────────────
        $fb_checked = $show_feedback ? 'checked' : '';
        $panel_visible = ($cat <= $num_sections) ? '' : " style='display:none'";
        $display_block .= "
<div class='card mb-3' id='section_panel_$cat'$panel_visible>
  <div class='card-header'>
    <strong>Section $cat</strong>
    <span id='save_status_$cat' class='ms-3 small'></span>
  </div>
  <div class='card-body'>
    <div class='row mb-2'>
      <div class='col-12'>
        <label class='feedbackSection'>Section title:</label>
        <input class='feedbackSectionQ' type='text' id='sec$cat' name='sec$cat' value='$description' size='60'>
      </div>
    </div>
    <div class='row mb-2 feedbackWeights'>
      <div class='col-auto'>
        Proportion of assessment:
        <input type='number' id='weightpc_name$cat' name='weightpc_name$cat' value='$weight_pc' min='0' max='100' size='3'>%
      </div>
      <div class='col-auto'>
        Weight factor:
        <input type='number' id='factor_name$cat' name='factor_name$cat' value='$factor' min='0' max='1' step='0.0001' size='4'>
      </div>
      <div class='col-auto'>
        Number of questions (max $MAX_QUESTIONS):
        <input type='number' id='new_numqs$cat' name='new_numqs$cat'
               value='$num_q_in_db' min='1' max='$MAX_QUESTIONS' size='3'
               onchange='updateQuestionVisibility($cat)'>
      </div>
    </div>";

        // Questions
        for ($q = 1; $q <= $MAX_QUESTIONS; $q++) {
            $q_visible = ($q <= $num_q_in_db) ? '' : "style='display:none'";
            $nr = $num_responses[$q];
            $qtext = $questions[$q];
            $qw = $q_weights_arr[$q];

            $display_block .= "
    <div id='q_block_c{$cat}_q{$q}' class='question-block border rounded p-2 mb-2' $q_visible>
      <span class='feedbackQ'>Q$q:
        <input class='feedbackQem' type='text' id='q$cat-$q' name='q$cat-$q' value='$qtext' size='100'>
      </span><br>
      <div class='options ms-3'>
        <label>Responses:
          <input type='number' id='res$cat-$q' name='res$cat-$q'
                 value='$nr' min='1' max='$MAX_RESPONSES' size='2'
                 onchange='updateResponseVisibility($cat, $q)'>
        </label>
        &nbsp;
        <label>Mark fraction (%):
          <input type='number' id='qw$cat-$q' name='qw$cat-$q'
                 value='$qw' min='0' max='100' step='1' size='4'>
        </label>
        <table class='resp-table mt-1'>
          <thead><tr>
            <th style='width:2em'>#</th>
            <th>Response text</th>
            <th style='width:6em'>Mark</th>
            <th style='width:3em'>Test</th>
          </tr></thead>
          <tbody>";

            for ($r = 1; $r <= $MAX_RESPONSES; $r++) {
                $r_style = ($r <= $nr) ? '' : " style='display:none'";
                $rtext = $resp_texts[$q][$r];
                $rmark = $resp_marks[$q][$r];
                $display_block .= "
            <tr id='resp_c{$cat}_q{$q}_r{$r}'$r_style>
              <td>$r</td>
              <td><input type='text' id='t$cat-$q-$r' name='t$cat-$q-$r' value='$rtext' class='form-control form-control-sm'></td>
              <td><input type='number' id='m$cat-$q-$r' name='m$cat-$q-$r' value='$rmark'
                         min='-1000' max='1000' class='form-control form-control-sm'
                         onchange='syncRadio(this, \"radio_c{$cat}_q{$q}\", $r); recalcSection($cat)'></td>
              <td style='text-align:center'><input type='radio' name='radio_c{$cat}_q{$q}' value='$rmark'
                         onchange='recalcSection($cat)'></td>
            </tr>";
            }

            $display_block .= '
          </tbody>
        </table>';

            $display_block .= "
      </div><!-- .options -->
    </div><!-- #q_block_c{$cat}_q{$q} -->";
        }

        // Feedback checkbox + section score + save button
        $display_block .= "
    <div class='mt-2'>
      <label>
        <input type='checkbox' id='show_cat_feedback$cat' name='show_cat_feedback$cat'
               value='1' $fb_checked>
        Require written feedback from marker for this section
      </label>
    </div>
    <div class='mt-2'>
      <span class='feedbackSectionMarkText'>Test score for section $cat:</span>
      <span class='feedbackSectionMark' id='sectionmark$cat'>—</span>
    </div>
    <div class='mt-3'>
      <button type='button' class='btn btn-primary btn-sm'
              onclick='saveSection($cat)'>Save Section $cat</button>
    </div>
  </div><!-- .card-body -->
</div><!-- #section_panel_$cat -->";
    } // end for $cat

    $display_block .= "
<div class='mb-4'>
  <span class='feedbackSection'>Grand total (test): <strong id='grand_total_2'>0.0</strong></span>
</div>
<hr>
<p><em>Use the <strong>Save Section</strong> buttons above to commit changes section by section.</em></p>
<p><small>Questions table: $assessqs_year &nbsp;|&nbsp; Categories: $assess_cats</small></p>

<input type='hidden' id='markmax' value='$mark_max'>
<input type='hidden' id='markpc'  value='$mark_pc'>";
} // end if has access
?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<style>
  .question-block { background: #f9f9f9; }
  #sticker { position: fixed; top: 60px; right: 10px; background: #fceda7;
             border: 2px solid #aaa; padding: 6px 12px; font-weight: bold;
             border-radius: 4px; z-index: 999; }
  .save-ok  { color: green; font-weight: bold; }
  .save-err { color: red;   font-weight: bold; }
  .resp-table { border-collapse: collapse; width: 100%; }
  .resp-table th { background: #e9ecef; padding: 2px 6px; font-size: 0.85em;
                   text-align: left; border: 1px solid #ced4da; }
  .resp-table td { padding: 2px 4px; border: 1px solid #dee2e6; vertical-align: middle; }
  .resp-table input[type=text]   { width: 100%; }
  .resp-table input[type=number] { width: 100%; }
</style>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Edit questions &amp; marks</span>
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
// ─── Section-level visibility + count save ────────────────────────────────────

function updateSectionVisibility() {
    var n     = parseInt(document.getElementById('num_sections').value, 10);
    var MAX_C = <?php echo $MAX_CATS; ?>;
    for (var c = 1; c <= MAX_C; c++) {
        var panel = document.getElementById('section_panel_' + c);
        if (panel) { panel.style.display = (c <= n) ? '' : 'none'; }
    }
    recalcGrandTotal();
}

function saveNumSections() {
    var statusEl = document.getElementById('num_sections_status');
    var n = parseInt(document.getElementById('num_sections').value, 10);
    statusEl.className   = 'small';
    statusEl.textContent = 'Saving…';

    var data = new FormData();
    data.append('op', 'save_num_sections');
    data.append('num_sections', n);
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) { data.append('csrf_token', csrfMeta.getAttribute('content')); }

    var url = window.location.pathname +
              '?pm_id=<?php echo urlencode($pm_id ?? '') ?>&part=<?php echo urlencode($assesspart ?? '') ?>';

    fetch(url, { method: 'POST', body: data })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            statusEl.className   = 'small ' + (json.ok ? 'save-ok' : 'save-err');
            statusEl.textContent = (json.ok ? '✓ ' : '✗ ') + json.msg;
            if (json.ok) { updateSectionVisibility(); }
        })
        .catch(function(err) {
            statusEl.className   = 'small save-err';
            statusEl.textContent = '✗ Network error: ' + err;
        });
}

// ─── Keep radio value in sync when mark number is edited ─────────────────────

function syncRadio(markInput, radioGroupName, responseIndex) {
    var radios = document.getElementsByName(radioGroupName);
    if (radios[responseIndex - 1]) {
        radios[responseIndex - 1].value = markInput.value;
    }
}

// ─── Visibility helpers ───────────────────────────────────────────────────────

function updateQuestionVisibility(cat) {
    var numqs = parseInt(document.getElementById('new_numqs' + cat).value, 10);
    var MAX_Q = <?php echo $MAX_QUESTIONS; ?>;
    for (var q = 1; q <= MAX_Q; q++) {
        var block = document.getElementById('q_block_c' + cat + '_q' + q);
        if (block) { block.style.display = (q <= numqs) ? '' : 'none'; }
    }
    recalcSection(cat);
}

function updateResponseVisibility(cat, q) {
    var numr  = parseInt(document.getElementById('res' + cat + '-' + q).value, 10);
    var MAX_R = <?php echo $MAX_RESPONSES; ?>;
    for (var r = 1; r <= MAX_R; r++) {
        var row = document.getElementById('resp_c' + cat + '_q' + q + '_r' + r);
        if (row) { row.style.display = (r <= numr) ? 'table-row' : 'none'; }
    }
    recalcSection(cat);
}

// ─── Live mark calculation ────────────────────────────────────────────────────

function recalcSection(cat) {
    var MAX_Q  = <?php echo $MAX_QUESTIONS; ?>;
    var numqs  = parseInt(document.getElementById('new_numqs' + cat).value, 10) || 0;
    var factor = parseFloat(document.getElementById('factor_name' + cat).value) || 0;
    var total  = 0;

    for (var q = 1; q <= numqs; q++) {
        var radios = document.getElementsByName('radio_c' + cat + '_q' + q);
        for (var i = 0; i < radios.length; i++) {
            if (radios[i].checked) {
                total += parseFloat(radios[i].value) || 0;
                break;
            }
        }
    }

    var sectionScore = total * factor;
    var el = document.getElementById('sectionmark' + cat);
    if (el) { el.textContent = sectionScore.toFixed(2); }

    recalcGrandTotal();
}

function recalcGrandTotal() {
    var MAX_CATS = <?php echo $MAX_CATS; ?>;
    var total = 0;
    for (var cat = 1; cat <= MAX_CATS; cat++) {
        var el = document.getElementById('sectionmark' + cat);
        if (el) { total += parseFloat(el.textContent) || 0; }
    }
    var gt1 = document.getElementById('grand_total');
    var gt2 = document.getElementById('grand_total_2');
    if (gt1) { gt1.textContent = total.toFixed(2); }
    if (gt2) { gt2.textContent = total.toFixed(2); }
}

// ─── AJAX save ───────────────────────────────────────────────────────────────

function saveSection(cat) {
    var statusEl = document.getElementById('save_status_' + cat);
    statusEl.className = 'ms-3 small';
    statusEl.textContent = 'Saving…';

    var MAX_Q  = <?php echo $MAX_QUESTIONS; ?>;
    var MAX_R  = <?php echo $MAX_RESPONSES; ?>;
    var numqs  = parseInt(document.getElementById('new_numqs' + cat).value, 10) || 0;

    var data   = new FormData();
    data.append('op',  'save_section');
    data.append('cat', cat);

    // Category-level fields
    function field(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    }
    function checked(id) {
        var el = document.getElementById(id);
        return (el && el.checked) ? '1' : '0';
    }

    data.append('sec'        + cat,         field('sec'        + cat));
    data.append('weightpc_name' + cat,      field('weightpc_name' + cat));
    data.append('factor_name'   + cat,      field('factor_name'   + cat));
    data.append('new_numqs'     + cat,      numqs);
    if (document.getElementById('show_cat_feedback' + cat) &&
        document.getElementById('show_cat_feedback' + cat).checked) {
        data.append('show_cat_feedback' + cat, '1');
    }

    // Add CSRF token if present
    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    if (csrfMeta) { data.append('csrf_token', csrfMeta.getAttribute('content')); }

    // Question + response fields
    for (var q = 1; q <= numqs; q++) {
        data.append('q'   + cat + '-' + q, field('q'   + cat + '-' + q));
        data.append('res' + cat + '-' + q, field('res' + cat + '-' + q));
        data.append('qw'  + cat + '-' + q, field('qw'  + cat + '-' + q));

        var numr = parseInt(field('res' + cat + '-' + q), 10) || 1;
        for (var r = 1; r <= numr; r++) {
            data.append('t' + cat + '-' + q + '-' + r, field('t' + cat + '-' + q + '-' + r));
            data.append('m' + cat + '-' + q + '-' + r, field('m' + cat + '-' + q + '-' + r));
        }
    }

    var url = window.location.pathname +
              '?pm_id=<?php echo urlencode($pm_id ?? '') ?>&part=<?php echo urlencode($assesspart ?? '') ?>';

    fetch(url, { method: 'POST', body: data })
        .then(function(r) { return r.json(); })
        .then(function(json) {
            if (json.ok) {
                statusEl.className   = 'ms-3 small save-ok';
                statusEl.textContent = '✓ ' + json.msg;
            } else {
                statusEl.className   = 'ms-3 small save-err';
                statusEl.textContent = '✗ ' + json.msg;
            }
        })
        .catch(function(err) {
            statusEl.className   = 'ms-3 small save-err';
            statusEl.textContent = '✗ Network error: ' + err;
        });
}
</script>
</body>
</html>
