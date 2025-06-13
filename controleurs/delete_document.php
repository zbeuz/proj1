<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Check if all required parameters are provided
if (!isset($_GET['id']) || !isset($_GET['type']) || !isset($_GET['system_id'])) {
    $_SESSION['error_message'] = "Paramètres manquants pour la suppression";
    header("Location: ../controleurs/admin_dashboard.php");
    exit();
}

$document_id = intval($_GET['id']);
$document_type = $_GET['type'];
$system_id = intval($_GET['system_id']);

try {
    // Get document file name before deletion
    if ($document_type === 'technique') {
        $stmtGetFile = $connect->prepare("SELECT fichier_document FROM document_technique WHERE id_document_technique = ?");
        $stmtGetFile->execute([$document_id]);
    } else if ($document_type === 'pedagogique') {
        $stmtGetFile = $connect->prepare("SELECT fichier_document FROM document_pedago WHERE id_document_pedago = ?");
        $stmtGetFile->execute([$document_id]);
    } else {
        $_SESSION['error_message'] = "Type de document invalide";
        header("Location: ../controleurs/view_system.php?id=" . $system_id);
        exit();
    }
    
    $fileData = $stmtGetFile->fetch(PDO::FETCH_ASSOC);
    
    if ($fileData) {
        $fileName = $fileData['fichier_document'];
        
        // Delete from database
        if ($document_type === 'technique') {
            $stmtDelete = $connect->prepare("DELETE FROM document_technique WHERE id_document_technique = ?");
            $stmtDelete->execute([$document_id]);
        } else if ($document_type === 'pedagogique') {
            $stmtDelete = $connect->prepare("DELETE FROM document_pedago WHERE id_document_pedago = ?");
            $stmtDelete->execute([$document_id]);
        }
        
        // Delete file from server
        $filePath = "../ressources/document/" . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        $_SESSION['success_message'] = "Document supprimé avec succès";
    } else {
        $_SESSION['error_message'] = "Document non trouvé";
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la suppression du document: " . $e->getMessage();
}

// Redirect back to system view
header("Location: ../controleurs/view_system.php?id=" . $system_id);
exit();
?> 