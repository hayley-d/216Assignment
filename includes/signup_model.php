<?php
//only functions working with the database
//ensures strict types
declare(strict_types=1);

require_once '../config.php';

function get_user(string $username_given)
{
    global $db;
    $query = "SELECT * FROM users WHERE username = ?";

    try {
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $username_given);
        $stmt->execute();



        // Bind the result variable
        $stmt->bind_result($id, $username, $password, $created_at);

        // Check if any rows were returned
        if (!$stmt->fetch()) {
            // User not found
            return null;
        }

        // Return the user data
        return [
            'id' => $id,
            'username' => $username,
            'password' => $password,
            'created_at' => $created_at,
        ];
    } catch (Exception $e) {
        // Handle the exception (log, display an error, etc.)
        echo "Error: " . $e->getMessage();
        return null;
    }
}

function create_user($username,$password):void
{
    global $db;
    //Hash the password
    $hashed_password = argon2i($password);

    $query = "INSERT INTO users (username,password) VALUES (?,?);";

    try {
        $stmt = $db->prepare($query);
        $stmt->bind_param("ss", $username,$hashed_password); // 's' indicates a string parameter
        $stmt->execute();

    } catch (Exception $e) {
        // Handle the exception (log, display an error, etc.)
        echo "Error: " . $e->getMessage();
        header('Location: ../index.php');
        die();
    }
}

