<?php	// UTF-8 marker äöüÄÖÜß€
error_reporting(0);
ini_set('display_errors', 0);
require_once __DIR__ . DIRECTORY_SEPARATOR . 'resources/page.php';


class AJAX extends Page
{
    function __construct() 
    {
        parent::__construct();
    }
     
    function __destruct() 
    {
        parent::__destruct();
    }

    function processReceivedData() 
    {
        if (isset($_REQUEST["getmessages"]) && isset($_REQUEST["offset"]))
            $this->getMessages($_REQUEST["offset"]);
        
            
        if (isset($_REQUEST["getDeletedMessages"]))
            $this->getDeletedMessages();

        
        if(isset($_POST["username"]) && isset($_POST["msg"])) 
                $this->addMessage($_POST["username"], $_POST["msg"]); 
        
    }

    public static function main() 
    {
        try {
            $page = new AJAX();
            header("Content-Type: application/json; charset=UTF-8");
            $page->processReceivedData();
        }
        catch (Exception $e) {
            header("Content-type: text/plain; charset=UTF-8");
            echo $e->getMessage();
        }
    }
}

AJAX::main();