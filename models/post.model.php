<?php
class PostModel
{
    private $db;

    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }

    public function getAllPosts()
    {
        $sql = "SELECT p.*, t.topic_name AS topic_name, u.username 
                FROM posts p
                JOIN topics t ON p.topic_id = t.id
                JOIN users u ON p.user_id = u.iduser
                ORDER BY p.created_at DESC";
        return $this->db->query($sql);
    }

    public function getPostById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM posts WHERE idpost = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createPost($user_id, $title, $content, $topic_id)
    {
        $stmt = $this->db->prepare("INSERT INTO posts (user_id, title, content, topic_id, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("issi", $user_id, $title, $content, $topic_id);
        return $stmt->execute();
    }

    public function getImagesByPost($post_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM post_images WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function addImage($post_id, $image_url)
    {
        $stmt = $this->db->prepare("INSERT INTO post_images (post_id, image_url) VALUES (?, ?)");
        $stmt->bind_param("is", $post_id, $image_url);
        return $stmt->execute();
    }

    public function getAllPostsWithImages()
    {
        $sql = "
        SELECT p.*, u.*, t.topic_name, pi.image_url
        FROM posts p
        JOIN users u ON p.user_id = u.iduser
        JOIN topics t ON p.topic_id = t.id
        LEFT JOIN post_images pi ON pi.post_id = p.idpost
        ORDER BY p.created_at DESC
    ";
        return $this->db->query($sql);
    }

    public function getPostsByUser($user_id)
    {
        $stmt = $this->db->prepare("SELECT p.*, t.topic_name, u.username, u.name, u.avt FROM posts p JOIN topics t ON p.topic_id = t.id JOIN users u ON p.user_id = u.iduser WHERE p.user_id = ? ORDER BY p.created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getRecommendedPosts($user_id, $limit = 20)
    {
        // Logic:
        // 1. Bài viết mới (trong 30 phút) lên đầu (is_new = 1)
        // 2. Sắp xếp theo tổng tương tác (like + comment) giảm dần
        // 3. Nếu không có tương tác, sắp xếp theo thời gian
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
                COUNT(DISTINCT c.idcmt) AS comments_count,
                (COUNT(DISTINCT l.idlike) + COUNT(DISTINCT c.idcmt)) AS total_interactions,
                MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) AS is_liked,
                CASE 
                    WHEN p.created_at >= NOW() - INTERVAL 30 MINUTE THEN 1 
                    ELSE 0 
                END AS is_new,
                CASE 
                    WHEN p.created_at >= NOW() - INTERVAL 2 DAY THEN 1 
                    ELSE 0 
                END AS is_recent
            FROM posts p
            LEFT JOIN topics t ON p.topic_id = t.id
            LEFT JOIN users u ON p.user_id = u.iduser
            LEFT JOIN likes l ON p.idpost = l.post_id
            LEFT JOIN comments c ON p.idpost = c.post_id
            LEFT JOIN post_images pi ON p.idpost = pi.post_id
            GROUP BY p.idpost
            ORDER BY 
                is_new DESC, 
                is_recent DESC,
                total_interactions DESC, 
                p.created_at DESC
            LIMIT ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getFollowingPosts($user_id, $limit = 20)
    {
        if (!$user_id || (int)$user_id <= 0) {
            return null;
        }

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
                COUNT(DISTINCT c.idcmt) AS comments_count,
                (COUNT(DISTINCT l.idlike) + COUNT(DISTINCT c.idcmt)) AS total_interactions,
                MAX(CASE WHEN l.user_id = ? THEN 1 ELSE 0 END) AS is_liked,
                CASE 
                    WHEN p.created_at >= NOW() - INTERVAL 30 MINUTE THEN 1 
                    ELSE 0 
                END AS is_new,
                CASE 
                    WHEN p.created_at >= NOW() - INTERVAL 2 DAY THEN 1 
                    ELSE 0 
                END AS is_recent
            FROM posts p
            LEFT JOIN topics t ON p.topic_id = t.id
            LEFT JOIN users u ON p.user_id = u.iduser
            LEFT JOIN likes l ON p.idpost = l.post_id
            LEFT JOIN comments c ON p.idpost = c.post_id
            LEFT JOIN post_images pi ON p.idpost = pi.post_id
            WHERE p.user_id IN (
                SELECT following_id FROM follows WHERE follower_id = ?
            )
            AND p.user_id != ? -- Không hiển thị bài viết của chính mình
            GROUP BY p.idpost
            ORDER BY 
                is_new DESC,
                is_recent DESC,
                total_interactions DESC,
                p.created_at DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        // user_id cho is_liked, user_id cho follows, user_id để loại trừ, limit
        $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
}
