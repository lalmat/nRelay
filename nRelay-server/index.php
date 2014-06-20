<?php
/**
 * nRelay demo - Server Side
 */
session_start();

require_once("config.inc.php");                            // Synode Bridge configuration
require_once(__DIR__."/vendors/nRelay/nRelay.class.php");  // Synode Server API

// This code tell the nRelay Bridge that he can accept the user with this hash.
// When the bridge receive this hash connexion, it will place the user in
// the specified communication room ('home' in this demo).
try {
  $s = new nRelay(NRLY_HOST, NRLY_SECRET);
  $userHash = $s->allow(session_id(), "home");
  unset($s);
}
catch(Exception $e) {
	die("nRelay Error: nRelay Bridge seems to be offline.");
}

// Template values
$assignAry['{$NLRY_HOST}'] = NRLY_HOST;
$assignAry['{$USER_HASH}'] = $userHash;

// Here is maybe the world's smallest template engine !
// - Absolutly not optimized ! Just for this demo ! -
$html = file_get_contents(__DIR__."/assets/index.tpl");
foreach($assignAry as $key=>$value) { $html = str_replace($key, $value, $html); }

// Send HTML to browser.
echo $html;