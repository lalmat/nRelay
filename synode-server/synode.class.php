<?php
require_once( __DIR__ . '/vendors/ElephantIO/Client.php');
use ElephantIO\Client as ElephantIOClient;

class Synode {
  private $socketIO;
  private $secret;

  public function __construct($bridgeHost, $secret) {
    $this->secret = $secret;
    $this->socketIO = new ElephantIOClient("http://".$bridgeHost, 'socket.io', 1, false, true, false);
  }

  public function allow($room, $userHash) {
    $msg = new synodeMessage();
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

class synodeMessage {
  public $auth;
  public $room;
  public $access;
  public $userHash;
  public $data;

  public function toJSON() {
    return json_encode($this);
  }
}