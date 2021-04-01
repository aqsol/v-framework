<?php

namespace core;

class Module extends \core\Vobject {

    public function __construct($config = []) {
	$defaults = array(
	    'id' => null,
	    'classSuffix' => 'Controller',
	    'namespace' => 'controllers',
	);
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);

    }


    //version without autoloader
    function runAction($controller, $action) {
	$pathSegs = [];
	$pathSegs[] = APP;
	if ($this->id) {
	    $pathSegs[] = strtr(get_called_class(), '\\', '/');
	}
	
//	$pathSegs[] = $this->classesDir;
	$pathSegs[] = $this->namespace;

	//now we need to find controller/command somewhere
	$cClass = ucfirst(strtolower($controller)) . $this->classSuffix;

	$pathSegs[] = $cClass;

	$cFile = implode('/', $pathSegs) . '.php';

	if (is_file($cFile)) {
	    require $cFile;
	} else {
	    \V::app()->error(404, "Controller/command `$cClass` not found");
	}

	$cClass = $this->namespace . '\\' . $cClass;

	//now initialize the controller and run action
    	$instance = new $cClass([ 'action' => $action, 'id' => $controller ]);
	\V::app()->controller = $instance;

	if(method_exists($instance,$action)) 
	    $instance->$action();
	else
	    \V::app()->error(404, "Cannot call controller action, missing action '$action'");

    }

/*
    //version with autoloader
    function runAction($controller, $action) {
	//we let the autoloader do it's job

	//now we need to find controller/command somewhere
	$cClass = $this->namespace . '\\' . ucfirst(strtolower($controller)) . $this->classSuffix;

	//now initialize the controller and run action
	try {
    	    $instance = new $cClass([ 'action' => $action, 'id' => $controller ]);
    	} catch ( \Error $e) {
    	    \V::app()->error(404, "Cannot call controller: not found");
    	}
	\V::app()->controller = $instance;

	if(method_exists($instance,$action) || $instance->has_attachment($action)) 
	    $instance->$action();
	else
	    \V::app()->error(404, "Cannot call controller action, missing action '$action'");

    }
*/

}