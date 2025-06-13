<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if we're editing a section
$editMode = false;
$sectionData = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $sectionId = trim($_GET['id']);
    $editMode = true;
    
    try {
        $stmtSection = $connect->prepare("SELECT * FROM section WHERE nom = ?");
        $stmtSection->execute([$sectionId]);
        $sectionData = $stmtSection->fetch(PDO::FETCH_ASSOC);
        
        if (!$sectionData) {
            $_SESSION['error_message'] = "Section non trouvée";
            header("Location: ../controleurs/admin_dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la récupération des informations de la section";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
}

// Include section form view
include("../vues/ajouter_section.php");
?> 