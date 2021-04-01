<?php
namespace widget;

class FlashMessage extends \core\VObject {
    public function __construct($config = []) {
	$defaults = [
//            'heading' => 'Default FMessage heading',
            'text' => 'Default FMessage text',
            'positionClass' => 'toast-top-right',
//            'loaderBg' => '"#ff6849',
            'type' => 'success',
//            'hideAfter' => 4000,
//            'stack' => 6
	    'closeButton' => true,
	    'timeOut' => 5000,
	    'extendedTimeOut' => 1000,
	    'progressBar' => true,
	];
	$config += $defaults;
	parent::__construct($config);
    }

    public static function create($data) {
	if (is_string($data))
	    $data = [ 'text' => $data ];
	return new self($data);
    }
    
    public function error() {
	$this->_config['type'] = 'error';
	return $this;
    }

    public function success() {
	$this->_config['type'] = 'success';
	return $this;
    }
    
    public function info() {
	$this->_config['type'] = 'info';
	return $this;
    }

    public function warning() {
	$this->_config['type'] = 'warning';
	return $this;
    }
    
    
    public function add() {
	\V::app()->user->setFlash(uniqid(), $this->_config);
    }
}