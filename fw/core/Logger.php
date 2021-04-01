<?php

namespace core;
/**
 * Log
 *
 * This is the primary logger for application. You may specify in config:
 * enabled - whether the component is enabled
 * level - the logging level (DEBUG, INFO, WARN, ERROR)
 * log.writer - name of the class that will perform the actual logging
 */
class Logger extends \core\VObject {
    protected static $levels = array(
        'ERROR'	=> 1,
        'WARN'	=> 2,
        'INFO'	=> 3,
        'DEBUG' => 4
    );

    // Constructor
    public function __construct($config = array()) {
	//set some defaults
	$defaults = array(
	    'enabled' => true,
	    'level' => 'ERROR',
	    'writer' => function($message, $level) {},
	);
	$config += $defaults;
	parent::__construct($config);
    }

    // Set level
    public function setLevel($level) {
	if (!array_key_exists($level, self::$levels))
            throw new \InvalidArgumentException('Invalid log level');
        $this->_config['level'] = self::$levels[$level];
    }

    // Get level as text
    public function getLevel() {
	foreach(self::$levels as $name => $val)
    	    if($this->_config['level'] == $val)
    		return $name;
    }

    // some helper functions
    // Log debug message
    public function debug($message) {
        return $this->write($message, 'DEBUG');
    }

    // Log info message
    public function info($message) {
        return $this->write($message, 'INFO');
    }

    // Log warn message
    public function warn($message) {
        return $this->write($message, 'WARN');
    }

    // Log error message
    public function error($message) {
        return $this->write($message, 'ERROR');
    }

    /**
     * Log message
     * @param   mixed   The object to log
     * @param   int     The message level
     * @return int|false
     */
    protected function write($message, $level) {
        if (!$this->_config['enabled']) {
    	    return false;
    	}
    	if (self::$levels[$level] <= self::$levels[$this->_config['level']]) {
            return $this->_config['writer']($message, $level);
        } else {
            return false;
        }
    }

}
