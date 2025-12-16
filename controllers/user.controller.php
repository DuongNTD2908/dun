<?php
// Central session bootstrap (sets cookie path and starts session)
require_once __DIR__ . '/../config/session.php';
require_once "../config/database.php";
require_once "../models/user.model.php";
require_once __DIR__ . '/../config/google_config.php';
// PHPMailer and mail config
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/mail_config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Small application log helper (writes to logs/app.log). Safe if no permission.
function write_app_log($msg) {
    $dir = __DIR__ . '/../logs';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/app.log';
    $entry = date('c') . ' | ' . $msg . PHP_EOL;
    @file_put_contents($file, $entry, FILE_APPEND);
}

// Determine canonical site origin (scheme + host)
$siteOrigin = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

// Only allow CORS with credentials when the request origin matches the site origin.
if ($origin && $origin === $siteOrigin) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Return 200 with proper headers
    http_response_code(200);
    exit;
}

$userModel = new UserModel($mysqli);
$action = $_POST['action'] ?? $_GET['action'] ?? null;


switch ($action) {
    case 'google':
        // Accept ID token from client, verify with Google tokeninfo endpoint, then login/register user
        header('Content-Type: application/json; charset=utf-8');
        write_app_log("Google login attempt started from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        
        // Only accept POST for this endpoint
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            write_app_log('Google endpoint rejected non-POST request: ' . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            echo json_encode(['ok' => false, 'msg' => 'Method not allowed']);
            exit;
        }

        $idToken = $_POST['id_token'] ?? '';
        if (!$idToken) {
            write_app_log("Missing id_token");
            echo json_encode(['ok' => false, 'msg' => 'Missing id_token']);
            exit;
        }
        
        write_app_log("Token received, length: " . strlen($idToken));

        // Verify token with Google using cURL
        $tokenInfoUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);
        $ch = curl_init($tokenInfoUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $tokenInfoRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        error_log("[USER_CONTROLLER] Google token verify - HTTP code: $httpCode, cURL error: $curlError, Response length: " . strlen($tokenInfoRaw));
        
        if (!$tokenInfoRaw || $httpCode !== 200) {
            error_log("[USER_CONTROLLER] Token verify failed: HTTP $httpCode, Response: " . substr($tokenInfoRaw, 0, 200));
            echo json_encode(['ok' => false, 'msg' => 'Token verification failed (HTTP ' . $httpCode . ')', 'debug' => $curlError]);
            exit;
        }
        
        $payload = json_decode($tokenInfoRaw, true);
        if (!$payload) {
            error_log("[USER_CONTROLLER] Token JSON decode failed: " . $tokenInfoRaw);
            echo json_encode(['ok' => false, 'msg' => 'Invalid token response']);
            exit;
        }
        
        error_log("[USER_CONTROLLER] Token payload decoded successfully");
        
        // Check audience
        $expectedAud = GOOGLE_CLIENT_ID;
        $actualAud = $payload['aud'] ?? null;
        if (!$actualAud || $actualAud !== $expectedAud) {
            error_log("[USER_CONTROLLER] Token audience mismatch: expected $expectedAud, got $actualAud");
            echo json_encode(['ok' => false, 'msg' => 'Invalid token audience']);
            exit;
        }

        // token valid: extract user data
        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;
        if (!$email) {
            error_log("[USER_CONTROLLER] No email in token payload");
            echo json_encode(['ok' => false, 'msg' => 'No email in token']);
            exit;
        }
        
        error_log("[USER_CONTROLLER] Token verified for email: $email");

        // find existing user by email
        $user = $userModel->getUserByEmail($email);
        if ($user) {
            write_app_log("Existing user found, logging in: " . $email);
            // login existing user
            $_SESSION['user_id'] = $user['iduser'];
            // persist role for UI checks (admin button etc.)
            $_SESSION['role'] = $user['role'] ?? '';
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'] ?? ($name ?? '');
            $_SESSION['email'] = $user['email'];
            $_SESSION['avt'] = $user['avt'] ?? null;
            $_SESSION['isonline'] = 1;
            $userModel->setOnlineStatus($user['iduser'], 1);
            write_app_log("Google login successful for existing user: " . $email);
                // Compute absolute redirect URL back to app root
                $appBase = dirname(dirname($_SERVER['SCRIPT_NAME'])); // e.g. '/DunWeb'
                $redirectUrl = $siteOrigin . $appBase . '/';
                echo json_encode(['ok' => true, 'redirect' => $redirectUrl]);
            exit;
        }

        write_app_log("New user registration for: $email");
        
        // Register a new user using email (create a safe username)
        $base = preg_replace('/[^a-z0-9]/', '', strtolower(strtok($email, '@')));
        $usernameCandidate = $base ?: 'user' . substr(md5($email), 0, 6);
        $suffix = '';
        $try = 0;
        while ($userModel->isUserExist($usernameCandidate . $suffix)) {
            $try++;
            $suffix = $try;
            if ($try > 100) break;
        }
        $finalUsername = $usernameCandidate . $suffix;
        $randomPassword = bin2hex(random_bytes(8));
        
        write_app_log("Creating new user: $finalUsername for email: $email");
        
        $regOk = $userModel->register($finalUsername, $randomPassword, $email, 'customer');
        if (!$regOk) {
            write_app_log("Failed to register new user: " . $email);
            echo json_encode(['ok' => false, 'msg' => 'Failed to create user account']);
            exit;
        }
        
        $newUser = $userModel->getUserByUsername($finalUsername);
        if (!$newUser) {
            error_log("[USER_CONTROLLER] User registered but not found: " . $finalUsername);
            echo json_encode(['ok' => false, 'msg' => 'Registration succeeded but user not found']);
            exit;
        }
        
        write_app_log("New user created successfully: " . $finalUsername . " (ID: " . $newUser['iduser'] . ")");
        
        $_SESSION['user_id'] = $newUser['iduser'];
        // persist role for UI checks
        $_SESSION['role'] = $newUser['role'] ?? 'customer';
        $_SESSION['username'] = $newUser['username'];
        $_SESSION['name'] = $name ?? '';
        $_SESSION['email'] = $newUser['email'];
        $_SESSION['avt'] = null;
        $_SESSION['isonline'] = 1;
        $userModel->setOnlineStatus($newUser['iduser'], 1);
        write_app_log("Google registration successful for new user: " . $email . " (username: " . $finalUsername . ")");
            echo json_encode(['ok' => true, 'redirect' => $redirectUrl]);
        exit;

    case 'login':
        // Allow login by username OR email. Accept either `username` or `email` POST fields.
        $identifier = '';
        if (!empty($_POST['email'])) {
            $identifier = trim($_POST['email']);
        } elseif (!empty($_POST['username'])) {
            $identifier = trim($_POST['username']);
        }
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $user = $userModel->login($identifier, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['iduser'];
            $_SESSION['role'] = $user['role'] ?? '';
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['avt'] = $user['avt'];
            $_SESSION['isonline'] = 1;
            $userModel->setOnlineStatus($user['iduser'], 1);
            echo "<script>alert('Đăng nhập thành công'); window.location.href='../';</script>";
        } else {
            echo "<script>alert('Sai tên đăng nhập hoặc mật khẩu');window.history.back();</script>";
        }
        break;

    case 'logout':
        $user_id = $_SESSION['user_id'] ?? 0;
        $userModel->setOnlineStatus($user_id, 0);
        session_destroy();
        header("Location: ../");
        exit();

    case 'register':
        if (
            !isset($_POST['username']) || !isset($_POST['password']) || !isset($_POST['confirm-password']) || !isset($_POST['email']) ||
            empty(trim($_POST['username'])) || empty(trim($_POST['password'])) || empty(trim($_POST['email']))
        ) {
            echo "<script>alert('Vui lòng nhập đầy đủ thông tin!');window.history.back();</script>";
            break;
        }

        // Sanitize inputs
        $username = trim(strip_tags($_POST['username']));
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirm-password']);
        $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
        $role = 'customer'; // Mặc định vai trò là 'customer'

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Email không hợp lệ!');window.history.back();</script>";
            break;
        }

        if (
            strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[\W_]/', $password)
        ) {
            echo "<script>alert('Mật khẩu phải có ít nhất 8 ký tự, gồm 1 chữ in hoa và 1 ký tự đặc biệt!');window.history.back();</script>";
            break;
        }

        if ($password !== $confirmPassword) {
            echo "<script>alert('Mật khẩu xác nhận không khớp!');window.history.back();</script>";
            break;
        }

        try {
            if ($userModel->isUserExist($username) || $userModel->isUserExist($email)) {
                echo "<script>alert('Tên đăng nhập hoặc email đã tồn tại!');window.history.back();</script>";
                break;
            }
            $result = $userModel->register($username, $password, $email, $role);
            if ($result) {
                // Auto-login the newly registered user
                $newUser = $userModel->getUserByUsername($username);
                if ($newUser) {
                    $_SESSION['user_id'] = $newUser['iduser'];
                    $_SESSION['role'] = $newUser['role'] ?? 'customer';
                    $_SESSION['username'] = $newUser['username'];
                    $_SESSION['name'] = $newUser['name'] ?? '';
                    $_SESSION['email'] = $newUser['email'];
                    $_SESSION['isonline'] = 1;
                    $userModel->setOnlineStatus($newUser['iduser'], 1);
                    // Require profile completion
                    $_SESSION['require_profile'] = true;
                    echo "<script>window.location.href='../';</script>";
                    exit;
                }
                echo "<script>alert('Đăng ký thành công! Vui lòng đăng nhập.'); window.location.href='../';</script>";
            } else {
                echo "<script>alert('Đăng ký thất bại. Vui lòng thử lại!');window.history.back();</script>";
            }
        } catch (Exception $e) {
            echo "<script>alert('Đã xảy ra lỗi: " . $e->getMessage() . "');window.history.back();</script>";
        }
        break;

    case 'update':
        $id = $_POST['id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $date = $_POST['date'];
        $gender = $_POST['gender'];
        
        $avt = null;
        // Handle Avatar Upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['avatar']['tmp_name'];
            $fileName = basename($_FILES['avatar']['name']);
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (in_array($fileExt, $allowed)) {
                $newFileName = 'user_' . $id . '_' . time() . '.' . $fileExt;
                $uploadDir = '../src/img/';
                if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                    $avt = 'src/img/' . $newFileName;
                    $_SESSION['avt'] = $avt; // Update session immediately
                }
            }
        }

        $userModel->updateUser($id, $name, $email, $phone, $date, $gender, $avt);
        echo "<script>alert('Cập nhật thành công');</script>";
        break;

    case 'delete':
        $id = $_POST['id'];
        $userModel->deleteUser($id);
        echo "<script>alert('Xóa người dùng thành công');</script>";
        break;

    case 'list':
        $users = $userModel->getAllUsers();
        include "../views/user-list.php";
        break;

    case 'detail':
        $id = $_GET['id'];
        $user = $userModel->getUserById($id);
        include "../views/user-detail.php";
        break;
    case 'forgot':
        // Show forgot password form
        include __DIR__ . '/../views/forgot-password.php';
        break;
    case 'send_reset':
        $email = trim($_POST['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<script>alert('Vui lòng nhập email hợp lệ');window.history.back();</script>";
            break;
        }

        // ensure password_resets table exists (includes optional pin for PIN-based resets)
        $createSql = "CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(128) NOT NULL,
            pin VARCHAR(16) DEFAULT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $mysqli->query($createSql);

        $user = $userModel->getUserByEmail($email);
        // Always show generic message to avoid leaking
        $msg = 'Nếu email tồn tại trong hệ thống, một liên kết đặt lại sẽ được gửi.';
        if (!$user) {
            echo "<script>alert('" . addslashes($msg) . "'); window.location.href='../';</script>";
            break;
        }

        $token = bin2hex(random_bytes(16));
        // 6-digit PIN for email verification
        $pin = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
        $userModel->createPasswordReset($user['iduser'], $token, $expires, $pin);

        // Build absolute reset URL
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']); // e.g. /DunWeb/controllers
        $resetUrl = $siteOrigin . $scriptDir . '/user.controller.php?action=reset&token=' . urlencode($token);

        // Try to send email using PHPMailer
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            // Choose encryption based on port
            if (defined('SMTP_PORT') && (int)SMTP_PORT === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port = SMTP_PORT;
            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($email, $user['name'] ?? '');
            $mail->isHTML(true);
            $mail->Subject = 'Yêu cầu đặt lại mật khẩu cho DunWeb';
            $mail->Body = '<p>Xin chào ' . htmlspecialchars($user['name'] ?? '') . ',</p>' .
                '<p>Bạn (hoặc ai đó) đã yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>' .
                '<p>Mã xác thực (PIN) của bạn là: <b>' . htmlspecialchars($pin) . '</b></p>' .
                '<p>PIN có hiệu lực trong 1 giờ. Nếu bạn không yêu cầu, hãy bỏ qua email này.</p>' .
                '<hr/>' .
                '<p>Hoặc dùng liên kết đặt lại mật khẩu trực tiếp: <a href="' . $resetUrl . '">Đặt lại mật khẩu</a></p>' .
                '<p>Nếu không thể nhấp, hãy sao chép và dán đường dẫn sau vào trình duyệt:</p>' .
                '<p>' . htmlspecialchars($resetUrl) . '</p>';
            $mail->AltBody = 'Ma PIN: ' . $pin . ' - Link: ' . $resetUrl;
            $mail->send();

            // Show generic message
            echo "<!doctype html><html><body><p>" . htmlspecialchars($msg) . "</p><p>Vui lòng kiểm tra email để nhận liên kết đặt lại mật khẩu.</p><p><a href='../'>Về trang chủ</a></p></body></html>";
        } catch (Exception $e) {
            // Log and fall back to dev link for debugging
            write_app_log('Password reset email send failed: ' . $mail->ErrorInfo . ' Exception: ' . $e->getMessage());
            // Fallback: show dev link (useful for local dev)
            echo "<!doctype html><html><body><p>" . htmlspecialchars($msg) . "</p><p>Dev reset link (email failed): <a href=\"{$resetUrl}\">{$resetUrl}</a></p><p><a href='../'>Về trang chủ</a></p></body></html>";
        }
        break;
    case 'reset':
        $token = $_GET['token'] ?? '';
        if (empty($token)) {
            echo "<script>alert('Liên kết không hợp lệ');window.location.href='../';</script>";
            break;
        }
        // validate token
        $row = $userModel->getPasswordResetByToken($token);
        if (!$row || strtotime($row['expires_at']) < time()) {
            echo "<script>alert('Liên kết đã hết hạn hoặc không hợp lệ');window.location.href='../';</script>";
            break;
        }
        include __DIR__ . '/../views/reset-password.php';
        break;
    case 'do_reset':
        // Support two flows:
        // 1) token-based reset (user clicked link)
        // 2) email+PIN-based reset (user received PIN in email)
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm-password'] ?? '';
        $email = trim($_POST['email'] ?? '');
        $pin = trim($_POST['pin'] ?? '');

        if ($token) {
            // token flow
            if (empty($password) || $password !== $confirm) {
                echo "<script>alert('Dữ liệu không hợp lệ hoặc mật khẩu không khớp');window.history.back();</script>";
                break;
            }
            $row = $userModel->getPasswordResetByToken($token);
            if (!$row || strtotime($row['expires_at']) < time()) {
                echo "<script>alert('Liên kết đã hết hạn hoặc không hợp lệ');window.location.href='../';</script>";
                break;
            }
            $userId = $row['user_id'];
        } else {
            // email+pin flow
            if (empty($email) || empty($pin) || empty($password) || $password !== $confirm) {
                echo "<script>alert('Vui lòng nhập email, mã PIN và mật khẩu mới (và xác nhận mật khẩu)');window.history.back();</script>";
                break;
            }
            $user = $userModel->getUserByEmail($email);
            if (!$user) {
                echo "<script>alert('Email không tồn tại');window.history.back();</script>";
                break;
            }
            $row = $userModel->getPasswordResetByUserAndPin($user['iduser'], $pin);
            if (!$row || strtotime($row['expires_at']) < time()) {
                echo "<script>alert('Mã PIN không hợp lệ hoặc đã hết hạn');window.history.back();</script>";
                break;
            }
            $userId = $row['user_id'];
            // preserve token to delete later
            $token = $row['token'] ?? '';
        }

        // validate password rules (same as registration)
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[\W_]/', $password)) {
            echo "<script>alert('Mật khẩu phải có ít nhất 8 ký tự, gồm 1 chữ in hoa và 1 ký tự đặc biệt!');window.history.back();</script>";
            break;
        }
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        $userModel->updatePassword($userId, $newHash);
        if (!empty($token)) $userModel->deletePasswordReset($token);
        echo "<script>alert('Đặt lại mật khẩu thành công, đăng nhập bằng mật khẩu mới.'); window.location.href='../';</script>";
        break;
    case 'complete_profile':
        // AJAX endpoint to complete user profile after auto-login
        header('Content-Type: application/json');
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            echo json_encode(['ok' => false, 'msg' => 'Không có phiên đăng nhập']);
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        if (empty($name) || empty($gender) || empty($dob)) {
            echo json_encode(['ok' => false, 'msg' => 'Vui lòng nhập đầy đủ thông tin']);
            exit;
        }
        $ok = $userModel->updateProfile($userId, $name, $dob, $gender);
        if ($ok) {
            // clear the require_profile flag
            unset($_SESSION['require_profile']);
            // update session name
            $_SESSION['name'] = $name;
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'msg' => 'Lưu thất bại']);
        }
        exit;

    case 'update_bio':
        $userId = $_SESSION['user_id'] ?? 0;
        if (!$userId) {
            echo json_encode(['ok' => false, 'msg' => 'Vui lòng đăng nhập']);
            exit;
        }
        $bio = $_POST['bio'] ?? '';
        // Limit bio length to 500 chars
        if (mb_strlen($bio) > 500) {
            echo json_encode(['ok' => false, 'msg' => 'Tiểu sử quá dài (tối đa 500 ký tự)']);
            exit;
        }
        $ok = $userModel->updateBio($userId, $bio);
        echo json_encode(['ok' => $ok, 'bio' => nl2br(htmlspecialchars($bio))]);
        exit;
}
