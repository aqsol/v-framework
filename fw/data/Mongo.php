<?php

namespace data;
class Mongo extends \core\VObject {
  
    // Checks if all data is in place for connection.
    public function __construct($config = array()) {
	//set some defaults
	$defaults = array(
	    'uri' => 'mongodb://localhost:27017',
	    'uriOptions' => [
		'appname' => 'MyApplication',
		//'authSource' => null,
		//'canonicalizeHostname' => null,
		//'connectTimeoutMS' => 10000,
		//'gssapiServiceName' => null,
		//'heartbeatFrequencyMS' => 60000,
		//'journal' => true,
		//'localThresholdMS' => 15,
		//'maxStalenessSeconds' => null,
		//'password' => null,
		//'readConcernLevel' => '', //local, majority, linearizable
		//'readPreference' => 'primary',
		//'readPreferenceTags' => [],
		//'replicaSet' => null,
		//'safe' => true, //deprecated
		//'serverSelectionTimeoutMS' => 30000,
		//'serverSelectionTryOnce' => true,
		//'slaveOk' => true, //deprecated
		//'socketCheckIntervalMS' => 5000,
		//'socketTimeoutMS' => 300000,
		//'ssl' => false,
		//'username' => null,
		//'w' => 'majority',
		//'wTimeoutMS' => 0,
	    ],

	    'driverOptions' => [
	    ],
	    'defaultDatabase' => null,
	);
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }

    protected function _init() {
	//open the connection; if errors, let default error handler handle them (no catch)
	$this->client = new \MongoDB\Client($this->uri, $this->uriOptions, $this->driverOptions);
	parent::_init();
    }


    //optimized call
    public function __call($method, $params) {
	switch (count($params)) {
	    case 0:
		return $this->client->{$method}();
	    case 1:
		return $this->client->{$method}($params[0]);
	    case 2:
		return $this->client->{$method}($params[0], $params[1]);
	    case 3:
		return $this->client->{$method}($params[0], $params[1], $params[2]);
	    default:
		return call_user_func_array([ $this->client, $method ], $params);
	}
    }


}