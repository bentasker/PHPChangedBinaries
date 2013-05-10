<?php



class changedbinariesRemote{

    protected $enabled;
    protected $config;
    protected $token;
    var $notify;


    function __construct(&$notify){
      require dirname(__FILE__)."/../config/remotehashes.php";
      $this->enabled = $remote_store_enabled;
      $this->config = $config;
      $this->notify = $notify;
    }


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
      $req->request = new stdClass();
      $req->key = $this->config['api_key'];
      $req->token = $this->config['api_secret'];
      $req->request->$apiIndex->filehash = $fname;
      $req->request->$apiIndex->curhash = $hash;
      $req->request->$apiIndex->filename = $file;

      $request = json_encode($req);
      $this->notify->debug("Request is $request");
      $resp = $this->placeRequest($request);
      
      if (!$resp){
	$this->notify->warning("API Request for $file failed");
	return array('CONNECTFAIL',null);
      }

      $resp = json_decode($resp);


      if ($resp->status != 'ok'){
	$this->notify->warning("API Reported an error: {$resp->status}: {$resp->error}");
	return array("APIERROR",null);
      }
      

      if ($resp->$apiIndex->match == 1){
	$this->notify->info("$file unchanged since last update");
	return array("RESPRECEIVED",null);
      }else{
	$this->notify->alarm("$file has changed");
	return array("RESPRECEIVED",null);
      }
      
      // this is going to break things for testing
      return array(true,null);
    }




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
      $req->request = new stdClass();
      $req->key = $this->config['api_key'];
      $req->token = $this->token;
      $req->request->$apiIndex->filehash = $fname;
      $req->request->$apiIndex->curhash = $hash;
      $req->request->$apiIndex->filename = $file;

      $request = json_encode($req);
      $this->notify->debug("Request is $request");
      $resp = $this->placeRequest($request);
      
      if (!$resp){
	$this->notify->warning("API Request for $file failed");
	return 'CONNECTFAIL';
      }

      $resp = json_decode($resp);

      if ($resp->status != 'ok' && $resp->status != 'autherror'){
	$this->notify->warning("API Reported an error: {$resp->status}: {$resp->error}");
	return "APIERROR";

      }elseif($resp->status == 'autherror'){

	$alert = "Unauthorised attempt to update file hashes by ".getenv("USER")." at ".date('Y-m-d H:i:s');
	$ssh = getenv('SSH_CLIENT');
	 if (!empty($ssh)){
	    $alert .= " SSH connection details are $ssh";
	 }
	$this->notify->secalert($alert);

      }

      if ($resp->response->$apiIndex->updated == 1){
	$this->notify->info("Hash for $file updated");
      }else{
	$this->notify->warning("Hash for $file not updated, but API didn't raise an error");
      }
	return 'RESPRECEIVED';

    }


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



}


/** Updates authentication plan

When creating the API key user is asked to specify a password - $pass
Shared secret is created $secret
Server secret1 is created $serv_secret
Server secret2 is created $serv_secret2
API Token is generated as follows

Might change the cipher though

$secret = openssl_encrypt($serv_secret,'des-cbc',$serv_secret2.$pass);
$secret = openssl_encrypt($secret,'des-cbc',$pass);


When updating, user is asked for their password. This is used to decrypt the first wrapper (so never sent to the server)
the output is then submitted to the server, which decrypts it to ensure that the final key is correct. 

*/


?>
