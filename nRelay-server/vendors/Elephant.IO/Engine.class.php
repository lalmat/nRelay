<?php
namespace ElephantIO;

require_once(__DIR__."/Payload.class.php");
require_once(__DIR__."/Debug.trait.php");

/**
 * Engine.IO PHP Client.
 *
 * EIO abreviation stand for "Engine.IO"
 *
 * @author Mathieu Lallemand (lalmat)
 * based on the work of Ludovic Barreca <ludovic@balloonup.com>
 *
 */
class Engine {

	const E_OPEN    = 0;
  const E_CLOSE   = 1;
  const E_PING    = 2;
  const E_PONG    = 3;
  const E_MESSAGE = 4;
  const E_UPGRADE = 5;
  const E_NOOP    = 6;

  const T_POLLING   = 'polling';
  const T_WEBSOCKET = 'websocket';

  const DEBUG_INLINE = 1;
  const DEBUG_HTML   = 2;

  private $_serverProtocol;					// Protocol used - http, https, etc.
  private $_serverSecured;					// Is the server using SSL security
  private $_serverHost;							// Server host
  private $_serverPath;							// Server path
  private $_serverPort;							// Server port
  private $_serverSSLSelfSigned;		// Allow Self Signed Certificate from the server
  private $_name;										// Name of engine.io
  private $_version;								// Version of engine.io
	private $_transport;							// Transport used
	private $_useBase64;							// Use Base64 encoding or binary encoding
	private $_isOnline;								// Is the socket online ?
	private $_messageHandler;					// Link to the Socket.IO object. Used to follow messages
	private $_lastProbe;							// Last probe sent/received, used with ping and pong messages

	private $_handshakeTimeout;       // Not seriously used ATM
	private $_lastKeepAlive;					// Not seriously used ATM

	private $sckSession;							// Socket Session Informations
	private $sckHandler;							// Socket Handler

	private $_debugMode;							// Debug Mode

	use Debug; 	// Including Debug Trait

	// Object definition ----------------------------------------------------------------------------

	public function __construct($url, $ssl_selfSignedCertificate=false, $debug=null, $engineName='engine.io', $engineVersion=2) {
		$parsedURL = parse_url($url);
		$this->_serverProtocol      = $parsedURL['scheme'];
		$this->_serverHost          = $parsedURL['host'];
		$this->_serverPath          = isset($parsedURL['path']) ? $parsedURL['path'] : "";
		$this->_serverSecured       = isset($parsedURL['scheme']) ? ($parsedURL['scheme'] == "https") : false;
		$this->_serverPort          = isset($parsedURL['port']) ? $parsedURL['port'] : ($this->_serverSecured?443:80);
		$this->_serverSSLSelfSigned = $ssl_selfSignedCertificate;

		$this->_name      = $engineName;
		$this->_version   = $engineVersion;
		$this->_transport = self::T_POLLING;
		$this->_useBase64 = true;
		$this->_debugMode = $debug;

	}

	public function __destruct() {
		unset($this->sckHandler);
	}


	// Main operations ------------------------------------------------------------------------------

	/**
	 * Start the Engine
	 *
	 * If defined, $messageHandler must be an object with a public 'socketReceiver' method accepting 1 argument
	 * @param string $messageHandler
	 * @throws EngineException
	 */
	public function start($messageHandler=null) {
		$this->debug("Engine: Starting...");
		$this->_isOnline = false;
		if ($this->handshake()) {
			$host = (($this->_serverSecured) ? "ssl://" : "").$this->_serverHost;
			$this->debug("Engine: Opening new socket on ".$host.":".$this->_serverPort);
			$this->sckHandler = fsockopen($host, $this->_serverPort, $errno, $errstr);
			if (!$this->sckHandler) {
				throw new EngineException("fSocket Error #".$errno." : ".$errstr);
			} else {
				$this->_messageHandler = $messageHandler;
				$this->eio_upgrade('websocket');
				$this->_isOnline = true;
			}
		}
	}

	/**
	 * Stop the Engine
	 *
	 * @return boolean
	 */
	public function stop() {
		$this->debug("Engine: Stopping...");
		if (!is_null($this->sckHandler)) {
			$this->write(self::E_CLOSE, null, false);
			fclose($this->sckHandler);
			$this->sckHandler = null;
			$this->debug("Engine: Stopped.");
		} else {
			$this->debug("Engine: Already stopped.");
		}
		$this->_isOnline = false;
	}

	/**
	 * Return if the Engine is started and online
	 * @return boolean
	 */
	public function isOnline() {
		return $this->_isOnline;
	}

	/**
	 * Do the handshake with Socket.IO Server
	 * @throws EngineException
	 * @throws \Exception
	 * @return boolean
	 */
	private function handshake() {
		$this->debug("Engine: Handshaking...");

		// Create handshake URL
		$url  = $this->_serverProtocol."://".$this->_serverHost;
		$url .= ((is_numeric($this->_serverPort)) ? ":".$this->_serverPort : "")."/";
		$url .= $this->_name."/";
		$url .= "?EIO=".$this->_version;
		$url .= "&transport=".$this->_transport;
		$url .= "&b64=".($this->_useBase64?"1":"0");

		$this->debug("Engine: handshake query  = ".$url);

		// Pass it to cURL.
		$ch = curl_init($url);

		// Set cURL to return the result;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		if ($this->_serverSSLSelfSigned) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}
		/*
		// TODO: Optimize this process
		if (null !== $this->handshakeTimeout) {
			$timeout   = $this->handshakeTimeout;
			$constants = array(CURLOPT_CONNECTTIMEOUT_MS, CURLOPT_TIMEOUT_MS);

			$version = curl_version();
			$version = $version['version'];

			// CURLOPT_CONNECTTIMEOUT_MS and CURLOPT_TIMEOUT_MS were only implemented on curl 7.16.2
			if (true === version_compare($version, '7.16.2', '<')) {
				$timeout  /= 1000;
				$constants = array(CURLOPT_CONNECTTIMEOUT, CURLOPT_TIMEOUT);
			}

			curl_setopt($ch, $constants[0], $timeout);
			curl_setopt($ch, $constants[1], $timeout);
		}
		*/

		// Handshake !
		$res = curl_exec($ch);
		$this->debug("Engine: handshake result = ".$res);

		if ($res === false || $res === '')
			throw new EngineException("Handshake cURL Error: ".curl_error($ch));

		// Close cURL
		curl_close($ch);

		// Parse the response
		$this->sckSession = json_decode(substr($res, strpos($res,"{"), strrpos($res,"}")));
		// We should now have :
		// $this->sckSession->sid, $this->sckSession->pingInterval, $this->sckSession->pingTimeout, $sess->upgrades;

		// Check if the server support WebSockets
		if (!in_array('websocket',$this->sckSession->upgrades))
			throw new \Exception('This socket.io server do not support websocket protocol. Terminating connection...');

		return true;
	}

	/**
	 * Read the buffer and return the oldest event in stack
	 *
	 * @access public
	 * @return string
	 * // https://tools.ietf.org/html/rfc6455#section-5.2
	 */
	private function read() {
		// Ignore first byte, I hope Socket.io does not send fragmented frames, so we don't have to deal with FIN bit.
		// There are also reserved bit's which are 0 in socket.io, and opcode, which is always "text frame" in Socket.io
		fread($this->sckHandler, 1);

		// There is also masking bit, as MSB, but it's 0 in current Socket.io
		$payload_len = ord(fread($this->sckHandler, 1));

		switch ($payload_len) {
			case 126:
				$payload_len = unpack("n", fread($this->sckHandler, 2));
				$payload_len = $payload_len[1];
				break;
			case 127:
				$this->stdout('error', "Next 8 bytes are 64bit uint payload length, not yet implemented, since PHP can't handle 64bit longs!");
				break;
		}

		// Use buffering to handle packet size > 16Kb
    $read = 0;
    $payload = '';
    while ($read < $payload_len && ($buff = fread($this->sckHandler, $payload_len-$read))) {
			$read += strlen($buff);
			$payload .= $buff;
		}
		return $payload;
	}

	/**
	 * Send message to the websocket
	 *
	 * @access public
	 * @param int $eioCode
	 * @param int $sioCode
	 * @param int $endpoint
	 * @param string $message
	 * @return ElephantIO\Client
	 */
	private function write($eioCode, $message=null, $readResponse=true) {
		if (!is_int($eioCode) || $eioCode > 8)
			throw new EngineException('EngineIO (EIO) event code must be an integer strictly inferior to 9.');

		$raw_message = $eioCode.$message;

		$payload = new Payload();
		$payload->setOpcode(Payload::OPCODE_TEXT)
		        ->setMask(true)
		        ->setPayload($raw_message);

		$encoded = $payload->encodePayload();
		$this->debug("Engine:WRITE ".$raw_message);
		fwrite($this->sckHandler, $encoded);

		if ($readResponse)
			$this->parse($this->read());
	}

	/**
	 * Parse the response and do the right thing
	 * @param unknown $rsp
	 * @throws EngineException
	 */
	private function parse($rsp) {
		$this->debug("Engine:PARSE ".$rsp);
		$rspEvent = trim(substr($rsp,0,1));
		$rspData  = substr($rsp,1);

		// Every packet count !
		$this->_lastKeepAlive = time();

		switch($rspEvent) {
			case self::E_OPEN:
				$this->debug("Engine OPEN Received: ".$rspData);
				break;

			case self::E_CLOSE:
				$this->debug("Engine CLOSE Received: ".$rspData);
				$this->close();
				break;

			case self::E_PING :
				$this->debug("Engine PING Received: ".$rspData);
				$this->eio_ping($msg);
				break;

			case self::E_PONG:
				$this->debug("Engine PONG Received: ".$rspData);
				$this->eio_pong($msg);
				break;

			case self::E_MESSAGE:
				$this->debug("Engine MESSAGE Received: ".$rspData);
				$this->eio_message($rspData);
				break;

			case self::E_UPGRADE:
				$this->debug("Engine UPGRADE Received: ".$rspData);
				break;

			case self::E_NOOP:
				$this->debug("Engine NOOP Received: ".$rspData);
				break;

			default:
				$this->debug("Engine Unknown Event Received : ".$rsp);
//				throw new EngineException("Unknown Engine.IO event: ".$rspEvent);

		}
	}

	/**
	 * Public method used to send messages
	 * @param unknown $message
	 */
	public function send($message) {
		if ($this->isOnline()) {
			$this->write(self::E_MESSAGE, $message, false);
		}
	}

	// Engine IO - Message management ---------------------------------------------------------------

	/**
	 * EngineIO [PING] : Send a ping packet to the server.
	 * @param unknown $probe
	 */
	private function eio_ping($probe) {
		$this->_lastProbe = $probe;
		$this->write(self::E_PONG, $probe);
	}

	/**
	 * EngineIO [PONG] : Updates the server keepalive
	 * @param unknown $probe
	 */
	private function eio_pong($probe) {
		if ($probe == $this->_lastProbe) $this->_lastKeepAlive = time();
	}

	/**
	 * EngineIO [MESSAGE] : Get a message from the server and pass it to the messageHandler object.
	 * @param unknown $data
	 */
	private function eio_message($data) {
		if (is_object($this->_messageHandler) && method_exists($this->_messageHandler, "socketReceiver")) {
			$this->debug("Engine: Sending '$data' to registered _messageHandler");
			$this->_messageHandler->socketReceiver($data);
		} else {
			$this->debug("Engine: no registered _messageHandler");
		}
	}

	/**
	 * EngineIO [UPGRADE] : Upgrade the transport layer to Websocket and tell the server that all is OK.
	 * @param unknown $transport
	 * @throws \Exception
	 */
	private function eio_upgrade($transport) {
		$this->debug("Engine: Upgrading to transport '".$transport."'");
		$this->_transport = $transport;

		$url  = "/".$this->_name."/"."?EIO=".$this->_version."&transport=".$this->_transport."&sid=".rawurlencode($this->sckSession->sid);

		$key = $this->generateKey();
		$getRequest  = "GET ".$url." HTTP/1.1\r\n";
		$getRequest .= "Host: ".$this->_serverHost."\r\n";
		if ($transport == self::T_WEBSOCKET) {
			$getRequest .= "Upgrade: WebSocket\r\n";
			$getRequest .= "Connection: Upgrade\r\n";
			$getRequest .= "Sec-WebSocket-Key: $key\r\n";
			$getRequest .= "Sec-WebSocket-Version: 13\r\n";
		}
		$getRequest .= "Origin: *\r\n\r\n";
		fwrite($this->sckHandler, $getRequest);
		$res = fgets($this->sckHandler);

		if ($res === false)
			throw new \Exception('Socket.io did not respond properly. Aborting...');

		if ($subres = substr($res, 0, 12) != 'HTTP/1.1 101')
			throw new \Exception('Unexpected Response. Expected HTTP/1.1 101 got '.$subres.'. Aborting...');

		// Flushing garbage
		while(trim(fgets($this->sckHandler)) !== "");

		// Tells the server that we have upgraded the transport protocol.
		$this->write(self::E_UPGRADE, null, false);
	}


	// Tool functions -------------------------------------------------------------------------------

	private function generateKey($length = 16) {
		$c = 0;
		$tmp = '';
		while($c++ * 16 < $length) $tmp .= md5(mt_rand(), true);
		return base64_encode(substr($tmp, 0, $length));
	}

}

class EngineException extends \Exception {}