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

// Nettoyage automatique des fichiers temporaires de plus de 24h
$tmp_dir = "../ressources/tmp/";
if (is_dir($tmp_dir)) {
    $files = glob($tmp_dir . "*.zip");
    foreach ($files as $file) {
        if (is_file($file) && (time() - filemtime($file)) > 86400) { // 24 heures
            unlink($file);
        }
    }
}

// Vérifier si l'ID du travail est spécifié
if (!isset($_GET['travail_id']) || empty($_GET['travail_id'])) {
    header("Location: ../vues/travail_a_faire.php");
    exit;
}

$travail_id = $_GET['travail_id'];
$id_utilisateur = $_SESSION['user_id'];

// Récupérer les informations sur le travail et vérifier si l'utilisateur est l'auteur
try {
    $query_travail = "SELECT * FROM travail_a_faire WHERE id_travail = :travail_id";
    $stmt_travail = $connect->prepare($query_travail);
    $stmt_travail->bindParam(':travail_id', $travail_id, PDO::PARAM_INT);
    $stmt_travail->execute();
    
    if ($stmt_travail->rowCount() == 0) {
        header("Location: ../vues/travail_a_faire.php");
        exit;
    }
    
    $travail = $stmt_travail->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si l'utilisateur est l'auteur du travail
    if ($travail['id_utilisateur'] != $id_utilisateur) {
        header("Location: ../vues/travail_a_faire.php");
        exit;
    }
    
    // Récupérer les dépôts de devoirs pour ce travail
    $query_depots = "SELECT d.*, u.nom_utilisateur, u.prenom_utilisateur
                     FROM depot_devoir d
                     JOIN utilisateur u ON d.id_utilisateur = u.id_utilisateur
                     WHERE d.id_travail = :travail_id
                     ORDER BY u.nom_utilisateur, u.prenom_utilisateur";
    
    $stmt_depots = $connect->prepare($query_depots);
    $stmt_depots->bindParam(':travail_id', $travail_id, PDO::PARAM_INT);
    $stmt_depots->execute();
    
    $depots = $stmt_depots->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($depots) == 0) {
        $_SESSION['error_message'] = "Aucun devoir n'a été déposé pour ce travail.";
        header("Location: ../vues/depot_devoir.php?travail_id=" . $travail_id);
        exit;
    }
    
    // Nettoyer le titre pour le nom de fichier
    $titre_clean = preg_replace('/[^a-zA-Z0-9_-]/', '_', $travail['titre']);
    $zip_name = "Devoirs_" . $titre_clean . "_" . date("Y-m-d_H-i") . ".zip";
    $zip_path = "../ressources/tmp/" . $zip_name;
    
    // Créer le répertoire temporaire s'il n'existe pas
    if (!file_exists('../ressources/tmp')) {
        mkdir('../ressources/tmp', 0777, true);
    }
    
    // Créer une nouvelle instance de ZipArchive
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        
        // Ajouter un fichier README avec les informations complètes sur le travail
        $readme_content = "=== INFORMATIONS SUR LE TRAVAIL ===\n";
        $readme_content .= "Titre : " . $travail['titre'] . "\n";
        $readme_content .= "Description : " . $travail['description'] . "\n";
        $readme_content .= "Section : " . $travail['id_section'] . "\n";
        $readme_content .= "Période : du " . date('d/m/Y', strtotime($travail['date_debut'])) . " au " . date('d/m/Y', strtotime($travail['date_fin'])) . "\n";
        $readme_content .= "Date de génération de l'archive : " . date('d/m/Y à H:i:s') . "\n";
        $readme_content .= "Nombre de dépôts : " . count($depots) . "\n\n";
        
        $readme_content .= "=== LISTE DES DÉPÔTS ===\n";
        
        foreach ($depots as $index => $depot) {
            $eleve = $depot['prenom_utilisateur'] . " " . $depot['nom_utilisateur'];
            $numero_eleve = sprintf("%02d", $index + 1);
            
            $readme_content .= "\n" . $numero_eleve . ". " . $eleve . "\n";
            $readme_content .= "   Date de dépôt : " . date('d/m/Y à H:i', strtotime($depot['date_creation'])) . "\n";
            
            // Créer un dossier par élève dans le zip
            $folder_name = $numero_eleve . "_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $depot['prenom_utilisateur'] . "_" . $depot['nom_utilisateur']) . "/";
            
            // Ajouter les instructions/commentaires de l'élève dans un fichier texte
            if (!empty($depot['instructions'])) {
                $instructions_content = "=== COMMENTAIRES ET INSTRUCTIONS DE L'ÉLÈVE ===\n";
                $instructions_content .= "Élève : " . $eleve . "\n";
                $instructions_content .= "Date de dépôt : " . date('d/m/Y à H:i', strtotime($depot['date_creation'])) . "\n\n";
                $instructions_content .= "Contenu :\n";
                $instructions_content .= $depot['instructions'] . "\n";
                
                $zip->addFromString($folder_name . "Instructions_" . $eleve . ".txt", $instructions_content);
                $readme_content .= "   Instructions : OUI (voir fichier Instructions_" . $eleve . ".txt)\n";
            } else {
                $readme_content .= "   Instructions : Aucune\n";
            }
            
            // Ajouter le fichier du devoir s'il existe
            if (!empty($depot['fichier_depot'])) {
                $file_path = "../ressources/depots/" . $depot['fichier_depot'];
                if (file_exists($file_path)) {
                    // Récupérer l'extension du fichier original
                    $ext = pathinfo($depot['fichier_depot'], PATHINFO_EXTENSION);
                    $original_name = pathinfo($depot['fichier_depot'], PATHINFO_FILENAME);
                    
                    // Créer un nom de fichier lisible
                    $filename = $folder_name . "Devoir_" . $eleve . "." . $ext;
                    
                    // Ajouter le fichier au zip
                    $zip->addFile($file_path, $filename);
                    $readme_content .= "   Fichier déposé : OUI (" . $filename . ")\n";
                    $readme_content .= "   Taille du fichier : " . round(filesize($file_path) / 1024, 2) . " KB\n";
                } else {
                    $readme_content .= "   Fichier indiqué mais non trouvé : " . $depot['fichier_depot'] . "\n";
                }
            } else {
                $readme_content .= "   Fichier déposé : Aucun\n";
            }
            
            $readme_content .= "   -----------------------------------------\n";
        }
        
        $readme_content .= "\n=== RÉSUMÉ ===\n";
        $readme_content .= "Total des élèves ayant déposé : " . count($depots) . "\n";
        $readme_content .= "Élèves avec fichiers : " . count(array_filter($depots, function($d) { return !empty($d['fichier_depot']); })) . "\n";
        $readme_content .= "Élèves avec instructions : " . count(array_filter($depots, function($d) { return !empty($d['instructions']); })) . "\n";
        
        // Ajouter le fichier README principal au zip
        $zip->addFromString("README_" . $titre_clean . ".txt", $readme_content);
        
        // Ajouter aussi un fichier CSV pour analyse
        $csv_content = "Numéro;Nom;Prénom;Date_Depot;Instructions;Fichier_Depose;Taille_Ko\n";
        foreach ($depots as $index => $depot) {
            $csv_content .= ($index + 1) . ";";
            $csv_content .= "\"" . $depot['nom_utilisateur'] . "\";";
            $csv_content .= "\"" . $depot['prenom_utilisateur'] . "\";";
            $csv_content .= date('d/m/Y H:i', strtotime($depot['date_creation'])) . ";";
            $csv_content .= (!empty($depot['instructions']) ? "OUI" : "NON") . ";";
            
            if (!empty($depot['fichier_depot'])) {
                $file_path = "../ressources/depots/" . $depot['fichier_depot'];
                if (file_exists($file_path)) {
                    $csv_content .= "OUI;";
                    $csv_content .= round(filesize($file_path) / 1024, 2);
                } else {
                    $csv_content .= "ERREUR;0";
                }
            } else {
                $csv_content .= "NON;0";
            }
            $csv_content .= "\n";
        }
        
        $zip->addFromString("Analyse_" . $titre_clean . ".csv", $csv_content);
        
        // Fermer le zip
        $zip->close();
        
        // Vérifier que le fichier a été créé
        if (!file_exists($zip_path)) {
            $_SESSION['error_message'] = "Erreur lors de la création de l'archive.";
            header("Location: ../vues/depot_devoir.php?travail_id=" . $travail_id);
            exit;
        }
        
        // Envoyer le fichier zip au navigateur
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=\"" . $zip_name . "\"");
        header("Content-Length: " . filesize($zip_path));
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        
        // Lire et envoyer le fichier
        if (readfile($zip_path) === false) {
            $_SESSION['error_message'] = "Erreur lors du téléchargement de l'archive.";
            header("Location: ../vues/depot_devoir.php?travail_id=" . $travail_id);
            exit;
        }
        
        // Supprimer le fichier zip temporaire après un délai
        register_shutdown_function(function() use ($zip_path) {
            if (file_exists($zip_path)) {
                unlink($zip_path);
            }
        });
        
        exit;
        
    } else {
        $_SESSION['error_message'] = "Impossible de créer l'archive ZIP.";
        header("Location: ../vues/depot_devoir.php?travail_id=" . $travail_id);
        exit;
    }
    
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: ../vues/depot_devoir.php?travail_id=" . $travail_id);
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    header("Location: ../vues/depot_devoir.php?travail_id=" . $travail_id);
    exit;
}
?> 