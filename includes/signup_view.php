<?php
//ensures strict types

function check_signup_errors()
{
    if(isset($_SESSION['errors_signup']))
    {
        $errors = $_SESSION['errors_signup'];

        foreach ($errors as $error){
            echo '<p>'.$error.'</p>';
        }
        unset($_SESSION['errors_signup']);
    }
    else if(isset($_GET['signup']) && $_GET['signup'] === 'success'){
        //take the user to the login page
        header('Location: ./login.php');
        die();
    }
}


