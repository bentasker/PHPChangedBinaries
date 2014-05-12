<?php
/** changed_binaries, remote_admin script
*
* @author B Tasker
* @copyright B Tasker 2013
*
* http://www.bentasker.co.uk/documentation/security/197-remotehashstore-documentation
* 
* @license GNU GPL V2 - See LICENSE
*
* Allows for adjustment of settings in the RemoteHashes API
*
*/

error_reporting(0);
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

      if (in_array("--addkey",$this->arguments)){
	$this->addKey();
      }
      
      if (in_array("--rmserver",$this->arguments)){
	  $this->rmserver();
      }

      if (in_array("--addserver",$this->arguments)){
	  // Call the add server function
	  $this->addserver();
      }

      if(in_array("--listservers",$this->arguments)){
	  $this->listservers();
      }
      

    }


    /** List servers recorded against an API Key
    *
    */
    function listservers(){
      $servers = $this->remote->listservers();

      foreach ($servers as $server){
	$this->notify->info("Server Name: {$server->serverRef}		Contact: {$server->contact}	Minimum Checkin: {$server->checkfreq} days");
      }
      $this->notify->info(" ");
    }



    /** Remove the specified server
    *
    */
    function rmserver(){
      $k = array_search("--rmserver",$this->arguments);
      $k++;
      $ident = $this->arguments[$k];
      $this->remote->rmserver($ident);

      // Having added the server, let's list the remaining ones
      $this->listservers();
    }


    /** Add the authkey
    *
    */
    function addKey(){
      $k = array_search("--addkey",$this->arguments);
      $k++;
      $key = $this->arguments[$k];

      $fh = fopen(_PROGPATH.'config/authkey','w');
      fwrite($fh,$key);
      fclose($fh);
    }


    /** Add a server
    *
    */
    function addserver(){

      if (!in_array("--email",$this->arguments)){
	echo "ERROR: You must specify a contact email";
	$this->usage('addserver');
	return false;
      }


      if ($k = array_search("--checkin",$this->arguments)){
	$k++;
	$checkin = $this->arguments[$k];
      }else{
	$checkin = 7;
      }

      $k = array_search("--addserver",$this->arguments);
      $k++;
      $serverident = $this->arguments[$k];
      $k = array_search("--email",$this->arguments);
      $k++;      
      $contact = $this->arguments[$k];
      
      $this->remote->addserver($serverident,$contact,$checkin);

      // Having added the server, let's list them
      $this->listservers();


    }


    /** Add a server
    *
    */
    function editserver(){

      if (!$k = array_search("--email",$this->arguments)){
	$email = '';
      }else{
	$k++;      
	$contact = $this->arguments[$k];
      }

      if ($k = array_search("--checkin",$this->arguments)){
	$k++;
	$checkin = $this->arguments[$k];
      }else{
	$checkin = '';
      }


      if ($k = array_search("--newname",$this->arguments)){
	$k++;
	$newident = $this->arguments[$k];
      }else{
	$newident = '';
      }


      $k = array_search("--editserver",$this->arguments);
      $k++;
      $serverident = $this->arguments[$k];
      
      $this->remote->editserver($serverident,$contact,$checkin,$newident);

      // Having added the server, let's list them
      $this->listservers();


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
