<?php
class Section {
    private $connect;
    
    public function __construct($connect) {
        $this->connect = $connect;
    }
    
    // Get all sections
    public function getAllSections() {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM section ORDER BY nom ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des sections: " . $e->getMessage());
        }
    }
    
    // Get section by ID (nom)
    public function getSectionById($sectionId) {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM section WHERE nom = ?");
            $stmt->execute([$sectionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de la section: " . $e->getMessage());
        }
    }
    
    // Add new section
    public function addSection($nom, $specialite, $option, $promotion) {
        try {
            $stmt = $this->connect->prepare("
                INSERT INTO section 
                (nom, specialite, `option`, promotion) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$nom, $specialite, $option, $promotion]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'ajout de la section: " . $e->getMessage());
        }
    }
    
    // Update section (special case because nom is the primary key)
    public function updateSection($oldNom, $nom, $specialite, $option, $promotion) {
        try {
            // Begin transaction
            $this->connect->beginTransaction();
            
            // If name is changing, need to update foreign keys
            if ($oldNom != $nom) {
                // Update foreign keys in matiere table
                $stmtMatiere = $this->connect->prepare("
                    UPDATE matiere
                    SET nom = ?, section_classe = ?
                    WHERE nom = ?
                ");
                $stmtMatiere->execute([$nom, $nom, $oldNom]);
                
                // Update foreign keys in utilisateur table
                $stmtUser = $this->connect->prepare("
                    UPDATE utilisateur
                    SET id_section = ?
                    WHERE id_section = ?
                ");
                $stmtUser->execute([$nom, $oldNom]);
                
                // Delete old section
                $stmtDelete = $this->connect->prepare("DELETE FROM section WHERE nom = ?");
                $stmtDelete->execute([$oldNom]);
            } else {
                // Delete old section (will be re-inserted)
                $stmtDelete = $this->connect->prepare("DELETE FROM section WHERE nom = ?");
                $stmtDelete->execute([$oldNom]);
            }
            
            // Insert new/updated section
            $stmtInsert = $this->connect->prepare("
                INSERT INTO section
                (nom, specialite, `option`, promotion)
                VALUES (?, ?, ?, ?)
            ");
            $stmtInsert->execute([$nom, $specialite, $option, $promotion]);
            
            // Commit transaction
            $this->connect->commit();
            
            return true;
        } catch (PDOException $e) {
            // Rollback on error
            $this->connect->rollBack();
            throw new Exception("Erreur lors de la mise à jour de la section: " . $e->getMessage());
        }
    }
    
    // Delete section
    public function deleteSection($sectionId) {
        try {
            // Check if section has users
            $stmtUsers = $this->connect->prepare("SELECT COUNT(*) FROM utilisateur WHERE id_section = ?");
            $stmtUsers->execute([$sectionId]);
            if ($stmtUsers->fetchColumn() > 0) {
                throw new Exception("Impossible de supprimer cette section car des utilisateurs y sont associés");
            }
            
            // Check if section has subjects
            $stmtSubjects = $this->connect->prepare("SELECT COUNT(*) FROM matiere WHERE nom = ?");
            $stmtSubjects->execute([$sectionId]);
            if ($stmtSubjects->fetchColumn() > 0) {
                throw new Exception("Impossible de supprimer cette section car des matières y sont associées");
            }
            
            // Delete section
            $stmtDelete = $this->connect->prepare("DELETE FROM section WHERE nom = ?");
            $stmtDelete->execute([$sectionId]);
            
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression de la section: " . $e->getMessage());
        }
    }
}
?> 