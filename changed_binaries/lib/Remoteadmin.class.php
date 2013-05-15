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


    /** Send a request to the API to add a new server
    *
    */
    function addserver($serverident,$contact){

      $this->apiserver .= 'admin';
      $this->getauthToken();


      $request->action = 'addserver';

      $this->request->requesttime = time();
      $this->request->key = $this->config['api_key'];
      $this->request->server = $this->config['server_ident'];
      $this->request->token = $this->token;
      $this->request->session = $this->sessid;

      
      $request->request->serverid = $serverident;
      $request->request->contact = $contact;	


      $this->placeRequest(json_encode($request));


    }




}


?>