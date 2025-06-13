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
if (!isset($_POST['subject_id']) || !isset($_POST['teacher_id'])) {
    $_SESSION['error_message'] = "Informations manquantes pour l'assignation";
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

// Get data
$subjectId = intval($_POST['subject_id']);
$teacherId = intval($_POST['teacher_id']);

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
    
    // Check if user exists and is a teacher
    $teacher = $userModel->getUserById($teacherId);
    if (!$teacher) {
        $_SESSION['error_message'] = "Formateur non trouvé";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    if ($teacher['role'] != 1) {
        $_SESSION['error_message'] = "L'utilisateur sélectionné n'est pas un formateur";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
    
    // Assign teacher to subject
    $matiereModel->assignTeacher($subjectId, $teacherId);
    
    $_SESSION['success_message'] = "Formateur assigné avec succès à la matière";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de l'assignation: " . $e->getMessage();
}

// Redirect back to admin dashboard
header("Location: ../controleurs/admin_dashboard.php");
exit();
?> 