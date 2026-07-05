<?php
$page_title = "Donate"; ?>
<div style="display:flex; align-items:center; justify-content:center; min-height:70vh; animation: fade-in 0.5s ease-out;">
    <div class="tw-card" style="max-width:500px; width:100%;">
        <div class="tw-card-body" style="text-align:center; padding:48px 32px;">
            <div style="width:80px; height:80px; border-radius:20px; background:linear-gradient(135deg, #E9197B, #FF6B35); display:flex; align-items:center; justify-content:center; margin:0 auto 24px; box-shadow:0 8px 32px rgba(233,25,123,0.3);">
                <i class="fas fa-heart" style="font-size:36px; color:white;"></i>
            </div>

            <h1 class="text-3xl font-black gradient-text mb-4">Support Rustnite</h1>
            <p class="text-twitch-muted mb-8" style="line-height:1.6;">
                Your support keeps the servers running and helps us build more features,
                more languages, and more epic battles for the coding community! 💪
            </p>

            <div class="grid grid-cols-1 gap-4 mb-8">
                <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:8px; background:rgba(0,217,90,0.05); border:1px solid rgba(0,217,90,0.1);">
                    <i class="fas fa-server" style="color:#00D95A; font-size:20px;"></i>
                    <div style="text-align:left;">
                        <div class="font-bold text-sm">Server Infrastructure</div>
                        <div class="text-xs text-twitch-muted">Keep the code execution servers running</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:8px; background:rgba(145,71,255,0.05); border:1px solid rgba(145,71,255,0.1);">
                    <i class="fas fa-brain" style="color:#9147FF; font-size:20px;"></i>
                    <div style="text-align:left;">
                        <div class="font-bold text-sm">AI Models</div>
                        <div class="text-xs text-twitch-muted">Improve Big Pickle AI tutor capabilities</div>
                    </div>
                </div>
                <div style="display:flex; align-items:center; gap:12px; padding:12px; border-radius:8px; background:rgba(255,215,0,0.05); border:1px solid rgba(255,215,0,0.1);">
                    <i class="fas fa-wand-magic-sparkles" style="color:#FFD700; font-size:20px;"></i>
                    <div style="text-align:left;">
                        <div class="font-bold text-sm">New Features</div>
                        <div class="text-xs text-twitch-muted">Develop more languages, games, and battles</div>
                    </div>
                </div>
            </div>

            <a href="<?= PAYPAL_DONATE_URL ?>" target="_blank" class="tw-btn tw-btn-primary tw-btn-lg tw-btn-block" style="padding:16px; font-size:18px; background:linear-gradient(135deg, #E9197B, #FF6B35);">
                <i class="fab fa-paypal"></i>
                Donate via PayPal
            </a>

            <p style="font-size:11px; color:#ADADB8; margin-top:16px;">
                Every contribution, no matter how small, makes a difference ❤️
            </p>

            <div style="margin-top:24px; padding-top:20px; border-top:1px solid #2D2D35;">
                <a href="index.php?page=dashboard" class="tw-btn tw-btn-ghost">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>
