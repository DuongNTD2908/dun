<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . "/../models/post.model.php";
require_once __DIR__ . "/../models/topic.model.php";
require_once __DIR__ . '/../config/upload_helper.php';
$postModel = new PostModel($mysqli);
$posts = [];
$result = $postModel->getAllPostsWithImages();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }
}
$action = $_GET['action'] ?? $_POST['action'] ?? null;
switch ($action) {
    case 'ajax_get':
        // Return JSON details for a post (used by search modal)
        header('Content-Type: application/json; charset=utf-8');
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['ok'=>false,'msg'=>'missing id']); exit; }
        $p = $postModel->getPostById($id);
        if (!$p) { echo json_encode(['ok'=>false,'msg'=>'not found']); exit; }
        // images
        $imgs = [];
        $imgsRes = $postModel->getImagesByPost($id);
        if ($imgsRes && $imgsRes instanceof mysqli_result) {
            while ($r = $imgsRes->fetch_assoc()) $imgs[] = $r['image_url'];
        }
        echo json_encode(['ok'=>true,'post'=>$p,'images'=>$imgs]);
        exit;

    case 'list':
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $posts = $postModel->getAllPostsWithImages();
            $data = [];
            if ($posts) {
                while ($row = $posts->fetch_assoc()) {
                    $data[$row['idpost']]['idpost'] = $row['idpost'];
                    $data[$row['idpost']]['username'] = $row['username'];
                    $data[$row['idpost']]['title'] = $row['title'];
                    $data[$row['idpost']]['topic_name'] = $row['topic_name'];
                    $data[$row['idpost']]['images'][] = $row['image_url'];
                }
            }
            header('Content-Type: application/json');
            echo json_encode(array_values($data));
            exit;
        }
        break;
    case 'feed':
        // Action mới để lấy feed (Recommended hoặc Following) trả về HTML
        $tab = $_GET['tab'] ?? 'recommended';
        $user_id = $_SESSION['user_id'] ?? 0;
        $posts = [];
        
        if ($tab === 'following') {
            if ($user_id) {
                $result = $postModel->getFollowingPosts($user_id);
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $posts[] = $row;
                    }
                }
            }
        } else {
            // Recommended (mặc định)
            $result = $postModel->getRecommendedPosts($user_id);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }
            }
        }
        
        // Include view để render HTML
        include __DIR__ . '/../views/post-card.php';
        break;
    case 'detail':
        $post = $postModel->getPostById($id);
        $images = $postModel->getImagesByPost($id);
        include "../index.php";
        break;
    case 'create':
        // Collect inputs
        $title = $_POST['title'] ?? null;
        $content = $_POST['content'] ?? '';
        $topic_id = $_POST['topic'] ?? null;
        $user_id = $_SESSION['user_id'] ?? null;

        if (!$user_id) {
            echo "<script>alert('Bạn cần đăng nhập để đăng bài'); window.location.href='../';</script>";
            exit;
        }

        if (empty(trim($title))) {
            echo "<script>alert('Tiêu đề không được để trống'); window.location.href='../';</script>";
            return;
        }

        if (empty(trim($content))) {
            echo "<script>alert('Nội dung không được để trống'); window.location.href='../';</script>";
            return;
        }

        // Create post
        $result = $postModel->createPost($user_id, $title, $content, $topic_id);
        if ($result) {
            $postId = $mysqli->insert_id;

            // Handle uploaded images (name="image_url[]") with centralized helper
            if (!empty($_FILES['image_url']) && is_array($_FILES['image_url']['name'])) {
                $res = save_uploaded_files_array($_FILES['image_url'], [
                    'subdir' => '',
                    'maxFiles' => 5,
                    'maxSize' => 2 * 1024 * 1024,
                    'allowedMimes' => ['image/jpeg','image/png','image/gif']
                ]);
                if (!empty($res['saved'])) {
                    foreach ($res['saved'] as $p) {
                        $postModel->addImage($postId, $p);
                    }
                }
                if (!empty($res['skipped'])) {
                    $msg = 'Một số file bị bỏ qua: ' . implode(', ', $res['skipped']);
                    echo "<script>alert('" . addslashes($msg) . "');</script>";
                }
            }

            echo "<script>alert('Đăng bài thành công'); window.location.href='../';</script>";
            exit;
        } else {
            echo "<script>alert('Lỗi khi đăng bài'); window.location.href='../';</script>";
            exit;
        }
        break;
    case "topicall":
        $topicModel->getAllTopics();
        break;
}
