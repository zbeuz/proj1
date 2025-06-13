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
    <title>Menu d'Ajout</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">

</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
        <button id="ajout-button" class="sidebar-btn active">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <h1>Que souhaitez-vous ajouter ?</h1>
            
            <div class="menu-ajout">
                <div class="menu-item" onclick="window.location.href='ajout_systeme.php'">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="3" width="18" height="18" rx="2" stroke="#333" stroke-width="2"/>
                        <circle cx="12" cy="12" r="4" stroke="#333" stroke-width="2"/>
                        <path d="M12 5V7" stroke="#333" stroke-width="2" stroke-linecap="round"/>
                        <path d="M12 17V19" stroke="#333" stroke-width="2" stroke-linecap="round"/>
                        <path d="M5 12H7" stroke="#333" stroke-width="2" stroke-linecap="round"/>
                        <path d="M17 12H19" stroke="#333" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <h3>Système</h3>
                    <p>Ajouter un nouveau système technique</p>
                </div>
                
                <div class="menu-item" onclick="window.location.href='ajout_document_technique.php'">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 2H6C5.46957 2 4.96086 2.21071 4.58579 2.58579C4.21071 2.96086 4 3.46957 4 4V20C4 20.5304 4.21071 21.0391 4.58579 21.4142C4.96086 21.7893 5.46957 22 6 22H18C18.5304 22 19.0391 21.7893 19.4142 21.4142C19.7893 21.0391 20 20.5304 20 20V8L14 2Z" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M14 2V8H20" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 13H16" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 17H16" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M10 9H10.01" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>Document Technique</h3>
                    <p>Ajouter un document technique pour un système</p>
                </div>
                
                <div class="menu-item" onclick="window.location.href='ajout_document_pedago.php'">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 3H8C9.06087 3 10.0783 3.42143 10.8284 4.17157C11.5786 4.92172 12 5.93913 12 7V21C12 20.2044 11.6839 19.4413 11.1213 18.8787C10.5587 18.3161 9.79565 18 9 18H2V3Z" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M22 3H16C14.9391 3 13.9217 3.42143 13.1716 4.17157C12.4214 4.92172 12 5.93913 12 7V21C12 20.2044 12.3161 19.4413 12.8787 18.8787C13.4413 18.3161 14.2044 18 15 18H22V3Z" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>Document Pédagogique</h3>
                    <p>Ajouter un document pédagogique ou un travail</p>
                </div>
                
                <div class="menu-item" onclick="window.location.href='ajout_travail.php'">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>Travail à Faire</h3>
                    <p>Créer un nouveau travail pour une section</p>
                </div>
                
                <div class="menu-item" onclick="window.location.href='ajout_fournisseur.php'">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 4H18C18.5304 4 19.0391 4.21071 19.4142 4.58579C19.7893 4.96086 20 5.46957 20 6V20C20 20.5304 19.7893 21.0391 19.4142 21.4142C19.0391 21.7893 18.5304 22 18 22H6C5.46957 22 4.96086 21.7893 4.58579 21.4142C4.21071 21.0391 4 20.5304 4 20V6C4 5.46957 4.21071 4.96086 4.58579 4.58579C4.96086 4.21071 5.46957 4 6 4H8" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M15 2H9C8.44772 2 8 2.44772 8 3V5C8 5.55228 8.44772 6 9 6H15C15.5523 6 16 5.55228 16 5V3C16 2.44772 15.5523 2 15 2Z" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 11H16" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 16H16" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 11H8.01" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 16H8.01" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>Fournisseur</h3>
                    <p>Ajouter un nouveau fournisseur</p>
                </div>
                
                <div class="menu-item" onclick="window.location.href='ajouter_utilisateur.php'">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 21V19C20 17.9391 19.5786 16.9217 18.8284 16.1716C18.0783 15.4214 17.0609 15 16 15H8C6.93913 15 5.92172 15.4214 5.17157 16.1716C4.42143 16.9217 4 17.9391 4 19V21" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 11C14.2091 11 16 9.20914 16 7C16 4.79086 14.2091 3 12 3C9.79086 3 8 4.79086 8 7C8 9.20914 9.79086 11 12 11Z" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <h3>Utilisateur</h3>
                    <p>Ajouter un nouvel utilisateur</p>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html> 