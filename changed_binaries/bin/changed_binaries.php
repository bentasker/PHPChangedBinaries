<?php
error_reporting(0);


class changedbinariesmain{

function getpath(){
  $this->path = explode(":",getenv("PATH"));
  return $this->path;
}


function setadditional($dir){
  if (!in_array($dir,$this->path)){
    $this->path[] = $dir;
  }
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


    $this->storeHash($trg);

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
  }

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

$cbins->init();









?>