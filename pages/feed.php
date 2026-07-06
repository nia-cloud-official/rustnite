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
$page_num = (int) ($_GET["p"] ?? 1);
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
if (!empty($filter_type)) {
    $count_sql .= " WHERE type = ?";
    $total = $pdo->prepare($count_sql);
    $total->execute([$filter_type]);
} else {
    $total = $pdo->query($count_sql);
}
$total_posts = $total->fetch()["count"];
$total_pages = ceil($total_posts / $per_page);

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
            <p class="text-twitch-muted mt-1">Share ideas, ask questions, write blogs, and connect with the community!</p>
        </div>
        <button onclick="document.getElementById('create-post-form').style.display = document.getElementById('create-post-form').style.display === 'none' ? 'block' : 'none'" class="tw-btn tw-btn-primary">
            <i class="fas fa-plus"></i>
            Create Post
        </button>
    </div>

    <!-- Create Post Form -->
    <div id="create-post-form" class="tw-card tw-card-body mb-6" style="display:<?= $post_error ||
    $post_success
        ? "block"
        : "none" ?>; border-color: rgba(145,71,255,0.3);">
        <h3 class="font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-pen-fancy" style="color: #9147FF;"></i>
            Create a Post
        </h3>

        <?php if ($post_error): ?>
            <div style="background:rgba(233,25,123,0.1); border:1px solid rgba(233,25,123,0.2); border-radius:8px; padding:12px; margin-bottom:16px;">
                <span style="color:#E9197B; font-size:13px;"><?= htmlspecialchars(
                    $post_error,
                ) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($post_success): ?>
            <div style="background:rgba(0,217,90,0.1); border:1px solid rgba(0,217,90,0.2); border-radius:8px; padding:12px; margin-bottom:16px;">
                <span style="color:#00D95A; font-size:13px;"><?= htmlspecialchars(
                    $post_success,
                ) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-twitch-muted mb-1">Post Type</label>
                    <select name="post_type" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text">
                        <?php foreach ($post_types as $key => $info): ?>
                            <option value="<?= $key ?>"><i class="fas <?= $info[
    "icon"
] ?>"></i> <?= ucfirst($key) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm text-twitch-muted mb-1">Title (optional)</label>
                    <input type="text" name="post_title" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text" placeholder="Give your post a title..." maxlength="200">
                </div>
                <div>
                    <label class="block text-sm text-twitch-muted mb-1">Content *</label>
                    <textarea name="post_content" rows="4" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text" placeholder="Share your thoughts, ask a question, or write a blog post..." required></textarea>
                </div>
                <div>
                    <label class="block text-sm text-twitch-muted mb-1">Code Snippet (optional)</label>
                    <textarea name="code_snippet" rows="3" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text font-mono text-sm" placeholder="Paste code here..."></textarea>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-twitch-muted mb-1">Language</label>
                        <select name="language_slug" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text">
                            <option value="">None</option>
                            <?php foreach ($languages as $lang): ?>
                                <option value="<?= $lang["slug"] ?>"><?= $lang[
    "name"
] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-twitch-muted mb-1">Tags (comma separated)</label>
                        <input type="text" name="tags" class="w-full p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text" placeholder="e.g. rust, beginners, help">
                    </div>
                </div>
                <button type="submit" name="create_post" class="tw-btn tw-btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Publish Post
                </button>
            </div>
        </form>
    </div>

    <!-- Type Filters -->
    <div class="flex items-center gap-2 mb-6 overflow-x-auto" style="scrollbar-width:none;">
        <a href="?page=feed" class="tw-btn <?= empty($filter_type)
            ? "tw-btn-primary"
            : "tw-btn-ghost" ?> tw-btn-sm">
            <i class="fas fa-globe"></i>
            All
        </a>
        <?php foreach ($post_types as $key => $info): ?>
            <a href="?page=feed&type=<?= $key ?>" class="tw-btn <?= $filter_type ===
$key
    ? "tw-btn-primary"
    : "tw-btn-ghost" ?> tw-btn-sm">
                <i class="fas <?= $info["icon"] ?>"></i>
                <?= ucfirst($key) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Posts List -->
    <?php if (!empty($posts)): ?>
        <?php foreach ($posts as $post):

            $type_info = $post_types[$post["type"]] ?? $post_types["post"];
            $user_liked = $post["user_liked"] ? true : false;
            $likes = max($post["likes_count"], $post["likes_count_real"]);
            $comments = max(
                $post["comments_count"],
                $post["comments_count_real"],
            );
            ?>
            <div class="tw-card mb-4" style="animation: slide-up 0.3s ease-out;">
                <div class="tw-card-body">
                    <div class="flex items-start gap-4">
                        <!-- Type Icon -->
                        <div class="tw-avatar" style="width:40px; height:40px; font-size:14px; background:<?= $type_info[
                            "color"
                        ] ?>20; color:<?= $type_info[
    "color"
] ?>; flex-shrink:0;">
                            <i class="fas <?= $type_info["icon"] ?>"></i>
                        </div>

                        <div style="flex:1; min-width:0;">
                            <!-- Post Header -->
                            <div class="flex items-center gap-2 mb-1">
                                <span class="font-medium text-sm"><?= htmlspecialchars(
                                    $post["username"],
                                ) ?></span>
                                <span class="text-xs px-1.5 py-0.5 rounded" style="background:<?= $type_info[
                                    "color"
                                ] ?>20; color:<?= $type_info[
    "color"
] ?>; font-size:10px;">
                                    <?= ucfirst($post["type"]) ?>
                                </span>
                                <span class="text-xs text-twitch-muted"><?= time_ago(
                                    $post["created_at"],
                                ) ?></span>
                                <?php if ($post["is_pinned"]): ?>
                                    <span class="text-xs" style="color:#A970FF;"><i class="fas fa-thumbtack"></i> Pinned</span>
                                <?php endif; ?>
                            </div>

                            <!-- Title -->
                            <?php if (!empty($post["title"])): ?>
                                <h3 class="font-bold text-base mb-1"><?= htmlspecialchars(
                                    $post["title"],
                                ) ?></h3>
                            <?php endif; ?>

                            <!-- Content -->
                            <div class="text-sm text-twitch-text mb-3" style="line-height:1.6; white-space:pre-wrap;"><?= htmlspecialchars(
                                $post["content"],
                            ) ?></div>

                            <!-- Code Snippet -->
                            <?php if (!empty($post["code_snippet"])): ?>
                                <pre class="p-3 bg-twitch-dark rounded-lg text-sm font-mono mb-3 overflow-x-auto" style="border:1px solid #2D2D35;"><code><?= htmlspecialchars(
                                    $post["code_snippet"],
                                ) ?></code></pre>
                            <?php endif; ?>

                            <!-- Tags -->
                            <?php if (!empty($post["tags"])): ?>
                                <div class="flex items-center gap-2 mb-3 flex-wrap">
                                    <?php foreach (
                                        explode(",", $post["tags"])
                                        as $tag
                                    ): ?>
                                        <span class="text-xs px-2 py-0.5 rounded-full" style="background:rgba(145,71,255,0.1); color:#A970FF;">#<?= trim(
                                            htmlspecialchars($tag),
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
] ?>" class="flex items-center gap-1 text-sm <?= $user_liked
    ? "tw-btn-primary"
    : "text-twitch-muted" ?>" style="text-decoration:none;">
                                    <i class="fas fa-heart <?= $user_liked
                                        ? "text-twitch-pink"
                                        : "" ?>" style="color:<?= $user_liked
    ? "#E9197B"
    : "" ?>;"></i>
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

                                <?php if (
                                    $post["user_id"] === $_SESSION["user_id"]
                                ): ?>
                                    <a href="?page=feed&delete=<?= $post[
                                        "id"
                                    ] ?>&confirm=1" class="flex items-center gap-1 text-sm text-twitch-muted" style="text-decoration:none;" onclick="return confirm('Delete this post?')">
                                        <i class="fas fa-trash"></i>
                                        <span>Delete</span>
                                    </a>
                                <?php endif; ?>
                            </div>

                            <!-- Comments (only on single view) -->
                            <?php if ($view_post_id > 0): ?>
                                <?php
                                $stmt = $pdo->prepare(
                                    "SELECT fc.*, u.username FROM feed_comments fc JOIN users u ON fc.user_id = u.id WHERE fc.post_id = ? ORDER BY fc.created_at ASC",
                                );
                                $stmt->execute([$post["id"]]);
                                $comments_list = $stmt->fetchAll();
                                ?>
                                <div class="mt-4 pt-4" style="border-top:1px solid #2D2D35;">
                                    <h4 class="font-bold text-sm mb-3">Comments (<?= count(
                                        $comments_list,
                                    ) ?>)</h4>

                                    <?php if (!empty($comments_list)): ?>
                                        <?php foreach (
                                            $comments_list
                                            as $comment
                                        ): ?>
                                            <div class="flex items-start gap-3 mb-3">
                                                <div class="tw-avatar" style="width:28px; height:28px; font-size:10px;"><?= get_avatar_letter(
                                                    $comment["username"],
                                                ) ?></div>
                                                <div style="flex:1;">
                                                    <div class="flex items-center gap-2 mb-1">
                                                        <span class="font-medium text-xs"><?= htmlspecialchars(
                                                            $comment[
                                                                "username"
                                                            ],
                                                        ) ?></span>
                                                        <span class="text-xs text-twitch-muted"><?= time_ago(
                                                            $comment[
                                                                "created_at"
                                                            ],
                                                        ) ?></span>
                                                    </div>
                                                    <p class="text-sm"><?= htmlspecialchars(
                                                        $comment["content"],
                                                    ) ?></p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <form method="POST" class="flex gap-2 mt-3">
                                        <input type="hidden" name="post_id" value="<?= $post[
                                            "id"
                                        ] ?>">
                                        <input type="text" name="comment_content" class="flex-1 p-2 bg-twitch-medium border border-twitch-border rounded-lg text-twitch-text text-sm" placeholder="Write a comment..." required>
                                        <button type="submit" name="add_comment" class="tw-btn tw-btn-primary tw-btn-sm">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php
        endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1 && !$view_post_id): ?>
            <div class="flex items-center justify-center gap-2 mt-6">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=feed&p=<?= $i .
                        (!empty($filter_type)
                            ? "&type=" . urlencode($filter_type)
                            : "") ?>" class="tw-btn <?= $i === $page_num
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
/* Responsive feed */
@media (max-width: 768px) {
    div[style*="animation: fade-in"] {
        padding: 0 4px;
    }
}
</style>
