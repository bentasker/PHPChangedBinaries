<?php
/** Configuration file for RemoteHashStore integration
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



$remote_store_enabled = true;
$config['api_server'] = 'http://api.bentasker.co.uk/changedbinaries/';
$config['api_key'] = '';
$config['server_ident'] = '';

// How many hashes should we send to the API at once?
$config['processblock'] = 100;


// Set this to true to have the API server email alerts.
$config['server_email'] = true;


// Don't change this!
$config['api_secret'] = '6789';





 
