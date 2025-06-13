<?php
session_start();
include("../configurations/connexion.php");
include("../modeles/Document.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize document model
$documentModel = new Document($connect);

// Handle search
$searchResults = [];
$searchTerm = "";

if (isset($_GET['query']) && !empty($_GET['query'])) {
    $searchTerm = trim($_GET['query']);
    
    try {
        $searchResults = $documentModel->searchDocumentsByTitle($searchTerm);
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur lors de la recherche: " . $e->getMessage();
    }
}

// Get all categories for filter
try {
    $categories = $documentModel->getAllCategories();
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des catégories: " . $e->getMessage();
    $categories = [];
}

// Include search results view
include("../vues/resultats_recherche.php");
?> 