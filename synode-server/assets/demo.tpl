<!DOCTYPE html>
<html>
	<head>
		<title>Synode Client</title>
		<script type="text/javascript" src="assets/socket.io.min.js"></script>
		<script type="text/javascript" src="assets/synode.js"></script>
		<script type="text/javascript">
		  var syno = new synode("http://localhost:1337","{USER_HASH}");
			syno.pushCallback = function(data) {
				console.log(data);
			}
			syno.start();
		</script>
	</head>
	<body>
	<h1>SynodeJS demo</h1>
	<div id="log"></div>
	</body>
</html>