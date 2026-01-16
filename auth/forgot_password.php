<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Acadex</title>
    <link rel="icon" type="image/webp" href="../image/Acadex-logo.webp"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../css/auth/forgot_password.css">
</head>
<body>
    <div class="wrapper">
        <div class="panel-left">
            <h1>Reset your password</h1>
            <p>Request a verification code, confirm it, then set a new password. Codes expire after 15 minutes and can only be used once.</p>
        </div>
        <div class="panel-right">
            <h2>Forgot Password</h2>
            <div id="alert" class="message"></div>

            <div class="section active" data-step="1">
                <h3>1) Request verification code</h3>
                <div class="section-body">
                    <div class="form-group">
                        <label for="req-email">Teacher Email</label>
                        <input type="email" id="req-email" placeholder="teacher@example.com">
                    </div>
                    <button class="btn" id="btn-request">Send Code</button>
                    <div class="helper">We’ll email a 6-character code. It expires in 15 minutes.</div>
                </div>
            </div>

            <div class="section" data-step="2">
                <h3>2) Verify code</h3>
                <div class="section-body">
                    <div class="form-group">
                        <label for="verify-email">Teacher Email</label>
                        <input type="email" id="verify-email" placeholder="teacher@example.com">
                    </div>
                    <div class="form-group">
                        <label for="verify-code">Verification Code</label>
                        <input type="text" id="verify-code" placeholder="A1B2C3" maxlength="6">
                    </div>
                    <button class="btn" id="btn-verify">Verify Code</button>
                    <div class="helper">Enter the code you received via email.</div>
                </div>
            </div>

            <div class="section" data-step="3">
                <h3>3) Set new password</h3>
                <div class="section-body">
                    <div class="form-group">
                        <label for="reset-email">Teacher Email</label>
                        <input type="email" id="reset-email" placeholder="teacher@example.com">
                    </div>
                    <div class="form-group">
                        <label for="reset-code">Verification Code</label>
                        <input type="text" id="reset-code" placeholder="A1B2C3" maxlength="6">
                    </div>
                    <div class="form-group">
                        <label for="reset-pass">New Password</label>
                        <input type="password" id="reset-pass" placeholder="At least 6 characters">
                    </div>
                    <div class="form-group">
                        <label for="reset-pass2">Confirm New Password</label>
                        <input type="password" id="reset-pass2" placeholder="Repeat password">
                    </div>
                    <button class="btn" id="btn-reset">Update Password</button>
                    <div class="helper">Code must be verified and unexpired.</div>
                </div>
            </div>

            <div class="links">
                <a href="teacher-login.php">Back to Teacher Login</a>
            </div>
        </div>
    </div>

    <script>
        const alertBox = document.getElementById('alert');

        function showMessage(type, text) {
            alertBox.className = 'message ' + type;
            alertBox.textContent = text;
            alertBox.style.display = 'block';
            setTimeout(() => { alertBox.style.display = 'none'; }, 6000);
        }

        async function postReset(data) {
            const res = await fetch('../includes/password_reset.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data)
            });
            return res.json();
        }

        const sections = document.querySelectorAll('.section');
        function setStep(step) {
            sections.forEach(sec => {
                const isTarget = sec.getAttribute('data-step') === step;
                sec.classList.toggle('active', isTarget);
            });
        }
        setStep('1');

        document.getElementById('btn-request').addEventListener('click', async () => {
            const email = document.getElementById('req-email').value.trim();
            const resp = await postReset({ action: 'request_code', email });
            showMessage(resp.success ? 'success' : 'error', resp.message || 'Request failed');
            if (resp.success) {
                document.getElementById('verify-email').value = email;
                document.getElementById('reset-email').value = email;
                setStep('2');
            }
        });

        document.getElementById('btn-verify').addEventListener('click', async () => {
            const email = document.getElementById('verify-email').value.trim();
            const code = document.getElementById('verify-code').value.trim();
            const resp = await postReset({ action: 'verify_code', email, code });
            showMessage(resp.success ? 'success' : 'error', resp.message || 'Verification failed');
            if (resp.success) {
                document.getElementById('reset-email').value = email;
                document.getElementById('reset-code').value = code;
                setStep('3');
            }
        });

        document.getElementById('btn-reset').addEventListener('click', async () => {
            const email = document.getElementById('reset-email').value.trim();
            const code = document.getElementById('reset-code').value.trim();
            const password = document.getElementById('reset-pass').value;
            const confirmPassword = document.getElementById('reset-pass2').value;
            const resp = await postReset({ action: 'set_password', email, code, password, confirmPassword });
            showMessage(resp.success ? 'success' : 'error', resp.message || 'Reset failed');
        });
    </script>
</body>
</html>
