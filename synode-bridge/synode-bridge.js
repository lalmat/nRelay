// Synode - Proof of concept
// Mathieu LALLEMAND (@lalmat) - 2014
// Prototype de bridge temps réel authentifié

// ----------------------------------------------------------------------------
// Configuration

var port      = 1337;
var userAry   = [];
var roomCount = [];
var debug     = true;
var authoritySecret = "motDePasse";

//-----------------------------------------------------------------------------
// Serveur Synode

var io = require("socket.io").listen(port, { log: false });
if (debug) console.log("Brige Ready : Port "+port);

io.sockets.on('connection', function (socket) {

  socket.on('disconnect', function() {
    if (debug) console.log("Disconnected");
    if (socket.isUser) roomCount[socket.room]--;
  });

  socket.on('allow', function(order) {
    if (debug) console.log("Allow order received");
    if(isAutority(order.auth)) {
      userAry[order.userHash] = {allowed:order.access, room:order.room, expire:(getTimestamp()+30)};
      console.log("Allow order accepted : "+order.userHash);
    }
  });

  socket.on('stat', function(order) {
    if (debug) console.log("Asking for stats");
    if(isAutority(order.auth)) {
      socket.emit('stat', {rooms:roomCount,pending:userAry});
    }
  });

  socket.on('subscribe', function(data) {
    if (debug) console.log("Asking for Subscription");
    if (userAry[data.userHash] != "undefined" && userAry[data.userHash].allowed) {
      socket.room = userAry[data.userHash].room;
      console.log("Joining "+socket.room);
      socket.join(socket.room);
      roomCount[socket.room]++;
      socket.isUser = true;
      socket.emit("subscribe", {result:true});
      if (debug) console.log("Subscription accepted");
    }
    else {
      socket.emit("subscribe", {result:false});
      if (debug) console.log("Subscription denied");
      socket.disconnect();
    }
  });

  socket.on('push', function(order){
    if (debug) console.log("Push from server : "+order.data);
    if(isAutority(order.auth)) {
      socket.broadcast.to(order.room).emit("push",order.data);
    }
  });

  socket.on('sync', function(data){
    if (debug) console.log("Sync from user");
    if (socket.isUser) {
      socket.broadcast.to(socket.room).emit("sync", data);
    }
  });

  if (debug) console.log("Incomming connexion !");
});

setInterval(garbageCollect, 5000);

//-----------------------------------------------------------------------------
function isAutority(auth) {
  return (authoritySecret == auth)
}

//-----------------------------------------------------------------------------
function garbageCollect() {
  // Clean not connected users
  var tmpUserAry   = [];
  var curTimestamp = getTimestamp();
  for (var userHash in userAry) {
    if (userAry[userHash].expire < curTimestamp) tmpUserAry[userHash] = userAry[userHash];
  }
  userAry = tmpUserAry;

  // clean unused rooms
  var tmpRoomCount = [];
  for (var room in roomCount) {
    if (roomCount[room]>0) tmpRoomCount[room] = roomCount[room]
  }
  roomCount = tmpRoomCount;
}

//-----------------------------------------------------------------------------
function getTimestamp() {
  return Math.round(+new Date() / 1000);
}