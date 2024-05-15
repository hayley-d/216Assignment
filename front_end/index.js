const socket = new io('http://localhost:2043');

socket.on('connect', ()=>{
    console.log(`Client connected: ${socket.id}`);
});

socket.on('update-bid',amount =>{
    console.log("Updated Bid: " + amount);
})

socket.on('auctionJoined', (data) => {
    // console.log(data);
    console.log(data);
});


//Sends the auction code to the server
function sendCode()
{
    //Get the code from the input
    const code = $('#auctionCode').val();
    //Send the code to the server
    socket.emit('joinAuction', code);
    //Hide the code area
    ('#auction-code-container').hide();
}

function showAuction(){
    $('#auction-room').show();
}
