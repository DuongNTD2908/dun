<?php
class FollowModel
{
    private $db;

    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }

    public function followUser($follower_id, $following_id)
    {
        // Insert follow
        $stmt = $this->db->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $follower_id, $following_id);
        $stmt->execute();

        // Kiểm tra follow ngược
        $check = $this->db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
        $check->bind_param("ii", $following_id, $follower_id);
        $check->execute();
        $result = $check->get_result();

        // *** ĐÂY LÀ DÒNG ĐÃ SỬA (TRƯỚC ĐÂY LÀ fetch()) ***
        if ($result->num_rows > 0) {
            // Follow hai chiều → là bạn bè
            $user1 = min($follower_id, $following_id);
            $user2 = max($follower_id, $following_id);
            $friend = $this->db->prepare("INSERT IGNORE INTO friends (user1_id, user2_id) VALUES (?, ?)");
            $friend->bind_param("ii", $user1, $user2);
            $friend->execute();
        }
    }

    public function unfollowUser($follower_id, $following_id)
    {
        $stmt = $this->db->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->bind_param("ii", $follower_id, $following_id);
        $stmt->execute();

        // Nếu là bạn bè → xoá khỏi bảng friends
        $user1 = min($follower_id, $following_id);
        $user2 = max($follower_id, $following_id);

        $stmt = $this->db->prepare("DELETE FROM friends WHERE user1_id = ? AND user2_id = ?");
        $stmt->bind_param("ii", $user1, $user2);
        $stmt->execute();
    }

    public function getFollowers($user_id)
    {
        $stmt = $this->db->prepare("SELECT u.* FROM follows f JOIN users u ON f.follower_id = u.iduser WHERE f.following_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getFollowing($user_id)
    {
        $stmt = $this->db->prepare("SELECT u.* FROM follows f JOIN users u ON f.following_id = u.iduser WHERE f.follower_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getFriends($user_id)
    {
        $stmt = $this->db->prepare("
            SELECT u.* FROM friends f 
            JOIN users u ON (u.iduser = f.user1_id OR u.iduser = f.user2_id)
            WHERE (f.user1_id = ? OR f.user2_id = ?) AND u.iduser != ?
        ");
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function isFollowing($follower_id, $following_id)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1");
        if ($stmt === false) return false;
        $stmt->bind_param("ii", $follower_id, $following_id);
        $stmt->execute();
        $res = $stmt->get_result();
        return ($res && $res->num_rows > 0);
    }
}
