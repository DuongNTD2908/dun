<?php
class LikeModel
{
    private $db;

    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }

    public function addLike($post_id, $user_id)
    {
        // Sử dụng INSERT IGNORE để tránh lỗi khi đã tồn tại và tăng hiệu suất
        $stmt = $this->db->prepare("INSERT IGNORE INTO likes (post_id, user_id, liked_at) VALUES (?, ?, NOW())");
        if (!$stmt) return false;
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        // Trả về true nếu có một hàng mới được thêm vào (tức là một lượt thích mới)
        return $stmt->affected_rows > 0;
    }

    public function removeLike($post_id, $user_id)
    {
        $stmt = $this->db->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ii", $post_id, $user_id);
        return $stmt->execute();
    }

    public function countLikes($post_id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM likes WHERE post_id = ?");
        if (!$stmt) return 0;
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? (int)$result['total'] : 0;
    }

    public function hasLiked($post_id, $user_id)
    {
        $stmt = $this->db->prepare("SELECT idlike FROM likes WHERE post_id = ? AND user_id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
