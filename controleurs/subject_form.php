<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Get all sections for dropdown
try {
    $stmtSections = $connect->prepare("SELECT * FROM section ORDER BY nom ASC");
    $stmtSections->execute();
    $sections = $stmtSections->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des sections";
    $sections = [];
}

// Check if we're editing a subject
$editMode = false;
$subjectData = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $subjectId = intval($_GET['id']);
    $editMode = true;
    
    try {
        $stmtSubject = $connect->prepare("SELECT * FROM matiere WHERE id_matiere = ?");
        $stmtSubject->execute([$subjectId]);
        $subjectData = $stmtSubject->fetch(PDO::FETCH_ASSOC);
        
        if (!$subjectData) {
            $_SESSION['error_message'] = "Matière non trouvée";
            header("Location: ../controleurs/admin_dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la récupération des informations de la matière";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
}

// Include subject form view
include("../vues/ajouter_matiere.php");
?> 