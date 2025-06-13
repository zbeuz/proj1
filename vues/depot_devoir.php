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

// Vérifier si l'ID du travail est spécifié
if (!isset($_GET['travail_id']) || empty($_GET['travail_id'])) {
    header("Location: travail_a_faire.php");
    exit;
}

$travail_id = $_GET['travail_id'];
$id_utilisateur = $_SESSION['user_id'];

// Récupérer les informations sur le travail
try {
    $query_travail = "SELECT t.*, s.nom_systeme 
                      FROM travail_a_faire t 
                      LEFT JOIN systeme s ON t.id_systeme = s.id_systeme
                      WHERE t.id_travail = :travail_id";
    
    $stmt_travail = $connect->prepare($query_travail);
    $stmt_travail->bindParam(':travail_id', $travail_id, PDO::PARAM_INT);
    $stmt_travail->execute();
    
    if ($stmt_travail->rowCount() == 0) {
        header("Location: travail_a_faire.php");
        exit;
    }
    
    $travail = $stmt_travail->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'utilisateur est l'auteur du travail
    if ($travail['id_utilisateur'] != $id_utilisateur) {
        header("Location: travail_a_faire.php");
        exit;
    }
    
    // Récupérer les dépôts de devoirs pour ce travail
    $query_depots = "SELECT d.*, u.nom_utilisateur, u.prenom_utilisateur
                     FROM depot_devoir d
                     JOIN utilisateur u ON d.id_utilisateur = u.id_utilisateur
                     WHERE d.id_travail = :travail_id
                     ORDER BY d.date_depot DESC";
    
    $stmt_depots = $connect->prepare($query_depots);
    $stmt_depots->bindParam(':travail_id', $travail_id, PDO::PARAM_INT);
    $stmt_depots->execute();
    
    $depots = $stmt_depots->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données : " . $e->getMessage();
}

// Créer une table depot_devoir si elle n'existe pas
try {
    $check_table = $connect->query("SHOW TABLES LIKE 'depot_devoir'");
    if ($check_table->rowCount() == 0) {
        // Créer la table
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
} catch (PDOException $e) {
    // Silencieux
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dépôts de Devoirs</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">
</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn active">Travail à Faire</button>
        <button id="depot-devoir-button" class="sidebar-btn">Dépôt Devoir</button>
        <button id="ajout-button" class="sidebar-btn">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <?php if (isset($error_message)): ?>
                <div class="message error">
                    <?php echo $error_message; ?>
                </div>
            <?php else: ?>
                <a href="travail_a_faire.php" class="btn-consulter" style="background-color: #7f8c8d; margin-bottom: 20px;">← Retour aux travaux</a>
                
                <div class="document" style="margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <h1 style="font-size: 1.5rem; font-weight: bold; color: #2c3e50; margin: 0;"><?php echo htmlspecialchars($travail['titre']); ?></h1>
                        <div style="display: flex; gap: 15px; color: #7f8c8d; font-size: 0.9rem;">
                            <div>Du <?php echo date('d/m/Y', strtotime($travail['date_debut'])); ?></div>
                            <div>au <?php echo date('d/m/Y', strtotime($travail['date_fin'])); ?></div>
                        </div>
                    </div>
                    
                    <p style="display: inline-block; background-color: #3498db; color: white; padding: 3px 10px; border-radius: 15px; font-size: 0.8rem; margin-bottom: 10px;"><?php echo htmlspecialchars($travail['id_section']); ?></p>
                    
                    <p style="margin-bottom: 20px; color: #34495e; line-height: 1.5;">
                        <?php echo nl2br(htmlspecialchars($travail['description'])); ?>
                    </p>
                    
                    <?php if (!empty($travail['nom_systeme'])): ?>
                        <p style="display: inline-block; background-color: #3498db; color: white; padding: 4px 10px; border-radius: 15px; font-size: 0.8rem;">
                            Système associé: <?php echo htmlspecialchars($travail['nom_systeme']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <h2>Dépôts des élèves</h2>
                
                <?php if (empty($depots)): ?>
                    <div style="text-align: center; padding: 40px; background-color: #f9f9f9; border-radius: 8px; color: #7f8c8d;">
                        <p>Aucun élève n'a encore déposé de devoir pour ce travail.</p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <span style="background-color: #f39c12; color: white; padding: 8px 12px; border-radius: 20px; font-size: 0.9rem; font-weight: bold;">
                                👥 <?php echo count($depots); ?> élève<?php echo count($depots) > 1 ? 's' : ''; ?> ont déposé
                            </span>
                            <?php 
                                $avec_fichiers = count(array_filter($depots, function($d) { return !empty($d['fichier_depot']); }));
                                $avec_instructions = count(array_filter($depots, function($d) { return !empty($d['instructions']); }));
                            ?>
                            <span style="background-color: #27ae60; color: white; padding: 6px 10px; border-radius: 15px; font-size: 0.8rem;">
                                📎 <?php echo $avec_fichiers; ?> fichier<?php echo $avec_fichiers > 1 ? 's' : ''; ?>
                            </span>
                            <span style="background-color: #3498db; color: white; padding: 6px 10px; border-radius: 15px; font-size: 0.8rem;">
                                📝 <?php echo $avec_instructions; ?> instruction<?php echo $avec_instructions > 1 ? 's' : ''; ?>
                            </span>
                        </div>
                        
                        <?php if (count($depots) > 0): ?>
                            <a href="../controleurs/telecharger_tous.php?travail_id=<?php echo $travail_id; ?>" class="btn-consulter" style="background-color: #27ae60; font-size: 1rem; padding: 10px 20px; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                                📦 Télécharger tous les devoirs (ZIP)
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <?php foreach ($depots as $depot): ?>
                            <div class="column">
                                <div class="document">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px;">
                                        <div style="font-weight: bold; color: #2c3e50; display: flex; align-items: center;">
                                            <div style="width: 30px; height: 30px; border-radius: 50%; background-color: #3498db; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 8px;"><?php echo mb_substr($depot['prenom_utilisateur'], 0, 1); ?></div>
                                            <?php echo htmlspecialchars($depot['prenom_utilisateur'] . ' ' . $depot['nom_utilisateur']); ?>
                                        </div>
                                        <div style="color: #7f8c8d; font-size: 0.9rem;">
                                            Déposé le <?php echo date('d/m/Y à H:i', strtotime($depot['date_creation'])); ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($depot['instructions'])): ?>
                                        <div style="background-color: #f8f9fa; padding: 12px; border-radius: 8px; border-left: 4px solid #3498db; margin-bottom: 15px;">
                                            <div style="font-weight: bold; color: #2c3e50; margin-bottom: 8px; font-size: 0.9rem;">📝 Instructions de l'élève :</div>
                                            <div style="color: #34495e; line-height: 1.4;">
                                                <?php echo nl2br(htmlspecialchars($depot['instructions'])); ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div style="background-color: #ffeaa7; padding: 10px; border-radius: 8px; margin-bottom: 15px; color: #636e72; font-style: italic; font-size: 0.9rem;">
                                            ⚠️ Aucune instruction ou commentaire fourni par l'élève
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px; background-color: #ecf0f1; border-radius: 8px;">
                                        <?php if (!empty($depot['fichier_depot'])): ?>
                                            <div style="color: #27ae60; font-size: 0.9rem;">
                                                📎 Fichier déposé : <strong><?php echo htmlspecialchars(pathinfo($depot['fichier_depot'], PATHINFO_EXTENSION)); ?></strong>
                                            </div>
                                            <a href="../ressources/depots/<?php echo htmlspecialchars($depot['fichier_depot']); ?>" class="btn-consulter" style="background-color: #3498db; font-size: 0.9rem; padding: 8px 15px;" download>
                                                💾 Télécharger
                                            </a>
                                        <?php else: ?>
                                            <div style="color: #e74c3c; font-size: 0.9rem; font-style: italic;">
                                                ❌ Aucun fichier déposé
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html> 