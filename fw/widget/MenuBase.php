<?php


namespace widget;


use \widget\Widget;
use \util\Set;


class MenuBase extends Widget {

	// ---------------------------------------------------------------------------------------------------------
	// dropdown menu stuff

	/**
	 * Renders the content of a menu item.
	 * Note that the container and the sub-menus are not rendered here.
	 * @param array $item the menu item to be rendered. Please refer to [[items]] to see what data might be in the item.
	 * @return string the rendering result
	 */
	public function renderMenuItem($item) {
	    //SC: added a 'html' option to render directly given html content instead of route/params
	    if (!empty($item['html']))
		return $item['html'];
	
	    if (!isset($item['linkOptions']))
		$item['linkOptions'] = [];
	    $item['linkOptions'] += array('escape' => false);

	    if (isset($item['url']))
		$url = $item['url'];
	    else
		$url = \V::app()->createUrl(isset($item['route'])?$item['route']:'#', isset($item['params'])?$item['params']:array());

	    return self::html()->link($item['label'], $url, $item['linkOptions']);
	}



	/**
	 * Checks whether a menu item is active.
	 * This is done by checking if [[route]] and [[params]] match that specified in the `url` option of the menu item.
	 * When the `url` option of a menu item is specified in terms of an array, its first element is treated
	 * as the route for the item and the rest of the elements are the associated parameters.
	 * Only when its route and parameters match [[route]] and [[params]], respectively, will a menu item
	 * be considered active.
	 * @param array $item the menu item to be checked
	 * @return boolean whether the menu item is active
	 */
	public function isItemActive($item, $settings) {
	    if (isset($item['route'])) {
		$item_route = ltrim($item['route'], '/');
		if (($settings['activateItems'] === true && $item_route == $settings['route']) ||
		    ($settings['activateItems'] === 'prefix' && strpos($settings['route'], $item_route) === 0)) {
		    if (isset($item['params'])) {
			foreach ($item['params'] as $name => $value) {
			    if (!isset($settings['params'][$name]) || $settings['params'][$name] != $value)
				return false;
			}
		    }
		    return true;
		}
	    }
	    return false;
	}


	/**
	 * Normalizes the [[items]] property to remove invisible items and activate certain items.
	 * @param array $items the items to be normalized.
	 * @param boolean $active whether there is an active child menu item.
	 * @return array the normalized menu items
	 */
	public function normalizeMenuItems($items, $settings = array(), &$active) {
	    //options: hideEmptyItems, activateParents, activateItems, route, params
	    foreach ($items as $i => $item) {
		//remove invisible items
		if (isset($item['visible']) && !$item['visible']) {
		    unset($items[$i]);
		    continue;
		}
		//normalize label
		if (!isset($item['label'])) {
		    $items[$i]['label'] = '';
		}

		$hasActiveChild = false;
		if (isset($item['items'])) {
		    $items[$i]['items'] = $this->normalizeMenuItems($item['items'], $settings, $hasActiveChild);
		    if (empty($items[$i]['items']) && $settings['hideEmptyItems']) {
			unset($items[$i]['items']);
			if (!isset($items[$i]['route'])) {
			    unset($items[$i]);
			    continue;
			}
		    }
		}

		if (!isset($item['active'])) {
		    //\V::app()->log->debug(@$item['route'] . ':item[active] not set');
		    if ($settings['activateParents'] && $hasActiveChild || $settings['activateItems'] && $this->isItemActive($item, $settings)) {
			$active = true;
			$items[$i]['active'] = true;
		    } else {
			$items[$i]['active'] = false;
		    }
		} elseif ($item['active']) {
		    //\V::app()->log->debug(@$item['route'] . ':item[active] is set and true');
	    	    $active = true;
		}

		//item options processing
		$itemOptions = isset($item['itemOptions'])?$item['itemOptions']:$settings['itemOptions'];
		if ($items[$i]['active'] == true) {

		    //\V::app()->log->debug(@$item['route'] . ':adding class `active`');

		    //if item is active, add 'active' class to it's definition
		    $class = array();
		    $class[] = $settings['activeCssClass'];
			
		    if (empty($itemOptions['class'])) {
			$itemOptions['class'] = implode(' ', $class);
		    } else { 
			$itemOptions['class'] .= ' ' . implode(' ', $class);
		    }
		}
		$items[$i]['itemOptions'] = $itemOptions;
		
		//linkOptions processing
		$linkOptions = isset($item['linkOptions'])?$item['linkOptions']:$settings['linkOptions'];
		$items[$i]['linkOptions'] = $linkOptions;
		
		
		//\V::app()->log->debug(@$item['route'] . ':' . print_r($itemOptions, true));

	    }
	    return $items;
	}



}