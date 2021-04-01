<?php

namespace core;

class Session extends \core\VObject {

    public function __construct($config = []) {
	$defaults  = array(
	    'auto_start' => true,
	);
	$config += $defaults;
	parent::__construct($config);

    }

    protected function _init() {
	//hook-up handlers
	if ($this->_config['auto_start']) {
    	    \V::app()->hooks->addHook('before', array($this, 'openSession'));
    	}
        \V::app()->hooks->addHook('after', array($this, 'closeSession'));
    }

    // Load session data from cookie
    public function openSession() {
        session_start();
    }

    // Save session data to cookie
    public function closeSession() {
        session_write_close();
    }

    public function destroy($id) {
    	return true;
    }


}
