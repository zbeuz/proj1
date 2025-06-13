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

// Récupérer tous les utilisateurs
try {
    $query = "SELECT u.id_utilisateur, u.nom_utilisateur, u.prenom_utilisateur, 
                     u.adresse_mail, u.role, s.nom as nom_section
              FROM utilisateur u
              LEFT JOIN section s ON s.nom = (
                  CASE 
                      WHEN u.id_section = 1 THEN 'MELEC'
                      WHEN u.id_section = 2 THEN 'SISR'
                      WHEN u.id_section = 3 THEN 'SLAM'
                      ELSE NULL
                  END
              )
              ORDER BY u.role, u.nom_utilisateur";
    $stmt = $connect->prepare($query);
    $stmt->execute();
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Erreur lors de la récupération des utilisateurs : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Utilisateurs</title>
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
            <h1>Liste des Utilisateurs</h1>
            
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
            
            <?php if (isset($error_message)): ?>
                <div style="color: red; padding: 15px; background-color: #ffeeee; border-radius: 8px; margin: 10px 0; border-left: 4px solid #e74c3c; font-weight: bold;">
                    <?php echo $error_message; ?>
                </div>
            <?php else: ?>
                <button class="btn-consulter" style="margin-bottom: 20px;" onclick="window.location.href='ajouter_utilisateur.php'">+ Ajouter un utilisateur</button>
                
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.1); margin-top: 20px;">
                    <thead style="background-color: #2d65cc; color: white;">
                        <tr>
                            <th style="padding: 15px 12px; text-align: left; font-weight: bold;">ID</th>
                            <th style="padding: 15px 12px; text-align: left; font-weight: bold;">Nom</th>
                            <th style="padding: 15px 12px; text-align: left; font-weight: bold;">Prénom</th>
                            <th style="padding: 15px 12px; text-align: left; font-weight: bold;">Email</th>
                            <th style="padding: 15px 12px; text-align: left; font-weight: bold;">Section</th>
                            <th style="padding: 15px 12px; text-align: left; font-weight: bold;">Rôle</th>
                            <th style="padding: 15px 12px; text-align: left; font-weight: bold;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($utilisateurs as $user): ?>
                            <tr style="border-bottom: 1px solid #eee;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor=''">
                                <td style="padding: 12px; vertical-align: top;"><?php echo htmlspecialchars($user['id_utilisateur']); ?></td>
                                <td style="padding: 12px; vertical-align: top;"><?php echo htmlspecialchars($user['nom_utilisateur']); ?></td>
                                <td style="padding: 12px; vertical-align: top;"><?php echo htmlspecialchars($user['prenom_utilisateur']); ?></td>
                                <td style="padding: 12px; vertical-align: top;"><?php echo htmlspecialchars($user['adresse_mail']); ?></td>
                                <td style="padding: 12px; vertical-align: top;"><?php echo htmlspecialchars($user['nom_section'] ?? 'Non assigné'); ?></td>
                                <td style="padding: 12px; vertical-align: top;">
                                    <span style="padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: bold; color: white; background-color: <?php echo $user['role'] == 1 ? '#28a745' : '#007bff'; ?>">
                                        <?php echo $user['role'] == 1 ? 'Formateur' : 'Apprenti'; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; vertical-align: top;">
                                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                                        <button class="btn-consulter" style="background-color: #f39c12; padding: 6px 10px; font-size: 0.8rem;" onclick="window.location.href='modifier_utilisateur.php?id=<?php echo $user['id_utilisateur']; ?>'">Modifier</button>
                                        <button class="btn-consulter" style="background-color: #e74c3c; padding: 6px 10px; font-size: 0.8rem;" onclick="if(confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) window.location.href='../controleurs/delete_user.php?id=<?php echo $user['id_utilisateur']; ?>'">Supprimer</button>
                                        <button class="btn-consulter" style="background-color: #17a2b8; padding: 6px 10px; font-size: 0.8rem;" onclick="resetPassword(<?php echo $user['id_utilisateur']; ?>, '<?php echo htmlspecialchars($user['nom_utilisateur'] . ' ' . $user['prenom_utilisateur']); ?>')">Réinit. MDP</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>

<script src="../ressources/js/app_formateur.js"></script>
<script>
    function resetPassword(userId, userName) {
        if (confirm("Êtes-vous sûr de vouloir réinitialiser le mot de passe de " + userName + " à la valeur par défaut (aforp2020) ?")) {
            window.location.href = '../controleurs/reset_password.php?id=' + userId;
        }
    }
</script>
<?php include('../modeles/footer.php'); ?>
</body>
</html> 