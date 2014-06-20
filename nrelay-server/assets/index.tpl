<!DOCTYPE html>
<html>
	<head>
		<title>Synode Client</title>
		<script type="text/javascript" src="assets/js/socket.io.min.js"></script>
		<script type="text/javascript" src="assets/js/jquery.min.js"></script>
		<script type="text/javascript" src="assets/js/nRelay.js"></script>
		<script type="text/javascript">

			// Don't use jQuery for simple element selection, it's bad !
		  function domId(eltId) { return document.getElementById(eltId); }

		 	// Server Push function
		 	function doPush() {
		 		var url = "push.php?MSG="+encodeURIComponent(domId('clientText').value);
		 		$.get(url, function(r){
		 			if (!r.result) { console.log('error'); }
		 			domId('clientText').value = "";
		 			domId('clientText').focus();
		 		},'json');
		 	}

			// This is what to do when ServerPush is received:
		  function myPush(msg) {
		  	console.log("MyPush:"+msg);
		  	domId("log").innerHTML = "<b>SERVER PUSH ("+msg.uid+") :</b>"+msg.action+":"+msg.data+"<br />"+domId("log").innerHTML;
		  }

		  // This is what to do when ClientSync is received:
		  function mySync(msg) {
		  	domId("log").innerHTML = "<b>CLIENT SYNC ("+msg.uid+") :</b>"+msg.action+":"+msg.data+"<br />"+domId("log").innerHTML;
		  }
		
		  // Client Sync function
		 	function doSync() {
		 		synDemo.sync("say", domId('clientText').value);
		 		domId('clientText').value = "";
		 		domId('clientText').focus();
		 	}

		  // This line ignite the realtime communication.
		 	var nRelayDemo = new nRelay("{$NRLY_HOST}","{$USER_HASH}").start(myPush, mySync);
		 	
		</script>
	</head>
	<body>
		<h1>Simple SynodeJS demo</h1>
		<input type="text" id="clientText" /> 
		<button type="button" onclick="doPush()">Push</button>
		<button type="button" onclick="doSync()">Sync</button>
		<hr />
		<div id="log"></div>
	</body>
</html>