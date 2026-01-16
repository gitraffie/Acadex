<?php include '../includes/process_register.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Acadex</title>
    <link rel="icon" type="image/webp" href="image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/auth/registration.css">
</head>
<body>
    <div class="register-container">
        <div class="register-left">
            <div class="left-content">
                <div class="logo">Acadex</div>
                <div class="welcome-icon">🎓</div>
                <h2>Join Acadex Today!</h2>
                <p>Create your account to access personalized dashboards, track progress, and connect with your academic community.</p>
            </div>
        </div>

        <div class="register-right">
            <div class="register-header">
                <h1>Create Account</h1>
                <p>Sign up to get started</p>
            </div>

            <?php
            if (isset($_GET['errors'])) {
                $errors = explode('|', urldecode($_GET['errors']));
                foreach ($errors as $error) {
                    echo '<div class="error-message" style="display: block;">' . htmlspecialchars($error) . '</div>';
                }
            }
            if (isset($_GET['success'])) {
                echo '<div class="success-message" style="display: block;">Account created successfully! Redirecting...</div>';
            }
            ?>
            <div id="errorMessage" class="error-message"></div>
            <div id="successMessage" class="success-message"></div>
            <div class="info-message">
                Teacher self-registration is disabled. Please contact your administrator to have your account created.
            </div>

            <form id="registerForm" action="../includes/process_register.php" method="POST">
                <fieldset disabled>
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input 
                                type="text" 
                                id="firstName" 
                                name="firstName" 
                                placeholder="Enter your first name" 
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input 
                                type="text" 
                                id="lastName" 
                                name="lastName" 
                                placeholder="Enter your last name" 
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="your@email.com"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Create a password"
                                required
                            >
                            <i class="fas fa-eye input-icon-right" id="togglePassword"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                placeholder="Confirm your password"
                                required
                            >
                            <i class="fas fa-eye input-icon-right" id="toggleConfirmPassword"></i>
                        </div>
                    </div>

                    <button type="submit" name="register-btn" class="register-btn" disabled>Registration Disabled</button>
                </fieldset>
            </form>

            <div class="other-login">
                <p>Already have an account? <a href="teacher-login.php">Sign in here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Form validation and submission
        const registerForm = document.getElementById('registerForm');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');

        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            showError('Teacher self-registration is disabled. Please contact your administrator.');
            return false;
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 5000);
        }

        function showSuccess(message) {
            successMessage.textContent = message;
            successMessage.style.display = 'block';
        }

        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirmPassword');

        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Add enter key support for form submission
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    registerForm.dispatchEvent(new Event('submit'));
                }
            });
        });
    </script>
</body>
</html>
