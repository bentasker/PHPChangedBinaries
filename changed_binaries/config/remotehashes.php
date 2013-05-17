<?php
/** Configuration file for RemoteHashStore integration
*
*/



$remote_store_enabled = true;
$config['api_server'] = 'http://benscomputer.no-ip.org/changedbinaries/';
$config['api_key'] = '9ee82cdd69c32aad0ed16598c034ede2625a0eae';
$config['server_ident'] = 'test6';

// How many hashes should we send to the API at once?
$config['processblock'] = 100;


// Set this to true to have the API server email alerts.
$config['server_email'] = true;


// Don't change this!
$config['api_secret'] = '6789';





 
