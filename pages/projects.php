<?php
$page_title = "Projects";
$user_progress = get_user_progress($_SESSION["user_id"]);
$completed_lessons = array_filter(
    $user_progress,
    fn($lesson) => $lesson["completed"],
);
$completed_count = count($completed_lessons);

$projects = [
    [
        "id" => 1,
        "title" => "CLI Calculator",
        "description" =>
            "Build a command-line calculator that performs basic arithmetic operations with proper error handling.",
        "difficulty" => "beginner",
        "estimated_time" => "2-3 hours",
        "xp_reward" => 500,
        "prerequisites" => 3,
        "skills" => ["Functions", "Variables", "User Input", "Error Handling"],
        "icon" => "fas fa-calculator",
        "color" => "#00D95A",
    ],
    [
        "id" => 2,
        "title" => "Todo List Manager",
        "description" =>
            "Create a todo list application with add, delete, and complete functionality using file I/O.",
        "difficulty" => "beginner",
        "estimated_time" => "3-4 hours",
        "xp_reward" => 700,
        "prerequisites" => 5,
        "skills" => ["Arrays", "File I/O", "String Manipulation", "Structs"],
        "icon" => "fas fa-list-check",
        "color" => "#9147FF",
    ],
    [
        "id" => 3,
        "title" => "Password Generator",
        "description" =>
            "Build a secure password generator with configurable options and strength indicators.",
        "difficulty" => "intermediate",
        "estimated_time" => "2-3 hours",
        "xp_reward" => 800,
        "prerequisites" => 8,
        "skills" => ["Random", "Strings", "User Input", "Functions"],
        "icon" => "fas fa-key",
        "color" => "#A970FF",
    ],
    [
        "id" => 4,
        "title" => "HTTP Web Server",
        "description" =>
            "Build a basic HTTP web server that can serve static files and handle routes.",
        "difficulty" => "intermediate",
        "estimated_time" => "4-6 hours",
        "xp_reward" => 1000,
        "prerequisites" => 10,
        "skills" => ["Networking", "TCP", "HTTP Protocol", "File I/O"],
        "icon" => "fas fa-server",
        "color" => "#FF6B35",
    ],
    [
        "id" => 5,
        "title" => "Chat Application",
        "description" =>
            "Build a real-time chat application using WebSockets or TCP with multiple clients.",
        "difficulty" => "advanced",
        "estimated_time" => "6-8 hours",
        "xp_reward" => 1500,
        "prerequisites" => 14,
        "skills" => [
            "Networking",
            "Threading",
            "Concurrency",
            "Protocol Design",
        ],
        "icon" => "fas fa-comments",
        "color" => "#E9197B",
    ],
    [
        "id" => 6,
        "title" => "Mini Database Engine",
        "description" =>
            "Create a simple key-value database engine with persistence, indexing, and querying.",
        "difficulty" => "advanced",
        "estimated_time" => "8-10 hours",
        "xp_reward" => 2000,
        "prerequisites" => 18,
        "skills" => [
            "Data Structures",
            "File I/O",
            "Indexing",
            "Serialization",
        ],
        "icon" => "fas fa-database",
        "color" => "#FFD700",
    ],
];
?>
<div style="animation: fade-in 0.5s ease-out;">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-code-branch" style="color: #00D95A;"></i>
                Hands-On Projects
            </h1>
            <p class="text-twitch-muted mt-1">Build real-world projects to showcase in your portfolio!</p>
        </div>
        <div class="flex items-center gap-2 text-sm text-twitch-muted">
            <span><i class="fas fa-check-circle" style="color:#00D95A;"></i> <?= $completed_count ?> lessons completed</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($projects as $project):

            $locked = $completed_count < $project["prerequisites"];
            $progress_pct = min(
                100,
                round(($completed_count / $project["prerequisites"]) * 100),
            );
            ?>
            <div class="tw-card <?= $locked
                ? "opacity-75"
                : "" ?>" style="animation: slide-up 0.5s ease-out;">
                <div class="stream-thumb" style="background: linear-gradient(135deg, #1F1F23, #2D2D35); padding:24px; display:flex; align-items:center; justify-content:center; flex-direction:column; position:relative;">
                    <i class="<?= $project[
                        "icon"
                    ] ?>" style="font-size:40px; color:<?= $project[
    "color"
] ?>; margin-bottom:8px;"></i>
                    <span class="font-bold text-sm"><?= $project[
                        "title"
                    ] ?></span>

                    <?php if ($locked): ?>
                        <div style="position:absolute; top:12px; right:12px;">
                            <i class="fas fa-lock" style="color:#ADADB8;"></i>
                        </div>
                    <?php else: ?>
                        <div style="position:absolute; top:12px; right:12px;">
                            <i class="fas fa-unlock" style="color:#00D95A;"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="tw-card-body">
                    <?= get_difficulty_badge($project["difficulty"]) ?>

                    <p class="text-xs text-twitch-muted mt-3 mb-4"><?= htmlspecialchars(
                        $project["description"],
                    ) ?></p>

                    <div class="flex flex-wrap gap-1 mb-4">
                        <?php foreach ($project["skills"] as $skill): ?>
                            <span class="text-xs px-2 py-1 rounded-full" style="background:rgba(145,71,255,0.1); color:#A970FF;"><?= $skill ?></span>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs text-twitch-muted"><i class="far fa-clock mr-1"></i> <?= $project[
                            "estimated_time"
                        ] ?></span>
                        <span class="text-sm font-bold" style="color:#A970FF;"><i class="fas fa-star mr-1"></i> +<?= $project[
                            "xp_reward"
                        ] ?> XP</span>
                    </div>

                    <?php if ($locked): ?>
                        <div>
                            <div class="flex items-center justify-between text-xs text-twitch-muted mb-1">
                                <span>Prerequisite: <?= $project[
                                    "prerequisites"
                                ] ?> lessons</span>
                                <span><?= $completed_count ?>/<?= $project[
    "prerequisites"
] ?></span>
                            </div>
                            <div class="xp-bar-container">
                                <div class="xp-bar" style="width:<?= $progress_pct ?>%;"></div>
                            </div>
                        </div>
                        <button disabled class="tw-btn tw-btn-ghost tw-btn-sm tw-btn-block mt-3" style="cursor:not-allowed;">
                            <i class="fas fa-lock"></i>
                            Locked
                        </button>
                    <?php else: ?>
                        <button class="tw-btn tw-btn-primary tw-btn-sm tw-btn-block mt-3" onclick="showToast('Project starting soon! 🚀', 'info')">
                            <i class="fas fa-play"></i>
                            Start Project
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php
        endforeach; ?>
    </div>
</div>
