<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Aperture Auctions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="js/public/css/auctionRoom.css">
</head>
<body>
<!--<div class="back-btn"><a href="index.php">Back</a></div>-->
<div class="back-btn"><a href="index.php">Back</a></div>
<div id="auction-room">
    <div class="heading"><h1>Auction Name</h1></div>
    <div class="heading"><h2>Current Bid: R300 000</h2></div>
    <div class="auction-container" id="auction-container">

        <div class="auction-image"></div>
        <div class="property-container">
            <div class="property-card">
                <div class="property-title">3 Bedroom Apartment London</div>
                <div class="property-location">London, England</div>
                <div class="property-details">
                    <div><div class="bed"></div>5</div>
                    <div><div class="bath"></div>2</div>
                    <div><div class="parking"></div>0</div>
                </div>

                <div class="property-description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed ut fringilla odio.
                    Nullam ut tortor vel justo scelerisque fermentum. Integer euismod justo vel metus condimentum feugiat.
                    Nunc tristique mi eu eros efficitur, nec ultricies nulla tristique. Maecenas vitae metus sed turpis mollis commodo.
                    Nulla facilisi. Vivamus dictum, ipsum ac feugiat posuere, sem velit tristique sem, nec placerat quam erat nec nulla.
                    Sed vel mi nec nisl feugiat tincidunt sit amet ut justo. Vivamus lobortis nisl vitae sapien suscipit convallis.
                    Ut ultricies quam vel quam congue, ac lacinia sapien venenatis. Sed tincidunt mi id massa fringilla, sit amet elementum
                    nisi condimentum. Sed pretium est non ligula laoreet, eu congue ligula vestibulum. Suspendisse potenti. Sed dictum mi vitae
                    risus elementum, eu pellentesque leo posuere. Vivamus at gravida libero. Sed in odio odio.
                </div>

                <div class="property-amenities">
                    <div><div class="pin"></div><p>Balcony</p></div>
                    <div><div class="pin"></div><p>Modern</p></div>
                    <div><div class="pin"></div><p>London central</p></div>
                    <div><div class="pin"></div><p>Great Views</p></div>
                </div>
            </div>

        </div>

    </div>

    <div class="bid-container">

        <form id="bid-form">
            <h2>Place a Bid</h2>
            <div>
                <label for="bid">Your Bid</label>
                <input type="number" name="bid" id="bid" min="300000" placeholder="300000">
            </div>
            <div><button class="submit-btn">Place Bid</button></div>
        </form>
    </div>
</div>

<div id="auction-code-container">
    <div id="auction-code-form">
        <h2>Auction Code</h2>
        <input type="text" name="auctionCode" id="auctionCode" placeholder="Auction Room Code">
        <button class="submit-btn" onclick="joinAuction()">Join Auction</button>
    </div>
</div>





<script src="js/public/script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectedAuction = JSON.parse(localStorage.getItem('selectedAuction'));
        if (selectedAuction)
        {
            viewCard(selectedAuction);
        }
    });

    //.onclick = function(){
    //        const sepectedProperty = property;
    //        localStorage.setItem('selectedProperty',JSON.stringify(sepectedProperty));
    //        window.location.href = 'view.php';
    //    };
</script>
</body>
</html>
