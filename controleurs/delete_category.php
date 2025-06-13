<?php
session_start();
include("../configurations/connexion.php");
include("../modeles/Document.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID de catégorie non fourni";
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

$categoryId = intval($_GET['id']);

try {
    // Initialize Document model to use its methods
    $documentModel = new Document($connect);
    
    // Check if category has documents associated with it
    $hasDocuments = $documentModel->categoryHasDocuments($categoryId);
    
    if ($hasDocuments) {
        $_SESSION['error_message'] = "Impossible de supprimer cette catégorie car elle contient des documents. Veuillez d'abord supprimer ou déplacer ces documents.";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Delete the category
    $stmt = $connect->prepare("DELETE FROM categorie_document WHERE id_categorie = ?");
    $result = $stmt->execute([$categoryId]);
    
    if ($result) {
        $_SESSION['success_message'] = "Catégorie supprimée avec succès";
    } else {
        $_SESSION['error_message'] = "Erreur lors de la suppression de la catégorie";
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur de base de données: " . $e->getMessage();
}

header("Location: ../controleurs/admin_dashboard.php");
exit();
?> 