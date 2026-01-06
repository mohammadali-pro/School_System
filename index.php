<?php
require 'config/db.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Validate CSRF token
    if (!validateCSRFFromPost()) {
        $message = "Invalid security token. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        // Validate inputs
        if (empty($email) || empty($password)) {
            $message = "Email and password are required.";
        } elseif (!validateEmail($email)) {
            $message = "Invalid email format.";
        } else {
            // Check if login is allowed (rate limiting)
            $loginCheck = isLoginAllowed($email);
            
            if (is_array($loginCheck) && !$loginCheck['allowed']) {
                $message = $loginCheck['message'];
            } else {
                // Get the user using email
                $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();
                
                // Verify password
                if ($user && password_verify($password, $user['password'])) {
                    
                    // Track successful login
                    trackLoginAttempt($email, true);
                    
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    // Save session data
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirect based on role
                    if ($user['role'] === 'admin') {
                        header('Location: dashboard/admin_dashboard.php');
                    } elseif ($user['role'] === 'teacher') {
                        header('Location: dashboard/teacher_dashboard.php');
                    } else {
                        header('Location: dashboard/student_dashboard.php');
                    }
                    exit;
                    
                } else {
                    // Track failed login attempt
                    trackLoginAttempt($email, false);
                    $message = "Invalid email or password!";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - School Management System</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <p class="welcome">Access your account to manage your dashboard, and more.</p>
    <div class="login-container">
        <div class="req">
            <h2>Welcome! <br>Please log in to access your account.</h2>
        </div>
        <div class="mat">
            <?php if($message) echo "<p class='message'>" . htmlspecialchars($message) . "</p>"; ?>
            <form method="POST" id="loginForm" novalidate>
                <?php echo csrfTokenField(); ?>
                <div class="input-wrapper">
                    <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input type="email" name="email" id="email" placeholder="Email" required>
                    <svg class="validation-icon valid-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display: none;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <svg class="validation-icon error-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span class="error-message" style="display: none;"></span>
                </div>
                <div class="input-wrapper">
                    <svg class="input-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" name="password" id="password" placeholder="Password" required minlength="8">
                    <svg class="validation-icon valid-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="display: none;">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <svg class="validation-icon error-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: none;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span class="error-message" style="display: none;"></span>
                </div>
                <button type="submit" id="loginBtn">
                    <span class="btn-text">Login</span>
                </button>
            </form>
            <script>
                const form = document.getElementById('loginForm');
                const emailInput = document.getElementById('email');
                const passwordInput = document.getElementById('password');
                const loginBtn = document.getElementById('loginBtn');

                // Reset form when page loads (handles back button scenario)
                function resetForm() {
                    // Re-enable button if it was disabled
                    loginBtn.disabled = false;
                    
                    // Clear all validation states
                    [emailInput, passwordInput].forEach(input => {
                        const wrapper = input.closest('.input-wrapper');
                        const validIcon = wrapper.querySelector('.valid-icon');
                        const errorIcon = wrapper.querySelector('.error-icon');
                        const errorMessage = wrapper.querySelector('.error-message');
                        
                        input.classList.remove('valid', 'invalid');
                        wrapper.classList.remove('has-error');
                        validIcon.style.display = 'none';
                        errorIcon.style.display = 'none';
                        errorMessage.style.display = 'none';
                    });
                    
                    // Clear form fields
                    form.reset();
                }

                // Reset form on page load
                window.addEventListener('pageshow', function(event) {
                    // Check if page was loaded from cache (back button)
                    if (event.persisted) {
                        resetForm();
                    }
                });

                // Also reset on regular page load
                resetForm();

                // Real-time validation
                function validateInput(input) {
                    const wrapper = input.closest('.input-wrapper');
                    const validIcon = wrapper.querySelector('.valid-icon');
                    const errorIcon = wrapper.querySelector('.error-icon');
                    const errorMessage = wrapper.querySelector('.error-message');
                    
                    if (input.validity.valid) {
                        input.classList.remove('invalid');
                        input.classList.add('valid');
                        wrapper.classList.remove('has-error');
                        validIcon.style.display = 'block';
                        errorIcon.style.display = 'none';
                        errorMessage.style.display = 'none';
                    } else {
                        input.classList.remove('valid');
                        input.classList.add('invalid');
                        wrapper.classList.add('has-error');
                        validIcon.style.display = 'none';
                        errorIcon.style.display = 'block';
                        errorMessage.style.display = 'block';
                        
                        if (input.validity.valueMissing) {
                            errorMessage.textContent = input.type === 'email' ? 'Email is required' : 'Password is required';
                        } else if (input.validity.typeMismatch) {
                            errorMessage.textContent = 'Please enter a valid email address';
                        } else if (input.validity.tooShort) {
                            errorMessage.textContent = 'Password must be at least 4 characters';
                        } else {
                            errorMessage.textContent = 'Please check this field';
                        }
                    }
                }

                // Validate on input
                emailInput.addEventListener('input', () => validateInput(emailInput));
                passwordInput.addEventListener('input', () => validateInput(passwordInput));

                // Validate on blur
                emailInput.addEventListener('blur', () => validateInput(emailInput));
                passwordInput.addEventListener('blur', () => validateInput(passwordInput));

                // Form submission
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    let isValid = true;
                    [emailInput, passwordInput].forEach(input => {
                        validateInput(input);
                        if (!input.validity.valid) {
                            isValid = false;
                        }
                    });

                    if (isValid) {
                        const btn = document.getElementById('loginBtn');
                        btn.disabled = true;
                        form.submit();
                    } else {
                        // Focus first invalid field
                        const firstInvalid = form.querySelector('input.invalid');
                        if (firstInvalid) {
                            firstInvalid.focus();
                        }
                    }
                });
            </script>
        </div>
    </div>
</body>
</html>
