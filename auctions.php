<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aperture Auctions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/auctionCards.css">
</head>
<body>
<!--<div class="back-btn"><a href="index.php">Back</a></div>-->
<div class="heading"><h1>Manage Auctions</h1></div>

<div class="auction-container" id="auction-container">
    <!--<div class="auction-card-container">
        <div><h3 class="auction-name">3 Bedroom Apartment Cape Town CBD</h3></div>
        <div><p><span class="auction-date">Start:  </span><?php /*$current_datetime = new DateTime(); echo $current_datetime->format('Y-m-d H:i:s'); */?></p></div>
        <div><p><span class="auction-date">End:   </span><?php /*$current_datetime = new DateTime(); echo $current_datetime->format('Y-m-d H:i:s'); */?> </p></div>
        <div><p><span class="auction-date">Status:   </span>Ongoing</p></div>
        <div><p><span class="auction-date">Highest Bid:   </span>R300 000</p></div>

        <div><button class="update-btn" onclick="updateAuctionForm(<?php /*echo 12; */?>)">Update</button></div>
    </div>-->



    <!--<div class="auction-card-container">
        <form action="#" class="update-auction-form">
            <div><h3 class="auction-name">3 Bedroom Apartment Cape Town CBD</h3></div>
            <div class="form-element"><label for="start">Start Time</label></div>
            <div class="form-element"><input type="datetime-local" name="start" id="start" value="<?php /*$current_datetime = new DateTime(); echo $current_datetime->format('Y-m-d H:i:s'); */?>"></div>

            <div class="form-element"><label for="end">End Time</label></div>
            <div class="form-element"><input type="datetime-local" name="end" id="end" value="<?php /*$current_datetime = new DateTime(); echo $current_datetime->format('Y-m-d H:i:s'); */?>"></div>

            <input type="hidden" name="user_id" id="user_id" value="<?php /* echo $_SESSION['user_id']*/?>">

            <div><button class="end-btn" onclick="updateAuctionForm(<?php /*echo 12; */?>)">End Auction</button></div>
            <div><button class="end-btn" onclick="updateAuctionForm(<?php /*echo 12; */?>)">Save Changes</button></div>
        </form>
    </div>-->
</div>


<script src="js/auctionCards.js">
</script>

<script>
    document.addEventListener('DOMContentLoaded',function()
    {
        //fetch the data
        console.log("Calling..........")
        fetchAuctionData(<?php  echo $_SESSION['user_id']?>);
    })
</script>
</body>
</html>