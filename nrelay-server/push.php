<?php
/**
 * nRelay Server - Server Broadcast Demo
 */
session_start();
require_once("config.inc.php");    												// nRelay Bridge configuration
require_once(__DIR__."/vendors/nRelay/nRelay.class.php"); // nRelay Server API

if (!isset($_GET['MSG']) || trim($_GET['MSG']) == "") {
  die("{result:false, error:'Message Could not be empty !'}");
}

$uid = session_id();
$msg = $_GET['MSG'];

try {
  $s = new nRelay(NRLY_HOST, NRLY_SECRET);
  $s->push($uid, "home", "say", $msg);
  unset($s);
}
catch(Exception $e) {
	die("{result:false, error:'Socket Connexion error:<br /><b>".$e->getMessage()."</b>'}");
}
echo "{result:true}";