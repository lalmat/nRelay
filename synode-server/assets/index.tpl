<!DOCTYPE html>
<html>
	<head>
		<title>Synode Client</title>
		<script type="text/javascript" src="assets/js/socket.io.min.js"></script>
		<script type="text/javascript" src="assets/js/jquery.min.js"></script>
		<script type="text/javascript" src="assets/js/synode.js"></script>
		<script type="text/javascript">

			// Don't use jQuery for simple element selection, it's bad !
		  function domElt(eltId) { return document.getElementById(eltId); }

		 	// Server Push function
		 	function doPush() {
		 		var url = "push.php?MSG="+encodeURIComponent(domElt('clientText').value);
		 		$.get(url, function(r){
		 			if (!r.result) { console.log('error'); }
		 			domElt('clientText').value = "";
		 			domElt('clientText').focus();
		 		},'json');
		 	}

			// This is what to do when ServerPush is received:
		  function myPush(msg) {
		  	console.log("MyPush:"+msg);
		  	domElt("log").innerHTML = "<b>SERVER ("+msg.uid+") :</b>"+msg.action+":"+msg.data+"<br />"+domElt("log").innerHTML;
		  }

		  // Client Sync function
		 	function doSync() {
		 		synDemo.sync("say", domElt('clientText').value);
		 		domElt('clientText').value = "";
		 		domElt('clientText').focus();
		 	}

		  // This is what to do when ClientSync is received:
		  function mySync(msg) {
		  	domElt("log").innerHTML = "<b>CLIENT ("+msg.uid+") :</b>"+msg.action+":"+msg.data+"<br />"+domElt("log").innerHTML;
		  }
		
		  // This line ignite the realtime communication.
		 	var synDemo = new synode("{$SYN_HOST}","{$USR_HASH}").start(myPush, mySync);
		 	
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