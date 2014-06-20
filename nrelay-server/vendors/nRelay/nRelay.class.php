<?php
require_once( __DIR__ . '/../ElephantIO/Client.php');
use ElephantIO\Client as ElephantIOClient;

/**
 * RTS SERVER CLASS - PHP -
 */
class nRelay {
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
  	unset($this->socketIO);
  	$this->secret = null;
  }

  /**
   * Allow a userId to connect the SynodeBridge
   * @param  [int]    $userId [ID of the user]
   * @param  [string] $room   [Room to connect to]
   * @return [string]         [User Hash that should be used to connect to the SynodeBridge]
   */
  public function allow($userId, $room) {
    $auth = new nRelayAuth($userId, $room, $this->secret);
    $auth->access   = true;
    $auth->userHash = uniqid();
    if ($this->send('allow', $auth)) return $auth->userHash;
    return null;
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
    $msg = new nRelayMsg($userId, $room, $this->secret);
    $msg->action = $action;
    $msg->data   = $data;
    return $this->send("push", $msg);
  }

  /**
   * Deep magic from Elephant.IO
   * @param  [type]        $code [description]
   * @param  synodeMessage $msg  [description]
   * @return [type]              [description]
   */
  private function send($code, nRelayMsg $msg) {
    $this->socketIO->init();
    $this->socketIO->emit($code, $msg);
    $this->socketIO->close();
    return true; // TODO: Do better next time !
  }
}

/**
 * Generic message class
 */
class nRelayData {
  public $userId;
  public $tokenSalt;
  public $tokenTest;
  public $room;

  public function __construct($userId, $room, $secret) {
  	$this->userId = $userId;
  	$this->room   = $room;
  	$s->tokenSalt = md5(uniqid());
  	$s->tokenTest = hash("sha256", $this->tokenSalt.$secret);
  }
}

/**
 * Authentication message
 */
class nRelayAuth extends nRelayData {
  public $access;
  public $userHash;
}

/**
 * Communication message
 */
class nRelayMsg extends nRelayData {
  public $action;
  public $data;
}