<?php
class University {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function getAllUniversities() {
        $stmt = $this->pdo->query("SELECT * FROM universities ORDER BY name");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getUniversity($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM universities WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>