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
        if ($this->hasLiked($post_id, $user_id)) return false;
        
        $stmt = $this->db->prepare("INSERT INTO likes (post_id, user_id, liked_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $post_id, $user_id);
        return $stmt->execute();
    }

    public function removeLike($post_id, $user_id)
    {
        $stmt = $this->db->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $post_id, $user_id);
        return $stmt->execute();
    }

    public function countLikes($post_id)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM likes WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'];
    }

    public function hasLiked($post_id, $user_id)
    {
        $stmt = $this->db->prepare("SELECT idlike FROM likes WHERE post_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $post_id, $user_id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
