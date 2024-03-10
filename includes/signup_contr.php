<?php
//ensures strict types
declare(strict_types=1);

function is_input_empty(string $username,string $password):bool
{
    if( empty($username) || empty($password))
    {
        return true;
    }
    else{
        return false;
    }
}

function is_input_to_long(string $username,string $password):bool
{
    if( strlen($username)>30 || strlen($password)>255)
    {
        return true;
    }
    else{
        return false;
    }
}

function is_username_taken(string $username):bool {
    if(get_user($username) !== null)
    {
        return true;
    }
    else {
        return false;
    }
}

function set_user($username,$password):void
{
    create_user($username,$password);
}

function argon2i($password): string
{
    $salt = bin2hex(random_bytes(16)); // generate a random 16-byte salt
    $hash_options = [
        'memory_cost' => 1024,
        'time_cost' => 2,
        'threads' => 2
    ];

    // Hash the password with Argon2i
    $hashed_password = password_hash($password . $salt, PASSWORD_ARGON2I, $hash_options);

    // Return the hashed password and the salt
    return $hashed_password . '|' . $salt;
}