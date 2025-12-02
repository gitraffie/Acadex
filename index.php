<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acadex - Smart Grading & Attendance System</title>
    <link rel="icon" type="image/webp" href="image/Acadex-logo.webp"/>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        .navbar {
            position: fixed;
            width: 100%;
            background: #ffffffff;
            backdrop-filter: blur(5px);
            padding: 1rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: flex;
            align-items: center;
        }

        .logo img {
            height: 2.8rem;
            margin-right: 0.2rem;

        }

        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s ease;
            position: relative;
        }

        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .nav-links a:hover::after {
            width: 100%;
        }

        .cta-btn {
            padding: 0.7rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .cta-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8rem 5% 4rem;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            top: -200px;
            right: -200px;
            animation: float 6s ease-in-out infinite;
        }

        .hero::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-30px); }
        }

        .hero-content {
            flex: 1;
            z-index: 2;
            animation: slideInLeft 1s ease;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .hero h1 {
            font-size: 3.5rem;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.3rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
        }

        .primary-btn, .secondary-btn {
            padding: 1rem 2rem;
            font-size: 1.1rem;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .primary-btn {
            background: white;
            color: #667eea;
        }

        .primary-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .secondary-btn {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .secondary-btn:hover {
            background: white;
            color: #667eea;
        }

        .hero-image {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 2;
            animation: slideInRight 1s ease;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .dashboard-mockup {
            width: 600px;
            height: 400px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            padding: 1rem;
            animation: floatDashboard 4s ease-in-out infinite;
        }

        @keyframes floatDashboard {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        .features {
            padding: 5rem 5%;
            background: #f8f9fa;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            margin-bottom: 3rem;
            color: #333;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #333;
        }

        .feature-card p {
            color: #666;
            line-height: 1.8;
        }

        .stats {
            padding: 5rem 5%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 3rem;
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }

        .stat-item h2 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .stat-item p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .cta-section {
            padding: 5rem 5%;
            text-align: center;
            background: white;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #333;
        }

        .cta-section p {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }

        footer {
            background: #1a1a1a;
            color: white;
            padding: 3rem 5%;
            text-align: center;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-links a {
            color: white;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #667eea;
        }

        @media (max-width: 768px) {
            .hero {
                flex-direction: column;
                text-align: center;
                padding-top: 6rem;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero-content, .hero-image {
                flex: none;
            }

            .dashboard-mockup {
                width: 100%;
                height: 300px;
                margin-top: 2rem;
            }

            .nav-links {
                display: none;
            }

            .hero-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo"><img src="image/Acadex-logo.webp" alt="Acadex Logo">Acadex</div>
        <ul class="nav-links">
            <li><a href="#features">Features</a></li>
            <li><a href="#benefits">Benefits</a></li>
            <li><a href="#contact">Contact</a></li>
        </ul>
        <a class="cta-btn" href="auth/student-login.php" style="text-decoration: none">Login</a>
    </nav>

    <section class="hero">
        <div class="hero-content">
            <h1>Automate Your Academic Management</h1>
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
            if (window.scrollY > 100) {
                navbar.style.boxShadow = '0 5px 30px rgba(0, 0, 0, 0.15)';
            } else {
                navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
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
