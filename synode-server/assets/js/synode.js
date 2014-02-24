/**
 * Synode Javascript Client Object
 * ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
 * Mathieu LALLEMAND (@lalmat) - 2014
 * MIT Licence
 */
function synode(host, hash) {

  this.bridgeHost = host;
  this.socket     = null;
  this.connected  = false;
  this.debug      = true;

  /**
   * Start the realtime communication with Synode Bridge.
   * @param  {[function]} onPush
   * @param  {[function]} onSync
   * @return {[void]}
   */
  this.start = function (onPush, onSync) {
    var that = this;

    if (this.debug) { console.log("Synode: Connecting '" + this.bridgeHost + "'"); }
    this.socket = io.connect(this.bridgeHost);

    // Handshake method
    this.socket.on('connect', function () {
      if (that.debug) { console.log("Synode: Socket connected, subscribe using [" + hash + "]"); }
      that.socket.emit("subscribe", {userHash : hash});
    });

    // Server Push : Message from API Server
    this.socket.on('push', function (msg) {
      if (that.debug) { console.log("Synode: Server Push Received :"); console.log(msg); }
      onPush(msg);
    });

    // Client Sync : Message from Synode User in the same room
    this.socket.on('sync', function (msg) {
      if (that.debug) { console.log("Synode: Client Sync Received :"); console.log(msg); }
      onSync(msg.uid, msg.data);
    });

    // Result of Handshaking
    this.socket.on("subscribe", function (data) {
      that.connected = data.result;
      console.log("Synode: connection : " + that.connected);
    });

    // Connexion reset.
    this.socket.on("disconnect", function () {
      console.log("Synode: Socket closed");
      that.socket.disconnect();
      that.socket = null;
      // Reconnecting
      setTimeout(function () { that.start(onPush, onSync); }, 2000);
    });
    return that;
  };

  /**
   * Broadcast data to all Synode sockets in the same room as this socket
   * @param  {[object]} data
   */
  this.sync = function (data) {
    var that = this;
    if (this.connected) { this.socket.emit("sync", data); }
    else { console.log("SynodeError: Not connected."); }
    return that;
  };

  return this;
}