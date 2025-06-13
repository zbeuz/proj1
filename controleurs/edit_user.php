<?php
session_start();
include("../configurations/connexion.php");
include("../modeles/Utilisateur.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if user ID is provided
if (!isset($_POST['id_utilisateur']) || empty($_POST['id_utilisateur'])) {
    $_SESSION['error_message'] = "Identifiant de l'utilisateur non spécifié";
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

// Initialize user model
$userModel = new Utilisateur($connect);

// Get user ID
$userId = intval($_POST['id_utilisateur']);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $role = intval($_POST['role']);
    $section = trim($_POST['section']);
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($section)) {
        $_SESSION['error_message'] = "Tous les champs sont obligatoires";
        header("Location: ../controleurs/user_form.php?id=" . $userId);
        exit();
    }
    
    // Check if email already exists for another user
    try {
        $existingUser = $userModel->getUserByEmail($email);
        if ($existingUser && $existingUser['id_utilisateur'] != $userId) {
            $_SESSION['error_message'] = "Cette adresse email est déjà utilisée par un autre utilisateur";
            header("Location: ../controleurs/user_form.php?id=" . $userId);
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur lors de la vérification de l'email: " . $e->getMessage();
        header("Location: ../controleurs/user_form.php?id=" . $userId);
        exit();
    }
    
    try {
        // Update user
        $userModel->updateUser($userId, $nom, $prenom, $email, $role, $section);
        
        $_SESSION['success_message'] = "Utilisateur mis à jour avec succès";
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage();
        header("Location: ../controleurs/user_form.php?id=" . $userId);
        exit();
    }
} else {
    header("Location: ../controleurs/user_form.php?id=" . $userId);
    exit();
}
?> 