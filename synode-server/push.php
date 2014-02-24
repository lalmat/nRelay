<?php
/**
 * SYNODE Server - Server Broadcast Demo
 */
require_once("config.inc.php");    // Synode Bridge configuration
require_once(__DIR__."/vendors/Synode/synode.class.php");  // Synode Server API

try {
  $msg = "Server Message @ ".date("H:i:s")." : Hello World !";
  echo $msg;
  $s = new Synode(SYN_HOST, SYN_SECRET);
  $s->push("index_room",$msg);
  unset($s);
}
catch(Exception $e) {
	die("Socket Connexion error:<br /><b>".$e->getMessage()."</b>");
}