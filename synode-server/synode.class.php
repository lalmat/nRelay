<?php
require_once( __DIR__ . '/vendors/ElephantIO/Client.php');
use ElephantIO\Client as ElephantIOClient;

class Synode {
  private $socketIO;
  private $secret;

  public function __construct($bridgeHost, $secret) {
    $this->secret = $secret;
    $this->socketIO = new ElephantIOClient("http://".$bridgeHost, 'socket.io', 1, false, true, true);
  }

  public function allow($userHash, $room) {
    $msg = new synodeMessage();
    $msg->auth = $this->secret;
    $msg->room = $room;
    $msg->access = true;
    $msg->userHash = $userHash;
    $this->send('allow',$msg);
  }

  public function push($message, $room) {
    $msg = new synodeMessage();
    $msg->auth = $this->secret;
    $msg->room = $room;
    $msg->data = $message;
    $this->send("push",$msg);
  }

  private function send($code, synodeMessage $msg) {
    $this->socketIO->init();
    $this->socketIO->emit($code, $msg);
    //$this->socketIO->emit(ElephantIOClient::TYPE_EVENT, null, null, json_encode(array("name"=>$code, "args"=>$msg)));
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