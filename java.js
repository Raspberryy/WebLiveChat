// ***********************
// **       Init        **
// ***********************

let spamFilter;
let maxMessages = 2; // Gets reset every time checkDeletedMessages is called (default 5000ms)

// Can either be called in onLoad() or (if no access to that) like in the example
// Sets recurring functin calls and applys Eventlisteners
function initLiveChat(){
    "use strict";

	console.log("LiveChatInit");
    spamFilter = maxMessages;

    requestServer();
    setInterval(requestServer, 1000);   // Requests new messages from Server
    setInterval(checkDeletedMessages, 5000);    // Checks for bad messages that should be deleted 

    document.getElementById("eingabe").addEventListener("keyup", keylistenerEingabe);
    document.getElementById("name").addEventListener("keyup", keylistenerName);

    document.getElementById("optionsmenuid").style.display = "none";
    document.getElementById("errormsg").style.display = "none";

    let cookiename = getName();
    if (cookiename != null && cookiename != "")
        document.getElementById("name").value = cookiename;
}

// ** Enter in text field -> Sends message **
function keylistenerEingabe(event){
    "use strict";
    
    if( event.keyCode === 13){
        sendMessage();
    }
}

// ** Enter in name field -> Sets name and switches to Livechat **
function keylistenerName(){
    "use strict";

    if( event.keyCode === 13){
        setName(document.getElementById("name").value);
        showOptions();
    }
}

// ***********************
// **   DOM Management  **
// ***********************

// ** Switches between Options Menu and LiveChat **
function showOptions() {
    "use strict";

    let chat = document.getElementById("messages");
    let optmenu = document.getElementById("optionsmenuid");

    if(chat.style.display == "none") {
        chat.style.display = "";
        optmenu.style.display = "none";
        document.getElementById("errormsg").style.display = "none";
    }
    else {
        chat.style.display = "none";
        optmenu.style.display = "flex";
    }
}

// ** Appends Livechat with new message **
function addMessage(_id, _name, _text){
    "use strict";

    if(_id == "" || _name == "" || _text == "")
        return;
    
    // Create Message
    let newMsg = document.createElement("div");     
    newMsg.setAttribute("class", "message");
    newMsg.dataset.id = _id;
    newMsg.innerHTML = _name + ": " + decodeURIComponent(_text);

    // Add Message & AutoScroll
    let chat = document.getElementById("messages");
    if(chat.scrollTop === (chat.scrollHeight-chat.offsetHeight)) {
        chat.appendChild(newMsg);
        chat.scrollTop = chat.scrollHeight;
    }
    else {
        chat.appendChild(newMsg);
    }

    chat.dataset.offset = _id;
}

// ** Deletes Message with _id from DOM **
function deleteMessage(_id){    
    "use strict";

    if(_id == "")
        return;

    var children = document.getElementById("messages").children;

    for (var i = 0; i < children.length; i++) {
       if(children[i].dataset.id == _id) { 
            children[i].remove();
            console.log("Deleted ID: " + _id);
       }
    }
}

// **************
// **   Kekse  **
// **************

// ** Sets Cookie with username **
// Default expires is 30 days
function setName(_username){
    "use strict";

    var date = new Date();
    date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000)); // 30 Days

    document.cookie = "username=" + _username + "; expires=" + date.toUTCString() + "; path=/;  SameSite=Strict";
}

// ** Get username from cookie **
function getName(){
    "use strict";

    var ca = document.cookie.split(';');

    for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
    if (c.indexOf("username=") == 0) return c.substring(9, c.length);
    }

    return null;
}


// **************************
// ** Server Communication **
// **************************

let request; // XMLHTTPRequest for requestServer()

// ** Updates Livechat with new Messages **
// Requests Messages from Server using the the offset
// stored in the messages div. That way only new messages
// are requested
function requestServer(){
    "use strict";
	
    request = new XMLHttpRequest();
    request.open("GET", "reqmsgs.php?getmessages=true&offset=" + document.getElementById("messages").dataset.offset); // URL for HTTP-GET
    request.onreadystatechange= addMessages; // Callback-Handler
    request.send();    
}

// ** Callback Handler form requestServer **
// Parses JSON String and calls functions for DOM changes
function addMessages(){
    "use strict";
	
    if (request.readyState != 4 || request.status != 200 || request.responseText == "")
        return;

    if(request.responseText == null || request.responseText == "")
        return

    //console.log(request.responseText);
    let data = JSON.parse(request.responseText);

    for (var i = 0; i < data.length; i++) 
        addMessage(data[i][0], data[i][1], data[i][2]);        
}


let requestSendMsg; // XMLHTTPRequest for sendMessage()

// ** Sends text value of eingabe to server as new message **
// SPAM Filter: User is only allowed to write maxMessages every
// Interval the checkDeletedMessages is called (default 5000ms)
// If no name is entered, GUI switches to Options Menu
// Sends Messages via AJAX to Server
function sendMessage(){
    "use strict";

    let eingabe = document.getElementById("eingabe")
    
    // Skip Empty Messages
        if (eingabe.value === "")
            return;

    // Check for Spam
        if( spamFilter == 0 ){
            let sendbtn = document.getElementById("sendbtn");
            sendbtn.disabled = true;
            return;
        }
    
    // Check if username is set and handle GUI
        let currentname =   document.getElementById("name").value;
        if (currentname != null && currentname != ""){
            setName(currentname);
            document.getElementById("errormsg").style.display = "";
            document.getElementById("messages").style.display = "";
            document.getElementById("optionsmenuid").style.display = "none";
        }
        else {
            document.getElementById("errormsg").style.display = "";
            document.getElementById("messages").style.display = "none";
            document.getElementById("optionsmenuid").style.display = "flex";
            return;
        }
        
    // Create AJAX Request
        requestSendMsg = new XMLHttpRequest();
        requestSendMsg.open("POST", "http://localhost/reqmsgs.php", true); // URL for HTTP-GET 
        requestSendMsg.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");       
        requestSendMsg.send("username=" + encodeURIComponent(currentname) + "&msg=" + encodeURIComponent(eingabe.value));  

    // Reset value
        eingabe.value = "";
        spamFilter--;
        eingabe.focus();
}


let requestDelMsgs; // XMLHTTPRequest for checkDeletedMessages()

// ** Deletes exiting messages when deleted by an Admin **
// Requests deleted Posts not older than 4h and compares that
// list to existing messages in DOM. Deletes them if needed.
function checkDeletedMessages() {
    "use strict";
    spamFilter = maxMessages;
    let sendbtn = document.getElementById("sendbtn");
    sendbtn.disabled = false;

    requestDelMsgs = new XMLHttpRequest();
    requestDelMsgs.open("GET", "reqmsgs.php?getDeletedMessages"); // URL for HTTP-GET 
    requestDelMsgs.onreadystatechange = deleteMessages; // Callback-Handler
    requestDelMsgs.send();  
}

// ** Callback Handler form checkDeletedMessages **
// Parses JSON String and calls functions for DOM changes
function deleteMessages() {
    "use strict";
    
    if (requestDelMsgs.readyState != 4 || requestDelMsgs.status != 200)
        return;

    if(requestDelMsgs.responseText == null || requestDelMsgs.responseText == "")
        return;

    let data = JSON.parse(requestDelMsgs.responseText);
    
    

    for (var i = 0; i < data.length; i++){
        deleteMessage(data[i]);
    } 
}