<?php
class UserModel
{
    private $db;

    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }

    public function getAllUsers()
    {
        return $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
    }

    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE iduser = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getUserByUsername($username)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function register($username, $password, $email, $role = 'customer')
    {
        // Hash password before storing
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        // Insert with isonline default 0
        $stmt = $this->db->prepare("INSERT INTO users (username, password, email, role, isonline, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        if ($stmt === false) return false;
        $stmt->bind_param("ssss", $username, $hashed, $email, $role);
        return $stmt->execute();
    }

    public function updateUser($id, $name, $email, $phone, $dob, $gender)
    {
        // 'date' column name used in users table for birth date
        $stmt = $this->db->prepare("UPDATE users SET name = ?, email = ?, phone = ?, `date` = ?, gender = ? WHERE iduser = ?");
        $stmt->bind_param("sssssi", $name, $email, $phone, $dob, $gender, $id);
        return $stmt->execute();
    }

    public function deleteUser($id)
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE iduser = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function login($usernameOrEmail, $password)
    {
        // Allow login by username or email
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        if ($stmt === false) return false;
        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) return false;
        // Verify password (supports hashed and legacy plaintext)
        if (isset($user['password'])) {
            $stored = $user['password'];
            // If stored looks like a bcrypt hash, verify normally
            if (is_string($stored) && (strpos($stored, '$2y$') === 0 || strpos($stored, '$2a$') === 0)) {
                if (password_verify($password, $stored)) {
                    return $user;
                }
            } else {
                // Legacy plaintext fallback: if matches, re-hash and update DB
                if ($password === $stored) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $this->updatePassword($user['iduser'], $newHash);
                    // replace in returned user for consistency
                    $user['password'] = $newHash;
                    return $user;
                }
            }
        }
        return false;
    }

    public function updatePassword($id, $newHash)
    {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE iduser = ?");
        if ($stmt === false) return false;
        $stmt->bind_param("si", $newHash, $id);
        return $stmt->execute();
    }

    // Update basic profile fields after registration
    public function updateProfile($id, $name, $dob, $gender)
    {
        // Use `date` column name to match DB schema
        $stmt = $this->db->prepare("UPDATE users SET name = ?, `date` = ?, gender = ? WHERE iduser = ?");
        if ($stmt === false) return false;
        $stmt->bind_param("sssi", $name, $dob, $gender, $id);
        return $stmt->execute();
    }

    public function isUserExist($usernameOrEmail)
    {
        $stmt = $this->db->prepare("SELECT iduser FROM users WHERE username = ? OR email = ? LIMIT 1");
        if ($stmt === false) return false;
        $stmt->bind_param("ss", $usernameOrEmail, $usernameOrEmail);
        $stmt->execute();
        $res = $stmt->get_result();
        return ($res && $res->num_rows > 0);
    }

    // get user by email
    public function getUserByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        if ($stmt === false) return false;
        $stmt->bind_param("s", $email);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Password reset tokens
    public function createPasswordReset($userId, $token, $expiresAt, $pin = null)
    {
        // Try insert including `pin` column. If the database schema doesn't have that column,
        // fall back to legacy insert without pin.
        try {
            $stmt = $this->db->prepare("INSERT INTO password_resets (user_id, token, pin, expires_at, created_at) VALUES (?, ?, ?, ?, NOW())");
        } catch (mysqli_sql_exception $e) {
            // Fallback: try insert without pin column
            try {
                $stmt = $this->db->prepare("INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
            } catch (mysqli_sql_exception $e2) {
                return false;
            }
            if ($stmt === false) return false;
            $stmt->bind_param("iss", $userId, $token, $expiresAt);
            return $stmt->execute();
        }

        if ($stmt === false) return false;
        // ensure pin is a string (nullable)
        $pinVal = $pin === null ? null : (string)$pin;
        $stmt->bind_param("isss", $userId, $token, $pinVal, $expiresAt);
        return $stmt->execute();
    }

    public function getPasswordResetByToken($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM password_resets WHERE token = ? LIMIT 1");
        if ($stmt === false) return false;
        $stmt->bind_param("s", $token);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPasswordResetByUserAndPin($userId, $pin)
    {
        $stmt = $this->db->prepare("SELECT * FROM password_resets WHERE user_id = ? AND pin = ? LIMIT 1");
        if ($stmt === false) return false;
        $stmt->bind_param("is", $userId, $pin);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function deletePasswordReset($token)
    {
        $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
        if ($stmt === false) return false;
        $stmt->bind_param("s", $token);
        return $stmt->execute();
    }

    public function setOnlineStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE users SET isonline = ? WHERE iduser = ?");
        $stmt->bind_param("ii", $status, $id);
        return $stmt->execute();
    }
}
