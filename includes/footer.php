<?php if (!($hide_chrome ?? false)): ?>
    </div><!-- .tw-content -->
</div><!-- .tw-main -->
<?php else: ?>
</div><!-- .landing -->
<?php endif; ?>

<!-- Mobile Overlay -->
<div id="mobile-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.6); z-index:99;" onclick="toggleMobileSidebar()"></div>

<!-- ====== GLOBAL JAVASCRIPT ====== -->
<script>
// ====== NOTIFICATION SYSTEM ======
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const icons = {
        success: '<i class="fas fa-check-circle" style="color:#00D95A;"></i>',
        error: '<i class="fas fa-exclamation-circle" style="color:#E9197B;"></i>',
        info: '<i class="fas fa-info-circle" style="color:#9147FF;"></i>'
    };

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        ${icons[type] || icons.info}
        <span style="flex:1;">${message}</span>
        <button onclick="this.parentElement.remove()" style="background:none;border:none;color:#ADADB8;cursor:pointer;font-size:16px;">&times;</button>
    `;

    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// ====== CONFETTI SYSTEM ======
function fireConfetti(count = 50) {
    const colors = ['#9147FF', '#E9197B', '#00D95A', '#FF6B35', '#A970FF', '#FFD700'];

    for (let i = 0; i < count; i++) {
        const piece = document.createElement('div');
        piece.className = 'confetti-piece';
        piece.style.left = Math.random() * 100 + 'vw';
        piece.style.background = colors[Math.floor(Math.random() * colors.length)];
        piece.style.width = (Math.random() * 8 + 4) + 'px';
        piece.style.height = (Math.random() * 8 + 4) + 'px';
        piece.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
        piece.style.animationDuration = (Math.random() * 2 + 2) + 's';
        piece.style.animationDelay = (Math.random() * 0.5) + 's';

        document.body.appendChild(piece);

        setTimeout(() => piece.remove(), 4000);
    }
}

// ====== MOBILE SIDEBAR ======
function toggleMobileSidebar() {
    const sidebar = document.getElementById('main-sidebar');
    const overlay = document.getElementById('mobile-overlay');

    if (sidebar.classList.contains('mobile-open')) {
        sidebar.classList.remove('mobile-open');
        overlay.style.display = 'none';
    } else {
        sidebar.classList.add('mobile-open');
        overlay.style.display = 'block';
    }
}

// Close sidebar on nav click (mobile)
document.querySelectorAll('.tw-nav-item').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 768) {
            toggleMobileSidebar();
        }
    });
});

// ====== PARTICLES BACKGROUND REMOVED ======

// ====== GLOBAL SEARCH ======
const searchInput = document.getElementById('global-search');
if (searchInput) {
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            const query = this.value.trim();
            if (query) {
                window.location.href = 'index.php?page=lessons&search=' + encodeURIComponent(query);
            }
        }
    });
}

// ====== LIVE XP UPDATES ======
function updateXPBar(newXP, newLevel) {
    const xpBar = document.querySelector('.xp-bar');
    const levelDisplay = document.querySelector('.tw-user-info .xp');
    if (!xpBar || !levelDisplay) return;

    const xpPerLevel = <?= XP_PER_LEVEL ?>;
    const currentLevelXp = (newLevel - 1) * xpPerLevel;
    const progress = ((newXP - currentLevelXp) / (newLevel * xpPerLevel - currentLevelXp)) * 100;

    xpBar.style.width = Math.min(100, Math.max(0, progress)) + '%';
    levelDisplay.textContent = `Level ${newLevel} · ${newXP.toLocaleString()} XP`;
}

// ====== INTERACTIVE ANIMATIONS ======
document.addEventListener('DOMContentLoaded', function() {
    // Animate cards on scroll
    const cards = document.querySelectorAll('.tw-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s ease-out';
        observer.observe(card);
    });
});

// ====== NOTIFICATIONS DROPDOWN ======
function toggleNotifications() {
    let panel = document.getElementById('notif-panel');
    if (panel) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        return;
    }

    // Create panel
    panel = document.createElement('div');
    panel.id = 'notif-panel';
    panel.style.cssText = 'position:fixed; top:60px; right:100px; width:360px; max-height:480px; background:#18181B; border:1px solid #3A3A45; border-radius:8px; z-index:9999; overflow:hidden; box-shadow:0 8px 32px rgba(0,0,0,0.5); animation: slide-up 0.2s ease-out;';

    panel.innerHTML = '<div style="padding:12px 16px; border-bottom:1px solid #2D2D35; display:flex; justify-content:space-between; align-items:center;">' +
        '<span class="font-bold text-sm"><i class="fas fa-bell" style="color:#9147FF;"></i> Notifications</span>' +
        '<button onclick="document.getElementById(\'notif-panel\').style.display=\'none\'" style="background:none;border:none;color:#ADADB8;cursor:pointer;">&times;</button>' +
        '</div>' +
        '<div id="notif-list" style="overflow-y:auto; max-height:400px; padding:8px;">' +
        '<div style="text-align:center; padding:20px; color:#ADADB8; font-size:13px;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>' +
        '</div>' +
        '<div style="padding:8px 16px; border-top:1px solid #2D2D35; text-align:center;">' +
        '<a href="index.php?page=notifications" class="text-xs text-twitch-purple" style="text-decoration:none;">View All</a>' +
        '</div>';

    document.body.appendChild(panel);

    // Fetch notifications via AJAX
    fetch('api/notifications.php')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('notif-list');
            if (!data.notifications || data.notifications.length === 0) {
                list.innerHTML = '<div style="text-align:center; padding:20px; color:#ADADB8; font-size:13px;"><i class="fas fa-bell-slash" style="font-size:24px; margin-bottom:8px; display:block;"></i>No notifications yet</div>';
                return;
            }
            list.innerHTML = data.notifications.map(n => {
                const icons = { badge_earned: 'fa-award', level_up: 'fa-arrow-up', lesson_completed: 'fa-check', br_event: 'fa-crosshairs', mini_game: 'fa-gamepad', streak: 'fa-fire', follow: 'fa-user-plus', like: 'fa-heart' };
                const icon = icons[n.type] || 'fa-bell';
                const time = timeAgo(n.created_at);
                return '<div class="notif-item" style="padding:10px 12px; border-radius:6px; cursor:pointer; display:flex; gap:10px; align-items:start;" onmouseover="this.style.background=\'#2D2D35\'" onmouseout="this.style.background=\'transparent\'" onclick="this.style.opacity=\'0.6\'">' +
                    '<div style="width:32px; height:32px; border-radius:50%; background:rgba(145,71,255,0.1); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><i class="fas ' + icon + '" style="color:#9147FF; font-size:12px;"></i></div>' +
                    '<div style="flex:1;">' +
                    '<div class="text-sm font-medium">' + escapeHtml(n.title) + '</div>' +
                    '<div class="text-xs text-twitch-muted">' + escapeHtml(n.message) + '</div>' +
                    '<div class="text-xs text-twitch-muted" style="margin-top:2px;">' + time + '</div>' +
                    '</div></div>';
            }).join('');
        })
        .catch(() => {
            const list = document.getElementById('notif-list');
            if (list) list.innerHTML = '<div style="text-align:center; padding:20px; color:#ADADB8;">Failed to load notifications</div>';
        });
}

function timeAgo(dateStr) {
    const now = new Date();
    const date = new Date(dateStr);
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return "just now";
    if (diff < 3600) return Math.floor(diff/60) + "m ago";
    if (diff < 86400) return Math.floor(diff/3600) + "h ago";
    return Math.floor(diff/86400) + "d ago";
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close notification panel on click outside
document.addEventListener('click', function(e) {
    const panel = document.getElementById('notif-panel');
    if (panel && !panel.contains(e.target) && !e.target.closest('[onclick="toggleNotifications()"]')) {
        panel.style.display = 'none';
    }
});

// ====== CONSOLE EASTER EGG ======
console.log('%c🦀 Rustnite - Battle-Royale Coding Arena', 'font-size:24px; font-weight:bold; color:#9147FF;');
console.log('%c🚀 Let\'s code like a legend!', 'font-size:12px; color:#00D95A;');
</script>
</body>
</html>
