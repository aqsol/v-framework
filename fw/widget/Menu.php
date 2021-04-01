<?php


namespace widget;


use \widget\Widget;

class Menu extends MenuBase {

	public function __construct($config = array()) {
	    $defaults = array(
		'settings' => [
		    //whether to activate items according to their route: false - don't activate, true - activate on exact match, 'prefix' - if prefix matches, 
		    'activateItems' => true,
		    //whether to activate parents of active items
		    'activateParents' => true, 
		    //class for active elements
		    'activeCssClass' => 'active',
		    //whether to hide empty menu items
		    'hideEmptyItems' => true, 
		    //route and params
		    'route' => \V::app()->config('c_route'),
		    'params' => \V::app()->request->query,
		    //options for item
		    'itemOptions' => [],
		    'linkOptions' => [],
		],
		'wrapBlock' => false,
	    );
	    $config = \util\Set::merge($defaults, $config);

	    parent::__construct($config);

	}


	public function _init() {
	    $this->_config['menu']['items'] = $this->normalizeMenuItems($this->_config['menu']['items'], $this->_config['settings'], $hasActiveChild);
	    parent::_init();
	}


	public function run() {
	    //if there are no items, return null
	    if (!count($this->_config['menu']['items']))
		return null;

//	    \V::app()->log->debug(print_r($this->_config['menu'], true));
	    $menu = $this->renderMenuItems($this->_config['menu'], $this->_config['settings']);

	    if ($this->_config['wrapBlock'] !== false) {
		//ensure some fail-safe defaults, just in case
		if (empty($this->_config['wrapBlock']))
		    $this->_config['wrapBlock'] = [];
		if (empty($this->_config['wrapBlock']['tag']))
		    $this->_config['wrapBlock']['tag'] = 'div';
		if (empty($this->_config['wrapBlock']['options']))
		    $this->_config['wrapBlock']['options'] = [];
		    
    		$menu = self::html()->tag(
    		    '<{:tag}{:options}>{:content}</{:tag}>',
    		    [ 'tag' => $this->_config['wrapBlock']['tag'], 'content' => $menu, 'options' => $this->_config['wrapBlock']['options'] ],
    		    $this->_config['wrapBlock']['options'] + ['escape' => false]
    		);
	    }
	    
	    return $menu;
	}

	/**
	 * Recursively renders the menu items
	 * @return string the rendering result
	 */
	public function renderMenuItems($menu, $settings) {
	    $lines = [];
	    $menu['items'] = !empty($menu['items']) ? $menu['items'] : [];
	    $hasSubMenu = false;
	    foreach ($menu['items'] as $i => $item) {
		//render this menu item
		$menuText = $this->renderMenuItem($item);

//		\V::app()->log->debug("rendered item $i: " . $menuText);

		//submenu ?
		if (!empty($item['items'])) {
		    $menuText .= $this->renderMenuItems($item, $settings);
		}

//		\V::app()->log->debug("rendered item(incl submenu) $i: " . $menuText);

		if ($item['itemOptions'] === false) {
		    $lines[] = $menuText;
		} else {
		    $lines[] = \V::app()->view->html->tag(
			'list-item',
			[ 'content' => $menuText, 'options' => $item['itemOptions'] ],
			$item['itemOptions']
		    );
		}
	    }
	    $wrapOptions = isset($menu['wrapOptions']) ? $menu['wrapOptions'] : [];
	    if ($wrapOptions === false) {
		return implode("\n", $lines);
	    } else {
		$tag = empty($wrapOptions['tag']) ? 'list' : $wrapOptions['tag'];
		return \V::app()->view->html->tag(
		    $tag,
		    [ 'content' => "\n".implode("\n", $lines)."\n", 'options' => $wrapOptions ],
		    $wrapOptions
		);
	    }
	}

}