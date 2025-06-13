<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if subject ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Identifiant de la matière non spécifié";
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

// Get subject ID
$subjectId = intval($_GET['id']);

try {
    // Check if subject exists
    $stmtSubject = $connect->prepare("SELECT * FROM matiere WHERE id_matiere = ?");
    $stmtSubject->execute([$subjectId]);
    $subject = $stmtSubject->fetch(PDO::FETCH_ASSOC);
    
    if (!$subject) {
        $_SESSION['error_message'] = "Matière non trouvée";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Check if subject has relations
    $stmtCheckAnimer = $connect->prepare("SELECT COUNT(*) FROM animer WHERE id_matiere = ?");
    $stmtCheckAnimer->execute([$subjectId]);
    if ($stmtCheckAnimer->fetchColumn() > 0) {
        $_SESSION['error_message'] = "Impossible de supprimer cette matière car elle est associée à des formateurs";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    $stmtCheckInscrire = $connect->prepare("SELECT COUNT(*) FROM inscrire WHERE id_matiere = ?");
    $stmtCheckInscrire->execute([$subjectId]);
    if ($stmtCheckInscrire->fetchColumn() > 0) {
        $_SESSION['error_message'] = "Impossible de supprimer cette matière car des élèves y sont inscrits";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Delete subject
    $stmtDelete = $connect->prepare("DELETE FROM matiere WHERE id_matiere = ?");
    $stmtDelete->execute([$subjectId]);
    
    $_SESSION['success_message'] = "Matière supprimée avec succès";
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression de la matière: " . $e->getMessage();
}

// Redirect back to admin dashboard
header("Location: ../controleurs/admin_dashboard.php");
exit();
?> 