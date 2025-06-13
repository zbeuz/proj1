<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Vérifier si l'utilisateur est un apprenti (role = 2)
if ($_SESSION['user_role'] != 2) {
    $_SESSION['error_message'] = "Accès non autorisé.";
    header("Location: ../vues/travail_a_faire_apprenti.php");
    exit;
}

require_once("../configurations/connexion.php");

// Vérifier que les données POST sont présentes
if (!isset($_POST['id_depot']) || !isset($_POST['id_travail'])) {
    $_SESSION['error_message'] = "Données manquantes pour la suppression.";
    header("Location: ../vues/travail_a_faire_apprenti.php");
    exit;
}

$id_depot = intval($_POST['id_depot']);
$id_travail = intval($_POST['id_travail']);
$id_utilisateur = $_SESSION['user_id'];

try {
    // Vérifier que le dépôt appartient bien à l'utilisateur connecté
    $stmt_verify = $connect->prepare("
        SELECT d.fichier_depot, t.date_fin 
        FROM depot_devoir d 
        JOIN travail_a_faire t ON d.id_travail = t.id_travail
        WHERE d.id_depot = ? AND d.id_utilisateur = ?
    ");
    $stmt_verify->execute([$id_depot, $id_utilisateur]);
    $depot = $stmt_verify->fetch(PDO::FETCH_ASSOC);
    
    if (!$depot) {
        $_SESSION['error_message'] = "Dépôt non trouvé ou vous n'êtes pas autorisé à le supprimer.";
        header("Location: ../vues/travail_a_faire_apprenti.php");
        exit;
    }
    
    // Vérifier que la date limite n'est pas dépassée
    if (strtotime($depot['date_fin']) < strtotime('today')) {
        $_SESSION['error_message'] = "Impossible de supprimer le dépôt : la date limite est dépassée.";
        header("Location: ../vues/travail_a_faire_apprenti.php");
        exit;
    }
    
    // Supprimer le fichier physique s'il existe
    if (!empty($depot['fichier_depot'])) {
        $file_path = "../ressources/devoirs/" . $depot['fichier_depot'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    // Supprimer l'enregistrement de la base de données
    $stmt_delete = $connect->prepare("DELETE FROM depot_devoir WHERE id_depot = ? AND id_utilisateur = ?");
    $stmt_delete->execute([$id_depot, $id_utilisateur]);
    
    if ($stmt_delete->rowCount() > 0) {
        $_SESSION['success_message'] = "Votre dépôt a été supprimé avec succès. Vous pouvez maintenant en déposer un nouveau.";
    } else {
        $_SESSION['error_message'] = "Erreur lors de la suppression du dépôt.";
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression : " . $e->getMessage();
}

// Rediriger vers la page des travaux
header("Location: ../vues/travail_a_faire_apprenti.php");
exit;
?> 