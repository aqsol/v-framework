<?php

namespace http;

class CSRF extends \core\VObject {

    public function __construct($config = []) {
	$defaults = [
	    'csrf_header_key' => 'X-CSRF-Token',
	    'csrf_key' => '_csrf',
	    'enabled' => true,
	];

	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }

    public function _init() {
	parent::_init();
	
	if (!$this->enabled)
	    return;

	//register CSRF handlers
    	\V::app()->hooks->addHook('on:before-action', array($this, 'initialize'));
    }

    public function initialize() {
	$token = $this->getToken();

	\V::app()->clientscript->addPageMeta([ 'name' => 'csrf-param', 'content' => $this->csrf_key ]);
	\V::app()->clientscript->addPageMeta([ 'name' => 'csrf-token', 'content' => $token ]);
    }


    //returns the session token or a new generated token
    public function getToken($regen = false) {
	$token = $_SESSION[$this->csrf_key] ?? null;
        if (!$token || $regen)
            $_SESSION[$this->csrf_key] = $token = uniqid(); //or a better way of generating the random stuff

        return $token;
    }

}