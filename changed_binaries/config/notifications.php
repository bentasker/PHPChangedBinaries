<?php
/** changed_binaries - Notifications configuration
*
* @author B Tasker
* @copyright B Tasker 2013
*
* http://www.bentasker.co.uk/documentation/security/196-php-changed-binaries
* 
* @license GNU GPL V2 - See LICENSE
*
* Checks system binaries against stored hashes to detect changes
*
*/


$notify['enabled'] = true;
$notify['address'] = 'changedbins@bentasker.co.uk';

// The email subject to use. [SERVER] will be replaced with the machines hostname
$notify['subject'] = 'Changed Binaries report for [SERVER]';

// Which output levels should be sent?
$notify['info'] = false;
$notify['warning'] = true;
$notify['alarm'] = true;


// Debug settings
$notify['displaydebug'] = false;
$notify['emaildebug'] = false;
?> 
