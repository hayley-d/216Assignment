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
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/footer.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

</head>
<body>
<div class="header">
    <header>
        <div class="logo"></div>
        <div id="user-logged-out">
            <div><a></a></div>
            <div><a></a></div>
            <div id="user-profile">
                <div><a href="./includes/login.php">Login</a></div>
            </div>
        </div>
        <div id="user-logged-in">
            <div><a onclick="auctionRoom()">Join Auction</a></div>
            <div><a href="./createAuction.php">Create Auction</a></div>
            <div><a onclick="logout()">Logout</a></div>
        </div>
    </header>

</div>


<section id="landing">
    <div><h3>Discover Your Dream Home at Aperture Auctions</h3></div>

</section>

<section id="hosted-container">



</section>
<script>
 $(document).ready(function(){
     $('#user-logged-in').hide();

     if(sessionStorage.getItem('email') !== null)
     {
         //User is logged in
         $('#user-logged-in').show();
         $('#user-logged-out').hide();
     }
 });

 function logout(){
     sessionStorage.removeItem('email');
     window.location.href = "./includes/login.php";
 }

 function auctionRoom(){
     ///front_end/auction.php
     window.location.href = "./front_end/index.html";
 }

</script>


</body>
</html>