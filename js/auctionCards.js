
let auctions = [];

function createAuctionCard(auctionName,startDate,endDate,status,highestBid,auctionId)
{
    const container = document.createElement('div');
    container.id = auctionId;
    container.classList.add('auction-card-container');

    const nameContainer = document.createElement('div');
    const nameElement = document.createElement('h3');
    nameElement.classList.add('auction-name');
    nameElement.textContent = auctionName;
    nameContainer.appendChild(nameElement);
    container.appendChild(nameContainer);

    const startContainer = document.createElement('div');
    const startElement = document.createElement('p');
    startElement.innerHTML = `<span class="auction-date">Start:  </span>${startDate}`;
    startContainer.appendChild(startElement);
    container.appendChild(startContainer);

    const endContainer = document.createElement('div');
    const endElement = document.createElement('p');
    endElement.innerHTML = `<span class="auction-date">End:  </span>${endDate}`;
    endContainer.appendChild(endElement);
    container.appendChild(endContainer);

    const statusContainer = document.createElement('div');
    const statusElement = document.createElement('p');
    statusElement.innerHTML = `<span class="auction-date">Status:  </span>${status}`;
    statusContainer.appendChild(statusElement);
    container.appendChild(statusContainer);

    const bidContainer = document.createElement('div');
    const bidElement = document.createElement('p');
    bidElement.innerHTML = `<span class="auction-date">Highest Bid:  </span>${highestBid}`;
    bidContainer.appendChild(bidElement);
    container.appendChild(bidContainer);

    const updateButtonContainer = document.createElement('div');
    const updateElement = document.createElement('button');
    updateElement.classList.add('update-btn');
    updateElement.textContent = "Update";
    updateElement.addEventListener('click', function() {
        updateAuctionForm(auctionId);
    });
    updateButtonContainer.appendChild(updateElement);
    container.appendChild(updateButtonContainer);
    document.getElementById('auction-container').appendChild(container);
}

function removeAuctionCard(auctionName,startDate,endDate,status,highestBid,auctionId,code)
{
    let container = document.getElementById(auctionId);
    if (container) {
        while (container.firstChild) {
            container.removeChild(container.firstChild);
        }
    }

    //add form elements
    const form = document.createElement('form');
    form.setAttribute('action','./includes/update_auction_validate.php');
    form.classList.add('update-auction-form');
    form.id = "update-auction-form";
    form.method = 'POST';

    const nameContainer = document.createElement('div');
    const nameElement = document.createElement('h3');
    nameElement.classList.add('auction-name');
    nameElement.textContent = auctionName;
    nameContainer.appendChild(nameElement);
    form.appendChild(nameContainer);

    const startLabel = document.createElement('div');
    startLabel.classList.add('form-element');
    startLabel.innerHTML = `<label for="start">Start Time</label>`;
    form.appendChild(startLabel);

    const startInput = document.createElement('div');
    startInput.classList.add('form-element');
    startInput.innerHTML = `<input type="datetime-local" name="start" id="start" value="${startDate}">`;
    form.appendChild(startInput);

    const endLabel = document.createElement('div');
    endLabel.classList.add('form-element');
    endLabel.innerHTML = `<label for="end">End Time</label>`;
    form.appendChild(endLabel);

    const endInput = document.createElement('div');
    endInput.classList.add('form-element');
    endInput.innerHTML = `<input type="datetime-local" name="end" id="end" value="${endDate}">`;
    form.appendChild(endInput);

    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = "code";
    hiddenInput.id = "code";
    hiddenInput.value = code;
    form.appendChild(hiddenInput);


    const endButton = document.createElement('div');
    endButton.innerHTML = `<button class="end-btn" onclick="endAuction(${auctionId})">End Auction</button>`;
    form.appendChild(endButton);

    const saveButton = document.createElement('div');
    saveButton.innerHTML = `<button class="end-btn" onclick="updateAuction()">Save Changes</button>`;
    form.appendChild(saveButton);

    container.appendChild(form);
}

function updateAuctionForm(auction_id)
{
    console.log(auction_id)
    let auction = auctions.find(auc => auc.auction_id === auction_id);
    console.log(auctions);

    removeAuctionCard(auction.auction_name,auction.start,auction.end,auction.status,auction.highestBid,auction.auction_id,auction.auction_code);
}

async function endAuction(auction_id)
{
    //type = EndAuction
    // code = ""
    let auction = auctions.find(auc => auc.auction_id === auction_id);
    if (!auction) {
        console.error("Auction not found");
        return;
    }
    try{
        await endAuctionAPI(auction.auction_code);
    }
    catch (error) {
        console.error("Error fetching properties:", error);
    }
}

function endAuctionAPI(code) {
    return new Promise((resolve, reject) => {
        let xhr = new XMLHttpRequest();
        let url = "/auctionApi";

        let params = {
            type: "EndAuction",
            code: code
        };

        let requestBody = JSON.stringify(params);

        xhr.open("POST", url, true);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    let responseData = JSON.parse(xhr.responseText).data;
                    resolve(responseData);
                } else {
                    reject(new Error("Failed to end auction"));
                }
            }
        };

        xhr.onerror = function () {
            console.error("Request failed due to a network error or server issue.");
            reject(new Error("Network error or server issue"));
        };

        xhr.send(requestBody);
    });
}

function updateAuction()
{
    document.getElementById('update-auction-form').submit();
}



async function fetchAuctionData(user_id){
    try {
        auctions = new Array();
        await fetchAuctions(user_id);
        console.log("All data loaded");

    } catch (error) {
        console.error("Error fetching data:", error);
    }
}

async function fetchAuctions(user_id){
    try {
        let auctionData = await apiCallAuctions(user_id);
        for (let auction of auctionData) {
            console.log(auction);
            auctions.push(auction);
            createAuctionCard(auction.name,auction.start,auction.end,auction.status,auction.highest_bid,auction.auction_id)
        }
    } catch (error) {
        console.error("Error fetching auctions:", error);
    }
}

function apiCallAuctions(user_id)
{
    return new Promise((resolve, reject) => {
        //Declare XML Request variable and request url
        let xhr = new XMLHttpRequest();
        let url = "../includes/auction_api.php";
        //Declare parameters
        let params = {
            type: `GetUserAuctions`,
            user_id: user_id
        };

        let requestBody = JSON.stringify(params);

        xhr.open("POST", url, true);

        // Set the Content-Type header BEFORE sending the request
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    let responseData = JSON.parse(xhr.responseText).data;
                    resolve(responseData);
                } else {
                    reject(new Error("Failed to fetch agents"));
                }
            }
        };

        // Send the request to the API
        xhr.send(requestBody);

        xhr.onerror = function () {
            console.error("Request failed due to a network error or server issue.");
            reject(new Error("Network error or server issue"));
        };
    });
}

