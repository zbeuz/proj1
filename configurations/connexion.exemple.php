<?php
$host = "localhost";
$user = "votre_nom_utilisateur";
$password = "votre_mot_de_passe";
$bdd = "nom_de_votre_base_de_donnees";

try {
    $connect = new PDO('mysql:host=' . $host . ";dbname=" . $bdd, $user, $password);
    $connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "connexion réussi ";
    
    // Vérifier si la table depot_devoir existe et si elle a la colonne fichier_depot
    $checkTableExists = $connect->query("SHOW TABLES LIKE 'depot_devoir'");
    if ($checkTableExists->rowCount() > 0) {
        // Vérifier si la colonne fichier_depot existe
        $checkColumn = $connect->query("SHOW COLUMNS FROM depot_devoir LIKE 'fichier_depot'");
        if ($checkColumn->rowCount() == 0) {
            // La colonne n'existe pas, l'ajouter
            try {
                $connect->exec("ALTER TABLE depot_devoir ADD COLUMN fichier_depot varchar(255) DEFAULT NULL");
            } catch (PDOException $e) {
                // Ignorer les erreurs potentielles, cela sera géré par les scripts qui utilisent cette colonne
            }
        }
    }
} catch (Exception $execp) {
    die("Error connecting to database: " . $execp->getMessage());
}
?> 