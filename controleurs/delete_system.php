<?php
session_start();
include("../configurations/connexion.php");
include("../modeles/Systeme.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if system ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Identifiant du système non spécifié";
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

// Initialize system model
$systemModel = new Systeme($connect);

// Get system ID
$systemId = intval($_GET['id']);

try {
    // Check if system exists
    $system = $systemModel->getSystemById($systemId);
    if (!$system) {
        $_SESSION['error_message'] = "Système non trouvé";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Delete system (model already checks for documents)
    $systemModel->deleteSystem($systemId);
    
    // If has a photo that's not the default, delete it
    if ($system['photo_systeme'] != 'default_system.jpg') {
        $photoPath = "../ressources/images/" . $system['photo_systeme'];
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }
    }
    
    $_SESSION['success_message'] = "Système supprimé avec succès";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression du système: " . $e->getMessage();
}

// Redirect back to admin dashboard
header("Location: ../controleurs/admin_dashboard.php");
exit();
?> 