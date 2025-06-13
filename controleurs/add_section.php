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
    $nom = trim($_POST['nom']);
    $specialite = trim($_POST['specialite']);
    $option = trim($_POST['option']);
    $promotion = intval($_POST['promotion']);
    $editMode = isset($_POST['edit_mode']) && $_POST['edit_mode'] == '1';
    $oldNom = isset($_POST['old_nom']) ? trim($_POST['old_nom']) : '';
    
    // Validation
    if (empty($nom) || empty($specialite) || empty($option) || empty($promotion)) {
        $_SESSION['error_message'] = "Tous les champs sont obligatoires";
        if ($editMode) {
            header("Location: ../controleurs/section_form.php?id=" . urlencode($oldNom));
        } else {
            header("Location: ../controleurs/section_form.php");
        }
        exit();
    }
    
    try {
        if ($editMode) {
            // Check if new name already exists (if name is being changed)
            if ($nom != $oldNom) {
                $stmtCheck = $connect->prepare("SELECT COUNT(*) FROM section WHERE nom = ?");
                $stmtCheck->execute([$nom]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $_SESSION['error_message'] = "Une section avec ce nom existe déjà";
                    header("Location: ../controleurs/section_form.php?id=" . urlencode($oldNom));
                    exit();
                }
                
                // Update foreign keys in matiere table
                $stmtUpdateMatiere = $connect->prepare("UPDATE matiere SET nom = ?, section_classe = ? WHERE nom = ?");
                $stmtUpdateMatiere->execute([$nom, $nom, $oldNom]);
                
                // Update foreign keys in utilisateur table
                $stmtUpdateUser = $connect->prepare("UPDATE utilisateur SET id_section = ? WHERE id_section = ?");
                $stmtUpdateUser->execute([$nom, $oldNom]);
                
                // Delete old section
                $stmtDelete = $connect->prepare("DELETE FROM section WHERE nom = ?");
                $stmtDelete->execute([$oldNom]);
            }
            
            // Insert with new values
            $stmtInsert = $connect->prepare("
                INSERT INTO section (nom, specialite, `option`, promotion)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                specialite = VALUES(specialite),
                `option` = VALUES(`option`),
                promotion = VALUES(promotion)
            ");
            $stmtInsert->execute([$nom, $specialite, $option, $promotion]);
            
            $_SESSION['success_message'] = "Section mise à jour avec succès";
        } else {
            // Check if name already exists
            $stmtCheck = $connect->prepare("SELECT COUNT(*) FROM section WHERE nom = ?");
            $stmtCheck->execute([$nom]);
            if ($stmtCheck->fetchColumn() > 0) {
                $_SESSION['error_message'] = "Une section avec ce nom existe déjà";
                header("Location: ../controleurs/section_form.php");
                exit();
            }
            
            // Insert new section
            $stmtInsert = $connect->prepare("
                INSERT INTO section (nom, specialite, `option`, promotion)
                VALUES (?, ?, ?, ?)
            ");
            $stmtInsert->execute([$nom, $specialite, $option, $promotion]);
            
            $_SESSION['success_message'] = "Section ajoutée avec succès";
        }
        
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de l'ajout ou la mise à jour de la section: " . $e->getMessage();
        if ($editMode) {
            header("Location: ../controleurs/section_form.php?id=" . urlencode($oldNom));
        } else {
            header("Location: ../controleurs/section_form.php");
        }
        exit();
    }
} else {
    // If not POST request, redirect to form
    header("Location: ../controleurs/section_form.php");
    exit();
}
?> 