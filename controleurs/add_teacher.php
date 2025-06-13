<?php
session_start();
include("../configurations/connexion.php");
include("../modeles/Matiere.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if required parameters are provided
if (!isset($_POST['teacher_id']) || empty($_POST['teacher_id']) || 
    !isset($_POST['subject_id']) || empty($_POST['subject_id'])) {
    $_SESSION['error_message'] = "Informations d'assignation incomplètes";
    header("Location: ../controleurs/manage_teachers.php");
    exit();
}

$teacherId = intval($_POST['teacher_id']);
$subjectId = intval($_POST['subject_id']);

try {
    // Initialize the Matiere model
    $matiereModel = new Matiere($connect);
    
    // Check if teacher is already assigned
    $isAssigned = $matiereModel->isTeacherAssigned($teacherId, $subjectId);
    
    if ($isAssigned) {
        $_SESSION['error_message'] = "Ce formateur est déjà assigné à cette matière";
        header("Location: ../controleurs/manage_teachers.php?id=" . $subjectId);
        exit();
    }
    
    // Assign the teacher
    $result = $matiereModel->assignTeacher($teacherId, $subjectId);
    
    if ($result) {
        $_SESSION['success_message'] = "Formateur assigné avec succès";
    } else {
        $_SESSION['error_message'] = "Erreur lors de l'assignation du formateur";
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
}

header("Location: ../controleurs/manage_teachers.php?id=" . $subjectId);
exit();
?> 