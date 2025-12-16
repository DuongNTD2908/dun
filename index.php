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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
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

    <!-- Comment Modal -->
    <div id="comment-modal" class="comment-modal">
        <div class="comment-dialog">
            <div class="comment-header">
                <h3>Bình luận</h3>
                <button class="close-comment" id="close-comment">&times;</button>
            </div>
            <div class="comment-body" id="comment-body">
                <!-- Comments will be loaded here -->
                <div class="empty-state">
                    <p>Chưa có bình luận nào. Hãy là người đầu tiên!</p>
                </div>
            </div>
            <div class="comment-footer">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <img src="<?php echo !empty($_SESSION['avt']) ? htmlspecialchars($_SESSION['avt']) : 'src/img/user.png'; ?>" class="my-avt">
                    <div class="input-group">
                        <input type="text" id="comment-input" placeholder="Viết bình luận...">
                        <button id="send-comment"><i class="fa fa-paper-plane"></i></button>
                    </div>
                <?php else: ?>
                    <p style="width:100%;text-align:center;"><a href="#" class="btn-login" style="color:#0078d4;text-decoration:none;font-weight:bold;">Đăng nhập</a> để bình luận</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Welcome Popup -->
    <div id="welcome-modal" class="welcome-modal">
        <div class="welcome-content">
            <img src="src/img/welcome.png" alt="Welcome" class="welcome-img">
            <div class="welcome-actions">
                <img src="src/img/fast-forward.png" id="welcome-next" alt="Next" title="Bắt đầu khám phá">
            </div>
        </div>
        <img src="src/img/noel-tree.png" alt="Noel Tree" class="welcome-noel-tree">
        <img src="src/img/noelbox1.png" alt="Noel Box" class="welcome-box1">
        <img src="src/img/noelbox2.png" alt="Noel Box" class="welcome-box2">
        <img src="src/img/jingle.png" alt="Jingle" class="welcome-jingle">
    </div>
    <style>
        .welcome-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 108, 202, 1);
            /* Nền màu nhẹ (AliceBlue) */
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.5s ease, visibility 0.5s;
        }

        .welcome-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .welcome-content {
            display: flex;
            position: relative;
            z-index: 10;
            flex-direction: column;
            align-items: center;
            transform: scale(0.8);
            opacity: 0.6;
            /* Yêu cầu: 0.6 đến 1 */
            transition: transform 0.8s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.8s ease;
        }

        .welcome-modal.active .welcome-content {
            transform: scale(1);
            opacity: 1;
        }

        .welcome-modal.closing {
            opacity: 0;
        }

        .welcome-modal.closing .welcome-content {
            transform: scale(1.2);
            opacity: 0;
            transition: all 0.4s ease-in;
        }

        .welcome-img {
            max-width: 90%;
            max-height: 50vh;
            margin-bottom: 30px;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.1));
        }

        .welcome-noel-tree {
            position: absolute;
            width: 25%;
            bottom: 0;
            right: 0;
        }

        .welcome-jinglebell {
            position: absolute;
            width: 10%;
            top: 40px;
            z-index: 1;
        }

        .welcome-jingle {
            position: absolute;
            width: 80%;
            top: 0;
        }

        .welcome-box1{
            position: absolute;
            bottom: 0;
            right: 20%;
            width: 10%;
        }
        .welcome-box2{
            position: absolute;
            bottom: 0;
            left: 20%;
            width: 15%;
        }

        #welcome-next {
            width: 60px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        #welcome-next:hover {
            transform: translateX(10px);
        }

        .snowflake {
            position: absolute;
            top: -40px;
            user-select: none;
            pointer-events: none;
            animation: fall linear infinite;
            z-index: 10000;
        }

        @keyframes fall {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 1;
            }

            100% {
                transform: translateY(110vh) translateX(20px);
                opacity: 0.3;
            }
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Kiểm tra session để chỉ hiện 1 lần mỗi phiên
            if (!sessionStorage.getItem("welcome_seen")) {
                const modal = document.getElementById("welcome-modal");
                const nextBtn = document.getElementById("welcome-next");

                // Tạo hiệu ứng tuyết rơi (dấu chấm xanh/trắng)
                const colors = ['#fff', '#87CEEB', '#00BFFF'];
                for (let i = 0; i < 60; i++) {
                    const flake = document.createElement('div');
                    flake.classList.add('snowflake');
                    flake.textContent = '.';
                    flake.style.left = Math.random() * 100 + 'vw';
                    flake.style.animationDuration = (Math.random() * 3 + 2) + 's';
                    flake.style.animationDelay = Math.random() * 2 + 's';
                    flake.style.fontSize = (Math.random() * 30 + 20) + 'px';
                    flake.style.color = colors[Math.floor(Math.random() * colors.length)];
                    flake.style.opacity = Math.random();
                    modal.appendChild(flake);
                }

                // Hiệu ứng vào
                setTimeout(() => {
                    modal.classList.add("active");
                }, 100);

                // Hiệu ứng thoát
                nextBtn.addEventListener("click", () => {
                    modal.classList.remove("active");
                    modal.classList.add("closing");
                    setTimeout(() => {
                        modal.style.display = "none";
                        sessionStorage.setItem("welcome_seen", "true");
                    }, 400);
                });
            } else {
                document.getElementById("welcome-modal").style.display = "none";
            }
        });
    </script>

    <script>
        // Comment Modal Logic
        (function() {
            const modal = document.getElementById('comment-modal');
            const closeBtn = document.getElementById('close-comment');
            const body = document.body;

            // Event delegation for dynamically loaded posts
            document.addEventListener('click', function(e) {
                // Check if clicked element is comment button or inside it
                const btn = e.target.closest('.comment-btn');
                if (btn) {
                    const postId = btn.dataset.postId;
                    // Open modal
                    modal.classList.add('active');
                    body.style.overflow = 'hidden'; // Prevent background scroll
                    // Here you would fetch comments for postId via AJAX
                    console.log('Open comments for post:', postId);
                }

                // Close modal
                if (e.target === modal || e.target === closeBtn) {
                    modal.classList.remove('active');
                    body.style.overflow = '';
                }
            });
        })();
    </script>

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

                const response = await fetch('controllers/post.controller.php?action=feed&tab=' + encodeURIComponent(tabType), {
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