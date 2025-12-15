<?php
class MessageModel
{
    private $db;
    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }
    public function getMessages($user_id)
    {
        // Return messages received by a user (most recent first)
        $sql = "SELECT m.*, u.username AS sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.iduser
                                WHERE m.receiver_id = ?
                                    AND m.is_deleted = 0
                ORDER BY m.sent_at DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    public function sendMessage($sender_id, $receiver_id, $content, $type = 'text', $attachment_url = null, $conversation_id = null)
    {
        // Insert message into DB, associate with a conversation
        $sql = "INSERT INTO messages (sender_id, receiver_id, content, type, attachment_url, is_read, sent_at, conversation_id) VALUES (?, ?, ?, ?, ?, 0, NOW(), ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("iisssi", $sender_id, $receiver_id, $content, $type, $attachment_url, $conversation_id);
        return $stmt->execute();
    }

    public function getConversation($user1_id, $user2_id)
    {
                $stmt = $this->db->prepare("
                SELECT m.*, u.username AS sender_name
                FROM messages m
                JOIN users u ON m.sender_id = u.iduser
                WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
                    AND m.is_deleted = 0
                ORDER BY m.sent_at ASC
        ");
        if (!$stmt) return false;
        $stmt->bind_param("iiii", $user1_id, $user2_id, $user2_id, $user1_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    public function markAsRead($id)
    {
        $stmt = $this->db->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    public function deleteMessage($id)
    {
        // Soft-delete: set is_deleted flag so messages can be recovered or filtered
        $stmt = $this->db->prepare("UPDATE messages SET is_deleted = 1 WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    public function getOrCreateConversation($user1_id, $user2_id)
    {
        $stmt = $this->db->prepare("SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
        if (!$stmt) return false;
        $stmt->bind_param("iiii", $user1_id, $user2_id, $user2_id, $user1_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return (int)$row['id'];
        }

        $stmt = $this->db->prepare("INSERT INTO conversations (user1_id, user2_id, created_at) VALUES (?, ?, NOW())");
        if (!$stmt) return false;
        $stmt->bind_param("ii", $user1_id, $user2_id);
        $stmt->execute();
        return $this->db->insert_id;
    }
    /**
     * Get inbox (conversations) for a user: other user, last message and unread count
     */
    public function getInbox($user_id)
    {
    $sql = "SELECT c.id AS conversation_id,
               CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END AS other_user_id,
               u.username AS other_username,
               u.name AS other_name,
               u.avt AS other_avt,
               (SELECT content FROM messages m WHERE m.conversation_id = c.id AND m.is_deleted = 0 ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
               (SELECT sent_at FROM messages m WHERE m.conversation_id = c.id AND m.is_deleted = 0 ORDER BY m.sent_at DESC LIMIT 1) AS last_sent_at,
               (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.receiver_id = ? AND m.is_read = 0 AND m.is_deleted = 0) AS unread_count
        FROM conversations c
        JOIN users u ON u.iduser = (CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END)
        WHERE c.user1_id = ? OR c.user2_id = ?
        ORDER BY last_sent_at DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        // bind user_id multiple times
        $stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    public function searchInbox($user_id, $keyword)
    {
        $search = "%" . $keyword . "%";
        $sql = "SELECT c.id AS conversation_id,
               CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END AS other_user_id,
               u.username AS other_username,
               u.name AS other_name,
               u.avt AS other_avt,
               (SELECT content FROM messages m WHERE m.conversation_id = c.id AND m.is_deleted = 0 ORDER BY m.sent_at DESC LIMIT 1) AS last_message,
               (SELECT sent_at FROM messages m WHERE m.conversation_id = c.id AND m.is_deleted = 0 ORDER BY m.sent_at DESC LIMIT 1) AS last_sent_at,
               (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.receiver_id = ? AND m.is_read = 0 AND m.is_deleted = 0) AS unread_count
        FROM conversations c
        JOIN users u ON u.iduser = (CASE WHEN c.user1_id = ? THEN c.user2_id ELSE c.user1_id END)
        WHERE (c.user1_id = ? OR c.user2_id = ?)
        AND (u.username LIKE ? OR u.name LIKE ?)
        ORDER BY last_sent_at DESC";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("iiiiiss", $user_id, $user_id, $user_id, $user_id, $user_id, $search, $search);
        $stmt->execute();
        return $stmt->get_result();
    }
}
