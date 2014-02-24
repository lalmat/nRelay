/**
 * SYNODE BRIDGE - Run on nodeJS
 */
// ----------------------------------------------------------------------------
// Configuration

var port      = 1337;
var userAry   = [];
var roomCount = [];
var debug     = true;
var authoritySecret = "mauvaisMotDePasse";

//-----------------------------------------------------------------------------
// Serveur Synode

var io = require("socket.io").listen(port, { log: false });
if (debug) console.log("[SYNODE] Brige listening on port "+port);

io.sockets.on('connection', function (socket) {

  socket.on('disconnect', function() {
    if (debug) console.log("[SYNODE] Socket disconnected");
    if (socket.isUser) {
      if (debug) console.log("[SYNODE] Socket of user "+socket.uid+" removed from "+socket.room);
      roomCount[socket.room]--;
    }
  });

  socket.on('allow', function(order) {
    if (debug) console.log("[SYNODE] Incomming allow...");
    if(isAutority(order.auth)) {
      userAry[order.userHash] = {uid:order.userId, allowed:order.access, room:order.room, expire:(getTimestamp()+30)};
      console.log("[SYNODE] "+order.userHash+" allowed to join "+userAry[order.userHash].room+" until "+userAry[order.userHash].expire);
    } else {
      console.log("[SYNODE] Rejecting authority : "+order.auth);
    }
  });

  //TODO: Enable Bridge Statistics
  /*
  socket.on('stat', function(order) {
    if (debug) console.log("Asking for stats");
    if(isAutority(order.auth)) {
      socket.emit('stat', {rooms:roomCount,pending:userAry});
    }
  });
  */

  socket.on('subscribe', function(data) {
    if (debug) console.log("[SYNODE] Incomming subscription");
    if (userAry[data.userHash] != null && userAry[data.userHash].allowed) {
      socket.room   = userAry[data.userHash].room;
      socket.uid    = userAry[data.userHash].uid;
      socket.isUser = true;
      socket.join(socket.room);
      roomCount[socket.room]++;
      userAry[data.userHash].allowed = false; // Prevent 2 connexions at the same time ! Maybe I will change that method in future...
      console.log("[SYNODE] User "+socket.uid+" accepted in "+socket.room);
      socket.emit("subscribe", {result:true});
    }
    else {
      socket.emit("subscribe", {result:false});
      if (debug) console.log("[SYNODE] Incomming user denied.");
      socket.disconnect();
    }
  });

  socket.on('push', function(order){
    if (debug) console.log("[SYNODE] Incomming Server Push :");
    if(isAutority(order.auth)) {
      if (debug) console.log(order.data);
      socket.broadcast.to(order.room).emit("push",order.data);
    } else {
      if (debug) console.log("-> Push not allowed : bad secret");
    }
  });

  socket.on('sync', function(syncData){
    if (debug) console.log("[SYNODE] Incomming Client Sync :")
    if (socket.isUser) {
      if (debug) console.log(syncData);
      socket.in(socket.room).emit("sync", {uid:socket.uid, data:syncData});
    } else {
      if (debug) console.log("-> Sync not allowed : not a user");
    }
  });

  if (debug) console.log("[SYNODE] New connexion !");
});

setInterval(garbageCollect, 5000);

//-----------------------------------------------------------------------------
/**
 * Check the socket authority to push (Broadcast) messages.
 * @param  {string}  auth [description]
 * @return {Boolean}      [description]
 */
function isAutority(auth) {
  return (authoritySecret == auth); // TODO: Make a better security !
}

//-----------------------------------------------------------------------------
/**
 * Garbage collector, used to clean User & Room array.
 * @return {void}
 */
function garbageCollect() {
  // Clean users array
  var tmpUserAry   = [];
  var curTimestamp = getTimestamp();
  for (var userHash in userAry) {
    if (userAry[userHash].expire-curTimestamp > 0 && userAry[userHash].allowed) {
      tmpUserAry[userHash] = userAry[userHash];
    } else {
      if (debug) console.log("[SYNODE] Removing hash:"+userHash);
    }
  }
  userAry = tmpUserAry;

  // clean unused rooms
  var tmpRoomCount = [];
  for (var room in roomCount) {
    if (roomCount[room]>0) {
      tmpRoomCount[room] = roomCount[room];
    } else {
      if (debug) console.log("[SYNODE] Removing room:"+room);
    }
  }
  roomCount = tmpRoomCount;
}

//-----------------------------------------------------------------------------
/**
 * Return a timestamp (in sec.)
 * @return {int} [timestamp]
 */
function getTimestamp() {
  return Math.round(+new Date() / 1000);
}