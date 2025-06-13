<?php
class Systeme {
    private $connect;
    
    public function __construct($connect) {
        $this->connect = $connect;
    }
    
    // Get all systems
    public function getAllSystems() {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM systeme");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des systèmes: " . $e->getMessage());
        }
    }
    
    // Get system by ID
    public function getSystemById($systemId) {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM systeme WHERE id_systeme = ?");
            $stmt->execute([$systemId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du système: " . $e->getMessage());
        }
    }
    
    // Add new system
    public function addSystem($nom, $description, $photo, $numeroSerie, $fabricant, $reference) {
        try {
            $stmt = $this->connect->prepare("
                INSERT INTO systeme 
                (nom_systeme, description_systeme, photo_systeme, numero_serie_systeme, fabricant_systeme, reference_systeme) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$nom, $description, $photo, $numeroSerie, $fabricant, $reference]);
            return $this->connect->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'ajout du système: " . $e->getMessage());
        }
    }
    
    // Update system
    public function updateSystem($systemId, $nom, $description, $numeroSerie, $fabricant, $reference) {
        try {
            $stmt = $this->connect->prepare("
                UPDATE systeme 
                SET nom_systeme = ?, 
                    description_systeme = ?, 
                    numero_serie_systeme = ?, 
                    fabricant_systeme = ?, 
                    reference_systeme = ? 
                WHERE id_systeme = ?
            ");
            $stmt->execute([$nom, $description, $numeroSerie, $fabricant, $reference, $systemId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour du système: " . $e->getMessage());
        }
    }
    
    // Update system with photo
    public function updateSystemWithPhoto($systemId, $nom, $description, $photo, $numeroSerie, $fabricant, $reference) {
        try {
            $stmt = $this->connect->prepare("
                UPDATE systeme 
                SET nom_systeme = ?, 
                    description_systeme = ?, 
                    photo_systeme = ?,
                    numero_serie_systeme = ?, 
                    fabricant_systeme = ?, 
                    reference_systeme = ? 
                WHERE id_systeme = ?
            ");
            $stmt->execute([$nom, $description, $photo, $numeroSerie, $fabricant, $reference, $systemId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour du système: " . $e->getMessage());
        }
    }
    
    // Delete system
    public function deleteSystem($systemId) {
        try {
            // Check if system has documents
            $stmtCheckDocTech = $this->connect->prepare("SELECT COUNT(*) FROM document_technique WHERE id_systeme = ?");
            $stmtCheckDocTech->execute([$systemId]);
            if ($stmtCheckDocTech->fetchColumn() > 0) {
                throw new Exception("Impossible de supprimer ce système car il contient des documents techniques");
            }
            
            $stmtCheckDocPeda = $this->connect->prepare("SELECT COUNT(*) FROM document_pedago WHERE id_systeme = ?");
            $stmtCheckDocPeda->execute([$systemId]);
            if ($stmtCheckDocPeda->fetchColumn() > 0) {
                throw new Exception("Impossible de supprimer ce système car il contient des documents pédagogiques");
            }
            
            // Delete system
            $stmt = $this->connect->prepare("DELETE FROM systeme WHERE id_systeme = ?");
            $stmt->execute([$systemId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression du système: " . $e->getMessage());
        }
    }
    
    // Get technical documents for a system
    public function getTechnicalDocuments($systemId) {
        try {
            $stmt = $this->connect->prepare("
                SELECT dt.*, c.nom_categorie, u.nom_utilisateur, u.prenom_utilisateur
                FROM document_technique dt
                JOIN categorie_document c ON dt.id_categorie = c.id_categorie
                JOIN utilisateur u ON dt.id_utilisateur = u.id_utilisateur
                WHERE dt.id_systeme = ?
                ORDER BY dt.date_depot DESC
            ");
            $stmt->execute([$systemId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des documents techniques: " . $e->getMessage());
        }
    }
    
    // Get pedagogical documents for a system
    public function getPedagogicalDocuments($systemId) {
        try {
            $stmt = $this->connect->prepare("
                SELECT dp.*, c.nom_categorie, u.nom_utilisateur, u.prenom_utilisateur
                FROM document_pedago dp
                JOIN categorie_document c ON dp.id_categorie = c.id_categorie
                JOIN utilisateur u ON dp.id_utilisateur_deposer = u.id_utilisateur
                WHERE dp.id_systeme = ?
                ORDER BY dp.date_depot DESC
            ");
            $stmt->execute([$systemId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des documents pédagogiques: " . $e->getMessage());
        }
    }
}
?> 