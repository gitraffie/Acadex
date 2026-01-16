<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/auth/teacher-login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="left-content">
                <div class="logo">Acadex</div>
                <div class="welcome-icon"><img src="../image/teacher.webp" alt="Acadex Logo"></div>
                <h2>Welcome Back, Teacher!</h2>
                <p>Access your dashboard to manage grades, track attendance, and connect with your students.</p>
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h1>Teacher Login</h1>
                <p>Sign in to access your teaching dashboard</p>
            </div>

            <div id="errorMessage" class="error-message"></div>
            <div id="successMessage" class="success-message"></div>

            <form id="loginForm" action="../includes/login.php" method="POST">
                <input type="hidden" name="login_type" value="teacher">
                <input type="hidden" name="login-btn" value="1">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="teacher@example.com" 
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
                            placeholder="Enter your password"
                            required
                        >
                        <i class="fas fa-eye input-icon-right" id="togglePassword"></i>
                    </div>
                </div>

                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember me</label>
                    </div>
                    <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" name="login-btn" class="login-btn">Sign In</button>
            </form>

            <div class="other-login">
                <p>Teacher accounts are created by admins. Please contact your administrator for access.</p>
            </div>
        </div>
    </div>

    <script>
        // Form validation and submission
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');

        const params = new URLSearchParams(window.location.search);
        if (params.get('error')) {
            showError(params.get('error'));
        }
        if (params.get('success')) {
            showSuccess(params.get('success'));
        }

        loginForm.addEventListener('submit', function(e) {
            // For demonstration purposes - remove this in production
            // e.preventDefault();

            // Basic client-side validation
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Reset messages
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';

            // Email validation
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                e.preventDefault();
                showError('Please enter a valid email address');
                return false;
            }

            // Password validation
            // Removed length check for debugging
            // if (password.length < 6) {
            //     e.preventDefault();
            //     showError('Password must be at least 6 characters long');
            //     return false;
            // }

            // If validation passes, show loading state
            const submitBtn = loginForm.querySelector('.login-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Signing In...';
            submitBtn.disabled = true;

            // Uncomment below for demo without backend
            // setTimeout(() => {
            //     submitBtn.textContent = originalText;
            //     submitBtn.disabled = false;
            //     showSuccess('Login successful! Redirecting...');
            //     setTimeout(() => {
            //         window.location.href = 'teacher_dashboard.php';
            //     }, 1500);
            // }, 1000);
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
    </script>
</body>
</html>
