<?php
session_start();
include("../configurations/connexion.php");
include("../modeles/Matiere.php");
include("../modeles/Utilisateur.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if data is provided
if (!isset($_POST['subject_id']) || !isset($_POST['student_id'])) {
    $_SESSION['error_message'] = "Informations manquantes pour l'inscription";
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

// Get data
$subjectId = intval($_POST['subject_id']);
$studentId = intval($_POST['student_id']);

// Initialize models
$matiereModel = new Matiere($connect);
$userModel = new Utilisateur($connect);

try {
    // Check if subject exists
    $subject = $matiereModel->getSubjectById($subjectId);
    if (!$subject) {
        $_SESSION['error_message'] = "Matière non trouvée";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Check if user exists and is a student
    $student = $userModel->getUserById($studentId);
    if (!$student) {
        $_SESSION['error_message'] = "Élève non trouvé";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    if ($student['role'] != 2) {
        $_SESSION['error_message'] = "L'utilisateur sélectionné n'est pas un élève";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Check if student's section matches subject's section
    if ($student['id_section'] != $subject['nom']) {
        $_SESSION['error_message'] = "L'élève n'appartient pas à la section de cette matière";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Enroll student in subject
    $matiereModel->enrollStudent($subjectId, $studentId);
    
    $_SESSION['success_message'] = "Élève inscrit avec succès à la matière";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de l'inscription: " . $e->getMessage();
}

// Redirect back to admin dashboard
header("Location: ../controleurs/admin_dashboard.php");
exit();
?> 