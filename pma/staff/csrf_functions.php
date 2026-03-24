<?php
/**
 * CSRF token helpers.
 * Requires an active PHP session (session_start() must have been called first).
 */

function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Returns true if the submitted CSRF token matches the session token.
 * Accepts the token from either the POST field or the X-CSRF-Token HTTP header
 * (the header form is used by AJAX requests injected by the page-level script).
 * Uses hash_equals() to prevent timing attacks.
 */
function validate_csrf_token(): bool {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    // Prefer POST field; fall back to request header (AJAX)
    if (!empty($_POST['csrf_token'])) {
        $submitted = $_POST['csrf_token'];
    } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'];
    } else {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submitted);
}

/**
 * Returns an HTML hidden input carrying the current CSRF token.
 * Call this inside every <form> that performs a state-changing POST.
 */
function csrf_token_field(): string {
    $token = generate_csrf_token();
    return "<input type='hidden' name='csrf_token' value='" . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . "'>\n";
}

/**
 * Convenience wrapper: aborts with a plain-text error if the token is invalid.
 * Call at the top of any POST handler block, or once in setup_tables.php for
 * application-wide coverage.
 */
function require_valid_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token()) {
        http_response_code(403);
        die("Security error: invalid or missing CSRF token. Please go back and try again.");
    }
}

/**
 * Log a PDO database error privately and show a generic message to the user.
 * Use this instead of echo $ex->getMessage() to avoid leaking internals.
 *
 * @param PDOException $ex      The caught exception.
 * @param string       $context Optional label (function name, file, query) for the log entry.
 */
function pma_log_db_error(PDOException $ex, string $context = ''): void {
    $label = $context ? " [$context]" : '';
    error_log('PMA database error' . $label . ': ' . $ex->getMessage());
    echo "A database error occurred. Please contact the system administrator.<BR>\n";
}
