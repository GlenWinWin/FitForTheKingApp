</main>

    <!-- Bottom Navigation -->
    <?php if (isLoggedIn()): ?>
    <nav class="bottom-nav">
        <div class="bottom-nav-container">
            <a href="dashboard.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-home nav-icon"></i>
                <span class="nav-label">Dashboard</span>
            </a>
            <a href="devotion_today.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'devotion_today.php') ? 'active' : ''; ?>">
                <i class="fas fa-bible nav-icon"></i>
                <span class="nav-label">Devotion</span>
            </a>
            <a href="workout_day.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'workout_day.php') ? 'active' : ''; ?>">
                <i class="fas fa-dumbbell nav-icon"></i>
                <span class="nav-label">Workouts</span>
            </a>
            <a href="steps_calendar.php" class="nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'steps_calendar.php') ? 'active' : ''; ?>">
                <i class="fas fa-walking nav-icon"></i>
                <span class="nav-label">Steps</span>
            </a>
            <a href="#" class="nav-item" id="moreBtn">
                <i class="fas fa-ellipsis-h nav-icon"></i>
                <span class="nav-label">More</span>
            </a>
        </div>
    </nav>

    <!-- More Menu -->
    <div class="more-menu" id="moreMenu">
        <a href="weights_history.php" class="more-item">
            <i class="fas fa-weight-scale more-icon"></i>
            <span>Weight</span>
        </a>
        <a href="progress_photos_history.php" class="more-item">
            <i class="fas fa-camera more-icon"></i>
            <span>Progress</span>
        </a>
        <a href="prayers_testimonials.php" class="more-item">
            <i class="fas fa-hands-praying more-icon"></i>
            <span>Community</span>
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
    </div>
    <?php endif; ?>

    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            document.getElementById('navLinks').classList.toggle('active');
        });

        // More menu toggle for bottom nav
        const moreBtn = document.getElementById('moreBtn');
        const moreMenu = document.getElementById('moreMenu');
        
        if (moreBtn && moreMenu) {
            moreBtn.addEventListener('click', function(e) {
                e.preventDefault();
                moreMenu.classList.toggle('active');
            });
            
            // Close more menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!moreBtn.contains(event.target) && !moreMenu.contains(event.target)) {
                    moreMenu.classList.remove('active');
                }
            });
        }

        // Universal particles for ALL pages
        const particlesContainer = document.getElementById('particles-container');
        if (particlesContainer) {
            const particleCount = 20;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');

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
        }

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const navLinks = document.getElementById('navLinks');
            const mobileBtn = document.getElementById('mobileMenuBtn');
            
            if (navLinks && mobileBtn && !navLinks.contains(event.target) && !mobileBtn.contains(event.target)) {
                navLinks.classList.remove('active');
            }
        });

        // Password toggle functionality (for forms that need it)
        document.querySelectorAll('.password-toggle').forEach(toggle => {
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

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('ServiceWorker registration successful with scope: ', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('ServiceWorker registration failed: ', error);
                    });
            });
        }

        // Detect if app is running as PWA
        function isRunningAsPWA() {
            return window.matchMedia('(display-mode: standalone)').matches || 
                   window.navigator.standalone === true;
        }

        // PWA Install Prompt
        let deferredPrompt;
        const installButton = document.createElement('button');

        // Create install button
        function createInstallButton() {
            installButton.innerHTML = '<i class="fas fa-download"></i> Install App';
            installButton.style.cssText = `
                position: fixed;
                bottom: 100px;
                right: 20px;
                z-index: 1000;
                background: var(--gradient-accent);
                color: white;
                border: none;
                padding: 12px 20px;
                border-radius: var(--radius);
                font-weight: 600;
                cursor: pointer;
                box-shadow: var(--shadow-lg);
                transition: var(--transition);
                font-size: 14px;
            `;
            
            installButton.addEventListener('click', installApp);
            document.body.appendChild(installButton);
        }

        // Listen for install prompt
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            createInstallButton();
        });

        // Install function
        async function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                
                if (outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                    installButton.style.display = 'none';
                }
                deferredPrompt = null;
            }
        }

        // Hide button when app is installed
        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            installButton.style.display = 'none';
            deferredPrompt = null;
        });
    </script>
</body>
</html>