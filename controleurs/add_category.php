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
    $nom_categorie = trim($_POST['nom_categorie']);
    $editMode = isset($_POST['id_categorie']) && !empty($_POST['id_categorie']);
    
    // Validation
    if (empty($nom_categorie)) {
        $_SESSION['error_message'] = "Le nom de la catégorie est obligatoire";
        if ($editMode) {
            header("Location: ../controleurs/category_form.php?id=" . $_POST['id_categorie']);
        } else {
            header("Location: ../controleurs/category_form.php");
        }
        exit();
    }
    
    try {
        // Check if category name already exists
        $stmtCheck = $connect->prepare("SELECT COUNT(*) FROM categorie_document WHERE nom_categorie = ? AND id_categorie != ?");
        $stmtCheck->execute([$nom_categorie, $editMode ? intval($_POST['id_categorie']) : 0]);
        if ($stmtCheck->fetchColumn() > 0) {
            $_SESSION['error_message'] = "Une catégorie avec ce nom existe déjà";
            if ($editMode) {
                header("Location: ../controleurs/category_form.php?id=" . $_POST['id_categorie']);
            } else {
                header("Location: ../controleurs/category_form.php");
            }
            exit();
        }
        
        if ($editMode) {
            // Update existing category
            $categoryId = intval($_POST['id_categorie']);
            
            $stmtUpdate = $connect->prepare("UPDATE categorie_document SET nom_categorie = ? WHERE id_categorie = ?");
            $stmtUpdate->execute([$nom_categorie, $categoryId]);
            
            $_SESSION['success_message'] = "Catégorie mise à jour avec succès";
        } else {
            // Insert new category
            $stmtInsert = $connect->prepare("INSERT INTO categorie_document (nom_categorie) VALUES (?)");
            $stmtInsert->execute([$nom_categorie]);
            
            $_SESSION['success_message'] = "Catégorie ajoutée avec succès";
        }
        
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de l'ajout ou la mise à jour de la catégorie: " . $e->getMessage();
        if ($editMode) {
            header("Location: ../controleurs/category_form.php?id=" . $_POST['id_categorie']);
        } else {
            header("Location: ../controleurs/category_form.php");
        }
        exit();
    }
} else {
    // If not POST request, redirect to form
    header("Location: ../controleurs/category_form.php");
    exit();
} 