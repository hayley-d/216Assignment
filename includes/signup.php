<?php
require_once '../config_session.php';
    require_once './signup_view.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aperture Auctions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/sign_up.css">

</head>
<body>
<div><a href="../index.php">Back</a></div>
<section>
    <form id="signup-form" method="POST" action="signup_validate.php">

        <div class="heading"><h3>Sign Up</h3></div>
        <div>
            <label for="username">Username</label>
        </div>
        <div><input type="text" id="username" name="username" placeholder="Enter username"></div>

        <div>
            <label for="password">Password</label>
        </div>
        <div><input type="password" id="password" name="password" placeholder="Enter password"></div>

        <div class="submit"><button type="submit" onclick="validateInformation()">Sign up</button></div>

    </form>
</section>
<div class="errors">
    <?php
        check_signup_errors();
    ?>
</div>

<script>
    function validateInformation()
    {
        document.getElementById('signup-form').submit();
    }
</script>

</body>
</html>
