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

// Récupérer la liste des sections pour le menu déroulant
try {
    $query_sections = "SELECT nom, promotion FROM section ORDER BY nom, promotion DESC";
    $stmt_sections = $connect->prepare($query_sections);
    $stmt_sections->execute();
    $sections = $stmt_sections->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Erreur lors de la récupération des sections : " . $e->getMessage();
    $messageType = "error";
    $sections = [];
}

// Récupérer la liste des systèmes pour le menu déroulant
try {
    $query_systemes = "SELECT id_systeme, nom_systeme FROM systeme ORDER BY nom_systeme";
    $stmt_systemes = $connect->prepare($query_systemes);
    $stmt_systemes->execute();
    $systemes = $stmt_systemes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Erreur lors de la récupération des systèmes : " . $e->getMessage();
    $messageType = "error";
    $systemes = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $id_section = $_POST['id_section'];
    $id_systeme = !empty($_POST['id_systeme']) ? $_POST['id_systeme'] : null;
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $id_utilisateur = $_SESSION['user_id'];
    $fichier_joint = null;

    // Gestion de l'upload du document
    if (isset($_FILES['fichier_joint']) && $_FILES['fichier_joint']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../ressources/devoirs/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_name = basename($_FILES['fichier_joint']['name']);
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_filename = 'devoir_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_file = $upload_dir . $unique_filename;
        
        if (move_uploaded_file($_FILES['fichier_joint']['tmp_name'], $upload_file)) {
            $fichier_joint = $unique_filename;
        } else {
            $message = "Erreur lors de l'upload du fichier.";
            $messageType = "error";
        }
    }

    if ($messageType !== 'error') {
        try {
            $connect->beginTransaction();
            
            // Vérifier si la table travail_a_faire existe, sinon la créer
            $check_table = $connect->query("SHOW TABLES LIKE 'travail_a_faire'");
            if ($check_table->rowCount() == 0) {
                // Créer la table si elle n'existe pas
                $create_table = "CREATE TABLE `travail_a_faire` (
                    `id_travail` int NOT NULL AUTO_INCREMENT,
                    `titre` varchar(255) NOT NULL,
                    `description` text,
                    `date_debut` date NOT NULL,
                    `date_fin` date NOT NULL,
                    `id_section` varchar(50) NOT NULL,
                    `id_systeme` int DEFAULT NULL,
                    `id_utilisateur` int NOT NULL,
                    `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id_travail`),
                    KEY `fk_travail_section` (`id_section`),
                    KEY `fk_travail_systeme` (`id_systeme`),
                    KEY `fk_travail_utilisateur` (`id_utilisateur`),
                    CONSTRAINT `fk_travail_section` FOREIGN KEY (`id_section`) REFERENCES `section` (`nom`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `fk_travail_systeme` FOREIGN KEY (`id_systeme`) REFERENCES `systeme` (`id_systeme`) ON DELETE SET NULL ON UPDATE CASCADE,
                    CONSTRAINT `fk_travail_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
                
                $connect->exec($create_table);
            }
            
            // Vérifier si la colonne 'fichier_joint' existe, sinon l'ajouter
            $check_column = $connect->query("SHOW COLUMNS FROM `travail_a_faire` LIKE 'fichier_joint'");
            if ($check_column->rowCount() == 0) {
                $connect->exec("ALTER TABLE `travail_a_faire` ADD `fichier_joint` VARCHAR(255) NULL DEFAULT NULL AFTER `description`");
            }

            // Insérer le nouveau travail
            $query = "INSERT INTO travail_a_faire (titre, description, fichier_joint, date_debut, date_fin, id_section, id_systeme, id_utilisateur) 
                     VALUES (:titre, :description, :fichier_joint, :date_debut, :date_fin, :id_section, :id_systeme, :id_utilisateur)";
            
            $stmt = $connect->prepare($query);
            $stmt->bindParam(':titre', $titre, PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, PDO::PARAM_STR);
            $stmt->bindParam(':fichier_joint', $fichier_joint, PDO::PARAM_STR);
            $stmt->bindParam(':date_debut', $date_debut, PDO::PARAM_STR);
            $stmt->bindParam(':date_fin', $date_fin, PDO::PARAM_STR);
            $stmt->bindParam(':id_section', $id_section, PDO::PARAM_STR);
            $stmt->bindParam(':id_systeme', $id_systeme, PDO::PARAM_INT);
            $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
            
            $stmt->execute();
            $connect->commit();
            
            $message = "Travail à faire ajouté avec succès.";
            $messageType = "success";
        } catch (Exception $e) {
            // Vérifier si une transaction est active avant de faire un rollback
            if ($connect->inTransaction()) {
                $connect->rollBack();
            }
            $message = "Erreur lors de l'ajout du travail : " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Travail à Faire</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">

</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn active">Travail à Faire</button>
        <button id="ajout-button" class="sidebar-btn">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <h1>Ajouter un Travail à Faire</h1>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="styled-form">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="titre">Titre du travail*:</label>
                        <input type="text" id="titre" name="titre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description détaillée*:</label>
                        <textarea id="description" name="description" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fichier_joint">Joindre un fichier (optionnel):</label>
                        <input type="file" id="fichier_joint" name="fichier_joint">
                    </div>
                    
                    <div class="form-group">
                        <label for="id_section">Section concernée*:</label>
                        <select id="id_section" name="id_section" required>
                            <option value="">Sélectionnez une section</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?php echo htmlspecialchars($section['nom']); ?>">
                                    <?php echo htmlspecialchars($section['nom'] . ' - Promotion ' . $section['promotion']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_systeme">Système concerné (optionnel):</label>
                        <select id="id_systeme" name="id_systeme">
                            <option value="">Aucun système spécifique</option>
                            <?php foreach ($systemes as $systeme): ?>
                                <option value="<?php echo $systeme['id_systeme']; ?>">
                                    <?php echo htmlspecialchars($systeme['nom_systeme']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_debut">Date de début*:</label>
                        <input type="date" id="date_debut" name="date_debut" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_fin">Date de fin (limite de rendu)*:</label>
                        <input type="date" id="date_fin" name="date_fin" required>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-consulter btn-back" onclick="window.location.href='menu_ajout.php'">Retour</button>
                        <button type="submit" class="btn-consulter">Créer le travail</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<script>
    // Valider que la date de fin est après la date de début
    document.querySelector('form').addEventListener('submit', function(e) {
        var dateDebut = new Date(document.getElementById('date_debut').value);
        var dateFin = new Date(document.getElementById('date_fin').value);
        
        if (dateFin < dateDebut) {
            e.preventDefault();
            alert('La date de fin doit être postérieure à la date de début.');
        }
    });
    
    // Définir la date minimale à aujourd'hui
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var yyyy = today.getFullYear();
    
    today = yyyy + '-' + mm + '-' + dd;
    document.getElementById('date_debut').setAttribute('min', today);
    document.getElementById('date_fin').setAttribute('min', today);
</script>
<?php include('../modeles/footer.php'); ?>
</body>
</html>
