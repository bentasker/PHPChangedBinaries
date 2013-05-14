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
    var $notify;


    function __construct(&$notify){
      require dirname(__FILE__)."/../config/remotehashes.php";
      $this->enabled = $remote_store_enabled;
      $this->config = $config;
      $this->notify = $notify;

      if ($this->enabled){
	if (!$this->openAuditSession()){
	  return false;
	}
      }

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

      $fname = sha1($file);
      $apiIndex = "A$fname";

      $this->notify->debug("Building request");
      // Build the request
      $req = new stdClass();
      $req->action = 'check';
      $req->requesttime = time();
      $req->key = $this->config['api_key'];
      $req->token = $this->config['api_secret'];
      $req->server = $this->config['server_ident'];
      $req->session = $this->sessid;
      $req->request = new stdClass();
      $req->request->$apiIndex->filehash = $fname;
      $req->request->$apiIndex->curhash = $hash;
      $req->request->$apiIndex->filename = $file;
      $request = json_encode($req);

      $this->notify->debug("Request is $request");

      // Place the request
      $resp = $this->placeRequest($request);
      
      // Check the response
      if (!$resp){
	$this->notify->warning("API Request for $file failed");
	return array('CONNECTFAIL',null);
      }

      $resp = json_decode($resp);
      if ($resp->status != 'ok'){
	$this->notify->warning("{$resp->status}: {$resp->error}");
	return array("APIERROR",null);
      }
      

      if ($resp->response->$apiIndex->match == 1){
	$this->notify->info("$file unchanged since last update");
	return array("RESPRECEIVED",null);
      }else{
	$this->notify->alarm("$file has changed");
	return array("RESPRECEIVED",null);
      }
      
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

      $fname = sha1($file);

      // We prefix with an A to ensure it never starts with a number!
      $apiIndex = "A$fname";

      // Unlock the token
      if (!$this->getauthToken()){
	// We couldn't load it. Don't return false as we don't want to fall back on the local db
	return 'KEYFAIL';
      }

      $this->notify->debug("Building request");

      // Build the request
      $req = new stdClass();
      $req->action = 'upd';
      $req->requesttime = time();
      $req->key = $this->config['api_key'];
      $req->server = $this->config['server_ident'];
      $req->token = $this->token;
      $req->session = $this->sessid;
      $req->request = new stdClass();
      $req->request->$apiIndex->filehash = $fname;
      $req->request->$apiIndex->curhash = $hash;
      $req->request->$apiIndex->filename = $file;
      $request = json_encode($req);

      // Place the request
      $this->notify->debug("Request is $request");
      $resp = $this->placeRequest($request);
      
      // Check the response
      if (!$resp){
	$this->notify->warning("API Request for $file failed");
	return 'CONNECTFAIL';
      }

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

      if ($resp->response->$apiIndex->updated == 1){
	$this->notify->info("Hash for $file updated");
      }else{
	$this->notify->warning("Hash for $file not updated, but API didn't raise an error");
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
	curl_setopt($ch,CURLOPT_URL, $this->config['api_server']);
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
	  
	
      }
	  return true;
    }

    /** TODO: Use returned stats
    *
    */
    function __destruct(){
      $this->closeAuditSession();
    }


}

?>