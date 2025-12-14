<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/user.model.php';
require_once __DIR__ . '/models/follow.model.php';

$userModel = new UserModel($mysqli);
$followModel = new FollowModel($mysqli);

$currentUserId = $_SESSION['user_id'] ?? 0;
$profileId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUserId;
$profile = $userModel->getUserById($profileId);
if (!$profile) {
    echo "<!doctype html><html><body><p>Người dùng không tồn tại.</p></body></html>";
    exit;
}

$followersRes = $followModel->getFollowers($profileId);
$followingRes = $followModel->getFollowing($profileId);
$followersCount = $followersRes ? $followersRes->num_rows : 0;
$followingCount = $followingRes ? $followingRes->num_rows : 0;
$isFollowing = $currentUserId ? $followModel->isFollowing($currentUserId, $profileId) : false;
// load posts for this profile
require_once __DIR__ . '/models/post.model.php';
$postModel = new PostModel($mysqli);
$posts = [];
$res = $postModel->getPostsByUser($profileId);
if ($res && $res instanceof mysqli_result) {
    while ($row = $res->fetch_assoc()) {
        // get first image for preview (if any)
        $imgs = $postModel->getImagesByPost($row['idpost']);
        $firstImg = null;
        if ($imgs && $imgs instanceof mysqli_result) {
            $imgRow = $imgs->fetch_assoc();
            if ($imgRow && !empty($imgRow['image_url'])) $firstImg = $imgRow['image_url'];
        }
        $row['image_url'] = $firstImg ?: '';
        $posts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - <?php echo htmlspecialchars($profile['username']); ?></title>
    <link rel="shortcut icon" href="src/img/logodun.png" type="image/x-icon">
    <link rel="stylesheet" href="src/css/style.css">
    <link rel="stylesheet" href="src/css/login.css">
    <link rel="stylesheet" href="src/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<body>
    <?php include 'views/topbar.php' ?>
    <style>

    </style>

    <div class="page">
        <div class="cover" aria-hidden="true">
            <!-- users.bg -->
            <?php $cover = !empty($profile['bg']) ? $profile['bg'] : 'src/img/black.jpg'; ?>
            <img src="<?php echo htmlspecialchars($cover); ?>" alt="cover">
            <!-- users.avt -->
            <div class="avatar-profile" role="img" aria-label="avatar">
                <?php $avt = !empty($profile['avt']) ? $profile['avt'] : 'src/img/black.jpg'; ?>
                <img src="<?php echo htmlspecialchars($avt); ?>" alt="avatar">
            </div>
        </div>

        <div class="header-row">
            <div class="name-block">
                <h1 class="name"><?php echo htmlspecialchars($profile['name'] ?: $profile['username']); ?>
                    <span style="font-weight:600;color:#374151;font-size:18px">(@<?php echo htmlspecialchars($profile['username']); ?>)</span>
                </h1>
                <div class="sub"><span id="followers-count"><?php echo $followersCount; ?></span> người theo dõi · <span id="following-count"><?php echo $followingCount; ?></span> đang theo dõi</div>
            </div>

            <div class="actions">
                <?php if ($currentUserId && $currentUserId !== $profileId) : ?>
                    <button id="message-btn" class="btn" onclick="location.href='message.php?action=start&user_id=<?php echo $profileId; ?>'">Nhắn tin</button>
                    <button id="follow-btn" class="btn <?php echo $isFollowing ? '' : 'primary'; ?>">
                        <?php echo $isFollowing ? 'Đang theo dõi' : 'Theo dõi'; ?>
                    </button>
                <?php else: ?>
                    <button class="btn primary" onclick="location.href='edit_profile.php'">Chỉnh sửa trang cá nhân</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="main">
            <aside class="col-left">
                <div class="card">
                    <h3>Giới thiệu</h3>
                    <!-- users.bio -->
                    <p style="margin:0 0 8px 0; color:var(--muted);"><?php echo nl2br(htmlspecialchars($profile['bio'] ?? '')); ?></p>
                    <!-- users.date users.gender users.create_at(chỉ lấy ngày tháng năm) -->
                    <?php if (!empty($profile['date']) || !empty($profile['gender']) || !empty($profile['location'])): ?>
                        <?php if (!empty($profile['date'])): ?>
                            <div class="info-row">Ngày sinh: <strong style="color:#111"><?php echo htmlspecialchars($profile['date']); ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($profile['gender'])): ?>
                            <div class="info-row">Giới tính: <strong style="color:#111"><?php echo htmlspecialchars($profile['gender']); ?></strong></div>
                        <?php endif; ?>
                        <?php if (!empty($profile['location'])): ?>
                            <div class="info-row">Sống tại <strong style="color:#111"><?php echo htmlspecialchars($profile['location']); ?></strong></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if (!empty($profile['website'])): ?>
                        <div class="info-row">🔗 <a href="<?php echo htmlspecialchars($profile['website']); ?>" style="color:var(--accent); text-decoration:none;" target="_blank"><?php echo htmlspecialchars($profile['website']); ?></a></div>
                    <?php endif; ?>
                </div>
            </aside>

            <section class="col-right">
                <div style="padding:8px 0 0 0;">
                    <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 16px;">
                        <div style="font-weight:800; font-size: 20px;">Bài viết</div>
                    </div>
                    <div style="padding:16px;">
                        <?php if (!empty($posts)) : ?>
                            <?php include __DIR__ . '/views/post-card.php'; ?>
                        <?php else : ?>
                            <div class="card">
                                <p style="margin:0;color:var(--muted);padding:16px;">Không có bài viết nào.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <script>
        (function() {
            const followBtn = document.getElementById('follow-btn');
            const followersCountEl = document.getElementById('followers-count');
            const profileId = <?php echo json_encode($profileId, JSON_HEX_TAG); ?>;
            const currentUserId = <?php echo json_encode($currentUserId, JSON_HEX_TAG); ?>;

            if (!followBtn) return;

            followBtn.addEventListener('click', async function() {
                if (!currentUserId) {
                    // not logged in -> redirect to login
                    window.location.href = 'index.php?page=login';
                    return;
                }

                const isFollowing = followBtn.textContent.trim().includes('Đang');
                const action = isFollowing ? 'unfollow' : 'follow';

                const form = new FormData();
                form.append('action', action);
                form.append('following_id', profileId);

                try {
                    const res = await fetch('controllers/follow.controller.php', {
                        method: 'POST',
                        body: form,
                        credentials: 'same-origin'
                    });
                    const data = await res.json();
                    if (res.ok) {
                        let count = parseInt(followersCountEl.textContent || '0', 10);
                        if (action === 'follow') {
                            followBtn.textContent = 'Đang theo dõi';
                            followBtn.classList.remove('primary');
                            followersCountEl.textContent = count + 1;
                        } else {
                            followBtn.textContent = 'Thêm bạn bè';
                            followBtn.classList.add('primary');
                            followersCountEl.textContent = Math.max(0, count - 1);
                        }
                    } else {
                        console.error('Follow API returned error', data);
                        alert('Không thể thay đổi trạng thái theo dõi. Vui lòng thử lại.');
                    }
                } catch (err) {
                    console.error(err);
                    alert('Lỗi mạng. Vui lòng thử lại.');
                }
            });
        })();
    </script>
    <script src="src/js/login.js"></script>
    <script src="src/js/main.js"></script>
</body>

</html>