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
if (!isset($_POST['student_id']) || empty($_POST['student_id']) || 
    !isset($_POST['subject_id']) || empty($_POST['subject_id'])) {
    $_SESSION['error_message'] = "Informations d'inscription incomplètes";
    header("Location: ../controleurs/manage_enrollments.php");
    exit();
}

$studentId = intval($_POST['student_id']);
$subjectId = intval($_POST['subject_id']);

try {
    // Initialize the Matiere model
    $matiereModel = new Matiere($connect);
    
    // Check if student is already enrolled
    $isEnrolled = $matiereModel->isStudentEnrolled($studentId, $subjectId);
    
    if ($isEnrolled) {
        $_SESSION['error_message'] = "Cet étudiant est déjà inscrit à cette matière";
        header("Location: ../controleurs/manage_enrollments.php?subject_id=" . $subjectId);
        exit();
    }
    
    // Add the enrollment
    $result = $matiereModel->enrollStudent($studentId, $subjectId);
    
    if ($result) {
        $_SESSION['success_message'] = "Étudiant inscrit avec succès";
    } else {
        $_SESSION['error_message'] = "Erreur lors de l'inscription de l'étudiant";
    }
    
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
}

header("Location: ../controleurs/manage_enrollments.php?subject_id=" . $subjectId);
exit(); 