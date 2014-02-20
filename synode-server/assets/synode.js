function synode(host, hash) {

  this.bridgeHost = host;
  this.socket     = null;

  this.isConnected = false;

  this.pushCallback = null;
  this.syncCallback = null;

  this.start = function foo() {
		this.socket = io.connect(this.bridgeHost);

		this.socket.on('connect', function() {
			this.emit("subscribe", {userHash:hash});
	  });

		this.socket.on('push', function(data) {
			console.log("Push Received :"+data);
			//if (this.pushCallback != null) this.pushCallback(data);
		});

		this.socket.on('sync', function(data) {
			console.log("Sync Received :"+data);
			//if (this.syncCallback != null) this.syncCallback(data);
		});

		this.socket.on("subscribe", function(data) {
			isConnected = data.result;
			console.log("isConnected : "+isConnected);
		});

		this.socket.on("disconnect", function(data) {
			console.log("Socket closed");
			this = null;
		});
	}
}