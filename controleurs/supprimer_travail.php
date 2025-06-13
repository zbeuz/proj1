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

// Vérifier si l'ID du travail est spécifié
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: travail_a_faire.php?error=missing_id");
    exit;
}

$id_travail = $_GET['id'];
$id_utilisateur = $_SESSION['user_id'];
$log_errors = [];

try {
    // Vérifier si l'utilisateur est l'auteur du travail
    $query_check = "SELECT id_travail FROM travail_a_faire WHERE id_travail = :id_travail AND id_utilisateur = :id_utilisateur";
    $stmt_check = $connect->prepare($query_check);
    $stmt_check->bindParam(':id_travail', $id_travail, PDO::PARAM_INT);
    $stmt_check->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
    $stmt_check->execute();
    
    if ($stmt_check->rowCount() == 0) {
        // L'utilisateur n'est pas autorisé à supprimer ce travail
        header("Location: travail_a_faire.php?error=unauthorized");
        exit;
    }
    
    // Désactiver temporairement les clés étrangères pour permettre la suppression
    $connect->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    $connect->beginTransaction();
    
    // 1. Vérifier si la table depot_devoir existe et supprimer les dépôts associés
    $table_exists = false;
    $check_table = $connect->query("SHOW TABLES LIKE 'depot_devoir'");
    if ($check_table->rowCount() > 0) {
        $table_exists = true;
        
        // 2. Supprimer tous les dépôts de devoirs associés à ce travail
        try {
            $query_delete_depots = "DELETE FROM depot_devoir WHERE id_travail = :id_travail";
            $stmt_delete_depots = $connect->prepare($query_delete_depots);
            $stmt_delete_depots->bindParam(':id_travail', $id_travail, PDO::PARAM_INT);
            $result_depots = $stmt_delete_depots->execute();
            $rows_depots = $stmt_delete_depots->rowCount();
            
            if (!$result_depots) {
                $log_errors[] = "Échec de la suppression des dépôts: " . implode(", ", $stmt_delete_depots->errorInfo());
            } else {
                $log_errors[] = "Suppression de $rows_depots dépôts de devoirs réussie";
            }
        } catch (Exception $e) {
            $log_errors[] = "Exception lors de la suppression des dépôts: " . $e->getMessage();
        }
    }
    
    // 3. Supprimer le travail
    try {
        $query_delete_travail = "DELETE FROM travail_a_faire WHERE id_travail = :id_travail";
        $stmt_delete_travail = $connect->prepare($query_delete_travail);
        $stmt_delete_travail->bindParam(':id_travail', $id_travail, PDO::PARAM_INT);
        $result_travail = $stmt_delete_travail->execute();
        $rows_travail = $stmt_delete_travail->rowCount();
        
        if (!$result_travail) {
            $log_errors[] = "Échec de la suppression du travail: " . implode(", ", $stmt_delete_travail->errorInfo());
        } else if ($rows_travail == 0) {
            $log_errors[] = "Aucune ligne supprimée dans travail_a_faire";
            
            // Tenter de forcer la suppression avec une requête directe
            $force_delete = $connect->exec("DELETE FROM travail_a_faire WHERE id_travail = $id_travail");
            if ($force_delete === false) {
                $log_errors[] = "Échec de la suppression forcée: " . implode(", ", $connect->errorInfo());
            } else if ($force_delete == 0) {
                $log_errors[] = "Suppression forcée n'a supprimé aucune ligne";
            } else {
                $log_errors[] = "Suppression forcée réussie: $force_delete lignes";
            }
        } else {
            $log_errors[] = "Suppression du travail réussie";
        }
    } catch (Exception $e) {
        $log_errors[] = "Exception lors de la suppression du travail: " . $e->getMessage();
    }
    
    // Réactiver les clés étrangères
    $connect->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    if (count(array_filter($log_errors, function($error) {
        return strpos($error, 'Échec') !== false || strpos($error, 'Exception') !== false;
    })) == 0) {
        $connect->commit();
        // Rediriger avec un message de succès
        header("Location: travail_a_faire.php?success=deleted");
        exit;
    } else {
        // Il y a eu des erreurs, mais on essaie quand même de commettre
        $connect->commit();
        header("Location: travail_a_faire.php?warning=partial_delete&errors=" . urlencode(implode("; ", $log_errors)));
        exit;
    }
    
} catch (Exception $e) {
    // Vérifier si une transaction est active avant de faire un rollback
    if ($connect->inTransaction()) {
        $connect->rollBack();
    }
    
    // Réactiver les clés étrangères en cas d'erreur
    try {
        $connect->exec("SET FOREIGN_KEY_CHECKS = 1");
    } catch (Exception $e2) {
        // Ignorer cette erreur
    }
    
    // Rediriger avec un message d'erreur
    header("Location: travail_a_faire.php?error=delete_failed&message=" . urlencode($e->getMessage()));
    exit;
}
?>
