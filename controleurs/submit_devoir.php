<?php
session_start();
include("../configurations/connexion.php");

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_travail = isset($_POST['id_travail']) ? intval($_POST['id_travail']) : 0;
    $id_utilisateur = $_SESSION['user_id'];
    $instructions = isset($_POST['instructions']) ? $_POST['instructions'] : '';
    $date_depot = date('Y-m-d');
    $fichier_depot = null;

    // Vérifier si l'ID du travail est valide
    if ($id_travail <= 0) {
        $_SESSION['error_message'] = "ID de travail invalide.";
        header("Location: ../vues/travail_a_faire_apprenti.php");
        exit;
    }

    // Vérifier si un fichier a été téléchargé
    if (isset($_FILES['fichier_depot']) && $_FILES['fichier_depot']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['fichier_depot']['tmp_name'];
        $file_name = $_FILES['fichier_depot']['name'];
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        
        // Générer un nom de fichier unique
        $unique_name = 'devoir_' . time() . '_' . uniqid() . '.' . $file_ext;
        $upload_dir = '../ressources/devoirs/';
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Déplacer le fichier
        if (move_uploaded_file($tmp_name, $upload_dir . $unique_name)) {
            $fichier_depot = $unique_name;
        } else {
            $_SESSION['error_message'] = "Erreur lors du téléchargement du fichier.";
            header("Location: ../vues/travail_a_faire_apprenti.php");
            exit;
        }
    }

    try {
        // Vérifier si un dépôt existe déjà pour ce travail et cet utilisateur
        $check_query = "SELECT id_depot FROM depot_devoir WHERE id_travail = ? AND id_utilisateur = ?";
        $check_stmt = $connect->prepare($check_query);
        $check_stmt->execute([$id_travail, $id_utilisateur]);
        
        if ($check_stmt->rowCount() > 0) {
            // Mise à jour d'un dépôt existant
            $depot = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $id_depot = $depot['id_depot'];
            
            // Récupérer l'ancien fichier pour le supprimer si un nouveau est téléchargé
            $old_file_query = "SELECT fichier_depot FROM depot_devoir WHERE id_depot = ?";
            $old_file_stmt = $connect->prepare($old_file_query);
            $old_file_stmt->execute([$id_depot]);
            $old_depot = $old_file_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($fichier_depot !== null) {
                // Supprimer l'ancien fichier s'il existe
                if (!empty($old_depot['fichier_depot'])) {
                    $old_file_path = '../ressources/devoirs/' . $old_depot['fichier_depot'];
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                }
                $update_query = "UPDATE depot_devoir SET instructions = ?, date_depot = ?, fichier_depot = ? WHERE id_depot = ?";
                $params = [$instructions, $date_depot, $fichier_depot, $id_depot];
            } else {
                // Pas de nouveau fichier, garder l'ancien
                $update_query = "UPDATE depot_devoir SET instructions = ?, date_depot = ? WHERE id_depot = ?";
                $params = [$instructions, $date_depot, $id_depot];
            }
            
            $update_stmt = $connect->prepare($update_query);
            $update_stmt->execute($params);
            
            $_SESSION['success_message'] = "Votre travail a été mis à jour avec succès.";
        } else {
            // Création d'un nouveau dépôt
            $insert_query = "INSERT INTO depot_devoir (id_travail, instructions, date_depot, id_utilisateur, fichier_depot) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $connect->prepare($insert_query);
            $insert_stmt->execute([$id_travail, $instructions, $date_depot, $id_utilisateur, $fichier_depot]);
            
            $_SESSION['success_message'] = "Votre travail a été déposé avec succès.";
        }
        
        header("Location: ../vues/travail_a_faire_apprenti.php");
        exit;
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors du dépôt du travail: " . $e->getMessage();
        header("Location: ../vues/travail_a_faire_apprenti.php");
        exit;
    }
} else {
    header("Location: ../vues/travail_a_faire_apprenti.php");
    exit;
}
?> 