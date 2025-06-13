<?php
session_start();
include("../configurations/connexion.php");
include("../modeles/Document.php");

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour effectuer une recherche.']);
    exit();
}

// Vérifier que le terme de recherche est fourni
if (!isset($_GET['query']) || empty(trim($_GET['query']))) {
    echo json_encode(['success' => false, 'message' => 'Veuillez fournir un terme de recherche.']);
    exit();
}

$searchTerm = trim($_GET['query']);

try {
    // Initialiser le modèle de document
    $documentModel = new Document($connect);

    // Rechercher les documents techniques et pédagogiques
    $results = [];

    // Recherche dans les documents techniques
    $stmt = $connect->prepare("
        SELECT 
            dt.id_document_technique as id,
            dt.nom_document as title,
            'technique' as type,
            s.nom_systeme as system,
            c.nom_categorie as category,
            dt.date_depot as date,
            u.nom_utilisateur as author,
            dt.fichier_document as file,
            s.id_systeme as system_id
        FROM document_technique dt
        JOIN systeme s ON dt.id_systeme = s.id_systeme
        JOIN categorie_document c ON dt.id_categorie = c.id_categorie
        JOIN utilisateur u ON dt.id_utilisateur = u.id_utilisateur
        WHERE dt.nom_document LIKE ? OR s.nom_systeme LIKE ?
        ORDER BY dt.date_depot DESC
    ");
    $stmt->execute(['%' . $searchTerm . '%', '%' . $searchTerm . '%']);
    $technicalResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results = array_merge($results, $technicalResults);

    // Recherche dans les documents pédagogiques
    $stmt = $connect->prepare("
        SELECT 
            dp.id_document_pedago as id,
            dp.nom_document as title,
            'pedagogique' as type,
            s.nom_systeme as system,
            c.nom_categorie as category,
            dp.date_depot as date,
            u.nom_utilisateur as author,
            dp.fichier_document as file,
            s.id_systeme as system_id
        FROM document_pedago dp
        JOIN systeme s ON dp.id_systeme = s.id_systeme
        JOIN categorie_document c ON dp.id_categorie = c.id_categorie
        JOIN utilisateur u ON dp.id_utilisateur = u.id_utilisateur
        WHERE dp.nom_document LIKE ? OR s.nom_systeme LIKE ?
        ORDER BY dp.date_depot DESC
    ");
    $stmt->execute(['%' . $searchTerm . '%', '%' . $searchTerm . '%']);
    $pedagogicalResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results = array_merge($results, $pedagogicalResults);

    // Trier les résultats par date (les plus récents d'abord)
    usort($results, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    echo json_encode([
        'success' => true,
        'results' => $results,
        'count' => count($results)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la recherche: ' . $e->getMessage()
    ]);
}
?> 