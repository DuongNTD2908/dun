<?php
class SearchModel
{
    private $db;

    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }

    public function ensureTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS search_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            query_text VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        return $this->db->query($sql);
    }

    public function addHistory($user_id, $query)
    {
        $this->ensureTable();
        $query = trim(substr($query, 0, 255));
        $stmt = $this->db->prepare("INSERT INTO search_history (user_id, query_text, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param('is', $user_id, $query);
        return $stmt->execute();
    }

    public function getHistory($user_id, $limit = 20)
    {
        // *** THAY ĐỔI: Thêm 'id' vào câu lệnh SELECT ***
        $stmt = $this->db->prepare("SELECT id, query_text, created_at FROM search_history WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param('ii', $user_id, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    // *** HÀM MỚI: Xóa một mục lịch sử ***
    public function deleteHistoryItem($user_id, $history_id)
    {
        $stmt = $this->db->prepare("DELETE FROM search_history WHERE id = ? AND user_id = ?");
        $stmt->bind_param('ii', $history_id, $user_id);
        return $stmt->execute();
    }

    // *** HÀM MỚI: Xóa tất cả lịch sử của người dùng ***
    public function clearHistory($user_id)
    {
        $stmt = $this->db->prepare("DELETE FROM search_history WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
        return $stmt->execute();
    }
}
?>