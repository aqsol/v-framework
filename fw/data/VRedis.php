<?php

namespace data;

class VRedis extends \core\VObject {
    public $redis;

    // Checks if all data is in place for connection.
    public function __construct($config = []) {
	//set some defaults
	$defaults = [
	    'host' => '127.0.0.1',
	    'port' => 6379,
	    'timeout' => 1,
	    'persistent_id' => null,
	    'retry_interval' => 100,
	    'read_timeout' => 1,
	];
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }

    protected function _init() {
	//open the connection; if errors, let default error handler handle them (no catch)
	$this->redis = new \Redis();
	$this->redis->connect($this->host, $this->port, $this->timeout, $this->persistent_id, $this->retry_interval, $this->read_timeout);
	parent::_init();
    }


    public function __call($method, $params) {
	return call_user_func_array([ $this->redis, $method ], $params);
    }


}