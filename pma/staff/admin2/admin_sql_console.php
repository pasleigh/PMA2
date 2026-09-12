<?php
// SQL console for trusted super-admin use only.
// Deliberately not linked from any menu/nav - reachable only if the URL is known.
//
// Security model:
//  - Same admin-level gate as the rest of admin2 just to display the page.
//  - A SEPARATE console password (bcrypt hash in config_main.php as
//    $hash_to_test_sql_console_against) must be entered afresh with every
//    single request that could execute a query - it is never cached in the
//    session, so there is no standing "logged in" state to fire further
//    queries from (that was the flaw in the old submit.php: the password
//    check and the query-execution step were two independent requests, so
//    op=submit_sql could be posted directly without ever passing the
//    password check).
//  - CSRF is enforced application-wide by require_valid_csrf() inside
//    setup_tables.php (included below) for every POST request.
//  - Only POST can execute a query; GET only ever (re)draws the form, so a
//    query can never be triggered via a URL/GET request or leak into logs.
//  - Repeated wrong console passwords trip a session-based, exponentially
//    increasing lockout to slow down online guessing.
//  - A hierarchy of extra checks applies on top of the password, based on
//    how dangerous the statement looks (see classify_sql_console_query()):
//      * SELECT / SHOW / EXPLAIN / DESCRIBE   -> run immediately.
//      * INSERT / UPDATE / DELETE / REPLACE   -> one "are you sure" step.
//      * UPDATE / DELETE with no WHERE clause -> a warning plus a second,
//                                                 separate confirmation step.
//      * TRUNCATE / DROP / ALTER / CREATE /
//        RENAME (and anything not recognised) -> must type the target
//                                                 table name to proceed.
//    Every step re-parses the submitted SQL text itself rather than
//    trusting a hidden "which tier is this" field, so the confirmation
//    step actually shown always matches the query that would be run.
//  - Only a single SQL statement may be submitted at a time, so a
//    dangerous statement can't be smuggled in after a harmless-looking
//    first one (e.g. "SELECT 1; DROP TABLE x;").
//  - All query text and output is HTML-escaped before being echoed back.
//  - Every attempt (successful or not) is written to an audit log file.

(@include_once('./set_dir_admin.php')) or die('Cannot find this file to include: set_dir_admin.php<BR>');
(@include_once("$STAFF_DIR_VAL/config.php")) or die('Cannot find this file to include: config.php<BR>');
(@include_once('../setup_titles.php')) or die('Cannot find this file to include: setup_titles.php<BR>');

(@include_once('../connect_pdo.php')) or die('Cannot connect to the database<BR>');
(@include_once('./admin_functions.php')) or die('Cannot include admin functions<BR>');
(@include_once('../setup_tables.php')) or die('Cannot setup database tables<BR>'); // also enforces CSRF on POST
(@include_once('../staff_functions.php')) or die('Cannot open staff_functions.php<BR>');
(@include_once('../markstore_functions.php')) or die('Cannot open staff_functions.php<BR>');

// ---------------------------------------------------------------------
// SQL classification helpers
// ---------------------------------------------------------------------

const SQL_CONSOLE_SAFE_COMMANDS   = ['SELECT', 'SHOW', 'EXPLAIN', 'DESCRIBE', 'DESC'];
const SQL_CONSOLE_MUTATE_COMMANDS = ['INSERT', 'UPDATE', 'DELETE', 'REPLACE'];
const SQL_CONSOLE_NOWHERE_COMMANDS = ['UPDATE', 'DELETE'];
const SQL_CONSOLE_SCHEMA_COMMANDS = ['TRUNCATE', 'DROP', 'ALTER', 'CREATE', 'RENAME'];

/**
 * Strip leading whitespace and SQL comments so the real first keyword of a
 * statement can be found even if it is preceded by a comment.
 */
function sql_console_strip_leading_comments(string $sql): string {
    $sql = ltrim($sql);
    while (true) {
        if (preg_match('/^--[^\n]*\n?/', $sql)) {
            $sql = preg_replace('/^--[^\n]*\n?/', '', $sql, 1);
        } elseif (preg_match('/^#[^\n]*\n?/', $sql)) {
            $sql = preg_replace('/^#[^\n]*\n?/', '', $sql, 1);
        } elseif (preg_match('#^/\*.*?\*/#s', $sql)) {
            $sql = preg_replace('#^/\*.*?\*/#s', '', $sql, 1);
        } else {
            break;
        }
        $sql = ltrim($sql);
    }
    return $sql;
}

/**
 * Return the uppercased first keyword of a SQL statement, e.g. 'SELECT'.
 */
function sql_console_first_keyword(string $sql): string {
    $sql = sql_console_strip_leading_comments($sql);
    return preg_match('/^([A-Za-z]+)/', $sql, $m) ? strtoupper($m[1]) : '';
}

/**
 * Best-effort check for a top-level WHERE clause. Not a full parser, but
 * good enough to flag the common "forgot the WHERE" accident.
 */
function sql_console_has_where(string $sql): bool {
    return (bool)preg_match('/\bWHERE\b/i', $sql);
}

/**
 * Detect whether the submitted text contains more than one SQL statement,
 * respecting '...' "..." `...` quoting and -- / # / slash-star comments, so
 * a second, undisclosed statement can't ride along after a semicolon.
 * A single trailing semicolon (optionally followed by whitespace/comments)
 * is allowed.
 */
function sql_console_has_multiple_statements(string $sql): bool {
    $len = strlen($sql);
    $in_quote = null;
    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        if ($in_quote !== null) {
            if ($ch === '\\' && $in_quote !== '`') {
                $i++;
                continue;
            }
            if ($ch === $in_quote) {
                if ($i + 1 < $len && $sql[$i + 1] === $in_quote) {
                    $i++;
                    continue;
                }
                $in_quote = null;
            }
            continue;
        }
        if ($ch === "'" || $ch === '"' || $ch === '`') {
            $in_quote = $ch;
            continue;
        }
        if ($ch === ';') {
            $rest = sql_console_strip_leading_comments(substr($sql, $i + 1));
            return $rest !== '';
        }
    }
    return false;
}

/**
 * Try to pull the target table name out of a schema-changing statement.
 * Returns null if it can't be determined (caller falls back to a fixed
 * confirmation word in that case).
 */
function sql_console_extract_target_table(string $keyword, string $sql): ?string {
    $patterns = [
        'TRUNCATE' => '/TRUNCATE\s+(?:TABLE\s+)?`?(\w+)`?/i',
        'DROP'     => '/DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?`?(\w+)`?/i',
        'ALTER'    => '/ALTER\s+TABLE\s+`?(\w+)`?/i',
        'CREATE'   => '/CREATE\s+(?:OR\s+REPLACE\s+)?TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i',
        'RENAME'   => '/RENAME\s+TABLE\s+`?(\w+)`?/i',
    ];
    if (isset($patterns[$keyword]) && preg_match($patterns[$keyword], $sql, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * Append one line to the SQL console audit log, creating the log
 * directory if it does not exist yet.
 */
function sql_console_audit_log(string $line): void {
    $log_file = __DIR__ . '/../../logs/sql_console.log';
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        @mkdir($log_dir, 0750, true);
    }
    @file_put_contents($log_file, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Render the hidden/hidden-ish fields that must accompany every stage of
 * the confirmation flow: CSRF token, op, and the SQL text itself (shown
 * read-only once we're past the first stage).
 */
function sql_console_render_query_box(string $query, bool $readonly): string {
    $attr = $readonly ? " readonly style='background:#f5f5f5'" : '';
    return "<div class='mb-2'><strong>SQL statement:</strong><br>"
        . "<textarea rows='10' cols='100' name='text_input_sql' required$attr>"
        . htmlspecialchars($query, ENT_QUOTES, 'UTF-8')
        . "</textarea></div>\n";
}

function sql_console_render_password_field(): string {
    return "<p>Console password (required for every step): "
        . "<input type='password' name='sql_console_pwd' size='30' maxlength='100' autocomplete='off' required></p>\n";
}

$access = access_check_response($db, $this_staff_username, $this_staff_full_name, $staff_names, $access_phrase);

$display_block = '';
$display_block .= $connect_msg;
$display_block .= "<h2 style='font-size: x-large; color: darkgoldenrod'>" . htmlspecialchars($access_phrase, ENT_QUOTES, 'UTF-8') . "</h2>\n";
$display_block .= "<h2 class='text-danger'>SQL Console - System admins only. Use with extreme care.</h2>\n";
$instructions  = <<<EO_INS
                 <div class="container">
                        <div class="alert alert-danger">
                            <strong>This runs SQL directly against the live database.</strong> There is no undo.
                            <ul class="mb-0">
                                <li>Only one statement can be run at a time.</li>
                                <li><code>SELECT</code>/<code>SHOW</code>/<code>EXPLAIN</code>/<code>DESCRIBE</code> run immediately.</li>
                                <li><code>INSERT</code>/<code>UPDATE</code>/<code>DELETE</code>/<code>REPLACE</code> ask you to confirm first; an <code>UPDATE</code>/<code>DELETE</code> with no <code>WHERE</code> clause needs an extra confirmation.</li>
                                <li><code>TRUNCATE</code>/<code>DROP</code>/<code>ALTER</code>/<code>CREATE</code>/<code>RENAME</code> require you to type the table name to confirm.</li>
                                </ul>
                        </div>
                    </div>
EO_INS;
$display_block .= $instructions;

if ($access) {
    if (empty($_SESSION['sql_console_fail_count'])) {
        $_SESSION['sql_console_fail_count'] = 0;
    }
    if (empty($_SESSION['sql_console_lock_until'])) {
        $_SESSION['sql_console_lock_until'] = 0;
    }
    $locked_out = time() < $_SESSION['sql_console_lock_until'];

    $is_post = ($_SERVER['REQUEST_METHOD'] === 'POST');
    $op = $is_post ? ($_POST['op'] ?? null) : null;

    // Everything needed to resume wherever the confirmation flow left off.
    $query = '';
    $confirm_step = '';
    $confirm_table_name = '';
    $confirm_nowhere_ack = false;
    $submitted_pwd = '';

    if ($is_post && $op === 'submit_sql') {
        $submitted_pwd = (string)($_POST['sql_console_pwd'] ?? '');
        $query = trim((string)($_POST['text_input_sql'] ?? ''));
        $confirm_step = (string)($_POST['confirm_step'] ?? '');
        $confirm_table_name = trim((string)($_POST['confirm_table_name'] ?? ''));
        $confirm_nowhere_ack = !empty($_POST['confirm_nowhere_ack']);
        $now = date('Y-m-d H:i:s');

        if ($locked_out) {
            $wait = $_SESSION['sql_console_lock_until'] - time();
            $display_block .= "<div class='alert alert-danger'>Too many incorrect console password attempts. Try again in {$wait}s.</div>\n";
            // Don't try to resume the flow while locked out - back to a clean slate.
            $query = '';
            $confirm_step = '';
        } elseif ($query === '') {
            $display_block .= "<div class='alert alert-warning'>No SQL command entered.</div>\n";
        } elseif (sql_console_has_multiple_statements($query)) {
            $display_block .= "<div class='alert alert-danger'>Only one SQL statement may be run at a time.</div>\n";
            $confirm_step = ''; // force back to stage 1 for whatever they typed
        } elseif ($submitted_pwd === '' || !password_verify($submitted_pwd, $hash_to_test_sql_console_against)) {
            $_SESSION['sql_console_fail_count']++;
            $delay = min(300, 2 ** min($_SESSION['sql_console_fail_count'], 8));
            $_SESSION['sql_console_lock_until'] = time() + $delay;
            sql_console_audit_log("[$now] [$this_staff_username] [BAD PASSWORD]" . PHP_EOL);
            $display_block .= "<div class='alert alert-danger'>Incorrect console password. This attempt has been logged.</div>\n";
            // Keep them on the same stage so they only have to retype the password.
        } else {
            // Password correct for this request - reset the lockout.
            $_SESSION['sql_console_fail_count'] = 0;
            $_SESSION['sql_console_lock_until'] = 0;

            $keyword = sql_console_first_keyword($query);
            $query_for_log = preg_replace('/\s+/', ' ', $query);

            $do_execute = false;
            $tier_label = 'OTHER';

            if (in_array($keyword, SQL_CONSOLE_SAFE_COMMANDS, true)) {
                $tier_label = 'SAFE';
                $do_execute = true;
            } elseif (in_array($keyword, SQL_CONSOLE_MUTATE_COMMANDS, true)) {
                $no_where = in_array($keyword, SQL_CONSOLE_NOWHERE_COMMANDS, true) && !sql_console_has_where($query);
                if ($no_where) {
                    $tier_label = 'MUTATE-NO-WHERE';
                    if ($confirm_step === 'nowhere_final') {
                        $do_execute = true;
                    } elseif ($confirm_step === 'nowhere_ack' && $confirm_nowhere_ack) {
                        $display_block .= "<div class='alert alert-danger'>"
                            . "<strong>Final check:</strong> this $keyword statement has no WHERE clause and will affect "
                            . "<strong>every row</strong> in the table. Re-enter the console password once more to run it.</div>\n";
                        $display_block .= "\n<form method='post' action='" . htmlspecialchars((string)$_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' autocomplete='off'>\n";
                        $display_block .= csrf_token_field();
                        $display_block .= "<input type='hidden' name='op' value='submit_sql'>\n";
                        $display_block .= "<input type='hidden' name='confirm_step' value='nowhere_final'>\n";
                        $display_block .= sql_console_render_query_box($query, true);
                        $display_block .= sql_console_render_password_field();
                        $display_block .= "<p><button type='submit' class='btn btn-danger'>Yes, run this on every row - do it now</button> "
                            . "<a href='" . htmlspecialchars((string)$_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' class='btn btn-secondary'>Cancel</a></p>\n";
                        $display_block .= '</form>';
                    } else {
                        $display_block .= "<div class='alert alert-warning'>"
                            . "<strong>Warning:</strong> this $keyword statement has <u>no WHERE clause</u> and will affect "
                            . "<strong>every row</strong> in the target table. This needs an extra confirmation.</div>\n";
                        $display_block .= "\n<form method='post' action='" . htmlspecialchars((string)$_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' autocomplete='off'>\n";
                        $display_block .= csrf_token_field();
                        $display_block .= "<input type='hidden' name='op' value='submit_sql'>\n";
                        $display_block .= "<input type='hidden' name='confirm_step' value='nowhere_ack'>\n";
                        $display_block .= sql_console_render_query_box($query, true);
                        $display_block .= "<div class='form-check my-2'><input class='form-check-input' type='checkbox' name='confirm_nowhere_ack' value='1' id='nowhere_ack' required>"
                            . "<label class='form-check-label' for='nowhere_ack'>I understand this affects every row in the table and want to continue.</label></div>\n";
                        $display_block .= sql_console_render_password_field();
                        $display_block .= "<p><button type='submit' class='btn btn-warning'>Continue</button> "
                            . "<a href='" . htmlspecialchars((string)$_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' class='btn btn-secondary'>Cancel</a></p>\n";
                        $display_block .= '</form>';
                    }
                } else {
                    $tier_label = 'MUTATE';
                    if ($confirm_step === 'mutate_confirm') {
                        $do_execute = true;
                    } else {
                        $display_block .= "<div class='alert alert-warning'>This is a $keyword statement. Please confirm you want to run it.</div>\n";
                        $display_block .= "\n<form method='post' action='" . htmlspecialchars((string)$_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' autocomplete='off'>\n";
                        $display_block .= csrf_token_field();
                        $display_block .= "<input type='hidden' name='op' value='submit_sql'>\n";
                        $display_block .= "<input type='hidden' name='confirm_step' value='mutate_confirm'>\n";
                        $display_block .= sql_console_render_query_box($query, true);
                        $display_block .= sql_console_render_password_field();
                        $display_block .= "<p><button type='submit' class='btn btn-warning'>Yes, run this</button> "
                            . "<a href='" . htmlspecialchars((string)$_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' class='btn btn-secondary'>Cancel</a></p>\n";
                        $display_block .= '</form>';
                    }
                }
            } else {
                // Schema-changing commands, plus anything unrecognised defaults to
                // the strictest tier: type the table name (or CONFIRM) to proceed.
                $tier_label = in_array($keyword, SQL_CONSOLE_SCHEMA_COMMANDS, true) ? 'SCHEMA' : 'OTHER';
                $target_table = in_array($keyword, SQL_CONSOLE_SCHEMA_COMMANDS, true)
                    ? sql_console_extract_target_table($keyword, $query)
                    : null;
                $expected = $target_table ?? 'CONFIRM';

                if ($confirm_step === 'schema_confirm' && $confirm_table_name !== '' && strcasecmp($expected, $confirm_table_name) === 0) {
                    $do_execute = true;
                } else {
                    if ($confirm_step === 'schema_confirm') {
                        $display_block .= "<div class='alert alert-danger'>That did not match - please type it exactly.</div>\n";
                    }
                    $display_block .= "<div class='alert alert-danger'><strong>" . htmlspecialchars($keyword !== '' ? $keyword : 'This statement', ENT_QUOTES, 'UTF-8')
                        . "</strong> changes database structure and cannot be undone. "
                        . ($target_table !== null
                            ? "Type the target table name (<code>" . htmlspecialchars($target_table, ENT_QUOTES, 'UTF-8') . "</code>) below to confirm."
                            : "The target table name could not be determined automatically - type <code>CONFIRM</code> below to proceed.")
                        . "</div>\n";
                    $display_block .= "\n<form method='post' action='" . htmlspecialchars((string)$_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' autocomplete='off'>\n";
                    $display_block .= csrf_token_field();
                    $display_block .= "<input type='hidden' name='op' value='submit_sql'>\n";
                    $display_block .= "<input type='hidden' name='confirm_step' value='schema_confirm'>\n";
                    $display_block .= sql_console_render_query_box($query, true);
                    $display_block .= "<p>Type <code>" . htmlspecialchars($expected, ENT_QUOTES, 'UTF-8') . "</code> to confirm: "
                        . "<input type='text' name='confirm_table_name' size='40' autocomplete='off' required></p>\n";
                    $display_block .= sql_console_render_password_field();
                    $display_block .= "<p><button type='submit' class='btn btn-danger'>Confirm and run</button> "
                        . "<a href='" . htmlspecialchars((string)$_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' class='btn btn-secondary'>Cancel</a></p>\n";
                    $display_block .= '</form>';
                }
            }

            if ($do_execute) {
                $display_block .= '<p><strong>Query:</strong> ' . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . "</p>\n";
                try {
                    $result = $db->query($query);
                    $display_block .= "<div class='alert alert-success'>Query executed successfully.</div>\n";

                    if ($result instanceof PDOStatement) {
                        if ($result->columnCount() > 0) {
                            $rows = $result->fetchAll(PDO::FETCH_ASSOC);
                            if ($rows) {
                                $display_block .= "<div class='table-responsive'><table class='table table-sm table-bordered table-striped'><thead><tr>\n";
                                foreach (array_keys($rows[0]) as $col) {
                                    $display_block .= '<th>' . htmlspecialchars((string)$col, ENT_QUOTES, 'UTF-8') . '</th>';
                                }
                                $display_block .= "</tr></thead><tbody>\n";
                                foreach ($rows as $row) {
                                    $display_block .= '<tr>';
                                    foreach ($row as $val) {
                                        $display_block .= '<td>' . htmlspecialchars($val === null ? 'NULL' : (string)$val, ENT_QUOTES, 'UTF-8') . '</td>';
                                    }
                                    $display_block .= "</tr>\n";
                                }
                                $display_block .= '</tbody></table></div>' . "\n";
                            } else {
                                $display_block .= "<p>Query returned no rows.</p>\n";
                            }
                        } else {
                            $display_block .= '<p>Rows affected: ' . (int)$result->rowCount() . "</p>\n";
                        }
                    }
                    sql_console_audit_log("[$now] [$this_staff_username] [OK][$tier_label] $query_for_log" . PHP_EOL);
                } catch (PDOException $ex) {
                    $err_msg = $ex->getMessage();
                    $display_block .= "<div class='alert alert-danger'>Error running query: " . htmlspecialchars($err_msg, ENT_QUOTES, 'UTF-8') . "</div>\n";
                    sql_console_audit_log("[$now] [$this_staff_username] [ERROR][$tier_label] $query_for_log | $err_msg" . PHP_EOL);
                }
            }
        }
    }

    // Always offer a fresh stage-1 form at the bottom so another query can be
    // started. Password and SQL text are never pre-filled here.
    $display_block .= "\n<hr><h4>Run a new SQL statement</h4>\n";
    $display_block .= "\n<form method='post' action='" . htmlspecialchars((string)$_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8') . "' autocomplete='off'>\n";
    $display_block .= csrf_token_field();
    $display_block .= "<input type='hidden' name='op' value='submit_sql'>\n";
    $display_block .= sql_console_render_query_box('', false);
    $display_block .= sql_console_render_password_field();
    $display_block .= "<p><input type='submit' id='submitbutton_sql' name='submit' value='Run SQL'></p>\n";
    $display_block .= '</form>';
} else {
    $display_block .= '<h2>You must have full Super Admin level to access the functions of this page</h2><BR>';
}
?>
<!DOCTYPE HTML>
<html>
<?php
(@include_once('mobile_head_admin_bs.php')) or die('Cannot find this file to include: mobile_head_admin_bs.php<BR>');
?>
<body style="padding-top: 56px;">
<nav class="navbar fixed-top navbar-dark text-white bg-secondary">
    <div class="container">
        <span class="navbar-brand">SQL Console (<?php echo htmlspecialchars($this_staff_username, ENT_QUOTES, 'UTF-8'); ?>)</span>
    </div>
</nav>
<div id="wrapper">
    <div id="content">
        <div class="container">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
        <?php echo $display_block; ?>
                    </div>

                </div>
            </div>
        </div>
    </div><!-- /content -->
</div><!-- /wrapper -->
</body>
</html>
