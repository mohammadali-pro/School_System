<?php
/**
 * Security Helper Functions
 * 
 * Provides CSRF protection, session management, input validation,
 * and password strength validation.
 */

/**
 * Initialize secure session
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
        
        session_start();
        
        // Regenerate session ID periodically to prevent session fixation
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF token input field HTML
 */
function csrfTokenField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validate CSRF token from POST request
 */
function validateCSRFFromPost() {
    if (!isset($_POST[CSRF_TOKEN_NAME])) {
        return false;
    }
    return validateCSRFToken($_POST[CSRF_TOKEN_NAME]);
}

/**
 * Properly destroy session
 */
function destroySession() {
    initSecureSession();
    
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}

/**
 * Validate email format
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize input string
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate password strength
 */
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        $errors[] = "Password must be at least " . MIN_PASSWORD_LENGTH . " characters long";
    }
    
    if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    
    if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }
    
    if (PASSWORD_REQUIRE_NUMBER && !preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Track login attempts
 */
function trackLoginAttempt($email, $success = false) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }
    
    if ($success) {
        // Clear attempts on successful login
        unset($_SESSION['login_attempts'][$email]);
        return;
    }
    
    // Initialize or increment attempts
    if (!isset($_SESSION['login_attempts'][$email])) {
        $_SESSION['login_attempts'][$email] = [
            'count' => 0,
            'last_attempt' => time()
        ];
    }
    
    $_SESSION['login_attempts'][$email]['count']++;
    $_SESSION['login_attempts'][$email]['last_attempt'] = time();
}

/**
 * Check if login is allowed
 */
function isLoginAllowed($email) {
    if (!isset($_SESSION['login_attempts'][$email])) {
        return true;
    }
    
    $attempts = $_SESSION['login_attempts'][$email];
    
    // Check if locked out
    if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
        $timeSinceLastAttempt = time() - $attempts['last_attempt'];
        
        if ($timeSinceLastAttempt < LOGIN_LOCKOUT_TIME) {
            $remainingTime = LOGIN_LOCKOUT_TIME - $timeSinceLastAttempt;
            return [
                'allowed' => false,
                'message' => "Too many failed attempts. Please try again in " . ceil($remainingTime / 60) . " minutes."
            ];
        } else {
            // Lockout period expired, reset attempts
            unset($_SESSION['login_attempts'][$email]);
            return true;
        }
    }
    
    return true;
}

/**
 * Set error message in session
 */
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Set success message in session
 */
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Get and clear error message
 */
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

/**
 * Get and clear success message
 */
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}

/**
 * Redirect with error
 */
function redirectWithError($url, $message) {
    setErrorMessage($message);
    header("Location: $url");
    exit;
}

/**
 * Redirect with success
 */
function redirectWithSuccess($url, $message) {
    setSuccessMessage($message);
    header("Location: $url");
    exit;
}

/**
 * Check if user is authenticated
 */
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
}

/**
 * Check if user has specific role
 */
function requireRole($role) {
    requireAuth();
    if ($_SESSION['role'] !== $role) {
        header('Location: ../index.php');
        exit;
    }
}
?>
