<?php
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/post.model.php';
require_once __DIR__ . '/models/like.model.php';
require_once __DIR__ . '/models/comment.model.php';
require_once __DIR__ . '/models/search.model.php';

// Require login
if (empty($_SESSION['user_id'])) {
    // Redirect to home (client JS will open login modal) - but enforce server-side as well
    header('Location: /');
    exit;
}

$q = trim($_GET['q'] ?? '');
// Accept either 'topic' (new) or legacy 'topic_select' from topbar
$topic = $_GET['topic'] ?? $_GET['topic_select'] ?? 'all';

$searchModel = new SearchModel($mysqli);
if ($q !== '') {
    $searchModel->addHistory((int)$_SESSION['user_id'], $q);
}

// results containers
$posts = [];
$users = [];

if ($q !== '') {
    // limit query length
    $q = mb_substr($q, 0, 200);
    $like = '%' . $mysqli->real_escape_string($q) . '%';

    // If searching users specifically
    if ($topic === 'user') {
        $sql = "SELECT iduser, username, name, email, (SELECT COUNT(*) FROM posts p WHERE p.user_id = users.iduser) AS posts_count FROM users WHERE username LIKE ? OR name LIKE ? ORDER BY posts_count DESC LIMIT 100";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $like, $like);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) { $users[] = $row; }
        }
    } else {
        // search posts (topic === 'post' or 'all')
        $sql = "SELECT p.*, t.topic_name, u.username,
            (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.idpost) AS likes_count,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.idpost) AS comments_count
            FROM posts p
            JOIN users u ON p.user_id = u.iduser
            JOIN topics t ON p.topic_id = t.id
            WHERE (p.title LIKE ? OR p.content LIKE ?)";
        if ($topic === 'post') {
            // no change
        }
        $sql .= " ORDER BY (likes_count*2 + comments_count) DESC, p.created_at DESC LIMIT 100";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ss', $like, $like);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) { $posts[] = $row; }
        }

        // if topic is 'all' also include user matches (compact)
        if ($topic === 'all') {
            $sqlu = "SELECT iduser, username, name, email, (SELECT COUNT(*) FROM posts p WHERE p.user_id = users.iduser) AS posts_count FROM users WHERE username LIKE ? OR name LIKE ? ORDER BY posts_count DESC LIMIT 20";
            $st2 = $mysqli->prepare($sqlu);
            if ($st2) {
                $st2->bind_param('ss', $like, $like);
                $st2->execute();
                $res2 = $st2->get_result();
                while ($r2 = $res2->fetch_assoc()) { $users[] = $r2; }
            }
        }
    }
}

// Fetch user's recent search history
$history = $searchModel->getHistory((int)$_SESSION['user_id'], 10);

?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8" />
    <title>Tìm kiếm - DUN</title>
    <link rel="stylesheet" href="src/css/style.css">
    <link rel="stylesheet" href="src/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        body{
            background: #f9f9f9;
            height: 1000px;
        }
        .search-results {
            max-width: 900px;
            margin: 24px auto;
        }

        .result-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .meta {
            color: #666;
            font-size: 13px
        }

        .history {
            max-width: 900px;
            margin: 6px auto;
            padding: 12px;
            background: #fff;
            border-radius: 8px
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/views/topbar.php'; ?>
    <div class="search-results">
        <h2>Kết quả tìm kiếm cho: <?php echo htmlspecialchars($q); ?></h2>

        <?php if ($q === ''): ?>
                <p>Vui lòng nhập từ khóa để tìm kiếm.</p>
            <?php else: ?>
                <?php if (empty($posts) && empty($users)): ?>
                    <p>Không có kết quả.</p>
                <?php else: ?>
                    <?php if (!empty($posts)): ?>
                        <h3>Kết quả bài viết</h3>
                        <?php foreach ($posts as $p): ?>
                            <div class="result-item">
                                <h3><?php echo htmlspecialchars($p['title']); ?></h3>
                                <div class="meta">Bởi <?php echo htmlspecialchars($p['username']); ?> • Chủ đề: <?php echo htmlspecialchars($p['topic_name']); ?> • <strong><?php echo (int)$p['likes_count']; ?></strong> likes • <strong><?php echo (int)$p['comments_count']; ?></strong> comments</div>
                                <p><?php echo nl2br(htmlspecialchars(mb_substr($p['content'], 0, 300))); ?>...</p>
                                <p><a href="#" class="open-post" data-id="<?php echo (int)$p['idpost']; ?>">Xem bài viết</a></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if (!empty($users)): ?>
                        <h3>Kết quả người dùng</h3>
                        <?php foreach ($users as $u): ?>
                            <div class="result-item">
                                <strong><?php echo htmlspecialchars($u['username']); ?></strong> — <?php echo htmlspecialchars($u['name']); ?>
                                <div class="meta">Bài viết: <?php echo (int)$u['posts_count']; ?> • <a href="profile.php?id=<?php echo (int)$u['iduser']; ?>">Xem trang cá nhân</a></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
    </div>

    <script src="src/js/login.js"></script>
    <script src="src/js/main.js"></script>
    <script>
        // Modal for showing a post fetched via AJAX
        function showPostModal(post) {
            let modal = document.getElementById('search-post-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'search-post-modal';
                Object.assign(modal.style, {position:'fixed',left:0,top:0,right:0,bottom:0,background:'rgba(0,0,0,0.5)',display:'flex',alignItems:'center',justifyContent:'center',zIndex:2000});
                modal.innerHTML = '<div id="sp-wrap" style="width:90%;max-width:800px;background:#fff;border-radius:8px;padding:16px;max-height:90vh;overflow:auto;"></div>';
                document.body.appendChild(modal);
            }
            const wrap = document.getElementById('sp-wrap');
            const imagesHtml = (post.images || []).map(i=>'<div style="margin-top:8px"><img src="'+i+'" style="max-width:100%;border-radius:6px"/></div>').join('');
            wrap.innerHTML = `<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;"><h3 style="margin:0">${escapeHtml(post.title||'Bài viết')}</h3><button id="sp-close" style="border:0;background:transparent;font-size:20px;cursor:pointer">✕</button></div>`+
                `<div style="color:#374151;margin-bottom:12px">Bởi <strong>${escapeHtml(post.username||'')}</strong> • ${escapeHtml(post.topic_name||'')}</div>`+
                `<div style="white-space:pre-wrap;color:#111">${escapeHtml(post.content||'')}</div>`+imagesHtml+
                `<div id="sp-comments" style="margin-top:12px"></div>`;
            document.getElementById('sp-close').addEventListener('click', ()=> modal.style.display='none');

            // load comments
            const cmWrap = document.getElementById('sp-comments');
            cmWrap.innerHTML = '<p class="muted">Đang tải bình luận…</p>';
            fetch('controllers/comment.controller.php?action=list_json&post_id=' + encodeURIComponent(post.idpost || post.id), {credentials:'same-origin'})
                .then(r=>r.text())
                .then(text=>{ try { return JSON.parse(text||'[]'); } catch(e){console.error('Invalid JSON',text); return []; } })
                .then(arr=>{
                    if (!arr || arr.length===0) cmWrap.innerHTML='<p class="muted">Chưa có bình luận.</p>';
                    else cmWrap.innerHTML = arr.map(c=>`<div style="padding:8px;border-bottom:1px solid #eee"><strong>${escapeHtml(c.username)}</strong> <span style="color:#6b7280;font-size:12px">${escapeHtml(c.created_at)}</span><div style="margin-top:6px">${escapeHtml(c.content)}</div></div>`).join('');
                }).catch(err=>{ console.error(err); cmWrap.innerHTML='<p class="muted">Không thể tải bình luận.</p>' });
            modal.style.display = 'flex';
        }

        function escapeHtml(s){ return String(s||'').replace(/[&<>"]/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]; }); }

        document.addEventListener('click', function(e){
            const a = e.target.closest && e.target.closest('a.open-post');
            if (!a) return;
            e.preventDefault();
            const id = a.dataset.id;
            if (!id) return;
            fetch('controllers/post.controller.php?action=ajax_get&id=' + encodeURIComponent(id), {credentials:'same-origin'})
                .then(r=>r.json())
                .then(j=>{ if (j && j.ok) showPostModal(Object.assign(j.post,{images:j.images||[] })); else alert('Không tìm thấy bài viết'); })
                .catch(err=>{ console.error(err); alert('Lỗi tải bài viết'); });
        });
    </script>
</body>

</html>