<?php
// Include the configuration file
require_once 'config.php';
require_once 'config_session.php';
require_once './includes/login_view.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aperture Auctions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/header.css">
    <link rel="stylesheet" href="./css/index.css">
    <link rel="stylesheet" href="./css/footer.css">
</head>
<body>
<div class="header">
    <header>
        <div class="logo"></div>
        <div><a>Join Auction</a></div>
        <div><a>Create Auction</a></div>
        <!--<div class="login-container"><a>Login</a></div>-->
        <div id="user-profile">
            <?php
            header_user_status();
            ?>

        </div>

    </header>

</div>


