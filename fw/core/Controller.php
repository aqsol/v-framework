<?php
namespace core;
use \util\Set;

/**
 * Implements a basic controller functionallity.
 * It should not be instanciated directly but extended from.
 */

class Controller extends \core\VObject {


    public function run() {
	echo "function run should be implemented\n";
    }

    // SC: 19-07-2018: strange name...
    public function renderAjax($template, $data = [], $return = false) {
	$output = $this->render($template, $data, $layout = false, $return, $render_cscript = true);
	$script = \V::app()->clientscript->renderPageScripts();
	if ($return) {
	    $output .= $script;
	} else {
	    echo $script;
	}
	return $output;
    }

    public function render($template, $data = [], $layout = null, $return = false, $render_cscript = true) {
    	if(strpos($template,'/')===false) {
    	    //append controllerid in front of view
    	    $template = $this->id . '/' . $template;
    	}
	if ($layout === null)
	    $layout = $this->layout;

	return \V::app()->view->render($template, $data, $layout, $return, $render_cscript); //render the template
    }

    // Performs redirect
    protected function redirect($route, $params=array(), $ampersand='&', $terminate=true, $status=302) {
	$url = $this->createUrl($route, $params, $ampersand);
	return \V::app()->response->redirect($url, $terminate, $status);
    }


//    protected function error($number = 404, $message = 'Not found') {
//    	return \V::app()->error($number, $message);
//    }

    public function createUrl($route, $params = [], $ampersand = '&') {
	if($route === '')
	    $route = $this->id . '/' . $this->action;
	else if(strpos($route,'/') === false)
	    $route=$this->id . '/' . $route;

	return \V::app()->createUrl(trim($route,'/'), $params, $ampersand);
    }

    public function createAbsoluteUrl($route, $params=array(), $schema='', $ampersand='&') {
	$url=$this->createUrl($route, $params, $ampersand);
	if(strpos($url,'http') === 0)
	    return $url;
	else
	    return \V::app()->request->hostInfo . $url;
    }
}
