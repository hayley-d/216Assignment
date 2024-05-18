const socket = new io('http://localhost:2043');

socket.on('connect', ()=>{
    console.log(`Client connected: ${socket.id}`);
    socket.emit('registerUsername',sessionStorage.getItem('email'));
});

socket.on('UpdateBid',(data) =>
{
    console.log("Updated Auction: ",data.data);
    const auctionData = data.data;

    //Update amount
    $('#bidAmount').text(`Current Bid: ${auctionData['highest_bid']}`);

    $('#bid').attr('min', auctionData["highest_bid"]);

    sessionStorage.setItem('auction',JSON.stringify(auctionData));
})

socket.on('auctionJoined', (data) => {

    console.log("Auction Data: ",data.data);

    const auctionData = data.data;

   //Change auction Name
    $('#auctionName').text(auctionData['auction_name']);

    //Set the current bid
    $('#bidAmount').text(`Current Bid: ${auctionData['highest_bid']}`);

    //Set image background
    $('#image').css('background-image', `url("${auctionData['property_image']}")`);

    //Set property Title
    $('#property-title').text(auctionData['property_title']);

    //Set property location
    $('#property-location').text(auctionData['property_location'])

    //Set information
    $('#bed').text(auctionData['property_bedrooms']);
    $('#bath').text(auctionData['property_bathrooms']);
    $('#parking').text(auctionData['property_parking']);

    //Set description
    $('#description').text(auctionData['property_description'])

    //loop through amentiteis
    const amenities = auctionData['property_amenities'].split(",");
    amenities.forEach(amenity =>{
        // Create a new div with the required structure
        const amenityDiv = $('<div></div>');
        const pinDiv = $('<div class="pin"></div>');
        const amenityText = $('<p></p>').text(amenity);

        // Append the pin div and the p element to the amenity div
        amenityDiv.append(pinDiv);
        amenityDiv.append(amenityText);

        // Append the amenity div to the container
        $('#amenities').append(amenityDiv);
    });

    $('#bid').attr('min', auctionData["highest_bid"]);

    sessionStorage.setItem('auction',JSON.stringify(auctionData));

    hideCode();
    showAuction();
});

socket.on('AllAuctions', (data) => {
    console.log("Auction Data: ",data.data);

    const auctionData = data.data;

    populateAuctions(auctionData);
});

$(document).ready(function(){
    // Create a new Date object, which represents the current date and time
    var currentDate = new Date();

// Get the current year, month, day, hours, minutes, and seconds
    var year = currentDate.getFullYear();
    var month = ('0' + (currentDate.getMonth() + 1)).slice(-2); // Add leading zero if needed
    var day = ('0' + currentDate.getDate()).slice(-2); // Add leading zero if needed
    var hours = ('0' + currentDate.getHours()).slice(-2); // Add leading zero if needed
    var minutes = ('0' + currentDate.getMinutes()).slice(-2); // Add leading zero if needed
    var seconds = ('0' + currentDate.getSeconds()).slice(-2); // Add leading zero if needed
    var formattedDateTime = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':' + seconds;
    console.log(formattedDateTime);
    socket.emit('GetAllAuctions',formattedDateTime);

    //bid-form
    $('#bidButton').click(function(event)
    {
        event.preventDefault(); // Prevent default form submission
        makeBid(); // Call the validation function
    });
});


//Sends the auction code to the server
function sendCode()
{
    //Get the code from the input
    const code = $('#auctionCode').val();
    //Send the code to the server
    socket.emit('joinAuction', code);

}

function sendCode2(code)
{
    //Send the code to the server
    socket.emit('joinAuction', code);
}

function showAuction(){
    $('#auction-room').show();
}

function hideCode(){
    $('#auction-code-container').hide();
    $('#ongoing-auctions-container').hide();
}

function populateAuctions(auctions)
{
    auctions.forEach(function(auction){
        // Create a new auction card
        var auctionCard = $('<div class="auction-card"></div>');

        // Create card content
        var cardContent = $('<div class="card-content"></div>');
        cardContent.append('<h5>' + auction["auction_name"] + '</h5>');
        cardContent.append('<p>Current Bid: R' + auction["highest_bid"] + '</p>');
        cardContent.append('<p>Auction code: ' + auction["auction_code"] + '</p>');

        // Create join auction button
        var joinBtn = $('<button class="join-auction-btn">Join Auction</button>');
        joinBtn.click(function() {
            sendCode2(auction["auction_code"]);
        });

        // Append content and button to the card
        cardContent.append(joinBtn);
        auctionCard.append(cardContent);

        // Append the card to the container
        $('#ongoing-auctions-container').append(auctionCard);
    })
}

function makeBid()
{
    //Get the bid amount
    const amount = parseInt($('#bid').val());

    const currentAuction = JSON.parse(sessionStorage.getItem('auction'));

    if(amount < parseInt(currentAuction['highest_bid'])){
        //bid not large enough
        $('#bid').val('');
        alert("Bid not large enough");
    }
    else{
        //Make object
        const obj = {
            type:'UpdateAuction',
            code:currentAuction['auction_code'],
            start:currentAuction['start'],
            end:currentAuction['end'],
            highest_bid:amount,
            buyer:sessionStorage.getItem('email')
        }
        console.log(obj);
        socket.emit('MakeBid', obj);
    }
}

