            </div>
            
            <!-- Right Content - Hidden for lesson page -->
            <?php if (($_GET['page'] ?? 'dashboard') !== 'lesson'): ?>
            <div class="right-content">
                <div class="character-hero"></div>
                
                <!-- Character Info Overlay -->
                <div>
                    <?php if (isset($_SESSION['user_id'])): 
                        $user = get_user_by_id($_SESSION['user_id']);
                        $progress_in_level = $user['xp'] - (($user['level'] - 1) * XP_PER_LEVEL);
                        $level_progress = ($progress_in_level / XP_PER_LEVEL) * 100;
                    ?>
                        <!-- User Profile Card -->
                        <div class="content-card" style="background: rgba(26, 26, 26, 0.95); margin-bottom: 32px; padding: 24px;">
                            <div class="text-muted mb-3" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Rust Developer</div>
                            <div class="title-medium" style="font-size: 20px; margin-bottom: 20px;"><?= htmlspecialchars($user['username']) ?></div>
                            
                            <div class="flex items-center space-x-4">
                                <div class="w-14 h-14 bg-orange-500 rounded-full flex items-center justify-center">
                                    <span class="font-bold text-xl"><?= $user['level'] ?></span>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm text-muted mb-2">Level <?= $user['level'] ?> Rustacean</div>
                                    <div class="progress-container" style="height: 6px;">
                                        <div class="progress-bar" style="width: <?= $level_progress ?>%"></div>
                                    </div>
                                    <div class="text-xs text-muted mt-2"><?= $progress_in_level ?>/<?= XP_PER_LEVEL ?> XP</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Statistics Section -->
                        <div>
                            <div class="text-muted mb-6" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Your Statistics</div>
                            
                            <?php
                            $completed_lessons = $pdo->prepare("SELECT COUNT(*) as count FROM user_progress WHERE user_id = ? AND completed = 1");
                            $completed_lessons->execute([$_SESSION['user_id']]);
                            $completed_count = $completed_lessons->fetch()['count'];
                            
                            $total_lessons = $pdo->prepare("SELECT COUNT(*) as count FROM lessons");
                            $total_lessons->execute();
                            $total_count = $total_lessons->fetch()['count'];
                            
                            $user_rank = $pdo->prepare("SELECT COUNT(*) + 1 as rank FROM users WHERE xp > ?");
                            $user_rank->execute([$user['xp']]);
                            $rank = $user_rank->fetch()['rank'];
                            ?>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div class="stat-item" style="padding: 20px 16px;">
                                    <div class="stat-value" style="font-size: 32px; margin-bottom: 4px;"><?= $completed_count ?></div>
                                    <div class="stat-label" style="font-size: 12px; text-transform: uppercase;">Lessons</div>
                                </div>
                                <div class="stat-item" style="padding: 20px 16px;">
                                    <div class="stat-value" style="font-size: 32px; margin-bottom: 4px;"><?= number_format($user['xp']) ?></div>
                                    <div class="stat-label" style="font-size: 12px; text-transform: uppercase;">Total XP</div>
                                </div>
                                <div class="stat-item" style="padding: 20px 16px;">
                                    <div class="stat-value" style="font-size: 32px; margin-bottom: 4px;">#<?= $rank ?></div>
                                    <div class="stat-label" style="font-size: 12px; text-transform: uppercase;">Rank</div>
                                </div>
                                <div class="stat-item" style="padding: 20px 16px;">
                                    <div class="stat-value" style="font-size: 32px; margin-bottom: 4px;"><?= round(($completed_count / max($total_count, 1)) * 100) ?>%</div>
                                    <div class="stat-label" style="font-size: 12px; text-transform: uppercase;">Progress</div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Welcome message for non-logged users -->
                        <div class="content-card" style="background: rgba(26, 26, 26, 0.95); padding: 32px;">
                            <div class="title-medium" style="margin-bottom: 16px;">Join the Battle</div>
                            <div class="text-secondary mb-8" style="line-height: 1.6;">
                                Master Rust programming through epic challenges and compete with developers worldwide.
                            </div>
                            <div class="space-y-4">
                                <a href="index.php?page=register" class="btn-primary block text-center" style="padding: 14px 24px;">
                                    Start Your Journey
                                </a>
                                <a href="index.php?page=login" class="btn-secondary block text-center" style="padding: 14px 24px;">
                                    Login
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Mobile menu functionality
        function toggleMobileMenu() {
            const sidebar = document.getElementById('mobile-sidebar');
            sidebar.classList.toggle('mobile-open');
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('mobile-sidebar');
            const toggle = document.querySelector('.mobile-menu-toggle');
            
            if (sidebar && toggle && !sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('mobile-open');
            }
        });
        
        // Close mobile menu when navigating
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', function() {
                const sidebar = document.getElementById('mobile-sidebar');
                if (sidebar) {
                    sidebar.classList.remove('mobile-open');
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('mobile-sidebar');
                if (sidebar) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });

        // Enhanced notifications
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 100px;
                background: ${type === 'success' ? '#FF6B35' : '#DC2626'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: 600;
                z-index: 1000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 4000);
        }

        // Sidebar icon click handlers
        document.querySelectorAll('.sidebar-icon a').forEach(link => {
            link.addEventListener('click', function(e) {
                // Remove active class from all icons
                document.querySelectorAll('.sidebar-icon').forEach(icon => {
                    icon.classList.remove('active');
                });
                
                // Add active class to clicked icon's parent
                this.parentElement.classList.add('active');
            });
        });

        // Search functionality
        const searchInput = document.querySelector('.search-input');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    const query = this.value.trim();
                    if (query) {
                        // Implement search functionality
                        console.log('Searching for:', query);
                    }
                }
            });
        }
    </script>
</body>
</html>