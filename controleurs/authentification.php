<?php
session_start();
include("../configurations/connexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Validate inputs
    if (empty($email) || empty($password)) {
        $_SESSION['error_message'] = "Veuillez remplir tous les champs";
        header("Location: ../index.php");
        exit();
    }

    try {
        // Get user information
        $stmt = $connect->prepare("SELECT * FROM utilisateur WHERE adresse_mail = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            // Check if account is blocked
            if ($user['compte_bloque'] == 1) {
                $_SESSION['error_message'] = "Votre compte est bloqué. Veuillez contacter l'administrateur.";
                header("Location: ../index.php");
                exit();
            }

            // Reset password error counter on successful login
            $resetStmt = $connect->prepare("UPDATE utilisateur SET mot_de_passe_errone = 0 WHERE id_utilisateur = ?");
            $resetStmt->execute([$user['id_utilisateur']]);

            // Store user information in session
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['user_name'] = $user['prenom_utilisateur'] . ' ' . $user['nom_utilisateur'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['section_id'] = $user['id_section'];

            // Check if first connection to show password change popup
            if ($user['premiere_connexion'] == 1) {
                $_SESSION['first_login'] = true;
                header("Location: ../vues/change_password.php");
                exit();
            }

            // Redirect based on role
            if ($user['role'] == 1) {
                header("Location: ../vues/accueil_formateur.php");
            } else {
                header("Location: ../vues/accueil_apprenti.php");
            }
        } else {
            // Increment wrong password counter
            if ($user) {
                $wrongCount = $user['mot_de_passe_errone'] + 1;
                $updateStmt = $connect->prepare("UPDATE utilisateur SET mot_de_passe_errone = ? WHERE id_utilisateur = ?");
                $updateStmt->execute([$wrongCount, $user['id_utilisateur']]);

                // Block account after 3 attempts
                if ($wrongCount >= 3) {
                    $blockStmt = $connect->prepare("UPDATE utilisateur SET compte_bloque = 1 WHERE id_utilisateur = ?");
                    $blockStmt->execute([$user['id_utilisateur']]);
                    $_SESSION['error_message'] = "Compte bloqué après 3 tentatives incorrectes. Contactez l'administrateur.";
                } else {
                    $_SESSION['error_message'] = "Identifiants incorrects. Tentative " . $wrongCount . "/3";
                }
            } else {
                $_SESSION['error_message'] = "Identifiants incorrects";
            }
            
            header("Location: ../index.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur de connexion à la base de données";
        header("Location: ../index.php");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
?> 