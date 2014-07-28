<?php

namespace ElephantIO;

require_once(__DIR__."/Engine.class.php");
require_once(__DIR__."/Debug.trait.php");

/**
 * Socket.IO PHP Client.
 *
 * SIO abreviation stand for "Socket.IO"
 * @author Mathieu Lallemand (lalmat)
 *
 * based on the work of :
 * @author Ludovic Barreca <ludovic@balloonup.com>
 *
 */
class Socket {

	// Socket IO message types
	const S_CONNECT      = 0;
	const S_DISCONNECT   = 1;
	const S_EVENT        = 2;
	const S_ACK          = 3;
	const S_ERROR        = 4;
	const S_BINARY_EVENT = 5;
	const S_BINARY_ACK   = 6;

	const DEBUG_INLINE = 1;
	const DEBUG_HTML   = 2;

	private $Engine;	// Engine.IO PHP object
	private $cbAry; 	// Event Callback Array

	use Debug;				// Include debug trait.

	// ----------------------------------------------------------------------------------------------

  public function __construct($url, $ssl_allowSelfSigned=false, $debugMode=null) {
  	$this->_debugMode = $debugMode;
  	$this->Engine = new Engine($url, $ssl_allowSelfSigned, $debugMode, 'socket.io');
  	$this->EngineOnline = false;
  	return $this;
  }

  public function __destruct() {
  	unset($this->Engine);
  }

  // ----------------------------------------------------------------------------------------------

  /**
   * Open the Socket.IO connection
   */
  public function open() {
  	if (!$this->Engine->isOnline()) {
  		$this->Engine->start($this);
  	} else {
  		$this->debug("Socket: Socket is offline, can't send message.");
  	}
  	return $this;
  }

  /**
   * Backward compatibility
   */
  public function connect() {
  	return $this->open();
  }

  /**
   * Send an event throught the socket
   *
   * @param string $event
   * @param array $args
   */
  public function emit($event, $args) {
  	if ($this->Engine->isOnline()) {
  		$this->Engine->send(self::S_EVENT.'["'.$event.'",'.json_encode($args).']');
  	} else {
  		$this->debug("Socket: Socket is offline, can't send message.");
  	}
  	return $this;
  }

  /**
   * Called when an event is received by the socket
   * @param string $event
   * @param array $args
   */
  public function socketReceiver($rawdata) {
 		$this->debug("Socket Receiver: ".$rawdata);
 		if (strlen($rawdata)>0) {
 			$msgCode = substr($rawdata,0,1);
 			$msgData = json_decode(substr($rawdata,1));
 			switch ($msgCode) {
 				case self::S_EVENT:
 					$this->debug("Socket Event Data:".print_r($msgData,true));
 					$this->sio_event($msgData);
 					break;

 			}
 		}
  }

  public function sio_event($data) {
  	if (is_array($data)) {
  		$event = $data[0];
  		$args  = $data[1];

  		if (is_object($this->cbAry[$event])) {
  			$this->cbAry[$event]->$event($args);
  		} else {
  			$this->debug("No callback for event '".$event."'");
  		}
  	}
  }

  /**
   * Register a function to associate to an event
   * @param string $event
   * @param function $func
   */
  public function register($event, $object) {
  	if (is_object($object) && method_exists($object, $event)) {
  		$this->cbAry[$event] = $object;
  	} else {
			throw new SocketException("This object does not have $event method !");
  	}
  	return $this;
  }

  /**
   * Close the socket connection
   */
  public function close() {
  	if ($this->Engine->isOnline()) {
  		$this->Engine->stop();
  	}
  }

}
class SocketException extends \Exception {}