<?php
require_once '../config.php';

function updateAuction($code,$start,$end)
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
            "message" => $code,
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
    $status = ($current_time >= $start_time && $current_time < $end_time) ? "ongoing" : ($current_time < $end_time ? "waiting" : "done");




    // Update the auction in the database
    // Update the auction in the database
    $update_query = "UPDATE auctions SET status = ?, start = ?, end = ? WHERE auction_code = ?";
    $stmt = $db->prepare($update_query);
    $stmt->bind_param("ssss", $status, $start, $end, $code);
    $stmt->execute();
}

if($_SERVER["REQUEST_METHOD"] === "POST")
{
    $start = $_POST['start'];
    $end = $_POST['end'];
    $code = $_POST['code'];

    updateAuction($code,$start,$end);
    header("Location: ../index.php");
    die();
}
else{
    header("Location: ../createAuction.php");
    die();
}
