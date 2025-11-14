<?php
require_once 'config.php';
requireAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Fit for the King - <?php echo $pageTitle ?? 'Dashboard'; ?></title>
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

        /* Header */
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

        .admin-badge {
            background: var(--gradient-accent);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius);
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        /* Navigation */
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

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.5);
            border: 1px solid var(--glass-border);
        }

        /* Main Content */
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: calc(100vh - 80px);
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

        /* Input Styles */
        .input-container {
            position: relative;
        }

        .input {
            width: 100%;
            padding: 1rem;
            border-radius: var(--radius);
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.5);
            color: var(--text);
            font-size: 1rem;
            transition: var(--transition);
            backdrop-filter: blur(5px);
        }

        .input::placeholder {
            color: var(--light-text);
        }

        .input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.2);
            background: rgba(255, 255, 255, 0.8);
            outline: none;
        }

        /* Grid System */
        .grid {
            display: grid;
            gap: 2rem;
        }

        .grid-2 {
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .grid-3 {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }

        .grid-4 {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }

        /* Stats Cards */
        .stat-card {
            text-align: center;
            padding: 2rem 1rem;
            background: var(--glass-bg);
            border-radius: var(--radius);
            border: 1px solid var(--glass-border);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--accent);
            margin-bottom: 0.5rem;
            background: var(--gradient-blue);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stat-label {
            color: var(--light-text);
            font-size: 1.1rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            color: var(--text);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        /* Badge Styles */
        .badge {
            background: var(--gradient-accent);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 600;
        }

        /* Message Styles */
        .message {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 600;
            animation: slideIn 0.5s ease;
            border: 1px solid transparent;
        }

        .message.error {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            border-color: rgba(244, 67, 54, 0.2);
        }

        .message.success {
            background: rgba(76, 175, 80, 0.1);
            color: #4caf50;
            border-color: rgba(76, 175, 80, 0.2);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .app-header {
                padding: 1rem;
            }

            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: var(--glass-bg);
                backdrop-filter: blur(15px);
                flex-direction: column;
                padding: 1rem;
                border-bottom: 1px solid var(--glass-border);
                box-shadow: var(--shadow-lg);
            }

            .nav-links.active {
                display: flex;
            }

            .mobile-menu-btn {
                display: block;
            }

            .main-content {
                padding: 1rem;
            }

            .grid-2, .grid-3, .grid-4 {
                grid-template-columns: 1fr;
            }

            .card {
                padding: 1.5rem;
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
            <a href="index.php" class="logo">
                <img src="logo/logo.png" alt="Fit for the King" class="logo-img">
                <span>Fit for the King</span>
                <span class="admin-badge">Admin</span>
            </a>
            
            <button class="mobile-menu-btn" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>

            <nav class="nav-links" id="navLinks">
                <a href="index.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
                <a href="workout_plans.php" class="nav-link"><i class="fas fa-dumbbell"></i> Workout Plans</a>
                <a href="progress_photos.php" class="nav-link"><i class="fas fa-camera"></i> Progress Photos</a>
                <a href="devotions.php" class="nav-link"><i class="fas fa-bible"></i> Devotions</a>
                <a href="../dashboard.php" class="nav-link"><i class="fas fa-arrow-left"></i> Back to App</a>
                <a href="../logout.php" class="nav-link" onclick="return confirm('Are you sure you want to log out?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>
    </header>

    <main class="main-content">