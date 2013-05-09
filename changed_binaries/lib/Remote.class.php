<?php



class changedbinariesRemote{

    protected $enabled;
    protected $config;
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
      
      $fname = sha1($file);

      // this is going to break things for testing
      return array(true,null);
    }


    function updatehash($file,$hash){

      if (!$this->enabled){
	return false;
      }

      $fname = sha1($file);
	
	
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
