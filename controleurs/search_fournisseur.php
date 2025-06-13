<?php
session_start();
require_once('../configurations/connexion.php');

header('Content-Type: application/json');

// Vérifier si l'utilisateur est authentifié
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si l'utilisateur est un formateur (role = 1)
if ($_SESSION['user_role'] != 1) {
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si un terme de recherche est fourni
if (!isset($_GET['term']) || empty(trim($_GET['term']))) {
    echo json_encode([]);
    exit;
}

$searchTerm = '%' . trim($_GET['term']) . '%';

try {
    // Rechercher les fournisseurs correspondant au terme de recherche
    $query = "SELECT id_fournisseur, nom_entreprise, contact_nom, contact_prenom 
              FROM fournisseur 
              WHERE nom_entreprise LIKE :term OR contact_nom LIKE :term OR contact_prenom LIKE :term
              ORDER BY nom_entreprise
              LIMIT 10";
    
    $stmt = $connect->prepare($query);
    $stmt->bindParam(':term', $searchTerm, PDO::PARAM_STR);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?> 