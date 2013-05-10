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


    function retrievehash($file){

      if (!$this->enabled){
	return array(false,null);
      }
      $this->notify->debug("Attempting to retrieve hash from RemoteStore");

      $fname = sha1($file);

      // this is going to break things for testing
      return array(true,null);
    }


    function updatehash($file,$hash){

      if (!$this->enabled){
	return false;
      }

      $fname = sha1($file);

      // Unlock the token
      if (!$this->getauthToken()){
	// We couldn't load it. Don't return false as we don't want to fall back on the local db
	return 'KEYFAIL';
      }

	$this->notify->debug("Attempting to update has in RemoteStore");
    /* We'll be needing this once the API response comes back.
	echo "Incorrect password";
	  $alert = "Unauthorised attempt to update file hashes by ".getenv("USER")." at ".date('Y-m-d H:i:s');

	  $ssh = getenv('SSH_CLIENT');
	  if (!empty($ssh)){
	    $alert .= " SSH connection details are $ssh";
	  }

    */
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
	  
	  return true;
      }
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
