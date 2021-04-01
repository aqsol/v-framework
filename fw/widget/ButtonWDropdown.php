<?php

namespace widget;

class ButtonWDropdown extends Menu {
    public function __construct($config = []) {
	$defaults = [
	    'settings' => [
		//global options for item
		'itemOptions' => [],
		'linkOptions' => [ 'class' => 'dropdown-item' ],
	    ],
	    'wrapBlock' => [
		'tag' => 'div',
		'options' => [ 'class' => 'btn-group' ]
	    ],
	];
	
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }

    public function run() {
	$dd = '';
	//primary
	//ensure 'btn' class here
	if(empty($this->_config['primary']['linkOptions']))
	    $this->_config['primary']['linkOptions'] = [];
	if(empty($this->_config['primary']['linkOptions']['class']))
	    $this->_config['primary']['linkOptions']['class'] = '';
	$this->add_option($this->_config['primary']['linkOptions']['class'], 'btn');
	$dd .= $this->renderMenuItem($this->_config['primary']);

	//button
	//copy class from primary and add specific dropdown stuff
	$btn_class = $this->_config['primary']['linkOptions']['class'];
	$this->add_option($btn_class, 'dropdown-toggle');
	$dd .= (new Button([
	    'tag' => 'button',
	    'label' => '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>',
	    'options' => [
		'type' => 'button',
		'class' => $btn_class,
		'data-toggle' => 'dropdown'
	    ]
	]))->run();

	//menu
	//use wrapBlock for wrapping entire block, not just menu
	$wrapBlock = $this->_config['wrapBlock'];
	$this->_config['wrapBlock'] = false;
	$dd .= parent::run();
	
	//final block, use wrapBlock saved above
	return self::html()->tag(
	    '<{:tag}{:options}>{:content}</{:tag}>',
	    [ 'tag' => $wrapBlock['tag'], 'content' => $dd, 'options' => $wrapBlock['options'] ],
	    $wrapBlock['options'] + ['escape' => false]
	);
    }

}

