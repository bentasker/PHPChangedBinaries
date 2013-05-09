<?php
error_reporting(E_ALL);


// Load the dependencies
require_once('../lib/notify.class.php');
require_once('../lib/Remote.class.php');



class changedbinariesmain{

    protected $path;
    protected $action;
    protected $files = array();
    var $notify;


    function __construct(){
      $this->notify = new changedbinariesNotify;
      $this->remote = new changedbinariesRemote($this->notify);
    }



    /** Get PATH and add to our checklist
    *
    */
    function getpath(){
      $this->path = explode(":",getenv("PATH"));
      return $this->path;
    }


    /** Add an additional directory for checking
    *
    */
    function setadditional($dir){
      if (!in_array($dir,$this->path)){
	$this->path[] = $dir;
      }
    }



    /** Add a file for checking
    *
    */
    function addfile($file){
      if (!file_exists($file)){
	return false;
      }

      $this->files[] = $file;
    }



    /** Set the processing action
    *
    */
    function setaction($act){
      $this->action = $act;
    }



    /** Check all files in a directory and recurse into subdirs
    *
    */
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



    /** Work out the action to apply and then apply to the given file
    *
    */
    function checkfile($file){
      $fn = $this->action."Hash";
      $this->$fn($file);
    }



    /** Update the stored hash for a given file
    *
    * @arg file - string
    *
    * @return void
    */
    function storeHash($file){
      $fname = sha1($file);
      $hash = $this->calcHash($file);

      $remote = $this->remote->updatehash($file,$hash);

      // If remote is false, the remotehash store is disabled in config
      if (!$remote){
	  $this->notify->info("Storing hash for $file in ". dirname(__FILE__)."/../db/$fname.def");

	  $f = fopen(dirname(__FILE__)."/../db/$fname.def",'w');

	  $str = "<?php \$hsh='$hash'; \$store='".time()."';?>";
	  fwrite($f,$str);
	  fclose($f);
      }

    }



    /** Calculate the hash of a file
    *
    * @arg file - string
    *
    * @return string
    *
    */
    function calcHash($file){
      return hash_file('sha512',$file);
    }



    /** Check the hash of a file against it's stored value
    *
    */
    function checkHash($file){

      $cur = $this->calcHash($file);

      list($remote,$stored) = $this->remote->retrievehash($file);

      // If remote is false it means the remotehash store is disabled in the config
      if (!$remote){
	$stored = $this->loadDef($file);
      }

      // Compare the hashes
      if ($cur != $stored){
	$this->notify->alarm("$file has changed");
      }else{
	$this->notify->info("$file unchanged since last update");
      }

    }



    /** Load a files hash from the local database
    *
    */
    function loadDef($file){

      $fname = sha1($file);

      if (!file_exists(dirname(__FILE__)."/../db/$fname.def") || !include(dirname(__FILE__)."/../db/$fname.def")){
	$this->notify->warning("Could not load definition for $file");
	return false;
      }

      return $hsh;
    }



    /** Entry point, sets the ball rolling
    *
    */
    function init(){

      foreach ($this->files as $file){
	  $this->checkfile($file);
      }


      foreach ($this->path as $path){
      $this->checkfiles($path);
      }


    }



    /** Get input of some form from the user
    *
    */
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

  if ($cbins->getInput("Enter YES if you're sure all files are unmodified") == "YES"){
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

}elseif($argv[1] == '-updfile'){

  // Update hash for a single file
  $file = $argv[2];

  if (file_exists($file)){
    $cbins->storeHash($file);
  }else{
    echo "File does not exist";
  }


}else{
  $cbins->setaction('check');
}



// Start the check
$cbins->init();

?>