<?php
// Suppress any output before setting content type
ob_start();

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/post.model.php';
require_once __DIR__ . '/../models/recommendation.model.php';
require_once __DIR__ . '/../controllers/recommendation.controller.php';

$tab = $_GET['tab'] ?? 'recommended';
$userId = $_SESSION['user_id'] ?? 0;

$postModel = new PostModel($mysqli);
$recModel = new RecommendationModel($mysqli);

$posts = [];

if ($tab === 'following' && $userId) {
    // Get posts from followed users
    $result = $postModel->getFollowingPosts($userId, 20);
    if ($result && $result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
    }
} else {
    // Default to recommended (all posts + suggestions)
    $result = $recModel->getRecommendations($userId, 20);
    if ($result && $result instanceof mysqli_result) {
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }
    }
}

// If following tab but no posts, show message
if (empty($posts) && $tab === 'following') {
    echo '<p style="text-align: center; color: #6b7280; padding: 20px;">Bạn chưa theo dõi ai hoặc những người bạn theo dõi chưa có bài viết.</p>';
    ob_end_flush();
    exit;
}

// If no posts at all
if (empty($posts)) {
    echo '<p style="text-align: center; color: #6b7280; padding: 20px;">Không có bài viết nào.</p>';
    ob_end_flush();
    exit;
}

ob_end_clean();
?>
<script>
    // Define CURRENT_USER_ID in window scope so it's available to initPostEventListeners
    window.CURRENT_USER_ID_FEED = <?php echo (int)$userId; ?>;
</script>
<?php

// Include post-card view to render posts
include __DIR__ . '/../views/post-card.php';
?>

<script>
    // Call init function after posts are rendered
    (function() {
        // Small delay to ensure DOM is fully painted
        setTimeout(function() {
            if (window.initPostEventListeners) {
                console.log('Initializing post event listeners from feed');
                window.initPostEventListeners();
            } else {
                console.warn('initPostEventListeners not found');
            }
        }, 10);
    })();
</script>

