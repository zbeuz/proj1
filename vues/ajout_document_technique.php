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

// Récupérer la liste des systèmes pour le menu déroulant
try {
    $query_systemes = "SELECT id_systeme, nom_systeme FROM systeme ORDER BY nom_systeme";
    $stmt_systemes = $connect->prepare($query_systemes);
    $stmt_systemes->execute();
    $systemes = $stmt_systemes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Erreur lors de la récupération des systèmes : " . $e->getMessage();
    $messageType = "error";
    $systemes = [];
}

// Récupérer la liste des catégories pour le menu déroulant
try {
    $query_categories = "SELECT id_categorie, nom_categorie FROM categorie_document ORDER BY nom_categorie";
    $stmt_categories = $connect->prepare($query_categories);
    $stmt_categories->execute();
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Erreur lors de la récupération des catégories : " . $e->getMessage();
    $messageType = "error";
    $categories = [];
}

// Récupérer la liste des catégories techniques pour le menu déroulant
try {
    $query_categories_techniques = "SELECT id_categorie_technique, nom_categorie_technique FROM categorie_technique ORDER BY nom_categorie_technique";
    $stmt_categories_techniques = $connect->prepare($query_categories_techniques);
    $stmt_categories_techniques->execute();
    $categories_techniques = $stmt_categories_techniques->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Erreur lors de la récupération des catégories techniques : " . $e->getMessage();
    $messageType = "error";
    $categories_techniques = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom_document = $_POST['nom_document'];
    $id_systeme = $_POST['id_systeme'];
    $id_categorie = 1; // Catégorie fixée à "Document Technique"
    $id_categorie_technique = !empty($_POST['id_categorie_technique']) ? $_POST['id_categorie_technique'] : null;
    $date_depot = date('Y-m-d');
    $id_utilisateur = $_SESSION['user_id'];
    $id_utilisateur_actualiser = $_SESSION['user_id'];
    $image_logo = '../ressources/images/doc_tech.png'; // Logo par défaut
    $id_section = $_SESSION['section_id'] ?? 1; // Valeur par défaut ou utiliser celle de la session
    $date_modification = date('Y-m-d');
    
    // Gestion de l'upload du document
    $fichier_document = '';
    
    if (isset($_FILES['fichier_document']) && $_FILES['fichier_document']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../ressources/document/';
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $temp_name = $_FILES['fichier_document']['tmp_name'];
        $file_name = basename($_FILES['fichier_document']['name']);
        
        // Générer un nom de fichier unique
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
        $unique_filename = 'doc_tech_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_file = $upload_dir . $unique_filename;
        
        // Vérifier si le fichier est valide (PDF, Word, etc.)
        $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $file_type = mime_content_type($temp_name);
        
        if (in_array($file_type, $allowed_types) || $file_extension == 'pdf' || $file_extension == 'doc' || $file_extension == 'docx') {
            if (move_uploaded_file($temp_name, $upload_file)) {
                // Stocker uniquement le nom du fichier dans la base de données, pas le chemin complet
                $fichier_document = $unique_filename;
            } else {
                $message = "Erreur lors de l'upload du document.";
                $messageType = "error";
            }
        } else {
            $message = "Le fichier doit être au format PDF ou Word.";
            $messageType = "error";
        }
    } else {
        $message = "Veuillez sélectionner un document à uploader.";
        $messageType = "error";
    }
    
    // Si aucune erreur et que le fichier a été uploadé
    if ($messageType != "error" && !empty($fichier_document)) {
        try {
            $connect->beginTransaction();
            
            $query = "INSERT INTO document_technique (nom_document, fichier_document, date_depot, id_utilisateur, image_logo, id_section, date_modification, id_systeme, id_utilisateur_actualiser, id_categorie, id_categorie_technique) 
                     VALUES (:nom_document, :fichier_document, :date_depot, :id_utilisateur, :image_logo, :id_section, :date_modification, :id_systeme, :id_utilisateur_actualiser, :id_categorie, :id_categorie_technique)";
            
            $stmt = $connect->prepare($query);
            $stmt->bindParam(':nom_document', $nom_document, PDO::PARAM_STR);
            $stmt->bindParam(':fichier_document', $fichier_document, PDO::PARAM_STR);
            $stmt->bindParam(':date_depot', $date_depot, PDO::PARAM_STR);
            $stmt->bindParam(':id_utilisateur', $id_utilisateur, PDO::PARAM_INT);
            $stmt->bindParam(':image_logo', $image_logo, PDO::PARAM_STR);
            $stmt->bindParam(':id_section', $id_section, PDO::PARAM_INT);
            $stmt->bindParam(':date_modification', $date_modification, PDO::PARAM_STR);
            $stmt->bindParam(':id_systeme', $id_systeme, PDO::PARAM_INT);
            $stmt->bindParam(':id_utilisateur_actualiser', $id_utilisateur_actualiser, PDO::PARAM_INT);
            $stmt->bindParam(':id_categorie', $id_categorie, PDO::PARAM_INT);
            $stmt->bindParam(':id_categorie_technique', $id_categorie_technique, PDO::PARAM_INT);
            
            $stmt->execute();
            $connect->commit();
            
            $message = "Document technique ajouté avec succès.";
            $messageType = "success";
        } catch (Exception $e) {
            $connect->rollBack();
            $message = "Erreur lors de l'ajout du document technique : " . $e->getMessage();
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Document Technique</title>
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
            <h1>Ajouter un Document Technique</h1>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="styled-form">
                <form action="" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nom_document">Nom du document*:</label>
                        <input type="text" id="nom_document" name="nom_document" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_systeme">Système*:</label>
                        <select id="id_systeme" name="id_systeme" required>
                            <option value="">Sélectionnez un système</option>
                            <?php foreach ($systemes as $systeme): ?>
                                <option value="<?php echo $systeme['id_systeme']; ?>">
                                    <?php echo htmlspecialchars($systeme['nom_systeme']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="id_categorie_technique">Type de document technique*:</label>
                        <select id="id_categorie_technique" name="id_categorie_technique" required>
                            <option value="">Sélectionnez un type de document</option>
                            <?php foreach ($categories_techniques as $categorie_technique): ?>
                                <option value="<?php echo $categorie_technique['id_categorie_technique']; ?>">
                                    <?php echo htmlspecialchars($categorie_technique['nom_categorie_technique']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fichier_document">Fichier (PDF ou Word)*:</label>
                        <input type="file" id="fichier_document" name="fichier_document" accept=".pdf,.doc,.docx" required>
                    </div>
                    
                    <div class="button-group">
                        <button type="button" class="btn-consulter btn-back" onclick="window.location.href='menu_ajout.php'">Retour</button>
                        <button type="submit" class="btn-consulter">Ajouter le document</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>

<?php include('../modeles/footer.php'); ?>
</body>
</html> 