<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./ressources/styles/styles_accueil.css">
    <title>Accueil</title>

</head>

<body>

    <?php
    session_start();
    include("./configurations/connexion.php");
    ?>
    <header class="entete">
        <img class="logo_uimm" src="./ressources/images/logo-uimm.svg" alt="">
        <img class="logo_aforp" src="./ressources/images/logo_aforp.png" alt="">
        <h1 class="titre_accueil">
            Bienvenue dans le pôle électrotechnique et maintenance
        </h1>
    </header>

    <section class="container">
        <section class="login_container">
            <section class="left_container">
                <p class="slogan">
                    Trouvez facilement les documents pour les différents systèmes.
                </p>
                <p class="petit_slogan">
                    De l'impression à la transition numérique, un clic suffit !
                </p>
            </section>
            <section class="right_container">
                <h1 class="titre">Authentification</h1>
                <br>
                <hr>
                <section class="login">
                    <img src="./ressources/images/avatar.png" alt="">
                    <form class="formulaire-conection" method="POST" action="./controleurs/authentification.php">
                        <input type="mail" placeholder="Identifiant" name="email" required>
                        <input type="password" placeholder="mot de passe" name="password" required>
                        <?php
                        if (isset($_SESSION['error_message'])) {
                            echo '<p class="error_message">' . $_SESSION['error_message'] . '</p>';
                            unset($_SESSION['error_message']);
                        }
                        ?>
                        <button type="submit">Connexion</button>
                    </form>
                </section>
            </section>
        </section>
    </section>

    <div id="popup" class="popup">
        <form method="POST" action="./controleurs/change_password.php">
            <h2>Changement de mot de passe</h2>
            <p>Bienvenue! C'est votre première connexion. Veuillez changer votre mot de passe.</p>
            <input type="password" id="new_password" name="new_password" placeholder="Nouveau mot de passe" 
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{12,}" 
                   title="Le mot de passe doit contenir au moins 12 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial." required>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmez le mot de passe" 
                   pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*]).{12,}" 
                   title="Les mots de passe doivent correspondre." required>
            <button type="submit">Changer le mot de passe</button>
            <button type="button" onclick="closePopup()">Fermer</button>
        </form>
    </div>                    
    <?php include("./modeles/footer.php"); ?>
</body>

</html>
