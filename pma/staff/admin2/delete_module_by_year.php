<?php
(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');
(@include_once('../csrf_functions.php')) or die('Cannot find this file to include: csrf_functions.php<BR>');
(@include_once('./utility_functions.php')) or die('Cannot find this file to include: utility_functions.php<BR>');
(@include_once('./adminusers.php')) or die('Cannot find this file to include: adminusers.php<BR>');

/**
 * Build the list of all database tables belonging to one module row from projects_setup.
 * Mirrors the table-naming logic in setup_tables.php.
 */
function get_module_tables(array $row): array {
    $sc = strtolower($row['short_code']);
    $yb = $row['year_begin'];
    $ye = $row['year_end'];

    $tables = [
        "students_{$sc}_{$yb}",
        "students_programmes_{$sc}_{$yb}",
        "select_{$sc}_{$yb}",
        "alloc_{$sc}_{$yb}",
        "allocated_{$sc}_{$yb}",
        "allocated_{$sc}_{$yb}_staff",
        "allocated_all_{$yb}_staff",
    ];

    for ($i = 1; $i <= 5; $i++) {
        for ($j = 1; $j <= 3; $j++) {
            $tables[] = "assess_{$sc}_{$ye}_p{$i}_m{$j}";
        }
        $tables[] = "assessqs_cats_{$sc}_{$ye}_p{$i}";
        $tables[] = "assessqs_{$sc}_{$ye}_p{$i}";
    }

    $tables[] = "assess_total_{$sc}_{$ye}";
    $tables[] = "master_name_{$sc}_{$yb}";
    $tables[] = "project_details_{$sc}_{$yb}";
    $tables[] = "staff_inactive_{$sc}_{$yb}";
    $tables[] = "staff_admin_{$sc}_{$yb}";
    $tables[] = "projects_areas_{$sc}_{$yb}";
    $tables[] = "feedback_records_{$sc}_{$yb}";
    $tables[] = "staff_names_{$sc}_{$yb}";

    return $tables;
}

// Admin check - super-admin level > 1 required for year-level deletion
$this_staff_username = get_server_username();
$admin_level = GetSuperAdminLevel($db, $this_staff_username);
$is_super_admin = ($admin_level > 1);

// ── AJAX: list all modules for a year ──────────────────────────────────────
if (isset($_GET['op']) && $_GET['op'] === 'list_year_modules') {
    header('Content-Type: application/json');
    if (!$is_super_admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit();
    }
    $year = (int)($_GET['year'] ?? 0);
    if ($year === 0) {
        echo json_encode(['error' => 'No year specified']);
        exit();
    }
    try {
        $stmt = $db->prepare("SELECT * FROM projects_setup WHERE year_begin=:year ORDER BY module_short_desc");
        $stmt->execute([':year' => $year]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        error_log('delete_module_by_year [list]: ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
        exit();
    }
    $result = [];
    foreach ($rows as $row) {
        $label = $row['module_prefix'] . $row['module'] . ' ' . $row['module_short_desc'] . ' (' . $row['short_code'] . ')';
        $result[] = [
            'id'     => (int)$row['id'],
            'name'   => htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
            'tables' => get_module_tables($row),
        ];
    }
    echo json_encode($result);
    exit();
}

// ── AJAX: delete all modules for a year ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['op']) && $_POST['op'] === 'delete_year_modules') {
    header('Content-Type: application/json');
    if (!$is_super_admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied']);
        exit();
    }
    if (!validate_csrf_token()) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF validation failed']);
        exit();
    }
    $year = (int)($_POST['year'] ?? 0);
    if ($year === 0) {
        echo json_encode(['error' => 'No year specified']);
        exit();
    }
    $confirm_text = trim($_POST['confirm_text'] ?? '');
    $expected = 'DELETE ' . $year;
    if ($confirm_text !== $expected) {
        echo json_encode(['error' => "Confirmation text did not match. Expected exactly: $expected"]);
        exit();
    }

    try {
        $stmt = $db->prepare("SELECT * FROM projects_setup WHERE year_begin=:year ORDER BY module_short_desc");
        $stmt->execute([':year' => $year]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        error_log('delete_module_by_year [fetch for delete]: ' . $ex->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Database error fetching modules']);
        exit();
    }

    $log = [];
    foreach ($modules as $row) {
        $pm_id = (int)$row['id'];
        $module_label = htmlspecialchars($row['module_short_desc'] . ' (' . $row['short_code'] . ')', ENT_QUOTES, 'UTF-8');
        $entry = ['module' => $module_label, 'tables' => []];

        foreach (get_module_tables($row) as $tbl) {
            try {
                $db->query("DROP TABLE IF EXISTS `$tbl`");
                $entry['tables'][] = ['name' => $tbl, 'ok' => true];
            } catch (PDOException $ex) {
                error_log("delete_module_by_year DROP $tbl: " . $ex->getMessage());
                $entry['tables'][] = ['name' => $tbl, 'ok' => false, 'msg' => $ex->getMessage()];
            }
        }

        try {
            $s = $db->prepare("DELETE FROM projects_setup WHERE id=:id");
            $s->execute([':id' => $pm_id]);
            $entry['tables'][] = ['name' => "projects_setup (id=$pm_id)", 'ok' => true];
        } catch (PDOException $ex) {
            error_log("delete_module_by_year DELETE projects_setup id=$pm_id: " . $ex->getMessage());
            $entry['tables'][] = ['name' => "projects_setup (id=$pm_id)", 'ok' => false, 'msg' => $ex->getMessage()];
        }

        try {
            $s = $db->prepare("DELETE FROM projects_setup_proposals WHERE proj_id=:id");
            $s->execute([':id' => $pm_id]);
            $entry['tables'][] = ['name' => "projects_setup_proposals (proj_id=$pm_id)", 'ok' => true];
        } catch (PDOException $ex) {
            error_log("delete_module_by_year DELETE projects_setup_proposals proj_id=$pm_id: " . $ex->getMessage());
            $entry['tables'][] = ['name' => "projects_setup_proposals (proj_id=$pm_id)", 'ok' => false, 'msg' => $ex->getMessage()];
        }

        $log[] = $entry;
    }

    echo json_encode(['success' => true, 'year' => $year, 'log' => $log]);
    exit();
}

// ── Main page ──────────────────────────────────────────────────────────────
$years = [];
try {
    $results = $db->query("SELECT DISTINCT year_begin FROM projects_setup ORDER BY year_begin ASC");
    $years = $results->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $ex) {
    error_log('delete_module_by_year [years query]: ' . $ex->getMessage());
}

$self_url = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">Delete all modules by year</span>
        <ul class="nav justify-content-center">
            <li class="nav-item"><a target="_self" href="./index.php" class="nav-link px-2 text-white-50">Main admin page</a></li>
        </ul>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container mt-3">

<?php if (!$is_super_admin): ?>
            <div class="alert alert-danger">
                <strong>Access denied.</strong> Year-level module deletion requires super-admin access.
            </div>
            <a href="./index.php">Go back to the main admin page</a>

<?php else: ?>

            <div class="alert alert-warning">
                <strong>Warning:</strong> This page permanently deletes <em>all</em> modules and their data for an entire academic year.
                Dropped database tables cannot be recovered. Use with extreme caution.
            </div>

            <!-- Step 1: select year -->
            <div class="card mb-3">
                <div class="card-header bg-secondary text-white"><strong>Step 1 &mdash; Select academic year</strong></div>
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label for="year-select" class="form-label">Year (oldest first):</label>
                            <select id="year-select" class="form-select">
                                <option value="">-- choose year --</option>
<?php foreach ($years as $y): ?>
                                <option value="<?php echo (int)$y; ?>"><?php echo (int)$y; ?></option>
<?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <button id="btn-preview" class="btn btn-primary">Preview modules to be deleted</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: preview panel -->
            <div class="card mb-3" id="preview-card" style="display:none;">
                <div class="card-header bg-info text-white"><strong>Step 2 &mdash; Modules and tables that will be deleted</strong></div>
                <div class="card-body" id="preview-panel"></div>
            </div>

            <!-- Step 3: confirm and delete -->
            <div class="card mb-3 border-danger" id="confirm-section" style="display:none;">
                <div class="card-header bg-danger text-white"><strong>Step 3 &mdash; Confirm deletion</strong></div>
                <div class="card-body">
                    <p>
                        To permanently delete all modules for year <strong id="confirm-year"></strong>,
                        type exactly <code id="confirm-hint"></code> in the box below and click the delete button.
                    </p>
                    <div class="row g-2 align-items-end">
                        <div class="col-auto">
                            <input type="text" id="confirm-input" class="form-control" placeholder="Type confirmation here" autocomplete="off">
                        </div>
                        <div class="col-auto">
                            <button id="btn-delete" class="btn btn-danger">Delete all modules for this year &mdash; PERMANENT</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Results -->
            <div id="delete-result"></div>

<?php endif; ?>

        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->

<script>
$(document).ready(function () {
    var ajaxUrl = '<?php echo $self_url; ?>';

    function resetConfirm() {
        $('#confirm-section').hide();
        $('#delete-result').empty();
    }

    $('#year-select').on('change', function () {
        $('#preview-card').hide();
        $('#preview-panel').empty();
        resetConfirm();
    });

    $('#btn-preview').on('click', function () {
        var year = $('#year-select').val();
        if (!year) {
            alert('Please select a year first.');
            return;
        }
        resetConfirm();
        $('#preview-card').show();
        $('#preview-panel').html('<p class="text-muted">Loading&hellip;</p>');

        $.get(ajaxUrl, {op: 'list_year_modules', year: year})
            .done(function (data) {
                if (data.error) {
                    $('#preview-panel').html('<div class="alert alert-danger">' + data.error + '</div>');
                    return;
                }
                if (data.length === 0) {
                    $('#preview-panel').html('<div class="alert alert-info">No modules found for year ' + year + '.</div>');
                    return;
                }
                var html = '<p><strong>' + data.length + ' module(s) will be deleted along with all their data tables.</strong></p>';
                $.each(data, function (idx, mod) {
                    var tableItems = $.map(mod.tables, function (t) {
                        return '<li class="font-monospace small">' + t + '</li>';
                    }).join('');
                    html += '<div class="card mb-2">';
                    html += '<div class="card-header" data-bs-toggle="collapse" data-bs-target="#mod' + idx + '" style="cursor:pointer">';
                    html += '<strong>' + mod.name + '</strong> <span class="text-muted small">(' + mod.tables.length + ' tables &mdash; click to expand)</span>';
                    html += '</div>';
                    html += '<div class="collapse" id="mod' + idx + '">';
                    html += '<div class="card-body p-2"><ul class="mb-0">' + tableItems + '</ul></div>';
                    html += '</div></div>';
                });
                $('#preview-panel').html(html);

                var hint = 'DELETE ' + year;
                $('#confirm-year').text(year);
                $('#confirm-hint').text(hint);
                $('#confirm-input').val('').prop('disabled', false);
                $('#btn-delete').prop('disabled', false);
                $('#confirm-section').show();
            })
            .fail(function () {
                $('#preview-panel').html('<div class="alert alert-danger">Request failed. Please try again.</div>');
            });
    });

    $('#btn-delete').on('click', function () {
        var year    = $('#year-select').val();
        var confirm_text = $('#confirm-input').val();
        if (!year) { return; }

        $('#btn-delete').prop('disabled', true);
        $('#delete-result').html('<p class="text-muted">Deleting&hellip; please wait.</p>');

        $.post(ajaxUrl, {
            op:           'delete_year_modules',
            year:         year,
            confirm_text: confirm_text
        })
        .done(function (data) {
            $('#btn-delete').prop('disabled', false);
            if (data.error) {
                $('#delete-result').html('<div class="alert alert-danger">' + data.error + '</div>');
                return;
            }
            var html = '<div class="alert alert-success"><strong>Deletion complete for year ' + data.year + '.</strong></div>';
            $.each(data.log, function (i, mod) {
                html += '<div class="card mb-2">';
                html += '<div class="card-header"><strong>' + mod.module + '</strong></div>';
                html += '<div class="card-body p-2"><ul class="mb-0">';
                $.each(mod.tables, function (j, t) {
                    var cls = t.ok ? 'text-success' : 'text-danger';
                    var status = t.ok ? 'deleted' : ('error: ' + (t.msg || 'unknown'));
                    html += '<li class="font-monospace small ' + cls + '">' + t.name + ' &mdash; ' + status + '</li>';
                });
                html += '</ul></div></div>';
            });
            $('#delete-result').html(html);

            // Remove the deleted year from the selector
            $('#year-select option[value="' + year + '"]').remove();
            $('#year-select').val('');
            $('#preview-card').hide();
            $('#preview-panel').empty();
            $('#confirm-section').hide();
        })
        .fail(function (xhr) {
            $('#btn-delete').prop('disabled', false);
            var msg = 'Request failed.';
            try {
                var r = JSON.parse(xhr.responseText);
                if (r.error) { msg = r.error; }
            } catch (e) {}
            $('#delete-result').html('<div class="alert alert-danger">' + msg + '</div>');
        });
    });
});
</script>
</body>
</html>
