<?php
class CommentModel
{
    private $db;

    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }

    public function getCommentsByPost($post_id)
    {
        // Join to users using the correct primary key column name (iduser)
        $stmt = $this->db->prepare("SELECT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.iduser WHERE post_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function addComment($post_id, $user_id, $content)
    {
        $stmt = $this->db->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $post_id, $user_id, $content);
        return $stmt->execute();
    }

    public function deleteComment($idcmt)
    {
        $stmt = $this->db->prepare("DELETE FROM comments WHERE idcmt = ?");
        $stmt->bind_param("i", $idcmt);
        return $stmt->execute();
    }

    public function updateComment($idcmt, $content)
    {
        $stmt = $this->db->prepare("UPDATE comments SET content = ? WHERE idcmt = ?");
        $stmt->bind_param("si", $content, $idcmt);
        return $stmt->execute();
    }
}