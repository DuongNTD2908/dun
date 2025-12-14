<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/notification.model.php';

// Normalize DB variable name: some files export $mysqli, others $db
$dbConn = null;
if (isset($mysqli) && $mysqli) $dbConn = $mysqli;
elseif (isset($db) && $db) $dbConn = $db;

if (!$dbConn) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Không thể kết nối DB']);
    exit;
}

$notificationModel = new NotificationModel($dbConn); 

// Lấy action từ URL
$action = $_GET['action'] ?? '';

// *** THÊM DÒNG NÀY: Để đảm bảo JSON được trả về đúng cách ***
header('Content-Type: application/json; charset=utf-8');

switch ($action) {
    case 'list':
        // Lấy danh sách thông báo của user hiện tại
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401); // Thêm mã 401 Unauthorized
            echo json_encode(['ok' => false, 'msg' => 'Bạn chưa đăng nhập']);
            exit;
        }
        $result = $notificationModel->getNotifications($userId);
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        echo json_encode(['ok' => true, 'data' => $notifications]);
        break;

    case 'add':
        // Thêm thông báo mới
        $userId = $_POST['user_id'] ?? null;
        $content = trim($_POST['content'] ?? '');
        if (!$userId || empty($content)) {
            http_response_code(400); // Thêm mã 400 Bad Request
            echo json_encode(['ok' => false, 'msg' => 'Thiếu dữ liệu']);
            exit;
        }
        $ok = $notificationModel->addNotification($userId, $content);
        echo json_encode(['ok' => $ok]);
        break;

    case 'delete':
        // Xóa thông báo
        $idnoti = $_POST['idnoti'] ?? null;
        if (!$idnoti) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Thiếu id thông báo']);
            exit;
        }
        $ok = $notificationModel->deleteNotification($idnoti);
        echo json_encode(['ok' => $ok]);
        break;

    default:
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Hành động không hợp lệ']);
        break;
}