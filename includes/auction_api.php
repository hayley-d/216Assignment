<?php
require_once '../config.php';

//get the post data
$json = file_get_contents('php://input');
// Converts it into a PHP object
$requestData = json_decode($json,true);

class auctionApi
{
    public function createAuction( $name, $property_id, $user_email,$start_time,$end_time)
    {
        global $db;
        if($name == null || $property_id == null || $user_email == null || $start_time == null || $end_time == null)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => 'Missing auction details'
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        $name = filter_var($name, FILTER_SANITIZE_STRING);

        //get the userId
        $user_id = (int) $this->getUserId($user_email);

        // Convert start_time and end_time to DateTime objects for comparison
        $start_datetime = new DateTime($start_time);
        $end_datetime = new DateTime($end_time);

        // Check if start time is before end time
        if($start_datetime >= $end_datetime)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Start date comes after end date"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }

        // Check if start time has passed and end time has not passed
        $current_datetime = new DateTime();
        $status = "ongoing";
        if($current_datetime >= $start_datetime && $current_datetime < $end_datetime)
        {
            $status = "ongoing";
        }
        elseif($current_datetime < $start_datetime)
        {
            $status = "waiting";
        }
        else
        {
            $status = "done";
        }

        $code = $this->generateAuctionCode();

        $start_time = $start_datetime->format('Y-m-d H:i:s');
        $end_time = $end_datetime->format('Y-m-d H:i:s');

        // Insert auction into 'auction' table with the property ID and status
        $query = "INSERT INTO auctions (auction_name, property_id,user_id ,start, end, status, auction_code) VALUES (?, ?, ?, ?, ?, ?,?)";
        $stmt = $db->prepare($query);
        if (!$stmt) {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "Failed to prepare query"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
        $stmt->bind_param("siissss", $name, $property_id,$user_id, $start_time, $end_time, $status,$code);
        $result = $stmt->execute();

        // Check if the insertion was successful
        if (!$result) {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "INSERT INTO auctions ('auction_name','property_id','user_id','start','end','status','highest_bid','buyer','auction_code') VALUES ('$name', '$property_id', '$user_id', '$start_time', '$end_time', '$status', '0', NULL, '$code')"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
        if ($stmt->affected_rows > 0) {
            header('Content-Type: application/json');
            http_response_code(200);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Success",
                "timestamp" => $timestamp,
                "data" => 'Auction Created'
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        } else {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => 'Failed to create auction'
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    public function updateAuction($code,$start,$end,$highest_bid,$buyer)
    {
        global $db;
        if($code == null)
        {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Auction code invalid",
                "data" => []
            ]);
            die();
        }

        $code = filter_var($code, FILTER_SANITIZE_STRING);

        //Check if the auction exists
        $query = "SELECT * FROM auctions WHERE auction_code = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows == 0)
        {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "No auction with the provided name exists",
                "data" => []
            ]);
            die();
        }

        $auction = $result->fetch_assoc();

        $current_time = new DateTime();
        $start_time = ($start != null) ? new DateTime($start): new DateTime($auction['start']);
        $end_time = ($end != null) ? new DateTime($end): new DateTime($auction['end']);

        if($start_time >= $end_time)
        {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Start date/time must be before the end date/time",
                "data" => []
            ]);
            die();
        }

        // Determine auction status based on start and end times
        $status = ($current_time >= $start_time && $current_time <= $end_time) ? "ongoing" : ($current_time < $start_time ? "waiting" : "done");
        $update_values[] = "status = '$status'";

        if($start !=null)
        {
            $update_values[] = "start = '$start'";
        }

        if($end !=null)
        {
            $update_values[] = "end = '$end'";
        }

        if($highest_bid !=null)
        {
            //update the highest bid
            $update_values[] = "highest_bid = '$highest_bid'";
        }

        if(buyer != null)
        {
            //update buyer (user_id)
            $update_values[] = "buyer = '$buyer'";
        }

        // Update the auction in the database
        $update_query = "UPDATE auctions SET " . implode(", ", $update_values) . " WHERE auction_code = '$code'";
        $stmt = $db->prepare($update_query);
        $stmt->execute();

        // Check if the update was successful
        if($stmt->affected_rows > 0) {
            // Return success response
            $this->response(true, "Auction updated successfully");
        } else {
            // Return failure response
            $this->response(false, "Failed to update auction");
        }
    }

    public function getAuction(string $code)
    {
        try{
            if ($code == null) {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => "Missing Auction Code"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
            // Sanitize and validate parameters
            $code = filter_var($code, FILTER_SANITIZE_STRING);

            global $db;
            //Return specified auction
            $query = "SELECT * FROM auctions WHERE auction_code = ? ";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $code);
            $stmt->execute();

            // Bind the result variable
            $stmt->bind_result($auction_id, $auction_name, $property_id, $user_id, $start, $end, $status,$highest_bid,$buyer,$auction_code);

            // Fetch the auction data
            $stmt->fetch();

            $auction_data = [
                'auction_id' => $auction_id,
                'auction_name' => $auction_name,
                'property_id' => $property_id,
                'user_id' => $user_id,
                'start' => $start,
                'end' => $end,
                'status' => $status,
                'highest_bid' => $highest_bid,
                'buyer' => $buyer,
                'auction_code' => $auction_code
            ];

            $stmt->close();

            //Get the property data
            $obj = $this->getProperty($auction_data['property_id']);

            $auction_data['property_title'] = $obj['title'];
            $auction_data['property_location'] = $obj['location'];
            $auction_data['property_price'] = $obj['price'];
            $auction_data['property_bedrooms'] = $obj['bedroom'];
            $auction_data['property_bathrooms'] = $obj['bathroom'];
            $auction_data['property_parking'] = $obj['parking'];
            $auction_data['property_amenities'] = $obj['amenities'];
            $auction_data['property_description'] = $obj['description'];
            $auction_data['property_image'] = $obj['image_path'];

            header('Content-Type: application/json');
            http_response_code(200);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Success",
                "timestamp" => $timestamp,
                "data" => $auction_data
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
        catch(Exception $e){
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    public function getAllAuctions($date)
    {
        try{
            global $db;
            //Update all auction statuses first
            $this->updateAuctionsStatus($date);

            $query = "SELECT * FROM auctions WHERE status = 'ongoing'";
            $stmt = $db->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            $results = $result->fetch_all(MYSQLI_ASSOC);

            foreach ($results as &$auction) {
                $propertyId = $auction['property_id'];
                $property = $this->getProperty($propertyId);
                $auction['property'] = $property;
            }

            header('Content-Type: application/json');
            http_response_code(200);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Success",
                "timestamp" => $timestamp,
                "data" => $results
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
        catch(Exception $e){
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    function updateAuctionsStatus($date){
        try{
            global $db;
            // Get current date and time
            $currentDateTime = $date;

            // Update auctions with start date in the future to 'waiting'
            $query = "UPDATE auctions SET status = 'waiting' WHERE start > ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $currentDateTime);
            $stmt->execute();
            $stmt->close();

            // Update auctions with start date in the past and end date in the future to 'ongoing'
            $query = "UPDATE auctions SET status = 'ongoing' WHERE start <= ? AND end > ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("ss", $currentDateTime, $currentDateTime);
            $stmt->execute();
            $stmt->close();

            // Update auctions with end date in the past to 'ended'
            $query = "UPDATE auctions SET status = 'done' WHERE end <= ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $currentDateTime);
            $stmt->execute();
            $stmt->close();
        }
        catch(Exception $e){
            header('Content-Type: application/json');
            http_response_code(500);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    public function getUserAuction( $userId)
    {
        // Sanitize and validate parameters
        $userId = filter_var($userId, FILTER_SANITIZE_NUMBER_INT);
        global $db;
        $query = "SELECT * FROM auctions WHERE user_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);

        foreach ($results as &$auction) {
            $propertyId = $auction['property_id'];
            $property = $this->getProperty($propertyId);
            $auction['property'] = $property;
        }

        $this->response(true,$results);
    }

    public function getProperty( $propertyId)
    {
        $propertyId = (int) $propertyId;
        try{
            // Sanitize and validate parameters
            $propertyId = (int) filter_var($propertyId, FILTER_SANITIZE_NUMBER_INT);
            global $db;
            $query = "SELECT * FROM `properties` WHERE `property_id` = ?";
            $stmt = $db->prepare($query);

            if (!$stmt) {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => 'Failed to prepare query'
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }

            $stmt->bind_param("i", $propertyId);

            $stmt->execute();

            // Bind the result variable
            $stmt->bind_result($property_id, $title, $price, $location, $bedroom, $bathroom, $parking,$amenities,$description,$image_path);

            // Fetch the result
            if ($stmt->fetch()) {
                // Return the property data
                return [
                    'property_id' => $property_id,
                    'title' => $title,
                    'price' => $price,
                    'location' => $location,
                    'bedroom' => $bedroom,
                    'bathroom' => $bathroom,
                    'parking' => $parking,
                    'amenities' => $amenities,
                    'description' => $description,
                    'image_path' =>$image_path,

                ];
            } else {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => 'No property found'
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
        }
        catch(Exception $e)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    public function getUser( $email)
    {
        global $db;
        $query = "SELECT * FROM users WHERE email = ?";

        $stmt = $db->prepare($query);

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows > 0)
        {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function getUserId( $email)
    {
        try{
            global $db;

            // Sanitize the email input
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);

            $query = "SELECT id FROM users WHERE email = ?";

            $stmt = $db->prepare($query);

            if (!$stmt) {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => 'Failed to prepare query'
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }

            $stmt->bind_param("s", $email);
            $stmt->execute();

            $result = $stmt->get_result();
            if ($result->num_rows > 0)
            {
                $row = $result->fetch_assoc();
                $user_id = $row['id'];
                $stmt->close();
                return $user_id;
            } else {
                $stmt->close();
                return false;
            }
        }
        catch(Exception $e)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    public function createProperty($title,$price,$location,$bedroom,$bathroom,$parking,$amenities,$description,$image_url)
    {
        try{
            global $db;
            $title = filter_var($title, FILTER_SANITIZE_STRING);
            $location = filter_var($location, FILTER_SANITIZE_STRING);
            $price = filter_var($price, FILTER_SANITIZE_NUMBER_INT);
            $bedroom = (int)filter_var($bedroom, FILTER_SANITIZE_NUMBER_INT);
            $bathroom = (int)filter_var($bathroom, FILTER_SANITIZE_NUMBER_INT);
            $parking = (int)filter_var($parking, FILTER_SANITIZE_NUMBER_INT);
            $amenities = filter_var($amenities, FILTER_SANITIZE_STRING);
            $description = filter_var($description, FILTER_SANITIZE_STRING);
            $image_url = filter_var($image_url, FILTER_SANITIZE_URL);

            $query = "INSERT INTO properties (title, price, location, bedroom, bathroom, parking, amenities, description,image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)";
            $stmt = $db->prepare($query);
            if (!$stmt) {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => 'Failed to prepare query while creating property'
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
            $stmt->bind_param("sdsdddsss",
                $title,
                $price,
                $location,
                $bedroom,
                $bathroom,
                $parking,
                $amenities,
                $description,
                $image_url
            );
            $result = $stmt->execute();
            if (!$result) {
                header('Content-Type: application/json');
                http_response_code(400);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Fail",
                    "timestamp" => $timestamp,
                    "data" => $stmt->error
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
            $property_id = $stmt->insert_id;
            return $property_id;
        }
        catch(Exception $e)
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => $e
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
    }

    public function endAuction($code){
        global $db;
        if($code == null)
        {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Auction code invalid",
                "data" => []
            ]);
            die();
        }

        $code = filter_var($code, FILTER_SANITIZE_STRING);

        $query = "UPDATE auctions SET status = 'done' WHERE auction_code = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $code);
        $stmt->execute();
        // Check if the update was successful
        if($stmt->affected_rows > 0) {
            // Return success response
            $this->response(true, "Auction updated successfully");
        }
    }

    public function updateBuyer($code,$buyer){
        global $db;
        if($code == null)
        {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Auction code invalid",
                "data" => []
            ]);
            die();
        }

        $code = filter_var($code, FILTER_SANITIZE_STRING);
        $buyer = filter_var($buyer, FILTER_SANITIZE_NUMBER_INT);

        $query = "UPDATE auctions SET buyer = ? WHERE auction_code = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("is", $buyer, $code);
        $stmt->execute();

        // Check if the update was successful
        if ($stmt->affected_rows > 0) {
            // Return success response
            $this->response(true, "Auction updated successfully");
        } else {
            // Return failure response
            $this->response(false, "Failed to update auction");
        }
    }

    protected function response(bool $success,$data)
    {
        $time = time();

        $responseData = ["success" => $success, "timestamp" => $time, "data" => $data];

        echo json_encode($responseData, JSON_PRETTY_PRINT);
    }

    private function generateAuctionCode(){
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits = '0123456789';
        $code = '';

        // Generate 3 random letters
        for ($i = 0; $i < 3; $i++) {
            $code .= $letters[rand(0, strlen($letters) - 1)];
        }

        // Generate 3 random digits
        for ($i = 0; $i < 3; $i++) {
            $code .= $digits[rand(0, strlen($digits) - 1)];
        }

        // Shuffle the code to mix letters and digits
        $code = str_shuffle($code);

        return $code;
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
        $query = "SELECT * FROM users WHERE email = ?";
        try {
            $stmt = $db->prepare($query);
            $stmt->bind_param("s", $email_given);
            $stmt->execute();

            // Bind the result variable
            $stmt->bind_result($id,$username,$password,$email,$created_at);

            // Fetch the user data
            $stmt->fetch();

            // Return the user data
            $user = [
                'id' => $id,
                'username' => $username,
                'password' => $password,
                'email' => $email,
                'created_at' => $created_at,
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
                if(!$this->is_password_valid($password_given,$password))
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

    function signUp($email_given,$username_given,$password_given)
    {
        if($email_given == null || $password_given == null || $username_given == null)
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

        if($this->getUser($email_given))
        {
            header('Content-Type: application/json');
            http_response_code(400);
            $timestamp = round(microtime(true) * 1000);
            $response = array(
                "status" => "Fail",
                "timestamp" => $timestamp,
                "data" => "User Already Exists"
            );
            echo json_encode($response, JSON_PRETTY_PRINT);
            die();
        }
        else{
            global $db;

            $hashed_password = $this->argon2i($password_given);

            $query = "INSERT INTO users (username,password,email) VALUES (?,?,?);";

            try {
                $stmt = $db->prepare($query);
                $stmt->bind_param("sss", $username_given,$hashed_password,$email_given);
                $stmt->execute();

                header('Content-Type: application/json');
                http_response_code(200);
                $timestamp = round(microtime(true) * 1000);
                $response = array(
                    "status" => "Success",
                    "timestamp" => $timestamp,
                    "data" => "User Added"
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
                    "data" => "Error Adding New User"
                );
                echo json_encode($response, JSON_PRETTY_PRINT);
                die();
            }
        }
    }

    function is_password_valid( $password, $hashed_password)
    {
        if($this->verify_argon2i($password,$hashed_password))
        {
            return true;
        }
        else{
            return false;
        }
    }

    function argon2i($password): string
    {
        //$salt = bin2hex(random_bytes(16)); // generate a random 16-byte salt
        $hash_options = [
            'memory_cost' => 1024,
            'time_cost' => 2,
            'threads' => 2
        ];

        // Hash the password with Argon2i
        $hashed_password = password_hash($password, PASSWORD_ARGON2I, $hash_options);

        // Return the hashed password and the salt
        return $hashed_password ;
    }

    function verify_argon2i($password, $hashed_password): bool
    {
        return password_verify($password, $hashed_password);
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
}
//user id is passed through the hidden input

$api = new auctionApi();

if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
    $type = $requestData['type'] ?? null;

    if($type == null)
    {
        header('Content-Type: application/json');
        http_response_code(400);
        $timestamp = round(microtime(true) * 1000);
        $response = array(
            "status" => "Fail",
            "timestamp" => $timestamp,
            "data" => "Invalid Type Provided"
        );
        echo json_encode($response, JSON_PRETTY_PRINT);
        die();
    }

    if($type == 'CreateAuction')
    {
        $title = $requestData['propertyTitle'] ?? null;
        $price = $requestData['propertyPrice'] ?? null;
        $location = $requestData['propertyLocation'] ?? null;
        $bedroom = $requestData['propertyBed'] ?? null;
        $bathroom= $requestData['propertyBath'] ?? null;
        $parking= $requestData['propertyParking'] ?? null;
        $amenities = $requestData['propertyAmenities'] ?? null;
        $description = $requestData['propertyDescription'] ?? null;
        $image_url = $requestData['propertyImage'] ?? null;
        /*Create property*/
        $property_id = $api->createProperty($title,$price,$location,$bedroom,$bathroom,$parking,$amenities,$description,$image_url);

        $name = $requestData['auctionName'] ?? null;
        $user_email = $requestData['userEmail'] ?? null;
        $start = $requestData['start'] ?? null;
        $end = $requestData['end'] ?? null;
        /*create auction*/
        $api->createAuction($name,$property_id,$user_email,$start,$end);
    }
    else if($type == 'UpdateAuction')
    {
        $code = $requestData['code'] ?? null;
        $start = $requestData['start'] ?? null;
        $end = $requestData['end'] ?? null;
        $highest_bid = $requestData['highest_bid'] ?? null;
        $buyer = $requestData['buyer'] ?? null;

        $api->updateAuction($code,$start,$end,$highest_bid,$buyer);

    }
    else if($type == 'GetAuction'){
        $return = $requestData['return'] ?? null;
        if($return === "*")
        {
            //Get all auctions
            $date = $requestData['date'] ?? null;
            $api->getAllAuctions($date);
        }
        else{
            //GetAuction
            $code = $requestData['code'] ?? null;
            $api->getAuction($code);
        }
    }
    else if($type == 'GetAllAuctions'){
        //GetAllAuction
        $api->getAllAuctions();
    }
    else if($type == 'GetUserAuctions'){
        //GetAllAuction
        $user_id = $requestData['user_id'] ?? null;
        $api->getUserAuction($user_id);
    }
    else if($type == 'EndAuction'){
        //GetAllAuction
        $code = $requestData['code'] ?? null;
        $api->endAuction($code);
    }
    else if($type == 'updateBuyer'){
        //GetAllAuction
        $code = $requestData['code'] ?? null;
        $buyer = $requestData['buyer'] ?? null;
        $api->updateBuyer($code,$buyer);
    }
    else if($type == 'GetUser'){
        //GetAllAuction
        $user_id = $requestData['user_id'] ?? null;
        $api->getUser($user_id);
    }else if($type == 'GetProperty')
    {
        $property_id = $requestData['property_id'] ?? null;
        $api->getProperty($property_id);
    }
    else if($type == 'Login'){
        $email = $requestData['email'] ?? null;
        $password = $requestData['password'] ?? null;
        $api->loginImplement($email,$password);
    }
    else if($type=='Signup')
    {
        $email = $requestData['email'] ?? null;
        $password = $requestData['password'] ?? null;
        $username = $requestData['username'] ?? null;
        $api->signUp($email,$username,$password);
    }
    else{
        header('Content-Type: application/json');
        http_response_code(400);
        $timestamp = round(microtime(true) * 1000);
        $response = array(
            "status" => "Fail",
            "timestamp" => $timestamp,
            "data" => "Invalid Request Type"
        );
        echo json_encode($response, JSON_PRETTY_PRINT);
        die();
    }
}