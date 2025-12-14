<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/report.model.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$reportModel = new ReportModel($mysqli);

if ($action === 'report') {
    // create a new report (AJAX or form POST)
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'msg' => 'Invalid method']);
        exit;
    }
    $postId = (int)($_POST['post_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $reporter = $_SESSION['user_id'] ?? 0;
    if (!$postId || !$reason || !$reporter) {
        echo json_encode(['ok' => false, 'msg' => 'Thiếu dữ liệu hoặc chưa đăng nhập']);
        exit;
    }
    $ok = $reportModel->addReport($postId, $reporter, $reason);
    if ($ok) echo json_encode(['ok' => true, 'msg' => 'Báo cáo đã lưu']);
    else echo json_encode(['ok' => false, 'msg' => 'Lỗi lưu báo cáo']);
    exit;
}

// For other admin APIs (optional) could be added here.

echo json_encode(['ok' => false, 'msg' => 'No action']);
