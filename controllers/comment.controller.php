<?php
// start output buffering to avoid accidental non-JSON output
ob_start();
// don't show PHP notices/warnings to clients
ini_set('display_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../config/session.php';
}
require_once "../config/database.php";
require_once "../models/comment.model.php";
require_once "../models/user.model.php";
require_once "../models/post.model.php";
require_once "../models/notification.model.php";


$commentModel = new CommentModel($mysqli);
$userModel = new UserModel($mysqli);
// *** MỚI: Khởi tạo các model ***
$postModel = new PostModel($mysqli); // Giả sử tên model của bạn là PostModel
$notificationModel = new NotificationModel($mysqli);

$action = $_POST['action'] ?? $_GET['action'] ?? null;

// ... (phần debug log của bạn giữ nguyên) ...
$__logPath = __DIR__ . '/../comment_debug.log';
$__logEntry = date('c') . " | action=" . ($action ?? 'null') . " | session_user=" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null') . " | GET=" . json_encode($_GET) . " | POST=" . json_encode($_POST) . "\n";
@file_put_contents($__logPath, $__logEntry, FILE_APPEND);


// prepare response container
$response = null;
$http_code = 200;

switch ($action) {
    case 'add':
        $post_id = (int)($_POST['post_id'] ?? 0);
        $user_id = $_SESSION['user_id'] ?? 0; // Đây là ID của người bình luận
        $content = trim($_POST['content'] ?? '');
        if (!$user_id) {
            $http_code = 401;
            $response = ['error' => 'Unauthorized'];
            break;
        }
        if (!$post_id || $content === '') {
            $http_code = 400;
            $response = ['error' => 'Missing parameters'];
            break;
        }
        $ok = $commentModel->addComment($post_id, $user_id, $content);
        if ($ok) {
            // Lấy username của người bình luận
            $username = $_SESSION['username'] ?? null;
            if (!$username) {
                $u = $userModel->getUserById($user_id);
                $username = $u['username'] ?? 'user';
            }
            
            // *** LOGIC THÔNG BÁO MỚI ***
            try {
                // 1. Lấy ID của chủ bài viết (người nhận thông báo)
                // (Bạn cần đảm bảo bạn có hàm getPostById() trong PostModel)
                $post = $postModel->getPostById($post_id); 
                if ($post) {
                    $recipient_id = (int)$post['user_id'];
                    
                    // 2. Không tự gửi thông báo cho chính mình
                    if ($recipient_id > 0 && $recipient_id !== $user_id) {
                        // 3. Tạo nội dung và gửi thông báo
                        $noti_content = $username . " đã bình luận về bài viết của bạn.";
                        $notificationModel->addNotification($recipient_id, $noti_content);
                    }
                }
            } catch (Exception $e) {
                // Ghi lỗi nếu có, nhưng không làm hỏng request chính
                @file_put_contents($__logPath, "Notification Error: " . $e->getMessage() . "\n", FILE_APPEND);
            }
            // *** KẾT THÚC LOGIC THÔNG BÁO ***

            // Trả về response như bình thường
            $response = ['status' => 'ok', 'comment' => ['idcmt' => $mysqli->insert_id ?? null, 'post_id' => $post_id, 'user_id' => $user_id, 'username' => $username, 'content' => $content, 'created_at' => date('Y-m-d H:i:s')]];
        
        } else {
            $http_code = 500;
            $response = ['error' => 'Insert failed'];
        }
        break;

    // ... (các case 'list', 'count', 'delete', 'update' giữ nguyên) ...
    
    case 'list_json':
    case 'list':
        $post_id = (int)($_GET['post_id'] ?? 0);
        if (!$post_id) { $response = []; break; }
        $res = $commentModel->getCommentsByPost($post_id);
        $out = [];
        if ($res && $res instanceof mysqli_result) {
            while ($row = $res->fetch_assoc()) {
                $out[] = $row;
            }
        }
        $response = $out;
        break;

    case 'count':
        $post_id = (int)($_GET['post_id'] ?? 0);
        if (!$post_id) { $response = ['count' => 0]; break; }
        $stmt = $mysqli->prepare("SELECT COUNT(*) AS c FROM comments WHERE post_id = ?");
        $stmt->bind_param('i', $post_id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $cnt = (int)($r['c'] ?? 0);
        $response = ['count' => $cnt];
        break;

    case 'delete':
        $idcmt = (int)($_POST['idcmt'] ?? 0);
        $ok = $commentModel->deleteComment($idcmt);
        $response = ['status' => $ok ? 'ok' : 'fail'];
        break;

    case 'update':
        $idcmt = (int)($_POST['idcmt'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $ok = $commentModel->updateComment($idcmt, $content);
        $response = ['status' => $ok ? 'ok' : 'fail'];
        break;

    default:
        $http_code = 400;
        $response = ['error' => 'no_action'];
        break;
}

// ... (phần code trả về JSON của bạn giữ nguyên) ...
header('Content-Type: application/json; charset=utf-8');
if ($http_code) { http_response_code($http_code); }
if (ob_get_length() !== false) { @ob_end_clean(); }
echo json_encode($response);
exit;