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

// Vérifier si l'ID de l'utilisateur est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "ID d'utilisateur non spécifié.";
    header("Location: ../vues/liste_utilisateurs.php");
    exit;
}

require_once("../configurations/connexion.php");

$userId = $_GET['id'];

// Récupérer les informations de l'utilisateur
try {
    $stmt = $connect->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = ?");
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() == 0) {
        $_SESSION['error_message'] = "Utilisateur introuvable.";
        header("Location: ../vues/liste_utilisateurs.php");
        exit;
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur lors de la récupération des informations de l'utilisateur : " . $e->getMessage();
    header("Location: ../vues/liste_utilisateurs.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Utilisateur</title>
    <link rel="stylesheet" href="../ressources/styles/styles.css">

</head>
<body>
<?php include('../modeles/header.php'); ?>
<div class="container">
    <aside class="sidebar">
        <button id="system-button" class="sidebar-btn">Système</button>
        <button id="docs-pedagogiques-button" class="sidebar-btn">Documents Pédagogiques</button>
        <button id="travail-a-faire-button" class="sidebar-btn">Travail à Faire</button>
        <button id="ajout-button" class="sidebar-btn">Ajouter</button>
        <button id="utilisateur-button" class="sidebar-btn active">Utilisateur</button>
        <button id="fournisseur-button" class="sidebar-btn">Fournisseur</button>
    </aside>
    
    <main class="main-content">
        <div class="content-area" id="content-area">
            <h1>Modifier l'Utilisateur</h1>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div style="color: red; padding: 15px; background-color: #ffeeee; border-radius: 8px; margin: 10px 0; border-left: 4px solid #e74c3c; font-weight: bold;">
                    <?php echo $_SESSION['error_message']; ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div style="color: #155724; padding: 15px; background-color: #d4edda; border-radius: 8px; margin: 10px 0; border-left: 4px solid #28a745; font-weight: bold;">
                    <?php echo $_SESSION['success_message']; ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <div class="styled-form">
                <form action="../controleurs/update_user.php" method="post">
                    <input type="hidden" name="id_utilisateur" value="<?php echo htmlspecialchars($user['id_utilisateur']); ?>">
                    
                    <div class="form-group">
                        <label for="nom_utilisateur">Nom:</label>
                        <input type="text" id="nom_utilisateur" name="nom_utilisateur" value="<?php echo htmlspecialchars($user['nom_utilisateur']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="prenom_utilisateur">Prénom:</label>
                        <input type="text" id="prenom_utilisateur" name="prenom_utilisateur" value="<?php echo htmlspecialchars($user['prenom_utilisateur']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="adresse_mail">Adresse Mail:</label>
                        <input type="email" id="adresse_mail" name="adresse_mail" value="<?php echo htmlspecialchars($user['adresse_mail']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="section_utilisateur">Section:</label>
                        <select id="section_utilisateur" name="section_utilisateur" required>
                            <option value="1" <?php echo $user['id_section'] == 1 ? 'selected' : ''; ?>>BTS MELEC</option>
                            <option value="2" <?php echo $user['id_section'] == 2 ? 'selected' : ''; ?>>BTS SISR</option>
                            <option value="3" <?php echo $user['id_section'] == 3 ? 'selected' : ''; ?>>BTS SLAM</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Rôle:</label>
                        <select id="role" name="role" required>
                            <option value="1" <?php echo $user['role'] == 1 ? 'selected' : ''; ?>>Formateur</option>
                            <option value="2" <?php echo $user['role'] == 2 ? 'selected' : ''; ?>>Apprenti</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-consulter">Enregistrer</button>
                        <a href="liste_utilisateurs.php" class="btn-consulter">Annuler</a>
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