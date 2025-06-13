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

require_once("../configurations/connexion.php");

// Initialiser les variables de message
$message = '';
$messageType = '';

// Vérifier si la table fournisseur existe
try {
    $query_check = "SHOW TABLES LIKE 'fournisseur'";
    $stmt_check = $connect->prepare($query_check);
    $stmt_check->execute();
    $table_exists = $stmt_check->rowCount() > 0;
    
    if (!$table_exists) {
        // Créer la table fournisseur si elle n'existe pas
        $create_table_query = "CREATE TABLE IF NOT EXISTS `fournisseur` (
            `id_fournisseur` int NOT NULL AUTO_INCREMENT,
            `nom_entreprise` varchar(100) NOT NULL,
            `contact_nom` varchar(50) DEFAULT NULL,
            `contact_prenom` varchar(50) DEFAULT NULL,
            `telephone` varchar(20) DEFAULT NULL,
            `email` varchar(100) DEFAULT NULL,
            `adresse` text DEFAULT NULL,
            `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_fournisseur`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $stmt_create = $connect->prepare($create_table_query);
        $stmt_create->execute();
        
        $message = "La table fournisseur a été créée avec succès.";
        $messageType = "success";
    }
} catch (Exception $e) {
    $message = "Erreur lors de la vérification/création de la table fournisseur : " . $e->getMessage();
    $messageType = "error";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_entreprise = $_POST['nom_entreprise'];
    $contact_nom = $_POST['contact_nom'];
    $contact_prenom = $_POST['contact_prenom'];
    $telephone = $_POST['telephone'];
    $email = $_POST['email'];
    $adresse = $_POST['adresse'];
    
    try {
        $connect->beginTransaction();
        
        $query = "INSERT INTO fournisseur (nom_entreprise, contact_nom, contact_prenom, telephone, email, adresse) 
                 VALUES (:nom_entreprise, :contact_nom, :contact_prenom, :telephone, :email, :adresse)";
        
        $stmt = $connect->prepare($query);
        $stmt->bindParam(':nom_entreprise', $nom_entreprise, PDO::PARAM_STR);
        $stmt->bindParam(':contact_nom', $contact_nom, PDO::PARAM_STR);
        $stmt->bindParam(':contact_prenom', $contact_prenom, PDO::PARAM_STR);
        $stmt->bindParam(':telephone', $telephone, PDO::PARAM_STR);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->bindParam(':adresse', $adresse, PDO::PARAM_STR);
        
        $stmt->execute();
        $connect->commit();
        
        $message = "Fournisseur ajouté avec succès.";
        $messageType = "success";
    } catch (Exception $e) {
        $connect->rollBack();
        $message = "Erreur lors de l'ajout du fournisseur : " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Fournisseur</title>
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
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn active">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <h1>Ajouter un Fournisseur</h1>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="styled-form">
                <form action="" method="post">
                    <div class="form-group">
                        <label for="nom_entreprise">Nom de l'entreprise*:</label>
                        <input type="text" id="nom_entreprise" name="nom_entreprise" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_prenom">Prénom du contact:</label>
                        <input type="text" id="contact_prenom" name="contact_prenom">
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_nom">Nom du contact:</label>
                        <input type="text" id="contact_nom" name="contact_nom">
                    </div>
                    
                    <div class="form-group">
                        <label for="telephone">Téléphone:</label>
                        <input type="tel" id="telephone" name="telephone">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email">
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse">Adresse:</label>
                        <textarea id="adresse" name="adresse"></textarea>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-consulter btn-back" onclick="window.location.href='menu_ajout.php'">Retour</button>
                        <button type="submit" class="btn-consulter">Ajouter le fournisseur</button>
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