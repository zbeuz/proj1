<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Vérifier si l'utilisateur est un apprenti (role = 2)
if ($_SESSION['user_role'] != 2) {
    header("Location: ../index.php");
    exit;
}

require_once("../configurations/connexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_utilisateur = $_SESSION['user_id'];
    $id_travail = isset($_POST['id_travail']) ? intval($_POST['id_travail']) : 0;
    $instructions = isset($_POST['instructions']) ? trim($_POST['instructions']) : '';
    
    if ($id_travail <= 0) {
        $_SESSION['error_message'] = "ID de travail invalide.";
        header("Location: ../vues/travail_a_faire_apprenti.php");
        exit;
    }
    
    try {
        // Vérifier que le dépôt existe et appartient à l'utilisateur
        $check_query = "SELECT d.*, t.date_fin FROM depot_devoir d
                       JOIN travail_a_faire t ON d.id_travail = t.id_travail
                       WHERE d.id_utilisateur = :id_utilisateur AND d.id_travail = :id_travail";
        
        $check_stmt = $connect->prepare($check_query);
        $check_stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $check_stmt->bindParam(':id_travail', $id_travail, PDO::PARAM_INT);
        $check_stmt->execute();
        
        $depot_existant = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$depot_existant) {
            $_SESSION['error_message'] = "Aucun dépôt trouvé pour ce travail.";
            header("Location: ../vues/travail_a_faire_apprenti.php");
            exit;
        }
        
        // Vérifier que la date limite n'est pas dépassée
        if (strtotime($depot_existant['date_fin']) < strtotime('today')) {
            $_SESSION['error_message'] = "Impossible de modifier : la date limite est dépassée.";
            header("Location: ../vues/travail_a_faire_apprenti.php");
            exit;
        }
        
        $fichier_depot = $depot_existant['fichier_depot']; // Conserver le fichier existant par défaut
        
        // Traitement du nouveau fichier s'il est fourni
        if (isset($_FILES['fichier_depot']) && $_FILES['fichier_depot']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../ressources/depots/';
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Supprimer l'ancien fichier s'il existe
            if ($fichier_depot && file_exists($upload_dir . $fichier_depot)) {
                unlink($upload_dir . $fichier_depot);
            }
            
            // Générer un nom unique pour le nouveau fichier
            $file_extension = pathinfo($_FILES['fichier_depot']['name'], PATHINFO_EXTENSION);
            $new_filename = 'depot_' . $id_travail . '_' . $id_utilisateur . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['fichier_depot']['tmp_name'], $upload_path)) {
                $fichier_depot = $new_filename;
            } else {
                $_SESSION['error_message'] = "Erreur lors du téléchargement du fichier.";
                header("Location: ../vues/travail_a_faire_apprenti.php");
                exit;
            }
        }
        
        // Mettre à jour le dépôt
        $update_query = "UPDATE depot_devoir 
                        SET instructions = :instructions, 
                            fichier_depot = :fichier_depot,
                            date_depot = NOW()
                        WHERE id_utilisateur = :id_utilisateur AND id_travail = :id_travail";
        
        $update_stmt = $connect->prepare($update_query);
        $update_stmt->bindParam(':instructions', $instructions, PDO::PARAM_STR);
        $update_stmt->bindParam(':fichier_depot', $fichier_depot, PDO::PARAM_STR);
        $update_stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $update_stmt->bindParam(':id_travail', $id_travail, PDO::PARAM_INT);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Votre dépôt a été modifié avec succès.";
        } else {
            $_SESSION['error_message'] = "Erreur lors de la modification du dépôt.";
        }
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur de base de données : " . $e->getMessage();
    }
    
    header("Location: ../vues/travail_a_faire_apprenti.php");
    exit;
    
} else {
    // Redirection si la méthode n'est pas POST
    header("Location: ../vues/travail_a_faire_apprenti.php");
    exit;
}
?> 