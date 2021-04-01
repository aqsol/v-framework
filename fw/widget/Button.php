<?php

namespace widget;

class Button extends Widget {
    public function __construct($config=[]) {
	$defaults = [
	    'tag' => 'button',
	    'label' => \V::t('Button'),
	    'options' => [
		'type' => 'button'
	    ],
	];
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }
    /**
     * Initializes the widget.
     * If you override this method, make sure you call the parent implementation first.
     */
    public function _init() {
        parent::_init();
        if(empty($this->options))
    	    $this->options = [];
    	if (empty($this->options['class']))
    	    $this->options['class'] = '';
    	$this->add_option($this->options['class'], 'btn');
    }

    public function run() {
        return self::html()->tag('<{:tag}{:options}>{:content}</{:tag}>', [ 'tag' => $this->tag, 'content' => $this->label, 'options' => $this->options], $this->options + ['escape' => false]);
    }
}
