<?php
// Forgot password form view
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #e3f2fd, #ffffff);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .form-container {
            width: 420px;
            background: #fff;
            padding: 40px 35px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .form-container:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 12px;
            color: #0078d7;
            font-size: 1.6rem;
        }

        .form-container p {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-top: 16px;
            font-weight: 500;
            color: #333;
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccd6e0;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #0078d7;
            box-shadow: 0 0 0 3px rgba(0, 120, 215, 0.15);
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 28px;
        }

        .button-group button,
        .button-group .btn-back {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .button-group button {
            background: #0078d7;
            color: #fff;
        }

        .button-group button:hover {
            background: #005fa3;
            transform: translateY(-2px);
        }

        .button-group .btn-back {
            background: #f0f0f0;
            color: #333;
            border: 1px solid #d0d0d0;
        }

        .button-group .btn-back:hover {
            background: #e8e8e8;
            transform: translateY(-2px);
        }

        .btn-back img {
            width: 18px;
            height: 18px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Quên mật khẩu</h2>
        <p>Nhập email của bạn để nhận đường dẫn đặt lại mật khẩu.</p>
        <form action="/DunWeb/controllers/user.controller.php?action=send_reset" method="post">
            <label for="fp-email">Email</label>
            <input id="fp-email" name="email" type="email" required placeholder="Nhập email của bạn" />

            <div class="button-group">
                <button type="button" class="btn-back" onclick="window.history.back()"><img src="../src/img/back.png" alt="Back"> Quay lại</button>
                <button type="submit">Gửi liên kết</button>
            </div>
        </form>
    </div>
</body>
</html>
