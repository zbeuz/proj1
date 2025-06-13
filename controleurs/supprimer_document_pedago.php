<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Vérifier si l'utilisateur est un formateur (role = 1)
if ($_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit;
}

// Vérifier si l'ID du document est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID de document non spécifié.";
    header("Location: ../vues/documents_pedagogiques.php");
    exit;
}

include("../configurations/connexion.php");

$document_id = $_GET['id'];

try {
    // Récupérer les informations sur le document (notamment le nom du fichier)
    $query_doc = "SELECT * FROM document_pedago WHERE id_document_pedago = ? AND id_utilisateur_deposer = ?";
    $stmt_doc = $connect->prepare($query_doc);
    $stmt_doc->execute([$document_id, $_SESSION['user_id']]);
    
    if ($stmt_doc->rowCount() == 0) {
        $_SESSION['error_message'] = "Document introuvable ou vous n'êtes pas autorisé à le supprimer.";
        header("Location: ../vues/documents_pedagogiques.php");
        exit;
    }
    
    $document = $stmt_doc->fetch(PDO::FETCH_ASSOC);
    $file_path = "../ressources/document/" . $document['fichier_document'];
    
    // Supprimer le document de la base de données
    $query_delete = "DELETE FROM document_pedago WHERE id_document_pedago = ?";
    $stmt_delete = $connect->prepare($query_delete);
    $stmt_delete->execute([$document_id]);
    
    // Supprimer le fichier physique
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    $_SESSION['success_message'] = "Document pédagogique supprimé avec succès.";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression du document : " . $e->getMessage();
}

header("Location: ../vues/documents_pedagogiques.php");
exit;
?> 