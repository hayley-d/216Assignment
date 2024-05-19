const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const readline = require('readline');
const ejs = require('ejs');
const path = require('path');
const axios = require('axios');
require('dotenv').config();



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
    let auctionUsers = {}; // Store users currently bidding in each auction
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
                    Object.keys(auctionUsers).forEach(auctionID => {
                        auctionUsers[auctionID] = auctionUsers[auctionID].filter(user => user !== username);
                    });
                    delete usernameSocketMap[username];
                }
            });
        });

        socket.on('MakeBid',async (data,currentDate)=>{
            currentDate = new Date(currentDate).getTime();
            console.log(data);
            try {
                const usernameServer = Object.keys(usernameSocketMap).find(key => usernameSocketMap[key] === socket.id);
                if (!usernameServer)
                {
                    socket.emit('auctionBidError', 'Username not registered');
                    return;
                }
                // Username and password for authorization
                const username = process.env.USERNAME2;
                const password = process.env.PASSWORD;

                // Encode credentials
                const basicAuth = Buffer.from(`${username}:${password}`).toString('base64');

                // Make the update API call using Axios
                const response = await axios.post(
                    'https://wheatley.cs.up.ac.za/u21528790/COS216/PA4/includes/auction_api.php',
                    data,
                    {
                        headers: {
                            'Authorization': `Basic ${basicAuth}`,
                            'Content-Type': 'application/json'
                        }
                    }
                );



                console.log(`Bid Updated`);

                // Make another API call to get the updated auction details
                const getResponse = await axios.post(
                    'https://wheatley.cs.up.ac.za/u21528790/COS216/PA4/includes/auction_api.php',
                    {
                        type: 'GetAuction',
                        code: data['code']
                    },
                    {
                        headers: {
                            'Authorization': `Basic ${basicAuth}`,
                            'Content-Type': 'application/json'
                        }
                    }
                );

                const updatedAuctionData = getResponse.data;

                if(updatedAuctionData.data['auction_code'] === null)
                {
                    socket.emit('auctionBidError', 'Unable to make bid');

                }
                else{
                    const auctionEndTime = new Date(updatedAuctionData.data['end']).getTime();
                    // Check if the auction has ended
                    if (currentDate >= auctionEndTime)
                    {
                        const response = await axios.post(
                            'https://wheatley.cs.up.ac.za/u21528790/COS216/PA4/includes/auction_api.php',
                            {
                                type:'EndAuction',
                                code:data['code'],
                                date:updatedAuctionData.data['end']
                            },
                            {
                                headers: {
                                    'Authorization': `Basic ${basicAuth}`,
                                    'Content-Type': 'application/json'
                                }
                            }
                        );
                        socket.emit('AuctionEnd', updatedAuctionData);
                        return;
                    }
                    console.log(`Updated Auction Data Retrieved:`, updatedAuctionData);

                    // Notify the client who made the bid about success
                    socket.emit('UpdateBid', updatedAuctionData);

                    // Broadcast the updated auction details to all users in the auction
                    const auctionID = data['code'];
                    if (auctionUsers[auctionID]) {
                        auctionUsers[auctionID].forEach(username => {
                            const userSocketId = usernameSocketMap[username];
                            if (userSocketId) {
                                io.to(userSocketId).emit('UpdateBid', updatedAuctionData);
                            }
                        });
                    }
                }
            } catch (error)
            {
                console.error('Error updating auction:', error.message);
                if (error.response && error.response.data) {
                    console.error('Server error message:', error.response.data.message);
                }
                // Notify the client about the error
                socket.emit('auctionBidError', 'Failed to make bid');
            }
        });

        socket.on('registerUsername', (username) => {
            // Store the username and socket ID mapping
            usernameSocketMap[username] = socket.id;
            console.log(`Username registered: ${username}`);
        });

        //KILL Command
        socket.on('KILL', (username) => {
            // Close the socket connection for the given username
            const socketId = usernameSocketMap[username];
            if (socketId) {
                io.to(socketId).emit('forceDisconnect');
                console.log(`Closed connection for user: ${username}`);
            } else {
                console.log(`User ${username} not found`);
            }
        });

        socket.on('LIST', () => {
            // Send the list of usernames and socket IDs to the client
            const userList = Object.entries(usernameSocketMap).map(([username, socketId]) => {
                return { username, socketId };
            });
            socket.emit('userList', userList);
        });

        socket.on('QUIT', () => {
            console.log('Server going offline...');
            // Notify all clients that the server is going offline
            io.emit('serverShutdown', 'Server is going offline now.');

            // Wait to ensure message delivery
            setTimeout(() => {
                // Disconnect all clients
                io.sockets.sockets.forEach(s => s.disconnect(true));
                // Close the server
                server.close(() => {
                    console.log('Server has been shut down.');
                });
            }, 1000);
        });

        socket.on('AUCTIONS', () => {
            const auctionList = Object.entries(auctionData).map(([auctionID, auctionDetails]) => {
                return {
                    AuctionID: auctionID,
                    AuctionName: auctionDetails['auction_name'],
                    UsersBidding: auctionUsers[auctionID] || []
                };
            });
            socket.emit('auctionList', auctionList);
        });

        socket.on('joinAuction', async (code) => {
            try {
                const usernameServer = Object.keys(usernameSocketMap).find(key => usernameSocketMap[key] === socket.id);
                if (!usernameServer)
                {
                    socket.emit('auctionJoinError', 'Username not registered');
                    return;
                }

                // Username and password for authorization
                const username = process.env.USERNAME2;
                const password = process.env.PASSWORD;

                // Encode credentials
                const basicAuth = Buffer.from(`${username}:${password}`).toString('base64');

                // Make the API call using Axios
                console.log("Code: ",code)
                const response = await axios.post(
                    'https://wheatley.cs.up.ac.za/u21528790/COS216/PA4/includes/auction_api.php',
                    {
                        type: 'GetAuction',
                        code: code
                    },
                    {
                        headers: {
                            'Authorization': `Basic ${basicAuth}`,
                            'Content-Type': 'application/json'
                        }
                    }
                );

                const auctionData = response.data;
                console.log(`Auction data retrieved:`,auctionData);

                //Check if the auction has started if it has then continue if not then deny access
                if(auctionData.data['status'] !== 'ongoing')
                {
                    socket.emit('auctionNotJoined', auctionData.data['status']);
                }
                else{
                    // Store the retrieved auction data globally
                    this.auctionData = auctionData;

                    if (!auctionUsers[code]) {
                        auctionUsers[code] = [];
                    }

                    auctionUsers[code].push(usernameServer);
                    console.log(`User ${usernameServer} joined auction ${code}`);

                    // Notify the client about successful join
                    socket.emit('auctionJoined', auctionData);
                }
            } catch (error) {
                console.error('Error joining auction:', error.message);
                socket.emit('auctionJoinError', 'Error joining auction');
                if (error.response && error.response.data) {
                    console.error('Server error message:', error.response.data.message);
                }
            }
        });

        socket.on('GetAllAuctions', async (date) => {
            try {
                // Username and password for authorization
                const username = process.env.USERNAME2;
                const password = process.env.PASSWORD;

                // Encode credentials
                const basicAuth = Buffer.from(`${username}:${password}`).toString('base64');

                // Make the API call using Axios
                const response = await axios.post(
                    'https://wheatley.cs.up.ac.za/u21528790/COS216/PA4/includes/auction_api.php',
                    {
                        type: 'GetAuction',
                        return: '*',
                        currentDate:date
                    },
                    {
                        headers: {
                            'Authorization': `Basic ${basicAuth}`,
                            'Content-Type': 'application/json'
                        }
                    }
                );

                const auctionData = response.data;
                console.log(`Auction data retrieved:`,auctionData);

                // Store the retrieved auction data globally
                this.auctionData = auctionData;

                //Return all the ongoing auctions
                socket.emit('AllAuctions', auctionData);
            } catch (error) {
                console.error('Error joining auction:', error.message);
                socket.emit('auctionFetchError', 'Error Fetching auctions');
                if (error.response && error.response.data) {
                    console.error('Server error message:', error.response.data.message);
                }
            }
        });

        socket.on('createAuction', async (data) => {
            try {
                // Username and password for authorization
                const username = process.env.USERNAME2;
                const password = process.env.PASSWORD;

                // Encode credentials
                const basicAuth = Buffer.from(`${username}:${password}`).toString('base64');

                //Add Auction code
                data['code'] = generateAuctionID();
                data['type'] = 'CreateAuction';

                console.log(data);

                // Make the API call using Axios
                const response = await axios.post(
                    'https://wheatley.cs.up.ac.za/u21528790/COS216/PA4/includes/auction_api.php',
                    data,
                    {
                        headers: {
                            'Authorization': `Basic ${basicAuth}`,
                            'Content-Type': 'application/json'
                        }
                    }
                );


                console.log(`Auction Created`);

                // Notify the client about success
                socket.emit('auctionCreated', auctionData);
            } catch (error) {
                console.error('Error joining auction:', error.message);
                socket.emit('auctionCreateError', 'Error Creating auction');
                if (error.response && error.response.data) {
                    console.error('Server error message:', error.response.data.message);
                }
            }
        });

        socket.on('endAuction', async (data) =>
        {
            try{
                // Username and password for authorization
                const username = process.env.USERNAME2;
                const password = process.env.PASSWORD;

                // Encode credentials
                const basicAuth = Buffer.from(`${username}:${password}`).toString('base64');

                // Make the update API call using Axios
                const response = await axios.post(
                    'https://wheatley.cs.up.ac.za/u21528790/COS216/PA4/includes/auction_api.php',
                    data,
                    {
                        headers: {
                            'Authorization': `Basic ${basicAuth}`,
                            'Content-Type': 'application/json'
                        }
                    }
                );
                socket.emit('auctionEnded', 'Auctioneer has ended the auction');
            }
            catch (error)
            {
                console.error('Error updating auction:', error.message);
                if (error.response && error.response.data) {
                    console.error('Server error message:', error.response.data.message);
                }
                // Notify the client about the error
                socket.emit('auctionBidError', 'Failed to end auction');
            }
        });

        socket.on('send', (data) => {
            io.emit('chat', data);
        });
    });

    // Utility function to generate unique AuctionID
    function generateAuctionID() {
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let randomString = '';
        for (let i = 0; i < 10; i++) {
            const randomIndex = Math.floor(Math.random() * characters.length);
            randomString += characters[randomIndex];
        }
        return randomString;
    }



    // Periodic cleanup of disconnected sockets
    setInterval(() => {
        console.log('Running cleanup for disconnected sockets...');
        io.sockets.sockets.forEach(socket => {
            if (socket.disconnected) {
                console.log(`Cleaning up disconnected socket: ${socket.id}`);
                Object.keys(usernameSocketMap).forEach(username => {
                    if (usernameSocketMap[username] === socket.id) {
                        delete usernameSocketMap[username];
                    }
                });
            }
        });
    }, 30000); // Run cleanup every 30 seconds
}

askForPort();