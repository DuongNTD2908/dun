<?php
require_once __DIR__ . '/../models/recommendation.model.php';
require_once __DIR__ . '/../models/like.model.php'; // để hiển thị số like

class RecommendationController
{
    private $model;
    private $likeModel;

    public function __construct($mysqli)
    {
        $this->model = new RecommendationModel($mysqli);
        $this->likeModel = new LikeModel($mysqli);
    }

    public function showRecommendations($userId)
    {
        $result = $this->model->getRecommendations($userId);

        // chuyển result thành mảng để post-card dễ duyệt foreach
        $posts = [];
        if ($result && $result instanceof mysqli_result) {
            while ($row = $result->fetch_assoc()) {
                $posts[] = $row;
            }
        }

        // If personalized recommendations returned nothing for a logged-in user,
        // fall back to public/popular posts so the feed isn't empty.
        if (empty($posts) && !empty($userId)) {
            $fallbackRes = $this->model->getRecommendations(0, 10);
            if ($fallbackRes && $fallbackRes instanceof mysqli_result) {
                $posts = [];
                while ($r = $fallbackRes->fetch_assoc()) {
                    $posts[] = $r;
                }
            }
        }

        // biến $likeModel sẽ có trong view
        $likeModel = $this->likeModel;

        include __DIR__ . '/../views/post-card.php';
    }
}
