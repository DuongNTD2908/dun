<?php
class TopicModel
{
    private $db;

    public function __construct($mysqli)
    {
        $this->db = $mysqli;
    }

    public function getAllTopics()
    {
        $sql = "SELECT * FROM topics ORDER BY name ASC";
        return $this->db->query($sql);
    }

    public function getTopicById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM topics WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    
}