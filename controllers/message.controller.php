<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/message.model.php';
require_once __DIR__ . '/../config/upload_helper.php';
$messageModel = new MessageModel($mysqli);
$action = $_POST['action'] ?? $_GET['action'] ?? null;
switch ($action) {
    case 'send':
        $sender_id = $_SESSION['user_id'];
        $receiver_id = $_POST['receiver_id'];
        $content = $_POST['content'];
        $type = 'text';
        $attachment_url = null;
        // Ensure conversation exists
        $conversation_id = $messageModel->getOrCreateConversation($sender_id, $receiver_id);

        // handle attachment securely using centralized helper
        if (!empty($_FILES['attachment']['name'])) {
            $res = save_uploaded_file_single($_FILES['attachment'], [
                'subdir' => 'messages',
                'maxSize' => 5 * 1024 * 1024,
                'allowedMimes' => ['image/jpeg','image/png','image/gif','application/pdf']
            ]);
            if ($res['ok']) {
                $attachment_url = $res['path'];
                $type = 'file';
            } else {
                header('Content-Type: application/json');
                $msg = 'Không thể lưu file: ' . ($res['error'] ?? 'error');
                echo json_encode(['status' => 'error', 'message' => $msg]);
                exit;
            }
        }

        $ok = $messageModel->sendMessage($sender_id, $receiver_id, $content, $type, $attachment_url, $conversation_id);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'attachment_url' => $attachment_url,
            'conversation_id' => $conversation_id
        ]);
        break;
    case 'read':
        $id = $_POST['id'];
        $messageModel->markAsRead($id);
        // respond JSON for AJAX
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        break;
    case 'delete':
        $id = $_POST['id'];
        $messageModel->deleteMessage($id);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        break;
    case 'inbox':
        $user_id = $_SESSION['user_id'];
        $keyword = $_GET['q'] ?? '';
        if ($keyword !== '') {
            $conversations = $messageModel->searchInbox($user_id, $keyword);
        } else {
            $conversations = $messageModel->getInbox($user_id);
        }
        // return JSON list for client-side rendering
        $rows = [];
        if ($conversations && $conversations instanceof mysqli_result) {
            while ($r = $conversations->fetch_assoc()) $rows[] = $r;
        }
        header('Content-Type: application/json');
        echo json_encode($rows);
        break;
    case 'conversation':
        $user1_id = $_SESSION['user_id'];
        $user2_id = $_GET['with'];
        $messages = $messageModel->getConversation($user1_id, $user2_id);
        $rows = [];
        if ($messages && $messages instanceof mysqli_result) {
            while ($r = $messages->fetch_assoc()) $rows[] = $r;
        }

        // also fetch other user's basic info for header display
        $stmt = $mysqli->prepare("SELECT iduser, username, name, avt FROM users WHERE iduser = ? LIMIT 1");
        $other = null;
        if ($stmt) {
            $stmt->bind_param('i', $user2_id);
            $stmt->execute();
            $other = $stmt->get_result()->fetch_assoc();
        }

        header('Content-Type: application/json');
        echo json_encode(['meta' => $other ?: new stdClass(), 'messages' => $rows]);
        break;
}
