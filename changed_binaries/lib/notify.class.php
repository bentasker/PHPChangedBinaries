<?php
/** changedbinaries Notification library
*
* @author B Tasker
* @copyright B Tasker 2013
*
* http://www.bentasker.co.uk/documentation/security/196-php-changed-binaries
* 
* @license GNU GPL V2 - See LICENSE
*
* Handles all notifications, if configured to do so will email on completion.
*
*/


class changedbinariesNotify{
   protected $alarms = array();
   protected $warnings = array();
   protected $infos = array();
   protected $debugs = array();
   protected $secalerts = array();
   protected $config;
   protected $infocount = 0;
   protected $warncount = 0;
   protected $alarmcount = 0;


  function __construct(){
    require _PROGPATH."config/notifications.php";
    $this->config = $notify;
  }

  function alarm($str){
    echo "ALERT: ".$str."\n";
    $this->alarmcount++;
    if ($this->config['alarm']){
	$this->alarms[] = $str;
    }
  }


  function warning($str){
    echo "WARN: ".$str."\n";
    $this->warncount++;
    if ($this->config['warning']){
	$this->warnings[] = $str;
    }
  }



  function info($str){
    echo $str."\n";
    $this->infocount++;
    if ($this->config['info']){
	$this->infos[] = $str;
    }
  }


  function debug($str){

    if ($this->config['displaydebug']){
      echo "$str\n";
    }

    if ($this->config['emaildebug']){
    $this->debugs[] = $str;
    }

  }

  function secalert($str){
    $this->secalerts[] = $str;
  }


  function __destruct() {
    echo "Notify: Processing queued notifications\n\n";

    if (!$this->config['enabled'] || empty($this->config['address'])){
      return;
    }
    $secalerts = count($this->secalerts);

    $msg = date('Y-m-d H:i:s') . ": Changed Binaries output follows\n".
	  $secalerts . " Security alerts\n".
	  $this->alarmcount . " Alarms\n" . $this->warncount . " Warnings\n" . $this->infocount . " Information messages\n\n";

    if ($secalerts > 0){
    $msg .=  "\n\nSECURITY ALERTS\n\n" . implode("\n",$this->secalerts);
    }

    if ($this->config['alarm']){
	$msg .=  "\n\nALARMS\n\n" . implode("\n",$this->alarms);
    }

    if ($this->config['warning']){
	$msg .=  "\n\nWARNINGS\n\n" . implode("\n",$this->warnings);
    }

    if ($this->config['info']){
	$msg .=  "\n\nInformational Messages\n\n" . implode("\n",$this->infos);
    }

    $msg .= "\n Report ends";

    mail($this->config['address'],str_replace("[SERVER]",getenv('HOSTNAME'),$this->config['subject']),$msg);

  }



}









?>