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
if (!isset($_GET['teacher_id']) || !isset($_GET['subject_id'])) {
    $_SESSION['error_message'] = "Paramètres manquants pour le retrait";
    header("Location: ../controleurs/manage_teachers.php");
    exit();
}

// Get parameters
$teacherId = intval($_GET['teacher_id']);
$subjectId = intval($_GET['subject_id']);

// Initialize matiere model
$matiereModel = new Matiere($connect);

try {
    // Remove teacher from subject
    $matiereModel->removeTeacher($subjectId, $teacherId);
    
    $_SESSION['success_message'] = "Formateur retiré avec succès de la matière";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors du retrait: " . $e->getMessage();
}

// Redirect back
header("Location: ../controleurs/manage_teachers.php?subject_id=" . $subjectId);
exit();
?> 