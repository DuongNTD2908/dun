<?php
// Start output buffering immediately
ob_start();

// Prevent PHP notices/warnings
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../models/follow.model.php";
require_once __DIR__ . "/../models/user.model.php";
require_once __DIR__ . "/../models/notification.model.php";


$followModel = new FollowModel($mysqli);
// *** MỚI: BẠN CŨNG THIẾU CÁC DÒNG NÀY ***
$userModel = new UserModel($mysqli);
$notificationModel = new NotificationModel($mysqli);

$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Always return JSON
header('Content-Type: application/json; charset=utf-8');

// ... (phần debug log của bạn giữ nguyên) ...
$logPath = __DIR__ . '/../follow_debug.log';
$logEntry = date('c') . " | action=" . ($action ?? 'null') . " | session_user=" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null') . " | post=" . json_encode($_POST) . "\n";
@file_put_contents($logPath, $logEntry, FILE_APPEND);


// Build a response array
$response = ['error' => 'no_action'];

switch ($action) {
    case 'follow':
        $follower_id = $_SESSION['user_id'] ?? null; // Người đi theo dõi
        $following_id = $_POST['following_id'] ?? null; // Người được theo dõi
        
        if (!$follower_id) {
            http_response_code(401);
            $response = ['error' => 'Unauthorized'];
            break;
        }
        if (!$following_id) {
            http_response_code(400);
            $response = ['error' => 'missing following_id'];
            break;
        }

        // 1. Thực hiện hành động follow
        $followModel->followUser((int)$follower_id, (int)$following_id);
        
        // 2. LOGIC THÔNG BÁO (Sẽ không còn lỗi vì đã require model)
        try {
            // Lấy tên của người đi theo dõi
            $follower_username = $_SESSION['username'] ?? null;
            if (!$follower_username) {
                // $userModel giờ đã tồn tại
                $u = $userModel->getUserById($follower_id); 
                $follower_username = $u['username'] ?? 'Someone';
            }
            
            // Tạo nội dung và gửi thông báo
            $noti_content = $follower_username . " đã bắt đầu theo dõi bạn.";
            // $notificationModel giờ đã tồn tại
            $notificationModel->addNotification((int)$following_id, $noti_content); 

        } catch (Exception $e) {
            // Ghi lỗi nếu có
            @file_put_contents($logPath, "Notification Error: " . $e->getMessage() . "\n", FILE_APPEND);
        }
        
        // 3. Đặt phản hồi thành công
        $response = ['status' => 'followed'];
        break;

    // case 'unfollow' không có logic thông báo, nên nó hoạt động bình thường
    case 'unfollow':
        $follower_id = $_SESSION['user_id'] ?? null;
        $following_id = $_POST['following_id'] ?? null;
        if (!$follower_id) {
            http_response_code(401);
            $response = ['error' => 'Unauthorized'];
            break;
        }
        if (!$following_id) {
            http_response_code(400);
            $response = ['error' => 'missing following_id'];
            break;
        }
        $followModel->unfollowUser((int)$follower_id, (int)$following_id);
        $response = ['status' => 'unfollowed'];
        break;

    case 'followers':
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) { http_response_code(401); $response = ['error' => 'Unauthorized']; break; }
        $result = $followModel->getFollowers($user_id);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $response = $data;
        break;

    case 'following':
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) { http_response_code(401); $response = ['error' => 'Unauthorized']; break; }
        $result = $followModel->getFollowing($user_id);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $response = $data;
        break;

    case 'friends':
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) { http_response_code(401); $response = ['error' => 'Unauthorized']; break; }
        $result = $followModel->getFriends($user_id);
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $response = $data;
        break;

    case 'is_following':
        $follower_id = $_SESSION['user_id'] ?? null;
        $following_id = $_POST['following_id'] ?? $_GET['following_id'] ?? null;
        if (!$follower_id) {
            $response = ['is_following' => false];
            break;
        }
        if (!$following_id) { http_response_code(400); $response = ['error' => 'missing following_id']; break; }
        $is = (bool)$followModel->isFollowing((int)$follower_id, (int)$following_id);
        $response = ['is_following' => $is];
        break;
}

// ... (phần code trả về JSON của bạn giữ nguyên) ...
$buffer = '';
if (ob_get_length() !== false) {
    $buffer = ob_get_clean();
}

echo json_encode($response);
exit;