<?php
class ReportModel
{
    private $db;
    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }

    public function addReport($postId, $reporterId, $reason)
    {
        $stmt = $this->db->prepare("INSERT INTO post_reports (post_id, reporter_id, reason, created_at) VALUES (?, ?, ?, NOW())");
        if (!$stmt) return false;
        $stmt->bind_param('iis', $postId, $reporterId, $reason);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function getAllReports()
    {
        $res = $this->db->query("SELECT pr.*, p.title AS post_title, p.content AS post_content, u.username AS reporter_name, p.idpost AS post_id, p.user_id AS post_user_id FROM post_reports pr LEFT JOIN posts p ON pr.post_id = p.idpost LEFT JOIN users u ON pr.reporter_id = u.iduser ORDER BY pr.created_at DESC");
        if (!$res) return [];
        $rows = [];
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        return $rows;
    }

    public function getReportById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM post_reports WHERE idreport = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function markHandled($id, $handledBy, $adminNote = null)
    {
        $stmt = $this->db->prepare("UPDATE post_reports SET handled = 1, handled_by = ?, handled_at = NOW(), admin_note = ? WHERE idreport = ?");
        if (!$stmt) return false;
        $stmt->bind_param('isi', $handledBy, $adminNote, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
