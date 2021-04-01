<?php

namespace widget;


class Widget extends \core\VObject {
    //a counter used to generate [[id]] for widgets.
    public static $_counter = 0;

    // the prefix to the automatically generated widget IDs.
    public static $_autoIdPrefix = 'w';

    public function __construct(array $config = array()) {
	$defaults = array(
	    'id' => null,
	);
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }

    public function _init() {
	if ($this->_config['id'] == null) {
            $this->_config['id'] = static::$_autoIdPrefix . static::$_counter++;
	}
    }


    public static function html() {
	return \V::app()->view->html;
    }
    
    public static function form() {
	return \V::app()->view->form;
    }

    public function run() {}


    //helper function
    public function add_option(&$string, $option) {
	if (empty($string))
	    $__data = [];
	else
	    $__data = explode(' ', $string);
	
	if (!in_array($option, $__data))
	    $__data[] = $option;
	
	$string = implode(' ', $__data);
    }

    public function rm_option(&$string, $option) {
	$__data = explode(' ', $string);
	unset($__data[$option]);
	$string = implode(' ', $__data);
    }


}
