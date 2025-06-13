<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Apprenti</title>
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
        <button id="systeme-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
    </aside>
    
    <main class="main-content">
        <div class="search-bar">
            <input type="text" placeholder="Saisissez votre recherche">
            <button class="search-btn">🔍</button>
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
                                <button class="btn-tech-doc" data-type="1">Documents Techniques</button>
                                <button class="btn-documents-pedagogiques">Documents Pédagogiques</button>
                                <button class="btn-travail-a-faire">Travail à faire</button>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</div>

<script src="../ressources/js/app.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html>

