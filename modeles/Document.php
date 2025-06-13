<?php
class Document {
    private $connect;
    
    public function __construct($connect) {
        $this->connect = $connect;
    }
    
    // Récupérer tous les documents (techniques et pédagogiques)
    public function getAllDocuments() {
        try {
            // Récupération des documents techniques
            $stmtTechnical = $this->connect->prepare("
                SELECT 
                    dt.id_document_technique as id,
                    dt.nom_document,
                    dt.fichier_document,
                    dt.date_depot,
                    'technique' as type_document,
                    s.nom_systeme,
                    s.id_systeme,
                    c.nom_categorie,
                    u1.nom_utilisateur as nom_deposant,
                    u1.prenom_utilisateur as prenom_deposant,
                    u2.nom_utilisateur as nom_modificateur,
                    u2.prenom_utilisateur as prenom_modificateur,
                    dt.date_modification
                FROM document_technique dt
                JOIN systeme s ON dt.id_systeme = s.id_systeme
                JOIN categorie_document c ON dt.id_categorie = c.id_categorie
                JOIN utilisateur u1 ON dt.id_utilisateur = u1.id_utilisateur
                JOIN utilisateur u2 ON dt.id_utilisateur_actualiser = u2.id_utilisateur
                ORDER BY dt.date_depot DESC
            ");
            $stmtTechnical->execute();
            $technicalDocs = $stmtTechnical->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupération des documents pédagogiques
            $stmtPedagogical = $this->connect->prepare("
                SELECT 
                    dp.id_document_pedago as id,
                    dp.nom_document,
                    dp.fichier_document,
                    dp.date_depot,
                    'pedagogique' as type_document,
                    s.nom_systeme,
                    s.id_systeme,
                    c.nom_categorie,
                    u1.nom_utilisateur as nom_deposant,
                    u1.prenom_utilisateur as prenom_deposant,
                    u2.nom_utilisateur as nom_modificateur,
                    u2.prenom_utilisateur as prenom_modificateur,
                    dp.date_limite_rendu_devoir as date_modification
                FROM document_pedago dp
                JOIN systeme s ON dp.id_systeme = s.id_systeme
                JOIN categorie_document c ON dp.id_categorie = c.id_categorie
                JOIN utilisateur u1 ON dp.id_utilisateur = u1.id_utilisateur
                JOIN utilisateur u2 ON dp.id_utilisateur_deposer = u2.id_utilisateur
                ORDER BY dp.date_depot DESC
            ");
            $stmtPedagogical->execute();
            $pedagogicalDocs = $stmtPedagogical->fetchAll(PDO::FETCH_ASSOC);
            
            // Fusion et tri des résultats
            $allDocuments = array_merge($technicalDocs, $pedagogicalDocs);
            usort($allDocuments, function($a, $b) {
                return strtotime($b['date_depot']) - strtotime($a['date_depot']);
            });
            
            return $allDocuments;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des documents: " . $e->getMessage());
        }
    }
    
    // Récupérer les documents par système
    public function getDocumentsBySystem($systemId) {
        try {
            // Récupération des documents techniques pour ce système
            $stmtTechnical = $this->connect->prepare("
                SELECT 
                    dt.id_document_technique as id,
                    dt.nom_document,
                    dt.fichier_document,
                    dt.date_depot,
                    'technique' as type_document,
                    s.nom_systeme,
                    c.nom_categorie,
                    u1.nom_utilisateur as nom_deposant,
                    u1.prenom_utilisateur as prenom_deposant
                FROM document_technique dt
                JOIN systeme s ON dt.id_systeme = s.id_systeme
                JOIN categorie_document c ON dt.id_categorie = c.id_categorie
                JOIN utilisateur u1 ON dt.id_utilisateur = u1.id_utilisateur
                WHERE dt.id_systeme = ?
                ORDER BY dt.date_depot DESC
            ");
            $stmtTechnical->execute([$systemId]);
            $technicalDocs = $stmtTechnical->fetchAll(PDO::FETCH_ASSOC);
            
            // Récupération des documents pédagogiques pour ce système
            $stmtPedagogical = $this->connect->prepare("
                SELECT 
                    dp.id_document_pedago as id,
                    dp.nom_document,
                    dp.fichier_document,
                    dp.date_depot,
                    'pedagogique' as type_document,
                    s.nom_systeme,
                    c.nom_categorie,
                    u1.nom_utilisateur as nom_deposant,
                    u1.prenom_utilisateur as prenom_deposant
                FROM document_pedago dp
                JOIN systeme s ON dp.id_systeme = s.id_systeme
                JOIN categorie_document c ON dp.id_categorie = c.id_categorie
                JOIN utilisateur u1 ON dp.id_utilisateur = u1.id_utilisateur
                WHERE dp.id_systeme = ?
                ORDER BY dp.date_depot DESC
            ");
            $stmtPedagogical->execute([$systemId]);
            $pedagogicalDocs = $stmtPedagogical->fetchAll(PDO::FETCH_ASSOC);
            
            // Fusion et tri des résultats
            $allDocuments = array_merge($technicalDocs, $pedagogicalDocs);
            usort($allDocuments, function($a, $b) {
                return strtotime($b['date_depot']) - strtotime($a['date_depot']);
            });
            
            return $allDocuments;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des documents par système: " . $e->getMessage());
        }
    }
    
    // Rechercher des documents par titre
    public function searchDocumentsByTitle($searchTerm) {
        try {
            $searchTerm = '%' . $searchTerm . '%';
            
            // Recherche dans les documents techniques
            $stmtTechnical = $this->connect->prepare("
                SELECT 
                    dt.id_document_technique as id,
                    dt.nom_document,
                    dt.fichier_document,
                    dt.date_depot,
                    'technique' as type_document,
                    s.nom_systeme,
                    s.id_systeme,
                    c.nom_categorie,
                    u1.nom_utilisateur,
                    u1.prenom_utilisateur
                FROM document_technique dt
                JOIN systeme s ON dt.id_systeme = s.id_systeme
                JOIN categorie_document c ON dt.id_categorie = c.id_categorie
                JOIN utilisateur u1 ON dt.id_utilisateur = u1.id_utilisateur
                WHERE dt.nom_document LIKE ? OR s.nom_systeme LIKE ?
                ORDER BY dt.date_depot DESC
            ");
            $stmtTechnical->execute([$searchTerm, $searchTerm]);
            $technicalDocs = $stmtTechnical->fetchAll(PDO::FETCH_ASSOC);
            
            // Recherche dans les documents pédagogiques
            $stmtPedagogical = $this->connect->prepare("
                SELECT 
                    dp.id_document_pedago as id,
                    dp.nom_document,
                    dp.fichier_document,
                    dp.date_depot,
                    'pedagogique' as type_document,
                    s.nom_systeme,
                    s.id_systeme,
                    c.nom_categorie,
                    u1.nom_utilisateur,
                    u1.prenom_utilisateur
                FROM document_pedago dp
                JOIN systeme s ON dp.id_systeme = s.id_systeme
                JOIN categorie_document c ON dp.id_categorie = c.id_categorie
                JOIN utilisateur u1 ON dp.id_utilisateur = u1.id_utilisateur
                WHERE dp.nom_document LIKE ? OR s.nom_systeme LIKE ?
                ORDER BY dp.date_depot DESC
            ");
            $stmtPedagogical->execute([$searchTerm, $searchTerm]);
            $pedagogicalDocs = $stmtPedagogical->fetchAll(PDO::FETCH_ASSOC);
            
            // Fusion et tri des résultats
            $allDocuments = array_merge($technicalDocs, $pedagogicalDocs);
            usort($allDocuments, function($a, $b) {
                return strtotime($b['date_depot']) - strtotime($a['date_depot']);
            });
            
            return $allDocuments;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la recherche de documents: " . $e->getMessage());
        }
    }

    public function getDocumentById($documentId, $documentType = null) {
        try {
            if (!$documentType) {
                // Essayer de déterminer le type
                $stmtCheck1 = $this->connect->prepare("SELECT COUNT(*) FROM document_technique WHERE id_document_technique = ?");
                $stmtCheck1->execute([$documentId]);
                
                if ($stmtCheck1->fetchColumn() > 0) {
                    $documentType = 'technique';
                } else {
                    $documentType = 'pedagogique';
                }
            }
            
            if ($documentType == 'technique') {
                $stmt = $this->connect->prepare("
                    SELECT 
                        dt.*,
                        s.nom_systeme,
                        c.nom_categorie,
                        u1.nom_utilisateur as deposant_nom,
                        u1.prenom_utilisateur as deposant_prenom,
                        u2.nom_utilisateur as modificateur_nom,
                        u2.prenom_utilisateur as modificateur_prenom
                    FROM document_technique dt
                    JOIN systeme s ON dt.id_systeme = s.id_systeme
                    JOIN categorie_document c ON dt.id_categorie = c.id_categorie
                    JOIN utilisateur u1 ON dt.id_utilisateur = u1.id_utilisateur
                    JOIN utilisateur u2 ON dt.id_utilisateur_actualiser = u2.id_utilisateur
                    WHERE dt.id_document_technique = ?
                ");
                $stmt->execute([$documentId]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $stmt = $this->connect->prepare("
                    SELECT 
                        dp.*,
                        s.nom_systeme,
                        c.nom_categorie,
                        u1.nom_utilisateur as deposant_nom,
                        u1.prenom_utilisateur as deposant_prenom,
                        u2.nom_utilisateur as modificateur_nom,
                        u2.prenom_utilisateur as modificateur_prenom
                    FROM document_pedago dp
                    JOIN systeme s ON dp.id_systeme = s.id_systeme
                    JOIN categorie_document c ON dp.id_categorie = c.id_categorie
                    JOIN utilisateur u1 ON dp.id_utilisateur = u1.id_utilisateur
                    JOIN utilisateur u2 ON dp.id_utilisateur_deposer = u2.id_utilisateur
                    WHERE dp.id_document_pedago = ?
                ");
                $stmt->execute([$documentId]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du document: " . $e->getMessage());
        }
    }

    public function categoryHasDocuments($categoryId) {
        try {
            // Vérifier dans les documents techniques
            $stmt1 = $this->connect->prepare("SELECT COUNT(*) as count FROM document_technique WHERE id_categorie = ?");
            $stmt1->execute([$categoryId]);
            $countTechnical = $stmt1->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Vérifier dans les documents pédagogiques
            $stmt2 = $this->connect->prepare("SELECT COUNT(*) as count FROM document_pedago WHERE id_categorie = ?");
            $stmt2->execute([$categoryId]);
            $countPedagogical = $stmt2->fetch(PDO::FETCH_ASSOC)['count'];
            
            return ($countTechnical + $countPedagogical) > 0;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la vérification des documents associés à la catégorie: " . $e->getMessage());
        }
    }

    public function incrementViewCount($documentId, $documentType) {
        try {
            if ($documentType == 'technique') {
                $stmt = $this->connect->prepare("
                    UPDATE document_technique 
                    SET vues = IFNULL(vues, 0) + 1 
                    WHERE id_document_technique = ?
                ");
            } else {
                $stmt = $this->connect->prepare("
                    UPDATE document_pedago 
                    SET vues = IFNULL(vues, 0) + 1 
                    WHERE id_document_pedago = ?
                ");
            }
            return $stmt->execute([$documentId]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'incrémentation du nombre de vues: " . $e->getMessage());
        }
    }

    public function incrementDownloadCount($documentId, $documentType) {
        try {
            if ($documentType == 'technique') {
                $stmt = $this->connect->prepare("
                    UPDATE document_technique 
                    SET telechargements = IFNULL(telechargements, 0) + 1 
                    WHERE id_document_technique = ?
                ");
            } else {
                $stmt = $this->connect->prepare("
                    UPDATE document_pedago 
                    SET telechargements = IFNULL(telechargements, 0) + 1 
                    WHERE id_document_pedago = ?
                ");
            }
            return $stmt->execute([$documentId]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de l'incrémentation du nombre de téléchargements: " . $e->getMessage());
        }
    }

    public function getCategoryById($categoryId) {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM categorie_document WHERE id_categorie = ?");
            $stmt->execute([$categoryId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération de la catégorie: " . $e->getMessage());
        }
    }
    
    public function getSystemById($systemId) {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM systeme WHERE id_systeme = ?");
            $stmt->execute([$systemId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération du système: " . $e->getMessage());
        }
    }
    
    public function getAllCategories() {
        try {
            $stmt = $this->connect->prepare("SELECT * FROM categorie_document ORDER BY nom_categorie ASC");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la récupération des catégories: " . $e->getMessage());
        }
    }

    public function teacherHasAccessToDocument($teacherId, $documentId, $documentType) {
        try {
            // Trouver le système associé au document
            if ($documentType == 'technique') {
                $stmtDoc = $this->connect->prepare("SELECT id_systeme FROM document_technique WHERE id_document_technique = ?");
            } else {
                $stmtDoc = $this->connect->prepare("SELECT id_systeme FROM document_pedago WHERE id_document_pedago = ?");
            }
            $stmtDoc->execute([$documentId]);
            $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);
            
            if (!$doc) {
                return false;
            }
            
            // Vérifier si le formateur est associé à une matière liée à ce système
            $stmt = $this->connect->prepare("
                SELECT COUNT(*) as count
                FROM animer a
                JOIN matiere m ON a.id_matiere = m.id_matiere
                WHERE a.id_utilisateur = ? AND m.id_systeme = ?
            ");
            $stmt->execute([$teacherId, $doc['id_systeme']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la vérification de l'accès du formateur: " . $e->getMessage());
        }
    }

    public function studentHasAccessToDocument($studentId, $documentId, $documentType) {
        try {
            // Trouver le système associé au document
            if ($documentType == 'technique') {
                $stmtDoc = $this->connect->prepare("SELECT id_systeme FROM document_technique WHERE id_document_technique = ?");
            } else {
                $stmtDoc = $this->connect->prepare("SELECT id_systeme FROM document_pedago WHERE id_document_pedago = ?");
            }
            $stmtDoc->execute([$documentId]);
            $doc = $stmtDoc->fetch(PDO::FETCH_ASSOC);
            
            if (!$doc) {
                return false;
            }
            
            // Vérifier si l'étudiant est inscrit à une matière liée à ce système
            $stmt = $this->connect->prepare("
                SELECT COUNT(*) as count
                FROM inscrire i
                JOIN matiere m ON i.id_matiere = m.id_matiere
                WHERE i.id_utilisateur = ? AND m.id_systeme = ?
            ");
            $stmt->execute([$studentId, $doc['id_systeme']]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['count'] > 0;
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la vérification de l'accès de l'étudiant: " . $e->getMessage());
        }
    }
}
?> 