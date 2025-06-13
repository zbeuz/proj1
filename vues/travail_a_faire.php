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

// Récupérer les travaux à faire créés par le formateur
$id_utilisateur = $_SESSION['user_id'];
$travaux = [];

try {
    // Vérifier si la table existe
    $check_table = $connect->query("SHOW TABLES LIKE 'travail_a_faire'");
    if ($check_table->rowCount() > 0) {
        $query = "SELECT t.*, s.nom_systeme, COUNT(d.id_depot) as nb_depots 
                 FROM travail_a_faire t 
                 LEFT JOIN systeme s ON t.id_systeme = s.id_systeme
                 LEFT JOIN depot_devoir d ON t.id_travail = d.id_travail
                 WHERE t.id_utilisateur = :id_utilisateur
                 GROUP BY t.id_travail
                 ORDER BY t.date_fin ASC";
        
        $stmt = $connect->prepare($query);
        $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
        $stmt->execute();
        $travaux = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des travaux : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travaux à Faire</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">
    <link rel="stylesheet" href="../ressources/styles/travail_a_faire.css">
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
            <h1>Travaux à Faire</h1>
            
            <a href="ajout_travail.php" class="btn-consulter add-travail-btn">
                Ajouter un nouveau travail
            </a>
            
            <?php if (isset($error_message)): ?>
                <div class="message error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php 
            // Afficher les messages de suppression
            if (isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
                <div class="message success">
                    Le travail a été supprimé avec succès.
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="message error">
                    <?php 
                    switch ($_GET['error']) {
                        case 'delete_failed':
                            echo "Échec lors de la suppression du travail.";
                            if (isset($_GET['message'])) echo " Détails: " . htmlspecialchars($_GET['message']);
                            break;
                        case 'unauthorized':
                            echo "Vous n'êtes pas autorisé à supprimer ce travail.";
                            break;
                        case 'missing_id':
                            echo "ID du travail non spécifié.";
                            break;
                        default:
                            echo "Une erreur est survenue.";
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['warning']) && $_GET['warning'] == 'partial_delete'): ?>
                <div class="message warning">
                    Le travail a été partiellement supprimé, mais des erreurs sont survenues.
                    <?php if (isset($_GET['errors'])): ?>
                        <br>Détails: <?php echo htmlspecialchars($_GET['errors']); ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($travaux)): ?>
                <div class="no-travaux">
                    <p>Vous n'avez pas encore créé de travaux.</p>
                </div>
            <?php else: ?>
                <div class="travaux-container">
                    <?php foreach ($travaux as $travail): ?>
                        <?php 
                            $date_fin = new DateTime($travail['date_fin']);
                            $now = new DateTime();
                            $interval = $now->diff($date_fin);
                            $days_left = $interval->days;
                            $date_class = $date_fin < $now ? 'date-warning' : 'date-normal';
                        ?>
                        <div class="travail-card">
                            <div class="travail-header">
                                <h2 class="travail-title"><?php echo htmlspecialchars($travail['titre']); ?></h2>
                                <div class="travail-dates">
                                    <div class="date-item">
                                        <span>Début:</span>
                                        <strong><?php echo date('d/m/Y', strtotime($travail['date_debut'])); ?></strong>
                                    </div>
                                    <div class="date-item <?php echo $date_class; ?>">
                                        <span>Fin:</span>
                                        <strong><?php echo date('d/m/Y', strtotime($travail['date_fin'])); ?></strong>
                                        <?php if ($date_fin >= $now): ?>
                                            <span>(dans <?php echo $days_left; ?> jours)</span>
                                        <?php else: ?>
                                            <span>(terminé)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="travail-section"><?php echo htmlspecialchars($travail['id_section']); ?></div>
                            
                            <div class="travail-description">
                                <?php echo nl2br(htmlspecialchars($travail['description'])); ?>
                            </div>

                            <?php if (!empty($travail['fichier_joint'])): ?>
                                <div class="travail-actions">
                                    <a href="../ressources/devoirs/<?php echo htmlspecialchars($travail['fichier_joint']); ?>" class="btn-action btn-document" target="_blank">Consulter le document</a>
                                </div>
                            <?php endif; ?>

                            <div class="travail-meta">
                                <div class="travail-systeme">
                                    <?php if (!empty($travail['nom_systeme'])): ?>
                                        <span>Système: <?php echo htmlspecialchars($travail['nom_systeme']); ?></span>
                                    <?php else: ?>
                                        <span>Aucun système associé</span>
                                    <?php endif; ?>
                                </div>
                                <div class="travail-date-creation">
                                    Créé le <?php echo date('d/m/Y', strtotime($travail['date_creation'])); ?>
                                </div>
                            </div>
                            
                            <div class="travail-actions">
                                <a href="depot_devoir.php?travail_id=<?php echo $travail['id_travail']; ?>" class="btn-action btn-voir-depots">
                                    Voir les dépôts
                                    <?php if ($travail['nb_depots'] > 0): ?>
                                        <span class="badge"><?php echo $travail['nb_depots']; ?></span>
                                    <?php endif; ?>
                                </a>
                                <button class="btn-action btn-modifier" onclick="location.href='modifier_travail.php?id=<?php echo $travail['id_travail']; ?>'">Modifier</button>
                                <button class="btn-action btn-supprimer" onclick="confirmerSuppression(<?php echo $travail['id_travail']; ?>)">Supprimer</button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function confirmerSuppression(id) {
    if (confirm("Êtes-vous sûr de vouloir supprimer ce travail ? Cette action est irréversible.")) {
        window.location.href = "../controleurs/supprimer_travail.php?id=" + id;
    }
}
</script>

<script src="../ressources/js/app_formateur.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html> 