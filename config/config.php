<?php

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'school_system');
define('DB_USER', 'root');
define('DB_PASS', '');

// Email Configuration
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 587);
define('EMAIL_USERNAME', 'maazm691@gmail.com');
define('EMAIL_PASSWORD', 'imca ypng bhzu xzqy');
define('EMAIL_FROM_ADDRESS', 'maazm691@gmail.com');
define('EMAIL_FROM_NAME', 'School System');
define('EMAIL_ENCRYPTION', 'tls');

// Security Settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Password Requirements
define('MIN_PASSWORD_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_LOWERCASE', true);
define('PASSWORD_REQUIRE_NUMBER', true);
define('PASSWORD_REQUIRE_SPECIAL', false);

// Application Settings
define('APP_NAME', 'School Management System');
define('APP_URL', 'http://localhost/School_System');

// Error Reporting
define('DISPLAY_ERRORS', true);

if (DISPLAY_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>
