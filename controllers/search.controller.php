<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/search.model.php';

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$searchModel = new SearchModel($mysqli);

header('Content-Type: application/json; charset=utf-8');

// --- THAY ĐỔI: Kiểm tra User ID ngay từ đầu ---
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
    exit;
}
$userId = (int)$userId; // Đảm bảo là số nguyên

// --- Xử lý các yêu cầu GET ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'history') {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $res = $searchModel->getHistory($userId, $limit); // $userId đã được xác thực
        $out = [];
        if ($res && $res instanceof mysqli_result) {
            while ($r = $res->fetch_assoc()) {
                $out[] = $r;
            }
        }
        echo json_encode(['ok' => true, 'history' => $out]);
        exit;
    }
}

// --- Xử lý các yêu cầu POST (để xóa) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // *** HÀNH ĐỘNG MỚI: Xóa một mục ***
    if ($action === 'delete_item') {
        $history_id = $_POST['id'] ?? null;
        if (!$history_id) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Missing ID']);
            exit;
        }
        // $userId đã được xác thực
        $ok = $searchModel->deleteHistoryItem($userId, (int)$history_id);
        echo json_encode(['ok' => $ok]);
        exit;
    }

    // *** HÀNH ĐỘNG MỚI: Xóa tất cả ***
    if ($action === 'clear_all') {
        // $userId đã được xác thực
        $ok = $searchModel->clearHistory($userId);
        echo json_encode(['ok' => $ok]);
        exit;
    }
}

// Lỗi nếu không có hành động nào khớp
http_response_code(400);
echo json_encode(['ok' => false, 'msg' => 'no_action_matched']);
exit;
?>