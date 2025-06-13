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

// Vérifier si l'ID du travail est spécifié
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: travail_a_faire.php");
    exit;
}

$id_travail = $_GET['id'];

// Récupérer les informations du travail
try {
    $query_travail = "SELECT * FROM travail_a_faire WHERE id_travail = :id_travail AND id_utilisateur = :id_utilisateur";
    $stmt_travail = $connect->prepare($query_travail);
    $stmt_travail->bindParam(':id_travail', $id_travail, PDO::PARAM_INT);
    $stmt_travail->bindParam(':id_utilisateur', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt_travail->execute();
    
    if ($stmt_travail->rowCount() == 0) {
        header("Location: travail_a_faire.php");
        exit;
    }
    
    $travail = $stmt_travail->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $message = "Erreur lors de la récupération des données du travail : " . $e->getMessage();
    $messageType = "error";
    $travail = null;
}

// Récupérer la liste des sections pour le menu déroulant
try {
    $query_sections = "SELECT nom, promotion FROM section ORDER BY nom, promotion DESC";
    $stmt_sections = $connect->prepare($query_sections);
    $stmt_sections->execute();
    $sections = $stmt_sections->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $message = "Erreur lors de la récupération des sections : " . $e->getMessage();
    $messageType = "error";
    $sections = [];
}

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

// Traitement du formulaire de modification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $id_section = $_POST['id_section'];
    $id_systeme = !empty($_POST['id_systeme']) ? $_POST['id_systeme'] : null;
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];

    try {
        $connect->beginTransaction();
        
        // Mettre à jour le travail
        $query = "UPDATE travail_a_faire 
                 SET titre = :titre, 
                     description = :description, 
                     date_debut = :date_debut, 
                     date_fin = :date_fin, 
                     id_section = :id_section, 
                     id_systeme = :id_systeme
                 WHERE id_travail = :id_travail";
        
        $stmt = $connect->prepare($query);
        $stmt->bindParam(':titre', $titre, PDO::PARAM_STR);
        $stmt->bindParam(':description', $description, PDO::PARAM_STR);
        $stmt->bindParam(':date_debut', $date_debut, PDO::PARAM_STR);
        $stmt->bindParam(':date_fin', $date_fin, PDO::PARAM_STR);
        $stmt->bindParam(':id_section', $id_section, PDO::PARAM_STR);
        $stmt->bindParam(':id_systeme', $id_systeme, PDO::PARAM_INT);
        $stmt->bindParam(':id_travail', $id_travail, PDO::PARAM_INT);
        
        $stmt->execute();
        $connect->commit();
        
        $message = "Travail modifié avec succès.";
        $messageType = "success";
        
        // Mettre à jour les informations du travail
        $travail['titre'] = $titre;
        $travail['description'] = $description;
        $travail['date_debut'] = $date_debut;
        $travail['date_fin'] = $date_fin;
        $travail['id_section'] = $id_section;
        $travail['id_systeme'] = $id_systeme;
    } catch (Exception $e) {
        // Vérifier si une transaction est active avant de faire un rollback
        if ($connect->inTransaction()) {
            $connect->rollBack();
        }
        $message = "Erreur lors de la modification du travail : " . $e->getMessage();
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un Travail à Faire</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">

</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn active">Travail à Faire</button>
        <button id="ajout-button" class="sidebar-btn">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <h1>Modifier un Travail à Faire</h1>
            
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($travail): ?>
                <div class="styled-form">
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="titre">Titre du travail*:</label>
                            <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($travail['titre']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description détaillée*:</label>
                            <textarea id="description" name="description" required><?php echo htmlspecialchars($travail['description']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_section">Section concernée*:</label>
                            <select id="id_section" name="id_section" required>
                                <option value="">Sélectionnez une section</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?php echo htmlspecialchars($section['nom']); ?>" <?php echo ($travail['id_section'] == $section['nom']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($section['nom'] . ' - Promotion ' . $section['promotion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_systeme">Système concerné (optionnel):</label>
                            <select id="id_systeme" name="id_systeme">
                                <option value="">Aucun système spécifique</option>
                                <?php foreach ($systemes as $systeme): ?>
                                    <option value="<?php echo $systeme['id_systeme']; ?>" <?php echo ($travail['id_systeme'] == $systeme['id_systeme']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($systeme['nom_systeme']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_debut">Date de début*:</label>
                            <input type="date" id="date_debut" name="date_debut" value="<?php echo $travail['date_debut']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_fin">Date de fin (limite de rendu)*:</label>
                            <input type="date" id="date_fin" name="date_fin" value="<?php echo $travail['date_fin']; ?>" required>
                        </div>
                        
                        <div class="button-group">
                            <button type="button" class="btn-consulter btn-back" onclick="window.location.href='travail_a_faire.php'">Retour</button>
                            <button type="submit" class="btn-consulter">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<script>
    // Valider que la date de fin est après la date de début
    document.querySelector('form').addEventListener('submit', function(e) {
        var dateDebut = new Date(document.getElementById('date_debut').value);
        var dateFin = new Date(document.getElementById('date_fin').value);
        
        if (dateFin < dateDebut) {
            e.preventDefault();
            alert('La date de fin doit être postérieure à la date de début.');
        }
    });
</script>
<?php include('../modeles/footer.php'); ?>
</body>
</html>
