<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Vérifier si c'est la première connexion
if (!isset($_SESSION['first_login']) || $_SESSION['first_login'] !== true) {
    // Rediriger selon le rôle
    if ($_SESSION['user_role'] == 1) {
        header("Location: accueil_formateur.php");
    } else {
        header("Location: accueil_apprenti.php");
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changement de mot de passe</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">
    <link rel="stylesheet" href="../ressources/styles/change_password.css">
</head>
<body>
    <div class="password-card">
        <h1>Changement de mot de passe</h1>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="error-message">
                <?php 
                    echo $_SESSION['error_message']; 
                    unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="password-requirements">
            <p><strong>Pour votre sécurité, veuillez changer votre mot de passe.</strong></p>
            <p>Votre mot de passe doit respecter les critères suivants :</p>
            <ul>
                <li>Au moins 12 caractères</li>
                <li>Au moins une lettre majuscule</li>
                <li>Au moins une lettre minuscule</li>
                <li>Au moins un chiffre</li>
                <li>Au moins un caractère spécial (!@#$%^&*()_+-=[]{}|;':\",./<>?)</li>
            </ul>
        </div>
        
        <form action="../controleurs/change_password.php" method="post">
            <div class="form-group">
                <label for="new_password">Nouveau mot de passe</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirmez le mot de passe</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="submit-btn">Changer mon mot de passe</button>
        </form>
    </div>
</body>
</html> 