  /**
 * nRelay BRIDGE - Run on nodeJS
 */

// ----------------------------------------------------------------------------
// Configuration

var authoritySecret = "mauvaisMotDePasse";    // API password

var debug     = true;           // Enable / disable debug mode
var port      = 1337;           // Listening port
var userAry   = new Array();    // Array of users tokens allowed for connexion
var roomCount = new Array();    // Array of rooms with number of user connected to.
var gcTimeout = 5;              // Garbage Collector Interval (in sec.)
var cxTimeout = 30;             // Max duration allowed to connect (in sec.)

var HTTPS = new Array(); 
HTTPS['enabled'] = true;             // Activation du HTTPS
HTTPS['prvKey'] = "ssl/node.key";    // Clé privée
HTTPS['pubKey'] = "ssl/node.crt";    // Clé publique
HTTPS['optCA']  = false; //"ssl/node-ca.crt"; // Clé publique Autorité de Certification

//-----------------------------------------------------------------------------
// Serveur nRelay
var jsSHA  = require("jssha"); 
var app = null;
var io = null;

if (HTTPS['enabled']) {
  if (debug) console.log("[nRelay] SSL Mode ON");
  var fs    = require("fs");

  var ssl = {
    "ca":   false,
    "key":  false,
    "cert": false,
  }
  if (HTTPS['optCA'] != false) ssl.ca = HTTPS['optCA'];
  ssl.key  = fs.readFileSync(HTTPS['prvKey']),
  ssl.cert = fs.readFileSync(HTTPS['pubKey']),

  app = require("https").createServer(ssl);
  io  = require("socket.io")(app);
  app.listen(port);
} 

else {
  if (debug) console.log("[nRelay] SSL Mode OFF");
  io  = require('socket.io').listen(port);
}

if (debug) console.log("[nRelay] Brige listening on port "+port);
io.on('connection', function (socket) {

  socket.on('disconnect', function() {
    if (debug) console.log("[nRelay] Socket disconnected");
    if (socket.isUser) {
      if (debug) console.log("[nRelay] Socket of user "+socket.uid+" removed from "+socket.room);
      roomCount[socket.room]--;
    }
  });

  socket.on('allow', function(order) {
    if (debug) console.log("[nRelay] Incomming allow...");
    if(isAutority(order.tokenSalt, order.tokenTest)) {
      userAry[order.userHash] = { uid:order.userId, allowed:order.access, room:order.room, expire:(getTimestamp()+30) };
      if (debug) console.log("[nRelay] "+order.userHash+" allowed to join "+userAry[order.userHash].room+" until "+userAry[order.userHash].expire);
    } else {
      if (debug) console.log("[nRelay] Rejecting authority : "+order.tokenTest+" failed.");
    }
  });

  socket.on('subscribe', function(data) {
    if (debug) console.log("[nRelay] Incomming subscription");
    if (userAry[data.userHash] != null && userAry[data.userHash].allowed) {
      socket.uid    = userAry[data.userHash].uid;
      socket.room   = userAry[data.userHash].room;
      socket.isUser = true;
      socket.join(socket.room);
      roomCount[socket.room] = (roomCount[socket.room] > 0) ? roomCount[userAry[data.userHash].room]++ : 1;
      userAry[data.userHash].allowed = false; // Prevent multiple connexions.
      if (debug) console.log("[nRelay] User "+socket.uid+" allowed in "+socket.room+" ("+roomCount[socket.room]+" users).");
      socket.emit("subscribe", {result:true});
    }
    else {
      socket.emit("subscribe", {result:false});
      if (debug) console.log("[nRelay] Incomming user denied.");
      socket.disconnect();
    }
  });

  socket.on('push', function(order){
    if (debug) console.log("[nRelay] Incomming Server Push ("+order.userId+" => "+order.room+"):");
    if(isAutority(order.tokenSalt, order.tokenTest)) {
      //if (debug) console.log(order.data);
      io.sockets.in(order.room).emit("push", {uid:order.userId, action:order.action, data:order.data});
    } else {
      if (debug) console.log("[nRelay] Server Push not allowed : bad password");
    }
  });

  socket.on('sync', function(syncData){
    if (debug) console.log("[nRelay] Incomming Client Sync ("+socket.uid+" => "+socket.room+"):")
    if (socket.isUser) {
      //if (debug) console.log(syncData);
      io.sockets.in(socket.room).emit("sync", {uid:socket.uid, action:syncData.action, data:syncData.data});
    } else {
      if (debug) console.log("[nRelay] Sync not allowed : not a user");
    }
  });

  if (debug) console.log("[nRelay] New connexion !");
});

setInterval(garbageCollect, gcTimeout*1000);

//-----------------------------------------------------------------------------
/**
 * Check the socket authority to push (Broadcast) messages.
 * @param  {string}  token1   Token de Salt
 * @param  {string}  token2   Token de Test
 * @return {Boolean}      [description]
 */
function isAutority(token1, token2) {
  var crypticSecret = token1+authoritySecret;
  var crypticToken  = new jsSHA(crypticSecret, "TEXT");
  var crypticSHA256 = crypticToken.getHash("SHA-256", "HEX");
  return (crypticSHA256 == token2);
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
      if (debug) console.log("[nRelay] Removing hash:"+userHash);
    }
  }
  userAry = tmpUserAry;

  // clean unused rooms
  var tmpRoomCount = [];
  for (var room in roomCount) {
    if (roomCount[room]>0) {
      tmpRoomCount[room] = roomCount[room];
    } else {
      if (debug) console.log("[nRelay] Removing room:"+room);
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
