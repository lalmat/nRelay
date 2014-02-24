<!DOCTYPE html>
<html>
	<head>
		<title>Synode Client</title>
		<script type="text/javascript" src="assets/js/socket.io.min.js"></script>
		<script type="text/javascript" src="assets/js/synode.js"></script>
		<script type="text/javascript">

			// This is what to do when ServerPush is received:
		  function myPush(msg) {
		  	console.log("MyPush:"+msg);
		  	document.getElementById("log").innerHTML = "<b>SERVER : </b>"+msg+"<br />"+document.getElementById("log").innerHTML;
		  }

		  // This is what to do when ClientSync is received:
		  function mySync(uid, data) {
		  	document.getElementById("log").innerHTML = "<b>CLIENT ("+uid+") : </b>"+data+"<br />"+document.getElementById("log").innerHTML;
		  }

		  // This ignite the realtime communication :
		 	var synDemo = new synode("{$SYN_HOST}","{$USR_HASH}").start(myPush, mySync);
		 	
		 	// Client Sync function
		 	function doSync() {
		 		synDemo.sync(document.getElementById('clientSyncText').value);
		 		document.getElementById('clientSyncText').value = "";
		 		document.getElementById('clientSyncText').focus();
		 	}

		</script>
	</head>
	<body>
	<h1>SynodeJS demo</h1>
	<input type="text" id="clientSyncText" /><button type="button" onclick="doSync()">Send to my room</button>
	<div id="log"></div>
	</body>
</html>