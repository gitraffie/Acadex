<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acadex - Smart Grading & Attendance System</title>
    <link rel="icon" type="image/webp" href="image/Acadex-logo.webp"/>
    
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <nav class="navbar">
        <div class="logo"><img src="image/Acadex-logo-white.webp" alt="Acadex Logo"><h3>Acadex</h3></div>
        <ul class="nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#benefits">Benefits</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <a class="cta-btn" href="auth/student-login.php" style="text-decoration: none">Login</a>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1 id="typing-text"></h1>
            <p>Streamline student grading, attendance tracking, and real-time notifications with our intelligent system. Save time, improve accuracy, and enhance transparency.</p>
            <div class="hero-buttons">
                <button class="primary-btn" href="auth/student-login.php">Get Started</button>
                <button class="secondary-btn">Watch Demo</button>
            </div>
        </div>
        <div class="hero-image">
            <div class="dashboard-mockup">
                <svg width="100%" height="100%" viewBox="0 0 600 400">
                    <rect x="10" y="10" width="580" height="60" rx="5" fill="#667eea" opacity="0.1"/>
                    <rect x="20" y="25" width="100" height="30" rx="5" fill="#667eea"/>
                    <circle cx="560" cy="40" r="15" fill="#667eea" opacity="0.3"/>
                    
                    <rect x="10" y="90" width="280" height="290" rx="10" fill="#f8f9fa"/>
                    <rect x="20" y="100" width="260" height="40" rx="5" fill="#667eea" opacity="0.2"/>
                    <rect x="20" y="150" width="260" height="30" rx="5" fill="#e0e0e0"/>
                    <rect x="20" y="190" width="260" height="30" rx="5" fill="#e0e0e0"/>
                    <rect x="20" y="230" width="260" height="30" rx="5" fill="#e0e0e0"/>
                    
                    <rect x="310" y="90" width="280" height="130" rx="10" fill="#f8f9fa"/>
                    <circle cx="450" cy="155" r="40" fill="#667eea" opacity="0.3"/>
                    
                    <rect x="310" y="240" width="280" height="140" rx="10" fill="#f8f9fa"/>
                    <rect x="320" y="250" width="120" height="10" rx="5" fill="#667eea" opacity="0.5"/>
                    <rect x="320" y="270" width="180" height="10" rx="5" fill="#667eea" opacity="0.3"/>
                    <rect x="320" y="290" width="150" height="10" rx="5" fill="#667eea" opacity="0.4"/>
                </svg>
            </div>
        </div>
    </section>

    <section class="features" id="features">
        <h2 class="section-title">Powerful Features for Modern Education</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Automated Grading</h3>
                <p>Instantly compute and record student grades with our intelligent algorithm. Reduce manual errors and save countless hours of administrative work.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">✅</div>
                <h3>Attendance Tracking</h3>
                <p>Real-time attendance management with automated calculations. Track patterns, generate reports, and identify issues before they become problems.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📧</div>
                <h3>Email Notifications</h3>
                <p>Keep students and parents informed with instant email alerts for grades, attendance, and important updates. Full transparency at every step.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Real-Time Dashboard</h3>
                <p>Access all information instantly through our intuitive dashboard. Students can view their records anytime, anywhere, on any device.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Secure & Reliable</h3>
                <p>Built with PHP and industry-standard security protocols. Your data is encrypted, backed up, and protected at all times.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📈</div>
                <h3>Analytics & Reports</h3>
                <p>Generate comprehensive reports with powerful analytics. Identify trends, track performance, and make data-driven decisions.</p>
            </div>
        </div>
    </section>

    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <h2>95%</h2>
                <p>Time Saved</p>
            </div>
            <div class="stat-item">
                <h2>99.9%</h2>
                <p>Accuracy Rate</p>
            </div>
            <div class="stat-item">
                <h2>50K+</h2>
                <p>Students Managed</p>
            </div>
            <div class="stat-item">
                <h2>24/7</h2>
                <p>System Availability</p>
            </div>
        </div>
    </section>

    <section class="cta-section" id="contact">
        <h2>Ready to Transform Your Academic Management?</h2>
        <p>Join hundreds of institutions already using Acadex to streamline their operations.</p>
        <button class="primary-btn" style="padding: 1rem 3rem; font-size: 1.2rem;">Get Started Today</button>
    </section>

    <footer>
        <div class="footer-links">
            <a href="#about">About Us</a>
            <a href="#privacy">Privacy Policy</a>
            <a href="#terms">Terms of Service</a>
            <a href="#support">Support</a>
        </div>
        <p>&copy; 2025 Acadex. All rights reserved.</p>
    </footer>

    <script>
        // Typing animation
        const text = "Automate Your Academic Management";
        const typingElement = document.getElementById('typing-text');
        let index = 0;

        function typeWriter() {
            if (index < text.length) {
                let currentText = text.substring(0, index + 1);
                if (index >= 22) {
                    currentText = text.substring(0, 22) + '<br>' + text.substring(22, index + 1);
                }
                typingElement.innerHTML = currentText + '<span class="cursor">|</span>';
                index++;
                setTimeout(typeWriter, 100);
            }
        }

        // Start typing animation when page loads
        window.addEventListener('load', function() {
            setTimeout(typeWriter, 500); // Delay before starting
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar scroll effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            const navLinks = document.querySelectorAll('.nav-links a');
            const logoImg = document.querySelector('.logo img');
            const logo = document.querySelector('.logo h3');
            if (window.scrollY > 100) {
                navbar.style.boxShadow = '0 5px 30px rgba(0, 0, 0, 0.15)';
                navbar.style.background = 'rgba(255, 255, 255, 0.9)';
                logoImg.src = 'image/Acadex-logo.webp';
                logo.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                logo.style.webkitBackgroundClip = 'text';
                logo.style.webkitTextFillColor = 'transparent';
                logo.style.color = '';
                navLinks.forEach(link => link.style.color = '#333');
            } else {
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
                navbar.style.background = 'transparent';
                logoImg.src = 'image/Acadex-logo-white.webp';
                logo.style.background = 'white';
                logo.style.webkitBackgroundClip = 'text';
                logo.style.webkitTextFillColor = '';
                logo.style.color = 'white';
                navLinks.forEach(link => link.style.color = 'white');
            }
        });

        // Feature card animation on scroll
        const observerOptions = {
            threshold: 0.2,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.feature-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
