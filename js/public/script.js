//const io = require('socket.io-client');
const socket = io('http://localhost:2043'); // Connect to the server

socket.on('connect', ()=>{
    console.log(`Client connected: ${socket.id}`);
});

socket.on('update-bid',amount =>{
    console.log("Updated Bid: " + amount);
})

socket.on('auctionJoined',auctionObj=>{
    //chnage dom to match auctionObj then display auction

    //change auction name
    const name = document.getElementById("auctionName");
    name.textContent = auctionObj.name;

    //change current bid
    const bid=document.getElementById('bidAmount');
    bid.textContent = "Current Bid:  "+auctionObj.highestBid;

    //change image
    const image = document.getElementById('image');
    image.style.backgroundImage = `url('${auctionObj.imageUrl}')`;

    //property title
    const title = document.getElementById('property-title');
    title.textContent = auctionObj.property.title;

    //property location
    const location = document.getElementById('property-location');
    location.textContent = auctionObj.property.location;

    //details
    const bed = document.getElementById('bed');
    const bath = document.getElementById('bath');
    const parking = document.getElementById('parking');

    bed.textContent = "" + auctionObj.property.bedrooms;
    bath.textContent = "" + auctionObj.property.bathrooms;
    parking.textContent = "" + auctionObj.property.parking;

    const desc = document.getElementById('description');
    desc.textContent = auctionObj.property.description;

    const amenities = auctionObj.property.amenities.split(",").map(item => item.trim());
    const container = document.getElementById('amenities');

    amenities.forEach(amenity => {
        // Create the HTML structure for each amenity
        const amenityContainer = document.createElement('div');
        const pinIcon = document.createElement('div');
        pinIcon.classList.add('pin');
        const amenityText = document.createElement('p');
        amenityText.textContent = amenity;

        // Assemble the elements
        amenityContainer.appendChild(pinIcon);
        amenityContainer.appendChild(amenityText);

        // Add the amenity container to the amenities div
        container.appendChild(amenityContainer);
    });
});


function sendBid(amount)
{
    socket.emit('send-bid',amount);
}

function joinAuction()
{
    //Get Auction code then
    const auctionCode = document.getElementById("auctionCode").value;
    console.log(auctionId);
    //send the code to the server
    socket.emit('joinAuction',auctionCode);
    const form = document .getElementById("auction-code-container");
    form.style.display = "none";
}