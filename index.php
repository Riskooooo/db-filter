<?php
    require_once 'header.php';

    // Récupérer l'URL d'autorisation/de connexion - Fetch the Authorization/Login URL
    $authUrl = $authHelper->getAuthUrl($callbackUrl);

    if(isset($_GET['code'])){
        $_SESSION['code'] = $_GET['code'];
    }
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - GD Life'Scape</title>
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <a href="<?php echo $authUrl; ?>" class="login">SE CONNECTER À DROPBOX</a><br>
    <a href="loading.php" class="start" data-aos="fade-up">Lancer la conversion des images</a>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init();
    </script>
</body>
</html>