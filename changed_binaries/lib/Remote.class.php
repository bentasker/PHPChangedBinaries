<?php
/** RemoteHashStore support library for changed_binaries
*
* @author B Tasker
* @copyright B Tasker 2013
* 
* @license GNU GPL V2 - See LICENSE
*
* This implements support for the API server - moving the hashes away from the server we're checking!
*
*/



class changedbinariesRemote{

    protected $enabled;
    protected $config;
    protected $token;
    protected $sessid;
    protected $request;
    protected $blocksize;
    protected $apimethod;
    protected $apiserver;
    var $notify;


    function __construct(&$notify){
      require _PROGPATH."config/remotehashes.php";
      $this->enabled = $remote_store_enabled;
      $this->config = $config;
      $this->apiserver = $config['api_server'];
      $this->notify = $notify;
      $this->request = new stdClass();
      $this->request->request = new stdClass();
      $this->blocksize = 0;

      if ($this->enabled){
	if (!$this->openAuditSession()){
	  return false;
	}
      }

    }


    /** Return whether we're enabled or not
    *
    */
    function isenabled(){
      return $this->enabled;
    }


    /** Open a session and set the session ID
    *
    */
    function openAuditSession(){
      $req = new stdClass();
      $req->action = 'start';
      $req->requesttime = time();
      $req->key = $this->config['api_key'];
      $req->token = $this->config['api_secret'];
      $req->server = $this->config['server_ident'];
      $request = json_encode($req);
      $this->notify->debug("Attempting to open Audit session");
      $resp = json_decode($this->placeRequest($request));

      if (!$resp || $resp->status != 'ok'){
	$this->notify->warning("Could not open audit session");
	return false;
      }
      $this->sessid = $resp->response->session;
      $this->notify->info("Session {$this->sessid} opened");

    }


    function closeAuditSession(){

      $req = new stdClass();
      $req->action = 'end';
      $req->requesttime = time();
      $req->key = $this->config['api_key'];
      $req->token = $this->config['api_secret'];
      $req->server = $this->config['server_ident'];
      $req->session = $this->sessid;
      $request = json_encode($req);
      $this->notify->debug("Attempting to open Audit session");
      $resp = json_decode($this->placeRequest($request));

      if (!$resp || $resp->status != 'ok'){
	$this->notify->warning("Could not close audit session");
	return false;
      }
      $this->notify->info("Session {$this->sessid} Closed");
      $this->notify->info(" ");
      

      if ($resp->response->stats){
	$this->notify->info("{$resp->response->stats->checked} hashes processed");
	$this->notify->info("{$resp->response->stats->secalerts} Security Alerts");
	$this->notify->info("{$resp->response->stats->alerts} Alerts");
	$this->notify->info("{$resp->response->stats->warnings} Warnings");
      }
      unset($this->sessid);
    }


    /** Check the supplied hash against the one stored on the API server
    *
    * @arg file - str, full path to file
    * @arg hash - str, hash of current file
    *
    */
    function retrievehash($file,$hash){

      if (!$this->enabled){
	return array(false,null);
      }
      $this->notify->debug("Attempting to retrieve hash from RemoteStore");

      $this->apimethod='Check';

      $fname = sha1($file);
      $apiIndex = "A$fname";

      $this->notify->debug("Building request");
      // Build the request
      
      $this->request->request->$apiIndex->filehash = $fname;
      $this->request->request->$apiIndex->curhash = $hash;
      $this->request->request->$apiIndex->filename = $file;

      // Increase the blocksize count
      $this->blocksize++;
      
      // Should we trigger the request?
      if ($this->blocksize == $this->config['processblock']){
	$this->blocksize = 0;
	$res = $this->processCheck();
	unset($this->request->request);
	$this->request->request = new stdClass();
	return $res;
      }

      // Return true so we don't trigger the local db checks
      return array(true,null);

    }



    /** Send a check request to the server
    *
    */
    function processCheck(){
      
      

      // Finish building the request
      $this->request->action = 'check';
      $this->request->requesttime = time();
      $this->request->key = $this->config['api_key'];
      $this->request->token = $this->config['api_secret'];
      $this->request->server = $this->config['server_ident'];
      $this->request->session = $this->sessid;

      // Encode the request
      $request = json_encode($this->request);
      $this->notify->debug("Request is $request");
      
      // Place the request
      $resp = $this->placeRequest($request);


      // Check the response
      if (!$resp){
	$this->notify->warning("API Request failed");
	return array('CONNECTFAIL',null);
      }

      // We got something back, so let's check the status
      $resp = json_decode($resp);
      if ($resp->status != 'ok'){
	$this->notify->warning("{$resp->status}: {$resp->error}");
	return array("APIERROR",null);
      }
      
      // Cycle through all hashes in the response
      foreach ($resp->response as $key=>$value){

	  if ($value->match == 1){
	    $this->notify->info("{$value->filename} unchanged since last update");
	  }elseif($value->match == 2){
	    // No stored hash
	    $this->notify->warning("{$value->filename} has no stored hash");
	  }else{
	    // The hash we calculated doesn't match - somethings changed
	    $this->notify->alarm("{$value->filename} has changed");
	  }

      }

      // Return a positive status
      return array("SUCCESS",$resp->response);
      
    }



    /** Update hash on the API server for the specified file
    *
    * @arg file - string. Full path to the file
    * @arg hash - string hash of that file
    *
    *
    */
    function updatehash($file,$hash){

      if (!$this->enabled){
	return false;
      }

      $this->notify->debug("Attempting to update hash in RemoteStore");
      $this->apimethod='Update';
      $fname = sha1($file);

      // We prefix with an A to ensure it never starts with a number!
      $apiIndex = "A$fname";

      // Unlock the token
      if (!$this->getauthToken()){
	// We couldn't load it. Don't return false as we don't want to fall back on the local db
	return 'KEYFAIL';
      }
      passthru('clear');
      $this->notify->debug("Building request");

      // Build the request

      $this->request->request->$apiIndex->filehash = $fname;
      $this->request->request->$apiIndex->curhash = $hash;
      $this->request->request->$apiIndex->filename = $file;
      
      $this->blocksize++;

      // Should we trigger the request?
      if ($this->blocksize == $this->config['processblock']){
	$this->blocksize = 0;
	$res = $this->processUpdate();
	unset($this->request->request);
	$this->request->request = new stdClass();
	return $res;
      }

      

    }



    /** Process an update request
    *
    */
    function processUpdate(){
      

      $this->request->action = 'upd';
      $this->request->requesttime = time();
      $this->request->key = $this->config['api_key'];
      $this->request->server = $this->config['server_ident'];
      $this->request->token = $this->token;
      $this->request->session = $this->sessid;


      $request = json_encode($this->request);
      // Place the request
      $this->notify->debug("Request is $request");
      $resp = $this->placeRequest($request);
      
      // Check the response
      if (!$resp){
	$this->notify->warning("API Request failed");
	return 'CONNECTFAIL';
      }

      // We got something back
      $resp = json_decode($resp);

      if ($resp->status != 'ok' && $resp->status != 'autherror'){
	$this->notify->warning("API Reported an error: {$resp->status}: {$resp->error}");
	return "APIERROR";

      }elseif($resp->status == 'autherror'){
	// The supplied password was wrong
	$alert = "Unauthorised attempt to update file hashes by ".getenv("USER")." at ".date('Y-m-d H:i:s');
	$ssh = getenv('SSH_CLIENT');
	 if (!empty($ssh)){
	    $alert .= " SSH connection details are $ssh";
	 }
	$this->notify->secalert($alert);
	exit;

      }


      // Now we need to check the hash responses
      foreach($resp->response as $key=>$value){
	  if ($value->updated == 1){
	    $this->notify->info("Hash for {$value->filename} updated");
	  }else{
	    $this->notify->warning("Hash for {$value->filename} not updated, but API didn't raise an error - likely database issue");
	  }
      }
	return 'RESPRECEIVED';
    }



    /** Post the supplied request to the API server
    *
    * @arg request - JSON encoded string
    *
    * @return JSON string
    */
    function placeRequest($request){

	$fields_string ='';
	$fields_string = 'data='.urlencode($request);
	$this->notify->debug("Posting request to {$this->config['api_server']}");
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, $this->apiserver);
	curl_setopt($ch,CURLOPT_POST, 1);
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);

	$this->response = curl_exec($ch);
	curl_close($ch);
	$this->notify->debug("Server response: {$this->response}");
	return $this->response;
    
    }



    /** Request a password from the user and use it to decrypt the auth token
    *
    * If the password is incorrect, the API server will know. The aim of this is
    * to prevent an attacker from updating hashes after rooting the server. Can't 
    * easily prevent that if the pass is stored on the same machine!
    *
    */
    function getauthToken(){
      if (!isset($this->token) || empty($this->token)){
	  $this->notify->debug("Requesting credentials");

	  $pass = hash("sha512",changedbinariesmain::getInput('Enter password'));

	  if (!file_exists(dirname(__FILE__)."/../config/authkey")){
	    $this->notify->debug("Could not load ".dirname(__FILE__)."/../config/authkey");
	    $this->notify->warning('Authkey file does not exist');
	    return false;
	  }

	  $this->notify->debug("Decrypting key");
	  $str = implode("\n",file(dirname(__FILE__)."/../config/authkey"));
	  $this->token = openssl_decrypt($str,'des-cbc',$pass);
	  return true;
	
      }
	  return 'GOTITALREADY';
    }



    function __destruct(){
      
      // Make sure we haven't got unprocessed requests left in the block
      if ($this->blocksize != 0){
	$fn = "process".$this->apimethod;
	$this->$fn();
      }

      $this->closeAuditSession();
    }


}

?>