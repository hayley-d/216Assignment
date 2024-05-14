const socket = new io('http://localhost:2043');

socket.on('chat', (data) => {
    // console.log(data);
    const chat = document.getElementById('chat-messages');
    const p = document.createElement('p');
    p.innerHTML = data;
    chat.appendChild(p);
});

function sendMessage(){
    const message = document.getElementById('message').value;
    socket.emit('send', message);
    document.getElementById('message').value = '';
}