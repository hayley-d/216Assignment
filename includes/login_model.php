<?php
declare(strict_types=1);

function get_user(string $username_given)
{
    global $db;

    $query = "SELECT * FROM users WHERE username = ?";

    try {
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $username_given); // 's' indicates a string parameter
        $stmt->execute();

        // Bind the result variable
        $stmt->bind_result($id,$username,$password,$created_at);

        // Fetch the user data
        $stmt->fetch();

        // Return the user data
        return [
            'id' => $id,
            'username' => $username,
            'password' => $password,
            'created_at' => $created_at
        ];
    } catch (Exception $e) {
        // Handle the exception (log, display an error, etc.)
        echo "Error: " . $e->getMessage();
        return null;
    }
}
