<?php
require_once __DIR__ . '/../config/database.php';

class RecommendationModel
{
    private $conn;
    public function __construct($mysqli)
    {
        $this->conn = $mysqli;
    }

    // Lấy bài viết gợi ý cho người dùng
    public function getRecommendations($userId, $limit = 10)
    {
        // If no user (guest), return popular/recent posts instead of personalized recommendations
        if (empty($userId) || (int)$userId <= 0) {
            $sql = "
            SELECT 
                p.*, 
                t.topic_name AS topic_name, 
                u.iduser AS user_id,
                u.username,
                u.name,
                u.avt,
                MAX(pi.image_url) AS image_url,
                COUNT(DISTINCT l.idlike) AS likes_count,
                COUNT(DISTINCT c.idcmt) AS comments_count
            FROM posts p
            LEFT JOIN topics t ON p.topic_id = t.id
            LEFT JOIN users u ON p.user_id = u.iduser
            LEFT JOIN likes l ON p.idpost = l.post_id
            LEFT JOIN comments c ON p.idpost = c.post_id
            LEFT JOIN post_images pi ON p.idpost = pi.post_id
            GROUP BY p.idpost
            ORDER BY (COUNT(DISTINCT l.idlike) * 0.6 + COUNT(DISTINCT c.idcmt) * 0.4) DESC, p.created_at DESC
            LIMIT ?";

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            return $stmt->get_result();
        }

        // Personalized recommendations for logged-in users
        // Show ALL posts sorted by popularity + randomization
        $sql = "
        SELECT 
            p.*, 
            t.topic_name AS topic_name, 
            u.iduser AS user_id,
            u.username,
            u.name,
            u.avt,
            MAX(pi.image_url) AS image_url,
            COUNT(DISTINCT l.idlike) AS likes_count,
            COUNT(DISTINCT c.idcmt) AS comments_count
        FROM posts p
        LEFT JOIN topics t ON p.topic_id = t.id
        LEFT JOIN users u ON p.user_id = u.iduser
        LEFT JOIN likes l ON p.idpost = l.post_id
        LEFT JOIN comments c ON p.idpost = c.post_id
        LEFT JOIN post_images pi ON p.idpost = pi.post_id
        WHERE p.user_id != ?
        GROUP BY p.idpost
        ORDER BY (COUNT(DISTINCT l.idlike) * 0.6 + COUNT(DISTINCT c.idcmt) * 0.4) DESC, RAND()
        LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
}
