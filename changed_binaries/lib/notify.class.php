<?php

class changedbinariesNotify{
   protected $alarms = array();
   protected $warnings = array();
   protected $infos = array();
   protected $config;


  function __construct(){
    require dirname(__FILE__)."/../config/notifications.php";
    $this->config = $notify;
  }

  function alarm($str){
    echo "ALERT: ".$str."\n";
    $this->alarms[] = $str;

  }


  function warning($str){
    echo "WARN: ".$str."\n";
    $this->warnings[] = $str;
  }



  function info($str){
    echo $str."\n";
    $this->infos[] = $str;
  }



  function __destruct() {
    echo "Notify: Processing queued notifications\n\n";
    $msg = date('Y-m-d H:i:s') . ": Changed Binaries output follows\n".
	  count($this->alarms) . " Alarms\n" . count($this->warnings) . " Warnings\n" . count($this->infos) . " Information messages\n\n";

    if (!$this->config['enabled'] || empty($this->config['address'])){
      return;
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