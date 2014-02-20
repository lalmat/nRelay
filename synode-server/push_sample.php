<?php
// Once connected with index.php, this how to send a message to all client in room "Home"
// Obviously, this should be a integrated in your existing API.

require_once("synode.class.php");
try {
  $s = new Synode("localhost:1337", "motDePasse");
  $s->push("home","Hello World !");
  unset($s);
}
catch(Exception $e) {
	die("Socket Connexion error:<br /><b>".$e->getMessage()."</b>");
}