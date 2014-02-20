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
console.log("Brige Ready : Port "+port);

io.sockets.on('connection', function (socket) {
  
  console.log("New Connexion !");

  socket.on('disconnect', function() {
    console.log("Disconnected");
    if (socket.isUser) roomCount[socket.room]--;
  });

  socket.on('allow', function(order) {
    console.log("Allow order received");
    if(isAutority(order.auth)) {
      userAry[order.userHash] = {allowed:order.access, room:order.room, expire:(getTimestamp()+30)};
      console.log("Allow order accepted : "+order.userHash);
    }
  });

  socket.on('stat', function(order) {
    if(isAutority(order.auth)) {
      socket.emit('stat', {rooms:roomCount,pending:userAry});
    }
  });

  socket.on('subscribe', function(data) {
    if (userAry[data.userHash] != "undefined" && userAry[data.userHash].allowed) {
      socket.room = userAry[data.userHash].room;
      socket.join(socket.room);
      roomCount[socket.room]++;
      socket.isUser = true;
      socket.emit("connected");
    }
    else {
      socket.emit("error", {text:"Access Denied"});
      socket.disconnect();
    }
  });

  socket.on('push', function(order){
    if(isAutority(order.auth)) {
      socket.broadcast.to(order.room).emit(order.data);
    }
  });

  socket.on('sync', function(data){
    if (socket.isUser) {
      socket.broadcast.to(socket.room).emit(data);
    }
  });
});

//-----------------------------------------------------------------------------
function isAutority(auth) {
  return (authoritySecret == auth)
}

//-----------------------------------------------------------------------------
function garbageCollect() {
  // Clean not connected users
  var tmpUserAry = []

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