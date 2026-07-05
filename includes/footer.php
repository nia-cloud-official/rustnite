    </div><!-- .tw-content -->
</div><!-- .tw-main -->

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

// ====== PARTICLES BACKGROUND ======
(function initParticles() {
    const canvas = document.getElementById('particles-canvas');
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    let particles = [];
    let mouseX = 0;
    let mouseY = 0;

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    resize();
    window.addEventListener('resize', resize);

    class Particle {
        constructor() {
            this.reset();
        }

        reset() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.size = Math.random() * 2 + 0.5;
            this.speedX = (Math.random() - 0.5) * 0.5;
            this.speedY = (Math.random() - 0.5) * 0.5;
            this.opacity = Math.random() * 0.5 + 0.1;
            this.pulse = Math.random() * Math.PI * 2;
        }

        update() {
            this.pulse += 0.01;
            this.x += this.speedX + (mouseX - canvas.width/2) * 0.0001;
            this.y += this.speedY + (mouseY - canvas.height/2) * 0.0001;
            this.currentOpacity = this.opacity * (0.5 + 0.5 * Math.sin(this.pulse));

            if (this.x < 0 || this.x > canvas.width || this.y < 0 || this.y > canvas.height) {
                this.reset();
            }
        }

        draw() {
            ctx.beginPath();
            ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(145, 71, 255, ${this.currentOpacity})`;
            ctx.fill();
        }
    }

    for (let i = 0; i < 80; i++) {
        particles.push(new Particle());
    }

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        // Draw connections
        for (let i = 0; i < particles.length; i++) {
            for (let j = i + 1; j < particles.length; j++) {
                const dx = particles[i].x - particles[j].x;
                const dy = particles[i].y - particles[j].y;
                const dist = Math.sqrt(dx * dx + dy * dy);

                if (dist < 150) {
                    ctx.beginPath();
                    ctx.moveTo(particles[i].x, particles[i].y);
                    ctx.lineTo(particles[j].x, particles[j].y);
                    ctx.strokeStyle = `rgba(145, 71, 255, ${0.1 * (1 - dist/150)})`;
                    ctx.lineWidth = 0.5;
                    ctx.stroke();
                }
            }
        }

        particles.forEach(p => {
            p.update();
            p.draw();
        });

        requestAnimationFrame(animate);
    }

    animate();
})();

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

// ====== TOGGLE NOTIFICATIONS ======
function toggleNotifications() {
    // Placeholder - will be implemented with a dropdown
    showToast('Notifications coming soon!', 'info');
}

// ====== CONSOLE EASTER EGG ======
console.log('%c🦀 Rustnite v<?= APP_VERSION ?>', 'font-size:24px; font-weight:bold; color:#9147FF;');
console.log('%cBattle-Royale Coding Arena', 'font-size:14px; color:#ADADB8;');
console.log('%c🚀 Let\'s code like a legend!', 'font-size:12px; color:#00D95A;');
</script>
</body>
</html>
