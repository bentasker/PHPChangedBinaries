<?php
error_reporting(0);


class changedbinariesmain{

    protected $path;
    protected $action;


    function getpath(){
      $this->path = explode(":",getenv("PATH"));
      return $this->path;
    }


    function setadditional($dir){
      if (!in_array($dir,$this->path)){
	$this->path[] = $dir;
      }
    }


    function setaction($act){
      $this->action = $act;
    }


    function checkfiles($dir){
      $fn = $this->action."Hash";

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


	$this->$fn($trg);

      }

    }



    function storeHash($file){
      $fname = sha1($file);
      $hash = $this->calcHash($file);
      echo "Storing hash for $file in ". dirname(__FILE__)."/../db/$fname.def\n";

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
	echo "ALERT: $file has changed\n";
      }else{
	echo "FINE: $file unchanged since last update\n";
      }

    }


    /** TODO: Do something slightly more useful!
    *
    */
    function fileChanged($file){
      echo "ALERT: $file has changed\n";

    }


    /** TODO: Do something slightly more useful!
    *
    */
    function fileUnChanged($file){
      echo "FINE: $file unchanged since last update";
    }



    function loadDef($file){

      $fname = sha1($file);

      if (!include(dirname(__FILE__)."/../db/$fname.def")){
	echo "WARN: Could not load definition for $file\n";
	return false;
      }

      return $hsh;
    }



    function init(){
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


/** TODO: Implement proper password control
*
*/
if ($argv[1] == "-upd"){

  if ($cbins->getInput("Password") == 1234){
    $cbins->setaction('store');
  }else{
    echo "Incorrect password";
    die;
  }

}else{
  $cbins->setaction('check');
}




$cbins->init();









?>