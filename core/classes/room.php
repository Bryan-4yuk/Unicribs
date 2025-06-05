<?php
class Room {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Consolidated create method
    public function create($landlord_id, $data) {
        $this->pdo->beginTransaction();
        
        try {
            // Insert room basic info
            $stmt = $this->pdo->prepare("INSERT INTO rooms 
                (landlord_id, title, description, price, address, latitude, longitude, university_id, 
                room_type, gender_preference, available_from) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $landlord_id,
                $data['title'],
                $data['description'],
                $data['price'],
                $data['address'],
                $data['latitude'],
                $data['longitude'],
                $data['university_id'],
                $data['room_type'],
                $data['gender_preference'],
                $data['available_from']
            ]);
            
            $room_id = $this->pdo->lastInsertId();
            
            // Insert features
            if (!empty($data['features'])) {
                $feature_stmt = $this->pdo->prepare("INSERT INTO room_features (room_id, feature) VALUES (?, ?)");
                foreach ($data['features'] as $feature) {
                    $feature_stmt->execute([$room_id, $feature]);
                }
            }
            
            // Insert images
            if (!empty($data['images'])) {
                $image_stmt = $this->pdo->prepare("INSERT INTO room_images (room_id, image_url, is_primary) VALUES (?, ?, ?)");
                $primary_set = false;
                
                foreach ($data['images'] as $index => $image) {
                    $is_primary = (!$primary_set && $index === 0) ? 1 : 0;
                    if ($is_primary) $primary_set = true;
                    
                    $image_stmt->execute([$room_id, $image, $is_primary]);
                }
            }
            
            $this->pdo->commit();
            return $room_id;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Room creation failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function update($room_id, $landlord_id, $data) {
        $this->pdo->beginTransaction();
        
        try {
            // Update room basic info
            $stmt = $this->pdo->prepare("UPDATE rooms SET 
                title = ?, description = ?, price = ?, address = ?, latitude = ?, longitude = ?, 
                university_id = ?, room_type = ?, gender_preference = ?, available_from = ?, 
                is_available = ?, status = ?
                WHERE id = ? AND landlord_id = ?");
            
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['price'],
                $data['address'],
                $data['latitude'],
                $data['longitude'],
                $data['university_id'],
                $data['room_type'],
                $data['gender_preference'],
                $data['available_from'],
                $data['is_available'],
                $data['status'],
                $room_id,
                $landlord_id
            ]);
            
            // Update features - delete existing and insert new
            $this->pdo->prepare("DELETE FROM room_features WHERE room_id = ?")->execute([$room_id]);
            
            if (!empty($data['features'])) {
                $feature_stmt = $this->pdo->prepare("INSERT INTO room_features (room_id, feature) VALUES (?, ?)");
                foreach ($data['features'] as $feature) {
                    $feature_stmt->execute([$room_id, $feature]);
                }
            }
            
            // Update images - only if new images are provided
            if (!empty($data['images'])) {
                // Delete existing images
                $this->pdo->prepare("DELETE FROM room_images WHERE room_id = ?")->execute([$room_id]);
                
                $image_stmt = $this->pdo->prepare("INSERT INTO room_images (room_id, image_url, is_primary) VALUES (?, ?, ?)");
                $primary_set = false;
                
                foreach ($data['images'] as $index => $image) {
                    $is_primary = (!$primary_set && $index === 0) ? 1 : 0;
                    if ($is_primary) $primary_set = true;
                    
                    $image_stmt->execute([$room_id, $image, $is_primary]);
                }
            }
            
            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
    
    public function delete($room_id, $landlord_id) {
        $stmt = $this->pdo->prepare("DELETE FROM rooms WHERE id = ? AND landlord_id = ?");
        return $stmt->execute([$room_id, $landlord_id]);
    }
    
    public function getRoom($room_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, u.full_name as landlord_name, u.profile_picture as landlord_avatar, 
                   un.name as university_name, un.location as university_location
            FROM rooms r
            JOIN users u ON r.landlord_id = u.id
            JOIN universities un ON r.university_id = un.id
            WHERE r.id = ?
        ");
        $stmt->execute([$room_id]);
        $room = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($room) {
            $room['features'] = $this->getRoomFeatures($room_id);
            $room['images'] = $this->getRoomImages($room_id);
        }
        
        return $room;
    }
    
    public function getRoomFeatures($room_id) {
        $stmt = $this->pdo->prepare("SELECT feature FROM room_features WHERE room_id = ?");
        $stmt->execute([$room_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    public function getRoomImages($room_id) {
        $stmt = $this->pdo->prepare("SELECT id, image_url, is_primary FROM room_images WHERE room_id = ? ORDER BY is_primary DESC");
        $stmt->execute([$room_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRooms($filters = [], $page = 1, $per_page = 10, $user_id = null) {
        $where = [];
        $params = [];
        
        // Build WHERE clause based on filters
        if (!empty($filters['university_id'])) {
            $where[] = "r.university_id = ?";
            $params[] = $filters['university_id'];
        }
            
        if (!empty($filters['min_price'])) {
            $where[] = "r.price >= ?";
            $params[] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where[] = "r.price <= ?";
            $params[] = $filters['max_price'];
        }
        
        if (!empty($filters['room_type'])) {
            $where[] = "r.room_type = ?";
            $params[] = $filters['room_type'];
        }
        
        if (!empty($filters['gender_preference']) && $filters['gender_preference'] != 'any') {
            $where[] = "(r.gender_preference = ? OR r.gender_preference = 'any')";
            $params[] = $filters['gender_preference'];
        }
        
        if (!empty($filters['features'])) {
            $feature_count = count($filters['features']);
            $placeholders = implode(',', array_fill(0, $feature_count, '?'));
            $where[] = "r.id IN (
                SELECT room_id FROM room_features 
                WHERE feature IN ($placeholders) 
                GROUP BY room_id 
                HAVING COUNT(DISTINCT feature) = $feature_count
            )";
            $params = array_merge($params, $filters['features']);
        }
        
        $where_clause = $where ? "WHERE " . implode(" AND ", $where) : "";
        
        // Count total rooms for pagination
        $count_stmt = $this->pdo->prepare("
            SELECT COUNT(*) 
            FROM rooms r
            $where_clause
        ");
        $count_stmt->execute($params);
        $total_rooms = $count_stmt->fetchColumn();
        $total_pages = ceil($total_rooms / $per_page);
        
        // Get rooms for current page
        $offset = ($page - 1) * $per_page;
        $query = "
            SELECT r.*, u.full_name as landlord_name, u.profile_picture as landlord_avatar, 
                un.name as university_name, un.location as university_location,
                (SELECT COUNT(*) FROM likes WHERE room_id = r.id) as like_count,
                (SELECT COUNT(*) FROM bookings WHERE room_id = r.id AND status = 'approved') as booking_count,";
                
        if ($user_id) {
            $query .= " (SELECT 1 FROM likes WHERE room_id = r.id AND user_id = ?) as is_liked,";
            $params[] = $user_id;
        } else {
            $query .= " 0 as is_liked,";
        }
        
        $query .= " (SELECT image_url FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM rooms r
            JOIN users u ON r.landlord_id = u.id
            JOIN universities un ON r.university_id = un.id
            $where_clause
            ORDER BY r.created_at DESC
            LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params); // Now only the filter params are passed here
        $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'rooms' => $rooms,
            'total_pages' => $total_pages,
            'current_page' => $page
        ];
    }
    
    public function getLandlordRooms($landlord_id, $status = 'active') {
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                un.name as university_name,
                (SELECT COUNT(*) FROM likes WHERE room_id = r.id) as like_count,
                (SELECT COUNT(*) FROM bookings WHERE room_id = r.id AND status = 'approved') as booking_count,
                (SELECT image_url FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM rooms r
            LEFT JOIN universities un ON r.university_id = un.id
            WHERE r.landlord_id = ? AND r.status = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->execute([$landlord_id, $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function toggleLike($user_id, $room_id) {
        // Check if already liked
        $stmt = $this->pdo->prepare("SELECT 1 FROM likes WHERE user_id = ? AND room_id = ?");
        $stmt->execute([$user_id, $room_id]);
        
        if ($stmt->fetch()) {
            // Unlike
            $stmt = $this->pdo->prepare("DELETE FROM likes WHERE user_id = ? AND room_id = ?");
            $stmt->execute([$user_id, $room_id]);
            return ['action' => 'unliked', 'count' => $this->getLikeCount($room_id)];
        } else {
            // Like
            $stmt = $this->pdo->prepare("INSERT INTO likes (user_id, room_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $room_id]);
            return ['action' => 'liked', 'count' => $this->getLikeCount($room_id)];
        }
    }

    public function getRoomRating($roomId) {
    // Get average rating and count
    $stmt = $this->pdo->prepare("
        SELECT AVG(rating) as avg_rating, COUNT(*) as review_count 
        FROM comments 
        WHERE room_id = ? AND is_review = 1 AND rating IS NOT NULL
    ");
    $stmt->execute([$roomId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return [
        'rating' => $result['avg_rating'] ? round($result['avg_rating'], 1) : 0,
        'review_count' => $result['review_count'] ? (int)$result['review_count'] : 0
    ];
    }

    public function getLandlordReviews($landlord_id) {
        $stmt = $this->pdo->prepare("
            SELECT c.*, u.full_name as reviewer_name, u.profile_picture as reviewer_avatar,
                r.id as room_id, r.title as room_title
            FROM comments c
            JOIN users u ON c.user_id = u.id
            JOIN rooms r ON c.room_id = r.id
            WHERE r.landlord_id = ? AND c.is_review = 1 AND c.rating IS NOT NULL
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$landlord_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLandlordRatingStats($landlord_id) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(c.id) as total_reviews,
                AVG(c.rating) as avg_rating
            FROM comments c
            JOIN rooms r ON c.room_id = r.id
            WHERE r.landlord_id = ? AND c.is_review = 1 AND c.rating IS NOT NULL
        ");
        $stmt->execute([$landlord_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'total_reviews' => $result['total_reviews'] ? (int)$result['total_reviews'] : 0,
            'avg_rating' => $result['avg_rating'] ? round($result['avg_rating'], 1) : 0
        ];
    }
    // Add comment
    public function addComment($userId, $roomId, $content, $parentId = null, $isReview = false, $rating = null) {
        $stmt = $this->pdo->prepare("INSERT INTO comments 
            (room_id, user_id, parent_id, is_review, rating, content) 
            VALUES (?, ?, ?, ?, ?, ?)");
        
        // Convert null parent_id to NULL for database
        $dbParentId = ($parentId === null) ? null : (int)$parentId;
        
        // Convert null rating to NULL for database if it's not a review
        $dbRating = ($isReview && $rating !== null) ? (int)$rating : null;
        
        return $stmt->execute([
            $roomId, 
            $userId, 
            $dbParentId, 
            $isReview ? 1 : 0, 
            $dbRating, 
            $content
        ]);
    }

    public function getComments($roomId, $includeReviews = true) {
        // Modified to always include reviews with ratings
        $where = "c.room_id = ? AND (c.is_review = 1 OR c.parent_id IS NULL)";
        
        // Get parent comments
        $stmt = $this->pdo->prepare("SELECT c.*, u.full_name, u.profile_picture 
                                    FROM comments c
                                    JOIN users u ON c.user_id = u.id
                                    WHERE $where
                                    ORDER BY c.created_at DESC");
        $stmt->execute([$roomId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get replies for each comment
        foreach ($comments as &$comment) {
            $stmt = $this->pdo->prepare("SELECT c.*, u.full_name, u.profile_picture 
                                        FROM comments c
                                        JOIN users u ON c.user_id = u.id
                                        WHERE c.parent_id = ?
                                        ORDER BY c.created_at ASC");
            $stmt->execute([$comment['id']]);
            $comment['replies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        return $comments;
    }

    public function deleteComment($commentId, $userId) {
        // First check if comment belongs to user
        $stmt = $this->pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($comment && $comment['user_id'] == $userId) {
            // Delete the comment
            $stmt = $this->pdo->prepare("DELETE FROM comments WHERE id = ?");
            return $stmt->execute([$commentId]);
        }
        return false;
    }

    public function getLikeCount($roomId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE room_id = ?");
        $stmt->execute([$roomId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    public function isLiked($userId, $roomId) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM likes WHERE user_id = ? AND room_id = ? LIMIT 1");
        $stmt->execute([$userId, $roomId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
    }

    public function getLikedRooms($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   (SELECT image_url FROM room_images WHERE room_id = r.id AND is_primary = 1 LIMIT 1) as primary_image
            FROM rooms r
            JOIN likes l ON r.id = l.room_id
            WHERE l.user_id = ?
            ORDER BY l.created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    
    
}