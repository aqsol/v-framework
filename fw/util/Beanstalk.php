<?php

namespace util;

use Pheanstalk\Pheanstalk;

class Beanstalk extends \core\VObject {

    protected $btalk = null;

    //creates a new queue object that can handle queues via beanstalk
    public function __construct(array $config = []) {
	//set some defaults
	$defaults = [
	    'host' => 'localhost',
	    'port' => 11300,
	    'queue' => 'default',
	];
	$config += $defaults;
	parent::__construct($config);
    }

    private function beanstalk() {
	if($this->btalk)
	    return $this->btalk;
	return $this->btalk = Pheanstalk::create($this->_config['host'], $this->_config['port']);
    }

    public function getQueue() {
	return $this->_config['queue'];
    }


    public function put($data = null, $queue = null, $delay = 0, $ttr = 120, $prio = Pheanstalk::DEFAULT_PRIORITY) {
	$queue = empty($queue) ? $this->_config['queue'] : $queue;
	$payload = serialize($data);
	return $this->beanstalk()->useTube($queue)->put($payload, $prio, $delay, $ttr);
    }

    public function pop($queue = null) {
	$queue = empty($queue) ? $this->_config['queue'] : $queue;
	//return job
	return $this->beanstalk()->watchOnly($queue)->reserve(0);
    }

    public function reserveWithTimeout($queue = null, $timeout = 60) {
	$queue = empty($queue) ? $this->_config['queue'] : $queue;
	//return job
	return $this->beanstalk()->watchOnly($queue)->reserveWithTimeout($timeout);
    }

    public function release($job, $prio = Pheanstalk::DEFAULT_PRIORITY, $delay = 0) {
	$this->beanstalk()->release($job, $prio, $delay);
    }

    /*
     other functions to be called via __call method:
     $statsJob = \V::app()->queue->statsJob(\Pheanstalk_Job $job);
     $stats = \V::app()->queue->stats();
     $res = \V::app()->queue->delete(\Pheanstalk_Job $job);
     $res = \V::app()->queue->bury(\Pheanstalk_Job $job);
     */
    public function __call($method, $params) {
	$connection = $this->beanstalk();
	return call_user_func_array([&$connection, $method], $params);
    }




}


