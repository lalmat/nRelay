<?php 
namespace ElephantIO;

trait Debug {

	private $_debugMode;

	/**
	 * Debug message
	 * @param unknown $message
	 */
	private function debug($message) {
		switch ($this->_debugMode) {
			case self::DEBUG_INLINE :
				echo $message.PHP_EOL;
				break;
			
			case self::DEBUG_HTML :
				echo "<!-- Elephant.IO::".print_r($message,true)."-->".PHP_EOL;
				break;
				
			default:
				// No debug.
		}
	}
}