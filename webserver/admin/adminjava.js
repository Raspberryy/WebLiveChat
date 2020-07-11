// ***********************
// **       Init        **
// ***********************

function init(){
    "use strict";

    requestServer();
    //setInterval(requestServer, 1000);
}

// ***********************
// **   DOM Management  **
// ***********************

// ** Adds Messges including delete and block buttons **
function addMessage(_id, _name, _text){
    "use strict";

    if(_id == "" || _name == "" || _text == "")
        return;
    
    // Create Message
    let form = document.createElement("form"); 
    
    form.setAttribute("class", "customtr");
    form.action = "./index.php"
    form.method = "POST";

    let msg = document.createElement("div");
    msg.setAttribute("class", "customtc");
    msg.textContent = _name + ": " + _text;
    form.appendChild(msg);

    let blockuser = document.createElement("input");
    blockuser.setAttribute("class", "customtc");
    blockuser.type = "submit";
    blockuser.name = "action";
    blockuser.value = "Block User";
    form.appendChild(blockuser);

    let delmsg = document.createElement("input");
    delmsg.setAttribute("class", "customtc");
    delmsg.type = "submit";
    delmsg.name = "action";
    delmsg.value = "Delete Message";
    form.appendChild(delmsg);
    
    let idinput = document.createElement("input");
    idinput.setAttribute("class", "customtc");
    idinput.type = "text";
    idinput.name = "msgID";
    idinput.value = _id;
    idinput.style.display = "none";
    form.appendChild(idinput);
    
    let chat = document.getElementById("messages");
    chat.dataset.offset = _id;
    chat.insertBefore(form, chat.firstChild);
}

// **************************
// ** Server Communication **
// **************************

let request; // XMLHTTPRequest for requestServer()

// ** Requests Messages from Server **
// Parses JSON String and calls functions for DOM changes
function requestServer(){
    "use strict";

    request = new XMLHttpRequest();
    request.open("GET", "../reqmsgs.php?getmessages=true&offset=" + document.getElementById("messages").dataset.offset); // URL for HTTP-GET
    request.onreadystatechange= addMessages; // Callback-Handler
    request.send();    
}

// ** Callbackhandler for requestServer()**
// Parses JSON String and calls functions for DOM changes
function addMessages(){
    "use strict";

    if (request.readyState != 4 || request.status != 200 || request.responseText == "")
        return;

    if(request.responseText == null || request.responseText == "")
        return

    console.log(request.responseText);
    let data = JSON.parse(request.responseText);

    for (var i = 0; i < data.length; i++) 
        addMessage(data[i][0], data[i][1], data[i][2]);        
}
