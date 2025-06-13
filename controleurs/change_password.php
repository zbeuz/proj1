<?php
session_start();
include("../configurations/connexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error_message'] = "Vous devez être connecté pour changer votre mot de passe";
        header("Location: ../index.php");
        exit();
    }
    
    // Validate passwords
    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_message'] = "Veuillez remplir tous les champs";
        header("Location: ../vues/change_password.php");
        exit();
    }
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "Les mots de passe ne correspondent pas";
        header("Location: ../vues/change_password.php");
        exit();
    }
    
    try {
        // Validation de la complexité du mot de passe
        if (strlen($new_password) < 12) {
            $_SESSION['error_message'] = "Le mot de passe doit contenir au moins 12 caractères";
            header("Location: ../vues/change_password.php");
            exit();
        }
        
        // Vérifier si le mot de passe contient au moins une lettre majuscule, une minuscule, un chiffre et un caractère spécial
        if (!preg_match('/[A-Z]/', $new_password)) {
            $_SESSION['error_message'] = "Le mot de passe doit contenir au moins une lettre majuscule";
            header("Location: ../vues/change_password.php");
            exit();
        }
        
        if (!preg_match('/[a-z]/', $new_password)) {
            $_SESSION['error_message'] = "Le mot de passe doit contenir au moins une lettre minuscule";
            header("Location: ../vues/change_password.php");
            exit();
        }
        
        if (!preg_match('/[0-9]/', $new_password)) {
            $_SESSION['error_message'] = "Le mot de passe doit contenir au moins un chiffre";
            header("Location: ../vues/change_password.php");
            exit();
        }
        
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $new_password)) {
            $_SESSION['error_message'] = "Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&*()_+-=[]{}|;':\",./<>?)";
            header("Location: ../vues/change_password.php");
            exit();
        }
        
        // Hasher le mot de passe avec password_hash (beaucoup plus sécurisé que MD5)
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Update password with secure hash
        $stmt = $connect->prepare("UPDATE utilisateur SET mot_de_passe = ?, premiere_connexion = 0 WHERE id_utilisateur = ?");
        $stmt->execute([$hashed_password, $_SESSION['user_id']]);
        
        // Update session to indicate password change success
        $_SESSION['first_login'] = false;
        
        // Redirect based on role
        if ($_SESSION['user_role'] == 1) {
            header("Location: ../vues/accueil_formateur.php");
        } else {
            header("Location: ../vues/accueil_apprenti.php");
        }
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de la mise à jour du mot de passe";
        header("Location: ../vues/change_password.php");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
?> 