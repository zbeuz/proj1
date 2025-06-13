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

require_once('../configurations/connexion.php');

// Vérifier si on affiche les fournisseurs d'un système spécifique ou un fournisseur spécifique
$system_id = isset($_GET['system_id']) ? intval($_GET['system_id']) : 0;
$fournisseur_id = isset($_GET['fournisseur_id']) ? intval($_GET['fournisseur_id']) : 0;
$system_name = '';

// Récupérer la liste des fournisseurs
$fournisseurs = [];
try {
    if ($system_id > 0) {
        // Afficher uniquement le fournisseur du système spécifique
        $query = "SELECT s.nom_systeme, f.* 
                  FROM systeme s
                  LEFT JOIN fournisseur f ON s.id_fournisseur = f.id_fournisseur
                  WHERE s.id_systeme = ?";
        $stmt = $connect->prepare($query);
        $stmt->execute([$system_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $system_name = $result['nom_systeme'];
            if ($result['id_fournisseur']) {
                $fournisseurs = [$result];
            }
        }
    } elseif ($fournisseur_id > 0) {
        // Afficher un fournisseur spécifique (depuis la recherche)
        $query = "SELECT * FROM fournisseur WHERE id_fournisseur = ?";
        $stmt = $connect->prepare($query);
        $stmt->execute([$fournisseur_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $fournisseurs = [$result];
        }
    } else {
        // Afficher tous les fournisseurs
        $query = "SELECT * FROM fournisseur ORDER BY nom_entreprise";
        $stmt = $connect->prepare($query);
        $stmt->execute();
        $fournisseurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // Table n'existe pas ou autre erreur
    $error_message = "La table fournisseur n'est pas encore disponible. Veuillez contacter l'administrateur.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Fournisseurs</title>
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
            <?php if ($system_id > 0): ?>
                <h1>Fournisseur du système : <?php echo htmlspecialchars($system_name); ?></h1>
                <button class="btn-consulter" style="background-color: #666; margin-bottom: 20px;" onclick="window.location.href='accueil_formateur.php'">Retour aux systèmes</button>
            <?php elseif ($fournisseur_id > 0): ?>
                <h1>Détails du Fournisseur</h1>
                <button class="btn-consulter" style="background-color: #666; margin-bottom: 20px;" onclick="window.location.href='liste_fournisseurs.php'">Retour à la liste</button>
            <?php else: ?>
                <h1>Liste des Fournisseurs</h1>
                <button class="btn-consulter" style="margin-bottom: 20px;" onclick="window.location.href='ajout_fournisseur.php'">+ Ajouter un fournisseur</button>
            <?php endif; ?>
            
            <div>
                <?php if ($system_id == 0): ?>
                    
                <?php endif; ?>
                
                <?php if (isset($error_message)): ?>
                    <div style="color: red; padding: 15px; background-color: #ffeeee; border-radius: 8px; margin: 10px 0; border-left: 4px solid #e74c3c; font-weight: bold;">
                        <?php echo $error_message; ?>
                    </div>
                <?php elseif (($system_id > 0 || $fournisseur_id > 0) && empty($fournisseurs)): ?>
                    <div style="color: #856404; padding: 15px; background-color: #fff3cd; border-radius: 8px; margin: 10px 0; border-left: 4px solid #ffc107; font-weight: bold;">
                        <?php if ($system_id > 0): ?>
                            Ce système n'a pas encore de fournisseur associé.
                        <?php else: ?>
                            Fournisseur non trouvé.
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                <table class="fournisseur-table">
                    <thead>
                        <tr>
                            <th>Entreprise</th>
                            <th>Contact</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Adresse</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($fournisseurs) > 0): ?>
                            <?php foreach ($fournisseurs as $fournisseur): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($fournisseur['nom_entreprise']); ?></td>
                                <td><?php echo htmlspecialchars($fournisseur['contact_prenom'] . ' ' . $fournisseur['contact_nom']); ?></td>
                                <td><?php echo htmlspecialchars($fournisseur['telephone']); ?></td>
                                <td><?php echo htmlspecialchars($fournisseur['email']); ?></td>
                                <td><?php echo htmlspecialchars($fournisseur['adresse']); ?></td>
                                <td>
                                    <button class="btn-consulter" onclick="supprimerFournisseur(<?php echo $fournisseur['id_fournisseur']; ?>)">Supprimer</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #666; font-style: italic;">Aucun fournisseur trouvé</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<script>
    function supprimerFournisseur(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce fournisseur ?')) {
            window.location.href = '../controleurs/supprimer_fournisseur.php?id=' + id;
        }
    }
</script>
<?php include('../modeles/footer.php'); ?>
</body>
</html> 