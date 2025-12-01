<?php
$pageTitle = "Login & Register";
require_once 'config.php';

$showSplash = !isset($_SESSION['splash_shown']) && !isset($_GET['nosplash']);
if ($showSplash && !isset($_SESSION['user_id'])) {
    $_SESSION['splash_shown'] = true;
    echo "<script>
        if (window.location.pathname !== '/splash.php') {
            window.location.href = 'splash.php';
        }
    </script>";
}

// Redirect if already logged in
if (isLoggedIn()) {
    echo "<script>window.location.href = 'dashboard.php';</script>";
    exit();
}

$message = '';
$error = '';

// Handle Login
if ($_POST && isset($_POST['login'])) {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    $user_query = "SELECT id, name, password_hash, role, is_accept FROM users WHERE email = ?";
    $stmt = $db->prepare($user_query);
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($password, $user['password_hash'])) {
        // Check if user is approved (for regular users)
        if ($user['role'] == 'user' && $user['is_accept'] == 0) {
            $error = "Your account is pending admin approval. Please wait for approval to access the system.";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Get profile picture for session
            $profile_query = "SELECT profile_picture FROM users WHERE id = ?";
            $stmt = $db->prepare($profile_query);
            $stmt->execute([$user['id']]);
            $profile_data = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['user_profile_picture'] = $profile_data['profile_picture'];
            
            if($user['role'] == 'admin'){
                echo "<script>window.location.href = 'admin/index.php';</script>";
            }
            else{
                echo "<script>window.location.href = 'dashboard.php';</script>";
            }
            exit();
        }
    } else {
        $error = "Invalid email or password!";
    }
}

// Handle Registration
if ($_POST && isset($_POST['signup'])) {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $name = $first_name . ' ' . $last_name;
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        // Check if email exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $stmt = $db->prepare($check_query);
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = "Email already exists!";
        } else {
            // Create user with is_accept = 0 (pending approval)
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (name, email, password_hash, is_accept) VALUES (?, ?, ?, 0)";
            $stmt = $db->prepare($insert_query);
            
            if ($stmt->execute([$name, $email, $password_hash])) {
                $message = "Registration successful! Your account is pending admin approval. You will be notified once approved.";
                $active_tab = 'signin'; // Switch to signin tab after successful registration
            } else {
                $error = "Registration failed! Please try again.";
            }
        }
    }
}

// Set active tab based on which form had error
$active_tab = 'signin';
if ($error && isset($_POST['signup'])) {
    $active_tab = 'create';
} elseif ($message && isset($_POST['signup'])) {
    $active_tab = 'signin';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register — Fit for the King</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        <?php 
        // Include the exact same CSS from your original HTML design
        // This ensures the page matches your original design perfectly
        ?>
        :root {
            /* Premium color palette */
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
            overflow: hidden;
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans";
        }

        body {
            background: var(--background);
            color: var(--text);
            display: grid;
            place-items: center;
            padding: 30px;
            line-height: 1.6;
            position: relative;
            overflow: auto;
        }

        /* Premium Background with Particles */
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
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }

            10% {
                opacity: 0.7;
            }

            90% {
                opacity: 0.3;
            }

            100% {
                transform: translateY(-100px) scale(1);
                opacity: 0;
            }
        }

        .shell {
            width: min(1200px, 100%);
            z-index: 1;
        }

        .split {
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            gap: 0;
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            background: var(--card-bg);
            transition: var(--transition);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            position: relative;
        }

        .split::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(26, 35, 126, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }

        /* LEFT: Premium Brand Section */
        .left {
            position: relative;
            padding: 70px 60px;
            background: var(--gradient-primary);
            color: var(--text);
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            z-index: 1;
        }

        .left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 15% 20%, rgba(26, 35, 126, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 85% 80%, rgba(26, 35, 126, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .logo-img {
            width: 120px;
            height: 120px;
            display: block;
            margin: 0 auto 35px;
            object-fit: contain;
            filter: drop-shadow(0 8px 20px rgba(26, 35, 126, 0.3));
            position: relative;
            z-index: 1;
            animation: logo-float 6s infinite ease-in-out;
        }

        @keyframes logo-float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }

            50% {
                transform: translateY(-10px) rotate(2deg);
            }
        }

        .title {
            font-size: 44px;
            line-height: 1.2;
            text-align: center;
            color: var(--text);
            font-weight: 700;
            margin: 0 0 18px;
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
        }

        .title span {
            background: var(--gradient-blue);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .subtitle {
            margin: 0 0 50px;
            text-align: center;
            color: var(--light-text);
            font-size: 1.25rem;
            position: relative;
            z-index: 1;
            font-weight: 400;
        }

        .benefits {
            margin-top: 15px;
            display: grid;
            gap: 22px;
            max-width: 450px;
            margin-inline: auto;
            position: relative;
            z-index: 1;
        }

        .benefit {
            display: flex;
            align-items: center;
            gap: 18px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            padding: 20px 24px;
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            transition: var(--transition);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.1);
            position: relative;
            overflow: hidden;
        }

        .benefit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(26, 35, 126, 0.1), transparent);
            transition: left 0.7s;
        }

        .benefit:hover::before {
            left: 100%;
        }

        .benefit:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(26, 35, 126, 0.15);
            border-color: rgba(26, 35, 126, 0.3);
        }

        .benefit svg {
            flex: 0 0 28px;
            fill: var(--accent);
            transition: var(--transition);
        }

        .benefit:hover svg {
            transform: scale(1.2);
        }

        .benefit span {
            color: var(--text);
            font-weight: 600;
            font-size: 1.05rem;
        }

        /* RIGHT: Premium Auth Panel */
        .right {
            padding: 60px 50px 50px;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            color: var(--text);
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            border-left: 1px solid var(--glass-border);
        }

        .right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background:
                radial-gradient(circle at 10% 20%, rgba(26, 35, 126, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(26, 35, 126, 0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .brandRow {
            display: flex;
            align-items: center;
            gap: 14px;
            color: var(--accent);
            font-weight: 700;
            letter-spacing: .3px;
            margin-bottom: 25px;
            font-size: 1.2rem;
            position: relative;
            z-index: 1;
        }

        .brand-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        h1 {
            margin: 0 0 12px;
            font-size: 34px;
            color: var(--text);
            font-weight: 700;
            letter-spacing: -0.3px;
            position: relative;
            z-index: 1;
        }

        .muted {
            color: var(--light-text);
            margin: 0 0 32px;
            font-size: 1.05rem;
            position: relative;
            z-index: 1;
            font-weight: 400;
        }

        .tabs {
            display: flex;
            gap: 12px;
            margin: 0 0 32px;
            background: rgba(255, 255, 255, 0.5);
            padding: 8px;
            border-radius: 16px;
            position: relative;
            z-index: 1;
            border: 1px solid var(--glass-border);
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 16px 18px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            background: transparent;
            color: var(--light-text);
            transition: var(--transition);
            font-size: 0.95rem;
            position: relative;
            overflow: hidden;
        }

        .tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-accent);
            opacity: 0;
            transition: opacity 0.3s;
            z-index: -1;
        }

        .tab.is-active {
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(26, 35, 126, 0.3);
        }

        .tab.is-active::before {
            opacity: 1;
            color: #ffffff;
        }

        label {
            display: block;
            color: var(--text);
            margin: 20px 0 12px;
            font-size: .95rem;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .input-container {
            position: relative;
        }

        .input {
            width: 100%;
            padding: 18px 20px;
            border-radius: 14px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.5);
            color: var(--text);
            outline: none;
            font-size: 1rem;
            transition: var(--transition);
            position: relative;
            z-index: 1;
            backdrop-filter: blur(5px);
        }

        .input::placeholder {
            color: var(--light-text);
        }

        .input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.2);
            background: rgba(255, 255, 255, 0.8);
        }

        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            z-index: 2;
        }

        .signinBtn {
            margin-top: 32px;
            width: 100%;
            padding: 18px 20px;
            border-radius: 14px;
            border: none;
            background: var(--gradient-accent);
            color: #ffffff;
            font-weight: 700;
            cursor: pointer;
            font-size: 1.05rem;
            transition: var(--transition);
            box-shadow: 0 8px 20px rgba(26, 35, 126, 0.3);
            position: relative;
            z-index: 1;
            overflow: hidden;
            letter-spacing: 0.5px;
        }

        .signinBtn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.7s;
        }

        .signinBtn:hover::before {
            left: 100%;
        }

        .signinBtn:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(26, 35, 126, 0.4);
        }

        .footer {
            margin-top: 36px;
            font-size: .9rem;
            color: var(--light-text);
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .footer a:hover {
            text-decoration: underline;
        }

        /* Panels visibility */
        .panel[hidden] {
            display: none
        }

        /* Messages */
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

        .message.info {
            background: rgba(33, 150, 243, 0.1);
            color: #2196f3;
            border-color: rgba(33, 150, 243, 0.2);
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

        /* Password toggle */
        .password-toggle {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--light-text);
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            z-index: 2;
        }

        .name-fields {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        /* Approval Notice */
        .approval-notice {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.3);
            color: #ff9800;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 500;
            animation: slideIn 0.5s ease;
        }

        .approval-notice i {
            margin-right: 0.5rem;
        }

        /* MOBILE RESPONSIVE */
        @media (max-width: 1100px) {
            body {
                padding: 20px;
            }

            .split {
                grid-template-columns: 1fr;
            }

            .left {
                padding: 50px 40px;
            }

            .right {
                padding: 50px 40px;
                border-left: none;
                border-top: 1px solid var(--glass-border);
            }
        }

        @media (max-width: 768px) {
            .title {
                font-size: 36px;
            }

            .benefits {
                gap: 18px;
            }

            .benefit {
                padding: 16px 20px;
            }

            .name-fields {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 15px;
            }

            .right {
                padding: 40px 25px 35px;
            }

            .title {
                font-size: 32px;
            }

            .tabs {
                flex-direction: column;
                gap: 8px;
            }

            .tab {
                padding: 14px 16px;
            }

            .input {
                padding: 16px 18px;
            }

            .signinBtn {
                padding: 16px 18px;
            }
        }

        @media (max-width: 768px) {
            .left {
                display: none;
            }
            
            .title {
                font-size: 36px;
            }

            .benefits {
                gap: 18px;
            }

            .benefit {
                padding: 16px 20px;
            }

            .name-fields {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Premium Background -->
    <div class="premium-bg"></div>
    <div class="particles-container" id="particles-container"></div>

    <div class="shell">
        <section class="split" role="main" aria-label="Login">
            <!-- LEFT: Premium Brand Section -->
            <aside class="left">
                <img src="logo/logo.png" alt="Fit for the King Logo" class="logo-img">
                <h2 class="title">Fit for the <span>King</span></h2>
                <p class="subtitle">Strengthening faith while building resilience</p>

                <div class="benefits">
                    <div class="benefit">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M12 22s8-4.5 8-10V5l-8-3-8 3v7c0 5.5 8 10 8 10z" />
                        </svg>
                        <span>Daily Devotions & Prayer</span>
                    </div>
                    <div class="benefit">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M3 3h18v2H3zm0 6h12v2H3zm0 6h18v2H3z" />
                        </svg>
                        <span>Progress Tracking</span>
                    </div>
                    <div class="benefit">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M12 12a5 5 0 100-10 5 5 0 000 10zm7 10v-1a4 4 0 00-4-4H9a4 4 0 00-4 4v1h14z" />
                        </svg>
                        <span>Supportive Community</span>
                    </div>
                    <div class="benefit">
                        <svg viewBox="0 0 24 24" width="20" height="20">
                            <path d="M12.1 21.35l-1.1-1.02C5.14 14.54 2 11.7 2 8.28 2 5.5 4.24 3.3 6.99 3.3c1.54 0 3.04.73 4.01 1.87C12.96 4.02 14.46 3.3 16 3.3 18.76 3.3 21 5.5 21 8.28c0 3.42-3.14 6.26-8.01 12.05l-.89 1.02z" />
                        </svg>
                        <span>Faith‑Based Fitness</span>
                    </div>
                </div>
            </aside>

            <!-- RIGHT: Premium Auth Panel -->
            <div class="right">
                <div class="brandRow" aria-hidden="true">
                    <img src="logo/logo.png" alt="Fit for the King Logo" class="brand-logo">
                    <span>Fit for the King</span>
                </div>
                <h1 id="authTitle"><?php echo $active_tab === 'signin' ? 'Welcome Back' : 'Create Your Account'; ?></h1>
                <p class="muted" id="authSubtitle"><?php echo $active_tab === 'signin' ? 'Join thousands transforming their lives' : 'Start your journey in minutes'; ?></p>

                <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>

                <?php if ($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>

                <?php if ($active_tab === 'create'): ?>
                <div class="approval-notice">
                    <i class="fas fa-clock"></i>
                    Note: All new accounts require admin approval before accessing the system.
                </div>
                <?php endif; ?>

                <div class="tabs" role="tablist">
                    <button class="tab <?php echo $active_tab === 'signin' ? 'is-active' : ''; ?>" id="tab-signin" role="tab" aria-controls="panel-signin"
                        aria-selected="<?php echo $active_tab === 'signin' ? 'true' : 'false'; ?>">Sign In</button>
                    <button class="tab <?php echo $active_tab === 'create' ? 'is-active' : ''; ?>" id="tab-create" role="tab" aria-controls="panel-create" 
                        aria-selected="<?php echo $active_tab === 'create' ? 'true' : 'false'; ?>">Create Account</button>
                </div>

                <!-- Sign In Form -->
                <form id="panel-signin" class="panel" method="POST" role="tabpanel" aria-labelledby="tab-signin" <?php echo $active_tab === 'create' ? 'hidden' : ''; ?>>
                    <input type="hidden" name="login" value="1">
                    <label for="email">Email Address</label>
                    <div class="input-container">
                        <input class="input" id="email" name="email" type="email" placeholder="john@example.com" 
                               value="<?php echo isset($_POST['email']) && $active_tab === 'signin' ? htmlspecialchars($_POST['email']) : ''; ?>" required />
                        <i class="input-icon fas fa-envelope"></i>
                    </div>

                    <label for="password">Password</label>
                    <div class="input-container">
                        <input class="input" id="password" name="password" type="password" placeholder="••••••••" required />
                        <button type="button" class="password-toggle" data-target="password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <button class="signinBtn" type="submit">
                        <i class="fas fa-arrow-right" style="margin-right: 10px;"></i>
                        Sign In to Your Account
                    </button>
                </form>

                <!-- Create Account Form -->
                <form id="panel-create" class="panel" method="POST" role="tabpanel" aria-labelledby="tab-create" <?php echo $active_tab === 'signin' ? 'hidden' : ''; ?>>
                    <input type="hidden" name="signup" value="1">
                    <div class="name-fields">
                        <div>
                            <label for="first_name">First Name</label>
                            <div class="input-container">
                                <input class="input" id="first_name" name="first_name" type="text" placeholder="John" 
                                       value="<?php echo isset($_POST['first_name']) && $active_tab === 'create' ? htmlspecialchars($_POST['first_name']) : ''; ?>" required />
                                <i class="input-icon fas fa-user"></i>
                            </div>
                        </div>
                        <div>
                            <label for="last_name">Last Name</label>
                            <div class="input-container">
                                <input class="input" id="last_name" name="last_name" type="text" placeholder="Doe" 
                                       value="<?php echo isset($_POST['last_name']) && $active_tab === 'create' ? htmlspecialchars($_POST['last_name']) : ''; ?>" required />
                                <i class="input-icon fas fa-user"></i>
                            </div>
                        </div>
                    </div>

                    <label for="email2">Email Address</label>
                    <div class="input-container">
                        <input class="input" id="email2" name="email" type="email" placeholder="john@example.com" 
                               value="<?php echo isset($_POST['email']) && $active_tab === 'create' ? htmlspecialchars($_POST['email']) : ''; ?>" required />
                        <i class="input-icon fas fa-envelope"></i>
                    </div>

                    <label for="password2">Password</label>
                    <div class="input-container">
                        <input class="input" id="password2" name="password" type="password" placeholder="Create a password"
                            minlength="6" required />
                        <button type="button" class="password-toggle" data-target="password2">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <label for="confirm">Confirm Password</label>
                    <div class="input-container">
                        <input class="input" id="confirm" name="confirm_password" type="password" placeholder="Repeat password" minlength="6"
                            required />
                        <button type="button" class="password-toggle" data-target="confirm">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <button class="signinBtn" type="submit">
                        <i class="fas fa-user-plus" style="margin-right: 10px;"></i>
                        Join the Kingdom
                    </button>
                </form>
            </div>
        </section>
    </div>

    <script>
        // Tab switcher with a11y attributes
        const tabs = [{
                btn: document.getElementById('tab-signin'),
                panel: document.getElementById('panel-signin'),
                title: 'Welcome Back',
                subtitle: 'Join thousands transforming their lives'
            },
            {
                btn: document.getElementById('tab-create'),
                panel: document.getElementById('panel-create'),
                title: 'Create Your Account',
                subtitle: 'Start your journey in minutes'
            }
        ];
        const title = document.getElementById('authTitle');
        const subtitle = document.getElementById('authSubtitle');

        function switchToTab(tabName) {
            tabs.forEach((t, i) => {
                const active = t.btn.id === `tab-${tabName}`;
                t.btn.classList.toggle('is-active', active);
                t.btn.setAttribute('aria-selected', String(active));
                if (active) {
                    t.panel.removeAttribute('hidden');
                    title.textContent = t.title;
                    subtitle.textContent = t.subtitle;
                    
                    // Show/hide approval notice
                    const approvalNotice = document.querySelector('.approval-notice');
                    if (approvalNotice) {
                        approvalNotice.style.display = tabName === 'create' ? 'block' : 'none';
                    }
                } else {
                    t.panel.setAttribute('hidden', '');
                }
            });
        }

        function activate(index) {
            tabs.forEach((t, i) => {
                const active = i === index;
                t.btn.classList.toggle('is-active', active);
                t.btn.setAttribute('aria-selected', String(active));
                if (active) {
                    t.panel.removeAttribute('hidden');
                    title.textContent = t.title;
                    subtitle.textContent = t.subtitle;
                    
                    // Show/hide approval notice
                    const approvalNotice = document.querySelector('.approval-notice');
                    if (approvalNotice) {
                        approvalNotice.style.display = i === 1 ? 'block' : 'none';
                    }
                } else {
                    t.panel.setAttribute('hidden', '');
                }
            });
        }

        tabs[0].btn.addEventListener('click', () => activate(0));
        tabs[1].btn.addEventListener('click', () => activate(1));

        // Password toggle functionality
        const passwordToggles = document.querySelectorAll('.password-toggle');
        passwordToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Create floating particles
        const particlesContainer = document.getElementById('particles-container');
        const particleCount = 30;

        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');

            // Random properties
            const size = Math.random() * 4 + 1;
            const left = Math.random() * 100;
            const animationDuration = Math.random() * 20 + 10;
            const animationDelay = Math.random() * 20;

            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${left}%`;
            particle.style.animationDuration = `${animationDuration}s`;
            particle.style.animationDelay = `${animationDelay}s`;

            particlesContainer.appendChild(particle);
        }

        // Password confirmation validation
        const signupForm = document.getElementById('panel-create');
        const confirmPasswordInput = document.getElementById('confirm');
        
        if (signupForm && confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', function() {
                const password = document.getElementById('password2').value;
                const confirmPassword = this.value;
                
                if (confirmPassword && password !== confirmPassword) {
                    this.style.borderColor = '#f44336';
                    this.style.boxShadow = '0 0 0 3px rgba(244, 67, 54, 0.2)';
                } else {
                    this.style.borderColor = '';
                    this.style.boxShadow = '';
                }
            });
            
            signupForm.addEventListener('submit', function(e) {
                const password = document.getElementById('password2').value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match! Please make sure both passwords are identical.');
                    confirmPasswordInput.focus();
                }
            });
        }
    </script>
</body>
</html>