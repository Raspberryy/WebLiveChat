<?php	// UTF-8 marker äöüÄÖÜß€
error_reporting(0);
ini_set('display_errors', 0);
require_once '../resources/page.php';


// ***********************************
// ** Class for Main administration **
// ***********************************

class Admin extends Page
{
    function __construct() 
    {
        parent::__construct();
        session_start();   
    }
     
    function __destruct() 
    {
        parent::__destruct();
    }

    // Handles Post and Get Data
    function processReceivedData() 
    {
        if(isset($_POST))
        {
            if (isset($_POST["action"]) && isset($_POST["msgID"])) {
                
                if($_POST["action"] == "Delete Message")
                    $this->deleteMessage($_POST["msgID"]);
                else if($_POST["action"] == "Block User")
                    $this->blockUser($_POST["msgID"]);
                
            } else if (isset($_POST["action"]) && isset($_POST["username"]) && isset($_POST["ipadr"])) {
                if($_POST["action"] == "Unblock User")
                    $this->unblockUser($_POST["username"], $_POST["ipadr"]);
            }

        }
    }

// ****************
// ** Login Mngt **
// ****************

    // First Function to be called
    // Checks Access & defines username and password
    function checkLogin(){
        $validUser = $_SESSION["login"] === true;

        if(isset($_POST["login"])) {
            $validUser = $_POST["username"] == "admin" && $_POST["password"] == "password";
            if(!$validUser)
            {
                $this->printLogin();
                die();
            }
            else
            {
                $_SESSION["login"] = true;
            } 
        }
        if(!$validUser) {
            $this->printLogin();
            die();
        } 
    }

    // Generate HTML for Login
    function printLogin(){
        echo <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8" />
    <title>Login</title>
</head>
<body>
    <form name="input" action="" method="post">
        <label for="username">Username:</label><input type="text" value="{$_POST['username']}" id="username" name="username" />
        <label for="password">Password:</label><input type="password" value="" id="password" name="password" />
        <input type="submit" value="Login" name="login" />
    </form>
</body>
</html>
HTML;
    } 

// *********************
// **  ViewData Mngt  **
// *********************

    // Print Blocked user as HTML
    protected function printBlockedUsers(){
        $sql =  "SELECT blockeduser.ipadr, messages.username " .
                "FROM blockeduser LEFT JOIN messages ON blockeduser.ipadr = messages.ipadr " .
                "WHERE messages.createDate in (SELECT MAX(messages.createDate) FROM messages GROUP BY ipadr)";     

        $result = $this->database->query($sql);  

         if ($result->num_rows > 0) {
            
            while($row = $result->fetch_assoc()) {
                echo "\t\t<form class='customtr' action='index.php' method='POST'>\n";
                echo "\t\t\t<input class='customtc' type='submit' name='action' value='Unblock User' readonly>\n";
                echo "\t\t\t<input class='customtc' type='text' name='ipadr' value='" . $row["ipadr"] . "' readonly>\n";
                echo "\t\t\t<input class='customtc' type='text' name='username' value='" . $row["username"] . "' readonly>\n";
                echo "\t\t</form>\n";
            }
        }


    }

    protected function printViewData(){
        echo <<<HTML
<html>
<head>
    <script src="./adminjava.js"></script>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <h2>Admin Panel</h2>

    <h4>Blocked User</h4>
    <div class ="livechat">
HTML;

    $this->printBlockedUsers();

echo <<<HTML
    </div>

    <div class="livechat">
        <div id="messages" class="messagecontainer" data-offset="0">
        </div>
    </div>

    <script>
        "use strict" 
        init();
    </script>
</body>
</html>

HTML;
    }

    public static function main() 
    {
        try {
            $page = new Admin();
            header("Content-Type: text/html; charset=UTF-8");
            $page->checkLogin();
            $page->processReceivedData();
            $page->printViewData();
        }
        catch (Exception $e) {
            header("Content-type: text/plain; charset=UTF-8");
            echo $e->getMessage();
        }
    }
}

Admin::main();