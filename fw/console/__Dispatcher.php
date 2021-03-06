<?php

namespace console;

class Dispatcher {

    /**
     * Dispatches a request based on a request object (an instance of `console\Request`).
     *
     * @param array $options
     * @return object The command action result which is an instance of `lithium\console\Response`.
     */
    public static function run() {
	\V::app()->request->params = $params = \V::app()->router->parseRequest(\V::app()->request);
	//print_r($app->request->params); die();

	//command might be in the form module/command
	$command = @$params['command'] ? $params['command'] : \V::app()->config['default.command'];
	$command = trim($command, '/');

	$segs = explode('/', $command);

	$mId = '';
	$mClass = '\core\Module';
	//search for a module by $segs[0]; if found, command/action from it; else, treat as command/action
	if (!empty($segs[0]) && array_key_exists($segs[0], \V::app()->config('modules'))) {
	    $mId = array_shift($segs);
	    $mClass = \V::app()->config('modules')[$mId]['class'];
	}
		
	$module = new $mClass([ 'id' => $mId, 'classSuffix' => 'Command', 'namespace' => 'commands' ]);

	$command = $segs[0];
	$action = $params['action'];

	//save the controller/action for further use
	\V::app()->config('c_controller', $command);
	\V::app()->config('c_action', $action);


	$module->runAction($command, $action);

    	// final output: Fetch status, header, and body
    	//\V::app()->response->output();
    }
}
