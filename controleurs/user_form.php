<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Get all sections for the dropdown
try {
    $stmtSections = $connect->prepare("SELECT * FROM section ORDER BY nom ASC");
    $stmtSections->execute();
    $sections = $stmtSections->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des sections";
    $sections = [];
}

// Check if we're editing a user
$editMode = false;
$userData = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $userId = $_GET['id'];
    $editMode = true;
    
    try {
        $stmtUser = $connect->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
        $stmtUser->execute([$userId]);
        $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);
        
        if (!$userData) {
            $_SESSION['error_message'] = "Utilisateur non trouvé";
            header("Location: ../controleurs/admin_dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la récupération des informations de l'utilisateur";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
}

// Include user form view
include("../vues/ajouter_utilisateur.php");
?> 