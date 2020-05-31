// ***********************
// **       Init        **
// ***********************

function keylistenerEingabe(event){
    if( event.keyCode === 13){
        sendMessage();
    }
}

// ***********************
// **   DOM Management  **
// ***********************

function createMessage(_text){
    let msg = document.createElement("div");
    msg.setAttribute("class", "message")
    msg.textContent = _text;
    return msg;
}

function showOptions() {

    let chat = document.getElementById("messages");

    if(chat.style.visibility == "hidden")
        chat.style.visibility = "visible";
    else
        chat.style.visibility = "hidden";

}


// **************************
// ** Server Communication **
// **************************

function requestServer(){
   
}

function sendMessage(){
      
    let eingabe = document.getElementById("eingabe")
    
    // Skip Empty Messages
        if (eingabe.value === "")
            return;
    
    // Create and Add Message & AutoScroll
        let chat = document.getElementById("messages");
        if(chat.scrollTop === (chat.scrollHeight-chat.offsetHeight)) {
            chat.appendChild(createMessage(eingabe.value));
            chat.scrollTop = chat.scrollHeight;
        }
        else {
            chat.appendChild(createMessage(eingabe.value));
        }

    // Reset value
        eingabe.value = "";
}



