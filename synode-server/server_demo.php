<?php
// Configuration
$hash = uniqid();

require_once("synode.class.php");
try {
  $s = new Synode("localhost:1337", "motDePasse");
  $s->allow($hash,"home");
  //unset($s);
}
catch(Exception $e) {
	die("Socket Connexion error:<br /><b>".$e->getMessage()."</b>");
}

echo "UID: ".$hash." called.".PHP_EOL;

/*
$html = str_replace("{USER_HASH}", $hash, file_get_contents(__DIR__."/assets/demo.tpl"));
echo $html;
*/