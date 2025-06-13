<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

require_once("../configurations/connexion.php");

$id_utilisateur = $_SESSION['user_id'];
$id_travail = isset($_GET['id_travail']) ? intval($_GET['id_travail']) : 0;

if ($id_travail <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID travail invalide']);
    exit;
}

try {
    // Récupérer les données du dépôt existant
    $query = "SELECT instructions, fichier_depot FROM depot_devoir 
              WHERE id_utilisateur = :id_utilisateur AND id_travail = :id_travail";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
    $stmt->bindParam(':id_travail', $id_travail, PDO::PARAM_INT);
    $stmt->execute();
    
    $depot = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($depot) {
        echo json_encode([
            'success' => true,
            'instructions' => $depot['instructions'],
            'fichier_depot' => $depot['fichier_depot']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Aucun dépôt trouvé']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur base de données: ' . $e->getMessage()]);
}
?> 