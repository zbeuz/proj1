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

// Initialiser les variables de message
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_systeme = $_POST['nom_systeme'];
    $description_systeme = !empty($_POST['description_systeme']) ? $_POST['description_systeme'] : '';
    $numero_serie_systeme = !empty($_POST['numero_serie_systeme']) ? $_POST['numero_serie_systeme'] : '';
    $id_fournisseur = !empty($_POST['id_fournisseur']) ? $_POST['id_fournisseur'] : null;
    $reference_systeme = !empty($_POST['reference_systeme']) ? $_POST['reference_systeme'] : '';

    // Gestion de l'upload de l'image
    $photo_systeme = 'default_system.jpg'; // Image par défaut
    
    if (isset($_FILES['photo_systeme']) && $_FILES['photo_systeme']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../ressources/systeme/';
        $temp_name = $_FILES['photo_systeme']['tmp_name'];
        $file_name = basename($_FILES['photo_systeme']['name']);
        
        // Générer un nom de fichier unique
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_filename = 'system_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_file = $upload_dir . $unique_filename;
        
        // Vérifier si le fichier est une image
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = mime_content_type($temp_name);
        
        if (in_array($file_type, $allowed_types)) {
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            if (move_uploaded_file($temp_name, $upload_file)) {
                // Stocker uniquement le nom du fichier dans la base de données
                $photo_systeme = $unique_filename;
            } else {
                $message = "Erreur lors de l'upload de l'image. L'image par défaut sera utilisée.";
                $messageType = "warning";
            }
        } else {
            $message = "Le fichier uploadé n'est pas une image valide. L'image par défaut sera utilisée.";
            $messageType = "warning";
        }
    }

    try {
        $connect->beginTransaction();
        
        // Récupérer le nom du fournisseur si id_fournisseur est défini
        $fabricant_systeme = '';
        if (!empty($id_fournisseur)) {
            $query_fournisseur = "SELECT nom_entreprise FROM fournisseur WHERE id_fournisseur = :id_fournisseur";
            $stmt_fournisseur = $connect->prepare($query_fournisseur);
            $stmt_fournisseur->bindParam(':id_fournisseur', $id_fournisseur, PDO::PARAM_INT);
            $stmt_fournisseur->execute();
            
            if ($fournisseur = $stmt_fournisseur->fetch(PDO::FETCH_ASSOC)) {
                $fabricant_systeme = $fournisseur['nom_entreprise'];
            }
        }
        
        $query_systeme = "INSERT INTO systeme (nom_systeme, description_systeme, photo_systeme, numero_serie_systeme, fabricant_systeme, id_fournisseur, reference_systeme)
                          VALUES (:nom_systeme, :description_systeme, :photo_systeme, :numero_serie_systeme, :fabricant_systeme, :id_fournisseur, :reference_systeme)";

        $stmt_systeme = $connect->prepare($query_systeme);
        $stmt_systeme->bindParam(':nom_systeme', $nom_systeme, PDO::PARAM_STR);
        $stmt_systeme->bindParam(':description_systeme', $description_systeme, PDO::PARAM_STR);
        $stmt_systeme->bindParam(':photo_systeme', $photo_systeme, PDO::PARAM_STR);
        $stmt_systeme->bindParam(':numero_serie_systeme', $numero_serie_systeme, PDO::PARAM_STR);
        $stmt_systeme->bindParam(':fabricant_systeme', $fabricant_systeme, PDO::PARAM_STR);
        $stmt_systeme->bindParam(':id_fournisseur', $id_fournisseur, PDO::PARAM_INT);
        $stmt_systeme->bindParam(':reference_systeme', $reference_systeme, PDO::PARAM_STR);
        $stmt_systeme->execute();

        $connect->commit();
        $message = "Système ajouté avec succès.";
        $messageType = "success";
    } catch (Exception $e) {
        $connect->rollBack();
        $message = "Une erreur est survenue lors de l'ajout du système : " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Système</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">

</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
        <button id="ajout-button" class="sidebar-btn active">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <h1>Ajouter un Système</h1>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="styled-form">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nom_systeme">Nom du système*:</label>
                        <input type="text" id="nom_systeme" name="nom_systeme" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description_systeme">Description:</label>
                        <textarea id="description_systeme" name="description_systeme"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="photo_systeme">Photo du système:</label>
                        <input type="file" id="photo_systeme" name="photo_systeme" accept="image/*" onchange="previewImage(this)">
                        <img id="preview" class="preview-image" src="#" alt="Aperçu de l'image">
                    </div>
                    
                    <div class="form-group">
                        <label for="numero_serie_systeme">Numéro de série:</label>
                        <input type="text" id="numero_serie_systeme" name="numero_serie_systeme">
                    </div>
                    
                    <div class="form-group">
                        <label for="id_fournisseur">Fabricant:</label>
                        <select id="id_fournisseur" name="id_fournisseur" class="form-control">
                            <option value="">-- Sélectionner un fabricant --</option>
                            <?php
                            // Récupération des fournisseurs depuis la base de données
                            try {
                                $query_fournisseurs = "SELECT id_fournisseur, nom_entreprise FROM fournisseur ORDER BY nom_entreprise";
                                $stmt_fournisseurs = $connect->prepare($query_fournisseurs);
                                $stmt_fournisseurs->execute();
                                
                                while ($fournisseur = $stmt_fournisseurs->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $fournisseur['id_fournisseur'] . '">' . htmlspecialchars($fournisseur['nom_entreprise']) . '</option>';
                                }
                            } catch (PDOException $e) {
                                echo '<option value="">Erreur lors du chargement des fournisseurs</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="reference_systeme">Référence:</label>
                        <input type="text" id="reference_systeme" name="reference_systeme">
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-consulter btn-back" onclick="window.location.href='menu_ajout.php'">Retour</button>
                        <button type="submit" class="btn-consulter">Ajouter le système</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}
</script>

<script src="../ressources/js/app_formateur.js"></script>
<?php include('../modeles/footer.php'); ?>
</body>
</html>
