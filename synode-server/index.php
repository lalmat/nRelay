<?php
// This is how to initiate a connexion to the realtime bridge :

// Generate a token :
$hash = uniqid();

require_once("synode.class.php");
try {
  $s = new Synode("localhost:1337", "motDePasse");
  $s->allow("home",$hash);
  unset($s);
}
catch(Exception $e) {
	die("Socket Connexion error, try to refresh:<br /><b>".$e->getMessage()."</b>");
}

$html = str_replace("{USER_HASH}", $hash, file_get_contents(__DIR__."/assets/demo.tpl"));
echo $html;