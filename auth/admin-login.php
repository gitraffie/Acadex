<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/auth/admin-login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="brand-pane">
            <div class="brand-header">
                <div class="logo">
                    <img src="../image/Acadex-logo-white.webp" alt="Acadex Logo">
                    Acadex Admin
                </div>
                <h2>Secure access for administrators</h2>
                <p>Sign in to manage teacher accounts, oversee onboarding, and keep your Acadex environment organized.</p>
                <div class="badges">
                    <span class="badge"><i class="fas fa-user-plus"></i> Create teacher accounts</span>
                    <span class="badge"><i class="fas fa-list-check"></i> Monitor classes</span>
                    <span class="badge"><i class="fas fa-lock"></i> Centralized control</span>
                </div>
            </div>
            <div class="brand-footer" style="position: relative; z-index: 2; margin-top: 3rem;">
                <p style="font-size: 0.95rem; color: rgba(255,255,255,0.8);">Need help? Contact the system owner to reset your admin access.</p>
            </div>
        </div>

        <div class="login-pane">
            <div class="login-header">
                <h1>Admin Login</h1>
                <p>Only administrators can access this portal.</p>
            </div>

            <div id="errorMessage" class="message error"></div>
            <div id="successMessage" class="message success"></div>

            <form id="loginForm" action="../includes/login.php" method="POST">
                <input type="hidden" name="login_type" value="admin">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" placeholder="admin@acadex.com" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <i class="fas fa-eye input-icon-right" id="togglePassword"></i>
                    </div>
                </div>

                <div class="actions">
                    <div class="remember">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember this device</label>
                    </div>
                    <a href="../auth/teacher-login.php">Go to teacher login</a>
                </div>

                <button type="submit" class="login-btn">Sign In</button>
            </form>

            <p class="help-text">Admin access is restricted. Make sure you use your administrator credentials.</p>
        </div>
    </div>

    <script>
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
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                e.preventDefault();
                showError('Please enter a valid email address.');
                return;
            }

            if (!password) {
                e.preventDefault();
                showError('Password is required.');
                return;
            }

            const submitBtn = loginForm.querySelector('.login-btn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Signing in...';
            submitBtn.disabled = true;

            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 1500);
        });

        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
        }

        function showSuccess(message) {
            successMessage.textContent = message;
            successMessage.style.display = 'block';
        }

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
