<?php
//check if user accessed page correctly
if($_SERVER["REQUEST_METHOD"]==="POST")
{
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $password = $_POST['password'];


    try{
        //connect to database
        require_once '../config.php';

        //model comes before the controller
        require_once 'signup_model.php';
        require_once 'signup_contr.php';

        //Array for holding errors
        $errors = [];

        //checks that all input is entered
        /*if(is_input_empty($username,$password)){
            $errors['empty_input'] = 'Please enter all required fields.';
        }
        else if (is_input_to_long($username,$password))
        {
            $errors['input_length'] = 'Username or Password is too long.';
        }
        else*/ if(is_username_taken($username)){
            //checks if the username is already on the database
            $errors['username_taken'] = 'Username is taken.';
        }

        foreach ($errors as $error){
            echo '<p>'.$error.'</p>';
        }

        //start the session
        require_once '../config_session.php';

        if(!empty($errors))
        {
            $_SESSION['error_signup'] = $errors;

            $_SESSION['user_signup_data'] = [
                'username' => $username
            ];

            header('Location: ./signup.php');
            die();
        }

        set_user($username,$password);


        header('Location: ./login.php');
        $stmt = null;

        die();

    } catch(mysqli_sql_exception $e)
    {
        die('Signup Failed: ' . $e->getMessage());
    }
}
else{
    header("Location: ../index.php");
    die();
}