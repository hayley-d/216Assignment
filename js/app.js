const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const readline = require('readline');
const ejs = require('ejs');
const path = require('path');
const axios = require('axios');



const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
    cors:{
        origin:['*'],
    },
});


app.use(express.static('../front_end'));

// Set the view engine to EJS
app.set('views', path.join('views'));
app.set('view engine', 'ejs');

let data={
    name:"Hayley"
}

app.use((err, req, res, next) => {
    console.error(err.stack);
    res.status(500).send('Internal Server Error');
});

app.get('/', (req, res) => {
    res.render('auctionRoom', data);
});

// API endpoint to fetch data from PHP API
app.post('/api', async (req, res) => {
    try {
        const { type, code } = req.body;
        if (!type || !code) {
            throw new Error('Invalid request. Missing parameters.');
        }

        // Make API call to PHP API with parameters
        const response = await axios.post('http://localhost:80/api.php', { type, code });
        const data = response.data;

        // Render EJS template with data
        res.render('auctionRoom', data);
    } catch (error) {
        console.error('Error fetching data from API:', error);
        res.status(500).send('Internal Server Error');
    }
});


const rl = readline.createInterface({
    input: process.stdin,
    output: process.stdout
});

function askForPort() {
    rl.question('Enter a port number between 1024 and 49151: ', (port) => {
        port = parseInt(port);

        if (isNaN(port) || port < 1024 || port > 49151 || isReservedPort(port)) {
            console.error('Invalid port number. Port must be between 1024 and 49151 and not reserved.');
            askForPort(); // Ask again if not correct
        } else {
            startServer(port);
        }
    });
}

function isReservedPort(port) {
    const reservedPorts = [20, 21, 22, 23, 25, 53, 80, 443, 3306];
    return reservedPorts.includes(port);
}



function startServer(port) {
    let usernameSocketMap = {};
    let auctionData = {};
    server.listen(port, () => {
        console.log(`Server listening on port ${port}`);
    });

    /*runs every time the client connects to the server*/
    io.on('connection', (socket) => {
        /*Each client gets a unique ID*/
        console.log(`Client connected: ${socket.id}`);

        socket.on('disconnect', () => {
            console.log(`Client disconnected: ${socket.id}`);
            // Remove the username and socket ID mapping when a client disconnects
            Object.keys(usernameSocketMap).forEach(username => {
                if (usernameSocketMap[username] === socket.id) {
                    delete usernameSocketMap[username];
                }
            });
        });

        socket.on('send-bid',(amount)=>{
            console.log(amount);
            io.emit('update-bid',amount);
        })

        socket.on('registerUsername', (username) => {
            // Store the username and socket ID mapping
            usernameSocketMap[username] = socket.id;
            console.log(`Username registered: ${username}`);
        });

        //KILL Command
        socket.on('kill', (username) => {
            // Close the socket connection for the given username
            const socketId = usernameSocketMap[username];
            if (socketId) {
                io.to(socketId).emit('forceDisconnect');
                console.log(`Closed connection for user: ${username}`);
            } else {
                console.log(`User ${username} not found`);
            }
        });

        socket.on('list', () => {
            // Send the list of usernames and socket IDs to the client
            const userList = Object.entries(usernameSocketMap).map(([username, socketId]) => {
                return { username, socketId };
            });
            socket.emit('userList', userList);
        });

        socket.on('joinAuction', async (code) => {
            try {

                // Basic code validation (replace with your specific requirements)
                if (!code || code.trim().length === 0) {
                    throw new Error('Invalid auction code: empty input');
                }

                // Make the API call using Axios
                const response = await axios.post(
                    '/api.php',
                    {
                        type: 'GetAuction',
                        code: code
                    }
                );

                const auctionData = response.data;
                console.log(`Auction data retrieved: ${JSON.stringify(auctionData)}`);

                if (!auctionData || !auctionData.name || !auctionData['highest_bid']) {
                    throw new Error('Invalid auction data received from API');
                }

                console.log(`Auction data retrieved: ${JSON.stringify(auctionData)}`);

                // Store the retrieved auction data globally
                this.auctionData = auctionData;

                // Notify the client about successful join
                socket.emit('auctionJoined', auctionData);
            } catch (error) {
                console.error('Error joining auction:', error.message);
                if (error.response && error.response.data) {
                    console.error('Server error message:', error.response.data.message);
                }
            }
        });

        socket.on('send', (data) => {
            io.emit('chat', data);
        });

        // Emit the io instance to the client
        //socket.emit('connect-with-io', io);
    });
}

askForPort();