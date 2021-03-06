<?php
/** changed_binaries
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

error_reporting(0);
define('_PROGPATH',dirname(__FILE__)."/../");

// Load the dependencies
require_once(_PROGPATH.'lib/notify.class.php');
require_once(_PROGPATH.'lib/Remote.class.php');



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
      $path = getenv("PATH");
      $this->notify->debug('Adding Path ('.$path.') to check');
      $this->path = explode(":",$path);

      // Add the PHP libraries directory
      $libraries = explode(":",ini_get('include_path'));

      $this->path = array_merge($libraries,$this->path);

      return $this->path;
    }


    /** Add an additional directory for checking
    *
    */
    function setadditional($dir){
      if (!in_array($dir,$this->path)){
	$this->notify->debug('Adding '.$dir.' to checkPath');
	$this->path[] = $dir;
      }
    }



    /** Add a file for checking
    *
    */
    function addfile($file){
      if (!file_exists($file)){
	$this->notify->debug('Not Adding '.$file.' to checkPath (ENOENT)');
	return false;
      }
      $this->notify->debug('Adding File '.$file.' to checkPath');
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
      if (!is_dir($dir) || $dir=='.' || !$h = opendir($dir)){
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
      set_time_limit(60); // Make sure we don't hit any max execution limits
      $fn = $this->action."Hash";
      $this->notify->debug('Running  '.$fn.' on '.$file);
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

      if ($remote == 'KEYFAIL'){
	exit;
      }

      // If remote is false, the remotehash store is disabled in config
      if (!$remote){
	  $this->notify->info("Storing hash for $file");
	  $this->notify->debug("Storing hash for $file in ". dirname(__FILE__)."/../db/$fname.def");

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

      list($remote,$stored) = $this->remote->retrievehash($file,$cur);

      // If remote is false it means the remotehash store is disabled in the config
      if (!$remote){
	  $stored = $this->loadDef($file);
	  $this->notify->debug("Comparing $cur to $stored for $file");

	  // Compare the hashes
	  if ($cur != $stored){
	    $this->notify->alarm("$file has changed");
	  }else{
	    $this->notify->info("$file unchanged since last update");
	  }
      }

    }



    /** Load a files hash from the local database
    *
    */
    function loadDef($file){

      $fname = sha1($file);
      $this->notify->debug("Loading ".dirname(__FILE__)."/../db/$fname.def");
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

      foreach ($this->files as $k=>$file){
	  $this->checkfile($file);
	  unset($this->files[$k]); // No point keeping the path in memory.
      }

      foreach ($this->path as $k=>$path){
	  $this->checkfiles($path);
	  unset($this->path[$k]); // No point keeping the path in memory.
      }


    }



    /** Get input of some from from the user
    *
    */
    function getInput($msg){
      fwrite(STDOUT,"$msg: ");
      return trim(fgets(STDIN));
    }



}


ini_set('memory_limit','256M'); // Make sure we don't hit an upper limit. See PHPCBA-4

$cbins = new changedbinariesmain;

$cbins->notify->debug('System loaded '. date('Y-m-d H:i:s'));
$cbins->notify->debug('Called with arguments'.$argv[0]);

$cbins->getpath();

// Make sure we check our own integrity 
$cbins->notify->debug('Adding self to checkpath');
$cbins->setadditional(dirname(__FILE__)."/../lib/");
$cbins->addfile(__FILE__);



/** If CPanel or Plesk are installed, we want to check them too - not always in PATH
*/
if (is_dir('/usr/local/cpanel/bin/')){
  $cbins->setadditional('/usr/local/cpanel/bin/');
}

if (is_dir('/usr/local/psa/bin/')){
  $cbins->setadditional('/usr/local/psa/bin/');
}


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

  // Get the config file back out of memory
  unset($lines);

}

$f = array_search('--updfile',$argv);
// Check what we need to do
if (in_array("--upd",$argv)){

  if ($cbins->getInput("Enter YES if you're sure all files are unmodified") == "YES"){

    $cbins->notify->debug('Setting action to storeHash');
    $cbins->setaction('store');
  }else{
    $cbins->notify->info('Aborting');
    die;
  }

}elseif ($f !== false){

  // Update hash for a single file
  $idx = $f+1;
  $file = $argv[$idx];

  if (file_exists($file)){
    $cbins->notify->debug('Setting action to storeHash');
    $cbins->setaction('store');
    $cbins->checkfile($file);
    die;
  }else{
    echo "File does not exist";
  }


}else{
  $cbins->notify->debug('Setting action to checkHash');
  $cbins->setaction('check');
}



// Start the check
$cbins->init();

?>
