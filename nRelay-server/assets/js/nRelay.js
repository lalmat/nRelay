/**
 * nRelay Javascript Client Object
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * Mathieu LALLEMAND (@lalmat) - 2014
 * MIT Licence
 */
function nRelay(host, hash) {

  this.bridgeHost = host;
  this.socket     = null;
  this.connected  = false;
  this.debug      = true;

  /**
   * Start the realtime communication with nRelay Bridge.
   * @param  {[function]} onPush
   * @param  {[function]} onSync
   * @return {[void]}
   */
  this.start = function (onPush, onSync) {
    var that = this;

    if (this.debug) { console.log("nRelay: Connecting '" + this.bridgeHost + "'"); }
    this.socket = io.connect(this.bridgeHost, {'force new connection': true, 'secure':true});

    // Handshake method
    this.socket.on('connect', function () {
      if (that.debug) { console.log("nRelay: Socket connected, subscribe using [" + hash + "]"); }
      that.socket.emit("subscribe", {userHash : hash});
    });

    // Server Push : Message from API Server
    this.socket.on('push', function (msg) {
      if (that.debug) { console.log("nRelay: Server Push Received :"); console.log(msg); }
      onPush(msg);
    });

    // Client Sync : Message from nRelay User in the same room
    this.socket.on('sync', function (msg) {
      if (that.debug) { console.log("nRelay: Client Sync Received :"); console.log(msg); }
      onSync(msg);
    });

    // Result of Handshaking
    this.socket.on("subscribe", function (data) {
      that.connected = data.result;
      console.log("nRelay: connection : " + that.connected);
    });

    // Connexion reset.
    this.socket.on("disconnect", function () {
      console.log("nRelay: Socket closed");
      that.socket.disconnect();
      that.socket = null;
      // Reconnecting
      setTimeout(function () { that.start(onPush, onSync); }, 2000);
    });
    return that;
  };

  /**
   * Broadcast data to all nRelay sockets in the same room as this socket
   * @param  {[object]} data
   */
  this.sync = function (myAction, myData) {
    var that = this;
    if (this.connected) { this.socket.emit("sync", {action:myAction, data:myData}); }
    else { console.log("nRelayError: Not connected."); }
    return that;
  };

  return this;
}