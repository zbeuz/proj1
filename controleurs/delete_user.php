<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Vérifier si l'utilisateur est un formateur (role = 1)
if ($_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit;
}

// Vérifier si l'ID de l'utilisateur est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID d'utilisateur non spécifié.";
    header("Location: ../vues/liste_utilisateurs.php");
    exit;
}

require_once("../configurations/connexion.php");

$userId = $_GET['id'];

// Vérifier que l'utilisateur n'essaie pas de supprimer son propre compte
if ($userId == $_SESSION['user_id']) {
    $_SESSION['error_message'] = "Vous ne pouvez pas supprimer votre propre compte.";
    header("Location: ../vues/liste_utilisateurs.php");
    exit;
}

// Vérifier si l'utilisateur existe
$check_user = $connect->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
$check_user->execute([$userId]);

if ($check_user->rowCount() == 0) {
    $_SESSION['error_message'] = "Utilisateur introuvable.";
    header("Location: ../vues/liste_utilisateurs.php");
    exit;
}

// Suppression de l'utilisateur
try {
    $stmt = $connect->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$userId]);
    
    $_SESSION['success_message'] = "L'utilisateur a été supprimé avec succès.";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression de l'utilisateur : " . $e->getMessage();
}

header("Location: ../vues/liste_utilisateurs.php");
exit;
?> 