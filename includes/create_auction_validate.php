<?php
require_once '../config.php';

function createProperty($title,$price,$location,$bedroom,$bathroom,$parking,$amenities,$description,$image_url)
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

function createAuction(string $name,int $property_id,int $user_id,$start_time,$end_time)
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

    $code = generateAuctionCode();

    $start_time = $start_datetime->format('Y-m-d H:i:s');
    $end_time = $end_datetime->format('Y-m-d H:i:s');

    // Insert auction into 'auction' table with the property ID and status
    $query = "INSERT INTO auctions (auction_name, property_id,user_id ,start, end, state, auction_code) VALUES (?, ?, ?, ?, ?, ?,?)";
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
}

function generateAuctionCode(){
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

if($_SERVER["REQUEST_METHOD"] === "POST")
{
    //get the data from the form inputs
    $name = $_POST['auction-name'];
    $start_time = $_POST['start'];
    $end_time = $_POST['end'];
    $title = $_POST['property-title'];
    $price = $_POST['property-price'];
    $location = $_POST['property-location'];
    $bedroom = $_POST['property-bed'];
    $bathroom = $_POST['property-bath'];
    $parking = $_POST['property-parking'];
    $amenities = $_POST['property-amenities'];
    $description = $_POST['property-desc'];
    $image_url = $_POST['property-image'];
    $user_id= $_POST['user_id'];


    $property_id = createProperty($title,$price,$location,$bedroom,$bathroom,$parking,$amenities,$description,$image_url);
    createAuction($name,$property_id,$user_id,$start_time,$end_time);

    header("Location: ../index.php");
    die();
}
else{
    header("Location: ../createAuction.php");
    die();
}


