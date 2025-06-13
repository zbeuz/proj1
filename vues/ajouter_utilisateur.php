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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter Utilisateur</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">
</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
        <button id="ajout-button" class="sidebar-btn">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn active">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <h1>Ajouter un Utilisateur</h1>
            
            <div class="styled-form">
                <form action="../controleurs/add_user.php" method="post">
                    <div class="form-group">
                        <label for="nom_utilisateur">Nom:</label>
                        <input type="text" id="nom_utilisateur" name="nom_utilisateur" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom_utilisateur">Prénom:</label>
                        <input type="text" id="prenom_utilisateur" name="prenom_utilisateur" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse_mail">Adresse Mail:</label>
                        <input type="email" id="adresse_mail" name="adresse_mail" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="section_utilisateur">Section:</label>
                        <select id="section_utilisateur" name="section_utilisateur" required>
                            <option value="1">BTS MELEC</option>
                            <option value="2">BTS SLAM</option>
                            <!-- Ajoutez d'autres options selon vos sections -->
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Rôle:</label>
                        <select id="role" name="role" required>
                            <option value="1">Formateur</option>
                            <option value="2">Apprenti</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-consulter">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html>
