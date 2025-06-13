<?php
session_start();
include("../configurations/connexion.php");
include("../modeles/Matiere.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if all required parameters are provided
if (!isset($_GET['student_id']) || !isset($_GET['subject_id'])) {
    $_SESSION['error_message'] = "Paramètres manquants pour la désinscription";
    header("Location: ../controleurs/manage_enrollments.php");
    exit();
}

// Get parameters
$studentId = intval($_GET['student_id']);
$subjectId = intval($_GET['subject_id']);

// Initialize matiere model
$matiereModel = new Matiere($connect);

try {
    // Remove student enrollment
    $matiereModel->unenrollStudent($subjectId, $studentId);
    
    $_SESSION['success_message'] = "Élève désinscrit avec succès de la matière";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la désinscription: " . $e->getMessage();
}

// Redirect back
header("Location: ../controleurs/manage_enrollments.php?subject_id=" . $subjectId);
exit();
?> 