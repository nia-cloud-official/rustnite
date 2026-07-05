<?php if (!isset($_SESSION["user_id"])): ?>
<?php
$page_title = "Home";
$languages = get_languages();
$lang_count = count($languages); // Count users
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$total_users = $stmt->fetch()["count"];
?>
<!-- EPIC TWITCH-STYLE LANDING PAGE -->
<div style="min-height:100vh; position:relative; overflow:hidden;">
    <!-- Animated Grid Background -->
    <div style="position:fixed; inset:0; z-index:0; overflow:hidden;">
        <div id="landing-grid"></div>
        <div style="position:absolute; inset:0; background:radial-gradient(ellipse at 50% 0%, rgba(145,71,255,0.15) 0%, transparent 70%);"></div>
    </div>

    <!-- Hero Content -->
    <div style="position:relative; z-index:10; display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:100vh; text-align:center; padding:40px 20px;">
        <!-- Animated Logo -->
        <div style="margin-bottom:32px; animation: bounce-in 1s ease-out;">
            <div class="tw-logo" style="width:100px; height:100px; font-size:48px; margin:0 auto; box-shadow:0 0 60px rgba(145,71,255,0.3);">R</div>
        </div>

        <!-- Main Title with Glitch -->
        <div style="animation: slide-up 0.8s ease-out; margin-bottom:16px;">
            <h1 style="font-size:clamp(48px, 10vw, 120px); font-weight:900; line-height:1; letter-spacing:-2px;">
                <span class="gradient-text">RUSTNITE</span>
            </h1>
            <div style="font-size:clamp(16px, 3vw, 32px); font-weight:300; color:#ADADB8; margin-top:8px; letter-spacing:4px; text-transform:uppercase;">
                <span id="landing-typewriter"></span><span style="animation:pulse-ring 1s infinite;">|</span>
            </div>
        </div>

        <!-- Tagline -->
        <p style="font-size:clamp(14px, 1.5vw, 20px); color:#ADADB8; max-width:600px; margin-bottom:40px; animation: slide-up 1s ease-out; line-height:1.6;">
            The <span style="color:#9147FF; font-weight:600;">battle-royale coding arena</span> where you master
            <span style="font-weight:600; color:#fff;">multiple programming languages</span> through
            epic challenges, AI-powered tutoring, and real-time competition.
        </p>

        <!-- CTA Buttons -->
        <div style="display:flex; gap:16px; flex-wrap:wrap; justify-content:center; animation: slide-up 1.2s ease-out;">
            <a href="index.php?page=register" class="tw-btn tw-btn-primary tw-btn-lg" style="padding:16px 40px; font-size:18px; animation:pulse-glow 2s infinite;">
                <i class="fas fa-rocket"></i>
                Enter the Arena
            </a>
            <a href="index.php?page=login" class="tw-btn" style="padding:16px 40px; font-size:18px; background:rgba(145,71,255,0.1); border:1px solid rgba(145,71,255,0.3); color:#EFEFF1;">
                <i class="fas fa-sign-in-alt"></i>
                Rejoin Battle
            </a>
        </div>

        <!-- Live Stats -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:24px; max-width:800px; width:100%; margin-top:60px; animation: slide-up 1.4s ease-out;">
            <div class="stat-item" style="background:rgba(30,30,40,0.5); backdrop-filter:blur(10px); border:1px solid rgba(145,71,255,0.15); padding:20px;">
                <div class="stat-value" id="hero-warriors" style="font-size:36px; color:#9147FF;">0</div>
                <div class="stat-label" style="font-size:12px; text-transform:uppercase; letter-spacing:1px;">Coders</div>
            </div>
            <div class="stat-item" style="background:rgba(30,30,40,0.5); backdrop-filter:blur(10px); border:1px solid rgba(0,217,90,0.15); padding:20px;">
                <div class="stat-value" id="hero-languages" style="font-size:36px; color:#00D95A;">0</div>
                <div class="stat-label" style="font-size:12px; text-transform:uppercase; letter-spacing:1px;">Languages</div>
            </div>
            <div class="stat-item" style="background:rgba(30,30,40,0.5); backdrop-filter:blur(10px); border:1px solid rgba(255,215,0,0.15); padding:20px;">
                <div class="stat-value" id="hero-challenges" style="font-size:36px; color:#FFD700;">0</div>
                <div class="stat-label" style="font-size:12px; text-transform:uppercase; letter-spacing:1px;">Challenges</div>
            </div>
            <div class="stat-item" style="background:rgba(30,30,40,0.5); backdrop-filter:blur(10px); border:1px solid rgba(169,112,255,0.15); padding:20px;">
                <div class="stat-value" style="font-size:36px; color:#A970FF;">Free</div>
                <div class="stat-label" style="font-size:12px; text-transform:uppercase; letter-spacing:1px;">Forever</div>
            </div>
        </div>
    </div>

    <!-- Language Strip -->
    <div style="position:relative; z-index:10; padding:40px 20px; border-top:1px solid rgba(255,255,255,0.05); border-bottom:1px solid rgba(255,255,255,0.05); overflow:hidden;">
        <div class="lang-scroll">
            <div class="lang-scroll-inner">
                <?php for ($repeat = 0; $repeat < 3; $repeat++): ?>
                    <?php foreach ($languages as $lang): ?>
                        <div class="lang-scroll-item">
                            <i class="<?= $lang[
                                "icon"
                            ] ?>" style="color:<?= $lang[
    "color"
] ?>; font-size:24px;"></i>
                            <span style="color:#ADADB8; font-weight:500;"><?= $lang[
                                "name"
                            ] ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <!-- Features Grid -->
    <div style="position:relative; z-index:10; padding:80px 20px; max-width:1200px; margin:0 auto;">
        <h2 style="text-align:center; font-size:clamp(28px, 4vw, 48px); font-weight:900; margin-bottom:60px;">
            <span class="gradient-text">Battle-Tested Features</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $features = [
                [
                    "icon" => "fa-graduation-cap",
                    "title" => "Multi-Language",
                    "desc" =>
                        "Learn Rust, Python, JavaScript, Go, and more. Switch between languages seamlessly.",
                    "color" => "#9147FF",
                ],
                [
                    "icon" => "fa-crosshairs",
                    "title" => "Battle Royale",
                    "desc" =>
                        "Real-time coding battles. Compete against other developers in live arena matches.",
                    "color" => "#E9197B",
                ],
                [
                    "icon" => "fa-robot",
                    "title" => "AI Tutor",
                    "desc" =>
                        "Big Pickle AI assistant helps you debug, explains concepts, and guides your learning.",
                    "color" => "#00D95A",
                ],
                [
                    "icon" => "fa-gamepad",
                    "title" => "Mini-Games",
                    "desc" =>
                        "Syntax speed races, bug hunts, output predictions - learning through play.",
                    "color" => "#FF6B35",
                ],
                [
                    "icon" => "fa-trophy",
                    "title" => "Leaderboards",
                    "desc" =>
                        "Global rankings, weekly champions, language-specific leaderboards. Climb to the top!",
                    "color" => "#FFD700",
                ],
                [
                    "icon" => "fa-code",
                    "title" => "Real Code Editor",
                    "desc" =>
                        "Monaco editor with multi-language support, real execution via Docker/Piston API.",
                    "color" => "#A970FF",
                ],
            ];
            foreach ($features as $f): ?>
                <div class="tw-card tw-card-body feature-card" style="text-align:center; padding:32px; border-color:rgba(255,255,255,0.05);">
                    <div style="width:64px; height:64px; border-radius:16px; background:<?= $f[
                        "color"
                    ] ?>15; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                        <i class="fas <?= $f[
                            "icon"
                        ] ?>" style="font-size:28px; color:<?= $f[
    "color"
] ?>;"></i>
                    </div>
                    <h3 style="font-size:18px; font-weight:700; margin-bottom:8px;"><?= $f[
                        "title"
                    ] ?></h3>
                    <p style="font-size:13px; color:#ADADB8; line-height:1.6;"><?= $f[
                        "desc"
                    ] ?></p>
                </div>
            <?php endforeach;
            ?>
        </div>
    </div>

    <!-- Final CTA -->
    <div style="position:relative; z-index:10; padding:80px 20px; text-align:center;">
        <div class="tw-card tw-card-body" style="max-width:600px; margin:0 auto; padding:48px; background:linear-gradient(135deg, rgba(145,71,255,0.1), rgba(233,25,123,0.05)); border-color:rgba(145,71,255,0.2);">
            <div style="font-size:48px; margin-bottom:16px;">🔥</div>
            <h2 style="font-size:32px; font-weight:900; margin-bottom:12px;" class="gradient-text">Ready to Dominate?</h2>
            <p style="color:#ADADB8; margin-bottom:32px; font-size:16px;">
                Join thousands of developers mastering multiple languages through epic battles.
                <span style="color:#00D95A; font-weight:600;">Free forever.</span>
                <span style="color:#9147FF; font-weight:600;">No credit card.</span>
            </p>
            <a href="index.php?page=register" class="tw-btn tw-btn-primary tw-btn-lg" style="padding:16px 48px; font-size:18px; animation:pulse-glow 2s infinite;">
                <i class="fas fa-rocket"></i>
                Start Your Journey
            </a>
        </div>
    </div>
</div>

<style>
    /* Animated grid */
    #landing-grid {
        position: absolute;
        inset: -50%;
        width: 200%;
        height: 200%;
        background-image:
            linear-gradient(rgba(145,71,255,0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(145,71,255,0.03) 1px, transparent 1px);
        background-size: 60px 60px;
        animation: grid-scroll 20s linear infinite;
        transform-origin: center;
    }

    @keyframes grid-scroll {
        0% { transform: translate(0, 0) rotate(0deg); }
        100% { transform: translate(-60px, -60px) rotate(1deg); }
    }

    /* Language scroll strip */
    .lang-scroll {
        overflow: hidden;
        width: 100%;
    }

    .lang-scroll-inner {
        display: flex;
        gap: 40px;
        animation: scroll-languages 30s linear infinite;
        width: max-content;
    }

    .lang-scroll-item {
        display: flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        padding: 8px 16px;
        border-radius: 8px;
        background: rgba(30,30,40,0.3);
        border: 1px solid rgba(255,255,255,0.05);
    }

    @keyframes scroll-languages {
        0% { transform: translateX(0); }
        100% { transform: translateX(-33.33%); }
    }

    /* Feature cards */
    .feature-card {
        transition: all 0.3s ease;
    }
    .feature-card:hover {
        transform: translateY(-8px) scale(1.02);
        border-color: rgba(145,71,255,0.3) !important;
        box-shadow: 0 20px 40px rgba(145,71,255,0.1);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Typewriter
    const phrases = [
        "Multi-Language Coding Arena 🔥",
        "Battle-Royale Learning 💪",
        "AI-Powered Tutoring 🚀",
        "Compete & Dominate 🏆"
    ];

    let phraseIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    const el = document.getElementById('landing-typewriter');

    function typeWriter() {
        if (!el) return;
        const current = phrases[phraseIndex];

        if (isDeleting) {
            el.textContent = current.substring(0, charIndex - 1);
            charIndex--;
        } else {
            el.textContent = current.substring(0, charIndex + 1);
            charIndex++;
        }

        let speed = isDeleting ? 30 : 60;

        if (!isDeleting && charIndex === current.length) {
            speed = 2000;
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            phraseIndex = (phraseIndex + 1) % phrases.length;
            speed = 300;
        }

        setTimeout(typeWriter, speed);
    }

    typeWriter();

    // Animate counters
    function animateCounter(id, target, duration = 2000) {
        const el = document.getElementById(id);
        if (!el) return;
        const start = 0;
        const increment = target / (duration / 16);
        let current = start;

        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                el.textContent = target;
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(current);
            }
        }, 16);
    }

    setTimeout(() => animateCounter('hero-warriors', <?= $total_users ?:
        42 ?>), 500);
    setTimeout(() => animateCounter('hero-languages', <?= $lang_count ?>), 800);
    setTimeout(() => animateCounter('hero-challenges', 20), 1100);
});
</script>

<?php else:header("Location: index.php?page=dashboard");
    exit();endif; ?>
