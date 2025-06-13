<?php
class Matiere {
    private $connect;
    
    public function __construct($connect) {
        $this->connect = $connect;
    }
    
    // Get all subjects
    public function getAllSubjects() {
        try {
            $stmt = $this->connect->prepare("
                SELECT m.*, s.specialite as section_specialite
                FROM matiere m
                JOIN section s ON m.nom = s.nom
                ORDER BY m.nom_matiere ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des matières: " . $e->getMessage());
        }
    }
    
    // Get subject by ID
    public function getSubjectById($subjectId) {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM matiere WHERE id_matiere = ?");
            $stmt->execute([$subjectId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de la matière: " . $e->getMessage());
        }
    }
    
    // Get subjects for a section
    public function getSubjectsBySection($sectionId) {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM matiere WHERE nom = ? ORDER BY nom_matiere ASC");
            $stmt->execute([$sectionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des matières par section: " . $e->getMessage());
        }
    }
    
    // Get subjects for a user (student)
    public function getSubjectsForStudent($userId) {
        try {
            $stmt = $this->connect->prepare("
                SELECT m.* 
                FROM matiere m
                JOIN inscrire i ON m.id_matiere = i.id_matiere
                WHERE i.id_utilisateur = ?
                ORDER BY m.nom_matiere ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des matières de l'élève: " . $e->getMessage());
        }
    }
    
    // Get subjects taught by a user (teacher)
    public function getSubjectsForTeacher($userId) {
        try {
            $stmt = $this->connect->prepare("
                SELECT m.* 
                FROM matiere m
                JOIN animer a ON m.id_matiere = a.id_matiere
                WHERE a.id_utilisateur = ?
                ORDER BY m.nom_matiere ASC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des matières du formateur: " . $e->getMessage());
        }
    }
    
    // Add new subject
    public function addSubject($nomMatiere, $promoClasse, $sectionClasse) {
        try {
            // Verify section exists
            $stmtSection = $this->connect->prepare("SELECT * FROM section WHERE nom = ?");
            $stmtSection->execute([$sectionClasse]);
            $section = $stmtSection->fetch(PDO::FETCH_ASSOC);
            
            if (!$section) {
                throw new Exception("La section spécifiée n'existe pas");
            }
            
            $stmt = $this->connect->prepare("
                INSERT INTO matiere 
                (nom_matiere, promo_classe, section_classe, nom) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$nomMatiere, $promoClasse, $sectionClasse, $sectionClasse]);
            return $this->connect->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'ajout de la matière: " . $e->getMessage());
        }
    }
    
    // Update subject
    public function updateSubject($subjectId, $nomMatiere, $promoClasse, $sectionClasse) {
        try {
            // Verify section exists
            $stmtSection = $this->connect->prepare("SELECT * FROM section WHERE nom = ?");
            $stmtSection->execute([$sectionClasse]);
            $section = $stmtSection->fetch(PDO::FETCH_ASSOC);
            
            if (!$section) {
                throw new Exception("La section spécifiée n'existe pas");
            }
            
            $stmt = $this->connect->prepare("
                UPDATE matiere 
                SET nom_matiere = ?, 
                    promo_classe = ?, 
                    section_classe = ?,
                    nom = ?
                WHERE id_matiere = ?
            ");
            $stmt->execute([$nomMatiere, $promoClasse, $sectionClasse, $sectionClasse, $subjectId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour de la matière: " . $e->getMessage());
        }
    }
    
    // Delete subject
    public function deleteSubject($subjectId) {
        try {
            // Check if subject has relations
            $stmtAnimer = $this->connect->prepare("SELECT COUNT(*) FROM animer WHERE id_matiere = ?");
            $stmtAnimer->execute([$subjectId]);
            if ($stmtAnimer->fetchColumn() > 0) {
                throw new Exception("Impossible de supprimer cette matière car elle est associée à des formateurs");
            }
            
            $stmtInscrire = $this->connect->prepare("SELECT COUNT(*) FROM inscrire WHERE id_matiere = ?");
            $stmtInscrire->execute([$subjectId]);
            if ($stmtInscrire->fetchColumn() > 0) {
                throw new Exception("Impossible de supprimer cette matière car des élèves y sont inscrits");
            }
            
            // Delete subject
            $stmt = $this->connect->prepare("DELETE FROM matiere WHERE id_matiere = ?");
            $stmt->execute([$subjectId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression de la matière: " . $e->getMessage());
        }
    }

    public function isStudentEnrolled($studentId, $subjectId) {
        try {
            $stmt = $this->connect->prepare("SELECT COUNT(*) as count FROM inscrire WHERE id_utilisateur = ? AND id_matiere = ?");
            $stmt->execute([$studentId, $subjectId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la vérification de l'inscription: " . $e->getMessage());
        }
    }

    public function enrollStudent($studentId, $subjectId) {
        try {
            $stmt = $this->connect->prepare("INSERT INTO inscrire (id_utilisateur, id_matiere) VALUES (?, ?)");
            return $stmt->execute([$studentId, $subjectId]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'inscription de l'étudiant: " . $e->getMessage());
        }
    }

    public function unenrollStudent($subjectId, $studentId) {
        try {
            $stmt = $this->connect->prepare("DELETE FROM inscrire WHERE id_utilisateur = ? AND id_matiere = ?");
            return $stmt->execute([$studentId, $subjectId]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la désinscription de l'étudiant: " . $e->getMessage());
        }
    }

    public function isTeacherAssigned($teacherId, $subjectId) {
        try {
            $stmt = $this->connect->prepare("SELECT COUNT(*) as count FROM animer WHERE id_utilisateur = ? AND id_matiere = ?");
            $stmt->execute([$teacherId, $subjectId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la vérification de l'assignation: " . $e->getMessage());
        }
    }

    public function assignTeacher($teacherId, $subjectId) {
        try {
            $stmt = $this->connect->prepare("INSERT INTO animer (id_utilisateur, id_matiere) VALUES (?, ?)");
            return $stmt->execute([$teacherId, $subjectId]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'assignation du formateur: " . $e->getMessage());
        }
    }

    public function removeTeacher($teacherId, $subjectId) {
        try {
            $stmt = $this->connect->prepare("DELETE FROM animer WHERE id_utilisateur = ? AND id_matiere = ?");
            return $stmt->execute([$teacherId, $subjectId]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression du formateur: " . $e->getMessage());
        }
    }
}
?> 