<?php
require_once __DIR__ . '/../config/session.php';
require_once "../config/database.php";
require_once "../models/like.model.php";

header('Content-Type: application/json; charset=utf-8');
$likeModel = new LikeModel($mysqli);
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

switch ($action) {
    case 'like':
        if (!$user_id) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
            break;
        }
        $post_id = (int)$_POST['post_id'];
        $likeModel->addLike($post_id, $user_id);
        $count = $likeModel->countLikes($post_id);
        echo json_encode(['ok' => true, 'status' => 'liked', 'count' => $count]);
        break;

    case 'unlike':
        if (!$user_id) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'msg' => 'Unauthorized']);
            break;
        }
        $post_id = (int)$_POST['post_id'];
        $likeModel->removeLike($post_id, $user_id);
        $count = $likeModel->countLikes($post_id);
        echo json_encode(['ok' => true, 'status' => 'unliked', 'count' => $count]);
        break;

    case 'count':
        $post_id = (int)$_GET['post_id'];
        $count = $likeModel->countLikes($post_id);
        echo json_encode(['ok' => true, 'count' => $count]);
        break;

    case 'check':
        $post_id = (int)$_GET['post_id'];
        if (!$user_id) {
            // not logged in -> cannot have liked
            echo json_encode(['ok' => true, 'liked' => false]);
        } else {
            $liked = $likeModel->hasLiked($post_id, $user_id);
            echo json_encode(['ok' => true, 'liked' => $liked]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'no_action']);
        break;
}
