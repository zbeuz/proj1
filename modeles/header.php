<?php
// Récupérer les informations de l'utilisateur depuis la session
$user_full_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Utilisateur';

// Extraire le prénom (premier mot du nom complet)
$name_parts = explode(' ', $user_full_name);
$user_prenom = isset($name_parts[0]) ? $name_parts[0] : 'U';

// Première lettre du prénom
$user_initial = strtoupper(substr($user_prenom, 0, 1));

// Couleurs pour l'avatar
$colors = ['#e74c3c', '#2d65cc', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c', '#e67e22', '#34495e', '#d63384', '#fd7e14'];
$avatar_color = $colors[crc32($user_prenom) % count($colors)]; // Couleur stable basée sur le prénom
?>

<link rel="stylesheet" href="../ressources/styles/header.css">

<style>
.user-avatar {
    background: <?php echo $avatar_color; ?>;
}
</style>

<header class="modern-navbar">
    <div class="navbar-container">
        <a href="<?php echo ($_SESSION['user_role'] == 1) ? 'accueil_formateur.php' : 'accueil_apprenti.php'; ?>" class="navbar-brand">
            <div class="home-icon">HOME</div>
            <div>
                <h1 class="brand-text">AFORP</h1>
                <p class="brand-subtitle">Pôle Électrotechnique et Maintenance</p>
            </div>
        </a>
        
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo $user_initial; ?>
            </div>
            <div class="dropdown-menu">
                <div class="user-info-section">
                    <h3 class="user-display-name"><?php echo htmlspecialchars($user_full_name); ?></h3>
                    <span class="user-role-badge">
                        <?php echo ($_SESSION['user_role'] == 1) ? 'Formateur' : 'Apprenti'; ?>
                    </span>
                </div>
                <a href="../controleurs/logout.php" class="dropdown-item logout" onclick="return confirm('Êtes-vous sûr de vouloir vous déconnecter ?')">
                    <span class="dropdown-icon">EXIT</span>
                    Se déconnecter
                </a>
            </div>
        </div>
    </div>
</header>

<script>
// Le dropdown fonctionne maintenant uniquement avec CSS :hover
// Plus besoin de JavaScript pour l'ouverture/fermeture
console.log('Header dropdown: fonctionnement au survol activé');
</script> 