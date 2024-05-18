<?php
// Include the configuration file
require_once 'config.php';
require_once 'config_session.php';

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
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    </head>
    <body>
        <div class="back-btn"><a href="index.php">Back</a></div>
        <div class="heading"><h1>Create Auction</h1></div>
        <div class="form-container">
            <form id="create-auc">

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

                <div class="button-element"><button type="button" id="submit">Create Auction</button></div>
            </form>
        </div>

        <script>
            $(document).ready(function() {
                $('#submit').click(function(event)
                {
                    event.preventDefault(); // Prevent default form submission
                    createAuction(); // Call the validation function
                });
            });

            function createAuction()
            {
                const auction_name = $('#auction-name').val();
                const start_time = $('#start').val();
                const end_time = $('#end').val();
                const property_title = $('#property-title').val();
                const property_price = parseInt($('#property-price').val());
                const property_location = $('#property-location').val();
                const property_bedroom = parseInt($('#property-bed').val());
                const property_bathroom = parseInt($('#property-bath').val());
                const property_parking = parseInt($('#property-parking').val());
                const property_amenities = $('#property-amenities').val();
                const property_description = $('#property-desc').val();
                const property_image = $('#property-image').val();
                const userEmail = sessionStorage.getItem('email');

                // Create data object to send
                const data = {
                    type:'CreateAuction',
                    propertyTitle:property_title,
                    propertyPrice:property_price,
                    propertyLocation: property_location,
                    propertyBed:property_bedroom,
                    propertyBath:property_bathroom,
                    propertyParking:property_parking,
                    propertyAmenities:property_amenities,
                    propertyDescription:property_description,
                    propertyImage:property_image,
                    auctionName:auction_name,
                    userEmail:userEmail,
                    start:start_time,
                    end:end_time
                }
                // Make AJAX request to your API
                $.ajax({
                    type: 'POST',
                    url: 'https://wheatley.cs.up.ac.za/u21528790/COS216/PA4/includes/auction_api.php',
                    data: JSON.stringify(data),
                    contentType: 'application/json',
                    beforeSend: function(xhr) {
                        // Set authorization header
                        xhr.setRequestHeader('Authorization', 'Basic ' + btoa('u21528790' + ':' + '345803Moo'));
                    },
                    success: function(response) {
                        console.log(response);
                        //take to the main page
                        window.location.href = "../index.php";
                    },
                    error: function(xhr, status, error) {
                        // Handle error response
                        console.error(xhr.responseText);
                    }
                });
            }
        </script>
    </body>
</html>
