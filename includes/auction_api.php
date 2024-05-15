<?php
require_once '../config.php';

//get the post data
$json = file_get_contents('php://input');
// Converts it into a PHP object
$requestData = json_decode($json,true);

class auctionApi
{
    public function createAuction(string $name,int $property_id,int $user_id,$start_time,$end_time)
    {
        global $db;
        if($name == null || $property_id == null || $user_id == null || $start_time == null || $end_time == null)
        {

        }
        $name = filter_var($name, FILTER_SANITIZE_STRING);
        $user_id = filter_var($user_id, FILTER_SANITIZE_NUMBER_INT);

        // Convert start_time and end_time to DateTime objects for comparison
        $start_datetime = new DateTime($start_time);
        $end_datetime = new DateTime($end_time);

        // Check if start time is before end time
        if($start_datetime >= $end_datetime)
        {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Start time is before the end time",
                "data" => []
            ]);
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
            // Check for errors in prepare
            echo "Prepare failed: (" . $db->errno . ") " . $db->error;
            die();
        }
        $stmt->bind_param("siissss", $name, $property_id,$user_id, $start_time, $end_time, $status,$code);
        $result = $stmt->execute();

        // Check if the insertion was successful
        if (!$result) {
            // Check for errors in execute
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            die();
        }
        if ($stmt->affected_rows > 0) {
            $this->response(true, "Auction created successfully");
        } else {
            $this->response(false, "Failed to create auction");
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

    public function getAllAuctions()
    {
        global $db;
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

        $this->response(true,$results);
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
        // Sanitize and validate parameters
        $propertyId = filter_var($propertyId, FILTER_SANITIZE_NUMBER_INT);
        global $db;
        $query = "SELECT * FROM properties WHERE property_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("s", $propertyId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $results = $result->fetch_all(MYSQLI_ASSOC);
            return $results;
        } else {
            $this->response(false, "Property not found");
            return null;
        }
    }

    public function getUser( $userId)
    {
        // Sanitize and validate parameters
        $userId = filter_var($userId, FILTER_SANITIZE_NUMBER_INT);
        global $db;
        $query = "SELECT * FROM users WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $results = $result->fetch_all(MYSQLI_ASSOC);
            $this->response(true,$results);
        } else {
            $this->response(false, "User not found");
            return null;
        }
    }

    public function createProperty($title,$price,$location,$bedroom,$bathroom,$parking,$amenities,$description,$image_url)
    {
        global $db;
        $title = filter_var($title, FILTER_SANITIZE_STRING);
        $location = filter_var($location, FILTER_SANITIZE_STRING);
        $price = filter_var($price, FILTER_SANITIZE_NUMBER_INT);
        $bedroom = filter_var($bedroom, FILTER_SANITIZE_NUMBER_INT);
        $bathroom = filter_var($bathroom, FILTER_SANITIZE_NUMBER_INT);
        $parking = filter_var($parking, FILTER_SANITIZE_NUMBER_INT);
        $amenities = filter_var($amenities, FILTER_SANITIZE_STRING);
        $description = filter_var($description, FILTER_SANITIZE_STRING);
        $image_url = filter_var($image_url, FILTER_SANITIZE_URL);

        $query = "INSERT INTO properties (title, price, location, bedroom, bathroom, parking, amenities, description,image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)";
        $stmt = $db->prepare($query);
        if (!$stmt) {
            // Check for errors in prepare
            echo "Prepare failed: (" . $db->errno . ") " . $db->error;
            die();
        }
        $stmt->bind_param("sdsdsssss",
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
            // Check for errors in execute
            echo "Execute failed: (" . $stmt->errno . ") " . $stmt->error;
            die();
        }
        $property_id = $stmt->insert_id;
        return $property_id;
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
        $title = $requestData['property-title'] ?? null;
        $price = $requestData['property-price'] ?? null;
        $location = $requestData['property-location'] ?? null;
        $bedroom = $requestData['property-bed'] ?? null;
        $bathroom= $requestData['property-bath'] ?? null;
        $parking= $requestData['property-parking'] ?? null;
        $amenities = $requestData['property-amenities'] ?? null;
        $description = $requestData['property-desc'] ?? null;
        $image_url = $requestData['property-image'] ?? null;
        /*Create property*/
        $property_id = $api->createProperty($title,$price,$location,$bedroom,$bathroom,$parking,$amenities,$description,$image_url);

        $name = $requestData['auction-name'] ?? null;
        $user_id = $requestData['user_id'] ?? null;
        $start = $requestData['start'] ?? null;
        $end = $requestData['end'] ?? null;
        /*create auction*/
        $api->createAuction($name,$property_id,$user_id,$start,$end);
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
        //GetAuction
        $code = $requestData['code'] ?? null;
        $api->getAuction($code);
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