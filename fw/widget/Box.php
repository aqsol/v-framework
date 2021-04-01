<?php


namespace widget;

class Box extends Widget {

    public function __construct($config = array()) {
	$defaults = array(
		'boxOptions' => [],
		'title' => '',
		'titleOptions' => [],
		'tools' => '',
		'toolsOptions' => [],
		'header' => '', 		//ignored
		'headerOptions' => [ 'class' => 'p-15 with-border' ],
		'body' => '',
		'bodyOptions' => [ 'class' => 'p-15' ],
		'footer' => '',
		'footerOptions' => [ 'class' => 'p-15' ],
	);
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);

    }



    public function run() {
	//footer, if not empty, aka not false and not '' - more or less
	$footer = null;
	if (!empty($this->footer)) {
	    if (empty($this->footerOptions))
		$this->footerOptions = [];
	    if (!isset($this->footerOptions['class']))
		$this->footerOptions['class'] = '';
	    $this->add_option($this->footerOption['class'], 'box-footer');
	    
	    $footer = $this->html()->tag('block', [ 'content' => $this->footer, 'options' => $this->footerOptions ], $this->footerOptions + [ 'escape' => false ] );
	}
	
	//body, if not empty, aka not false and not '' - more or less
	$body = null;
	if (!empty($this->body)) {
	    if (empty($this->bodyOptions))
		$this->bodyOptions = [];
	    if (!isset($this->bodyOptions['class']))
		$this->bodyOptions['class'] = '';
	    $this->add_option($this->bodyOption['class'], 'box-body');

	    $body = $this->html()->tag('block', [ 'content' => $this->body, 'options' => $this->bodyOptions ], $this->bodyOptions + [ 'escape' => false ] );
	}

	//tools, if not empty, aka not false and not '' - more or less
	$tools = null;
	if (!empty($this->tools)) {
	    if (empty($this->toolsOptions))
		$this->toolsOptions = [];
	    if (!isset($this->toolsOptions['class']))
		$this->toolsOptions['class'] = '';
//	    $this->add_option($this->toolsOptions['class'], 'box-tools');
	    $this->add_option($this->toolsOptions['class'], 'box-controls');
	    $this->add_option($this->toolsOptions['class'], 'pull-right');

	    $tools = $this->html()->tag('block', [ 'content' => $this->tools, 'options' => $this->toolsOptions ], $this->toolsOptions + [ 'escape' => false ] );
	}

	//title, if not empty, aka not false and not '' - more or less
	$title = null;
	if (!empty($this->title)) {
	    if (empty($this->titleOptions))
		$this->titleOptions = [];
	    if (!isset($this->titleOptions['class']))
		$this->titleOptions['class'] = '';
	    $this->add_option($this->titleOptions['class'], 'box-title');

	    $title = $this->html()->tag('<h3{:options}>{:content}</h3>', [ 'content' => $this->title, 'options' => $this->titleOptions ], $this->titleOptions + [ 'escape' => false ] );
	}
	
	//header is different: enabled if title or if tools
	$header = null;
	if (!empty($this->title) || !empty($this->tools)) {
	    if (empty($this->headerOptions))
		$this->headerOptions = [];
	    if (!isset($this->headerOptions['class']))
		$this->headerOptions['class'] = '';
	    $this->add_option($this->headerOptions['class'], 'box-header');

	    $header = $this->html()->tag('block', [ 'content' => $title . $tools, 'options' => $this->headerOptions ], $this->headerOptions + [ 'escape' => false ] );
	}

	//tools, if not empty, aka not false and not '' - more or less
	$body = null;
	if (!empty($this->body)) {
	    if (empty($this->bodyOptions))
		$this->bodyOptions = [];
	    if (!isset($this->bodyOptions['class']))
		$this->bodyOptions['class'] = '';
	    $this->add_option($this->bodyOptions['class'], 'box-body');

	    $body = $this->html()->tag('block', [ 'content' => $this->body, 'options' => $this->bodyOptions ], $this->bodyOptions + [ 'escape' => false ] );
	}

	//box options
	if (empty($this->boxOptions))
	    $this->boxOptions = [];
	if (!isset($this->boxOptions['class']))
	    $this->boxOptions['class'] = '';
	$this->add_option($this->boxOptions['class'], 'box');



	return $this->html()->tag('block', [ 'content' => $header . $body . $footer, 'options' => $this->boxOptions ], $this->boxOptions + [ 'escape' => false ] );
    }
}
