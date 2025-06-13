<?php
class Utilisateur {
    private $connect;
    
    public function __construct($connect) {
        $this->connect = $connect;
    }
    
    // Get all users
    public function getAllUsers() {
        try {
            $stmt = $this->connect->prepare("
                SELECT u.*, s.nom as section_nom, s.specialite as section_specialite
                FROM utilisateur u
                LEFT JOIN section s ON u.id_section = s.nom
                ORDER BY u.role ASC, u.nom_utilisateur ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
        }
    }
    
    // Get user by ID
    public function getUserById($userId) {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
        }
    }
    
    // Get user by email
    public function getUserByEmail($email) {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM utilisateur WHERE adresse_mail = ?");
            $stmt->execute([$email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
        }
    }
    
    // Add new user
    public function addUser($nom, $prenom, $email, $password, $role, $section) {
        try {
            $stmt = $this->connect->prepare("
                INSERT INTO utilisateur 
                (nom_utilisateur, prenom_utilisateur, adresse_mail, mot_de_passe, role, premiere_connexion, compte_bloque, mot_de_passe_errone, id_section) 
                VALUES (?, ?, ?, ?, ?, 1, 0, 0, ?)
            ");
            $stmt->execute([$nom, $prenom, $email, $password, $role, $section]);
            return $this->connect->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'ajout de l'utilisateur: " . $e->getMessage());
        }
    }
    
    // Update user
    public function updateUser($userId, $nom, $prenom, $email, $role, $section) {
        try {
            $stmt = $this->connect->prepare("
                UPDATE utilisateur 
                SET nom_utilisateur = ?, 
                    prenom_utilisateur = ?, 
                    adresse_mail = ?, 
                    role = ?, 
                    id_section = ? 
                WHERE id_utilisateur = ?
            ");
            $stmt->execute([$nom, $prenom, $email, $role, $section, $userId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
        }
    }
    
    // Update password
    public function updatePassword($userId, $password) {
        try {
            $stmt = $this->connect->prepare("
                UPDATE utilisateur 
                SET mot_de_passe = ?, 
                    premiere_connexion = 0 
                WHERE id_utilisateur = ?
            ");
            $stmt->execute([$password, $userId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour du mot de passe: " . $e->getMessage());
        }
    }
    
    // Reset password
    public function resetPassword($userId, $defaultPassword) {
        try {
            $stmt = $this->connect->prepare("
                UPDATE utilisateur 
                SET mot_de_passe = ?, 
                    premiere_connexion = 1,
                    compte_bloque = 0,
                    mot_de_passe_errone = 0
                WHERE id_utilisateur = ?
            ");
            $stmt->execute([$defaultPassword, $userId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la réinitialisation du mot de passe: " . $e->getMessage());
        }
    }
    
    // Delete user
    public function deleteUser($userId) {
        try {
            $stmt = $this->connect->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression de l'utilisateur: " . $e->getMessage());
        }
    }
    
    // Increment wrong password counter
    public function incrementWrongPassword($userId) {
        try {
            $user = $this->getUserById($userId);
            $wrongCount = $user['mot_de_passe_errone'] + 1;
            
            $stmt = $this->connect->prepare("UPDATE utilisateur SET mot_de_passe_errone = ? WHERE id_utilisateur = ?");
            $stmt->execute([$wrongCount, $userId]);
            
            // Block account after 3 wrong attempts
            if ($wrongCount >= 3) {
                $this->blockUser($userId);
            }
            
            return $wrongCount;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour du compteur de mot de passe erroné: " . $e->getMessage());
        }
    }
    
    // Reset wrong password counter
    public function resetWrongPassword($userId) {
        try {
            $stmt = $this->connect->prepare("UPDATE utilisateur SET mot_de_passe_errone = 0 WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la réinitialisation du compteur de mot de passe erroné: " . $e->getMessage());
        }
    }
    
    // Block user
    public function blockUser($userId) {
        try {
            $stmt = $this->connect->prepare("UPDATE utilisateur SET compte_bloque = 1 WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du blocage de l'utilisateur: " . $e->getMessage());
        }
    }
    
    // Unblock user
    public function unblockUser($userId) {
        try {
            $stmt = $this->connect->prepare("UPDATE utilisateur SET compte_bloque = 0, mot_de_passe_errone = 0 WHERE id_utilisateur = ?");
            $stmt->execute([$userId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors du déblocage de l'utilisateur: " . $e->getMessage());
        }
    }
}
?> 