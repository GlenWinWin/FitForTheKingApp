<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Fit for the King</title>
    <style>
        :root {
            --primary: #1a237e;
            --accent: #303f9f;
            --text: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        .splash-container {
            text-align: center;
            padding: 2rem;
        }

        .splash-logo {
            width: 120px;
            height: 120px;
            margin-bottom: 2rem;
            animation: logoPulse 2s infinite ease-in-out;
            filter: drop-shadow(0 8px 20px rgba(0, 0, 0, 0.3));
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .app-name {
            color: #1a237e;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .tagline {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1.1rem;
            margin-bottom: 3rem;
            font-weight: 400;
        }

        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="splash-container">
        <img src="logo/logo.png" alt="Fit for the King" class="splash-logo">
        <h1 class="app-name">Fit for the King</h1>
        <p class="tagline">Strengthening faith while building resilience</p>
        
        <div class="loading">
            <div class="spinner"></div>
            <p class="loading-text">Loading your journey...</p>
        </div>
    </div>

    <script>
        // Redirect after splash screen
        setTimeout(() => {
            window.location.href = '<?php echo isset($_SESSION["user_id"]) ? "dashboard.php" : "index.php"; ?>';
        }, 2000); // 2 second splash screen
        
        // Also redirect if user clicks anywhere
        document.addEventListener('click', () => {
            window.location.href = '<?php echo isset($_SESSION["user_id"]) ? "dashboard.php" : "index.php"; ?>';
        });
    </script>
</body>
</html>