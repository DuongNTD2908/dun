<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<header class="topbar">
    <div class="brand">DUN</div>
    <div class="nav">
        <a href="index.php">Trang chủ</a>
        <!-- <a href="#">Bạn bè</a> -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="message.php">Nhắn tin</a>
        <?php else: ?>
            <a onclick="alert('Hãy đăng nhập trước')" style="cursor: pointer;" class="btn-login">Nhắn tin</a>
        <?php endif ?>
    </div>
    <div class="search">
        <form id="search-form" action="search.php" method="get">
            <select name="topic_select" id="topic_select">
                <option value="all">Tất cả</option>
                <option value="post">Bài viết</option>
                <option value="user">Người dùng</option>
            </select>
            <input name="q" id="q" placeholder="Tìm kiếm" />
            <button type="submit"><i class="fa fa-search"></i></button>
        </form>
    </div>
    <nav class="actions">
        <div id="post" class="icon-nav">
            <img src="src/img/post.png" alt="" width="100%">
        </div>
        <div id="notif" class="icon-nav">
            <img src="src/img/bell.png" alt="" width="100%">
        </div>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <button class="btn ghost btn-login">Đăng nhập</button>
        <?php else: ?>
            <?php if (isset($_SESSION['avt'])): ?>
                <div id="profile-this-user" class="icon-nav">
                    <img style="border-radius: 50%; border: 2px solid pink;" src="<?php echo htmlspecialchars($_SESSION['avt']) ?>" alt="Vào profile" width="100%">
                </div>
            <?php else: ?>
                <div id="profile-this-user" class="icon-nav">
                    <img src="src/img/user.png" alt="Vào profile" width="100%">
                </div>
            <?php endif; ?>
            <p><?php
                $uname = isset($_SESSION['username']) ? $_SESSION['username'] : '';
                if (function_exists('mb_strlen')) {
                    $display = (mb_strlen($uname, 'UTF-8') > 8) ? mb_substr($uname, 0, 8, 'UTF-8') . '...' : $uname;
                } else {
                    // fallback if mbstring is not available (may break multibyte chars)
                    $display = (strlen($uname) > 8) ? substr($uname, 0, 8) . '...' : $uname;
                }
                echo htmlspecialchars($display, ENT_QUOTES, 'UTF-8');
            ?></p>
            <div id="logout" class="icon-nav">
                <img src="src/img/logout.png" alt="" width="100%">
            </div>
        <?php endif ?>
    </nav>
</header>
<div id="notification-nav" style="display:none;">
    <div>
        <h3 style="text-align: center;">Thông báo</h3>
    </div>
    <div id="notification-list" style="max-height:300px; overflow-y:auto; padding:10px;">
        <!-- Thông báo sẽ được load ở đây -->
    </div>

</div>
<script>
    function loadNotifications() {
        const notificationList = document.getElementById('notification-list');
        console.log('[topbar] loadNotifications called');
        // correct controller path (controllers, not controller) and send credentials
        fetch('controllers/notification.controller.php?action=list', {
                credentials: 'same-origin'
            })
            .then(async res => {
                const text = await res.text();
                // try to parse JSON, otherwise show raw response for debugging
                try {
                    return JSON.parse(text === '' ? '{}' : text);
                } catch (e) {
                    console.error('Invalid JSON from notification controller:', text);
                    notificationList.innerHTML = '<pre style="white-space:pre-wrap;max-height:260px;overflow:auto;color:#900">' + escapeHtml(text) + '</pre>';
                    throw new Error('Invalid JSON');
                }
            })
            .then(data => {
                notificationList.innerHTML = '';
                if (data && data.ok && Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(noti => {
                        const p = document.createElement('p');
                        const text = (noti.content || '').toString();
                        p.textContent = text + (noti.created_at ? (' (' + noti.created_at + ')') : '');
                        notificationList.appendChild(p);
                    });
                } else if (data && data.ok && Array.isArray(data.data) && data.data.length === 0) {
                    notificationList.innerHTML = '<p>Không có thông báo nào</p>';
                } else if (data && data.ok === false && data.msg) {
                    notificationList.innerHTML = '<p>' + (data.msg || 'Không thể tải thông báo') + '</p>';
                } else {
                    notificationList.innerHTML = '<p>Không có thông báo nào</p>';
                }
            })
            .catch(err => {
                console.error(err);
                // if we already wrote raw response, don't overwrite; otherwise show generic error
                if (!notificationList.innerHTML) notificationList.innerHTML = '<p>Lỗi tải thông báo</p>';
            });
    }

    // Toggle notification panel when bell icon is clicked and load notifications lazily
    (function() {
        const notifBtn = document.getElementById('notif');
        const notificationNav = document.getElementById('notification-nav');
        if (!notifBtn || !notificationNav) return;

        notifBtn.addEventListener('mouseenter', function(e) {
            // toggle visibility
            if (notificationNav.style.display === 'block') {
                notificationNav.style.display = 'none';
            } else {
                notificationNav.style.display = 'block';
                loadNotifications();
            }
        });
    })();

    const logout = document.getElementById('logout');
    const profileUser = document.getElementById('profile-this-user');
    if (logout) logout.addEventListener('click', () => {
        window.location.href = 'controllers/user.controller.php?action=logout';
    });
    if (profileUser) profileUser.addEventListener('click', () => {
        window.location.href = 'profile.php';
    });
    // prevent search for anonymous users: open login modal instead
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    (function() {
        const searchFormEl = document.getElementById('search-form');
        if (!searchFormEl) return;
        searchFormEl.addEventListener('submit', function(e) {
            if (!isLoggedIn) {
                e.preventDefault();
                const loginBtn = document.querySelector('.btn-login');
                if (loginBtn) {
                    // many UI attach click to this button; trigger it
                    loginBtn.click();
                } else {
                    alert('Vui lòng đăng nhập để tìm kiếm');
                }
            }
        });
    })();
</script>
<style>
    /* simple dropdown for recent searches */
    .search-dropdown {
        position: absolute;
        background: #fff;
        border: 1px solid #ddd;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .08);
        border-radius: 6px;
        z-index: 1200;
        min-width: 220px;
        max-width: 380px;
    }

    .search-dropdown ul {
        list-style: none;
        margin: 0;
        padding: 8px 0;
    }

    .search-dropdown li {
        padding: 8px 12px;
        cursor: pointer;
        font-size: 14px;
        color: #222
    }

    .search-dropdown li:hover {
        background: #f5f7fa
    }

    .search-dropdown .meta {
        display: block;
        font-size: 12px;
        color: #888
    }
</style>
<script src="src/js/search.js"></script>
<div class="post-controller">
    <div id="btn-post" class="btn-primary">
        <img src="src/img/post.png" alt="" width="20%">
        <span>Post</span>
    </div>
        <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <div id="btn-admin-panel" class="btn-primary" title="Admin">
                <a href="admin_manager.php" style="display:flex;align-items:center;gap:8px;color:inherit;text-decoration:none;">
                    <img src="src/img/user.png" alt="" width="20%">
                    <span>Admin</span>
                </a>
            </div>
        <?php endif; ?>
</div>
<div id="login-modal" aria-hidden="true">
    <div class="overlay" data-action="close"></div>
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="login-title">
        <button class="close-btn" data-action="close" aria-label="Đóng">×</button>

        <form id="login-form" action="controllers/user.controller.php?action=login" method="post" autocomplete="on">
            <h3 id="login-title">Đăng nhập</h3>
            <div class="input-form">
                <label for="login-user">Email / Tên người dùng</label>
                <input id="login-user" name="username" type="text" required />
            </div>

            <div class="input-form">
                <label for="login-pass">Mật khẩu</label>
                <input id="login-pass" name="password" type="password" required />
            </div>


            <div class="row">
                <a href="controllers/user.controller.php?action=forgot" style="font-size:13px; color:#0078d4; text-decoration:none;">Quên mật khẩu?</a>
                <a id="newUser" href="#" style="font-size:13px; color:#0078d4; text-decoration:none;">Bạn chưa có
                    tài khoản?</a>
            </div>

            <div class="controls">
                <button type="submit" class="btn primary">Đăng nhập</button>
            </div>
        </form>
        <form id="register-form" action="controllers/user.controller.php?action=register" method="post" autocomplete="on">
            <h3 id="login-title">Đăng ký</h3>
            <div class="input-form">
                <label for="register-user">Tên người dùng</label>
                <input id="register-user" name="username" type="text" required />
            </div>
            <div class="input-form">
                <label for="register-email">Email</label>
                <input id="register-email" name="email" type="email" required />
            </div>
            <div class="input-form">
                <label for="register-pass">Mật khẩu</label>
                <input id="register-pass" name="password" type="password" required />
            </div>
            <div class="input-form">
                <label for="login-confirmpass">Xác nhận mật khẩu</label>
                <input id="login-confirmpass" name="confirm-password" type="password" required />
            </div>
            <div class="row">
                <a id="haveUser" href="#" style="font-size:13px; color:#0078d4; text-decoration:none;">Bạn đã có tài
                    khoản?</a>
            </div>
            <div class="controls">
                <button type="submit" class="btn primary">Đăng ký</button>
            </div>
        </form>
    </div>
</div>
<?php if (!empty($_SESSION['require_profile']) && isset($_SESSION['user_id'])): ?>
    <!-- Sau khi đăng ký, hiển thị điền thông tin cần thiết -->
    <div id="profile-modal" aria-hidden="false">
        <div class="overlay"></div>
        <div class="dialog" role="dialog" aria-modal="true">
            <div id="profile-step-1">
                <h3>Hoàn thiện hồ sơ</h3>
                <p>Vui lòng nhập họ tên, giới tính và ngày sinh để tiếp tục.</p>
                <div class="input-form">
                    <label for="pf-name">Họ và tên</label>
                    <input id="pf-name" name="name" type="text" />
                </div>
                <div class="input-form">
                    <label for="pf-gender">Giới tính</label>
                    <select id="pf-gender" name="gender">
                        <option value="">Chọn</option>
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div class="input-form">
                    <label for="pf-dob">Ngày sinh</label>
                    <input id="pf-dob" name="dob" type="date" />
                </div>
                <div style="text-align:right;margin-top:10px;">
                    <button id="pf-next" class="btn primary" disabled>Next</button>
                </div>
            </div>
            <div id="profile-step-2" style="display:none;text-align:center;">
                <h3>Chào mừng bạn <?php echo htmlspecialchars($_SESSION['name']); ?>!</h3>
                <p>Hãy bắt đầu nào 🎉</p>
                <div style="margin-top:12px;">
                    <button id="pf-finish" class="btn primary">Bắt đầu</button>
                </div>
            </div>
        </div>
    </div>
    <style>
        #profile-modal {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        #profile-modal .overlay {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: #00000099;
        }

        #profile-modal .dialog {
            position: relative;
            background: #fff;
            padding: 24px;
            border-radius: 12px;
            width: 400px;
            box-shadow: 0 6px 30px #00000033;
        }

        #profile-modal .input-form {
            margin-bottom: 10px;
            text-align: left;
        }

        #profile-modal label {
            display: block;
            font-size: 13px;
            margin-bottom: 6px;
        }

        #profile-modal input,
        #profile-modal select {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ddd;
        }
    </style>
    <script>
        (function() {
            const nameEl = document.getElementById('pf-name');
            const genderEl = document.getElementById('pf-gender');
            const dobEl = document.getElementById('pf-dob');
            const nextBtn = document.getElementById('pf-next');
            const step1 = document.getElementById('profile-step-1');
            const step2 = document.getElementById('profile-step-2');
            const finishBtn = document.getElementById('pf-finish');

            function validate() {
                const ok = nameEl.value.trim() !== '' && genderEl.value !== '' && dobEl.value !== '';
                nextBtn.disabled = !ok;
            }

            nameEl.addEventListener('input', validate);
            genderEl.addEventListener('change', validate);
            dobEl.addEventListener('change', validate);

            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                step1.style.display = 'none';
                step2.style.display = 'block';
            });

            finishBtn.addEventListener('click', async () => {
                finishBtn.disabled = true;
                const data = new URLSearchParams();
                data.append('name', nameEl.value.trim());
                data.append('gender', genderEl.value);
                data.append('dob', dobEl.value);

                const res = await fetch('controllers/user.controller.php?action=complete_profile', {
                    method: 'POST',
                    body: data,
                });
                const j = await res.json();
                if (j.ok) {
                    // remove modal
                    const modal = document.getElementById('profile-modal');
                    if (modal) modal.remove();
                    // reload to reflect new session name
                    window.location.reload();
                } else {
                    alert(j.msg || 'Lỗi');
                    finishBtn.disabled = false;
                }
            });

            // Prevent closing: intercept clicks on overlay
            document.querySelector('#profile-modal .overlay').addEventListener('click', (e) => {
                e.stopPropagation();
            });
            // Prevent ESC key
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') e.preventDefault();
            });
        })();
    </script>
<?php endif; ?>
<script src="src/js/google.js"></script>