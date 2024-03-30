<?php
require_once '../config_session.php';
require_once './login_view.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aperture Auctions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/login.css">

</head>
<body>
<div><a id="back-btn" href="../index.php">Back</a></div>
<section>
    <form id="login-form" method="POST" action="login_validate.php">

        <div class="heading"><h3>Login</h3></div>
        <div>
            <label for="username">Username</label>
        </div>
        <div><input type="text" id="username" name="username" placeholder="Enter username" required></div>

        <div>
            <label for="password">Password</label>
        </div>
        <div><input type="password" id="password" name="password" placeholder="Enter password" required></div>

        <div class="submit"><button type="submit" onclick="validateInformation()">Login</button> <button><a href="./signup.php">Sign Up</a></button></div>

    </form>
</section>
<div class="errors">
    <?php

    ?>
</div>

<script>
    function validateInformation()
    {
        document.getElementById('login-form').submit();
    }

    function redirect(){
        window.location.href = './signup.php';
    }
</script>

</body>
</html>
