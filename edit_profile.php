<?php
require_once __DIR__ . '/config/session.php';
require_once 'models/user.model.php';
require_once 'config/database.php'; // file kết nối DB

$userModel = new UserModel($mysqli);
$userId = $_SESSION['user_id'];

// Lấy thông tin user hiện tại
$stmt = $mysqli->prepare("SELECT * FROM users WHERE iduser = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa trang cá nhân</title>
    <link rel="stylesheet" href="src/css/edit.css">
    <link rel="shortcut icon" href="src/img/logodun.png" type="image/x-icon">
</head>

<body>
    <div class="form-container">
        <h2>Chỉnh sửa trang cá nhân</h2>
        <form action="controllers/user.controller.php?action=update" method="POST" id="editForm">
            <input type="hidden" name="id" value="<?php echo $user['iduser']; ?>">

            <label for="name">Họ và tên</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

            <label for="phone">Số điện thoại</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">

            <label for="date">Ngày sinh</label>
            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($user['date']); ?>" required>

            <label for="gender">Giới tính</label>
            <select id="gender" name="gender" required>
                <option value="Nam" <?php if ($user['gender'] === 'Nam') echo 'selected'; ?>>Nam</option>
                <option value="Nữ" <?php if ($user['gender'] === 'Nữ') echo 'selected'; ?>>Nữ</option>
                <option value="Khác" <?php if ($user['gender'] === 'Khác') echo 'selected'; ?>>Khác</option>
            </select>

            <div class="button-group">
                <button type="button" class="btn-back" onclick="window.history.back()"><img src="src/img/back.png" alt="Back"> Quay lại</button>
                <button type="submit">💾 Lưu thay đổi</button>
            </div>
        </form>
    </div>
</body>

<script>
    // Handle form submission and redirect on success
    document.getElementById('editForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(document.getElementById('editForm'));
        
        try {
            const res = await fetch('controllers/user.controller.php?action=update', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            
            const text = await res.text();
            let data = {};
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Response:', text);
                alert('Cập nhật thành công!');
                // Redirect to profile on any non-JSON response (server might have redirected)
                window.location.href = 'profile.php';
                return;
            }
            
            if (data.ok || res.ok) {
                alert('Cập nhật thành công!');
                // Redirect to user's profile
                window.location.href = 'profile.php';
            } else {
                alert(data.msg || 'Lỗi: Không thể cập nhật thông tin');
            }
        } catch (err) {
            console.error('Update error:', err);
            alert('Lỗi mạng. Vui lòng thử lại.');
        }
    });
</script>

</html>