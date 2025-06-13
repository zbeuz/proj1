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

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire
    $nom = isset($_POST["nom_utilisateur"]) ? trim($_POST["nom_utilisateur"]) : "";
    $prenom = isset($_POST["prenom_utilisateur"]) ? trim($_POST["prenom_utilisateur"]) : "";
    $email = isset($_POST["adresse_mail"]) ? trim($_POST["adresse_mail"]) : "";
    $section = isset($_POST["section_utilisateur"]) ? intval($_POST["section_utilisateur"]) : 0;
    $role = isset($_POST["role"]) ? intval($_POST["role"]) : 2; // Par défaut, rôle apprenti (2)

    // Validation des données
    if (empty($nom) || empty($prenom) || empty($email)) {
        $_SESSION['error_message'] = "Tous les champs doivent être remplis.";
        header("Location: ../vues/ajouter_utilisateur.php");
        exit;
    }

    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Format d'email invalide.";
        header("Location: ../vues/ajouter_utilisateur.php");
        exit;
    }

    require_once("../configurations/connexion.php");

    // Vérifier si l'email existe déjà
    $checkEmail = $connect->prepare("SELECT id_utilisateur FROM utilisateur WHERE adresse_mail = ?");
    $checkEmail->execute([$email]);
    if ($checkEmail->rowCount() > 0) {
        $_SESSION['error_message'] = "Cette adresse email est déjà utilisée.";
        header("Location: ../vues/ajouter_utilisateur.php");
        exit;
    }

    // Mot de passe par défaut
    $default_password = "aforp2020";
    // Hachage du mot de passe avec password_hash
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);

    // Insertion dans la base de données
    try {
        $stmt = $connect->prepare("INSERT INTO utilisateur (nom_utilisateur, prenom_utilisateur, adresse_mail, mot_de_passe, id_section, role, premiere_connexion, compte_bloque, mot_de_passe_errone) VALUES (?, ?, ?, ?, ?, ?, 1, 0, 0)");
        $stmt->execute([$nom, $prenom, $email, $hashed_password, $section, $role]);
        
        $_SESSION['success_message'] = "Utilisateur ajouté avec succès. Le mot de passe par défaut est 'aforp2020'.";
        header("Location: ../vues/liste_utilisateurs.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage();
        header("Location: ../vues/ajouter_utilisateur.php");
        exit;
    }
} else {
    // Si accès direct au script sans passer par le formulaire
    header("Location: ../vues/ajouter_utilisateur.php");
    exit;
}
?> 