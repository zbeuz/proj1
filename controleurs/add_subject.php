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
    $nom_matiere = trim($_POST['nom_matiere']);
    $promo_classe = trim($_POST['promo_classe']);
    $section_classe = trim($_POST['section_classe']);
    $editMode = isset($_POST['id_matiere']) && !empty($_POST['id_matiere']);
    
    // Validation
    if (empty($nom_matiere) || empty($promo_classe) || empty($section_classe)) {
        $_SESSION['error_message'] = "Tous les champs sont obligatoires";
        if ($editMode) {
            header("Location: ../controleurs/subject_form.php?id=" . $_POST['id_matiere']);
        } else {
            header("Location: ../controleurs/subject_form.php");
        }
        exit();
    }
    
    try {
        // Verify section exists
        $stmtSection = $connect->prepare("SELECT * FROM section WHERE nom = ?");
        $stmtSection->execute([$section_classe]);
        $section = $stmtSection->fetch(PDO::FETCH_ASSOC);
        
        if (!$section) {
            $_SESSION['error_message'] = "La section spécifiée n'existe pas";
            if ($editMode) {
                header("Location: ../controleurs/subject_form.php?id=" . $_POST['id_matiere']);
            } else {
                header("Location: ../controleurs/subject_form.php");
            }
            exit();
        }
        
        if ($editMode) {
            // Update existing subject
            $subjectId = intval($_POST['id_matiere']);
            
            $stmtUpdate = $connect->prepare("
                UPDATE matiere 
                SET nom_matiere = ?, 
                    promo_classe = ?, 
                    section_classe = ?,
                    nom = ?
                WHERE id_matiere = ?
            ");
            $stmtUpdate->execute([$nom_matiere, $promo_classe, $section_classe, $section_classe, $subjectId]);
            
            $_SESSION['success_message'] = "Matière mise à jour avec succès";
        } else {
            // Insert new subject
            $stmtInsert = $connect->prepare("
                INSERT INTO matiere 
                (nom_matiere, promo_classe, section_classe, nom) 
                VALUES (?, ?, ?, ?)
            ");
            $stmtInsert->execute([$nom_matiere, $promo_classe, $section_classe, $section_classe]);
            
            $_SESSION['success_message'] = "Matière ajoutée avec succès";
        }
        
        header("Location: ../controleurs/admin_dashboard.php");
        exit();
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur lors de l'ajout ou la mise à jour de la matière: " . $e->getMessage();
        if ($editMode) {
            header("Location: ../controleurs/subject_form.php?id=" . $_POST['id_matiere']);
        } else {
            header("Location: ../controleurs/subject_form.php");
        }
        exit();
    }
} else {
    // If not POST request, redirect to form
    header("Location: ../controleurs/subject_form.php");
    exit();
}
?>