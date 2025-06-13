<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Vérifier si l'utilisateur est un apprenti (role = 2)
if ($_SESSION['user_role'] != 2) {
    header("Location: ../index.php");
    exit;
}

require_once("../configurations/connexion.php");

$id_utilisateur = $_SESSION['user_id'];

// Récupérer les informations de l'utilisateur et sa section
$stmt_user = $connect->prepare("
    SELECT u.id_section, s.nom as nom_section 
    FROM utilisateur u 
    LEFT JOIN section s ON u.id_section = s.id_section 
    WHERE u.id_utilisateur = ?
");
$stmt_user->execute([$id_utilisateur]);
$user_info = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user_info || !$user_info['nom_section']) {
    echo "Erreur: Section de l'utilisateur non trouvée.";
    exit;
}

$id_section = $user_info['nom_section']; // 'MELEC', 'SISR', ou 'SLAM'
$id_section_num = $user_info['id_section']; // 1, 2, ou 3

// Pour le debug - afficher la section utilisée
echo "<!-- Debug: Utilisateur ID: {$id_utilisateur}, Section ID: {$id_section_num}, Nom Section: {$id_section} -->";

// Récupérer les travaux assignés à la section de l'apprenti
try {
    $query = "SELECT t.*, s.nom_systeme, u.nom_utilisateur, u.prenom_utilisateur 
              FROM travail_a_faire t 
              LEFT JOIN systeme s ON t.id_systeme = s.id_systeme
              JOIN utilisateur u ON t.id_utilisateur = u.id_utilisateur
              WHERE t.id_section = :id_section 
              AND t.date_fin >= CURDATE()
              ORDER BY t.date_debut ASC";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':id_section', $id_section, PDO::PARAM_STR);
    $stmt->execute();
    
    $travaux_en_cours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les travaux passés
    $query_passes = "SELECT t.*, s.nom_systeme, u.nom_utilisateur, u.prenom_utilisateur 
                    FROM travail_a_faire t 
                    LEFT JOIN systeme s ON t.id_systeme = s.id_systeme
                    JOIN utilisateur u ON t.id_utilisateur = u.id_utilisateur
                    WHERE t.id_section = :id_section 
                    AND t.date_fin < CURDATE()
                    ORDER BY t.date_fin DESC";
    
    $stmt_passes = $connect->prepare($query_passes);
    $stmt_passes->bindParam(':id_section', $id_section, PDO::PARAM_STR);
    $stmt_passes->execute();
    
    $travaux_passes = $stmt_passes->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les dépôts de l'apprenti avec détails
    $query_depots = "SELECT d.*, t.date_fin FROM depot_devoir d 
                     JOIN travail_a_faire t ON d.id_travail = t.id_travail 
                     WHERE d.id_utilisateur = :id_utilisateur";
    $stmt_depots = $connect->prepare($query_depots);
    $stmt_depots->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
    $stmt_depots->execute();
    
    $depots_details = $stmt_depots->fetchAll(PDO::FETCH_ASSOC);
    $depots = array_column($depots_details, 'id_travail');
    
    // Créer un tableau associatif pour accès rapide aux détails des dépôts
    $depots_map = [];
    foreach ($depots_details as $depot) {
        $depots_map[$depot['id_travail']] = $depot;
    }
    
} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des données : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travail à Faire - Apprenti</title>
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
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <?php if (isset($error_message)): ?>
                <div class="message error">
                    <?php echo $error_message; ?>
                </div>
            <?php else: ?>
                <!-- Messages de session -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="message success">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="message error">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    </div>
                <?php endif; ?>
                
                <h1>Travail à Faire</h1>
                
                <?php if (empty($travaux_en_cours) && empty($travaux_passes)): ?>
                    <div class="no-travaux">
                        <p>Aucun travail n'a été assigné à votre section pour le moment.</p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($travaux_en_cours)): ?>
                        <h2 class="section-title">Travaux en cours</h2>
                        <div class="travaux-container">
                            <?php foreach ($travaux_en_cours as $travail): ?>
                                <?php
                                $is_submitted = in_array($travail['id_travail'], $depots);
                                $days_left = (strtotime($travail['date_fin']) - time()) / (60 * 60 * 24);
                                $is_urgent = $days_left <= 3;
                                $card_class = $is_submitted ? 'travail-card completed' : ($is_urgent ? 'travail-card urgent' : 'travail-card');
                                ?>
                                <div class="<?php echo $card_class; ?>">
                                    <?php if ($is_submitted): ?>
                                        <div class="status-tag status-completed">Déposé</div>
                                    <?php elseif ($is_urgent): ?>
                                        <div class="status-tag status-urgent">Urgent</div>
                                    <?php endif; ?>
                                    
                                    <div class="travail-header">
                                        <h2 class="travail-title"><?php echo htmlspecialchars($travail['titre']); ?></h2>
                                        <div class="travail-dates">
                                            <div class="date-item">
                                                <span>Début:</span>
                                                <strong><?php echo date('d/m/Y', strtotime($travail['date_debut'])); ?></strong>
                                            </div>
                                            <div class="date-item <?php echo $is_urgent ? 'date-warning' : 'date-normal'; ?>">
                                                <span>Fin:</span>
                                                <strong><?php echo date('d/m/Y', strtotime($travail['date_fin'])); ?></strong>
                                                <?php if ($is_urgent): ?>
                                                    <span>(<?php echo ceil($days_left); ?> jour<?php echo ceil($days_left) > 1 ? 's' : ''; ?> restant<?php echo ceil($days_left) > 1 ? 's' : ''; ?>)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="travail-section">Par: <?php echo htmlspecialchars($travail['prenom_utilisateur'] . ' ' . $travail['nom_utilisateur']); ?></div>
                                    
                                    <div class="travail-description">
                                        <?php echo nl2br(htmlspecialchars($travail['description'])); ?>
                                    </div>
                                    
                                    <div class="travail-meta">
                                        <div class="travail-systeme">
                                            <?php if (!empty($travail['nom_systeme'])): ?>
                                                <span>Système: <?php echo htmlspecialchars($travail['nom_systeme']); ?></span>
                                            <?php else: ?>
                                                <span>Aucun système associé</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                                        <div class="travail-actions">
                        <?php if (in_array($travail['id_travail'], $depots)): ?>
                            <?php 
                            $depot_info = $depots_map[$travail['id_travail']];
                            $date_limite_passee = strtotime($travail['date_fin']) < strtotime('today');
                            ?>
                            
                            <!-- Bouton pour consulter le dépôt -->
                            <?php if (!empty($depot_info['fichier_depot'])): ?>
                                <a href="../ressources/devoirs/<?php echo htmlspecialchars($depot_info['fichier_depot']); ?>" 
                                   class="btn-action btn-document" 
                                   target="_blank">Consulter mon dépôt</a>
                            <?php endif; ?>
                            
                            <?php if (!$date_limite_passee): ?>
                                <!-- Bouton pour supprimer et redéposer -->
                                <button class="btn-action btn-supprimer" 
                                        onclick="confirmerSuppression(<?= $depot_info['id_depot'] ?>, <?= $travail['id_travail'] ?>)">
                                    Supprimer et redéposer
                                </button>
                            <?php else: ?>
                                <span class="btn-action btn-deposer completed">Déjà déposé</span>
                            <?php endif; ?>
                            
                        <?php elseif (strtotime($travail['date_fin']) < strtotime('today')): ?>
                            <span class="btn-action btn-deposer expired">Date limite dépassée</span>
                        <?php else: ?>
                            <a href="#" class="btn-action btn-deposer" onclick="openDepotForm(<?= $travail['id_travail'] ?>)">Déposer le travail</a>
                        <?php endif; ?>

                        <?php if (!empty($travail['fichier_joint'])): ?>
                            <a href="../ressources/devoirs/<?php echo htmlspecialchars($travail['fichier_joint']); ?>" 
                               class="btn-action btn-deposer" 
                               target="_blank">Consulter le document du devoir</a>
                        <?php endif; ?>
                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($travaux_passes)): ?>
                        <h2 class="section-title">Travaux passés</h2>
                        <div class="travaux-container">
                            <?php foreach ($travaux_passes as $travail): ?>
                                <?php
                                $is_submitted = in_array($travail['id_travail'], $depots);
                                $card_class = $is_submitted ? 'travail-card completed expired' : 'travail-card expired';
                                ?>
                                <div class="<?php echo $card_class; ?>">
                                    <?php if ($is_submitted): ?>
                                        <div class="status-tag status-completed expired">Déposé</div>
                                    <?php else: ?>
                                        <div class="status-tag status-expired">Expiré</div>
                                    <?php endif; ?>
                                    
                                    <div class="travail-header">
                                        <h2 class="travail-title"><?php echo htmlspecialchars($travail['titre']); ?></h2>
                                        <div class="travail-dates">
                                            <div class="date-item">
                                                <span>Début:</span>
                                                <strong><?php echo date('d/m/Y', strtotime($travail['date_debut'])); ?></strong>
                                            </div>
                                            <div class="date-item">
                                                <span>Fin:</span>
                                                <strong><?php echo date('d/m/Y', strtotime($travail['date_fin'])); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="travail-section">Par: <?php echo htmlspecialchars($travail['prenom_utilisateur'] . ' ' . $travail['nom_utilisateur']); ?></div>
                                    
                                    <div class="travail-description">
                                        <?php echo nl2br(htmlspecialchars($travail['description'])); ?>
                                    </div>
                                    
                                    <div class="travail-meta">
                                        <div class="travail-systeme">
                                            <?php if (!empty($travail['nom_systeme'])): ?>
                                                <span>Système: <?php echo htmlspecialchars($travail['nom_systeme']); ?></span>
                                            <?php else: ?>
                                                <span>Aucun système associé</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                                        <div class="travail-actions">
                        <?php if ($is_submitted): ?>
                            <?php $depot_info = $depots_map[$travail['id_travail']]; ?>
                            
                            <!-- Bouton pour consulter le dépôt -->
                            <?php if (!empty($depot_info['fichier_depot'])): ?>
                                <a href="../ressources/devoirs/<?php echo htmlspecialchars($depot_info['fichier_depot']); ?>" 
                                   class="btn-action btn-document" 
                                   target="_blank">Consulter mon dépôt</a>
                            <?php else: ?>
                                <span class="btn-action btn-deposer completed expired">Dépôt sans fichier</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="btn-action btn-deposer expired">Date limite dépassée</span>
                        <?php endif; ?>

                        <?php if (!empty($travail['fichier_joint'])): ?>
                            <a href="../ressources/devoirs/<?php echo htmlspecialchars($travail['fichier_joint']); ?>" 
                               class="btn-action btn-deposer" 
                               target="_blank">Consulter le document du devoir</a>
                        <?php endif; ?>
                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<div id="depot-form-container">
    <div class="depot-form-modal">
        <h2>Déposer un travail</h2>
        <form action="../controleurs/submit_devoir.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" id="id_travail_input" name="id_travail" value="">
            <div class="form-group">
                <label for="instructions">Instructions ou commentaires :</label>
                <textarea name="instructions" id="instructions" rows="4"></textarea>
            </div>
            <div class="form-group">
                <label for="fichier_depot">Fichier (optionnel) :</label>
                <input type="file" name="fichier_depot" id="fichier_depot">
            </div>
            <div class="form-actions">
                <button type="button" onclick="closeDepotForm()" class="btn-cancel">Annuler</button>
                <button type="submit" class="btn-submit">Déposer</button>
            </div>
        </form>
    </div>
</div>

<script src="../ressources/js/app_apprenti.js"></script>
<script>
function openDepotForm(id_travail) {
    document.getElementById('id_travail_input').value = id_travail;
    document.getElementById('depot-form-container').style.display = 'block';
}

function closeDepotForm() {
    document.getElementById('depot-form-container').style.display = 'none';
}

function confirmerSuppression(id_depot, id_travail) {
    if (confirm("Êtes-vous sûr de vouloir supprimer votre dépôt ? Vous pourrez en déposer un nouveau.")) {
        // Créer un formulaire pour envoyer les données
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = '../controleurs/supprimer_depot.php';
        
        var inputDepot = document.createElement('input');
        inputDepot.type = 'hidden';
        inputDepot.name = 'id_depot';
        inputDepot.value = id_depot;
        
        var inputTravail = document.createElement('input');
        inputTravail.type = 'hidden';
        inputTravail.name = 'id_travail';
        inputTravail.value = id_travail;
        
        form.appendChild(inputDepot);
        form.appendChild(inputTravail);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
<?php include('../modeles/footer.php'); ?>
</body>
</html> 