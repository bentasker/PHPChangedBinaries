<?php
/** changed_binaries, remote_admin script library
*
* @author B Tasker
* @copyright B Tasker 2013
* 
* @license GNU GPL V2 - See LICENSE
*
* Allows for adjustment of settings in the RemoteHashes API
*
*/




class changedbinariesRemoteAdmin extends changedbinariesRemote{



    function __construct(&$notify){
      require _PROGPATH."config/remotehashes.php";
      $this->enabled = $remote_store_enabled;
      $this->config = $config;
      $this->apiserver = $config['api_server']."/admin/";
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


    /** List all servers listed against the API Key
    *
    */
    function listservers(){
;
      $this->getauthToken();
      $request->action = 'listservers';

      $request->requesttime = time();
      $request->key = $this->config['api_key'];
      $request->server = $this->config['server_ident'];
      $request->token = $this->token;
      $request->session = $this->sessid;


      $resp = $this->placeRequest(json_encode($request));

      if (!$resp){
	$this->notify->warning("API Error");
	return false;
      }

      $resp = json_decode($resp);

      if ($resp->status != 'ok'){
	$this->notify->warning("{$resp->status}: {$resp->error}");
	return false;
      }

      return $resp->response->servers;

      
    }



    /** Remove the specified server
    *
    * @arg serverident
    *
    */
    function rmserver($serverident){
      $this->getauthToken();


      $request->action = 'rmserver';
      $request->requesttime = time();
      $request->key = $this->config['api_key'];
      $request->server = $this->config['server_ident'];
      $request->token = $this->token;
      $request->session = $this->sessid;

      
      $request->request->serverid = $serverident;

      $resp = $this->placeRequest(json_encode($request));

      if (!$resp){
	$this->notify->warning("API Error");
	return;
      }

      $resp = json_decode($resp);

      if ($resp->status != 'ok'){
	$this->notify->warning("{$resp->status}: {$resp->error}");
	return;
      }

      $this->notify->info("Server with Ident {$resp->response->serverid} removed");
	  
    }



    /** Send a request to the API to add a new server
    *
    */
    function addserver($serverident,$contact,$checkin=null){
      $this->getauthToken();
      $request->action = 'addserver';

      $request->requesttime = time();
      $request->key = $this->config['api_key'];
      $request->server = $this->config['server_ident'];
      $request->token = $this->token;
      $request->session = $this->sessid;

      $request->request->checkin = $checkin;
      $request->request->serverid = $serverident;
      $request->request->contact = $contact;	

      $resp = $this->placeRequest(json_encode($request));

      if (!$resp){
	$this->notify->warning("API Error");
	return;
      }

      $resp = json_decode($resp);

      if ($resp->status != 'ok'){
	$this->notify->warning("{$resp->status}: {$resp->error}");
	return;
      }

      $this->notify->info("Server with Ident {$resp->response->serverid} added");
	  
    }



    /** Send a request to the API to add a new server
    *
    */
    function editserver($serverident,$contact,$checkin,$newident){
      $this->getauthToken();
      $request->action = 'editserver';

      $request->requesttime = time();
      $request->key = $this->config['api_key'];
      $request->server = $this->config['server_ident'];
      $request->token = $this->token;
      $request->session = $this->sessid;

      $request->request->checkin = $checkin;
      $request->request->serverid = $serverident;
      $request->request->contact = $contact;	
      $request->request->newident = $newident;

      $resp = $this->placeRequest(json_encode($request));

      if (!$resp){
	$this->notify->warning("API Error");
	return;
      }

      $resp = json_decode($resp);

      if ($resp->status != 'ok'){
	$this->notify->warning("{$resp->status}: {$resp->error}");
	return;
      }

      $this->notify->info("Server with Ident {$resp->response->serverid} edited.");
	  
    }





}


?>