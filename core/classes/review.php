<?php
class Review {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get all reviews for a specific user (landlord)
    public function getUserReviews($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.full_name as reviewer_name, u.profile_picture as reviewer_avatar,
                   r.title as room_title, r.id as room_id
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN rooms r ON c.room_id = r.id
            WHERE r.landlord_id = ? AND c.is_review = 1
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get rating statistics for a user (landlord)
    public function getUserRatingStats($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(c.id) as total_reviews,
                AVG(c.rating) as avg_rating,
                SUM(CASE WHEN c.rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN c.rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN c.rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN c.rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN c.rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM comments c
            JOIN rooms r ON c.room_id = r.id
            WHERE r.landlord_id = ? AND c.is_review = 1 AND c.rating IS NOT NULL
        ");
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Ensure we have default values if no reviews exist
        if (!$stats['total_reviews']) {
            return [
                'total_reviews' => 0,
                'avg_rating' => 0,
                'five_star' => 0,
                'four_star' => 0,
                'three_star' => 0,
                'two_star' => 0,
                'one_star' => 0
            ];
        }
        
        return $stats;
    }
}