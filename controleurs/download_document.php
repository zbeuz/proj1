<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Check if all required parameters are provided
if (!isset($_GET['id']) || !isset($_GET['type'])) {
    $_SESSION['error_message'] = "Paramètres manquants pour le téléchargement";
    if ($_SESSION['user_role'] == 1) {
        header("Location: ../controleurs/admin_dashboard.php");
    } else {
        header("Location: ../controleurs/user_dashboard.php");
    }
    exit();
}

$document_id = intval($_GET['id']);
$document_type = $_GET['type'];

try {
    // Get document information
    if ($document_type === 'technique') {
        $stmtGetDoc = $connect->prepare("
            SELECT dt.*, s.nom_systeme 
            FROM document_technique dt
            JOIN systeme s ON dt.id_systeme = s.id_systeme
            WHERE dt.id_document_technique = ?
        ");
        $stmtGetDoc->execute([$document_id]);
    } else if ($document_type === 'pedagogique') {
        $stmtGetDoc = $connect->prepare("
            SELECT dp.*, s.nom_systeme 
            FROM document_pedago dp
            JOIN systeme s ON dp.id_systeme = s.id_systeme
            WHERE dp.id_document_pedago = ?
        ");
        $stmtGetDoc->execute([$document_id]);
    } else {
        $_SESSION['error_message'] = "Type de document invalide";
        if ($_SESSION['user_role'] == 1) {
            header("Location: ../controleurs/admin_dashboard.php");
        } else {
            header("Location: ../controleurs/user_dashboard.php");
        }
        exit();
    }
    
    $document = $stmtGetDoc->fetch(PDO::FETCH_ASSOC);
    
    if ($document) {
        $file = "../ressources/document/" . $document['fichier_document'];
        
        if (file_exists($file)) {
            // Set headers
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            
            // Clean output buffer
            ob_clean();
            flush();
            
            // Read file
            readfile($file);
            exit;
        } else {
            $_SESSION['error_message'] = "Fichier non trouvé sur le serveur";
        }
    } else {
        $_SESSION['error_message'] = "Document non trouvé";
    }
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération du document: " . $e->getMessage();
}

// If we get here, there was an error, redirect back
if (isset($_GET['system_id'])) {
    header("Location: ../controleurs/view_system.php?id=" . $_GET['system_id']);
} else if ($_SESSION['user_role'] == 1) {
    header("Location: ../controleurs/admin_dashboard.php");
} else {
    header("Location: ../controleurs/user_dashboard.php");
}
exit();
?> 