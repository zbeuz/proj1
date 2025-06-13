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

// Récupérer les travaux à faire existants
try {
    $query_travaux = "SHOW TABLES LIKE 'travail_a_faire'";
    $stmt_travaux = $connect->prepare($query_travaux);
    $stmt_travaux->execute();
    
    if ($stmt_travaux->rowCount() > 0) {
        $query_travaux = "SELECT id_travail, titre, id_section, date_fin FROM travail_a_faire ORDER BY date_fin DESC";
        $stmt_travaux = $connect->prepare($query_travaux);
        $stmt_travaux->execute();
        $travaux = $stmt_travaux->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $travaux = [];
        $message = "La table des travaux n'existe pas encore. Veuillez d'abord créer un travail.";
        $messageType = "warning";
    }
} catch (Exception $e) {
    $message = "Erreur lors de la récupération des travaux : " . $e->getMessage();
    $messageType = "error";
    $travaux = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_travail = $_POST['id_travail'];
    $instructions = $_POST['instructions'];
    $date_depot = date('Y-m-d');
    $id_utilisateur = $_SESSION['user_id'];
    
    // Vérifier si la table depot_devoir existe, sinon la créer
    try {
        $connect->beginTransaction();
        
        // Vérifier si la table existe
        $check_table = $connect->query("SHOW TABLES LIKE 'depot_devoir'");
        if ($check_table->rowCount() == 0) {
            // Créer la table si elle n'existe pas
            $create_table = "CREATE TABLE `depot_devoir` (
                `id_depot` int NOT NULL AUTO_INCREMENT,
                `id_travail` int NOT NULL,
                `instructions` text,
                `date_depot` date NOT NULL,
                `id_utilisateur` int NOT NULL,
                `date_creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `fichier_depot` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`id_depot`),
                KEY `fk_depot_travail` (`id_travail`),
                KEY `fk_depot_utilisateur` (`id_utilisateur`),
                CONSTRAINT `fk_depot_travail` FOREIGN KEY (`id_travail`) REFERENCES `travail_a_faire` (`id_travail`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_depot_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;";
            
            $connect->exec($create_table);
        }
        
        // Insérer le nouveau dépôt
        $query = "INSERT INTO depot_devoir (id_travail, instructions, date_depot, id_utilisateur) 
                 VALUES (:id_travail, :instructions, :date_depot, :id_utilisateur)";
        
        $stmt = $connect->prepare($query);
        $stmt->bindParam(':id_travail', $id_travail, PDO::PARAM_INT);
        $stmt->bindParam(':instructions', $instructions, PDO::PARAM_STR);
        $stmt->bindParam(':date_depot', $date_depot, PDO::PARAM_STR);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        
        $stmt->execute();
        $connect->commit();
        
        $message = "Dépôt de devoir créé avec succès.";
        $messageType = "success";
    } catch (Exception $e) {
        // Vérifier si une transaction est active avant de faire un rollback
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        $message = "Erreur lors de la création du dépôt : " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Dépôt de Devoir</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">
   
</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
        <button id="depot-devoir-button" class="sidebar-btn">Dépôt Devoir</button>
        <button id="ajout-button" class="sidebar-btn active">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <h1>Créer un Dépôt de Devoir</h1>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($travaux)): ?>
                <div class="styled-form">
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="id_travail">Travail associé*:</label>
                            <select id="id_travail" name="id_travail" required onchange="showTravailInfo()">
                                <option value="">Sélectionnez un travail</option>
                                <?php foreach ($travaux as $travail): ?>
                                    <option value="<?php echo $travail['id_travail']; ?>" 
                                            data-section="<?php echo htmlspecialchars($travail['id_section']); ?>"
                                            data-date="<?php echo htmlspecialchars($travail['date_fin']); ?>">
                                        <?php echo htmlspecialchars($travail['titre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="travail-info" class="travail-info">
                                <p><strong>Section:</strong> <span id="section-info"></span></p>
                                <p><strong>Date limite:</strong> <span id="date-info"></span></p>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="instructions">Instructions pour le dépôt*:</label>
                            <textarea id="instructions" name="instructions" required placeholder="Précisez les instructions pour le dépôt de devoir (format attendu, critères d'évaluation, etc.)"></textarea>
                        </div>
                        
                        <div class="button-group">
                            <button type="button" class="btn-consulter btn-back" onclick="window.location.href='menu_ajout.php'">Retour</button>
                            <button type="submit" class="btn-consulter">Créer le dépôt</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <p>Aucun travail disponible. Veuillez d'abord <a href="ajout_travail.php">créer un travail</a>.</p>
                <button type="button" class="btn-consulter btn-back" onclick="window.location.href='menu_ajout.php'">Retour</button>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<script>
    function showTravailInfo() {
        var select = document.getElementById('id_travail');
        var travailInfo = document.getElementById('travail-info');
        var sectionInfo = document.getElementById('section-info');
        var dateInfo = document.getElementById('date-info');
        
        if (select.value) {
            var selectedOption = select.options[select.selectedIndex];
            sectionInfo.textContent = selectedOption.getAttribute('data-section');
            
            // Formater la date
            var rawDate = selectedOption.getAttribute('data-date');
            var dateObj = new Date(rawDate);
            var options = { year: 'numeric', month: 'long', day: 'numeric' };
            dateInfo.textContent = dateObj.toLocaleDateString('fr-FR', options);
            
            travailInfo.style.display = 'block';
        } else {
            travailInfo.style.display = 'none';
        }
    }
</script>
<?php include('../modeles/footer.php'); ?>
</body>
</html>
