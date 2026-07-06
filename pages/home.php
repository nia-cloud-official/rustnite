<?php
$page_title = "Welcome"; ?>
<div class="min-h-screen flex flex-col items-center justify-center text-center px-4" style="animation: fade-in 0.8s ease-out; position: relative; overflow: hidden;">
    <!-- Background gradient -->
    <div style="position: fixed; inset: 0; background: radial-gradient(ellipse at 50% 0%, rgba(145,71,255,0.15) 0%, transparent 60%), radial-gradient(ellipse at 80% 80%, rgba(233,25,123,0.08) 0%, transparent 50%), radial-gradient(ellipse at 20% 80%, rgba(255,107,53,0.08) 0%, transparent 50%); z-index: -1;"></div>

    <!-- Floating orbs -->
    <div style="position: fixed; width: 300px; height: 300px; border-radius: 50%; background: radial-gradient(circle, rgba(145,71,255,0.08) 0%, transparent 70%); top: -100px; right: -100px; animation: float 8s ease-in-out infinite; z-index: -1;"></div>
    <div style="position: fixed; width: 200px; height: 200px; border-radius: 50%; background: radial-gradient(circle, rgba(233,25,123,0.06) 0%, transparent 70%); bottom: 10%; left: 5%; animation: float 6s ease-in-out infinite 2s; z-index: -1;"></div>
    <div style="position: fixed; width: 400px; height: 400px; border-radius: 50%; background: radial-gradient(circle, rgba(0,217,90,0.04) 0%, transparent 70%); bottom: -150px; right: 20%; z-index: -1;"></div>

    <div class="max-w-3xl mx-auto">
        <!-- Logo -->
        <img src="assets/logo.png" alt="Rustnite" style="height: 64px; margin: 0 auto 32px; filter: drop-shadow(0 0 40px rgba(145,71,255,0.3)); animation: pulse-glow 3s ease-in-out infinite;">

        <h1 class="text-6xl font-black mb-4 gradient-text" style="animation: slide-up 0.6s ease-out 0.1s both;">
            Rustnite
        </h1>
        <p class="text-xl text-twitch-muted mb-8 max-w-2xl mx-auto" style="animation: slide-up 0.6s ease-out 0.2s both;">
            The battle-royale coding arena. Learn any language, compete in real-time,
            battle other coders, and level up your skills.
        </p>

        <!-- Feature Pills -->
        <div class="flex flex-wrap justify-center gap-3 mb-10" style="animation: slide-up 0.6s ease-out 0.3s both;">
            <span class="px-4 py-2 rounded-full text-sm font-medium" style="background: rgba(145,71,255,0.1); color:#A970FF; border:1px solid rgba(145,71,255,0.2);">
                <i class="fab fa-rust mr-1"></i>Rust
            </span>
            <span class="px-4 py-2 rounded-full text-sm font-medium" style="background: rgba(55,118,171,0.1); color:#5BA3D9; border:1px solid rgba(55,118,171,0.2);">
                <i class="fab fa-python mr-1"></i>Python
            </span>
            <span class="px-4 py-2 rounded-full text-sm font-medium" style="background: rgba(247,223,30,0.1); color:#F7DF1E; border:1px solid rgba(247,223,30,0.2);">
                <i class="fab fa-js mr-1"></i>JavaScript
            </span>
            <span class="px-4 py-2 rounded-full text-sm font-medium" style="background: rgba(49,120,198,0.1); color:#5BA3D9; border:1px solid rgba(49,120,198,0.2);">
                <i class="fab fa-typescript mr-1"></i>TypeScript
            </span>
            <span class="px-4 py-2 rounded-full text-sm font-medium" style="background: rgba(0,173,216,0.1); color:#00ADD8; border:1px solid rgba(0,173,216,0.2);">
                <i class="fab fa-golang mr-1"></i>Go
            </span>
            <span class="px-4 py-2 rounded-full text-sm font-medium" style="background: rgba(237,139,0,0.1); color:#ED8B00; border:1px solid rgba(237,139,0,0.2);">
                <i class="fab fa-java mr-1"></i>Java
            </span>
            <span class="px-4 py-2 rounded-full text-sm font-medium" style="background: rgba(0,89,156,0.1); color:#5BA3D9; border:1px solid rgba(0,89,156,0.2);">
                <i class="fas fa-copyright mr-1"></i>C/C++
            </span>
        </div>

        <!-- CTA Buttons -->
        <div class="flex flex-wrap justify-center gap-4" style="animation: slide-up 0.6s ease-out 0.4s both;">
            <a href="index.php?page=login" class="tw-btn tw-btn-primary tw-btn-lg px-8">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Get Started
            </a>
            <a href="index.php?page=register" class="tw-btn px-8 tw-btn-lg" style="background:#2D2D35; color:#EFEFF1; border:1px solid #3A3A45;">
                <i class="fas fa-user-plus mr-2"></i>
                Create Account
            </a>
        </div>

        <!-- Feature Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-20" style="animation: slide-up 0.6s ease-out 0.5s both;">
            <div class="tw-card tw-card-body text-center p-6">
                <div class="text-3xl mb-3" style="color:#9147FF;">🎮</div>
                <h3 class="font-bold mb-2">Battle Royale</h3>
                <p class="text-sm text-twitch-muted">Compete against other coders in real-time coding battles. Last coder standing wins!</p>
            </div>
            <div class="tw-card tw-card-body text-center p-6">
                <div class="text-3xl mb-3" style="color:#FF6B35;">🤖</div>
                <h3 class="font-bold mb-2">AI Tutor</h3>
                <p class="text-sm text-twitch-muted">Learn with Big Pickle, your personal AI coding tutor. Get help, explanations, and code examples.</p>
            </div>
            <div class="tw-card tw-card-body text-center p-6">
                <div class="text-3xl mb-3" style="color:#00D95A;">🏆</div>
                <h3 class="font-bold mb-2">XP & Leaderboards</h3>
                <p class="text-sm text-twitch-muted">Earn XP, climb the leaderboard, unlock achievements, and show off your skills.</p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-16 mb-8 text-xs text-twitch-muted" style="animation: slide-up 0.6s ease-out 0.6s both;">
            <p>Powered by Big Pickle AI &bull; Multi-Language Coding Arena</p>
        </div>
    </div>
</div>
