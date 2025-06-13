<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

// Vérifier si l'utilisateur est un formateur (role = 1) ou un apprenti (role = 2)
if ($_SESSION['user_role'] != 1 && $_SESSION['user_role'] != 2) {
    header("Location: ../index.php");
    exit;
}

include("../configurations/connexion.php");

// Récupérer l'ID du système
$systemId = isset($_GET['system_id']) ? intval($_GET['system_id']) : 0;

if ($systemId <= 0) {
    echo "Erreur: ID système manquant.";
    exit;
}

// Récupérer les informations du système
try {
    $stmtSystem = $connect->prepare("SELECT * FROM systeme WHERE id_systeme = ?");
    $stmtSystem->execute([$systemId]);
    $system = $stmtSystem->fetch(PDO::FETCH_ASSOC);
    
    if (!$system) {
        echo "Erreur: Système non trouvé.";
        exit;
    }
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des informations du système.";
    exit;
}

// Récupérer les documents techniques pour ce système
try {
    $whereClause = "dt.id_systeme = ?";
    $params = [$systemId];
    
    // Ajouter un filtre par catégorie technique si spécifié
    if (isset($_GET['categorie_technique']) && !empty($_GET['categorie_technique'])) {
        $whereClause .= " AND dt.id_categorie_technique = ?";
        $params[] = $_GET['categorie_technique'];
    }
    
    $stmtDocTech = $connect->prepare("
        SELECT dt.*, c.nom_categorie, ct.nom_categorie_technique, u.nom_utilisateur, u.prenom_utilisateur
        FROM document_technique dt
        JOIN categorie_document c ON dt.id_categorie = c.id_categorie
        LEFT JOIN categorie_technique ct ON dt.id_categorie_technique = ct.id_categorie_technique
        JOIN utilisateur u ON dt.id_utilisateur_actualiser = u.id_utilisateur
        WHERE $whereClause
        ORDER BY dt.date_depot DESC
    ");
    $stmtDocTech->execute($params);
    $docsTech = $stmtDocTech->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $docsTech = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents Techniques - <?php echo htmlspecialchars($system['nom_systeme']); ?></title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">
    <link rel="stylesheet" href="../ressources/styles/view_doc_technique.css">
</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
        <?php if ($_SESSION['user_role'] == 1): ?>
        <button id="ajout-button" class="sidebar-btn">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
        <?php endif; ?>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <div class="system-header">
                <img src="../ressources/systeme/<?php echo htmlspecialchars($system['photo_systeme']); ?>" alt="<?php echo htmlspecialchars($system['nom_systeme']); ?>">
                <div class="system-info">
                    <h1><?php echo htmlspecialchars($system['nom_systeme']); ?></h1>
                    <p><?php echo htmlspecialchars($system['description_systeme']); ?></p>
                </div>
            </div>
            
            <h2>Documents Techniques</h2>
            
            <div class="filter-bar">
                <form method="get" action="" id="filterForm">
                    <input type="hidden" name="system_id" value="<?php echo $systemId; ?>">
                    <label for="categorie_technique">Filtrer par type:</label>
                    <select name="categorie_technique" id="categorie_technique" onchange="document.getElementById('filterForm').submit();">
                        <option value="">Tous les documents</option>
                        <?php
                        try {
                            $stmt_cat = $connect->prepare("SELECT * FROM categorie_technique ORDER BY nom_categorie_technique");
                            $stmt_cat->execute();
                            $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
                            
                            $filter_cat = isset($_GET['categorie_technique']) ? $_GET['categorie_technique'] : '';
                            
                            foreach ($categories as $cat) {
                                $selected = ($filter_cat == $cat['id_categorie_technique']) ? 'selected' : '';
                                echo '<option value="'.$cat['id_categorie_technique'].'" '.$selected.'>'.$cat['nom_categorie_technique'].'</option>';
                            }
                        } catch (PDOException $e) {
                            // Silence the error
                        }
                        ?>
                    </select>
                </form>
            </div>
            
            <div class="doc-list">
                <?php if (empty($docsTech)): ?>
                    <p>Aucun document technique disponible pour ce système.</p>
                <?php else: ?>
                    <?php foreach ($docsTech as $doc): ?>
                        <div class="doc-item">
                            <h3><?php echo htmlspecialchars($doc['nom_document']); ?></h3>
                            <?php if (!empty($doc['nom_categorie_technique'])): ?>
                                <span class="category-tag"><?php echo htmlspecialchars($doc['nom_categorie_technique']); ?></span>
                            <?php endif; ?>
                            <a href="../ressources/document/<?php echo htmlspecialchars($doc['fichier_document']); ?>" class="btn-consulter" target="_blank">Consulter le document</a>
                            <div class="doc-meta">
                                <span>Catégorie: <?php echo htmlspecialchars($doc['nom_categorie']); ?></span>
                                <span>Déposé par: <?php echo htmlspecialchars($doc['prenom_utilisateur'] . ' ' . $doc['nom_utilisateur']); ?></span>
                                <span>Date: <?php echo htmlspecialchars($doc['date_depot']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button class="btn-consulter return-btn" onclick="window.history.back()">Retour</button>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html>
