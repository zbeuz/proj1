<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $document_title = trim($_POST['document_title']);
    $system_id = intval($_POST['system_id']);
    $document_type = trim($_POST['document_type']);
    $category_id = intval($_POST['category_id']);
    
    // Validation
    if (empty($document_title) || empty($system_id) || empty($document_type) || empty($category_id)) {
        $_SESSION['error_message'] = "Tous les champs sont obligatoires";
        header("Location: ../controleurs/view_system.php?id=" . $system_id);
        exit();
    }
    
    // Handle file upload
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == 0) {
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];
        $filename = $_FILES['document_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newName = time() . '_' . preg_replace('/[^A-Za-z0-9\-]/', '', $document_title) . '.' . $ext;
            $uploadDir = "../ressources/document/";
            $uploadFile = $uploadDir . $newName;
            
            if (move_uploaded_file($_FILES['document_file']['tmp_name'], $uploadFile)) {
                try {
                    // Current date
                    $today = date('Y-m-d');
                    
                    // Insert document based on type
                    if ($document_type === 'technique') {
                        $stmt = $connect->prepare("
                            INSERT INTO document_technique 
                            (nom_document, fichier_document, date_depot, id_utilisateur, image_logo, id_section, date_modification, id_systeme, id_utilisateur_actualiser, id_categorie) 
                            VALUES (?, ?, ?, ?, 'doc_tech.png', ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $document_title,
                            $newName,
                            $today,
                            $_SESSION['user_id'],
                            $_SESSION['section_id'],
                            $today,
                            $system_id,
                            $_SESSION['user_id'],
                            $category_id
                        ]);
                    } else if ($document_type === 'pedagogique') {
                        // For pedagogical documents, set a due date (30 days from now)
                        $dueDate = date('Y-m-d', strtotime('+30 days'));
                        
                        $stmt = $connect->prepare("
                            INSERT INTO document_pedago 
                            (nom_document, fichier_document, date_depot, id_utilisateur, image_logo, id_section, date_limite_rendu_devoir, id_utilisateur_deposer, id_systeme, id_categorie) 
                            VALUES (?, ?, ?, ?, 'doc_peda.png', ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $document_title,
                            $newName,
                            $today,
                            $_SESSION['user_id'],
                            $_SESSION['section_id'],
                            $dueDate,
                            $_SESSION['user_id'],
                            $system_id,
                            $category_id
                        ]);
                    }
                    
                    $_SESSION['success_message'] = "Document uploadé avec succès";
                } catch (PDOException $e) {
                    $_SESSION['error_message'] = "Erreur lors de l'enregistrement du document: " . $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = "Erreur lors de l'upload du document";
            }
        } else {
            $_SESSION['error_message'] = "Format de fichier non autorisé";
        }
    } else {
        $_SESSION['error_message'] = "Erreur lors de l'upload du fichier";
    }
    
    // Redirect back to system view
    header("Location: ../controleurs/view_system.php?id=" . $system_id);
    exit();
} else {
    // If not POST request, redirect to admin dashboard
    if ($_SESSION['user_role'] == 1) {
        header("Location: ../controleurs/admin_dashboard.php");
    } else {
        header("Location: ../controleurs/user_dashboard.php");
    }
    exit();
}
?> 