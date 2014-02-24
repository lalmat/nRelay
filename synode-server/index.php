<?php
/**
 * SYNODE DEMO - API Server Side
 */
require_once("config.inc.php");    // Synode Bridge configuration
require_once(__DIR__."/vendors/Synode/synode.class.php");  // Synode Server API

// Template values
$userHash = uniqid();
$assignAry['{$SYN_HOST}'] = SYN_HOST;
$assignAry['{$USR_HASH}'] = $userHash;

// This code tell the Synode Bridge that he can accept the user with this hash.
// When the bridge receive this hash connexion, it will place the user in 
// the specified communication room ('home' in this demo).
try {
  $s = new Synode(SYN_HOST, SYN_SECRET);
  $s->allow("anonymous","index_room",$userHash);
  unset($s);
}
catch(Exception $e) {
	die("SynodeError: Synode Bridge seems to be offline.");
}

// Here is the world's smallest template engine ! 
// - Absolutly not optimized ! Just for this demo ! -
$html = file_get_contents(__DIR__."/assets/index.tpl");
foreach($assignAry as $key=>$value) { $html = str_replace($key, $value, $html); }

// Job done.
echo $html;