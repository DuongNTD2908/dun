<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/user.model.php';
require_once __DIR__ . '/models/post.model.php';
require_once __DIR__ . '/models/report.model.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$userModel = new UserModel($mysqli);
$postModel = new PostModel($mysqli);
$reportModel = new ReportModel($mysqli);

// Basic admin guard: ensure logged in user has role 'admin'
$currentUserId = $_SESSION['user_id'] ?? 0;
$currentUser = $currentUserId ? $userModel->getUserById($currentUserId) : null;
if (!$currentUser || ($currentUser['role'] ?? '') !== 'admin') {
    echo "<!doctype html><html><body><h2>Access denied</h2><p>Bạn cần quyền admin để truy cập trang này.</p></body></html>";
    exit;
}

// --- XỬ LÝ CÁC HÀNH ĐỘNG POST ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_user') {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if ($username && $email && $password) {
            if ($userModel->isUserExist($username) || $userModel->isUserExist($email)) {
                $message = 'Username hoặc email đã tồn tại.';
            } else {
                $ok = $userModel->register($username, $password, $email, 'user');
                $message = $ok ? 'Tạo người dùng thành công.' : 'Lỗi khi tạo người dùng.';
            }
        } else {
            $message = 'Vui lòng điền đầy đủ thông tin tạo người dùng.';
        }
    } elseif ($action === 'soft_delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid) {
            $stmt = $mysqli->prepare("UPDATE users SET user_deleted = 1 WHERE iduser = ?");
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $message = 'Đã ẩn người dùng.';
        }
    } elseif ($action === 'restore_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid) {
            $stmt = $mysqli->prepare("UPDATE users SET user_deleted = 0 WHERE iduser = ?");
            $stmt->bind_param('i', $uid);
            $stmt->execute();
            $message = 'Đã phục hồi người dùng.';
        }
    } elseif ($action === 'delete_post') {
        $pid = (int)($_POST['post_id'] ?? 0);
        if ($pid) {
            $stmt = $mysqli->prepare("DELETE FROM posts WHERE idpost = ?");
            $stmt->bind_param('i', $pid);
            $stmt->execute();
            $stmt2 = $mysqli->prepare("DELETE FROM post_images WHERE post_id = ?");
            $stmt2->bind_param('i', $pid);
            $stmt2->execute();
            $message = 'Đã xóa bài viết.';
        }
    } elseif ($action === 'edit_post') {
        $pid = (int)($_POST['post_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $topic_id = (int)($_POST['topic_id'] ?? 0);
        if ($pid && $title && $content) {
            $stmt = $mysqli->prepare("UPDATE posts SET title = ?, content = ?, topic_id = ? WHERE idpost = ?");
            $stmt->bind_param('ssii', $title, $content, $topic_id, $pid);
            $stmt->execute();
            $message = 'Cập nhật bài viết thành công.';
        } else {
            $message = 'Thiếu dữ liệu cập nhật bài viết.';
        }
    } elseif ($action === 'handle_report') {
        $reportId = (int)($_POST['report_id'] ?? 0);
        $op = $_POST['op'] ?? '';
        if ($reportId && ($op === 'dismiss' || $op === 'delete')) {
            $report = $reportModel->getReportById($reportId);
            if ($report) {
                if ($op === 'delete') {
                    $pid = (int)$report['post_id'];
                    if ($pid) {
                        $stmt = $mysqli->prepare("DELETE FROM posts WHERE idpost = ?");
                        $stmt->bind_param('i', $pid);
                        $stmt->execute();
                        $stmt2 = $mysqli->prepare("DELETE FROM post_images WHERE post_id = ?");
                        $stmt2->bind_param('i', $pid);
                        $stmt2->execute();
                    }
                }
                $adminId = $_SESSION['user_id'] ?? 0;
                $reportModel->markHandled($reportId, $adminId, $op === 'delete' ? 'Deleted by admin' : 'Dismissed');
                $message = 'Đã xử lý báo cáo.';
            } else {
                $message = 'Báo cáo không tồn tại.';
            }
        } else {
            $message = 'Thiếu dữ liệu thao tác.';
        }
    }
}

// --- LOGIC TÌM KIẾM VÀ LỌC BÀI VIẾT (GET) ---

$search_keyword = $_GET['keyword'] ?? '';
$filter_topic = $_GET['filter_topic'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'id';
$sort_order = $_GET['sort_order'] ?? 'desc';

// --- SỬA LỖI SQL Ở ĐÂY ---
// 1. Sửa p.author_id thành p.user_id (Theo hình ảnh database cột là user_id)
$sql = "SELECT p.*, u.username, t.topic_name,
        (SELECT image_url FROM post_images pi WHERE pi.post_id = p.idpost LIMIT 1) as image_url
        FROM posts p
        LEFT JOIN users u ON p.user_id = u.iduser
        LEFT JOIN topics t ON p.topic_id = t.id
        WHERE 1=1";

$params = [];
$types = "";

// 1. Lọc theo từ khóa
if ($search_keyword !== '') {
    $sql .= " AND (p.title LIKE ? OR p.content LIKE ? OR u.username LIKE ?";
    if (is_numeric($search_keyword)) {
        $sql .= " OR p.idpost = ?";
    }
    $sql .= ")";

    $likeKw = "%" . $search_keyword . "%";
    $params[] = $likeKw;
    $params[] = $likeKw;
    $params[] = $likeKw;
    $types .= "sss";

    if (is_numeric($search_keyword)) {
        $params[] = $search_keyword;
        $types .= "i";
    }
}

// 2. Lọc theo Topic
if ($filter_topic !== '') {
    $sql .= " AND p.topic_id = ?";
    $params[] = $filter_topic;
    $types .= "i";
}

// 3. Xử lý Sắp xếp
$allowed_sorts = [
    'id' => 'p.idpost',
    'author' => 'u.username',
    'topic' => 't.topic_name'
];
$allowed_orders = ['asc', 'desc'];

$order_col = $allowed_sorts[$sort_by] ?? 'p.idpost';
$order_dir = in_array(strtolower($sort_order), $allowed_orders) ? $sort_order : 'desc';

$sql .= " ORDER BY $order_col $order_dir";

// Thực thi truy vấn
$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$allPostsRes = $stmt->get_result();

$allUsersRes = $userModel->getAllUsers();
?>
<!doctype html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Manager</title>
    <link rel="shortcut icon" href="src/img/logodun.png" type="image/x-icon">
    <link rel="stylesheet" href="src/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f3f4f6;
            margin: 0;
        }

        h1 {
            padding-top: 30px;
        }

        .container {
            display: flex;
            max-width: 1400px;
            margin: 36px auto;
            gap: 10px;
        }

        nav.sidebar {
            width: 260px;
            background: #b4d9ff85;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .04);
        }

        nav.sidebar h3 {
            font-size: 24px;
            text-align: center;
        }

        nav.sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0
        }

        nav.sidebar li {
            margin: 8px 0
        }

        nav.sidebar button {
            width: 100%;
            text-align: left;
            font-size: 16px;
            font-weight: bold;
            padding: 10px;
            border: 0;
            background: transparent;
            cursor: pointer;
            border-radius: 6px;
            transition: 0.25s linear;
        }

        nav.sidebar button:hover {
            background: #eef2ff;
            color: #1e40af
        }

        nav.sidebar button.active {
            background: #eef2ff;
            color: #1e40af
        }

        main.content {
            flex: 1
        }

        .card {
            background: #fff;
            padding: 16px;
            border-radius: 8px;
            box-shadow: 0 1px 0 rgba(0, 0, 0, .04);
            margin-bottom: 16px
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        table th,
        table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            text-align: left;
        }

        table tbody tr {
            transition: transform 0.1s linear;
        }

        table tbody tr:hover {
            transform: translateY(-10px);
        }

        .muted {
            color: #6b7280
        }

        .btn {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            background: #fff;
            cursor: pointer
        }

        .btn.primary {
            background: #2563eb;
            color: #fff;
            border: none
        }

        form.inline {
            display: inline
        }

        main.content section {
            display: none;
        }

        #edit-pane {
            display: none;
        }

        /* CSS Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 25px;
            border: 1px solid #888;
            width: 50%;
            min-width: 400px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        input[readonly] {
            background-color: #e9ecef;
            color: #495057;
            cursor: not-allowed;
            border: 1px solid #ced4da;
        }

        /* CSS cho form tìm kiếm */
        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: end;
        }

        .search-group {
            display: flex;
            flex-direction: column;
        }

        .search-group label {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
            color: #555;
        }

        .search-input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                margin: 10px;
            }
            nav.sidebar {
                width: 100%;
                margin-bottom: 20px;
            }
            .modal-content {
                width: 90%;
                min-width: auto;
            }
        }
    </style>
</head>

<body>

    <div style="max-width:1200px; padding:0 12px; display: flex; align-items: center;">
        <div onclick="window.location.href='index.php'" style="width: 35px;
    margin: 20px;
    padding: 20px 0;
    cursor: pointer;">
            <img src="src/img/back.png" width="100%" alt="Back">
        </div>
        <h1 style="padding: 0;">Trang quản lý</h1>
        <?php if ($message): ?>
            <div class="card"><strong><?php echo htmlspecialchars($message); ?></strong></div>
        <?php endif; ?>
    </div>

    <div class="container">
        <nav class="sidebar" aria-label="Admin navigation">
            <h3>Quản trị</h3>
            <ul>
                <li><button id="tab-posts" type="button" class="active" data-target="panel-posts">Quản lý bài viết</button></li>
                <li><button id="tab-users" type="button" data-target="panel-users">Quản lý người dùng</button></li>
                <li><button id="tab-reports" type="button" data-target="panel-reports">Báo cáo</button></li>
            </ul>
        </nav>

        <main class="content">

            <section id="panel-posts">
                <div class="card">
                    <h3>Tìm kiếm & Lọc bài viết</h3>
                    <form method="get" class="search-form">
                        <div class="search-group" style="flex: 2; min-width: 200px;">
                            <label>Từ khóa (ID, Tiêu đề, Nội dung, Tác giả)</label>
                            <input type="text" name="keyword" class="search-input"
                                placeholder="Nhập từ khóa..."
                                value="<?php echo htmlspecialchars($search_keyword); ?>">
                        </div>

                        <div class="search-group">
                            <label>Chủ đề</label>
                            <select name="filter_topic" class="search-input" style="min-width: 120px;">
                                <option value="">Tất cả</option>
                                <option value="1" <?php if ($filter_topic == '1') echo 'selected'; ?>>Đại học</option>
                                <option value="2" <?php if ($filter_topic == '2') echo 'selected'; ?>>Toán học</option>
                                <option value="3" <?php if ($filter_topic == '3') echo 'selected'; ?>>Vật lý</option>
                                <option value="4" <?php if ($filter_topic == '4') echo 'selected'; ?>>Hóa học</option>
                                <option value="5" <?php if ($filter_topic == '5') echo 'selected'; ?>>Ngữ văn</option>
                                <option value="6" <?php if ($filter_topic == '6') echo 'selected'; ?>>Lịch sử</option>
                                <option value="7" <?php if ($filter_topic == '7') echo 'selected'; ?>>Tiếng Anh</option>
                                <option value="8" <?php if ($filter_topic == '8') echo 'selected'; ?>>Tin học</option>
                                <option value="9" <?php if ($filter_topic == '9') echo 'selected'; ?>>Kỹ năng mềm</option>
                            </select>
                        </div>

                        <div class="search-group">
                            <label>Sắp xếp theo</label>
                            <select name="sort_by" class="search-input">
                                <option value="id" <?php if ($sort_by == 'id') echo 'selected'; ?>>ID Bài viết</option>
                                <option value="author" <?php if ($sort_by == 'author') echo 'selected'; ?>>Tác giả (A-Z)</option>
                                <option value="topic" <?php if ($sort_by == 'topic') echo 'selected'; ?>>Chủ đề</option>
                            </select>
                        </div>

                        <div class="search-group">
                            <label>Thứ tự</label>
                            <select name="sort_order" class="search-input">
                                <option value="desc" <?php if ($sort_order == 'desc') echo 'selected'; ?>>Giảm dần</option>
                                <option value="asc" <?php if ($sort_order == 'asc') echo 'selected'; ?>>Tăng dần</option>
                            </select>
                        </div>

                        <div class="search-group">
                            <label>&nbsp;</label>
                            <button class="btn primary" type="submit">Tìm kiếm</button>
                        </div>

                        <div class="search-group">
                            <label>&nbsp;</label>
                            <a href="admin_manager.php" class="btn" style="text-decoration:none; line-height: 1.5;">Đặt lại</a>
                        </div>
                    </form>
                </div>

                <div class="card">
                    <h3>Danh sách bài viết</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tiêu đề</th>
                                <th>Nội dung</th>
                                <th>Tác giả</th>
                                <th>Topic</th>
                                <th>Ảnh</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($allPostsRes && $allPostsRes instanceof mysqli_result): ?>
                                <?php while ($r = $allPostsRes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo (int)$r['idpost']; ?></td>
                                        <td><?php echo htmlspecialchars($r['title'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars(mb_strimwidth($r['content'] ?? '', 0, 100, '...')); ?></td>
                                        <td><?php echo htmlspecialchars($r['username'] ?? $r['name'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['topic_name'] ?? ''); ?></td>
                                        <td style="width:120px;">
                                            <img src="<?php echo htmlspecialchars($r['image_url'] ?? ''); ?>"
                                                style="width:100px;height:60px;object-fit:cover">
                                        </td>
                                        <td style="white-space:nowrap">
                                            <form class="inline" method="post" onsubmit="return confirm('Xóa bài viết này?');">
                                                <input type="hidden" name="action" value="delete_post">
                                                <input type="hidden" name="post_id" value="<?php echo (int)$r['idpost']; ?>">
                                                <button class="btn" type="submit">Xóa</button>
                                            </form>
                                            <button class="btn" onclick="populateEdit(
    <?php echo (int)$r['idpost']; ?>, 
    <?php echo htmlspecialchars(json_encode($r['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>, 
    <?php echo htmlspecialchars(json_encode($r['content'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>, 
    <?php echo (int)($r['topic_id'] ?? 0); ?>
)">Sửa</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="muted">Không tìm thấy bài viết nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div id="edit-modal" class="modal-overlay">
                    <div class="modal-content">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <h2 style="margin:0;">Chỉnh sửa bài viết</h2>
                            <button type="button" onclick="closeEditModal()" style="background:none;border:none;font-size:24px;cursor:pointer;">&times;</button>
                        </div>

                        <form method="post">
                            <input type="hidden" name="action" value="edit_post">
                            <input type="hidden" name="post_id" id="edit-post-id">

                            <div style="margin-bottom:12px">
                                <label style="font-weight:bold; display:block; margin-bottom:5px;">ID Bài viết (Không thể sửa)</label>
                                <input type="text" id="edit-id-display" readonly
                                    style="width:100%; padding:10px; border-radius:6px; box-sizing:border-box;">
                            </div>

                            <div style="display:flex; gap:12px; margin-bottom:12px">
                                <div style="flex:2;">
                                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Tiêu đề</label>
                                    <input id="edit-title" name="title" placeholder="Tiêu đề"
                                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;">
                                </div>
                                <div style="width:160px;">
                                    <label style="font-weight:bold; display:block; margin-bottom:5px;">Chủ đề (Topic ID)</label>
                                    <input id="edit-topic" name="topic_id" placeholder="ID"
                                        style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;">
                                </div>
                            </div>

                            <div style="margin-bottom:15px">
                                <label style="font-weight:bold; display:block; margin-bottom:5px;">Nội dung</label>
                                <textarea id="edit-content" name="content" rows="6"
                                    style="width:100%; padding:10px; border:1px solid #ddd; border-radius:6px; box-sizing:border-box;"
                                    placeholder="Nội dung bài viết"></textarea>
                            </div>

                            <div style="text-align:right; gap:10px; display:flex; justify-content:flex-end;">
                                <button class="btn" type="button" onclick="closeEditModal()" style="background:#ddd;">Hủy bỏ</button>
                                <button class="btn primary" type="submit">Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </section>

            <section id="panel-users">
                <div class="card">
                    <h3>Thêm người dùng</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="add_user">
                        <div style="display:flex;gap:8px;margin-bottom:8px">
                            <input name="username" placeholder="username" style="padding:8px;flex:1">
                            <input name="email" placeholder="email" style="padding:8px;width:260px">
                        </div>
                        <div style="margin-bottom:8px"><input name="password" type="password" placeholder="password" style="padding:8px;width:320px"></div>
                        <button class="btn primary" type="submit">Tạo người dùng</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Danh sách người dùng</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Trạng thái</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($allUsersRes && $allUsersRes instanceof mysqli_result): ?>
                                <?php while ($u = $allUsersRes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo (int)$u['iduser']; ?></td>
                                        <td><?php echo htmlspecialchars($u['username'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
                                        <td><?php echo !empty($u['user_deleted']) ? '<span class="muted">Đã ẩn</span>' : '<span>Hoạt động</span>'; ?></td>
                                        <td>
                                            <?php if (empty($u['user_deleted'])): ?>
                                                <form class="inline" method="post">
                                                    <input type="hidden" name="action" value="soft_delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['iduser']; ?>">
                                                    <button class="btn" type="submit">Ẩn</button>
                                                </form>
                                            <?php else: ?>
                                                <form class="inline" method="post">
                                                    <input type="hidden" name="action" value="restore_user">
                                                    <input type="hidden" name="user_id" value="<?php echo (int)$u['iduser']; ?>">
                                                    <button class="btn" type="submit">Phục hồi</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="muted">Không có người dùng.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="panel-reports">
                <div class="card">
                    <h3>Báo cáo bài viết</h3>
                    <?php $reports = $reportModel->getAllReports(); ?>
                    <?php if (!empty($reports)): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Bài viết</th>
                                    <th>Báo bởi</th>
                                    <th>Lý do</th>
                                    <th>Thời gian</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reports as $r): ?>
                                    <tr>
                                        <td><?php echo (int)$r['idreport']; ?></td>
                                        <td style="max-width:320px;"><strong><?php echo htmlspecialchars($r['post_title'] ?? '[deleted]'); ?></strong>
                                            <div class="muted" style="font-size:13px;"><?php echo htmlspecialchars(mb_strimwidth($r['post_content'] ?? '', 0, 120, '...')); ?></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['reporter_name'] ?? ''); ?></td>
                                        <td style="max-width:280px;white-space:pre-wrap;"><?php echo htmlspecialchars($r['reason'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                                        <td style="white-space:nowrap">
                                            <?php if (empty($r['handled'])): ?>
                                                <form class="inline" method="post" style="display:inline-block;margin-right:6px">
                                                    <input type="hidden" name="action" value="handle_report">
                                                    <input type="hidden" name="report_id" value="<?php echo (int)$r['idreport']; ?>">
                                                    <input type="hidden" name="op" value="delete">
                                                    <button class="btn" type="submit" onclick="return confirm('Xóa bài viết này và đánh dấu báo cáo là đã xử lý?')">Xóa bài viết</button>
                                                </form>
                                                <form class="inline" method="post" style="display:inline-block">
                                                    <input type="hidden" name="action" value="handle_report">
                                                    <input type="hidden" name="report_id" value="<?php echo (int)$r['idreport']; ?>">
                                                    <input type="hidden" name="op" value="dismiss">
                                                    <button class="btn" type="submit">Bỏ qua</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="muted">Đã xử lý</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="muted">Không có báo cáo mới.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        function showAdminPanel(targetId) {
            document.querySelectorAll('nav.sidebar button[data-target]').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('main.content section[id^="panel-"]').forEach(panel => {
                panel.style.display = 'none';
            });
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.style.display = 'block';
            }
            const activeButton = document.querySelector(`nav.sidebar button[data-target="${targetId}"]`);
            if (activeButton) {
                activeButton.classList.add('active');
            }
        }

        function initAdminTabs() {
            const sidebar = document.querySelector('nav.sidebar');
            if (!sidebar) return;
            sidebar.addEventListener('click', (e) => {
                const btn = e.target.closest('button[data-target]');
                if (btn) {
                    e.preventDefault();
                    showAdminPanel(btn.dataset.target);
                }
            });
            const defaultActiveBtn = document.querySelector('nav.sidebar button.active');
            const defaultTargetId = defaultActiveBtn ? defaultActiveBtn.dataset.target : 'panel-posts';
            showAdminPanel(defaultTargetId);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initAdminTabs);
        } else {
            initAdminTabs();
        }

        function populateEdit(id, title, content, topic_id) {
            var elIdHidden = document.getElementById('edit-post-id');
            var elIdDisplay = document.getElementById('edit-id-display');
            if (elIdHidden) elIdHidden.value = id;
            if (elIdDisplay) elIdDisplay.value = id;

            var elTitle = document.getElementById('edit-title');
            if (elTitle) elTitle.value = title ? title.toString().replace(/&quot;/g, '"') : '';

            var elContent = document.getElementById('edit-content');
            if (elContent) elContent.value = content ? content.toString().replace(/&quot;/g, '"') : '';

            var elTopic = document.getElementById('edit-topic');
            if (elTopic) elTopic.value = topic_id;

            var modal = document.getElementById('edit-modal');
            if (modal) {
                modal.style.display = 'block';
            }
        }

        function closeEditModal() {
            var modal = document.getElementById('edit-modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }

        window.onclick = function(event) {
            var modal = document.getElementById('edit-modal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>

</html>