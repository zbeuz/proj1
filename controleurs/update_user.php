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
    $id_utilisateur = isset($_POST["id_utilisateur"]) ? intval($_POST["id_utilisateur"]) : 0;
    $nom = isset($_POST["nom_utilisateur"]) ? trim($_POST["nom_utilisateur"]) : "";
    $prenom = isset($_POST["prenom_utilisateur"]) ? trim($_POST["prenom_utilisateur"]) : "";
    $email = isset($_POST["adresse_mail"]) ? trim($_POST["adresse_mail"]) : "";
    $section = isset($_POST["section_utilisateur"]) ? intval($_POST["section_utilisateur"]) : 0;
    $role = isset($_POST["role"]) ? intval($_POST["role"]) : 2;

    // Validation des données
    if ($id_utilisateur <= 0) {
        $_SESSION['error_message'] = "ID utilisateur invalide.";
        header("Location: ../vues/liste_utilisateurs.php");
        exit;
    }

    if (empty($nom) || empty($prenom) || empty($email)) {
        $_SESSION['error_message'] = "Tous les champs doivent être remplis.";
        header("Location: ../vues/modifier_utilisateur.php?id=" . $id_utilisateur);
        exit;
    }

    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Format d'email invalide.";
        header("Location: ../vues/modifier_utilisateur.php?id=" . $id_utilisateur);
        exit;
    }

    require_once("../configurations/connexion.php");

    // Vérifier si l'email existe déjà pour un autre utilisateur
    try {
        $checkEmail = $connect->prepare("SELECT id_utilisateur FROM utilisateur WHERE adresse_mail = ? AND id_utilisateur != ?");
        $checkEmail->execute([$email, $id_utilisateur]);
        if ($checkEmail->rowCount() > 0) {
            $_SESSION['error_message'] = "Cette adresse email est déjà utilisée par un autre utilisateur.";
            header("Location: ../vues/modifier_utilisateur.php?id=" . $id_utilisateur);
            exit;
        }

        // Vérifier que l'utilisateur existe
        $checkUser = $connect->prepare("SELECT id_utilisateur FROM utilisateur WHERE id_utilisateur = ?");
        $checkUser->execute([$id_utilisateur]);
        if ($checkUser->rowCount() == 0) {
            $_SESSION['error_message'] = "Utilisateur introuvable.";
            header("Location: ../vues/liste_utilisateurs.php");
            exit;
        }

        // Mise à jour de l'utilisateur
        $stmt = $connect->prepare("UPDATE utilisateur SET nom_utilisateur = ?, prenom_utilisateur = ?, adresse_mail = ?, id_section = ?, role = ? WHERE id_utilisateur = ?");
        $stmt->execute([$nom, $prenom, $email, $section, $role, $id_utilisateur]);
        
        $_SESSION['success_message'] = "Utilisateur modifié avec succès.";
        header("Location: ../vues/liste_utilisateurs.php");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur lors de la modification de l'utilisateur : " . $e->getMessage();
        header("Location: ../vues/modifier_utilisateur.php?id=" . $id_utilisateur);
        exit;
    }
} else {
    // Si accès direct au script sans passer par le formulaire
    $_SESSION['error_message'] = "Accès non autorisé.";
    header("Location: ../vues/liste_utilisateurs.php");
    exit;
}
?> 