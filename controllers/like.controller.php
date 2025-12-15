<?php
// Bắt đầu output buffering để tránh lỗi header
ob_start();

require_once __DIR__ . '/../config/session.php';
require_once "../config/database.php";
require_once "../models/like.model.php";
require_once "../models/post.model.php";
require_once "../models/notification.model.php";

header('Content-Type: application/json; charset=utf-8');

$likeModel = new LikeModel($mysqli);
$postModel = new PostModel($mysqli);
$notificationModel = new NotificationModel($mysqli);

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

$response = null;
$http_code = 200;

if (!$user_id) {
    $http_code = 401;
    $response = ['ok' => false, 'msg' => 'Unauthorized'];
} else {
    switch ($action) {
        case 'like':
            $post_id = (int)($_POST['post_id'] ?? 0);
            if (!$post_id) {
                $http_code = 400;
                $response = ['ok' => false, 'msg' => 'Missing post_id'];
                break;
            }

            $is_new_like = $likeModel->addLike($post_id, $user_id);

            if ($is_new_like) {
                // Gửi thông báo cho chủ bài viết
                try {
                    $post = $postModel->getPostById($post_id);
                    if ($post) {
                        $recipient_id = (int)$post['user_id'];
                        // Không tự gửi thông báo cho chính mình
                        if ($recipient_id > 0 && $recipient_id !== $user_id) {
                            $username = $_SESSION['username'] ?? 'Someone';
                            $noti_content = htmlspecialchars($username) . " đã thích bài viết của bạn.";
                            $notificationModel->addNotification($recipient_id, $noti_content);
                        }
                    }
                } catch (Exception $e) {
                    // Ghi lại lỗi nếu cần, nhưng không làm hỏng request
                }
            }

            $count = $likeModel->countLikes($post_id);
            $response = ['ok' => true, 'status' => 'liked', 'count' => $count];
            break;

        case 'unlike':
            $post_id = (int)($_POST['post_id'] ?? 0);
            if (!$post_id) {
                $http_code = 400;
                $response = ['ok' => false, 'msg' => 'Missing post_id'];
                break;
            }
            $likeModel->removeLike($post_id, $user_id);
            $count = $likeModel->countLikes($post_id);
            $response = ['ok' => true, 'status' => 'unliked', 'count' => $count];
            break;

        case 'count':
            $post_id = (int)($_GET['post_id'] ?? 0);
            $count = $likeModel->countLikes($post_id);
            $response = ['ok' => true, 'count' => $count];
            break;

        case 'check':
            $post_id = (int)($_GET['post_id'] ?? 0);
            $liked = $likeModel->hasLiked($post_id, $user_id);
            $response = ['ok' => true, 'liked' => $liked];
            break;

        default:
            $http_code = 400;
            $response = ['ok' => false, 'msg' => 'no_action'];
            break;
    }
}

// Dọn dẹp buffer và gửi phản hồi JSON
if ($http_code) { http_response_code($http_code); }
if (ob_get_length() > 0) { @ob_end_clean(); }
echo json_encode($response);
exit;
