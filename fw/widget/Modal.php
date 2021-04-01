<?php
namespace widget;


class Modal extends Widget {
    const SIZE_EXTRA = 'modal-xl';
    const SIZE_LARGE = 'modal-lg';
    const SIZE_SMALL = 'modal-sm';
    const SIZE_DEFAULT = '';


    public function __construct($config = [] ) {
	$defaults = [
	    'modalOptions' => [ 'class' => 'modal fade', 'role' => 'dialog', 'tabindex' => '-1', 'aria-hidden' => 'true', 'data-backdrop' => 'true' ],
	    'dialogOptions' => [ 'class' => 'modal-dialog', 'role' => 'document' ],
	    'title' => 'default modal title',
	    'body' => '', //or false
	    'bodyOptions' => [ 'class' => 'modal-body' ],
	    'footer' => '', //or false, or callable
	    'footerOptions' => [ 'class' => 'modal-footer' ],
	    'headerOptions' => [ 'class' => 'modal-header' ],
	    'toggle' => '', //or false, or callable
	    'toggleOptions' => [ 'type' => 'button', 'class' => 'btn btn-default', 'data-toggle' => 'modal' ],
	    'size' => self::SIZE_DEFAULT,
	    'scrollable' => false,

	    'beforeContent' => '',
	    'afterContent' => '',
	];
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }

    public function renderBody() {
	switch(true) {
	    case $this->body === false:
		return null;
	    case is_string($this->body):
		return $this->html()->tag('block', [ 'content' => $this->body, 'options' => $this->bodyOptions ], [ 'escape' => false ] );
	    case is_callable($this->body):
		return $this->html()->tag('block', [ 'content' => $this->body(), 'options' => $this->bodyOptions ], [ 'escape' => false ] );
	    default:
		return $this->html()->tag('block', [ 'content' => '', 'options' => $this->bodyOptions ], [ 'escape' => false ] );
	}
	return 'some error in body generation';
    }


    public function renderHeader() {
	//title
	switch(true) {
	    case $this->title === false:
		return null;
		break;
	    default:
		$title = $this->html()->tag('<h5{:options}>{:content}</h5>', [ 'content' => $this->title, 'options' => [ 'class' => 'modal-title mr-auto' ] ], [ 'escape' => false ]);
		$title .= $this->html()->link('<i class="mdi mdi-close"></i>', '#', [ 'data-dismiss' => 'modal', 'escape' => false ]);
		return $this->html()->tag('block', ['content' => $title, 'options' => $this->headerOptions ], ['escape' => false]);
		break;
	}
    }

    public function renderFooter() {
	//footer
	switch(true) {
	    case $this->footer === false:
		return null;
	    case is_string($this->footer):
		return $this->html()->tag('block', ['content'=>$this->footer, 'options' => $this->footerOptions ], ['escape' => false]);
	    case is_callable($this->footer):
		return $this->html()->tag('block', ['content'=>$this->footer(), 'options' => $this->footerOptions ], ['escape' => false]);
	}
    }

    public function renderContent($text) {
	//content
	return $this->html()->tag('block', ['content'=>$text, 'options' => ['class'=>'modal-content']], ['escape' => false]);
    }

    public function run() {
	$this->add_option($this->modalOptions['class'], 'modal');
    
	$content = $this->renderContent($this->renderHeader() . $this->renderBody() . $this->renderFooter());

	switch($this->size) {
	    case 'extra': case self::SIZE_EXTRA:
		$this->add_option($this->dialogOptions['class'], self::SIZE_EXTRA);
		break;
	    case 'large': case self::SIZE_LARGE:
		$this->add_option($this->dialogOptions['class'], self::SIZE_LARGE);
		break;
	    case 'small': case self::SIZE_SMALL:
		$this->add_option($this->dialogOptions['class'], self::SIZE_SMALL);
		break;
	}

	if ($this->scrollable)
	    $this->add_option($this->dialogOptions['class'], 'modal-dialog-scrollable');
	
	$modal = $this->html()->tag('block', [ 'content' => $content, 'options' => $this->dialogOptions ], [ 'escape' => false ]);
	$modal = $this->beforeContent . $modal . $this->afterContent;
	$modal = $this->html()->tag('block', [ 'content' => $modal, 'options' => $this->modalOptions + ['id' => $this->id] ], [ 'escape' => false ]);

	return $modal;
    }

}

