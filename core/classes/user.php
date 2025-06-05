<?php
class User {
    private $pdo;
    private $user_id;
    private $full_name;
    private $email;
    private $user_type;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $this->user_id = $user['id'];
            $this->full_name = $user['full_name'];
            $this->email = $user['email'];
            $this->user_type = $user['user_type'];
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            
            return true;
        }
        return false;
    }
    
    public function register($data) {
        $stmt = $this->pdo->prepare("INSERT INTO users (full_name, email, password, user_type, cni_number) 
                                    VALUES (?, ?, ?, ?, ?)");
        $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
        $cni = $data['user_type'] === 'landlord' ? $data['cni'] : null;
        
        return $stmt->execute([
            $data['full_name'],
            $data['email'],
            $hashed_password,
            $data['user_type'],
            $cni
        ]);
    }
    
    public function googleLogin($google_id, $email, $name) {
        // Check if user exists
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
        $stmt->execute([$google_id, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Update google_id if not set
            if (empty($user['google_id'])) {
                $update = $this->pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
                $update->execute([$google_id, $user['id']]);
            }
            
            $this->user_id = $user['id'];
            $this->full_name = $user['full_name'];
            $this->email = $user['email'];
            $this->user_type = $user['user_type'];
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user['user_type'];
            
            return true;
        } else {
            // Register new user with Google
            $stmt = $this->pdo->prepare("INSERT INTO users (full_name, email, google_id, user_type) 
                                        VALUES (?, ?, ?, 'student')");
            if ($stmt->execute([$name, $email, $google_id])) {
                return $this->googleLogin($google_id, $email, $name);
            }
        }
        return false;
    }
    
    public function getUser($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function updateProfile($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE users SET full_name = ?, bio = ?, phone_number = ?, whatsapp_number = ? WHERE id = ?");
        return $stmt->execute([
            $data['full_name'],
            $data['bio'],
            $data['phone_number'],
            $data['whatsapp_number'],
            $id
        ]);
    }
    
    public function updateProfilePicture($id, $image_path) {
        $stmt = $this->pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        return $stmt->execute([$image_path, $id]);
    }
    public function updateUser($id, $data) {
        $fields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            $fields[] = "$field = ?";
            $values[] = $value;
        }
        
        $values[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function changePassword($id, $currentPassword, $newPassword) {
        $user = $this->getUser($id);
        
        if (password_verify($currentPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            return $stmt->execute([$hashedPassword, $id]);
        }
        
        return false;
    }

    public function changeEmail($id, $newEmail, $password) {
        $user = $this->getUser($id);
        
        if (password_verify($password, $user['password'])) {
            $stmt = $this->pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            return $stmt->execute([$newEmail, $id]);
        }
        
        return false;
    }

    public function deactivateAccount($id) {
        $stmt = $this->pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() !== false;
    }
    
    public function setResetToken($email, $token) {
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $stmt = $this->pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE email = ?");
        return $stmt->execute([$token, $expires, $email]);
    }
    
    public function resetPassword($token, $password) {
        // Check if token is valid and not expired
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()");
        $stmt->execute([$token]);
        $user_id = $stmt->fetchColumn();
        
        if ($user_id) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update = $this->pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
            return $update->execute([$hashed_password, $user_id]);
        }
        return false;
    }

}
?>