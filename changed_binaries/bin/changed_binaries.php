<?php
error_reporting(0);


// Load the dependencies
require_once('notify.class.php');

class changedbinariesmain{

    protected $path;
    protected $action;
    protected $files = array();
    var $notify;


    function __construct(){
      $this->notify = new changedbinariesNotify;
    }


    function getpath(){
      $this->path = explode(":",getenv("PATH"));
      return $this->path;
    }


    function setadditional($dir){
      if (!in_array($dir,$this->path)){
	$this->path[] = $dir;
      }
    }


    function addfile($file){
      if (!file_exists($file)){
	return false;
      }

      $this->files[] = $file;
    }


    function setaction($act){
      $this->action = $act;
    }


    function checkfiles($dir){


      if (!is_dir($dir) || !$h = opendir($dir)){
	return false;
      }

      while (false !== ($entry = readdir($h))){
	if ($entry == '.' || $entry == '..'){
	  continue;
	}
	$trg = $dir."/".$entry;

	// If it's a dir, we want to recurse
	if (is_dir($trg) && !is_link($trg)){
	  $this->checkfiles($trg);
	}elseif (is_link($trg) && is_dir($trg)){
	  continue; // we'll work this bit out later
	}


	$this->checkfile($trg);

      }

    }


    function checkfile($file){
      $fn = $this->action."Hash";
      $this->$fn($file);
    }


    function storeHash($file){
      $fname = sha1($file);
      $hash = $this->calcHash($file);
      $this->notify->info("Storing hash for $file in ". dirname(__FILE__)."/../db/$fname.def");

      $f = fopen(dirname(__FILE__)."/../db/$fname.def",'w');

      $str = "<?php \$hsh='$hash'; \$store='".time()."';?>";
      fwrite($f,$str);
      fclose($f);

    }


    function calcHash($file){
      return hash_file('sha512',$file);
    }



    function checkHash($file){

      $cur = $this->calcHash($file);
      $stored = $this->loadDef($file);

      if ($cur != $stored){
	$this->notify->alarm("$file has changed");
      }else{
	$this->notify->info("$file unchanged since last update");
      }

    }


    function loadDef($file){

      $fname = sha1($file);

      if (!include(dirname(__FILE__)."/../db/$fname.def")){
	$this->notify->warning("Could not load definition for $file");
	return false;
      }

      return $hsh;
    }



    function init(){

      foreach ($this->files as $file){
	  $this->checkfile($file);
      }


      foreach ($this->path as $path){
      $this->checkfiles($path);
      }


    }



    function getInput($msg){
      fwrite(STDOUT,"$msg: ");
      return trim(fgets(STDIN));
    }



}



$cbins = new changedbinariesmain;
$cbins->getpath();

/** If CPanel or Plesk are installed, we want to check them too - not always in PATH
*/
if (is_dir('/usr/local/cpanel/bin/')){
  $cbins->setadditional('/usr/local/cpanel/bin/');
}

if (is_dir('/usr/local/psa/bin/')){
  $cbins->setadditional('/usr/local/psa/bin/');
}


// Make sure we check our own integrity 
$cbins->addfile(__FILE__);

// Load any additional files
if (file_exists(dirname(__FILE__)."/../config/additional_files.cfg")){
  $lines = file(dirname(__FILE__)."/../config/additional_files.cfg");

  foreach($lines as $line){
    if (strpos($line,"#") !== false || strpos($line,"/") === false){
      continue;
    }
    $line = trim($line);

    if (is_dir($line)){
      $cbins->setadditional($line);
      continue;
    }

    $cbins->addfile($line);
  }

}



/** TODO: Implement proper password control
*
*/
if ($argv[1] == "-upd"){

  if ($cbins->getInput("Password") == 1234){
    $cbins->setaction('store');
  }else{
    echo "Incorrect password";
      $alert = "Unauthorised attempt to update file hashes by ".getenv("USER")." at ".date('Y-m-d H:i:s');

      $ssh = getenv('SSH_CLIENT');
      if (!empty($ssh)){
	$alert .= " SSH connection details are $ssh";
      }

    $cbins->notify->alarm($alert);
    die;
  }

}else{
  $cbins->setaction('check');
}



// Start the check
$cbins->init();

?>