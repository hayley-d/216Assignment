<?php
if($_SERVER["REQUEST_METHOD"] === "POST")
{
    //get the data from the form inputs
    $username = $_POST['username'];
    $password = $_POST['password'];


    try{
        require_once '../config.php';
        require_once 'login_model.php';
        require_once 'login_contr.php';


        $errors = []; //empty array for the errors caught

        /*if(is_input_empty($username,$password)){
            $errors['empty_input'] = "Fill in all fields";
        }*/

        $result = get_user($username);

        if(!is_username_valid($result))
        {
            $errors['wrong_username'] = "Username is incorrect";
        }

        if(is_username_valid($result))
        {
            if(!is_password_valid($password,$result['password']))
            {
                $errors['wrong_password'] = "Password is incorrect";
            }
        }


        //handel the errors
        require_once '../config_session.php';

        if(!empty($errors)){
            $_SESSION['errors_login'] = $errors;

            header("location: ./login.php");
            die();
        }
        else{

           /* $newSessionId = session_create_id();
            $sessionId = $newSessionId . '_' . $result['id'];
            session_id($sessionId); //sets the session id to the created session id
            $_SESSION['last_regeneration'] = time();*/


            $_SESSION['user_id'] = $result['id'];
            $_SESSION['username'] = htmlspecialchars($result['username']);//sanitize result avoid any cross side scripting
            $_SESSION['password'] = $password;


            echo $_SESSION['username']. $_SESSION['password'];

            header("Location: ../index.php");

            $stmt = null;//close statement
            die();
        }


    } catch(mysqli_sql_exception $e){
        die("Login Failed: " . $e->getMessage());
    }
}
else{
    //if no post method was recieved
    header("Location: ../index.php");
    die();
}
?>