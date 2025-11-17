<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit for the King - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
    <link rel="icon" type="image/x-icon" href="logo/logo.ico">
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

        /* Header - Simplified */
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

        /* Desktop Navigation - Simplified */
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

        /* Profile Dropdown */
        .profile-dropdown {
            position: relative;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-trigger {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            color: var(--text);
        }

        .profile-trigger:hover {
            background: var(--glass-bg);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.15);
        }

        .profile-picture {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .profile-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .profile-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 0.75rem;
            box-shadow: var(--shadow-lg);
            z-index: 101;
            display: none;
            flex-direction: column;
            gap: 0.25rem;
            min-width: 200px;
        }

        .profile-menu.active {
            display: flex;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .profile-menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--text);
            border-radius: 10px;
            transition: var(--transition);
            font-weight: 500;
        }

        .profile-menu-item:hover {
            background: rgba(26, 35, 126, 0.1);
            transform: translateX(5px);
        }

        .profile-menu-icon {
            width: 20px;
            text-align: center;
            color: var(--accent);
        }

        /* Bottom Navigation Bar */
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
            z-index: 100;
            box-shadow: 0 -5px 20px rgba(26, 35, 126, 0.1);
            display: none;
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

        /* More Menu */
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
            z-index: 101;
            display: none;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 150px;
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
            padding-bottom: 80px; /* Space for bottom nav */
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
                padding: 1rem;
            }

            .nav-links {
                display: none;
            }

            .profile-dropdown {
                display: none;
            }

            .bottom-nav {
                display: block;
            }

            .main-content {
                padding: 1rem;
                padding-bottom: 80px;
            }
        }

        @media (min-width: 769px) {
            .bottom-nav {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Premium Background -->
    <div class="premium-bg"></div>
    <div class="particles-container" id="particles-container"></div>

    <!-- Header -->
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
                    
                    <?php if (isAdmin()): ?>
                        <a href="admin/index.php" class="nav-link"><i class="fas fa-crown"></i> Admin</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="index.php" class="nav-link"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
            </nav>

            <?php if (isLoggedIn()): ?>
            <!-- Profile Dropdown -->
            <div class="profile-dropdown">
                <?php
                // Get user data for profile
                $user_id = $_SESSION['user_id'];
                $user_query = "SELECT name, profile_picture FROM users WHERE id = ?";
                $stmt = $db->prepare($user_query);
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $profile_picture = $user_data['profile_picture'] ?? 'imgs/profile.png';
                $user_name = $user_data['name'] ?? 'User';
                ?>
                <div class="profile-trigger" id="profileTrigger">
                    <img src="<?php echo $profile_picture; ?>" alt="Profile" class="profile-picture">
                    <span class="profile-name"><?php echo htmlspecialchars($user_name); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                
                <div class="profile-menu" id="profileMenu">
                    <a href="profile.php" class="profile-menu-item">
                        <i class="fas fa-user profile-menu-icon"></i>
                        <span>My Profile</span>
                    </a>
                    <a href="progress_photos_history.php" class="profile-menu-item">
                        <i class="fas fa-camera profile-menu-icon"></i>
                        <span>Progress Photos</span>
                    </a>
                    <a href="weights_history.php" class="profile-menu-item">
                        <i class="fas fa-weight-scale profile-menu-icon"></i>
                        <span>Weight History</span>
                    </a>
                    <a href="prayers_testimonials.php" class="profile-menu-item">
                        <i class="fas fa-hands-praying profile-menu-icon"></i>
                        <span>Community</span>
                    </a>
                    <?php if (isAdmin()): ?>
                        <a href="admin/index.php" class="profile-menu-item">
                            <i class="fas fa-crown profile-menu-icon"></i>
                            <span>Admin Panel</span>
                        </a>
                    <?php endif; ?>
                    <div class="profile-menu-item" style="border-top: 1px solid var(--border); margin-top: 0.5rem; padding-top: 1rem;">
                        <a href="logout.php" class="profile-menu-item" onclick="return confirm('Are you sure you want to log out?')" style="color: #f44336; width: 100%;">
                            <i class="fas fa-sign-out-alt profile-menu-icon"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="main-content">