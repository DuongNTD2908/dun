<?php
class NotificationModel
{
    private $db;

    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }

    public function getNotifications($user_id)
    {
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        if (!$stmt) return false;
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function addNotification($user_id, $content)
    {
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, content, created_at) VALUES (?, ?, NOW())");
        if (!$stmt) return false;
        $stmt->bind_param("is", $user_id, $content);
        return $stmt->execute();
    }

    public function deleteNotification($idnoti)
    {
        $stmt = $this->db->prepare("DELETE FROM notifications WHERE idnotifi = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $idnoti);
        return $stmt->execute();
    }
}