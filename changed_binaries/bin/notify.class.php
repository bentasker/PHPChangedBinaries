<?php



class changedbinariesNotify{
   protected $alarms = array();
   protected $warnings = array();
   protected $infos = array();
    

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
  }



}









?>