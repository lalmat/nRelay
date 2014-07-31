<?php
require_once( __DIR__ . '/../Elephant.IO/Socket.class.php');

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
    $this->secret   = $secret;
    $this->socketIO = new ElephantIO\Socket($bridgeHost, true, ElephantIO\Socket::DEBUG_HTML);
    $this->socketIO->open();
  }

  /**
   * Destructor
   */
  public function __destruct() {
  	@$this->socketIO->close();
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
  private function send($code, nRelayData $msg) {
  	try {
  		$this->socketIO->emit($code, $msg);
  		return true;
  	}
  	catch(\Exception $e) {
  		echo "<!-- ::NRELAY-SEND:EXCEPTION:: ".$e->getMessage()." -->";
  	}
  	return false;
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
  	$this->tokenSalt = md5(uniqid());
  	$this->tokenTest = hash("sha256", $this->tokenSalt.$secret);
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