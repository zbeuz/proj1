<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in and is a student/apprentice (role 2)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 2) {
    header("Location: ../index.php");
    exit();
}

// Get user information
$userId = $_SESSION['user_id'];
$sectionId = $_SESSION['section_id'];

// Get user's section info
try {
    $stmtSection = $connect->prepare("
        SELECT section.* 
        FROM section 
        JOIN utilisateur ON section.nom = utilisateur.id_section 
        WHERE utilisateur.id_utilisateur = ?
    ");
    $stmtSection->execute([$userId]);
    $section = $stmtSection->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des informations de section";
    $section = null;
}

// Get user's enrolled subjects
try {
    $stmtMatieres = $connect->prepare("
        SELECT matiere.* 
        FROM matiere 
        JOIN inscrire ON matiere.id_matiere = inscrire.id_matiere 
        WHERE inscrire.id_utilisateur = ?
    ");
    $stmtMatieres->execute([$userId]);
    $matieres = $stmtMatieres->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des matières";
    $matieres = [];
}

// Get available systems
try {
    $stmtSystems = $connect->prepare("SELECT * FROM systeme");
    $stmtSystems->execute();
    $systems = $stmtSystems->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des systèmes";
    $systems = [];
}

// Include the apprentice view
include("../vues/accueil_apprenti.php");
?> 