<?php
include("../configurations/connexion.php");

try {
    // Fetch all systems
    $stmt = $connect->prepare("SELECT * FROM systeme ORDER BY nom_systeme ASC");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des systèmes : " . $e->getMessage();
    $result = [];
}
?> 