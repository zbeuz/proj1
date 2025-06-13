<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if section ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Identifiant de la section non spécifié";
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

// Get section ID
$sectionId = trim($_GET['id']);

try {
    // Check if section exists
    $stmtSection = $connect->prepare("SELECT * FROM section WHERE nom = ?");
    $stmtSection->execute([$sectionId]);
    $section = $stmtSection->fetch(PDO::FETCH_ASSOC);
    
    if (!$section) {
        $_SESSION['error_message'] = "Section non trouvée";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Check if section has users
    $stmtCheckUsers = $connect->prepare("SELECT COUNT(*) FROM utilisateur WHERE id_section = ?");
    $stmtCheckUsers->execute([$sectionId]);
    if ($stmtCheckUsers->fetchColumn() > 0) {
        $_SESSION['error_message'] = "Impossible de supprimer cette section car des utilisateurs y sont associés";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Check if section has subjects
    $stmtCheckSubjects = $connect->prepare("SELECT COUNT(*) FROM matiere WHERE nom = ?");
    $stmtCheckSubjects->execute([$sectionId]);
    if ($stmtCheckSubjects->fetchColumn() > 0) {
        $_SESSION['error_message'] = "Impossible de supprimer cette section car des matières y sont associées";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Delete section
    $stmtDelete = $connect->prepare("DELETE FROM section WHERE nom = ?");
    $stmtDelete->execute([$sectionId]);
    
    $_SESSION['success_message'] = "Section supprimée avec succès";
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression de la section: " . $e->getMessage();
}

// Redirect back to admin dashboard
header("Location: ../controleurs/admin_dashboard.php");
exit();
?> 