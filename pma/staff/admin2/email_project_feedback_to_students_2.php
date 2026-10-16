<?php
// email_project_feedback_to_students_2.php
// Real-time progress version — do NOT modify the original _1 file; use this copy.

if (session_status() === PHP_SESSION_NONE) session_start();

(@include_once('./set_dir_admin.php'))                  or die('set_dir_admin.php missing');
(@include_once("$STAFF_DIR_VAL/config.php"))            or die('config.php missing');
(@include_once('../setup_titles.php'))                   or die('setup_titles.php missing');
(@include_once('../connect_pdo.php'))                    or die('connect_pdo.php missing');
(@include_once('../setup_tables.php'))                   or die('setup_tables.php missing');
(@include_once('../staff_functions.php'))                or die('staff_functions.php missing');
(@include_once('../markstore_functions.php'))            or die('markstore_functions.php missing');
(@include_once('../table_functions.php'))                or die('table_functions.php missing');
(@include_once('../show_feedback_form.php'))             or die('show_feedback_form.php missing');
(@include_once('../show_minor_part_feedback_form.php'))  or die('show_minor_part_feedback_form.php missing');
(@include_once('../EmailPDFAttachment.php'))             or die('EmailPDFAttachment.php missing');

require_once __BASE_DIR__ . '/vendor/autoload.php';

(@include_once('./admin_functions.php'))                 or die('admin_functions.php missing');

// ── Normalise request vars ───────────────────────────────────────────────────
$pm_id       = (int)($_REQUEST['pm_id']       ?? 0);
$part        = $_REQUEST['part']               ?? '';
$POST_OP     = $_REQUEST['op']                 ?? '';
$summary_mark = (int)($_REQUEST['summary_mark'] ?? 0);

// ── Auth ─────────────────────────────────────────────────────────────────────
$admin_access    = access_check_response($db, $this_staff_username, $this_staff_full_name,
                                         $staff_names, $access_phrase);
$is_module_admin = IsModuleAdmin2($db, $this_staff_username, $this_staff_full_name,
                                  $pm_id, null, null, $module_level_access_phrase);
if ($is_module_admin) {
    $access_phrase = $module_level_access_phrase;
}
$has_access = $is_module_admin || $admin_access;

// ── Module setup (needed for both stream and HTML modes) ─────────────────────
$setup_data     = null;
$module1        = '';
$year_begin     = '';
$module_short_code = '';
$assess_part_description_text = '';
$module_leader_email = GetStaffEmailFromFullname($db, $module_leader_fullname, $staff_names2);
$this_staff_email    = GetStaffEmailFromID($db, $this_staff_id, $staff_names2);

if ($pm_id > 0) {
    $res = $db->query("SELECT * FROM $projects_setup WHERE id=$pm_id");
    $setup_data = $res ? $res->fetch() : null;
    if ($setup_data) {
        $module1           = $setup_data['module'];
        $year_begin        = $setup_data['year_begin'];
        $module_short_code = $setup_data['short_code'];
    }
}

if (!empty($part)) {
    $assess_part_description_text = $p_description[$part] ?? '';
}

$summary_desc = ($summary_mark == 1) ? '(with summary marks)' : '';

$is_numeric = ($pm_id > 0 && !empty($part))
    ? GetIsNumeric($db, $pm_id, $part, $projects_setup)
    : false;

// Feedback table: create if absent
$res = $db->query("SHOW TABLES LIKE '$feedback_records'");
if ($res && $res->rowCount() == 0) {
    create_feedback_records_table_structure($db, $feedback_records);
}

set_time_limit(0);
ini_set('memory_limit', '144M');

// ════════════════════════════════════════════════════════════════════════════
// SSE STREAM MODE — must run before any HTML output
// ════════════════════════════════════════════════════════════════════════════
if ($POST_OP === 'stream' && $has_access) {

    // Disable all output buffering so each flush() actually reaches the browser
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
    while (ob_get_level() > 0) { @ob_end_flush(); }

    header('Content-Type: text/event-stream; charset=UTF-8');
    header('Cache-Control: no-store, no-cache');
    header('X-Accel-Buffering: no');   // Nginx pass-through
    header('Connection: keep-alive');

    // Read and release session ASAP so the browser can still load other pages
    $job = $_SESSION['email_feedback_job'] ?? null;
    session_write_close();

    $sse = function (array $data): void {
        echo 'data: ' . json_encode($data) . "\n\n";
        @ob_flush();
        flush();
    };

    if (!$job || (int)($job['pm_id'] ?? 0) !== $pm_id) {
        $sse(['type' => 'error', 'msg' => 'Session job not found — please go back and resubmit.']);
        exit;
    }

    $jPart        = $job['part']            ?? $part;
    $makepdf      = $job['makepdf']         ?? null;
    $subjectline  = $job['subjectline']     ?? '';
    $email_body_text = $job['email_body_text'] ?? '';
    $reallysend   = $job['reallysend']      ?? null;
    $checked_sids = array_flip($job['checked_sids'] ?? []);  // flip for O(1) lookup

    // Fetch all allocated students
    $student_rows = $db->query("SELECT * FROM $project_allocated ORDER BY projectid")->fetchAll();
    $total        = count($student_rows);

    $sse(['type' => 'start', 'total' => $total]);

    $cnt_sent = 0; $cnt_skipped = 0; $cnt_errors = 0; $n = 0;

    $lb = "\n";

    foreach ($student_rows as $recs) {
        $n++;
        $project_id       = $recs['projectid'];
        $sid              = $recs['sid'];
        $title            = stripslashes($recs['title']);
        $supervisor_id    = $recs['supervisor_id'];
        $second_marker_id = $recs['second_marker_id'];

        // Student details
        $st = $db->query("SELECT * FROM $students WHERE sid=$sid")->fetch();
        $student_name = $st['name']      ?? '';
        $username     = $st['username']  ?? '';
        $programme    = $st['programme'] ?? '';

        // Not ticked — skip silently (counted as skipped)
        if (!isset($checked_sids[(string)$sid])) {
            $cnt_skipped++;
            $sse(['type' => 'progress', 'n' => $n, 'total' => $total,
                  'student' => $student_name, 'username' => $username,
                  'status' => 'unchecked', 'detail' => '']);
            continue;
        }

        $total_mark_for_part = GetTotalForPart($db, $sid, $jPart, $markstore_totals);
        if ($total_mark_for_part < 0) {
            $cnt_skipped++;
            $sse(['type' => 'progress', 'n' => $n, 'total' => $total,
                  'student' => $student_name, 'username' => $username,
                  'status' => 'unmarked', 'detail' => 'No mark recorded']);
            continue;
        }

        // Build email body
        $email_full = "$student_name,$lb$lb" . $email_body_text;

        // Feedback form HTML
        $marker_entry  = "marker_p{$jPart}m1_id";
        $marker_id     = $recs[$marker_entry] ?? null;
        $mark_method   = GetMarkMethodString($db, $sid, $jPart, $markstore_totals);
        $marker_names  = GetMarkerNamesArray($db, $sid, $jPart, $project_allocated, $staff_names2);
        $markstore_list = GetMarkStoreListFromMarkMethodString(
            $mark_method, $markstores[$jPart][1] ?? '', $markstores[$jPart][2] ?? '', $markstores[$jPart][3] ?? '');
        $marker_list   = GetMarkerListFromMarkMethodString(
            $mark_method, $marker_names[1] ?? '', $marker_names[2] ?? '', $marker_names[3] ?? '');
        $marker_name   = GetStaffNameFromID($db, $marker_id, $staff_names2, null);

        $assessqs_year = $assessqs[$jPart]      ?? [];
        $assess_cats   = $assessqs_cats[$jPart] ?? [];
        $assesstype_text = $p_description[$jPart] ?? '';
        $mark_max      = $p_max[$jPart]   ?? 0;
        $mark_pc       = $p_percent[$jPart] ?? 0;

        $num_markstores = count($markstore_list) - 1;
        $feedback_form_html = '';
        if ($num_markstores == 1) {
            $feedback_form_html = show_feedback_form1(
                $db, $sid, $markstore_list[1], $markstore_list[1], $markstore_list[1],
                $markstore_totals, $assessqs_year, $student_name, $username, $title,
                $marker_list[1], $marker_list[1], $marker_name, $project_id,
                $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme,
                $jPart, $assesstype_text, $module_short_description, $is_numeric);
        } elseif ($num_markstores == 2) {
            $feedback_form_html = show_feedback_form2(
                $db, $sid, $markstore_list[1], $markstore_list[2], $markstore_list[2],
                $markstore_totals, $assessqs_year, $student_name, $username, $title,
                $marker_list[1], $marker_list[2], $marker_name, $project_id,
                $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme,
                $jPart, $assesstype_text, $module_short_description, $is_numeric);
        } elseif ($num_markstores == 3) {
            $feedback_form_html = show_feedback_form3(
                $db, $sid, $markstore_list[1], $markstore_list[2], $markstore_list[3],
                $markstore_totals, $assessqs_year, $student_name, $username, $title,
                $marker_list[1], $marker_list[2], $marker_name, $project_id,
                $assesstype_text, $assess_cats, $mark_max, $mark_pc, $programme,
                $jPart, $assesstype_text, $module_short_description, $is_numeric);
        } else {
            $cnt_skipped++;
            $sse(['type' => 'progress', 'n' => $n, 'total' => $total,
                  'student' => $student_name, 'username' => $username,
                  'status' => 'error', 'detail' => 'Could not build feedback form (unknown markstore count)']);
            continue;
        }

        $feedback_form_html = mb_convert_encoding($feedback_form_html, 'UTF-8', mb_list_encodings());

        // PDF
        $fileattached_pdf = '';
        $name_of_file     = '';
        $do_not_attach    = 1;
        if ($makepdf !== null) {
            error_reporting(0);
            $mpdf = new \Mpdf\Mpdf(['tempDir' => PMA_TMP_DIR]);
            $mpdf->setFooter("$student_name  Date printed: {DATE j M Y}.  Page {PAGENO}");
            $stylesheet = file_get_contents('../css/pagedesc.css');
            $mpdf->WriteHTML($stylesheet, 1);
            $mpdf->WriteHTML($feedback_form_html);
            $fileattached_pdf = $mpdf->Output('', 'S');
            $name_of_file     = "Feedback-$module_short_description-$assesstype_text.pdf";
            $do_not_attach    = 0;
            error_reporting(-1);
            unset($mpdf);
        }

        // Email
        $subject       = $subjectline !== '' ? $subjectline : 'Project Feedback';
        $myemail_to    = "$username$local_email_domain";
        $myemail_from  = "$module_leader_email (Project feedback)";
        $myemail_message = nl2br($email_full);
        $from          = $this_staff_email;
        $cc_to         = ($job['cc_module_leader'] ?? false) ? $module_leader_email : '';

        if ($reallysend === null) {
            $cnt_sent++;
            $sse(['type' => 'progress', 'n' => $n, 'total' => $total,
                  'student' => $student_name, 'username' => $username,
                  'status' => 'dryrun',
                  'detail'  => "Dry run — would send to $myemail_to"]);
        } else {
            $ok = EmailPDFAttachmentPHPMailer(
                $myemail_to, $myemail_from, $fileattached_pdf,
                $subject, $myemail_message, $name_of_file, $do_not_attach, $cc_to);
            if ($ok) {
                $cnt_sent++;
                RecordFeedbackSent($db, $sid, $jPart, $feedback_records);
                $sse(['type' => 'progress', 'n' => $n, 'total' => $total,
                      'student' => $student_name, 'username' => $username,
                      'status' => 'sent', 'detail' => $myemail_to]);
            } else {
                $cnt_errors++;
                $sse(['type' => 'progress', 'n' => $n, 'total' => $total,
                      'student' => $student_name, 'username' => $username,
                      'status' => 'error', 'detail' => "Failed to email $myemail_to"]);
            }
        }

        set_time_limit(0); // reset per-student
    }

    $sse(['type' => 'done', 'sent' => $cnt_sent, 'skipped' => $cnt_skipped, 'errors' => $cnt_errors]);
    exit;
}

// ════════════════════════════════════════════════════════════════════════════
// op=send (POST) — save job to session, then fall through to HTML progress view
// ════════════════════════════════════════════════════════════════════════════
if ($POST_OP === 'send' && $has_access) {
    $checked_sids = [];
    foreach ($_POST as $k => $v) {
        if (strpos($k, 'check_') === 0 && $v === 'on') {
            $checked_sids[] = substr($k, 6);
        }
    }
    $_SESSION['email_feedback_job'] = [
        'pm_id'           => $pm_id,
        'part'            => $part,
        'makepdf'         => $_POST['makepdf']          ?? null,
        'subjectline'     => $_POST['subjectline']      ?? '',
        'email_body_text' => $_POST['email_body_text']  ?? '',
        'reallysend'        => $_POST['reallysend']         ?? null,
        'cc_module_leader'  => isset($_POST['cc_module_leader']),
        'summary_mark'      => $summary_mark,
        'checked_sids'      => $checked_sids,
    ];
    session_write_close();
}

// ════════════════════════════════════════════════════════════════════════════
// HTML page output
// ════════════════════════════════════════════════════════════════════════════

// Default email body suggestion (used on op=select)
$lb = "\n";
$default_email_body = "Attached to this email is the feedback for the $assess_part_description_text part of the $module_short_description $summary_desc.$lb$lb"
    . "You should read this feedback, taking in the comments made and try to use these as advice to help improve similar future work.$lb$lb"
    . "If you have any questions about this feedback (or any other aspects of the project) please contact the supervisor, marker or module leader.$lb";

$default_subject = "Feedback for $module_short_description. $assess_part_description_text $summary_desc";
?>
<!DOCTYPE html>
<html lang="en">
<?php (@include_once('mobile_head_admin_bs.php')) or die('mobile_head_admin_bs.php missing'); ?>
<style>
/* ── Student selection table ──────────────────────────────────────── */
#students_select_table {
    max-height: 55vh;
    overflow: auto;
    border: 1px solid #dee2e6;
    border-radius: .375rem;
}
#student_email_list {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: .875rem;
}
#student_email_list th,
#student_email_list td {
    padding: .4rem .55rem;
    border-bottom: 1px solid #dee2e6;
    vertical-align: middle;
}
#student_email_list thead th {
    position: sticky; top: 0; z-index: 1;
    background: #6c757d; color: #fff;
    font-weight: 600; white-space: nowrap;
}
#student_email_list thead th:nth-child(8),
#student_email_list thead th:nth-child(9) { white-space: normal; min-width: 7rem; text-align: center; }
#student_email_list td:nth-child(5) { min-width: 12rem; }
#student_email_list thead th:nth-child(8) div {
    margin-top: .3rem; padding-top: .3rem;
    border-top: 1px solid rgba(255,255,255,.4);
    font-weight: normal;
}
#student_email_list thead th:nth-child(8) input { margin-left: .4rem; vertical-align: middle; }
#student_email_list tbody tr:nth-child(even) { background: #f8f9fa; }
#student_email_list tbody tr:hover { background: #e7f1ff; }
#student_email_list tbody tr.not-selected td { color: #adb5bd; }
#student_email_list input[type=checkbox] { width: 1.1rem; height: 1.1rem; cursor: pointer; }
#student_email_list td:nth-child(8),
#student_email_list td:nth-child(9) { text-align: center; }
.feedback-sent, .feedback-not-sent {
    display: inline-block; padding: .2em .6em; border-radius: 1rem;
    font-size: .8rem; font-weight: 600; white-space: nowrap;
}
.feedback-sent     { background: #d1e7dd; color: #0f5132; }
.feedback-not-sent { background: #fff3cd; color: #664d03; }

/* ── Progress log ──────────────────────────────────────────────────── */
#progress-log-wrap { max-height: 55vh; overflow-y: auto; }
#progress-log { font-size: .875rem; }
#progress-log td, #progress-log th { padding: .35rem .55rem; }
</style>

<body style="padding-top:56px;">

<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h6">
            Email Feedback — <?php echo htmlspecialchars("$module_short_description ($module1)"); ?>
            &nbsp;·&nbsp; <?php echo htmlspecialchars($assess_part_description_text); ?>
            &nbsp;·&nbsp; <?php echo htmlspecialchars($year_begin); ?>
        </span>
        <ul class="nav">
            <li class="nav-item"><a target="_self" href="../projectmarking_select_bs.php?pm_id=<?= $pm_id ?>" class="nav-link px-2 text-white-50 small">Module page</a></li>
            <li class="nav-item"><a target="_self" href="../session_modules_bs.php?year_begin=<?= urlencode($year_begin) ?>" class="nav-link px-2 text-white-50 small">Year list</a></li>
            <li class="nav-item"><a target="_self" href="./edit_module_settings.php?pm_id=<?= $pm_id ?>" class="nav-link px-2 text-white-50 small">Module admin</a></li>
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50 small">Admin home</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid py-3">

<?php if (!$has_access): ?>
    <div class="alert alert-danger">
        You do not have the required admin level.
        <a href="../index.php" class="alert-link">Go back</a>.
    </div>

<?php elseif ($POST_OP === 'send'): ?>
    <!-- ── Real-time progress panel ──────────────────────────────────────── -->
    <?php
        $job    = $_SESSION['email_feedback_job'] ?? [];
        $is_dry = empty($job['reallysend']);
        $has_pdf = !empty($job['makepdf']);
        $n_checked = count($job['checked_sids'] ?? []);
    ?>
    <div class="row g-3">
        <div class="col-12">
            <div class="alert <?= $is_dry ? 'alert-warning' : 'alert-info' ?> mb-0 py-2">
                <?php if ($is_dry): ?>
                    <strong>Dry run</strong> — emails will <em>not</em> actually be sent.
                    <?= $has_pdf ? 'PDFs will be generated.' : 'No PDFs.' ?>
                <?php else: ?>
                    <strong>Live send</strong> — emails will be sent for <?= $n_checked ?> selected student(s).
                    <?= $has_pdf ? 'PDFs will be generated and attached.' : 'No PDF attachment.' ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center gap-2">
                    <span id="status-label" class="fw-bold">Preparing…</span>
                    <span id="status-count" class="text-muted small ms-auto"></span>
                </div>
                <div class="card-body pb-1">
                    <div class="progress mb-3" style="height:1.4rem;">
                        <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                             role="progressbar" style="width:0%; min-width:2.5rem;" data-total="0">0%</div>
                    </div>
                    <div id="progress-log-wrap">
                        <table id="progress-log" class="table table-sm table-hover mb-0">
                            <thead class="table-secondary">
                                <tr>
                                    <th style="width:3rem">#</th>
                                    <th>Student</th>
                                    <th>Email</th>
                                    <th>Result</th>
                                </tr>
                            </thead>
                            <tbody id="log-body"></tbody>
                        </table>
                    </div>
                </div>
                <div id="summary-footer" class="card-footer d-none">
                    <div class="d-flex gap-4 align-items-center flex-wrap">
                        <span class="text-success fw-bold"><i class="bi bi-check-circle"></i> Sent: <span id="cnt-sent">0</span></span>
                        <span class="text-warning"><i class="bi bi-skip-forward"></i> Skipped: <span id="cnt-skipped">0</span></span>
                        <span class="text-danger"><i class="bi bi-x-circle"></i> Errors: <span id="cnt-errors">0</span></span>
                        <a href="?pm_id=<?= $pm_id ?>&part=<?= urlencode($part) ?>&op=select&summary_mark=<?= $summary_mark ?>"
                           class="btn btn-sm btn-outline-secondary ms-auto">← Back to form</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const pmId = <?= json_encode($pm_id) ?>;
        const part = <?= json_encode($part) ?>;

        const bar      = document.getElementById('progress-bar');
        const logBody  = document.getElementById('log-body');
        const logWrap  = document.getElementById('progress-log-wrap');
        const label    = document.getElementById('status-label');
        const countEl  = document.getElementById('status-count');
        const footer   = document.getElementById('summary-footer');

        const STATUS = {
            sent:      ['text-success', '✓ Sent'],
            dryrun:    ['text-primary',  '— Dry run'],
            unchecked: ['text-muted',    '— Not selected'],
            unmarked:  ['text-warning',  '⚠ Not yet marked'],
            error:     ['text-danger',   '✗ Error'],
        };

        const es = new EventSource(
            '<?= $_SERVER['PHP_SELF'] ?>?op=stream&pm_id=' + pmId + '&part=' + encodeURIComponent(part));

        es.onmessage = function (e) {
            const d = JSON.parse(e.data);

            if (d.type === 'error') {
                es.close();
                label.textContent = 'Error';
                label.classList.add('text-danger');
                footer.classList.remove('d-none');
                footer.innerHTML = '<span class="text-danger"><strong>Error:</strong> ' +
                    d.msg.replace(/</g, '&lt;') + '</span>';
                return;
            }

            if (d.type === 'start') {
                bar.setAttribute('data-total', d.total);
                label.textContent = 'Sending…';
                countEl.textContent = '0 / ' + d.total;
                return;
            }

            if (d.type === 'progress') {
                const total = parseInt(bar.getAttribute('data-total') || '1');
                const pct = Math.round(d.n / total * 100);
                bar.style.width = pct + '%';
                bar.textContent = pct + '%';
                countEl.textContent = d.n + ' / ' + total;

                const [cls, text] = STATUS[d.status] || ['text-secondary', d.status];
                const tr = document.createElement('tr');
                tr.innerHTML =
                    '<td>' + d.n + '</td>' +
                    '<td>' + d.student.replace(/</g, '&lt;') +
                        ' <small class="text-muted">(' + d.username.replace(/</g, '&lt;') + ')</small></td>' +
                    '<td><small class="text-muted">' + (d.detail || '').replace(/</g, '&lt;') + '</small></td>' +
                    '<td class="' + cls + ' fw-semibold">' + text + '</td>';
                logBody.appendChild(tr);
                // scroll new row into view
                logWrap.scrollTop = logWrap.scrollHeight;
                return;
            }

            if (d.type === 'done') {
                es.close();
                bar.classList.remove('progress-bar-animated');
                bar.style.width = '100%';
                bar.textContent = '100%';
                label.textContent = 'Complete';
                document.getElementById('cnt-sent').textContent    = d.sent;
                document.getElementById('cnt-skipped').textContent = d.skipped;
                document.getElementById('cnt-errors').textContent  = d.errors;
                footer.classList.remove('d-none');
            }
        };

        es.onerror = function () {
            es.close();
            label.textContent = 'Connection lost';
            label.classList.add('text-danger');
            footer.classList.remove('d-none');
            if (!footer.querySelector('.text-danger')) {
                footer.insertAdjacentHTML('afterbegin',
                    '<span class="text-danger me-3"><strong>Stream ended unexpectedly.</strong></span>');
            }
        };
    })();
    </script>

<?php elseif ($POST_OP === 'select'): ?>
    <!-- ── Selection form ────────────────────────────────────────────────── -->
    <div class="mb-2">
        <h5 class="mb-0"><?= htmlspecialchars("$module_short_description ($module_short_code), year $year_begin") ?></h5>
        <p class="text-muted mb-0 small">
            Feedback for: <strong><?= htmlspecialchars($assess_part_description_text) ?></strong>
            <?= htmlspecialchars($summary_desc) ?>
        </p>
    </div>

    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?pm_id=<?= $pm_id ?>&part=<?= urlencode($part) ?>">
        <input type="hidden" name="op"           value="send">
        <input type="hidden" name="module1"      value="<?= htmlspecialchars($module1) ?>">
        <input type="hidden" name="year_begin"   value="<?= htmlspecialchars($year_begin) ?>">
        <input type="hidden" name="summary_mark" value="<?= $summary_mark ?>">

        <div class="row g-3 mb-3">

            <!-- Email body -->
            <div class="col-lg-8">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Covering email text</div>
                    <div class="card-body pb-1">
                        <label for="email_body_text" class="form-label small text-muted">
                            HTML is allowed. This text goes in the email body before the attachment.
                        </label>
                        <textarea class="form-control font-monospace" style="font-size:.85rem;"
                                  rows="12" name="email_body_text" id="email_body_text"><?= htmlspecialchars($default_email_body) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Settings -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header fw-semibold">Send settings</div>
                    <div class="card-body">

                        <div class="mb-3">
                            <label for="subjectline" class="form-label fw-semibold small">Email subject line</label>
                            <input class="form-control" type="text" name="subjectline" id="subjectline"
                                   value="<?= htmlspecialchars($default_subject) ?>">
                        </div>

                        <div class="card bg-light border-warning mb-3">
                            <div class="card-body py-2">
                                <p class="mb-2 fw-bold small">Both options below must be ticked to send live emails with PDFs attached.</p>
                                <div class="form-check mb-1">
                                    <input class="form-check-input" type="checkbox"
                                           name="reallysend" id="reallysend" value="reallysend">
                                    <label class="form-check-label" for="reallysend">
                                        Really send emails
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="makepdf" id="makepdf" value="active">
                                    <label class="form-check-label" for="makepdf">
                                        Generate &amp; attach PDFs
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="cc_module_leader" id="cc_module_leader" value="1" checked>
                                <label class="form-check-label" for="cc_module_leader">
                                    CC email to module leader
                                </label>
                            </div>
                            <div class="text-muted small mt-1 ms-4">
                                <?= htmlspecialchars($module_leader_fullname) ?>
                                &lt;<?= htmlspecialchars($module_leader_email) ?>&gt;
                            </div>
                        </div>

                        <?php if ($summary_mark == 1): ?>
                        <div class="card border-secondary mb-3">
                            <div class="card-header small fw-semibold">Select marks to include</div>
                            <div class="card-body py-2">
                                <ul class="list-unstyled mb-0 small">
                                <?php for ($i = 1; $i <= $num_parts; $i++): ?>
                                    <li class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="show_mark_<?= $i ?>" value="show_mark_<?= $i ?>" checked
                                               id="show_mark_<?= $i ?>">
                                        <label class="form-check-label" for="show_mark_<?= $i ?>">
                                            Part <?= $i ?>: <?= htmlspecialchars($p_description[$i]) ?>
                                            (<?= $p_percent[$i] ?>%)
                                        </label>
                                    </li>
                                <?php endfor; ?>
                                    <li class="text-muted mt-1">Plus the overall mark.</li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary w-100">
                            Send feedback emails →
                        </button>
                    </div>
                </div>
            </div>
        </div><!-- /row -->

        <!-- Student list -->
        <div class="card">
            <div class="card-header fw-semibold d-flex align-items-center gap-2">
                Students
                <span class="text-muted fw-normal small ms-1">
                    — tick all students who should receive the email
                </span>
                <span class="text-muted fw-normal small ms-auto">
                    Only students with a recorded mark will receive an email.
                </span>
            </div>
            <div class="card-body p-0">
                <div id="students_select_table">
                    <?= GetStudentListTable($db, $project_allocated, $students, $staff2,
                                           $feedback_records, $part) ?>
                </div>
            </div>
        </div>

    </form>

<?php else: ?>
    <!-- ── No op — show a redirect hint ─────────────────────────────────── -->
    <div class="alert alert-secondary">
        No operation selected.
        <?php if ($pm_id): ?>
            <a href="?pm_id=<?= $pm_id ?>&part=<?= urlencode($part) ?>&op=select">
                Go to the email form</a>.
        <?php endif; ?>
    </div>
<?php endif; ?>

</div><!-- /container -->

<script>
/* Select / de-select all students and grey out unticked rows */
document.addEventListener('DOMContentLoaded', function () {
    var table    = document.getElementById('student_email_list');
    if (!table) return;
    var selectAll = document.getElementById('select-all');
    var boxes     = table.querySelectorAll('tbody input[type=checkbox]');

    function refresh() {
        var ticked = 0;
        boxes.forEach(function (b) {
            b.closest('tr').classList.toggle('not-selected', !b.checked);
            if (b.checked) ticked++;
        });
        if (selectAll) {
            selectAll.checked       = ticked === boxes.length;
            selectAll.indeterminate = ticked > 0 && ticked < boxes.length;
        }
    }
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            boxes.forEach(function (b) { b.checked = selectAll.checked; });
            refresh();
        });
    }
    boxes.forEach(function (b) { b.addEventListener('change', refresh); });
    refresh();
});
</script>

</body>
</html>
