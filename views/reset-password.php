<?php
// Reset password form view supports two modes:
// - token-based: use ?token=... (user clicked email link)
// - PIN-based: user submits email + PIN received by email
$token = $_GET['token'] ?? ($_POST['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Đặt lại mật khẩu</title>
    <link rel="stylesheet" href="/src/css/style.css">
    <style>
        body { background: linear-gradient(135deg,#eef7ff,#ffffff); font-family: 'Segoe UI', Roboto, sans-serif; }
        .card { max-width:480px; margin:48px auto; background:#fff; padding:28px; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.08); }
        h2 { color:#004a8f; margin-bottom:8px; }
        p.lead { color:#586069; margin-bottom:18px; }
        label { display:block; margin-top:12px; margin-bottom:6px; color:#333; font-weight:600 }
        input { width:100%; padding:10px 12px; border:1px solid #d6e4f0; border-radius:8px; }
        .controls { display:flex; gap:10px; margin-top:18px }
        .btn { padding:10px 14px; border-radius:8px; border:none; cursor:pointer }
        .btn.primary { background:#0078d7; color:#fff; flex:1 }
        .btn.secondary { background:#f0f2f5; color:#333; }
        .small { font-size:13px; color:#666; margin-top:8px }
    </style>
</head>
<body>
    <div class="card">
        <h2>Đặt lại mật khẩu</h2>
        <?php if (!empty($token)): ?>
            <p class="lead">Chúng tôi đã nhận yêu cầu đặt lại mật khẩu. Vui lòng nhập mật khẩu mới.</p>
            <form action="/DunWeb/controllers/user.controller.php?action=do_reset" method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token) ?>">
                <label for="new-pass">Mật khẩu mới</label>
                <input id="new-pass" name="password" type="password" required placeholder="Ít nhất 8 ký tự, 1 chữ in hoa, 1 ký tự đặc biệt" />
                <label for="confirm-pass">Xác nhận mật khẩu</label>
                <input id="confirm-pass" name="confirm-password" type="password" required />
                <div class="controls">
                    <button type="submit" class="btn primary">Đặt lại mật khẩu</button>
                    <a href="index.php" class="btn secondary">Hủy</a>
                </div>
            </form>
        <?php else: ?>
            <p class="lead">Bạn có thể nhập email và mã PIN (đã gửi tới Gmail) để đặt lại mật khẩu.</p>
            <form action="/DunWeb/controllers/user.controller.php?action=do_reset" method="post">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" required placeholder="Email đã đăng ký" />
                <label for="pin">Mã PIN</label>
                <input id="pin" name="pin" type="text" required placeholder="6 chữ số" />
                <label for="new-pass">Mật khẩu mới</label>
                <input id="new-pass" name="password" type="password" required placeholder="Mật khẩu mới" />
                <label for="confirm-pass">Xác nhận mật khẩu</label>
                <input id="confirm-pass" name="confirm-password" type="password" required />
                <div class="controls">
                    <button type="submit" class="btn primary">Đặt lại mật khẩu</button>
                    <a href="index.php" class="btn secondary">Hủy</a>
                </div>
                <p class="small">Nếu bạn chưa nhận được mã PIN, kiểm tra mục Spam hoặc thử gửi lại yêu cầu.</p>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
