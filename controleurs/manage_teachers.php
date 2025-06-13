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
    
    // Get all teachers (role 1, admin)
    $stmtTeachers = $connect->prepare("
        SELECT * FROM utilisateur 
        WHERE role = 1 
        ORDER BY nom_utilisateur, prenom_utilisateur
    ");
    $stmtTeachers->execute();
    $teachers = $stmtTeachers->fetchAll(PDO::FETCH_ASSOC);
    
    // If subject is selected, get assigned teachers
    $assignedTeachers = [];
    $availableTeachers = [];
    
    if ($subjectId) {
        $stmtAssigned = $connect->prepare("
            SELECT u.* 
            FROM utilisateur u
            JOIN animer a ON u.id_utilisateur = a.id_utilisateur
            WHERE a.id_matiere = ?
            ORDER BY u.nom_utilisateur, u.prenom_utilisateur
        ");
        $stmtAssigned->execute([$subjectId]);
        $assignedTeachers = $stmtAssigned->fetchAll(PDO::FETCH_ASSOC);
        
        // Get subject details
        $selectedSubject = $matiereModel->getSubjectById($subjectId);
        
        // Get teachers who are not assigned to this subject
        $stmtAvailable = $connect->prepare("
            SELECT u.* 
            FROM utilisateur u
            WHERE u.role = 1 
            AND u.id_utilisateur NOT IN (
                SELECT a.id_utilisateur FROM animer a WHERE a.id_matiere = ?
            )
            ORDER BY u.nom_utilisateur, u.prenom_utilisateur
        ");
        $stmtAvailable->execute([$subjectId]);
        $availableTeachers = $stmtAvailable->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des données: " . $e->getMessage();
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

// Include view
include("../vues/gestion_formateurs.php");
?> 