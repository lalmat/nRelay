<?php
require_once( __DIR__ . '/../ElephantIO/Client.php');
use ElephantIO\Client as ElephantIOClient;

/**
 * SYNODE SERVER CLASS - PHP -
 */
class Synode {
  private $socketIO;
  private $secret;

  /**
   * Constructor
   * @param [string] $bridgeHost [URL of the SynodeBridge]
   * @param [string] $secret     [API Password]
   */
  public function __construct($bridgeHost, $secret) {
    $this->secret = $secret;
    $this->socketIO = new ElephantIOClient($bridgeHost, 'socket.io', 1, false, true, false);
  }

  /**
   * Destructor
   */
  public function __destruct() {
    $this->secret = null;
    unset($this->socketIO);
  }

  /**
   * Allow a userId to connect the SynodeBridge
   * @param  [int]    $userId [ID of the user]
   * @param  [string] $room   [Room to connect to]
   * @return [string]         [User Hash that should be used to connect to the SynodeBridge]
   */
  public function allow($userId, $room) {
    $auth = new synodeAuth();
    $this->fillSkel($userId, $room, $auth);

    $auth->access   = true;
    $auth->userHash = uniqid();
    
    $this->send('allow',$auth);
    return $auth->userHash;
  }

  /**
   * Send a message to all user connected through the Synode Bridge to the room
   * @param  [int]    $userId [ID of the user]
   * @param  [string] $room   [Room to connect to]
   * @param  [string] $action [Trigged action]
   * @param  [string] $data   [Trigged data]
   * @return [boolean]        []
   */
  public function push($userId, $room, $action, $data) {
    $msg = new synodePush();
    $this->fillSkel($userId, $room, $msg);

    $msg->action = $action;
    $msg->data   = $data;
    
    $this->send("push",$msg);
    return true;
  }

  /**
   * Deep magic from Elephant.IO
   * @param  [type]        $code [description]
   * @param  synodeMessage $msg  [description]
   * @return [type]              [description]
   */
  private function send($code, synodeSkel $msg) {
    $this->socketIO->init();
    $this->socketIO->emit($code, $msg);
    $this->socketIO->close();
  }

  /**
   * Skeleton message filler
   * @param  [int]      $userId [ID of the User]
   * @param  [string]   $room   [Room]
   * @param  synodeSkel $s      []
   */
  private function fillSkel($userId, $room, synodeSkel $s) {
    $s->userId = $userId;
    $s->room   = $room;
    $s->tokenSalt = md5(uniqid());
    $s->tokenTest = hash("sha256", $s->tokenSalt.$this->secret);
  }

}

/**
 * Generic message class
 */
class synodeSkel {
  public $userId;
  public $tokenSalt;
  public $tokenTest;
  public $room;
}

/**
 * Authentication message
 */
class synodeAuth extends synodeSkel {
  public $access;
  public $userHash;
}

/**
 * Communication message
 */
class synodePush extends synodeAuth {
  public $action;
  public $data;
}