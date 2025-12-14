<?php
require_once __DIR__ . '/config/session.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dun - Trang chủ</title>
    <link rel="shortcut icon" href="src/img/logodun.png" type="image/x-icon">
    <link rel="stylesheet" href="src/css/style.css">
    <link rel="stylesheet" href="src/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="/DunWeb/src/js/posts.js"></script>
</head>

<body>
    <!-- header -->
    <?php include 'views/topbar.php'; ?>
    <!-- main -->
    <main class="container">
        <section class="left-col">

            <div class="tabs">

                <button class="tab active" data-tab="following">Following</button>

                <button class="tab" data-tab="recommended">Recommended</button>

            </div>

            <div id="feed-container">
                <!-- Posts loaded here -->
            </div>
        </section>

        <aside class="right-col">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="card login-callout">
                    <h4>Log in</h4>
                    <p>Logging in only takes a few seconds. This will unlock check-in rewards and game tools all at once!
                    </p>
                    <button class="btn primary btn-login">Log In</button>
                </div>
            <?php endif; ?>

            <div class="card quick-post">
                <h4>Post now</h4>
                <div class="icons-row">
                    <div id="btn-post-icon" class="circle">✏️<span>Post</span></div>
                </div>
            </div>
        </aside>
    </main>
    <div class="post-main">
        <span id="exit">x</span>
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="post-container">
                <div>
                    <h3>Đăng bài viết</h3>
                </div>
                <div>
                    <form action="controllers/post.controller.php?action=create" method="post" enctype="multipart/form-data" autocomplete="auto">
                        <div style="text-align: right;">
                            <select name="topic" id="topic">
                                <option value="1">Đại học</option>
                                <option value="2">Toán học</option>
                                <option value="3">Vật lý</option>
                                <option value="4">Hóa học</option>
                                <option value="5">Ngữ văn</option>
                                <option value="6">Lịch sử</option>
                                <option value="7">Tiếng Anh</option>
                                <option value="8">Tin học</option>
                                <option value="9">Kỹ năng mềm</option>
                            </select>
                        </div>
                        <div>
                            <input type="text" name="title" placeholder="Tiêu đề">
                        </div>
                        <div>
                            <textarea name="content" rows="4" cols="50" placeholder="Hãy chia sẻ suy nghĩ của bạn"></textarea>
                        </div>
                        <div>
                            <input type="file" name="image_url[]" id="image_url" accept="image/*" multiple>
                        </div>
                        <div class="img-post" id="imgPreview">
                        </div>
                        <div style="text-align: right;">
                            <button class="submit" type="submit">Đăng</button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center;">
                <h3>Bạn chưa đăng nhập</h3>
                <button class="login-popup btn-login">Log In</button>
            </div>
        <?php endif ?>
    </div>
    <script>
        const postClose = document.getElementById("exit");
        const postMain = document.querySelector('.post-main');
        const postOpen = document.getElementById("btn-post");
        const postOpen1 = document.getElementById("btn-post-icon");
        const imageInput = document.getElementById('image_url');
        const previewContainer = document.getElementById('imgPreview');

        postOpen1.style.cursor = 'pointer';
        // Đóng/mở form
        postClose.addEventListener('click', () => {
            postMain.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
        postOpen.addEventListener('click', () => {
            postMain.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });
        postOpen1.addEventListener('click', () => {
            postMain.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        // Image preview for selected files with limits and remove support
        (function() {
            if (!imageInput) return;
            const maxFiles = 5;
            const maxSize = 2 * 1024 * 1024; // 2MB per file
            let selectedFiles = [];

            function renderPreviews() {
                previewContainer.innerHTML = '';
                selectedFiles.forEach((file, idx) => {
                    if (!file.type.startsWith('image/')) return;
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        const wrapper = document.createElement('div');
                        wrapper.style.position = 'relative';
                        wrapper.style.display = 'inline-block';
                        wrapper.style.margin = '6px';

                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.alt = file.name;
                        img.style.width = '160px';
                        img.style.height = 'auto';
                        img.style.display = 'block';
                        img.style.borderRadius = '8px';

                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.textContent = '×';
                        btn.title = 'Remove';
                        btn.style.position = 'absolute';
                        btn.style.top = '2px';
                        btn.style.right = '2px';
                        btn.style.background = 'rgba(0,0,0,0.6)';
                        btn.style.color = '#fff';
                        btn.style.border = 'none';
                        btn.style.borderRadius = '50%';
                        btn.style.width = '24px';
                        btn.style.height = '24px';
                        btn.style.cursor = 'pointer';

                        btn.addEventListener('click', () => {
                            selectedFiles.splice(idx, 1);
                            syncInputFiles();
                            renderPreviews();
                        });

                        wrapper.appendChild(img);
                        wrapper.appendChild(btn);
                        previewContainer.appendChild(wrapper);
                    };
                    reader.readAsDataURL(file);
                });
            }

            function syncInputFiles() {
                const dt = new DataTransfer();
                selectedFiles.forEach(f => dt.items.add(f));
                imageInput.files = dt.files;
            }

            imageInput.addEventListener('change', () => {
                const files = Array.from(imageInput.files || []);
                // append respecting limits
                for (const f of files) {
                    if (selectedFiles.length >= maxFiles) break;
                    if (!f.type.startsWith('image/')) continue;
                    if (f.size > maxSize) {
                        alert(`File "${f.name}" is larger than 2MB and will be skipped.`);
                        continue;
                    }
                    selectedFiles.push(f);
                }
                if (selectedFiles.length > maxFiles) selectedFiles = selectedFiles.slice(0, maxFiles);
                syncInputFiles();
                renderPreviews();
            });
        })();
    </script>

    <!-- Tab switching logic -->
    <script>
        // Set global user ID for post events
        window.CURRENT_USER_ID = <?php echo (int)($_SESSION['user_id'] ?? 0); ?>;
        
        const feedContainer = document.getElementById('feed-container');
        const tabButtons = document.querySelectorAll('.tabs .tab');

        async function loadFeed(tabType) {
            try {
                feedContainer.innerHTML = '<p style="text-align: center; color: #6b7280;">Đang tải...</p>';
                
                const response = await fetch('/DunWeb/api/feed.php?tab=' + encodeURIComponent(tabType), {
                    credentials: 'same-origin'
                });

                if (!response.ok) throw new Error('Feed load failed');

                const html = await response.text();
                feedContainer.innerHTML = html;

                // Initialize post event listeners after content is loaded
                if (window.initPostEventListeners) {
                    window.initPostEventListeners();
                }

            } catch (err) {
                console.error('Feed load error:', err);
                feedContainer.innerHTML = '<p style="text-align: center; color: #ef4444;">Không thể tải bài viết. Vui lòng thử lại.</p>';
            }
        }

        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabType = btn.dataset.tab;
                
                // Update active state
                tabButtons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                // Load feed
                loadFeed(tabType);
            });
        });

        // Load initial feed (Following if logged in, else Recommended)
        window.addEventListener('DOMContentLoaded', () => {
            const initialTab = CURRENT_USER_ID ? 'following' : 'recommended';
            loadFeed(initialTab);
        });
    </script>
    <script src="src/js/login.js"></script>
    <script src="src/js/main.js"></script>
</body>

</html>