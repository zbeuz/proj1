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

// Initialize models
$matiereModel = new Matiere($connect);
$userModel = new Utilisateur($connect);

// Get subject ID if provided
$subjectId = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;

// Get data for dropdowns
try {
    // Get all subjects
    $subjects = $matiereModel->getAllSubjects();
    
    // Get all students (role 2)
    $stmtStudents = $connect->prepare("
        SELECT * FROM utilisateur 
        WHERE role = 2 
        ORDER BY nom_utilisateur, prenom_utilisateur
    ");
    $stmtStudents->execute();
    $students = $stmtStudents->fetchAll(PDO::FETCH_ASSOC);
    
    // If subject is selected, get enrolled students
    $enrolledStudents = [];
    $availableStudents = [];
    
    if ($subjectId) {
        $stmtEnrolled = $connect->prepare("
            SELECT u.* 
            FROM utilisateur u
            JOIN inscrire i ON u.id_utilisateur = i.id_utilisateur
            WHERE i.id_matiere = ?
            ORDER BY u.nom_utilisateur, u.prenom_utilisateur
        ");
        $stmtEnrolled->execute([$subjectId]);
        $enrolledStudents = $stmtEnrolled->fetchAll(PDO::FETCH_ASSOC);
        
        // Get subject details
        $selectedSubject = $matiereModel->getSubjectById($subjectId);
        
        // Get students in same section as the subject who are not enrolled
        $stmtAvailable = $connect->prepare("
            SELECT u.* 
            FROM utilisateur u
            WHERE u.role = 2 
            AND u.id_section = ?
            AND u.id_utilisateur NOT IN (
                SELECT i.id_utilisateur FROM inscrire i WHERE i.id_matiere = ?
            )
            ORDER BY u.nom_utilisateur, u.prenom_utilisateur
        ");
        $stmtAvailable->execute([$selectedSubject['nom'], $subjectId]);
        $availableStudents = $stmtAvailable->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des données: " . $e->getMessage();
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

// Include view
include("../vues/gestion_inscriptions.php");
?> 