<?php

namespace http;

class Dispatcher {

    public function run() {
	//ob_start();
    	\V::app()->hooks->applyHook('before.router');
	//parse the url and return route
	$route = \V::app()->router->parseRequest(\V::app()->request);
    	\V::app()->hooks->applyHook('after.router');


	//parse route and instantiate controller
	$route = trim($route, '/');

	$segs = explode('/', $route);

	$mId = '';
	$mClass = '\core\Module';
	//search for a module by $segs[0]; if found, controller/action from it; else, treat as controller/action
	if (!empty($segs[0]) && array_key_exists($segs[0], \V::app()->config('modules'))) {
	    $mId = array_shift($segs);
	    $mClass = \V::app()->config('modules')[$mId]['class'];
	}

	//\V::app()->log->debug("Dispatcher: $mClass");
	
	$module = new $mClass([ 'id' => $mId ]);

	//\V::app()->log->debug("Dispatcher: $mClass instantiated");

		
	$controller = \V::app()->config('default.controller');
	if (!empty($segs[0]))
	    $controller = array_shift($segs);
		
	$action = \V::app()->config('default.action');
	if (!empty($segs[0]))
	    $action = array_shift($segs);

	//\V::app()->log->debug("Dispatcher: controller/action=$controller/$action");

	//save the controller/action for further use
	\V::app()->config('c_module', $module);
	\V::app()->config('c_controller', $controller);
	\V::app()->config('c_action', $action);
	\V::app()->config('c_route', $route);

	//delegate to module the part of finding the controller and run the action
	$module->runAction($controller, $action);

    	//\V::app()->response->output(ob_get_clean());
    }


}