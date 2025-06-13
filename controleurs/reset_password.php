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

// Vérifier si l'ID de l'utilisateur à réinitialiser est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID d'utilisateur non spécifié.";
    header("Location: ../vues/liste_utilisateurs.php");
    exit;
}

$userId = $_GET['id'];

require_once("../configurations/connexion.php");

// Vérifier si l'utilisateur existe
$check_user = $connect->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
$check_user->execute([$userId]);

if ($check_user->rowCount() == 0) {
    $_SESSION['error_message'] = "Utilisateur introuvable.";
    header("Location: ../vues/liste_utilisateurs.php");
    exit;
}

// Mot de passe par défaut
$default_password = "aforp2020";
// Hachage du mot de passe avec password_hash
$hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

// Mise à jour du mot de passe dans la base de données
try {
    $stmt = $connect->prepare("UPDATE utilisateur SET mot_de_passe = ?, premiere_connexion = 1 WHERE id_utilisateur = ?");
    $stmt->execute([$hashed_password, $userId]);
    
    $_SESSION['success_message'] = "Le mot de passe a été réinitialisé avec succès.";
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la réinitialisation du mot de passe : " . $e->getMessage();
}

header("Location: ../vues/liste_utilisateurs.php");
exit;
?> 