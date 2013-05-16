<?php
/** changed_binaries, remote_admin script
*
* @author B Tasker
* @copyright B Tasker 2013
* 
* @license GNU GPL V2 - See LICENSE
*
* Allows for adjustment of settings in the RemoteHashes API
*
*/

error_reporting(E_ALL);
define('_PROGPATH',dirname(__FILE__)."/../");

// Load the dependencies
require_once(_PROGPATH.'lib/notify.class.php');
require_once(_PROGPATH.'lib/Remote.class.php');
require_once(_PROGPATH.'lib/Remoteadmin.class.php');



class changedbinariesmain{

    protected $path;
    protected $action;
    protected $arguments;
    protected $files = array();
    var $notify;
    


    function __construct($arg){
      $this->notify = new changedbinariesNotify;
      $this->remote = new changedbinariesRemoteAdmin($this->notify);
      $this->arguments = $arg;
    }



    /** Get input of some form from the user
    *
    */
    function getInput($msg){
      fwrite(STDOUT,"$msg: ");
      return trim(fgets(STDIN));
    }


    /** Entry point, passes off to the relevant handler
    *
    */
    function admininit(){
      // Check whether the system is actually configured to use the API
      if (!$this->remote->isenabled()){
	echo "Settings indicate that the API is not enabled. Exiting \n";
	return false;
      }

      
      if (in_array("--addserver",$this->arguments)){
	  // Call the add server function
	  $this->addserver();
      }elseif(in_array("--listservers",$this->arguments)){
	  $this->listservers();
      }
    }


    /** List servers recorded against an API Key
    *
    */
    function listservers(){
      $servers = $this->remote->listservers();

      foreach ($servers as $server){
	$this->notify->info("Server Name: {$server->serverRef}		Contact: {$server->contact}");
      }
      $this->notify->info(" ");
    }

    
    function addserver(){

      if (!in_array("--email",$this->arguments)){
	echo "ERROR: You must specify a contact email";
	$this->usage('addserver');
	return false;
      }

      $k = array_search("--addserver",$this->arguments);
      $k++;
      $serverident = $this->arguments[$k];
      $k = array_search("--email",$this->arguments);
      $k++;      
      $contact = $this->arguments[$k];
      
      $this->remote->addserver($serverident,$contact);


    }


    /** Output usage information
    *
    */
    function usage($method=false){

      // --addserver MYNAME --email ben@example.com


    }


}




$cbins = new changedbinariesmain($argv);

// Start the check
$cbins->admininit();

?>