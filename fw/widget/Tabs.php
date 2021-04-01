<?php

namespace widget;

/*
<!-- Nav tabs -->
<ul class="nav nav-tabs">
    <li class=" nav-item">
	<a href="#navpills-1" class="nav-link active" data-toggle="tab" aria-expanded="false">Tab One</a>
    </li>
    <li class="nav-item">
	<a href="#navpills-2" class="nav-link" data-toggle="tab" aria-expanded="false">Tab Two</a>
    </li>
    <li class="nav-item">
	<a href="#navpills-3" class="nav-link" data-toggle="tab" aria-expanded="true">Tab Three</a>
    </li>
</ul>

<!-- Tab panes -->
<div class="tab-content">
    <div id="navpills-1" class="tab-pane active">
	<p> content 1 </p>
    </div>
    <div id="navpills-2" class="tab-pane">
	<p> content 2 </p>
    </div>
    <div id="navpills-3" class="tab-pane">
	<p> content 3 </p>
    </div>
</div>
*/

class Tabs extends Widget {
    public function __construct($config = array()) {
	$defaults = [
	    //tabs options
	    'navItemOptions' => [ 'class' => 'nav-item' ],
	    'navLinkOptions' => [ 'class' => 'nav-link', 'data-toggle' => 'tab', ],
	    'navWrapOptions' => [ 'class' => 'nav nav-tabs card-header-tabs' ],
	    'activeClass' => 'active',
	    'activeTab' => null,
	    'contentOptions' => [ 'class' => 'tab-content' ],
	    'paneOptions' => [ 'class' => 'tab-pane' ],
	    'wrapOptions' => false,
	    'tabs' => [],
	];
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }

    public function _init() {
	//set active tab, if any
	if ($this->_config['activeTab']) {
	    $active = $this->_config['activeTab'];
	    $this->_config['tabs'][$active] += [ 'active' => true ];
	} else {
	    foreach($this->_config['tabs'] as $id => $data) break;
	    $this->_config['tabs'][$id] += [ 'active' => true ];
	}
	parent::_init();
    }


    public function run() {
	$nav = $this->renderNav();
	$content = $this->renderContent();

	if ($this->_config['wrapOptions'] === false)
	    return $nav."\n".$content;
	else
	    return self::html()->tag(
		'block',
		array('content' => $nav."\n".$content, 'options' => $this->_config['wrapOptions']),
		$this->_config['wrapOptions'] + [ 'escape' => false ]
	    );

    }

    public function renderContent($config = null) {
	$config = empty($config) ? $this->_config : $config;
	$tabs = [];
	foreach ($config['tabs'] as $tab_id => $tab_config) {
	    $options = $config['paneOptions'] + [ 'id' => $tab_id ];
	    if (isset($tab_config['active']) && $tab_config['active'] === true)
		$options['class'] .= ' ' . $config['activeClass'];
		
	    if (is_callable($tab_config['content'])) {
		$callable = $tab_config['content'];
		$content = $callable();
	    } else {
		$content = $tab_config['content'];
	    }
		
	    $tabs[] = self::html()->tag(
		'block',
		[ 'content' => $content, 'options' => $options ],
		$options + [ 'escape' => false ]
	    );
	}

	$tabs = "\n".implode("\n", $tabs)."\n";

	return self::html()->tag(
	    'block',
	    [ 'content' => $tabs, 'options' => $config['contentOptions'] ],
	    $config['contentOptions'] + [ 'escape' => false ]
	);
    }

    public function renderNav($config = null) {
	$config = empty($config) ? $this->_config : $config;
	$tabs = [];
	foreach ($config['tabs'] as $tab_id => $tab_config) {
	    $options = $config['navLinkOptions'] + [ 'data-toggle' => 'tab', 'aria-expanded' => 'false' ];
	    //whether to add class active to link
	    if (isset($tab_config['active']) && $tab_config['active'] === true) {
		    $options['class'] .= ' ' . $config['activeClass'];
	    }
		
	    $link = self::html()->link(
		$tab_config['label'],
		'#'.$tab_id,
		$options + [ 'escape' => false ]
	    );

	    $tabs[] = self::html()->tag(
		'list-item',
		[ 'content' => $link, 'options' => $config['navItemOptions'] ],
		$config['navItemOptions'] + [ 'escape' => false]
	    );
	}

	$tabs = implode("\n", $tabs);
	//wrap tabs with an ul
	$list = self::html()->tag(
	    'list',
	    [ 'content' => $tabs, 'options' => $config['navWrapOptions'] ],
	    $config['navWrapOptions']
	);

	return $list;
    }

}