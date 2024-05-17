<?php
require_once '../config.php';

//get the post data
$json = file_get_contents('php://input');
// Converts it into a PHP object
$requestData = json_decode($json,true);

class api
{
    protected const APIURL = 'https://wheatley.cs.up.ac.za/api/getimage';
    /*Verify the apikey*/
    public function verifyUser($apikey)
    {
        // Sanitize the API key using FILTER_SANITIZE_STRING
        $sanitizedApiKey = filter_var($apikey, FILTER_SANITIZE_STRING);

        global $db;

        $query = "SELECT apikey FROM u21528790_users WHERE apikey = ?";

        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $sanitizedApiKey);
            $stmt->execute();

            // Bind the result variable
            $stmt->bind_result($resultApiKey);

            // Fetch the user data
            $stmt->fetch();

            // Return the user data
            return $resultApiKey !== null;

        } catch (Exception $e) {
            $error_message = "Error verifying user";
            http_response_code(400);
            echo json_encode(['error' => $error_message]);
            return false;
        }
    }

    public function response($success, $message = "", $data="")
    {
        header('Content-Type: application/json');

        $time = time();

        $responseData = ["status" => 'success', "timestamp" => $time, "data" => $data];

        // Encode the response data
        echo json_encode($responseData, JSON_PRETTY_PRINT);
    }

    public function GetAllListings($db,$return,$limit,$sort_field,$sort_order, $search,$page,$fuzzy)
    {
        if($return == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Missing Return Fields"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        //parse the return
        $allowedFields = ["id", "title", "location", "price", "bedrooms", "bathrooms", "URL", "parking_spaces", "amenities", "description", "type", "images"];

        // Parse the return parameter
        $returnFields = [];
        if (isset($return))
        {
            if ($return === "*")
            {
                $selectFields = '*';
            } else
            {
                // Validate requested fields against allowed fields
                $invalidFields = array_diff($return, $allowedFields);
                if (count($invalidFields)>0)
                {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    $timestamp = round(microtime(true) * 1000);
                    $response = array(
                        "status" => "Fail",
                        "timestamp" => $timestamp,
                        "data" => "Invalid Return Fields"
                    );
                    echo json_encode($response, JSON_PRETTY_PRINT);
                    die();
                }
                $returnFields = $return;
                $selectFields = implode(", ", array_map(function($field) {return "`$field`";}, $returnFields));
            }
        }

        // Construct the SELECT clause based on returnFields


        $query = "SELECT $selectFields FROM u21528790_properties ";

        // Initialize the WHERE clause
        $whereClause = '';
        $allowedSearchKeys = ["id", "title", "location", "price_min", "price_max", "bedrooms", "bathrooms", "parking_spaces", "amenities", "type"];

        // Check if $search is not null and is an array
        if($search !== null)
        {
            if (is_array($search))
            {
                // Array to store prepared statement placeholders
                $placeholders = [];

                // Iterate through each search parameter
                foreach ($search as $key => $value)
                {
                    if (!in_array($key, $allowedSearchKeys)) {
                        // Throw an error if an invalid key is found
                        header('Content-Type: application/json');
                        http_response_code(400);
                        $timestamp = round(microtime(true) * 1000);
                        $response = [
                            "status" => "Fail",
                            "timestamp" => $timestamp,
                            "data" => "Invalid search field"
                        ];
                        echo json_encode($response, JSON_PRETTY_PRINT);
                        die();
                    }
                    else{
                        // Apply fuzzy search if enabled
                        if ($fuzzy && $key !== 'price_min' && $key !== 'price_max')
                        {
                            $whereClause .= "$key LIKE '%" . $value . "%' AND ";
                        }
                        else {
                            // Otherwise, perform exact match
                            if ($key === 'price_min') {
                                $whereClause .= 'price > ' . intval($value) . ' AND ';
                            } elseif ($key === 'price_max') {
                                $whereClause .= 'price < ' . intval($value) . ' AND ';
                            } else {
                                $whereClause .= "$key = '" . $value . "' AND ";
                            }
                        }
                    }
                }

                // Remove the trailing 'AND' from the WHERE clause
                $whereClause = rtrim($whereClause, 'AND ');


                // Add the WHERE clause to the main query if it's not empty
                if (!empty($whereClause)) {
                    $query .= " WHERE $whereClause";
                }

            }
            else{
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Invalid Search Field"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
        }

        if($sort_order !== null && $sort_field !== null)
        {
            if(in_array(strtoupper($sort_order), ['ASC', 'DESC']) /*&& in_array(strtoupper($sort_field), ['id', 'title','location','price','bedrooms','bathrooms','parking_spaces'])*/)
            {
                $query .= " ORDER BY ". $sort_field." ". $sort_order;
            }
            else{
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Invalid Order/Sort Field"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
        }
        else if($sort_order !== null && $sort_field === null)
        {
            if(in_array(strtoupper($sort_order), ['ASC', 'DESC']))
            {
                $query .= " ORDER BY title $sort_order";
            }
            else{
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Invalid Order Field"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
        }
        else if($sort_order === null && $sort_field !== null)
        {
            if(in_array(strtoupper($sort_field), ['id', 'title','location','price','bedrooms','bathrooms','parking_spaces']))
            {
                $query .= " ORDER BY $sort_field ASC";
            }
            else{
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Invalid Order Field"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
        }
        else if($sort_order === null && $sort_field === null)
        {
            $query .= " ORDER BY title ASC";
        }


        // Check if the limit parameter is provided and valid
        if ($limit !== null)
        {
            // Validate if the limit is a valid number and within the range 0 to 500
            if (!is_numeric($limit) || $limit < 0 || $limit > 500) {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Invalid Limit Parameter. Limit must be a number between 0 and 500."
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
            // Add the LIMIT clause to the query
            $query .= " LIMIT $limit";
        }

        try{

            $stmt = $db->prepare($query);

            if (!$stmt) {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "SQL Error: ". $query
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }

            if($search !== null)
            {
                // Bind parameters dynamically
                $bindTypes = str_repeat('s', count($search));
                $stmt->bind_param($bindTypes, ...array_values($search));
            }

            // Execute the statement
            $executionResult = $stmt->execute();

            if (!$executionResult) {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "SQL Error: ". $query
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }

            // Get the result set
            $result = $stmt->get_result();

            if (!$result) {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Error Fetching Result"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }

            // Fetch all results
            $results = $result->fetch_all(MYSQLI_ASSOC);
            // Return the user data

            $this->handleReturn($return,$results);
        }

        catch (Exception $e) {

            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e->getMessage()
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    public function getImageUrl($queryParams) {
        // Construct the full URL with query parameters
        $urlWithParams = auctionApi::APIURL . '?' . http_build_query($queryParams);
        // Initialize cURL session
        $ch = curl_init($urlWithParams);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Execute cURL session and get the result
        $jsonResponse  = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            return null;
        }

        // Close cURL session
        curl_close($ch);

        $response = json_decode($jsonResponse, true);

        if ($response === null || !isset($response['data']))
        {
            echo "Error fetching images";
            return null;
        }

        $imageUrls = $response['data'];

        if(gettype($imageUrls)=='String')
        {
            $imageUrlsArray = explode(",", $imageUrls);
            $sanitizedUrls = array_map(function ($imageUrl) {
                return htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
            }, $imageUrlsArray);

            // Check if the response contains valid image URLs
            foreach ($sanitizedUrls as $imageUrl) {
                if (filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
                    return null;
                }
            }
            return $sanitizedUrls;
        }

        $sanitizedUrls = array_map(function ($imageUrl) {
            return htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8');
        }, $imageUrls);

        // Check if the response contains valid image URLs
        foreach ($sanitizedUrls as $imageUrl) {
            if (filter_var($imageUrl, FILTER_VALIDATE_URL) === false) {
                return null;
            }
        }
        return $sanitizedUrls;
    }

    public function registerUser($name,$surname,$email,$password)
    {

        if($name == null || $surname == null || $email  == null || $password == null)
        {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "invalid user signup information",
                "data" => []
            ]);
            die();
        }

        if (!$this->is_email_valid($email)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Invalid email address",
                "data" => []
            ]);
            die();
        }

        if (strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[a-z]/', $password) ||
            !preg_match('/\d/', $password) ||
            !preg_match('/[!@#$%^&*()\-_=+{};:,<.>ยง~]/', $password)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one digit, and one symbol",
                "data" => []
            ]);
            die();
        }

        // Check if user with the email already exists
        $user = $this->get_user($email);

        if ($user === null)
        {
            http_response_code(409);
            echo json_encode([
                "success" => false,
                "message" => "User with this email already exists",
                "data" => []
            ]);
            die();
        }

        $hash = $this->argon2i($password);
        $apikey = $this->generateApiKey();

        $salt = $hash["salt"];
        $hashedPassword = $hash["password"];
        global $db;
        $query = "INSERT INTO u21528790_users (name, surname, email, password, apikey, salt) VALUES (?, ?, ?, ?, ?, ?);";
        try {
            $stmt = $db->prepare($query);
            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $db->error);
            }
            $stmt->bind_param("ssssss", $name, $surname, $email, $hashedPassword, $apikey, $salt);
            $stmt->execute();
        } catch (Exception $e) {
            var_dump( $db->error);
            // If an exception occurs, handle it and return an error response
            http_response_code(500);
            echo json_encode([
                "success" => false,
                "message" => $e->getMessage(),
                "data" => []
            ]);
            die();
        }

// If the insertion was successful, return a success response
        http_response_code(201);
        echo json_encode([
            "success" => true,
            "message" => "User registered successfully",
            "data" => []
        ]);
        die();
    }

    public function GetAllAgents ()
    {
        global $db;

        $query = "SELECT * FROM agencies";

        try {
            $stmt = $db->prepare($query);
            $stmt->execute();

            // Bind the result variable
            $stmt->bind_result($id,$name,$description,$logo,$url);

            // Fetch all rows
            $agents = [];
            while ($stmt->fetch()) {
                $agent = [
                    'id' => $id,
                    'name' => $name,
                    'description' => $description,
                    'logo' => $logo,
                    'url' => $url
                ];
                $agents [] = $agent;
            }

            // Return the makes array
            $this->response(true,'All agents fetched',$agents);
        } catch (Exception $e) {
            // Handle the exception (log, display an error, etc.)
            echo "Error: " . $e->getMessage();
            echo "Error fetching call makes.";
            return null;
        }
    }

    function generateApiKey() {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $apiKey = '';

        for ($i = 0; $i < 16; $i++) {
            $apiKey .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $apiKey;
    }

    function argon2i($password)
    {
        $salt = bin2hex(random_bytes(16)); // generate a random 16-byte salt
        $hash_options = [
            'memory_cost' => 1024, // memory cost parameter
            'time_cost' => 2, // time cost parameter
            'threads' => 2 // number of threads to use
        ];

        // Hash the password with Argon2i
        $hashed_password = password_hash($password . $salt, PASSWORD_ARGON2I, $hash_options);

        // Return the hashed password and the salt
        return [
            "password" => $hashed_password,
            "salt" => $salt
        ];
    }

    public  function handleReturn( $return, $database_results)
    {

        // Convert $return to an array if it's a string
        if (!is_array($return)) {
            // If $return is '*', convert it to an array containing all column names
            if ($return === '*') {
                // Fetch column names from the first database result
                $return = array_keys($database_results[0]);
            } else {
                // Split the comma-separated string into an array
                $return = explode(',', $return);
            }
        }

        // function filters out any unwanted data
        if(in_array("images", $return))
        {
            foreach ($database_results as $result)
            {
                $images = $this->getImageUrl(['listing_id' => $result['id']]);
                $result['images'] = $images;
            }
        }
        header('Content-Type: application/json');
        http_response_code(200);
        $timestamp = round(microtime(true) * 1000);
        $response = array(
            "status" => "Success",
            "timestamp" => $timestamp,
            "data" => $database_results
        );
        echo json_encode($response, JSON_PRETTY_PRINT);
        die();

    }

    function is_email_valid($email):bool
    {
        // Check if the email contains an "@" symbol
        if (strpos($email, '@') === false) {
            return false;
        }

        // Check if the email has at least one character before the "@" symbol
        $username = explode('@', $email)[0];
        if (empty($username)) {
            return false;
        }

        if(filter_var($email,FILTER_VALIDATE_EMAIL)){
            return true;
        }
        else{
            return false;
        }
    }

    function get_user(string $email_given)
    {
        global $db;
        $query = "SELECT * FROM u21528790_users WHERE email = ?";

        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $email_given); // 's' indicates a string parameter
            $stmt->execute();

            // Bind the result variable
            $stmt->bind_result($id,$name,$surname,$email,$password,$apikey,$salt);

            // Fetch the user data
            $stmt->fetch();

            // Return the user data
            return [
                'id' => $id,
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
                'password' => $password,
                'apikey' => $apikey,
                'salt' => $salt
            ];
        } catch (Exception $e) {
            // Handle the exception (log, display an error, etc.)
            echo "Error: " . $e->getMessage();
            return null;
        }
    }

    function login($email_given,$password_given)
    {
        if($email_given == null || $password_given == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid Input"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);

            die();
        }

        if(!$this->is_email_valid($email_given))
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid Email"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        global $db;
        $query = "SELECT * FROM u21528790_users WHERE email = ?";
        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $email_given);
            $stmt->execute();

            // Bind the result variable
            $stmt->bind_result($id,$name,$surname,$email,$password,$apikey,$salt);

            // Fetch the user data
            $stmt->fetch();



            if($email == null)
            {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "User not found"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
            else{
                if(!$this->is_password_valid($password_given,$password,$salt))
                {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    $timestamp = round(microtime(true) * 1000);
                    $response = array(
                        "status" => "Fail",
                        "timestamp" => $timestamp,
                        "data" => "Wrong Password"
                    );
                    echo json_encode($response, JSON_PRETTY_PRINT);
                    die();
                }
                else{
                    header('Content-Type: application/json');
                    http_response_code(400);
                    $timestamp = round(microtime(true) * 1000);
                    $response = array(
                        "status" => "success",
                        "timestamp" => $timestamp,
                        "data" => [
                            [
                                "apikey" => $apikey
                            ]
                        ]
                    );
                    echo json_encode($response, JSON_PRETTY_PRINT);
                    die();
                }
            }

        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e->getMessage()
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function loginImplement($email_given,$password_given)
    {
        if($email_given == null || $password_given == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid Input"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        if(!$this->is_email_valid($email_given))
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid Email"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        global $db;
        $query = "SELECT * FROM u21528790_users WHERE email = ?";
        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $email_given);
            $stmt->execute();

            // Bind the result variable
            $stmt->bind_result($id,$name,$surname,$email,$password,$apikey,$salt);

            // Fetch the user data
            $stmt->fetch();

            // Return the user data
            $user = [
                'id' => $id,
                'name' => $name,
                'surname' => $surname,
                'email' => $email,
                'password' => $password,
                'apikey' => $apikey,
                'salt' => $salt
            ];

            if($email == null)
            {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "User not found"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
            else{
                if(!$this->is_password_valid($password_given,$password,$salt))
                {
                    header('Content-Type: application/json');
                    http_response_code(400);
                    $timestamp = round(microtime(true) * 1000);
                    $response = array(
                        "status" => "Fail",
                        "timestamp" => $timestamp,
                        "data" => "Wrong Password"
                    );
                    echo json_encode($response, JSON_PRETTY_PRINT);
                    die();
                }
                else{
                    header('Content-Type: application/json');
                    http_response_code(200);
                    $timestamp = round(microtime(true) * 1000);
                    $response = array(
                        "status" => "success",
                        "timestamp" => $timestamp,
                        "data" => $user
                    );
                    echo json_encode($response, JSON_PRETTY_PRINT);
                    die();
                }
            }

        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e->getMessage()
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function is_password_valid( $password, $hashed_password, $salt)
    {
        if($this->verify_argon2i($password,$hashed_password,$salt))
        {
            return true;
        }
        else{
            return false;
        }
    }

    function verify_argon2i($password, $hashed_password , $salt)
    {
        // Verify the password
        return password_verify($password . $salt, $hashed_password);
    }

    function update_theme($theme,$apikey)
    {
        global $db;
        if($theme == null || $apikey == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid Information"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }


        $statment= $db->prepare("UPDATE user_information SET theme = ? WHERE apikey = ?");

        $statment->bind_param('ss',$theme,$apikey);

        if ($statment->execute()) {
            // Check if any rows were affected
            if ($statment->affected_rows > 0) {
                //Successful
                header('Content-Type: application/json');
                http_response_code(201);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Success",
                    "timestamp" => $timestamp,
                    "data" => "Update Successful"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            } else {
                header('Content-Type: application/json');
                http_response_code(500);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Update Error"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Update Error"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function update_price($apikey,$min_price,$max_price)
    {
        global $db;
        if($apikey == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid apikey/price"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        if($min_price == null || $min_price == 'null')
        {
            $min_price = 0;
        }

        if($max_price == null || $max_price == 'null')
        {
            $max_price = 0;
        }

        $statment= $db->prepare("UPDATE user_information SET min_price = ?, max_price = ? WHERE apikey = ?");

        $statment->bind_param('iis', $min_price, $max_price, $apikey);

        if ($statment->execute()) {
            // Check if any rows were affected
            if ($statment->affected_rows > 0) {
                //Successful
                /*header('Content-Type: application/json');
                http_response_code(201);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Success",
                    "timestamp" => $timestamp,
                    "data" => "Update Successful"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);*/
                return;

            } else {
                /*header('Content-Type: application/json');
                http_response_code(500);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Update Error"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();*/
            }
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Update Error"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function update_bedrooms($apikey,$min_bedrooms,$max_bedrooms)
    {
        global $db;
        if($apikey == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid apikey/bedrooms"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        if($min_bedrooms == null || $min_bedrooms == 'null')
        {
            $min_bedrooms = 0;
        }

        if($max_bedrooms == null || $max_bedrooms == 'null')
        {
            $max_bedrooms = 0;
        }


        $statment= $db->prepare("UPDATE user_information SET min_bedrooms = ?, max_bedrooms = ? WHERE apikey = ?");

        $statment->bind_param('iis', $min_bedrooms, $max_bedrooms, $apikey);

        if ($statment->execute()) {
            // Check if any rows were affected
            if ($statment->affected_rows > 0) {
                //Successful
                /*header('Content-Type: application/json');
                http_response_code(201);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Success",
                    "timestamp" => $timestamp,
                    "data" => "Update Successful"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);*/
                return;
            } else {
                /*header('Content-Type: application/json');
                http_response_code(500);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Update Error"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();*/
            }
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Update Error"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function update_bathrooms($apikey,$min_bathrooms,$max_bathrooms)
    {
        global $db;
        if($apikey == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid apikey/bathrooms"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        if($min_bathrooms == null || $min_bathrooms == 'null')
        {
            $min_bathrooms = 0;
        }

        if($max_bathrooms == null || $max_bathrooms == 'null')
        {
            $max_bathrooms = 0;
        }

        $statment= $db->prepare("UPDATE user_information SET min_bathrooms = ?, max_bathrooms = ? WHERE apikey = ?");

        $statment->bind_param('iis', $min_bathrooms, $max_bathrooms, $apikey);

        if ($statment->execute()) {
            // Check if any rows were affected
            if ($statment->affected_rows > 0) {
                //Successful
                header('Content-Type: application/json');
                http_response_code(201);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Success",
                    "timestamp" => $timestamp,
                    "data" => "Update Successful"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);

            } else {
                /*header('Content-Type: application/json');
                http_response_code(500);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Update Error"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();*/
                header('Content-Type: application/json');
                http_response_code(201);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Success",
                    "timestamp" => $timestamp,
                    "data" => "Update Successful"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
            }
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Update Error"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function update_favourites($apikey,$favourites)
    {
        global $db;
        if($apikey == null || $favourites == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid apikey/favourites"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        $statment= $db->prepare("UPDATE user_information SET favourites = ? WHERE apikey = ?");

        $statment->bind_param('ss', $favourites, $apikey);

        if ($statment->execute()) {
            // Check if any rows were affected
            if ($statment->affected_rows > 0) {
                //Successful
                header('Content-Type: application/json');
                http_response_code(201);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Success",
                    "timestamp" => $timestamp,
                    "data" => "Update Successful"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            } else {
                header('Content-Type: application/json');
                http_response_code(500);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Update Error"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
        } else {
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Update Error"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function get_favourites($properties)
    {
        global $db;
        // Split the comma-separated string into an array of IDs
        $propertyIds = explode(',', $properties);
        // Construct the parameter placeholders based on the number of IDs in the array
        $placeholders = implode(',', array_fill(0, count($propertyIds), '?'));
        $query = "SELECT * FROM u21528790_properties WHERE id IN ($placeholders)";

        try {
            $stmt = $db->prepare($query);
            // Construct the types string dynamically based on the number of IDs
            $types = str_repeat('i', count($propertyIds));

            // Create an array of references to bind parameters
            $params = array_merge([$types], $propertyIds);
            $refs = [];
            foreach ($params as $key => &$value) {
                $refs[$key] = &$value;
            }

            // Bind parameters dynamically
            call_user_func_array([$stmt, 'bind_param'], $refs);

            $stmt->execute();

            // Bind the result variable
            $stmt->bind_result($id,$title,$price,$bedrooms,$bathrooms,$parking_spaces,$location,$description,$images,$type,$amenities,$url,$agent,$createdAt,$updated);

            // Fetch the results
            $results = [];
            while ($stmt->fetch()) {
                $results[] = [
                    'id' => $id,
                    'title' => $title,
                    'price' => $price,
                    'bedrooms' => $bedrooms,
                    'bathrooms' => $bathrooms,
                    'parking_spaces' => $parking_spaces,
                    'location' => $location,
                    'description' => $description,
                    'images' => $images,
                    'type' => $type,
                    'amenities' => $amenities,
                    'url' => $url,
                    'agent' => $agent,
                    'created' => $createdAt,
                    'updated' => $updated
                ];
            }
            $this->handleReturn("*",$results);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e->getMessage()
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    //returns an array of ids for the properties
    function get_favourites_helper($apikey)
    {
        global $db;
        $query = "SELECT favourites FROM user_information WHERE apikey = ?";
        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $apikey); // 's' indicates a string parameter
            $stmt->execute();

            $stmt->bind_result($favourites);

            $stmt->fetch();

            // Close the statement
            $stmt->close();

            // Convert the string of IDs to an array of integers
            //$favouritesArray = array_map('intval', explode(',', $favourites));

            $this->get_favourites($favourites);


        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e->getMessage()
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function getTheme($apikey)
    {
        if($apikey == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid apikey/bedrooms"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        global $db;
        $query = "SELECT theme FROM user_information WHERE apikey = ?";
        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $apikey); // 's' indicates a string parameter
            $stmt->execute();

            $stmt->bind_result($theme);

            $stmt->fetch();

            // Close the statement
            $stmt->close();

            header('Content-Type: application/json');
            http_response_code(200);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Success",
                "timestamp" => $timestamp,
                "data" => $theme
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();


        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e->getMessage()
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function get_preferences($apikey){
        if($apikey == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Invalid apikey"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        global $db;
        $query = "SELECT min_bathrooms, max_bathrooms, min_bedrooms, max_bedrooms, min_price, max_price FROM user_information WHERE apikey = ?";
        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $apikey); // 's' indicates a string parameter
            $stmt->execute();

            $stmt->bind_result($min_bathrooms,$max_bathrooms,$min_bedrooms,$max_bedrooms,$min_price,$max_price);

            $stmt->fetch();

            // Close the statement
            $stmt->close();

            header('Content-Type: application/json');
            http_response_code(200);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Success",
                "timestamp" => $timestamp,
                "data" => [
                    "min_bathrooms" => $min_bathrooms,
                    "max_bathrooms" => $max_bathrooms,
                    "min_bedrooms" => $min_bedrooms,
                    "max_bedrooms" => $max_bedrooms,
                    "min_price" => $min_price,
                    "max_price" => $max_price
                ]
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();


        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e->getMessage()
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }
}

$api = new auctionApi();

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    $type = $requestData['type'] ?? null;

    /*$type = "updateBathroom";
    $requestData['apikey'] = "oUsBARLyO4bJfM7Y";
    $requestData['min_bathrooms'] = "0";
    $requestData['max_bathrooms'] = "4";*/
//    $requestData['apikey'] = "oUsBARLyO4bJfM7Y";
//    $type = "GetTheme";

    if($type == 'Register')
    {
        $name = $requestData['name'] ?? null;
        $surname = $requestData['surname'] ?? null;
        $email = $requestData['email'] ?? null;
        $password = $requestData['password'] ?? null;
        $api->registerUser($name,$surname,$email,$password);
        die();
    }
    else if($type == "Login")
    {
        $email = $requestData['email'] ?? null;
        $password = $requestData['password'] ?? null;
        $api->login($email,$password);
        die();
    }
    else if($type == "Login_")
    {
        $email = $requestData['email'] ?? null;
        $password = $requestData['password'] ?? null;
        $api->loginImplement($email,$password);
        die();
    }
    else if($type == "Login2")
    {
        $email = $requestData['email'] ?? null;
        $password = $requestData['password'] ?? null;
        $api->loginImplement($email,$password);
        die();
    }
    else if($type == "updateTheme")
    {
        $apikey = $requestData['apikey'] ?? null;
        $theme = $requestData['theme'] ?? null;
        $api->update_theme($theme,$apikey);
        die();
    }
    else if($type == "updateBathroom")
    {
        $apikey = $requestData['apikey'] ?? null;
        $min_bathrooms = $requestData['min_bathrooms'] ?? null;
        $max_bathrooms = $requestData['max_bathrooms'] ?? null;

        $api->update_bathrooms($apikey,$min_bathrooms,$max_bathrooms);
        die();
    }
    else if($type == "updateBedroom")
    {
        $apikey = $requestData['apikey'] ?? null;

        $min_bedrooms = $requestData['min_bedrooms'] ?? null;
        $max_bedrooms = $requestData['max_bedrooms'] ?? null;

        $api->update_bedrooms($apikey,$min_bedrooms,$max_bedrooms);
        die();
    }
    else if($type == "updatePrice")
    {
        $apikey = $requestData['apikey'] ?? null;
        $min_price = $requestData['min_price'] ?? null;
        $max_price = $requestData['max_price'] ?? null;
        $api->update_price($apikey,$min_price,$max_price);
        die();
    }
    else if($type == "updateFavourites")
    {
        $apikey = $requestData['apikey'] ?? null;
        $favourites = $requestData['favourites'] ?? null;
        $api->update_favourites($apikey,$favourites);
        die();
    }
    else if($type == "favourite")
    {
        $apikey = $requestData['apikey'] ?? null;
        $favourites = $requestData['favourites'] ?? null;
        $api->update_favourites($apikey,$favourites);
        die();
    }
    else if($type == "getFavourites")
    {
        $apikey = $requestData['apikey'] ?? null;
        $api->get_favourites_helper($apikey);
        die();
    }
    else if($type == "GetTheme")
    {
        $apikey = $requestData['apikey'] ?? null;
        $api->getTheme($apikey);
        die();
    }
    else if($type == "save")
    {
        $apikey = $requestData['apikey'] ?? null;

        $min_price = $requestData['min_price'] ?? null;
        $max_price = $requestData['max_price'] ?? null;

        $min_bedrooms = $requestData['min_bedrooms'] ?? null;
        $max_bedrooms = $requestData['max_bedrooms'] ?? null;

        $min_bathrooms = $requestData['min_bathrooms'] ?? null;
        $max_bathrooms = $requestData['max_bathrooms'] ?? null;

        $api->update_price($apikey,$min_price,$max_price);
        $api->update_bedrooms($apikey,$min_bedrooms,$max_bedrooms);
        $api->update_bathrooms($apikey,$min_bathrooms,$max_bathrooms);
        die();
    }
    else if($type == "GetPreferences")
    {
        $apikey = $requestData['apikey'] ?? null;
        $api->get_preferences($apikey);
    }

    $apiKey = $requestData['apikey'] ?? null;
    $return = $requestData['return'] ?? null;
    $limit = $requestData['limit'] ?? null;
    $sort = $requestData['sort'] ?? null;
    $order = $requestData['order'] ?? null;
    $search = $requestData['search'] ?? null;
    $fuzzy = $requestData['fuzzy'] ?? null;

    $page = $requestData['page'] ?? null;

    /*$type = 'GetAllListings';
    $page=1;
    $limit = 20;*/



    if($api->verifyUser($apiKey)){
        global $db;

        if($type == 'GetAllListings')
        {
            $api->GetAllListings($db,$return,$limit,$sort,$order,$search,$page,$fuzzy);
        }
        else if($type == 'GetAllAgents')
        {
            $api->GetAllAgents();
        }
        else {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "invalid type",
            ]);
            die();
        }
    }
    else{
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "message" => "invalid user",
            "data" => $apiKey
        ]);
        die();
    }

}
