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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

</head>
<body>
<div><a id="back-btn" href="../index.php">Back</a></div>
<section>
    <form id="login-form">

        <div class="heading"><h3>Login</h3></div>

        <div>
            <label for="email">Email</label>
        </div>
        <div><input type="text" id="email" name="email" placeholder="Enter Email" required></div>

        <div>
            <label for="password">Password</label>
        </div>
        <div><input type="password" id="password" name="password" placeholder="Enter password" required></div>

        <div class="submit"><button type="submit" id="login-button" onclick="findUser()">Login</button> <button><a href="./signup.php">Sign Up</a></button></div>

    </form>
</section>
<div class="errors">
    <?php

    ?>
</div>

<script>

    $(document).ready(function() {
        $('#login-button').click(function(event)
        {
            event.preventDefault(); // Prevent default form submission
            findUser(); // Call the validation function
        });
    });

    function findUser()
    {

        const email = $('#email').val();
        const password = $('#password').val();
        // Create data object to send
        const data = {
            type:'Login',
            email:email,
            password:password
        }
        // Make AJAX request to your API
        $.ajax({
            type: 'POST',
            url: 'https://wheatley.cs.up.ac.za/u21528790/COS216/PA4/includes/auction_api.php',
            data: JSON.stringify(data),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                // Set authorization header
                xhr.setRequestHeader('Authorization', 'Basic ' + btoa('u21528790' + ':' + '345803Moo'));
            },
            success: function(response) {
                console.log(response);
                //set the session variable
                sessionStorage.setItem('email',response.data.email)
                //take to the main page
                window.location.href = "../index.php";
            },
            error: function(xhr, status, error) {
                // Handle error response
                console.error(xhr.responseText);
            }
        });
    }

    function redirect(){
        window.location.href = './signup.php';
    }
</script>

</body>
</html>
