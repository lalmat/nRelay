<?php
require_once( __DIR__ . '/../ElephantIO/Client.php');
use ElephantIO\Client as ElephantIOClient;

/**
 * SYNODE SERVER CLASS - PHP -
 */
class Synode {
  private $socketIO;
  private $secret;

  public function __construct($bridgeHost, $secret) {
    $this->secret = $secret;
    $this->socketIO = new ElephantIOClient($bridgeHost, 'socket.io', 1, false, true, false);
  }

  public function allow($userId, $room, $userHash) {
    $msg = new synodeMessage();
    $msg->userId = $userId;
    $msg->auth = $this->secret;
    $msg->room = $room;
    $msg->access = true;
    $msg->userHash = $userHash;
    $this->send('allow',$msg);
  }

  public function push($room, $message) {
    $msg = new synodeMessage();
    $msg->auth = $this->secret;
    $msg->room = $room;
    $msg->data = $message;
    $this->send("push",$msg);
  }

  private function send($code, synodeMessage $msg) {
    $this->socketIO->init();
    $this->socketIO->emit($code, $msg);
    $this->socketIO->close();
  }
}

// TODO: Clean this message, maybe in more message, maybe in a more abstract message...
class synodeMessage {
  public $auth;
  public $room;
  public $access;
  public $userHash;
  public $data;
  public $userId;

  public function toJSON() {
    return json_encode($this);
  }
}