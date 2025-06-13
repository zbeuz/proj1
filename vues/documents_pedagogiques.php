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

include("../configurations/connexion.php");

// Récupérer toutes les sections pour le filtre
$sections = [];
try {
    $query_sections = "SELECT id_section, nom, specialite FROM section ORDER BY nom";
    $stmt_sections = $connect->prepare($query_sections);
    $stmt_sections->execute();
    $sections = $stmt_sections->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des sections : " . $e->getMessage();
}

// Récupérer les documents pédagogiques du formateur
$filter_section = isset($_GET['section']) ? $_GET['section'] : null;
$filter_system = isset($_GET['system_id']) ? intval($_GET['system_id']) : null;
$user_id = $_SESSION['user_id'];
$documents = [];
$system_name = '';

try {
    if ($filter_system) {
        // Récupérer les informations complètes du système pour l'affichage
        $query_system = "SELECT * FROM systeme WHERE id_systeme = :system_id";
        $stmt_system = $connect->prepare($query_system);
        $stmt_system->bindParam(':system_id', $filter_system, PDO::PARAM_INT);
        $stmt_system->execute();
        $system_data = $stmt_system->fetch(PDO::FETCH_ASSOC);
        $system_name = $system_data ? $system_data['nom_systeme'] : '';
        
        // Récupérer les documents pédagogiques pour ce système spécifique
        $query_docs = "SELECT dp.*, s.nom as nom_section, s.specialite as specialite_section, u.nom_utilisateur, u.prenom_utilisateur
                       FROM document_pedago dp
                       LEFT JOIN section s ON s.id_section = dp.id_section
                       LEFT JOIN utilisateur u ON dp.id_utilisateur_deposer = u.id_utilisateur
                       WHERE dp.id_systeme = :system_id";
        
        $stmt_docs = $connect->prepare($query_docs);
        $stmt_docs->bindParam(':system_id', $filter_system, PDO::PARAM_INT);
    } else {
        // Récupérer TOUS les documents pédagogiques (comportement pour la sidebar)
        $query_docs = "SELECT dp.*, s.nom as nom_section, s.specialite as specialite_section, u.nom_utilisateur, u.prenom_utilisateur
                       FROM document_pedago dp
                       LEFT JOIN section s ON s.id_section = dp.id_section
                       LEFT JOIN utilisateur u ON dp.id_utilisateur_deposer = u.id_utilisateur";
        
        if ($filter_section) {
            $query_docs .= " WHERE dp.id_section = :section_id";
        }
        
        $query_docs .= " ORDER BY dp.date_depot DESC";
        
        $stmt_docs = $connect->prepare($query_docs);
        
        if ($filter_section) {
            $stmt_docs->bindParam(':section_id', $filter_section, PDO::PARAM_INT);
        }
    }
    
    $stmt_docs->execute();
    $documents = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des documents : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents Pédagogiques - Formateur</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">
    <link rel="stylesheet" href="../ressources/styles/documents_pedagogiques.css">
</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn active">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
        <button id="ajout-button" class="sidebar-btn">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Rechercher un document pédagogique">
            <button class="search-btn" onclick="searchDocuments()">🔍</button>
        </div>
        <div class="content-area" id="content-area">
            <?php if ($filter_system && $system_data): ?>
                <div class="system-header">
                    <img src="../ressources/systeme/<?php echo htmlspecialchars($system_data['photo_systeme']); ?>" alt="<?php echo htmlspecialchars($system_name); ?>">
                    <div class="system-info">
                        <h1><?php echo htmlspecialchars($system_name); ?></h1>
                        <p><?php echo htmlspecialchars($system_data['description_systeme']); ?></p>
                    </div>
                </div>
                
                <h2>Documents Pédagogiques</h2>
            <?php else: ?>
                <h1>Documents Pédagogiques</h1>
                <p>Cette section vous permet de gérer les documents pédagogiques pour toutes les sections.</p>
                <a href="ajout_document_pedago.php" class="add-doc-btn">+ Ajouter un document</a>
            <?php endif; ?>
            
            <?php if (!$filter_system): ?>
            <div class="filter-bar">
                <form method="get" action="" id="filterForm">
                    <label for="section-filter">Filtrer par section:</label>
                    <select id="section-filter" name="section" onchange="document.getElementById('filterForm').submit();">
                        <option value="">Toutes les sections</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section['id_section']; ?>" <?php echo $filter_section == $section['id_section'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($section['nom'] . ' - ' . $section['specialite']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="doc-list">
                <?php if (isset($error_message)): ?>
                    <div class="error-message">
                        <?php echo $error_message; ?>
                    </div>
                <?php elseif (empty($documents)): ?>
                    <p>Aucun document pédagogique disponible <?php echo $filter_system ? 'pour ce système' : ($filter_section ? 'pour cette section' : ''); ?>.</p>
                <?php else: ?>
                    <?php foreach ($documents as $doc): ?>
                        <div class="doc-item">
                            <h3>
                                <?php
                                // Déterminer le nom à afficher
                                if (is_numeric($doc['nom_document'])) {
                                    // Extraire un nom à partir du fichier si nom_document est un nombre
                                    $file_name = pathinfo($doc['fichier_document'], PATHINFO_FILENAME);
                                    $file_name = preg_replace('/^doc_peda_\d+_[a-f0-9]+$/', 'Document pédagogique', $file_name);
                                    echo htmlspecialchars($file_name);
                                } else {
                                    // Utiliser le nom si c'est déjà une chaîne
                                    echo htmlspecialchars($doc['nom_document']);
                                }
                                ?>
                            </h3>
                            <a href="../ressources/document/<?php echo htmlspecialchars($doc['fichier_document']); ?>" class="btn-consulter" target="_blank">Consulter le document</a>
                            <?php if (!$filter_system): ?>
                                <button class="btn-delete" onclick="confirmDelete(<?php echo $doc['id_document_pedago']; ?>)">Supprimer</button>
                            <?php endif; ?>
                            <div class="doc-meta">
                                <span>Section: <?php echo htmlspecialchars($doc['nom_section'] . ' - ' . $doc['specialite_section']); ?></span>
                                <?php if (isset($doc['nom_utilisateur'])): ?>
                                    <span>Déposé par: <?php echo htmlspecialchars($doc['prenom_utilisateur'] . ' ' . $doc['nom_utilisateur']); ?></span>
                                <?php endif; ?>
                                <span>Date: <?php echo htmlspecialchars($doc['date_depot']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if ($filter_system): ?>
                <button class="btn-consulter return-btn" onclick="window.location.href='accueil_formateur.php'">Retour</button>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
function searchDocuments() {
    const searchValue = document.getElementById('search-input').value.toLowerCase();
    const documents = document.querySelectorAll('.document-card');
    
    documents.forEach(doc => {
        const title = doc.querySelector('.document-title').textContent.toLowerCase();
        if (title.includes(searchValue)) {
            doc.style.display = 'block';
        } else {
            doc.style.display = 'none';
        }
    });
}

function confirmDelete(documentId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce document pédagogique ?')) {
        window.location.href = '../controleurs/supprimer_document_pedago.php?id=' + documentId;
    }
}

document.getElementById('search-input').addEventListener('keyup', function(event) {
    if (event.key === 'Enter') {
        searchDocuments();
    }
});
</script>

<script src="../ressources/js/app_formateur.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html> 