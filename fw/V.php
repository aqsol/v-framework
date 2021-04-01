<?php

require(dirname(__FILE__) . '/core/VObject.php');
require(dirname(__FILE__) . '/util/Set.php');
require(dirname(__FILE__) . '/util/VString.php');

class EndException extends \Exception {}

class V extends \core\VObject {

    //objects catalog for single instance objects
    protected static $catalog;

    //constructor, initialize configuration
    public function __construct($config = []) {
//	$this->_dbg("--construct--");
	$defaults = [
	    'autoload' => [ 'ns_map' => [], 'class_map' => [], 'requires' => [] ],
	];
	$config = \util\Set::merge($defaults, $config);
	$config['init'] = false; //do not auto-init stuff
	parent::__construct($config);
    }

    public function _dbg($message) {
	$fp = fopen(BASE . '/logs/core_debug.log', 'a+');
//    	$res = fwrite($fp, date('c') . ':' . (string)$message . PHP_EOL);
    	$res = fwrite($fp, (string)$message . PHP_EOL);
    	fclose($fp);
    }

    public function _backtrace($called_class = '', $args_data = null) {
	$this->_dbg("Backtrace: called_class: $called_class: " . print_r($args_data, true));
	$btr = debug_backtrace();
	$lines = [];
	foreach($btr as $k => $btr_data) {
	    if ($k == 0) continue;
	    $line = "$k: ";
	    if (isset($btr_data['file']))
		$line .= "file: {$btr_data['file']} ({$btr_data['line']}): ";
		
	    if (isset($btr_data['class']))
		$line .= "method: {$btr_data['class']}::{$btr_data['function']}";
	    else
		$line .= "function: {$btr_data['function']}";
	    $lines[] = $line;
	}
	$this->_dbg("\n" . implode("\n", $lines));
    }	

    public function loadComponent($cid, $cConfig = []) {
	//$this->_dbg("loading component: $cid...");
	//load component
	$class = $cConfig['class'];
	unset($cConfig['class']);
	unset($cConfig['preload']);

	static::$catalog[$cid] = new $class($cConfig);
	return static::$catalog[$cid];
    }

    public function _init() {
	//set autoload paths
	//NB: we don't check anything here; if you do not provide data...well...it will not work
	foreach($this->_config['autoload']['ns_map'] as $prefix => $path) {
	    $this->addNsMap($prefix, $path);
	}
	foreach($this->_config['autoload']['class_map'] as $class => $path) {
	    $this->addClassMap($class, $path);
	}

	foreach($this->_config['autoload']['requires'] as $__file) {
	    require $__file;
	}

	spl_autoload_register([ $this, 'autoload_handler' ]);

	//initialize exception, error, shutdown handlers
    	set_exception_handler([ $this, 'exceptionHandler']);
    	set_error_handler(function($errno, $errstr = '', $errfile = '', $errline = '') {
    	    if (error_reporting() & $errno)
    		throw new \ErrorException($errstr, $errno, 0, $errfile, $errline);
    	});

	//load components that needs to be preloaded
	//$this->_dbg("init: preloading components...");
	foreach($this->_config['components'] as $cid => $cConfig) {
	    if (isset($cConfig['autoload.requires'])) {
		foreach($cConfig['autoload.requires'] as $__file)
		    require $__file;
	    }

	    if (isset($cConfig['autoload.namespaces'])) {
		foreach($cConfig['autoload.namespaces'] as $__ns => $__pfx)
		    $this->addNsMap($__ns, $__pfx);
	    }
	    if (isset($cConfig['preload']) && ($cConfig['preload'] == true)) {
		//$this->_dbg("init: preloading $cid");
		$this->loadComponent($cid, $cConfig);
	    }
	}
	//$this->_dbg("init: preloading components done");

	if (!empty($this->_config['app.timezone']))
	    date_default_timezone_set($this->_config['app.timezone']);
	if (!empty($this->_config['app.language']))
	    setlocale(LC_CTYPE, $this->_config['app.language']);

	parent::_init();
    }

    // Stop the application and immediately send the response with a specific status and body to the HTTP client.
    public function error($status, $message = '') {
//	\V::app()->log->debug('V::error: entering....');
    	if (ob_get_level() !== 0)
    	    ob_clean();

	$title = $this->response->getMessageForCode($status);

	$template = APP . '/' . $this->view->layouts_path . '/error_' . $status . '.php';
	if (is_readable($template)) {
	    $error_layout = $this->_config['app.error.layout'];
	    $body = $this->view->render($template, [ 'title' => $title, 'body' => $message ], $error_layout, true);
	} else {
	    $body = $title . "\n" . $message;
	}
		
	$this->response->status = $status;
	$this->response->write($body, true);
	$this->response->output();
    	$this->_stop(1); //defined in VObject
    }

    public function exceptionHandler($exception) {
	$message = " <p>The application could not run because of the following error:</p>\n";
	if ($type = get_class($exception))
    	    $message .= "<div><strong>Type:</strong> $type</div>\n";
	if ($errcode = $exception->getCode())
    	    $message .= "<div><strong>Code:</strong> $errcode</div>\n";
	if ($errstr = $exception->getMessage())
    	    $message .= "<div><strong>Message:</strong> $errstr</div>\n";
	if ($file = $exception->getFile())
    	    $message .= "<div><strong>File:</strong> $file</div>\n";
    	if ($line = $exception->getLine())
    	    $message .= "<div><strong>Line:</strong> $line</div>\n";
    	if ($trace = $exception->getTraceAsString())
    	    $message .= "<h2>Trace</h2>\n<pre>$trace</pre>\n";

	$this->error(500, $message);
    }


    //override setter/getter to operate on catalog (aka components)
    public function &__get($name) {
	if (isset($this->_config['components'][$name]) && !isset(static::$catalog[$name]))
	    $this->loadComponent($name, $this->_config['components'][$name]);

	if (isset(static::$catalog[$name]))
	    return static::$catalog[$name];

	$null = null;
	return $null;
    }

    public function __set($name, $value = null) {
	if (is_array($name) && !$value)
	    return array_map(array(&$this, '__set'), array_keys($name), array_values($name));

	return static::$catalog[$name] = $value;
    }

    public function __isset($name) {
	return isset(static::$catalog[$name]);
    }

    public function config($name, $value = null) {
	if ($name === null)
	    return null;

	//get config parameter 'name'
	if ($value === null) {
	    if (!array_key_exists($name, $this->_config))
		$this->_config[$name] = null;
	    return $this->_config[$name];
	}
	
	//value is not null set it
	return $this->_config[$name] = $value;
    }



    private static $default_config = [
	'web' => [
	    'mode' => 'web',
	    'default.controller' => 'site',
	    'default.action' => 'index',
	    'module.classSuffix' => 'Controller',
	    'module.namespace' => 'controllers',

	    'components' => [
		'hooks' => [ 'class' => '\core\Hooks', 'preload' => true, ],
		'request' => [ 'class' => '\http\Request', ],
		'response' => [ 'class' => '\http\Response', ],
		'view' => [
		    'class' => '\core\View',
		    'views_path' => 'views', //relative to app directory
		    'layouts_path' => 'views/layouts', //relative to app directory
		    'default_layout' => 'main',
		],
		'clientscript' => [ 'class' => '\util\ClientScript', ],
	    ],
	],
	'console' => [
	    'mode' => 'console',
	    'default.controller' => 'help',
	    'default.action' => 'run',
	    'module.classSuffix' => 'Command',
	    'module.namespace' => 'commands',

	    'components' => [
		'hooks' => [ 'preload' => true, 'class' => '\core\Hooks', ],
		'request' => [ 'class' => '\console\Request', ],
		'response' => [ 'class' => '\console\Response', ],
		'view' => [
		    'class' => '\core\View',
		    'views_path' => 'views', //relative to app directory
		    'layouts_path' => 'views/layouts', //relative to app directory
		    'default_layout' => 'main',
		],
		'router' => [ 'class' => '\console\Router', ],
		'clientscript' => [ 'class' => '\util\ClientScript', ],
	    ],
	],
    ];


    public static function create($config, $mode) {
	$cfg = \util\Set::merge(static::$default_config[$mode], $config);
	$app = static::$catalog['app'] = new self($cfg);
	$app->_init();
	return $app;
    }

    // get the application singleton
    public static function & app() {
	return static::$catalog['app'];
    }


    //this will stop the application
    public function end() {
	//get any un-written output and write it
    	$this->response->write(ob_get_clean());
    	$this->response->output();
	$this->_stop();
    }


    public function run() {
	$this->hooks->applyHook('on:start');

    	$this->hooks->applyHook('on:before-router');
	//parse the url/command and return route
	$route = $this->router->parseRequest($this->request);
	$route = trim($route, '/');
	//save route in c_config; after router might need it
	$this->config('c_route', $route);

    	$this->hooks->applyHook('on:after-router');
	$route = $this->config('c_route');

	//parse route and instantiate controller
	$segs = explode('/', $route);

	$module = null;
	$all_modules = $this->config('modules');
	//search for a module by $segs[0]; if found, controller/action from it; else, treat as controller/action
	if (!empty($segs[0]) && array_key_exists($segs[0], $all_modules)) {
	    $module = array_shift($segs);
	}

	$controller = $this->config('default.controller');
	if (!empty($segs[0]))
	    $controller = array_shift($segs);

	$action = $this->config('default.action');
	if (!empty($segs[0]))
	    $action = array_shift($segs);

	//save the controller/action for further use
	$this->_config['c_module'] = $module;
	$this->_config['c_controller'] = $controller;
	$this->_config['c_action'] = $action;
	if ($module)
	    $this->_config['c_module_path'] = strtr($all_modules[$module]['namespace'], '\\', '/');


	$this->hooks->applyHook('on:before-action');

	//delegate to module the part of finding the controller and run the action
	//$module->runAction($controller, $action);

	$pathSegs = [];
	$pathSegs[] = APP;
	if ($module)
	    $pathSegs[] = $this->_config['c_module_path'];

	$pathSegs[] = $this->config('module.namespace');


	//now we need to find controller/command somewhere
	$cClass = ucfirst(strtolower($controller)) . $this->config('module.classSuffix');
	$pathSegs[] = $cClass;
	$cFile = implode('/', $pathSegs) . '.php';


	if (is_file($cFile)) {
	    require $cFile;
	} else {
	    $this->error(404, "Controller/command `$cClass` not found");
	}

	$cClass = $this->config('module.namespace') . '\\' . $cClass;
	if ($module)
	    $cClass = $all_modules[$module]['namespace'] . '\\' . $cClass;

	//$this->log->debug('V::run: class to run=' . $cClass);

	//now initialize the controller and run action
    	$instance = new $cClass([ 'action' => $action, 'id' => $controller, 'module' => $module ]);
	//\V::app()->controller = $instance;

	if(method_exists($instance,$action)) 
	    $instance->$action();
	else
	    \V::app()->error(404, "Cannot call controller action, missing action '$action'");


	$this->hooks->applyHook('on:after-action');

    	restore_error_handler();
    	restore_exception_handler();

    	$this->response->write(ob_get_clean());
    	$this->response->output();

	$this->hooks->applyHook('on:end');
    }


    public function createUrl($route, $params = [], $ampersand = '&') {
	//if empty - put just action
	if ($route === '')
	    $route = $this->_config['c_action'];

	if (strpos($route, '/') === false)
	    $route = $this->_config['c_controller'] . '/' . $route;

	//if not absolute and we have module - prefix it
	if ($route[0] != '/' && !empty($this->_config['c_module']))
	    $route = $this->_config['c_module'] .'/' . $route;

	$route = trim($route, '/');
	$url = $this->router->createUrl($route, $params, $ampersand);
//	\V::app()->log->debug(__METHOD__ . ": url=$url");
	return $url;
    }

    public function createAbsoluteUrl($route, $params = [], $schema = '', $ampersand = '&') {
	$url = $this->createUrl($route, $params, $ampersand);
	if (strpos($url,'http')===0 )
	    return $url;

	return $this->request->hostInfo . $url;
    }


    public static function t($message, $params = []) {
	// no translation done yet, just the framework
	if (isset(static::$catalog['translate']))
	    return static::$catalog['translate']->t($message, $params);

	return \util\VString::insert($message, $params);
    }

    // autoload -------------------------------------------------------------
    private static $class_map = [
	'core\View'		=> FW . '/core/View.php',
	'core\Hooks'		=> FW . '/core/Hooks.php',
	'core\Security'		=> FW . '/core/Security.php',
	'core\SessionMongo' 	=> FW . '/core/SessionMongo.php',
	'core\Session' 		=> FW . '/core/Session.php',
	'core\User'		=> FW . '/core/User.php',
	//'core\Module'		=> FW . '/core/Module.php',
	'core\Controller'	=> FW . '/core/Controller.php',

	'data\Mongo'		=> FW . '/data/Mongo.php',
	'data\Validator'	=> FW . '/data/Validator.php',
	'data\MongoModel'	=> FW . '/data/MongoModel.php',
	'data\FormModel'	=> FW . '/data/FormModel.php',
	'data\MongoDataProvider'=> FW . '/data/MongoDataProvider.php',
	'data\VRedis'		=> FW . '/data/VRedis.php',
	'data\Pagination'	=> FW . '/data/Pagination.php',
	'data\ArrayDataProvider'=> FW . '/data/ArrayDataProvider.php',

	'auth\PhpManager'	=> FW . '/auth/PhpManager.php',
	'auth\Manager'		=> FW . '/auth/Manager.php',
	'auth\Item'		=> FW . '/auth/Item.php',
	'auth\Assignment'	=> FW . '/auth/Assignment.php',
	

	'http\Request'		=> FW . '/http/Request.php',
	'http\Response'		=> FW . '/http/Response.php',
	'http\Router'		=> FW . '/http/Router.php',
	'http\Dispatcher'	=> FW . '/http/Dispatcher.php',

	'util\Html'		=> FW . '/util/Html.php',
	'util\AssetManager'	=> FW . '/util/AssetManager.php',
	'util\ClientScript'	=> FW . '/util/ClientScript.php',

	'widget\GridView'	=> FW . '/widget/GridView.php',
	'widget\BaseListView'	=> FW . '/widget/BaseListView.php',
	'widget\Widget'		=> FW . '/widget/Widget.php',
	'widget\LinkPager'	=> FW . '/widget/LinkPager.php',
	'widget\DataColumn'	=> FW . '/widget/DataColumn.php',
	'widget\Column'		=> FW . '/widget/Column.php',
	'widget\ActionColumn'	=> FW . '/widget/ActionColumn.php',
	'widget\Menu'		=> FW . '/widget/Menu.php',
	'widget\MenuBase'	=> FW . '/widget/MenuBase.php',
    ];

    private static $ns_map = [
	'core\\'	=> [ FW . '/core/' ],
	'http\\'	=> [ FW . '/http/' ],
	'data\\'	=> [ FW . '/data/' ],
	'util\\'	=> [ FW . '/util/' ],
	'console\\'	=> [ FW . '/console/' ],
	'auth\\'	=> [ FW . '/auth/' ],
//	'websocket\\'	=> [ FW . '/websocket/' ],
	'widget\\'	=> [ FW . '/widget/' ],
	'queue\\'	=> [ FW . '/queue/' ],
    ];

    public function addNsMap($prefix, $base_dir, $prepend = false) {
	//$this->_dbg("V::loader: adding ns map: prefix=$prefix, base_dir=$base_dir");
	// normalize namespace prefix: make sure it ends in \
        $prefix = trim($prefix, '\\') . '\\';
                        
        // normalize the base directory with a trailing separator
        $base_dir = rtrim($base_dir, '/') . '/';
                                        
        // initialize the namespace prefix array
        if (empty(static::$ns_map[$prefix]))
    	    static::$ns_map[$prefix] = [];

        if ($prepend) {
            array_unshift(static::$ns_map[$prefix], $base_dir);
        } else {
            array_push(static::$ns_map[$prefix], $base_dir);
        }
    }

    public function addClassMap($class, $path, $prepend = false) {
	//$this->_dbg("V::loader: adding class map: class=$class, path=$path");
        static::$class_map[$class] = $path;
    }

    public function autoload_handler($class) {
//	$this->_dbg("V::loader: requested class=$class");
	//if a class is defined here in class_map, just require it and return; no file check; be aware!!!!
	if (isset(static::$class_map[$class])) {
	    //$this->_dbg("V::loader: found class map, file=" . static::$class_map[$class]);
            return require(static::$class_map[$class]);
	}

	$__class = $class;
	foreach(static::$ns_map as $prefix => $paths) {
	    //check if $ns is found in class
	    if (strpos($__class, $prefix) !== 0)
		continue;

//	    $this->_dbg("V::loader: prefix found:$prefix");

	    //prefix found, strip prefix from class
	    $__class = str_replace($prefix, '', $__class);
	    $__class = str_replace('\\', '/', $__class);
	    //loop thru paths, check for file
	    foreach($paths as $path) {
		//build a file name
		$filename = $path . $__class . '.php';
//		$this->_dbg("V::loader: try filename:$filename");
		if (is_file($filename)) {
        	    return require($filename);
		}
	    }
	}
        return false;
    }
}
