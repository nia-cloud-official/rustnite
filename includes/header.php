<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/favicon.png">
    <title><?= (isset($page_title) ? $page_title . " - " : "") .
        APP_NAME ?> - <?= APP_TAGLINE ?></title>

    <!-- Tailwind + Icons + Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Twitch-inspired Tailwind Config -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        twitch: {
                            bg: '#0E0E10',
                            dark: '#18181B',
                            medium: '#1F1F23',
                            light: '#2D2D35',
                            border: '#3A3A45',
                            hover: '#4A4A55',
                            text: '#EFEFF1',
                            muted: '#ADADB8',
                            purple: '#9147FF',
                            'purple-light': '#A970FF',
                            'purple-dark': '#772CE8',
                            pink: '#E9197B',
                            orange: '#FF6B35',
                        }
                    },
                    fontFamily: {
                        'display': ['Inter', 'system-ui', 'sans-serif'],
                    },
                    animation: {
                        'glitch': 'glitch 3s infinite',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-glow': 'pulse-glow 2s ease-in-out infinite',
                        'slide-up': 'slide-up 0.5s ease-out',
                        'slide-in': 'slide-in 0.3s ease-out',
                        'fade-in': 'fade-in 0.5s ease-out',
                        'bounce-in': 'bounce-in 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)',
                        'shimmer': 'shimmer 2s infinite linear',
                        'typing': 'typing 3s steps(40) 1s forwards',
                        'breathing': 'breathing 4s ease-in-out infinite',
                        'scan-line': 'scan-line 8s linear infinite',
                        'rainbow': 'rainbow 3s linear infinite',
                        'gradient-shift': 'gradient-shift 8s ease infinite',
                        'pulse-ring': 'pulse-ring 1.5s cubic-bezier(0.215, 0.61, 0.355, 1) infinite',
                    },
                    keyframes: {
                        'glitch': {
                            '0%, 100%': { transform: 'translate(0)' },
                            '20%': { transform: 'translate(-2px, 2px)' },
                            '40%': { transform: 'translate(2px, -2px)' },
                            '60%': { transform: 'translate(-1px, -1px)' },
                            '80%': { transform: 'translate(1px, 1px)' },
                        },
                        'float': {
                            '0%, 100%': { transform: 'translateY(0px)' },
                            '50%': { transform: 'translateY(-20px)' },
                        },
                        'pulse-glow': {
                            '0%, 100%': { boxShadow: '0 0 20px rgba(145, 71, 255, 0.3)' },
                            '50%': { boxShadow: '0 0 40px rgba(145, 71, 255, 0.6)' },
                        },
                        'slide-up': {
                            '0%': { transform: 'translateY(30px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        },
                        'slide-in': {
                            '0%': { transform: 'translateX(-20px)', opacity: '0' },
                            '100%': { transform: 'translateX(0)', opacity: '1' },
                        },
                        'fade-in': {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        'bounce-in': {
                            '0%': { transform: 'scale(0)', opacity: '0' },
                            '50%': { transform: 'scale(1.1)' },
                            '100%': { transform: 'scale(1)', opacity: '1' },
                        },
                        'shimmer': {
                            '0%': { backgroundPosition: '-200% 0' },
                            '100%': { backgroundPosition: '200% 0' },
                        },
                        'breathing': {
                            '0%, 100%': { transform: 'scale(1)' },
                            '50%': { transform: 'scale(1.05)' },
                        },
                        'scan-line': {
                            '0%': { transform: 'translateY(-100%)' },
                            '100%': { transform: 'translateY(100vh)' },
                        },
                        'rainbow': {
                            '0%': { filter: 'hue-rotate(0deg)' },
                            '100%': { filter: 'hue-rotate(360deg)' },
                        },
                        'gradient-shift': {
                            '0%': { backgroundPosition: '0% 50%' },
                            '50%': { backgroundPosition: '100% 50%' },
                            '100%': { backgroundPosition: '0% 50%' },
                        },
                        'pulse-ring': {
                            '0%': { transform: 'scale(0.95)', boxShadow: '0 0 0 0 rgba(145, 71, 255, 0.7)' },
                            '70%': { transform: 'scale(1)', boxShadow: '0 0 0 15px rgba(145, 71, 255, 0)' },
                            '100%': { transform: 'scale(0.95)', boxShadow: '0 0 0 0 rgba(145, 71, 255, 0)' },
                        },
                    }
                }
            }
        }
    </script>

    <style>
        /* ====== GLOBAL RESET & TWITCH THEME ====== */
        * { font-family: 'Inter', system-ui, sans-serif; margin: 0; padding: 0; box-sizing: border-box; }

        html {
            scroll-behavior: smooth;
        }

        body {
            background: #0E0E10;
            color: #EFEFF1;
            overflow-x: hidden;
            cursor: default;
        }

        /* ====== CUSTOM SCROLLBAR ====== */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0E0E10;
        }
        ::-webkit-scrollbar-thumb {
            background: #3A3A45;
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #9147FF;
        }

        /* ====== TWITCH INSPIRED LAYOUT ====== */
        .tw-sidebar {
            background: #1F1F23;
            width: 240px;
            border-right: 1px solid #3A3A45;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 100;
            overflow-y: auto;
            overflow-x: hidden;
            transition: transform 0.3s ease;
        }

        .tw-sidebar::-webkit-scrollbar { width: 3px; }

        .tw-sidebar-header {
            padding: 16px;
            border-bottom: 1px solid #3A3A45;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tw-logo {
            width: 30px;
            height: 10px;
            background: linear-gradient(135deg, #9147FF, #772CE8);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 900;
            color: white;
            text-shadow: 0 0 20px rgba(145, 71, 255, 0.5);
            animation: pulse-glow 2s ease-in-out infinite;
        }

        .tw-sidebar-section {
            padding: 16px 12px;
        }

        .tw-sidebar-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #ADADB8;
            padding: 0 8px;
            margin-bottom: 8px;
        }

        .tw-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 6px;
            color: #ADADB8;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            position: relative;
            overflow: hidden;
        }

        .tw-nav-item::before {
            content: '';
            position: absolute;
            left: -1px;
            top: 0;
            width: 3px;
            height: 100%;
            background: #9147FF;
            border-radius: 0 3px 3px 0;
            transform: scaleY(0);
            transition: transform 0.2s ease;
        }

        .tw-nav-item:hover {
            background: #2D2D35;
            color: #EFEFF1;
        }

        .tw-nav-item:hover::before {
            transform: scaleY(1);
        }

        .tw-nav-item.active {
            background: #2D2D35;
            color: #EFEFF1;
        }

        .tw-nav-item.active::before {
            transform: scaleY(1);
        }

        .tw-nav-item i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .tw-nav-item .nav-badge {
            margin-left: auto;
            background: #E9197B;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            min-width: 18px;
            text-align: center;
        }

        .tw-nav-item .nav-badge.purple {
            background: #9147FF;
        }

        .tw-nav-item .nav-badge.green {
            background: #00D95A;
        }

        /* ====== MAIN CONTENT ====== */
        .tw-main {
            margin-left: 240px;
            min-height: 100vh;
            background: #0E0E10;
            position: relative;
        }

        /* ====== TOP NAV BAR (TWITCH STYLE) ====== */
        .tw-top-bar {
            background: #18181B;
            height: 60px;
            border-bottom: 1px solid #3A3A45;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(12px);
        }

        .tw-top-bar-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .tw-search {
            background: #2D2D35;
            border: 1px solid #3A3A45;
            border-radius: 6px;
            padding: 8px 12px 8px 36px;
            width: 300px;
            color: #EFEFF1;
            font-size: 13px;
            transition: all 0.2s ease;
        }

        .tw-search::placeholder {
            color: #5A5A65;
        }

        .tw-search:focus {
            outline: none;
            border-color: #9147FF;
            background: #1F1F23;
            box-shadow: 0 0 0 3px rgba(145, 71, 255, 0.15);
        }

        .tw-search-container {
            position: relative;
        }

        .tw-search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #5A5A65;
            font-size: 13px;
        }

        .tw-top-bar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tw-icon-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ADADB8;
            transition: all 0.2s ease;
            background: transparent;
            border: none;
            cursor: pointer;
            position: relative;
            font-size: 18px;
        }

        .tw-icon-btn:hover {
            background: #2D2D35;
            color: #EFEFF1;
        }

        .tw-icon-btn .dot {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 8px;
            height: 8px;
            background: #E9197B;
            border-radius: 50%;
            border: 2px solid #18181B;
        }

        .tw-user-menu {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 4px 8px 4px 4px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .tw-user-menu:hover {
            background: #2D2D35;
        }

        .tw-avatar {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            background: linear-gradient(135deg, #9147FF, #772CE8);
            color: white;
            flex-shrink: 0;
        }

        .tw-avatar.online {
            box-shadow: 0 0 0 2px #00D95A;
        }

        .tw-avatar-img.online {
            box-shadow: 0 0 0 2px #00D95A;
        }

        .tw-user-info {
            display: none;
        }

        @media (min-width: 768px) {
            .tw-user-info {
                display: block;
            }
            .tw-user-info .name {
                font-size: 13px;
                font-weight: 600;
                color: #EFEFF1;
            }
            .tw-user-info .xp {
                font-size: 11px;
                color: #ADADB8;
            }
        }

        /* ====== CONTENT AREA ====== */
        .tw-content {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .tw-content-full {
            padding: 24px;
        }

        /* ====== TWITCH CARDS ====== */
        .tw-card {
            background: #18181B;
            border: 1px solid #2D2D35;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .tw-card:hover {
            border-color: #3A3A45;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }

        .tw-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #2D2D35;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .tw-card-body {
            padding: 20px;
        }

        /* ====== LESSON CONTENT (rendered markdown) ====== */
        .lesson-content h1 { font-size: 1.5rem; font-weight: 800; color: #EFEFF1; margin: 1.5rem 0 0.75rem; }
        .lesson-content h2 { font-size: 1.25rem; font-weight: 700; color: #EFEFF1; margin: 1.25rem 0 0.5rem; }
        .lesson-content h3 { font-size: 1.1rem; font-weight: 600; color: #EFEFF1; margin: 1rem 0 0.5rem; }
        .lesson-content p { margin: 0.75rem 0; line-height: 1.7; color: #ADADB8; }
        .lesson-content strong { color: #EFEFF1; }
        .lesson-content code { background: #0E0E10; padding: 2px 6px; border-radius: 4px; font-size: 0.85em; color: #A970FF; border: 1px solid #2D2D35; }
        .lesson-content pre.code-block { background: #0E0E10; border: 1px solid #2D2D35; border-radius: 8px; padding: 16px; overflow-x: auto; margin: 1rem 0; }
        .lesson-content pre.code-block code { background: none; border: none; padding: 0; color: #ADADB8; font-size: 0.85rem; }
        .lesson-content ul { margin: 0.75rem 0; padding-left: 1.5rem; }
        .lesson-content li { color: #ADADB8; margin: 0.25rem 0; list-style: disc; }
        .lesson-content blockquote { border-left: 3px solid #9147FF; padding: 0.5rem 1rem; margin: 1rem 0; background: rgba(145,71,255,0.05); border-radius: 0 8px 8px 0; color: #ADADB8; }
        .lesson-content hr { border: none; border-top: 1px solid #2D2D35; margin: 1.5rem 0; }
        .lesson-content a { color: #9147FF; text-decoration: underline; }
        .lesson-content a:hover { color: #A970FF; }

        /* ====== BUTTONS ====== */
        .tw-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .tw-btn-primary {
            background: #9147FF;
            color: white;
        }

        .tw-btn-primary:hover {
            background: #772CE8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(145, 71, 255, 0.4);
        }

        .tw-btn-secondary {
            background: #2D2D35;
            color: #EFEFF1;
        }

        .tw-btn-secondary:hover {
            background: #3A3A45;
        }

        .tw-btn-ghost {
            background: transparent;
            color: #ADADB8;
        }

        .tw-btn-ghost:hover {
            background: #2D2D35;
            color: #EFEFF1;
        }

        .tw-btn-danger {
            background: #E9197B;
            color: white;
        }

        .tw-btn-danger:hover {
            background: #C8156A;
        }

        .tw-btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .tw-btn-lg {
            padding: 12px 24px;
            font-size: 16px;
        }

        .tw-btn-block {
            display: flex;
            width: 100%;
            justify-content: center;
        }

        /* ====== GLITCH TEXT EFFECT ====== */
        .glitch-wrapper {
            position: relative;
            display: inline-block;
        }

        .glitch-text {
            position: relative;
            animation: glitch 3s infinite;
        }

        .glitch-text::before,
        .glitch-text::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .glitch-text::before {
            animation: glitch-top 3s infinite;
            clip-path: polygon(0 0, 100% 0, 100% 33%, 0 33%);
            color: #E9197B;
        }

        .glitch-text::after {
            animation: glitch-bottom 3s infinite;
            clip-path: polygon(0 67%, 100% 67%, 100% 100%, 0 100%);
            color: #00D95A;
        }

        @keyframes glitch-top {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(-3px, -1px); }
            40% { transform: translate(1px, 2px); }
            60% { transform: translate(-2px, 0); }
            80% { transform: translate(2px, 1px); }
        }

        @keyframes glitch-bottom {
            0%, 100% { transform: translate(0); }
            20% { transform: translate(2px, 1px); }
            40% { transform: translate(-1px, -1px); }
            60% { transform: translate(3px, 0); }
            80% { transform: translate(-2px, -2px); }
        }

        /* ====== ANIMATED GRADIENT TEXT ====== */
        .gradient-text {
            background: linear-gradient(-45deg, #9147FF, #E9197B, #FF6B35, #9147FF);
            background-size: 300% 300%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 8s ease infinite;
        }

        /* ====== SCAN LINE OVERLAY (REMOVED) ====== */

        /* ====== LIVE INDICATOR ====== */
        .live-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #E9197B;
            border-radius: 50%;
            animation: pulse-ring 1.5s infinite;
        }

        /* ====== STATS / XP DISPLAY ====== */
        .xp-bar-container {
            background: #2D2D35;
            height: 6px;
            border-radius: 3px;
            overflow: hidden;
        }

        .xp-bar {
            background: linear-gradient(90deg, #9147FF, #A970FF);
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .xp-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 2s infinite;
            background-size: 200% 100%;
        }

        /* ====== LANGUAGE PILLS ====== */
        .lang-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ====== MOBILE ====== */
        @media (max-width: 768px) {
            .tw-sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .tw-sidebar.mobile-open {
                transform: translateX(0);
            }

            .tw-main {
                margin-left: 0;
            }

            .tw-search {
                width: 180px;
            }

            .tw-content {
                padding: 16px;
            }

            .tw-top-bar {
                padding: 0 16px;
            }
        }

        @media (max-width: 480px) {
            .tw-search {
                width: 120px;
            }
            .tw-user-info {
                display: none;
            }
        }

        /* ====== MOBILE HAMBURGER ====== */
        .tw-mobile-toggle {
            display: none;
            background: transparent;
            border: none;
            color: #EFEFF1;
            font-size: 20px;
            cursor: pointer;
            padding: 4px;
        }

        @media (max-width: 768px) {
            .tw-mobile-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        /* ====== TOOLTIP ====== */
        .tw-tooltip {
            position: relative;
        }

        .tw-tooltip::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: #18181B;
            color: #EFEFF1;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
            border: 1px solid #3A3A45;
        }

        .tw-tooltip:hover::after {
            opacity: 1;
        }

        /* ====== TOAST NOTIFICATIONS ====== */
        .toast-container {
            position: fixed;
            top: 72px;
            right: 24px;
            z-index: 9998;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .toast {
            background: #18181B;
            border: 1px solid #3A3A45;
            border-radius: 8px;
            padding: 12px 16px;
            color: #EFEFF1;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
            animation: slide-up 0.3s ease-out;
            min-width: 280px;
            max-width: 400px;
        }

        .toast.success { border-left: 3px solid #00D95A; }
        .toast.error { border-left: 3px solid #E9197B; }
        .toast.info { border-left: 3px solid #9147FF; }

        /* ====== LOADING SPINNER ====== */
        .tw-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #2D2D35;
            border-top-color: #9147FF;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ====== STREAMER CARD ====== */
        .stream-card {
            position: relative;
            overflow: hidden;
        }

        .stream-card .stream-thumb {
            aspect-ratio: 16/9;
            background: #2D2D35;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }

        .stream-card .live-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: #E9197B;
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stream-card .viewer-count {
            position: absolute;
            bottom: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* ====== CONFETTI ====== */
        .confetti-piece {
            position: fixed;
            width: 10px;
            height: 10px;
            z-index: 10000;
            pointer-events: none;
            animation: confetti-fall linear forwards;
        }

        @keyframes confetti-fall {
            0% {
                transform: translateY(-10vh) rotate(0deg) scale(1);
                opacity: 1;
            }
            100% {
                transform: translateY(110vh) rotate(720deg) scale(0.5);
                opacity: 0;
            }
        }
    </style>
</head>
<body>

<!-- Scan Line Overlay -->
<div class="scan-line-overlay"></div>



<?php if (!($hide_chrome ?? false)): ?>
<!-- Toast Notification Container -->
<div class="toast-container" id="toast-container"></div>

<!-- Mobile Toggle -->
<button class="tw-mobile-toggle" id="mobile-toggle" onclick="toggleMobileSidebar()">
    <i class="fas fa-bars"></i>
</button>
<?php endif; ?>

<!-- ====== LEFT SIDEBAR (TWITCH STYLE) ====== -->
<?php if (!($hide_chrome ?? false)): ?>
<div class="tw-sidebar" id="main-sidebar">
    <div class="tw-sidebar-header">
        <img src="assets/logo.png" alt="Rustnite" style="height: 20px; display: block;">
    </div>

    <div class="tw-sidebar-section">
        <div class="tw-sidebar-section-title">Browse</div>

        <a href="index.php?page=dashboard" class="tw-nav-item <?= ($_GET[
            "page"
        ] ??
            "dashboard") ===
        "dashboard"
            ? "active"
            : "" ?>">
            <i class="fas fa-compass"></i>
            <span>Dashboard</span>
        </a>

        <a href="index.php?page=lessons" class="tw-nav-item <?= ($_GET[
            "page"
        ] ??
            "") ===
        "lessons"
            ? "active"
            : "" ?>">
            <i class="fas fa-graduation-cap"></i>
            <span>Learn</span>
            <?php if (isset($_SESSION["user_id"])):
                $unread = get_unread_notification_count($_SESSION["user_id"]);
                if ($unread > 0): ?>
                <span class="nav-badge"><?= $unread ?></span>
            <?php endif;
            endif; ?>
        </a>

        <a href="index.php?page=battle-royale" class="tw-nav-item <?= ($_GET[
            "page"
        ] ??
            "") ===
        "battle-royale"
            ? "active"
            : "" ?>">
            <i class="fas fa-crosshairs"></i>
            <span>Battle Royale</span>
        </a>

        <a href="index.php?page=mini-games" class="tw-nav-item <?= ($_GET[
            "page"
        ] ??
            "") ===
        "mini-games"
            ? "active"
            : "" ?>">
            <i class="fas fa-gamepad"></i>
            <span>Mini-Games</span>
        </a>

        <a href="index.php?page=ai-tutor" class="tw-nav-item <?= ($_GET[
            "page"
        ] ??
            "") ===
        "ai-tutor"
            ? "active"
            : "" ?>">
            <i class="fas fa-robot"></i>
            <span>AI Tutor</span>
        </a>

        <a href="index.php?page=projects" class="tw-nav-item <?= ($_GET[
            "page"
        ] ??
            "") ===
        "projects"
            ? "active"
            : "" ?>">
            <i class="fas fa-code-branch"></i>
            <span>Projects</span>
        </a>

        <a href="index.php?page=leaderboard" class="tw-nav-item <?= ($_GET[
            "page"
        ] ??
            "") ===
        "leaderboard"
            ? "active"
            : "" ?>">
            <i class="fas fa-trophy"></i>
            <span>Leaderboard</span>
        </a>
    </div>

    <div class="tw-sidebar-section">
        <div class="tw-sidebar-section-title">Social</div>

        <a href="index.php?page=feed" class="tw-nav-item <?= ($_GET["page"] ??
            "") ===
        "feed"
            ? "active"
            : "" ?>">
            <i class="fas fa-rss"></i>
            <span>Feed</span>
        </a>

        <a href="index.php?page=profile" class="tw-nav-item <?= ($_GET[
            "page"
        ] ??
            "") ===
        "profile"
            ? "active"
            : "" ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>

        <?php if (isset($_SESSION["user_id"])): ?>
        <a href="index.php?page=logout" class="tw-nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
        <?php endif; ?>
    </div>

    <div class="tw-sidebar-section" style="border-top: 1px solid #2D2D35; margin-top: auto;">
        <div class="tw-sidebar-section-title">Donate ❤️</div>
        <a href="index.php?page=donate" class="tw-nav-item">
            <i class="fas fa-heart" style="color: #E9197B;"></i>
            <span>Support Rustnite</span>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ====== MAIN CONTENT ====== -->
<?php if (!($hide_chrome ?? false)): ?>
<div class="tw-main">
    <!-- Top Bar -->
    <div class="tw-top-bar">
        <div class="tw-top-bar-left">
            <div class="tw-search-container">
                <i class="fas fa-search tw-search-icon"></i>
                <input type="text" class="tw-search" placeholder="Search lessons, languages, users..." id="global-search">
            </div>
        </div>

        <div class="tw-top-bar-right">
            <?php if (isset($_SESSION["user_id"])):

                $current_user = get_user_by_id($_SESSION["user_id"]);
                $unread_notifications = get_unread_notification_count(
                    $_SESSION["user_id"],
                );
                $languages = get_languages();
                ?>
                <div class="tw-tooltip" data-tooltip="Notifications">
                    <button class="tw-icon-btn" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="dot"></span>
                        <?php endif; ?>
                    </button>
                </div>

                <div class="tw-tooltip" data-tooltip="Daily Challenge">
                    <button class="tw-icon-btn" onclick="window.location='index.php?page=daily-challenge'">
                        <i class="fas fa-calendar-day"></i>
                    </button>
                </div>

                <div class="tw-user-menu" onclick="window.location='index.php?page=profile'">
                    >
                                        <?= get_avatar_html(
                                            $current_user,
                                            32,
                                            !empty($current_user["is_online"])
                                                ? "online"
                                                : "",
                                        ) ?>
                                        <div class="tw-user-info">
                        <div class="name"><?= htmlspecialchars(
                            $current_user["username"],
                        ) ?></div>
                        <div class="xp">Level <?= $current_user[
                            "level"
                        ] ?> · <?= number_format(
     $current_user["xp"],
 ) ?> XP</div>
                    </div>
                </div>
            <?php
            else:
                 ?>
                <a href="index.php?page=login" class="tw-btn tw-btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </a>
                <a href="index.php?page=register" class="tw-btn tw-btn-secondary">
                    <i class="fas fa-user-plus"></i>
                    Register
                </a>
            <?php
            endif; ?>
        </div>
    </div>

    <!-- Content -->
    <div class="tw-content">
<?php else: ?>
<div style="min-height:100vh;">
<?php endif; ?>
        <!-- XP Bar (for logged in users) -->
        <?php if (isset($_SESSION["user_id"]) && isset($current_user)):

            $xp_for_level = get_xp_for_next_level($current_user["xp"]);
            $current_level_xp = ($current_user["level"] - 1) * XP_PER_LEVEL;
            $progress =
                (($current_user["xp"] - $current_level_xp) /
                    ($xp_for_level - $current_level_xp)) *
                100;
            $progress = max(0, min(100, $progress));
            ?>
        <div style="margin-bottom: 20px; animation: slide-up 0.5s ease-out;">
            <div class="flex items-center justify-between mb-1">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-twitch-muted font-medium">Level <?= $current_user[
                        "level"
                    ] ?></span>
                    <span class="text-xs text-twitch-purple font-bold"><i class="fas fa-bolt"></i></span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs text-twitch-muted">
                        <i class="fas fa-fire mr-1" style="color: #FF6B35;"></i>
                        <?= $current_user["current_streak"] ?? 0 ?> day streak
                    </span>
                    <span class="text-xs text-twitch-muted">
                        <?= number_format(
                            $current_user["xp"],
                        ) ?> / <?= number_format($xp_for_level) ?> XP
                    </span>
                </div>
            </div>
            <div class="xp-bar-container">
                <div class="xp-bar" style="width: <?= round(
                    $progress,
                ) ?>%;"></div>
            </div>
        </div>
        <?php
        endif; ?>
