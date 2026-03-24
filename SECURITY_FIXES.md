# PMA2 Security Fixes Log

Generated: 2026-03-24

---

## Completed Fixes

### Fix 1 — Hardcoded Dev Mode Backdoor Removed
**Severity:** Critical
**File:** `pma/staff/index.php`

**Problem:** A URL parameter (`?devmode=cenpas24`) allowed anyone who knew the string to bypass authentication entirely and log in as a named user. Two hardcoded credentials were present in source code.

**What was changed:** Removed the entire `$dev_mode` / `$dev_user` block (lines 14–28) and the subsequent cookie-setting block (lines 35–39). The backdoor no longer exists in the codebase.

---

### Fix 2 — Database Credentials Removed from Source Code
**Severity:** Critical
**Files:** `pma/config_main.php`, `db_credentials.php` (new), `db_credentials.example.php` (new), `.gitignore`

**Problem:** 11 plaintext database passwords for all school deployments were hardcoded directly in `config_main.php`, which is tracked by git. Any repository exposure (accidental push, misconfigured access, CI log leak) would expose all database credentials simultaneously.

**What was changed:**
- Created `db_credentials.php` at the project root (one level above `pma/`) containing all credentials as a PHP array.
- `pma/config_main.php` now loads credentials at runtime via `require __DIR__ . '/../../db_credentials.php'` — no passwords appear in that file.
- Created `db_credentials.example.php` with `CHANGE_ME` placeholders, safe to commit, for developer onboarding.
- Added `db_credentials.php` to `.gitignore` so it can never be accidentally committed.

**Residual risk:** `db_credentials.php` is still under the XAMPP `htdocs` tree. It is a PHP file so its contents will not be served as plain text by Apache, but for maximum safety it should be moved above the web root (e.g. `/Applications/XAMPP/xamppfiles/htdocs/../db_credentials.php`) and the path in `config_main.php` updated accordingly.

---

### Fix 3 — Error Display Disabled in Production
**Severity:** Medium
**File:** `pma/index.php`

**Problem:** `display_errors = 1` and `error_reporting(E_ALL)` caused full PHP errors, stack traces, file paths, and query strings to be rendered in the browser for any visitor. This provides attackers with detailed internal information.

**What was changed:**
```php
// Before
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);
error_reporting(E_ALL);

// After
ini_set('display_startup_errors', 0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
```

Errors are still fully captured (for debugging) but now written to the PHP error log instead of shown to users.

---

## Completed Fixes (continued)

### Fix 4 — Prepared Statements (SQL Injection)
**Severity:** Critical
**Scope:** ~50+ query sites across `staff/admin2/` and `staff/proposals/`

Replacing all raw string-concatenated SQL queries with PDO prepared statements. Key files:

| File | Queries to fix |
|------|---------------|
| `staff/admin2/adminusers.php` | 5 |
| `staff/admin2/edit_admin_users.php` | 2 |
| `staff/admin2/edit_admin_users_module.php` | 6 |
| `staff/admin2/edit_project_areas.php` | 3 |
| `staff/admin2/edit_project_proposals_push_to_db.php` | 4 |
| `staff/admin2/first_use_staff.php` | 3 |
| `staff/admin2/match_associated_programmes.php` | 5 |
| `staff/admin2/upload_header_image.php` | 1 |
| `staff/admin2/upload_student_project_allocation.php` | 15+ |
| `staff/proposals/addproject2.php` | 2 |
| `staff/proposals/modproject2.php` | 2 |

**Pattern applied:**
```php
// Before (vulnerable)
$query = "SELECT * FROM admin_users WHERE username='$username'";
$result = $db->query($query);

// After (safe)
$result = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
$result->execute([$username]);
```

All 11 files listed above have been updated. Dynamic optional-field columns in `addproject2.php` and `modproject2.php` use server-side column names (not user input) with parameterised values.

---

### Fix 5 — Secure Cookie Flags
**Severity:** High
**Files:** `pma/staff/setup_titles.php`, `pma/staff/index.php`, `pma/config_main.php`

Adding `HttpOnly`, `Secure`, and `SameSite=Lax` flags to all `setcookie()` calls (7 call sites). This prevents JavaScript access to cookies (mitigating XSS cookie theft) and restricts transmission to HTTPS.

**Applied to all 7 `setcookie()` calls across `setup_titles.php`, `staff/index.php`, `config_main.php`. Requires HTTPS in production for `secure` flag to take effect.**

---

### Fix 6 — Session-Based Authentication
**Severity:** High
**Files:** `pma/staff/index.php`, `pma/staff/setup_tables.php`, `pma/staff/admin2/ADMINconnect_pdo.php`

Replaced cookie-based identity (`$_COOKIE['USERNAME']`) with PHP sessions (`$_SESSION['USERNAME']`). Cookies can be forged by any client; session values are stored server-side.

**Changes made:**
- `pma/staff/index.php` — `session_start()` added at top; `$_SESSION['USERNAME']` written alongside cookie on login; `$_COOKIE['USERNAME']` fallback replaced with `$_SESSION['USERNAME']`
- `pma/staff/setup_tables.php` — reads from `$_SESSION['USERNAME']`
- `pma/staff/admin2/ADMINconnect_pdo.php` — `session_start()` added; auth check uses `$_SESSION['USERNAME']`

---

---

### Fix 7 — XSS Output Encoding
**Severity:** High
**Files:** `pma/staff/logout.php`, `pma/staff/admin2/upload_header_image.php`, `pma/staff/admin2/upload_test.php`, `pma/staff/index.php`

All user-controlled values (file names, MIME types, usernames, full names, `$_SERVER['PHP_SELF']`, cookie-sourced URLs) are now wrapped in `htmlspecialchars($value, ENT_QUOTES, 'UTF-8')` before output. Numeric values are cast to `(int)`. `upload_test.php` no longer echoes the server temp path.

---

### Fix 8 — Cookie-Controlled Filesystem Paths Removed (LFI)
**Severity:** Critical
**Files:** `set_dir_staff.php`, `set_dir_admin.php`, `student/set_dir_student.php`, `proposals/set_dir_props.php`, `proposals_bs/set_dir_props.php`, `staff/config.php`, `config_main.php`

All five `set_dir_*.php` files now derive directory paths from `__DIR__` (a PHP compile-time constant) instead of reading them from user-controlled cookies. An attacker can no longer manipulate `BASE_DIR`/`STAFF_DIR`/etc. cookies to cause path traversal or arbitrary file inclusion.

`staff/config.php` now reads the school identifier from `$_SESSION['PMA_SCHOOL']` (set by `config_main.php`) with a cookie fallback only for backward compatibility on first load. `config_main.php` stores the school in the session at the same time as the cookie.

`session_start()` guards were added to all `set_dir_*.php` files so the session is available before any subsequent include reads from it.

---

### Fix 9 — CSRF Token Protection
**Severity:** High
**New file:** `pma/staff/csrf_functions.php`
**Files updated:** `setup_tables.php`, `edit_admin_users.php`, `edit_admin_users_module.php`, `edit_project_areas.php`, `match_associated_programmes.php`, `upload_header_image.php`

Created `csrf_functions.php` with `generate_csrf_token()`, `validate_csrf_token()`, `csrf_token_field()`, and `require_valid_csrf()`. The file is included via `setup_tables.php` so the helpers are available in every page.

`require_valid_csrf()` is called at the top of every POST handler in the files above; it aborts with HTTP 403 if the token is absent or wrong. Every form in those files now outputs `csrf_token_field()` as a hidden input. `$_SERVER['PHP_SELF']` in form actions is also escaped.

> **Note:** The application has 73+ forms total. CSRF protection has been applied to the highest-risk admin forms. Remaining forms (project selection, student allocation uploads, assessment marking) should have the same pattern applied: add `require_valid_csrf()` to the POST handler and `echo csrf_token_field()` inside each `<form>`.

---

### Fix 10 — Secure File Uploads
**Severity:** High
**File:** `pma/staff/admin2/upload_header_image.php`

- **MIME validation:** `$_FILES['image']['type']` (client-supplied, forgeable) is no longer used. Actual MIME type is determined server-side via `new finfo(FILEINFO_MIME_TYPE)` against a strict allowlist (`image/jpeg`, `image/png`).
- **Random filename:** The original user-supplied filename is never stored. A `bin2hex(random_bytes(16))` name is generated instead, eliminating SQL injection and XSS via filename.
- **SQL:** The INSERT query was still using raw string interpolation — now converted to a prepared statement with bound parameters.
- **CSRF:** The upload form is now also protected by the CSRF token (see Fix 9).

---

## All Fixes Complete

All 10 identified issues have been remediated.

---

## Additional Fixes (continued)

### Fix 11 — CSRF Global Coverage (JS Auto-Injection)
**Severity:** High
**Files:** `pma/staff/csrf_functions.php`, `pma/staff/mobile_head_bs.php`, `pma/staff/admin2/admin_mobile_head_bs.php`, `pma/staff/setup_tables.php`

The original CSRF protection (Fix 9) covered only the highest-risk admin forms. This fix extends coverage to all forms across the application without editing each form individually.

**How it works:**
- Both shared head files (`mobile_head_bs.php`, `admin_mobile_head_bs.php`) now output a `<meta name="csrf-token">` tag containing the session token.
- An inline `<script>` block in those head files runs on `DOMContentLoaded` and injects a hidden `csrf_token` input into every `<form>` that does not already have one. It also sets `jQuery.ajaxSetup({ headers: { 'X-CSRF-Token': token } })` to protect AJAX POST calls.
- `validate_csrf_token()` in `csrf_functions.php` now also accepts the token from the `X-CSRF-Token` HTTP request header (for AJAX), in addition to the POST field.
- `require_valid_csrf()` is called once at the top of `setup_tables.php`, providing a universal application-wide POST guard on every page that includes that file.

---

### Fix 12 — XSS from Database-Sourced Title Values
**Severity:** Medium
**Files:** `pma/staff/setup_titles.php`, `pma/staff/mobile_head_bs.php`, `pma/staff/admin2/admin_mobile_head_bs.php`

`$school_title` and `$running_title` are fetched from the database and were echoed directly into `<title>` tags in both head files. If an admin had stored a value containing `<script>` or `"` characters, it would render unescaped.

**What was changed:**
- `setup_titles.php` now creates `$school_title_h` and `$running_title_h` — HTML-escaped variants — immediately after the DB fetch using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- Both head files use these escaped variants in their `<title>` output.

---

### Fix 13 — Error Message Disclosure Removed
**Severity:** Medium
**Files:** `pma/staff/setup_tables.php` (primary), ~100 additional PHP files across `staff/`, `student/`, `staff/admin2/`, `staff/proposals/`

Catch blocks throughout the codebase were echoing `$ex->getMessage()` and the raw SQL query string (`$query`) directly to the browser, leaking database schema, table names, column names, and query logic.

**What was changed:**
- Added `pma_log_db_error(PDOException $ex, string $context)` helper to `csrf_functions.php`. It calls `error_log()` (server-side log) and echoes only a generic "A database error occurred" message.
- All 5 error catch blocks in `setup_tables.php` replaced with `pma_log_db_error()`.
- A Perl mass-replace pass across all PHP files:
  - Deleted every `echo(" Query = $query...")` line.
  - Replaced every `echo(" Err message: " . $ex->getMessage()...)` line with `error_log($ex->getMessage()); echo "A database error occurred..."`.
- One remaining live case in `student/projects-list-mobile_prog.php` fixed manually (unusual quoting pattern evaded the mass-replace regex).
- Remaining matches in `markstore_functions.php` and `connect_pdo.php` are commented-out dead code — no action needed.

**Note:** The `echo "File: ... near line:"` pattern (file path disclosure) still exists in some catch blocks. These reveal server file paths and should be removed in a future pass. They are lower severity than query/exception disclosure.
