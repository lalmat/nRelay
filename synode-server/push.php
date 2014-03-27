<?php
/**
 * SYNODE Server - Server Broadcast Demo
 */
session_start();
require_once("config.inc.php");    // Synode Bridge configuration
require_once(__DIR__."/vendors/Synode/synode.class.php");  // Synode Server API

if (!isset($_GET['MSG']) || trim($_GET['MSG']) == "") {
  die("{result:false, error:'Message Could not be empty !'}");
}

$uid = session_id();
$msg = $_GET['MSG'];

try {
  $s = new Synode(SYN_HOST, SYN_SECRET);
  $s->push($uid, "indexRoom", "say", $msg);
  unset($s);
}
catch(Exception $e) {
	die("{result:false, error:'Socket Connexion error:<br /><b>".$e->getMessage()."</b>'}");
}
echo "{result:true}";