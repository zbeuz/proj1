<?php
session_start();

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

include("../configurations/connexion.php");

// Récupérer l'ID de la section de l'utilisateur et l'ID du système si spécifié
$user_id = $_SESSION['user_id'];
$user_section = null;
$system_id = isset($_GET['system_id']) ? intval($_GET['system_id']) : null;

try {
    $query_user = "SELECT id_section FROM utilisateur WHERE id_utilisateur = ?";
    $stmt_user = $connect->prepare($query_user);
    $stmt_user->execute([$user_id]);
    $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data) {
        $user_section = $user_data['id_section'];
    }
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des informations de l'utilisateur : " . $e->getMessage();
}

// Récupérer les informations du système si un system_id est fourni
$system_info = null;
if ($system_id) {
    try {
        $query_system = "SELECT nom_systeme, description_systeme FROM systeme WHERE id_systeme = ?";
        $stmt_system = $connect->prepare($query_system);
        $stmt_system->execute([$system_id]);
        $system_info = $stmt_system->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des informations du système : " . $e->getMessage();
    }
}

// Récupérer les documents pédagogiques de la section de l'utilisateur
$documents = [];
if ($user_section) {
    try {
        if ($system_id) {
            // Filtrer par système spécifique
            $query_docs = "SELECT dp.*, s.nom as nom_section, sys.nom_systeme
                           FROM document_pedago dp
                           LEFT JOIN section s ON s.id_section = dp.id_section
                           LEFT JOIN systeme sys ON sys.id_systeme = dp.id_systeme
                           WHERE dp.id_section = ? AND dp.id_systeme = ?
                           ORDER BY dp.date_depot DESC";
            $stmt_docs = $connect->prepare($query_docs);
            $stmt_docs->execute([$user_section, $system_id]);
        } else {
            // Afficher tous les documents de la section
            $query_docs = "SELECT dp.*, s.nom as nom_section, sys.nom_systeme
                           FROM document_pedago dp
                           LEFT JOIN section s ON s.id_section = dp.id_section
                           LEFT JOIN systeme sys ON sys.id_systeme = dp.id_systeme
                           WHERE dp.id_section = ?
                           ORDER BY dp.date_depot DESC";
            $stmt_docs = $connect->prepare($query_docs);
            $stmt_docs->execute([$user_section]);
        }
        $documents = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des documents : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents Pédagogiques - Apprenti</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">
    <link rel="stylesheet" href="../ressources/styles/documents_pedagogiques.css">
</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="systeme-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn active">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
    </aside>
    
    <main class="main-content">
        <div class="search-bar">
            <input type="text" id="search-input" placeholder="Rechercher un document pédagogique">
            <button class="search-btn" onclick="searchDocuments()">🔍</button>
        </div>
        <div class="content-area" id="content-area">
            <?php if ($system_info): ?>
                <div class="system-header">
                    <div class="system-info">
                        <h1>Documents Pédagogiques - <?php echo htmlspecialchars($system_info['nom_systeme']); ?></h1>
                        <p><?php echo htmlspecialchars($system_info['description_systeme']); ?></p>
                    </div>
                </div>
                <button class="btn-consulter return-btn" onclick="window.location.href='documents_pedagogiques_apprenti.php'">← Retour à tous les documents</button>
            <?php else: ?>
                <h1>Documents Pédagogiques</h1>
                <p>Cette section vous permet de consulter les documents pédagogiques pour votre section.</p>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php else: ?>
                <div class="document-grid" id="document-grid">
                    <?php if (count($documents) > 0): ?>
                        <?php foreach ($documents as $doc): ?>
                            <div class="document-card">
                                <h3 class="document-title">
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
                                <div class="document-actions">
                                    <a href="../ressources/document/<?php echo htmlspecialchars($doc['fichier_document']); ?>" class="btn-consulter" target="_blank">Consulter le document</a>
                                </div>
                                <div class="document-info">
                                    <span>Section: <?php echo htmlspecialchars($doc['nom_section'] ?? 'Non spécifiée'); ?></span>
                                    <?php if (!empty($doc['nom_systeme'])): ?>
                                        <span>Système: <?php echo htmlspecialchars($doc['nom_systeme']); ?></span>
                                    <?php endif; ?>
                                    <span>Date: <?php echo date('d/m/Y', strtotime($doc['date_depot'])); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-documents">
                            <p>Aucun document pédagogique disponible pour votre section.</p>
                        </div>
                    <?php endif; ?>
                </div>
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

document.getElementById('search-input').addEventListener('keyup', function(event) {
    if (event.key === 'Enter') {
        searchDocuments();
    }
});
</script>
    
<script src="../ressources/js/app.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html> 