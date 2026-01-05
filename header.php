<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no">
    <title>Fit for the King - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1a237e">
    <meta name="description" content="Strengthening faith while building resilience - Fitness and Devotion App">
    <meta name="keywords" content="fitness, devotion, christian, workout, health">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="logo/logo-192x192.png">
    <link rel="apple-touch-icon" sizes="152x152" href="logo/logo-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="logo/logo-180x180.png">
    <link rel="apple-touch-icon" sizes="167x167" href="logo/logo-167x167.png">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="logo/logo.ico">
    <link rel="icon" type="image/png" href="logo/logo-192x192.png">
    <link rel="shortcut icon" type="image/png" href="logo/logo-192x192.png">

    <!-- iOS Specific Meta Tags -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Fit for the King">

    <!-- Splash Screen Images for iOS -->
    <!-- iPhone 5/5S/5C/SE (320px) -->
    <link rel="apple-touch-startup-image" href="logo/splash-640x1136.png" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)">
    
    <!-- iPhone 6/7/8 (375px) -->
    <link rel="apple-touch-startup-image" href="logo/splash-750x1334.png" media="(device-width: 375px) and (device-height: 667px) and (-webkit-device-pixel-ratio: 2)">
    
    <!-- iPhone 6/7/8 Plus (414px) -->
    <link rel="apple-touch-startup-image" href="logo/splash-1242x2208.png" media="(device-width: 414px) and (device-height: 736px) and (-webkit-device-pixel-ratio: 3)">
    
    <!-- iPhone X/XS/11 Pro (375px) -->
    <link rel="apple-touch-startup-image" href="logo/splash-1125x2436.png" media="(device-width: 375px) and (device-height: 812px) and (-webkit-device-pixel-ratio: 3)">
    
    <!-- iPhone XR/11 (414px) -->
    <link rel="apple-touch-startup-image" href="logo/splash-828x1792.png" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 2)">
    
    <!-- iPhone XS Max/11 Pro Max (414px) -->
    <link rel="apple-touch-startup-image" href="logo/splash-1242x2688.png" media="(device-width: 414px) and (device-height: 896px) and (-webkit-device-pixel-ratio: 3)">

    <!-- Android Chrome -->
    <meta name="mobile-web-app-capable" content="yes">

    <!-- Prevent default favicon.ico request -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>👑</text></svg>">
    
    <!-- Styles and Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #ffffff;
            --secondary: #1a237e;
            --accent: #1a237e;
            --accent-light: #303f9f;
            --accent-dark: #000051;
            --background: #ffffff;
            --card-bg: #ffffff;
            --text: #1a237e;
            --light-text: #5c6bc0;
            --border: rgba(26, 35, 126, 0.15);
            --gradient-primary: linear-gradient(145deg, #ffffff 0%, #1a237e 100%);
            --gradient-accent: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
            --gradient-blue: linear-gradient(135deg, #1a237e 0%, #5c6bc0 50%, #1a237e 100%);
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(26, 35, 126, 0.1);

            --radius: 20px;
            --shadow: 0 15px 35px rgba(26, 35, 126, 0.1);
            --shadow-lg: 0 25px 60px rgba(26, 35, 126, 0.15);
            --transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html, body {
            height: 100%;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans";
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Premium Background */
        .premium-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background:
                radial-gradient(ellipse at 20% 30%, rgba(26, 35, 126, 0.1) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 70%, rgba(26, 35, 126, 0.05) 0%, transparent 50%),
                var(--background);
        }

        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .particle {
            position: absolute;
            background: var(--accent);
            border-radius: 50%;
            opacity: 0;
            animation: float-particle 20s infinite linear;
        }

        @keyframes float-particle {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 0.7; }
            90% { opacity: 0.3; }
            100% { transform: translateY(-100px) scale(1); opacity: 0; }
        }

        /* Header - Desktop Only */
        .app-header {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow);
            display: none; /* Hidden by default on mobile */
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            font-size: 1.4rem;
            transition: var(--transition);
        }

        .logo:hover {
            transform: translateY(-2px);
        }

        .logo-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            filter: drop-shadow(0 4px 8px rgba(26, 35, 126, 0.3));
        }

        /* Desktop Navigation - Desktop Only */
        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .nav-link {
            text-decoration: none;
            color: var(--text);
            font-weight: 500;
            transition: var(--transition);
            padding: 0.75rem 1.25rem;
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(26, 35, 126, 0.1), transparent);
            transition: left 0.7s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            background: var(--glass-bg);
            color: var(--accent);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.15);
        }

        /* Bottom Navigation Bar - Mobile Only */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-top: 1px solid var(--glass-border);
            padding: 0.75rem 1rem;
            z-index: 1000;
            box-shadow: 0 -5px 20px rgba(26, 35, 126, 0.1);
            display: none; /* Hidden by default */
            transform: translateZ(0); /* Force hardware acceleration */
            -webkit-transform: translateZ(0);
        }

        .bottom-nav-container {
            display: flex;
            justify-content: space-around;
            align-items: center;
            max-width: 500px;
            margin: 0 auto;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: var(--light-text);
            transition: var(--transition);
            padding: 0.5rem;
            border-radius: var(--radius);
            flex: 1;
            max-width: 70px;
        }

        .nav-item.active {
            color: var(--accent);
            background: rgba(26, 35, 126, 0.1);
        }

        .nav-item:hover {
            color: var(--accent);
            transform: translateY(-2px);
        }

        .nav-icon {
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        .nav-label {
            font-size: 0.7rem;
            font-weight: 500;
            text-align: center;
        }

        /* More Menu - Mobile Only */
        .more-menu {
            position: fixed;
            bottom: 70px;
            right: 20px;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 1rem;
            box-shadow: var(--shadow-lg);
            z-index: 1001;
            display: none;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 200px;
            transform: translateZ(0); /* Force hardware acceleration */
        }

        .more-menu.active {
            display: flex;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .more-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            text-decoration: none;
            color: var(--text);
            border-radius: 10px;
            transition: var(--transition);
        }

        .more-item:hover {
            background: rgba(26, 35, 126, 0.1);
        }

        .more-icon {
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: calc(100vh - 80px);
            padding-bottom: 80px; /* Space for bottom nav on mobile */
        }

        /* Card Styles */
        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(26, 35, 126, 0.05), transparent);
            transition: left 0.7s;
        }

        .card:hover::before {
            left: 100%;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin: 0;
        }

        /* Button Styles */
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.7s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-accent);
            color: white;
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(26, 35, 126, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--accent);
            color: var(--accent);
        }

        .btn-outline:hover {
            background: var(--accent);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.2);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .app-header {
                display: none; /* Hide header on mobile */
            }

            .nav-links {
                display: none;
            }

            .bottom-nav {
                display: block; /* Show bottom nav on mobile */
            }

            .main-content {
                padding: 1rem;
                padding-bottom: 80px; /* Space for bottom nav */
                min-height: calc(100vh - 0px); /* No header on mobile */
            }
            
            /* Force bottom positioning for mobile browsers */
            @supports (-webkit-touch-callout: none) {
                .bottom-nav {
                    position: -webkit-sticky;
                    position: sticky;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                }
            }
        }

        @media (min-width: 769px) {
            .app-header {
                display: block; /* Show header on desktop */
            }

            .bottom-nav {
                display: none; /* Hide bottom nav on desktop */
            }
            
            .more-menu {
                display: none !important; /* Hide more menu on desktop */
            }
            
            .main-content {
                padding-bottom: 2rem; /* No need for bottom nav space on desktop */
                min-height: calc(100vh - 80px); /* Account for header only */
            }
        }
    </style>
</head>
<body>
    <!-- Premium Background -->
    <div class="premium-bg"></div>
    <div class="particles-container" id="particles-container"></div>

    <!-- Header - Desktop Only -->
    <header class="app-header">
        <div class="header-content">
            <a href="dashboard.php" class="logo">
                <img src="logo/logo.png" alt="Fit for the King" class="logo-img">
                <span>Fit for the King</span>
            </a>
            
            <nav class="nav-links" id="navLinks">
                <?php if (isLoggedIn()): ?>
                    <a href="dashboard.php" class="nav-link"><i class="fas fa-home"></i> Dashboard</a>
                    <a href="devotion_today.php" class="nav-link"><i class="fas fa-bible"></i> Devotion</a>
                    <a href="workout_day.php" class="nav-link"><i class="fas fa-dumbbell"></i> Workouts</a>
                    <a href="steps_calendar.php" class="nav-link"><i class="fas fa-walking"></i> Steps</a>
                    <a href="profile.php" class="nav-link"><i class="fas fa-user"></i> Profile</a>
                    
                    <?php if (isAdmin()): ?>
                        <a href="admin/index.php" class="nav-link"><i class="fas fa-crown"></i> Admin</a>
                    <?php endif; ?>
                    
                    <a href="logout.php" class="nav-link" onclick="return confirm('Are you sure you want to log out?')">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="index.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main-content">
    <!-- YOUR PAGE CONTENT GOES HERE -->
    <!-- Make sure to close main content in your page files -->
    
    <!-- Bottom Navigation (Mobile Only) -->
    <nav class="bottom-nav" id="bottomNav">
        <div class="bottom-nav-container">
            <a href="dashboard.php" class="nav-item" id="navDashboard">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="devotion_today.php" class="nav-item" id="navDevotion">
                <i class="fas fa-bible nav-icon"></i>
                <span class="nav-label">Devotion</span>
            </a>
            <a href="workout_day.php" class="nav-item" id="navWorkout">
                <i class="fas fa-dumbbell nav-icon"></i>
                <span class="nav-label">Workout</span>
            </a>
            <a href="steps_calendar.php" class="nav-item" id="navSteps">
                <i class="fas fa-walking nav-icon"></i>
                <span class="nav-label">Steps</span>
            </a>
            <a href="#" class="nav-item" id="navMore">
                <i class="fas fa-ellipsis-h nav-icon"></i>
                <span class="nav-label">More</span>
            </a>
        </div>
    </nav>

    <!-- More Menu (Mobile Only) - Shows ALL links -->
    <div class="more-menu" id="moreMenu">
        <?php if (isLoggedIn()): ?>
            <!-- Already have these in bottom nav, but include them for completeness -->
            <a href="dashboard.php" class="more-item">
                <i class="fas fa-home more-icon"></i>
                <span>Dashboard</span>
            </a>
            <a href="devotion_today.php" class="more-item">
                <i class="fas fa-bible more-icon"></i>
                <span>Devotion</span>
            </a>
            <a href="workout_day.php" class="more-item">
                <i class="fas fa-dumbbell more-icon"></i>
                <span>Workouts</span>
            </a>
            <a href="steps_calendar.php" class="more-item">
                <i class="fas fa-walking more-icon"></i>
                <span>Steps</span>
            </a>
            <a href="profile.php" class="more-item">
                <i class="fas fa-user more-icon"></i>
                <span>Profile</span>
            </a>
            
            <?php if (isAdmin()): ?>
                <a href="admin/index.php" class="more-item">
                    <i class="fas fa-crown more-icon"></i>
                    <span>Admin</span>
                </a>
            <?php endif; ?>
            
            <a href="logout.php" class="more-item" onclick="return confirm('Are you sure you want to log out?')">
                <i class="fas fa-sign-out-alt more-icon"></i>
                <span>Logout</span>
            </a>
        <?php else: ?>
            <a href="index.php" class="more-item">
                <i class="fas fa-sign-in-alt more-icon"></i>
                <span>Login</span>
            </a>
        <?php endif; ?>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // More Menu functionality (mobile only)
            const moreBtn = document.getElementById('navMore');
            const moreMenu = document.getElementById('moreMenu');
            
            if (moreBtn && moreMenu && window.innerWidth <= 768) {
                moreBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    moreMenu.classList.toggle('active');
                });
                
                // Close menu when clicking outside
                document.addEventListener('click', function(e) {
                    if (moreMenu.classList.contains('active')) {
                        if (!moreBtn.contains(e.target) && !moreMenu.contains(e.target)) {
                            moreMenu.classList.remove('active');
                        }
                    }
                });
                
                // Close menu when clicking a menu item
                const menuItems = moreMenu.querySelectorAll('.more-item');
                menuItems.forEach(item => {
                    item.addEventListener('click', function() {
                        moreMenu.classList.remove('active');
                    });
                });
            }
            
            // Highlight active page in bottom nav
            const currentPage = window.location.pathname.split('/').pop();
            const navItems = {
                'dashboard.php': 'navDashboard',
                'devotion_today.php': 'navDevotion',
                'workout_day.php': 'navWorkout',
                'steps_calendar.php': 'navSteps',
                'profile.php': 'navMore',
                'admin.php': 'navMore',
                'index.php': 'navMore'
            };
            
            if (navItems[currentPage]) {
                const activeNav = document.getElementById(navItems[currentPage]);
                if (activeNav) {
                    activeNav.classList.add('active');
                }
            }
            
            // Create particles for background
            createParticles();
        });
        
        function createParticles() {
            const container = document.getElementById('particles-container');
            if (!container) return;
            
            // Clear existing particles
            container.innerHTML = '';
            
            const particleCount = window.innerWidth < 768 ? 15 : 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size between 2-6px
                const size = Math.random() * 4 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}%`;
                
                // Random color opacity
                const opacity = Math.random() * 0.3 + 0.1;
                particle.style.opacity = opacity;
                particle.style.background = `rgba(26, 35, 126, ${opacity})`;
                
                // Random animation delay and duration
                const delay = Math.random() * 20;
                const duration = Math.random() * 10 + 20;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                
                container.appendChild(particle);
            }
        }
        
        // Recreate particles on window resize
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(createParticles, 250);
        });
        
        // Close more menu when switching to desktop
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const moreMenu = document.getElementById('moreMenu');
                if (moreMenu) {
                    moreMenu.classList.remove('active');
                }
            }
        });
    </script>
</body>
</html>