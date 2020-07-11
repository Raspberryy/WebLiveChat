<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once(__DIR__ . DIRECTORY_SEPARATOR . 'CensorWords.php'); // Filter for bad words


// *************
// ** Classes **
// *************

    class Message implements JsonSerializable {

        public $username;
        public $message;
        public $msgid;
        public $ip;
        public $jsonArray;

        function __construct( $username, $message, $msgid, $ip)
        {
            $this->username = $username;
            $this->message = $message;
            $this->msgid = $msgid;
            $this->ip = $ip;

            $this->jsonArray = array();
            array_push($this->jsonArray, 
                htmlspecialchars($this->username, ENT_QUOTES, 'UTF-8'), 
                htmlspecialchars($this->message, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($this->msgid, ENT_QUOTES, 'UTF-8'));
        }

        public function jsonSerialize() {
            return $this->jsonArray;
        }
    }


// ***********************************
// ** Base Class for LiveChat Logic **
// ***********************************

abstract class Page
{
    protected $database = null;
    protected $censor = null;
 
    protected function __construct() 
    {
        require_once 'pwd.php'; // Set mysql credentials here in file
        $this->database = new mysqli($servername, $username, $password, $dbname);

        $this->censor = new CensorWords;
        $this->censor->setDictionary('de');
    }
    

    protected function __destruct()    
    {
        $this->database->close();
    }
    
    protected function processReceivedData() 
    {
        if (get_magic_quotes_gpc()) {
            throw new Exception
                ("Please deactivate magic_quotes_gpc in php.ini!");
        }
    }

// *******************
// ** Messsges Mngt **
// *******************

    // Adds Message to messages table if user is not blocked
    protected function addMessage($_username, $_msg)
    {
        $username = $this->censor->censorString($_username);
        $msg = $this->censor->censorString($_msg);

        $username = substr($this->database->real_escape_string($username), 0, 254);
        $msg = substr($this->database->real_escape_string($msg), 0, 254);
        $ipadr = hash('sha256', substr($this->database->real_escape_string($_SERVER['REMOTE_ADDR']), 0, 254));

        $sql =  "INSERT INTO `messages` (`username`, `msg`, `ipadr`)" .
                " VALUES ('" . $username . "','" . $msg . "','" . $ipadr. "')"; 
        
        if($this->isBlockedUser($ipadr) != TRUE)
            $this->database->query($sql);
    }

    // Prints JSON string containing all messages with id 
    // greater than $_offset and not older than 3h
    protected function getMessages($_offset)
    {
        $offset = $this->database->real_escape_string($_offset);

        $sql = "SELECT * FROM `messages` WHERE id > " . $offset . " AND deleted = 0 ORDER BY `createDate` ASC"; 
        $result = $this->database->query($sql);

        $messages = array();
        
        if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                array_push($messages, new Message(intval($row["id"]), $row["username"], $row["msg"], $row["ipadr"]));                        
            }
        }

        echo json_encode($messages);
    }

    // Sets deleted = 1 for message with $_msgID in messages table
    protected function deleteMessage($_msgID)
    {
        $msgID = $this->database->real_escape_string($_msgID);
        $sql =  "UPDATE `messages` SET deleted = 1 WHERE id = " . $msgID; 
        
        $this->database->query($sql);   
    }

    // Prints JSON string containing all messages where delted = 1
    // and not older than 4h - This should be 1h more than the
    // interval in getMessages()
    protected function getDeletedMessages()
    {
        $sql = "SELECT id FROM `messages` WHERE createDate > DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 4 HOUR) AND deleted = 1 ORDER BY `createDate` ASC"; 
        $result = $this->database->query($sql);

        echo "[";
        if ($result->num_rows > 0) {
            $rowcntr = 0;
            while($row = $result->fetch_assoc()) {
                
                if ($rowcntr != 0) 
                    echo ", ";

                $rowcntr++; 
                echo ($row["id"]);                
            }
        }
        echo "]";
    }

// *******************
// **   User Mngt   **
// *******************

    // Checks if $ipadr exists in blockeduser table
    // return true or false
    protected function isBlockedUser($_ipadr)
    {
        $ipadr = $this->database->real_escape_string($_ipadr);
        $sql =  "SELECT ipadr FROM `blockeduser` WHERE ipadr = '" . $ipadr . "'"; 

        $result = $this->database->query($sql);

        if ($result->num_rows > 0)
                return TRUE;
        
        return FALSE;
    }

    // Inserts Entry to blockeduser table
    protected function addBlockedUser($_ipadr)
    {
        $ipadr = $this->database->real_escape_string($_ipadr);
        $sql =  "INSERT INTO blockeduser (ipadr) VALUES ('" . $ipadr . "')";
        $result = $this->database->query($sql);
    }

    // Creates new blockeduser if needed
    // Deletes all posts from this user
    protected function blockUser($_msgID)
    {
        $ip = $this->getIPAdr($_msgID);

        if(!$this->isBlockedUser($ip)) 
            $this->addBlockedUser($ip);
        
        $sql =  "UPDATE `messages` SET `deleted` = 1 WHERE ipadr = '" . $this->database->real_escape_string($ip) . "'";
        $this->database->query($sql);   
    }

    // Reverts blockUser()
    protected function unblockUser($_username, $_ipadr)
    {
        $username = $this->database->real_escape_string($_username);
        $ipadr = $this->database->real_escape_string($_ipadr);

        $sql =  "UPDATE `messages` SET `deleted` = 0 WHERE ipadr = '" . $ipadr . "' AND username='" . $username . "'";                 
        $this->database->query($sql);  
        
        $sql = "DELETE FROM blockeduser WHERE ipadr = '" . $ipadr . "'"; 
        $this->database->query($sql);  
    }

    // Gets IP for $msgID
    protected function getIPAdr($_msgID){
        $msgID = $this->database->real_escape_string($_msgID);
        $sql =  "SELECT ipadr FROM messages WHERE id = " . $msgID;
        $result = $this->database->query($sql);


        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row["ipadr"];
        }
        return "";
    }
}