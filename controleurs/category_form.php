<?php
session_start();
include("../configurations/connexion.php");
include("../modeles/Document.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Initialize document model
$documentModel = new Document($connect);

// Check if we're editing a category
$editMode = false;
$categoryData = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $categoryId = intval($_GET['id']);
    $editMode = true;
    
    try {
        // Get category data
        $stmtCategory = $connect->prepare("SELECT * FROM categorie_document WHERE id_categorie = ?");
        $stmtCategory->execute([$categoryId]);
        $categoryData = $stmtCategory->fetch(PDO::FETCH_ASSOC);
        
        if (!$categoryData) {
            $_SESSION['error_message'] = "Catégorie non trouvée";
            header("Location: ../controleurs/admin_dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la récupération des informations de la catégorie";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
}

// Get all categories for reference
try {
    $categories = $documentModel->getAllCategories();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des catégories";
    $categories = [];
}

// Include category form view
include("../vues/ajouter_categorie.php");
?> 