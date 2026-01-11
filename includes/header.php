<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - Battle-Royale Rust Learning</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { 
            font-family: 'Poppins', sans-serif; 
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #0D0D0D;
            color: #FFFFFF;
            overflow-x: hidden;
        }
        
        /* Exact sidebar styling from image */
        .sidebar {
            background: #1A1A1A;
            width: 80px;
            border-right: 1px solid #2A2A2A;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            z-index: 100;
        }
        
        .sidebar-icon {
            width: 48px;
            height: 48px;
            margin: 8px auto;
            background: transparent;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666666;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .sidebar-icon:hover, .sidebar-icon.active {
            background: #FF6B35;
            color: #FFFFFF;
        }
        
        .sidebar-icon a {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: inherit;
            text-decoration: none;
        }
        
        /* Main content area */
        .main-container {
            margin-left: 80px;
            min-height: 100vh;
            background: #0D0D0D;
        }
        
        /* Top bar exactly like image */
        .top-bar {
            background: #1A1A1A;
            height: 80px;
            border-bottom: 1px solid #2A2A2A;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
        }
        
        .greeting-text {
            color: #CCCCCC;
            font-size: 16px;
            font-weight: 400;
        }
        
        .username-text {
            color: #FFFFFF;
            font-size: 24px;
            font-weight: 600;
            margin-top: 4px;
        }
        
        /* Search bar exactly like image */
        .search-container {
            position: relative;
        }
        
        .search-input {
            background: #2A2A2A;
            border: 1px solid #3A3A3A;
            border-radius: 8px;
            padding: 10px 16px 10px 40px;
            width: 280px;
            color: #FFFFFF;
            font-size: 14px;
            font-weight: 400;
        }
        
        .search-input::placeholder {
            color: #666666;
            font-weight: 400;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #4A4A4A;
            background: #333333;
        }
        
        .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #666666;
            font-size: 14px;
        }
        
        /* Content area layout */
        .content-area {
            display: flex;
            height: calc(100vh - 80px);
        }
        
        .left-content {
            flex: 1;
            padding: 32px;
            overflow-y: auto;
        }
        
        /* Full width for lesson page */
        .lesson-page .left-content {
            max-width: none;
            padding: 32px 64px;
        }
        
        .right-content {
            width: 420px;
            background: #1A1A1A;
            position: relative;
            overflow: hidden;
            padding: 40px 32px;
            border-left: 1px solid #2A2A2A;
        }
        
        /* Buttons exactly like image */
        .btn-primary {
            background: #FF6B35;
            color: #FFFFFF;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            background: #E55A2B;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: transparent;
            color: #CCCCCC;
            border: 1px solid #3A3A3A;
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            border-color: #FF6B35;
            color: #FF6B35;
        }
        
        /* Cards exactly like image */
        .content-card {
            background: #1A1A1A;
            border: 1px solid #2A2A2A;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .content-card:hover {
            border-color: #3A3A3A;
        }
        
        /* Stats styling */
        .stat-item {
            background: #1A1A1A;
            border: 1px solid #2A2A2A;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #FF6B35;
            margin-bottom: 8px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #CCCCCC;
            font-weight: 500;
        }
        
        /* Character section like image */
        .character-hero {
            display: none;
        }
        
        /* Progress bars */
        .progress-container {
            background: #2A2A2A;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            background: linear-gradient(90deg, #FF6B35, #FF8A50);
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        /* GitHub buttons */
        .github-buttons {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .github-btn {
            background: #2A2A2A;
            border: 1px solid #3A3A3A;
            border-radius: 8px;
            padding: 8px 12px;
            color: #CCCCCC;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        
        .github-btn:hover {
            background: #333333;
            border-color: #4A4A4A;
            color: #FFFFFF;
            transform: translateY(-1px);
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 200;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-container {
                margin-left: 0;
            }
            
            .top-bar {
                padding: 0 20px;
            }
            
            .search-input {
                width: 200px;
            }
            
            .content-area {
                flex-direction: column;
            }
            
            .right-content {
                width: 100%;
                order: -1;
                padding: 20px;
            }
            
            .left-content {
                padding: 20px;
            }
            
            .github-buttons {
                position: fixed;
                top: 20px;
                right: 10px;
                flex-direction: row;
                gap: 4px;
            }
            
            .github-btn {
                padding: 6px 8px;
                font-size: 10px;
            }
            
            .github-btn span {
                display: none;
            }
            
            .title-large {
                font-size: 24px;
            }
            
            .title-medium {
                font-size: 20px;
            }
            
            .grid {
                grid-template-columns: 1fr !important;
            }
            
            .lg\\:grid-cols-2 {
                grid-template-columns: 1fr !important;
            }
            
            .lg\\:grid-cols-3 {
                grid-template-columns: 1fr !important;
            }
            
            .md\\:grid-cols-2 {
                grid-template-columns: 1fr !important;
            }
            
            .md\\:grid-cols-3 {
                grid-template-columns: 1fr !important;
            }
            
            .md\\:grid-cols-4 {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        @media (max-width: 480px) {
            .top-bar {
                padding: 0 15px;
            }
            
            .search-input {
                width: 150px;
                font-size: 12px;
            }
            
            .username-text {
                font-size: 18px;
            }
            
            .greeting-text {
                font-size: 14px;
            }
            
            .content-card {
                padding: 16px;
            }
            
            .stat-item {
                padding: 12px;
            }
            
            .stat-value {
                font-size: 20px;
            }
            
            .btn-primary, .btn-secondary {
                padding: 8px 16px;
                font-size: 12px;
            }
        }
        
        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 300;
            background: #2A2A2A;
            border: 1px solid #3A3A3A;
            border-radius: 8px;
            padding: 8px;
            color: #FFFFFF;
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- GitHub Buttons -->
    <div class="github-buttons">
        <a href="https://github.com/nia-cloud-official/rustnite" target="_blank" class="github-btn">
            <i class="fab fa-github"></i>
            <span>Fork on GitHub</span>
        </a>
        <a href="https://github.com/nia-cloud-official/rustnite" target="_blank" class="github-btn">
            <i class="fas fa-star"></i>
            <span>Star the Repo</span>
        </a>
    </div>

    <!-- Left Sidebar -->
    <div class="sidebar" id="mobile-sidebar">
        <div style="padding-top: 20px;">
            <!-- Logo -->
            <div class="sidebar-icon" style="margin-bottom: 32px;">
                <i class="fas fa-code text-lg"></i>
            </div>
            
            <!-- Navigation Icons -->
            <div class="sidebar-icon <?= ($_GET['page'] ?? 'dashboard') == 'dashboard' ? 'active' : '' ?>">
                <a href="index.php?page=dashboard">
                    <i class="fas fa-home text-lg"></i>
                </a>
            </div>
            
            <div class="sidebar-icon <?= ($_GET['page'] ?? '') == 'lessons' ? 'active' : '' ?>">
                <a href="index.php?page=lessons">
                    <i class="fas fa-book text-lg"></i>
                </a>
            </div>
            
            <div class="sidebar-icon <?= ($_GET['page'] ?? '') == 'projects' ? 'active' : '' ?>">
                <a href="index.php?page=projects">
                    <i class="fas fa-code-branch text-lg"></i>
                </a>
            </div>
            
            <div class="sidebar-icon <?= ($_GET['page'] ?? '') == 'leaderboard' ? 'active' : '' ?>">
                <a href="index.php?page=leaderboard">
                    <i class="fas fa-trophy text-lg"></i>
                </a>
            </div>
            
            <div class="sidebar-icon <?= ($_GET['page'] ?? '') == 'profile' ? 'active' : '' ?>">
                <a href="index.php?page=profile">
                    <i class="fas fa-user text-lg"></i>
                </a>
            </div>
            
            <!-- Bottom icons -->
            <div style="position: absolute; bottom: 80px; width: 100%;">
                <div class="sidebar-icon">
                    <a href="index.php?page=donate">
                        <i class="fas fa-heart text-lg"></i>
                    </a>
                </div>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="sidebar-icon">
                        <a href="index.php?page=logout">
                            <i class="fas fa-sign-out-alt text-lg"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <?php if (isset($_SESSION['user_id'])): 
                    $current_user = get_user_by_id($_SESSION['user_id']);
                ?>
                    <div class="greeting-text">
                        Good <?= date('H') < 12 ? 'Morning' : (date('H') < 18 ? 'Afternoon' : 'Evening') ?>
                    </div>
                    <div class="username-text"><?= htmlspecialchars($current_user['username']) ?></div>
                <?php else: ?>
                    <div class="greeting-text">Welcome to</div>
                    <div class="username-text"><?= APP_NAME ?></div>
                <?php endif; ?>
            </div>
            
            <div class="flex items-center space-x-6">
                <div class="search-container">
                    <input type="text" placeholder="Tap here to search" class="search-input">
                    <i class="fas fa-search search-icon"></i>
                </div>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="index.php?page=login" class="btn-primary">
                        Login
                    </a>
                <?php else: ?>
                    <div class="flex items-center space-x-4">
                        <i class="fas fa-bell text-xl text-gray-400 hover:text-orange-400 cursor-pointer"></i>
                        <i class="fas fa-envelope text-xl text-gray-400 hover:text-orange-400 cursor-pointer"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area <?= ($_GET['page'] ?? 'dashboard') === 'lesson' ? 'lesson-page' : '' ?>">
            <div class="left-content">