<?php
// Include the configuration file
require_once 'config.php';
require_once 'config_session.php';
require_once './includes/login_view.php';
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Aperture Auctions</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="css/createAuction.css">
    </head>
    <body>
        <div class="back-btn"><a href="index.php">Back</a></div>
        <div class="heading"><h1>Create Auction</h1></div>
        <div class="form-container">
            <form id="create-auc" method="POST" action="./includes/create_auction_validate.php">

                <div class="form-element"><label for="auction-name">Auction Name</label></div>
                <div class="form-element"><input type="text" name="auction-name" id="auction-name" required></div>

                <div class="form-element"><label for="start">Start Time</label></div>
                <div class="form-element"><input type="datetime-local" name="start" id="start" required></div>

                <div class="form-element"><label for="end">End Time</label></div>
                <div class="form-element"><input type="datetime-local" name="end" id="end" required></div>

                <div id="prop-heading"><h4>Property Details</h4></div>
                <div class="form-element"><label for="property-title">Title</label></div>
                <div class="form-element"><input type="text" name="property-title" id="property-title" required></div>

                <div class="form-element"><label for="property-price">Price</label></div>
                <div class="form-element"><input type="number" name="property-price" id="property-price" required></div>

                <div class="form-element"><label for="property-location">Location</label></div>
                <div class="form-element"><input type="text" name="property-location" id="property-location" required></div>

                <div class="form-element"><label for="property-bed">Bedroom Number</label></div>
                <div class="form-element"><input type="number" name="property-bed" id="property-bed" required></div>

                <div class="form-element"><label for="property-bath">Bathroom Number</label></div>
                <div class="form-element"><input type="number" name="property-bath" id="property-bath" required></div>

                <div class="form-element"><label for="property-parking">Parking Space Number</label></div>
                <div class="form-element"><input type="number" name="property-parking" id="property-parking" required></div>

                <div class="form-element"><label for="property-amenities">Amenities</label></div>
                <div class="form-element"><textarea name="property-amenities" id="property-amenities" rows="10" cols="50" required></textarea></div>

                <div class="form-element"><label for="property-desc">Description</label></div>
                <div class="form-element"><textarea name="property-desc" id="property-desc" rows="10" cols="50" required></textarea></div>

                <div class="form-element"><label for="property-image">Property Image URL</label></div>
                <div class="form-element"><input type="text" name="property-image" id="property-image" required></div>

                <input type="hidden" name="user_id" id="user_id" value="<?php  echo $_SESSION['user_id']?>">

                <div class="button-element"><button id="submit" onclick="validateInformation()">Create Auction</button></div>
            </form>
        </div>

        <script>
            function validateInformation()
            {
                document.getElementById('create-auc').submit();
            }
        </script>
    </body>
</html>
