<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if we're editing a system
$editMode = false;
$systemData = null;

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $systemId = $_GET['id'];
    $editMode = true;
    
    try {
        $stmtSystem = $connect->prepare("SELECT * FROM systeme WHERE id_systeme = ?");
        $stmtSystem->execute([$systemId]);
        $systemData = $stmtSystem->fetch(PDO::FETCH_ASSOC);
        
        if (!$systemData) {
            $_SESSION['error_message'] = "Système non trouvé";
            header("Location: ../controleurs/admin_dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la récupération des informations du système";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    }
}

// Include system form view
include("../vues/ajout_systeme.php");
?> 