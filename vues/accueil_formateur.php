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
    <title>Interface Formateur</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">
    <style>
        .card-front img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            object-position: center;
            border-radius: 5px 5px 0 0;
        }
    </style>
</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn active">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
        <button id="ajout-button" class="sidebar-btn">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="search-bar">
        </div>
        <div class="search-container">
            <input type="text" id="searchFournisseur" placeholder="Rechercher un fournisseur...">
            <div id="searchResults" class="search-results"></div>
        </div>
        <div class="content-area" id="content-area">
            <?php include '../controleurs/affichagesys.php'; ?>
            <div class="row">
                <?php foreach($result as $row): ?>
                    <div class="column">
                        <div class="card" data-system-id="<?php echo $row['id_systeme']; ?>">
                            <div class="card-front">
                                <img src="../ressources/systeme/<?php echo $row['photo_systeme']; ?>" alt="Image de la machine">
                                <p><?php echo $row['nom_systeme']; ?></p>
                                <p><?php echo $row['description_systeme']; ?></p>
                            </div>
                            <div class="card-back">
                                <button class="btn-consulter" data-type="1">Documents Techniques</button>
                                <button class="btn-consulter" data-type="6">Documents pédagogiques</button>
                                <button class="btn-consulter btn-info-fournisseur">Info Fournisseur</button>
                                <button class="btn-consulter btn-system-supprimer">Supprimer</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html>
