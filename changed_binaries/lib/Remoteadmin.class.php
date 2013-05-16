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

    function listservers(){
      $old = $this->apiserver;
      $this->apiserver .= 'admin/';
      $this->getauthToken();


      $request->action = 'listservers';

      $request->requesttime = time();
      $request->key = $this->config['api_key'];
      $request->server = $this->config['server_ident'];
      $request->token = $this->token;
      $request->session = $this->sessid;


      $resp = $this->placeRequest(json_encode($request));



      $this->apiserver = $old;
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

    /** Send a request to the API to add a new server
    *
    */
    function addserver($serverident,$contact){
      $old = $this->apiserver;
      $this->apiserver .= 'admin/';
      $this->getauthToken();


      $request->action = 'addserver';

      $request->requesttime = time();
      $request->key = $this->config['api_key'];
      $request->server = $this->config['server_ident'];
      $request->token = $this->token;
      $request->session = $this->sessid;

      
      $request->request->serverid = $serverident;
      $request->request->contact = $contact;	

      $resp = $this->placeRequest(json_encode($request));

      $this->apiserver = $old;
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




}


?>