<?php
$page_title = "Feed";
$user = get_user_by_id($_SESSION["user_id"]);
$languages = get_languages();

// Handle post creation
$post_error = "";
$post_success = "";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["create_post"])) {
    $title = sanitize($_POST["post_title"] ?? "");
    $content = sanitize($_POST["post_content"] ?? "");
    $post_type = $_POST["post_type"] ?? "post";
    $code_snippet = $_POST["code_snippet"] ?? "";
    $language_slug = $_POST["language_slug"] ?? "";
    $tags = sanitize($_POST["tags"] ?? "");

    if (empty($content)) {
        $post_error = "Content is required";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO feed_posts (user_id, type, title, content, code_snippet, language_slug, tags, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $_SESSION["user_id"],
                $post_type,
                $title,
                $content,
                $code_snippet ?: null,
                $language_slug ?: null,
                $tags ?: null,
            ]);
            $post_success = "Post created successfully!";
        } catch (PDOException $e) {
            $post_error = "Failed to create post: " . $e->getMessage();
        }
    }
}

// Get posts with pagination
$page_num = max(1, (int) ($_GET["p"] ?? 1));
$per_page = 20;
$offset = ($page_num - 1) * $per_page;

$view_post_id = (int) ($_GET["view"] ?? 0);
$filter_type = $_GET["type"] ?? "";

$sql = "SELECT fp.*, u.username, u.avatar_url,
        (SELECT COUNT(*) FROM feed_likes WHERE post_id = fp.id) as likes_count_real,
        (SELECT COUNT(*) FROM feed_comments WHERE post_id = fp.id) as comments_count_real,
        (SELECT id FROM feed_likes WHERE post_id = fp.id AND user_id = ?) as user_liked
        FROM feed_posts fp
        JOIN users u ON fp.user_id = u.id";
$params = [$_SESSION["user_id"]];

if ($view_post_id > 0) {
    $sql .= " WHERE fp.id = ?";
    $params[] = $view_post_id;
} else {
    if (!empty($filter_type)) {
        $sql .= " WHERE fp.type = ?";
        $params[] = $filter_type;
    }
    $sql .= " ORDER BY fp.is_pinned DESC, fp.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
}

$posts = $pdo->prepare($sql);
$posts->execute($params);
$posts = $posts->fetchAll();

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as count FROM feed_posts";
$params_count = [];
if (!empty($filter_type)) {
    $count_sql .= " WHERE type = ?";
    $params_count[] = $filter_type;
}
$total = $pdo->prepare($count_sql);
$total->execute($params_count);
$total_posts = $total->fetch()["count"];
$total_pages = max(1, ceil($total_posts / $per_page));

$post_types = [
    "post" => ["icon" => "fa-comment", "color" => "#9147FF"],
    "question" => ["icon" => "fa-question-circle", "color" => "#00D95A"],
    "blog" => ["icon" => "fa-blog", "color" => "#A970FF"],
    "idea" => ["icon" => "fa-lightbulb", "color" => "#FF6B35"],
    "share" => ["icon" => "fa-share-alt", "color" => "#E9197B"],
];
?>
<div style="animation: fade-in 0.5s ease-out;">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold flex items-center gap-3">
                <i class="fas fa-rss" style="color: #9147FF;"></i>
                Community Feed
            </h1>
            <p class="text-sm text-twitch-muted mt-1">Share ideas, ask questions, and connect with the community</p>
        </div>
        <button onclick="document.getElementById('create-post-form').style.display = document.getElementById('create-post-form').style.display === 'none' ? 'block' : 'none'" class="tw-btn tw-btn-primary">
            <i class="fas fa-plus"></i>
            New Post
        </button>
    </div>

    <!-- Create Post Form -->
    <div id="create-post-form" class="tw-card mb-6" style="display:none; animation: slide-up 0.3s ease-out;">
        <div class="tw-card-body">
            <?php if ($post_error): ?>
                <div style="background:rgba(233,25,123,0.1); border:1px solid rgba(233,25,123,0.2); border-radius:8px; padding:12px; margin-bottom:16px;">
                    <span style="color:#E9197B; font-size:13px;"><?= htmlspecialchars(
                        $post_error,
                        ENT_NOQUOTES,
                        "UTF-8",
                    ) ?></span>
                </div>
            <?php endif; ?>
            <?php if ($post_success): ?>
                <div style="background:rgba(0,217,90,0.1); border:1px solid rgba(0,217,90,0.2); border-radius:8px; padding:12px; margin-bottom:16px;">
                    <span style="color:#00D95A; font-size:13px;"><?= htmlspecialchars(
                        $post_success,
                        ENT_NOQUOTES,
                        "UTF-8",
                    ) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-twitch-muted mb-1">Post Type</label>
                        <select name="post_type" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text text-sm">
                            <option value="post"><i class="fas fa-comment"></i> Post</option>
                            <option value="question">Question</option>
                            <option value="idea">Idea</option>
                            <option value="blog">Blog</option>
                            <option value="share">Share</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-twitch-muted mb-1">Title</label>
                        <input type="text" name="post_title" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text text-sm" placeholder="Post title (optional)">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-twitch-muted mb-1">Content <span class="text-twitch-pink">*</span></label>
                        <textarea name="post_content" required rows="4" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text text-sm" placeholder="What's on your mind?"></textarea>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Code Snippet</label>
                            <textarea name="code_snippet" rows="3" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text text-sm font-mono" placeholder="Paste some code..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-twitch-muted mb-1">Language / Tags</label>
                            <select name="language_slug" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text text-sm mb-2">
                                <option value="">None</option>
                                <?php foreach ($languages as $lang): ?>
                                    <option value="<?= $lang[
                                        "slug"
                                    ] ?>"><?= $lang["name"] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" name="tags" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text text-sm" placeholder="tags: rust, web, api">
                        </div>
                    </div>
                    <button type="submit" name="create_post" class="tw-btn tw-btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Publish Post
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 mb-6 flex-wrap">
        <a href="?page=feed" class="tw-btn tw-btn-sm <?= empty($filter_type)
            ? "tw-btn-primary"
            : "tw-btn-ghost" ?>">
            <i class="fas fa-rss"></i> All
        </a>
        <?php foreach ($post_types as $type => $info): ?>
            <a href="?page=feed&type=<?= $type ?>" class="tw-btn tw-btn-sm <?= $filter_type ===
$type
    ? "tw-btn-primary"
    : "tw-btn-ghost" ?>">
                <i class="fas <?= $info["icon"] ?>"></i> <?= ucfirst($type) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Posts -->
    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post):

            $type_info = $post_types[$post["type"]] ?? $post_types["post"];
            $user_liked = $post["user_liked"] ? true : false;
            $likes = max($post["likes_count"], $post["likes_count_real"]);
            $comments = max(
                $post["comments_count"],
                $post["comments_count_real"],
            );
            $post_content = htmlspecialchars(
                $post["content"],
                ENT_NOQUOTES,
                "UTF-8",
            );
            $post_content = preg_replace(
                "/\b(https?:\/\/\S+)\b/",
                '<a href="$1" target="_blank" style="color:#9147FF;">$1</a>',
                $post_content,
            );
            ?>
            <div class="tw-card mb-4" style="animation: slide-up 0.3s ease-out;">
                <div class="tw-card-body">
                    <?php if ($post["is_pinned"]): ?>
                        <div class="flex items-center gap-1 mb-2">
                            <span class="text-xs font-bold" style="color:#9147FF;"><i class="fas fa-thumbtack"></i> Pinned</span>
                        </div>
                    <?php endif; ?>

                    <div class="flex items-center gap-3 mb-3">
                        <?= get_avatar_html(
                            [
                                "avatar_url" => $post["avatar_url"],
                                "username" => $post["username"],
                            ],
                            32,
                        ) ?>
                        <div style="flex:1;">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm"><?= htmlspecialchars(
                                    $post["username"],
                                    ENT_NOQUOTES,
                                    "UTF-8",
                                ) ?></span>
                                <span class="lang-pill" style="background:<?= $type_info[
                                    "color"
                                ] ?>20; color:<?= $type_info[
    "color"
] ?>; font-size:10px;">
                                    <i class="fas <?= $type_info[
                                        "icon"
                                    ] ?>"></i> <?= ucfirst($post["type"]) ?>
                                </span>
                            </div>
                            <div class="text-xs text-twitch-muted"><?= time_ago(
                                $post["created_at"],
                            ) ?></div>
                        </div>
                    </div>

                    <?php if ($post["title"]): ?>
                        <h3 class="font-bold text-lg mb-2"><?= htmlspecialchars(
                            $post["title"],
                            ENT_NOQUOTES,
                            "UTF-8",
                        ) ?></h3>
                    <?php endif; ?>

                    <div class="text-sm text-twitch-muted mb-3" style="line-height:1.7; white-space:pre-wrap;"><?= $post_content ?></div>

                    <?php if ($post["code_snippet"]): ?>
                        <pre style="background:#0E0E10; border:1px solid #2D2D35; border-radius:8px; padding:12px; overflow-x:auto; margin-bottom:12px;"><code style="font-size:12px; color:#ADADB8;"><?= htmlspecialchars(
                            $post["code_snippet"],
                            ENT_NOQUOTES,
                            "UTF-8",
                        ) ?></code></pre>
                    <?php endif; ?>

                    <?php if ($post["tags"]): ?>
                        <div class="flex items-center gap-2 mb-3 flex-wrap">
                            <?php foreach (
                                explode(",", $post["tags"])
                                as $tag
                            ): ?>
                                <span style="background:rgba(145,71,255,0.1); color:#A970FF; padding:2px 8px; border-radius:4px; font-size:11px;">#<?= trim(
                                    htmlspecialchars(
                                        $tag,
                                        ENT_NOQUOTES,
                                        "UTF-8",
                                    ),
                                ) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="flex items-center gap-4 mt-2 pt-3" style="border-top:1px solid #2D2D35;">
                        <a href="?page=feed&<?= $user_liked
                            ? "unlike"
                            : "like" ?>=<?= $post[
    "id"
] ?>" class="flex items-center gap-1 text-sm" style="text-decoration:none; color:<?= $user_liked
    ? "#E9197B"
    : "#ADADB8" ?>;">
                            <i class="fas fa-heart"></i>
                            <span><?= $likes ?> <?= $likes === 1
     ? "like"
     : "likes" ?></span>
                        </a>
                        <a href="?page=feed&view=<?= $post[
                            "id"
                        ] ?>" class="flex items-center gap-1 text-sm text-twitch-muted" style="text-decoration:none;">
                            <i class="fas fa-comment"></i>
                            <span><?= $comments ?> <?= $comments === 1
     ? "comment"
     : "comments" ?></span>
                        </a>
                        <div style="flex:1;"></div>
                        <span style="font-size:11px; color:#5A5A65;">
                            <i class="fas fa-globe"></i>
                        </span>
                    </div>

                    <!-- Comments Section -->
                    <?php if (
                        $view_post_id === (int) $post["id"] ||
                        $view_post_id > 0
                    ): ?>
                        <?php
                        $stmt = $pdo->prepare(
                            "SELECT fc.*, u.username, u.avatar_url FROM feed_comments fc JOIN users u ON fc.user_id = u.id WHERE fc.post_id = ? ORDER BY fc.created_at ASC",
                        );
                        $stmt->execute([$post["id"]]);
                        $post_comments = $stmt->fetchAll();
                        ?>
                        <div style="margin-top:16px; padding-top:16px; border-top:1px solid #2D2D35;">
                            <h4 class="text-sm font-bold mb-3">Comments (<?= $comments ?>)</h4>
                            <?php if (!empty($post_comments)): ?>
                                <?php foreach ($post_comments as $comment): ?>
                                    <div class="flex items-start gap-3 mb-3">
                                        <?= get_avatar_html($comment, 28) ?>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-xs"><?= htmlspecialchars(
                                                    $comment["username"],
                                                    ENT_NOQUOTES,
                                                    "UTF-8",
                                                ) ?></span>
                                                <span class="text-xs text-twitch-muted"><?= time_ago(
                                                    $comment["created_at"],
                                                ) ?></span>
                                            </div>
                                            <p class="text-sm text-twitch-muted mt-1"><?= htmlspecialchars(
                                                $comment["content"],
                                                ENT_NOQUOTES,
                                                "UTF-8",
                                            ) ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <form method="POST" style="display:flex; gap:8px; margin-top:12px;">
                                <input type="hidden" name="post_id" value="<?= $post[
                                    "id"
                                ] ?>">
                                <input type="text" name="comment_content" required class="flex-1 p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text text-sm" placeholder="Write a comment...">
                                <button type="submit" name="add_comment" class="tw-btn tw-btn-primary tw-btn-sm">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php
        endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1 && $view_post_id === 0): ?>
            <div class="flex items-center justify-center gap-2 mt-6">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=feed&p=<?=
                    $i
                    !empty($filter_type)
                        ? "&type=" . urlencode($filter_type)
                        : ""
                    ?>" class="tw-btn <?= $i === $page_num
    ? "tw-btn-primary"
    : "tw-btn-ghost" ?> tw-btn-sm">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="tw-card tw-card-body" style="text-align:center; padding:80px 20px;">
            <i class="fas fa-rss" style="font-size:64px; color:#2D2D35; margin-bottom:16px;"></i>
            <h3 class="text-xl font-bold mb-2">No Posts Yet</h3>
            <p class="text-twitch-muted mb-4">Be the first to share something with the community!</p>
            <button onclick="document.getElementById('create-post-form').style.display = 'block'" class="tw-btn tw-btn-primary">
                <i class="fas fa-plus"></i>
                Create First Post
            </button>
        </div>
    <?php endif; ?>
</div>

<style>
@media (max-width: 768px) {
    div[style*="animation: fade-in"] {
        padding: 0 4px;
    }
}
</style>
