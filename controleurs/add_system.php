<?php
session_start();
include("../configurations/connexion.php");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 1) {
    header("Location: ../index.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = trim($_POST['nom_systeme']);
    $description = trim($_POST['description_systeme']);
    $numero_serie = trim($_POST['numero_serie_systeme']);
    $fabricant = trim($_POST['fabricant_systeme']);
    $reference = trim($_POST['reference_systeme']);
    $editMode = isset($_POST['id_systeme']) && !empty($_POST['id_systeme']);
    
    // Validation
    if (empty($nom) || empty($description) || empty($numero_serie) || empty($fabricant) || empty($reference)) {
        $_SESSION['error_message'] = "Tous les champs sont obligatoires";
        if ($editMode) {
            header("Location: ../controleurs/system_form.php?id=" . $_POST['id_systeme']);
        } else {
            header("Location: ../controleurs/system_form.php");
        }
        exit();
    }
    
    // Handle file upload
    $photo = "";
    if (isset($_FILES['photo_systeme']) && $_FILES['photo_systeme']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['photo_systeme']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $newName = time() . "." . $ext;
            $uploadDir = "../ressources/images/";
            $uploadFile = $uploadDir . $newName;
            
            if (move_uploaded_file($_FILES['photo_systeme']['tmp_name'], $uploadFile)) {
                $photo = $newName;
            } else {
                $_SESSION['error_message'] = "Erreur lors de l'upload de l'image";
                if ($editMode) {
                    header("Location: ../controleurs/system_form.php?id=" . $_POST['id_systeme']);
                } else {
                    header("Location: ../controleurs/system_form.php");
                }
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Format d'image non autorisé (jpg, jpeg, png, gif uniquement)";
            if ($editMode) {
                header("Location: ../controleurs/system_form.php?id=" . $_POST['id_systeme']);
            } else {
                header("Location: ../controleurs/system_form.php");
            }
            exit();
        }
    } else if (!$editMode) {
        // If no image uploaded and not in edit mode, use a default image
        $photo = "default_system.jpg";
    }
    
    try {
        if ($editMode) {
            // Update existing system
            $systemId = $_POST['id_systeme'];
            
            // If no new photo uploaded, keep the existing one
            if (empty($photo)) {
                $stmtUpdate = $connect->prepare("
                    UPDATE systeme 
                    SET nom_systeme = ?, 
                        description_systeme = ?, 
                        numero_serie_systeme = ?, 
                        fabricant_systeme = ?, 
                        reference_systeme = ? 
                    WHERE id_systeme = ?
                ");
                $stmtUpdate->execute([$nom, $description, $numero_serie, $fabricant, $reference, $systemId]);
            } else {
                $stmtUpdate = $connect->prepare("
                    UPDATE systeme 
                    SET nom_systeme = ?, 
                        description_systeme = ?, 
                        photo_systeme = ?, 
                        numero_serie_systeme = ?, 
                        fabricant_systeme = ?, 
                        reference_systeme = ? 
                    WHERE id_systeme = ?
                ");
                $stmtUpdate->execute([$nom, $description, $photo, $numero_serie, $fabricant, $reference, $systemId]);
            }
            
            $_SESSION['success_message'] = "Système mis à jour avec succès";
        } else {
            // Insert new system
            $stmtInsert = $connect->prepare("
                INSERT INTO systeme 
                (nom_systeme, description_systeme, photo_systeme, numero_serie_systeme, fabricant_systeme, reference_systeme) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtInsert->execute([$nom, $description, $photo, $numero_serie, $fabricant, $reference]);
            
            $_SESSION['success_message'] = "Système ajouté avec succès";
        }
        
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de l'ajout ou la mise à jour du système: " . $e->getMessage();
        if ($editMode) {
            header("Location: ../controleurs/system_form.php?id=" . $_POST['id_systeme']);
        } else {
            header("Location: ../controleurs/system_form.php");
        }
        exit();
    }
} else {
    // If not POST request, redirect to form
    header("Location: ../controleurs/system_form.php");
    exit();
}
?> 