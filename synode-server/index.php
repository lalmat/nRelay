<?php
/**
 * SYNODE DEMO - API Server Side
 */
session_start();

require_once("config.inc.php");                            // Synode Bridge configuration
require_once(__DIR__."/vendors/Synode/synode.class.php");  // Synode Server API

// This code tell the Synode Bridge that he can accept the user with this hash.
// When the bridge receive this hash connexion, it will place the user in 
// the specified communication room ('home' in this demo).
try {
  $s = new Synode(SYN_HOST, SYN_SECRET);
  $userHash = $s->allow(session_id(), "indexRoom");
  unset($s);
}
catch(Exception $e) {
	die("SynodeError: Synode Bridge seems to be offline.");
}

// Template values
$assignAry['{$SYN_HOST}'] = SYN_HOST;
$assignAry['{$USR_HASH}'] = $userHash;

// Here is maybe the world's smallest template engine ! 
// - Absolutly not optimized ! Just for this demo ! -
$html = file_get_contents(__DIR__."/assets/index.tpl");
foreach($assignAry as $key=>$value) { $html = str_replace($key, $value, $html); }

// Send HTML to browser.
echo $html;