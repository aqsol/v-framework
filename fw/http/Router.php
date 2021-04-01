<?php
namespace http;

use \util\Set;
class Router extends \core\VObject {

    private $_rules = [];
    private $_baseUrl;


    public function __construct($config = []) {
	$defaults = [
	    'rules' => [],
	    'urlSuffix' => '',
	    'showScriptName' => true,
	    'appendParams' => true,
	    'caseSensitive' => false,
	    'matchValue' => false,
	    'useStrictParsing' => false,
	];

	$config = Set::merge($defaults, $config);
	parent::__construct($config);
    }


    // initialize rules
    public function _init() {
	parent::_init();
	//process rules
	if (empty($this->_config['rules']))
	    return;

	$ruleDefaults = [
	    'urlSuffix' => $this->urlSuffix,
	    'caseSensitive' => $this->caseSensitive,
	    'matchValue' => $this->matchValue,
	];
	//echo '<pre>' . print_r($this->_config['rules'], true) . '</pre>';
	foreach($this->_config['rules'] as $pattern=>$route) {
		$ruleConfig = $ruleDefaults;
		if (is_array($route)) {
		    $_route = $route[0];
		    unset($route[0]);
		    //$ruleConfig = \Set::merge($route, $ruleDefaults);
		    $ruleConfig['route'] = $_route;
		} else {
		    $ruleConfig['route'] = $route;
		}
		$ruleConfig['pattern'] = $pattern;
		//echo '<pre>' . print_r($ruleConfig, true) . '</pre>';
		$this->_rules[]= new \http\Rule($ruleConfig);

	}
    }

    public static function url($url) {
	if (is_array($url)) {
		if (isset($url[0])) {
		    $route = $url[0];
		    $params = array_splice($url, 1);
		    return \V::app()->createUrl($route, $params);
		} else {
		    throw new InvalidParamException('The array specifying a URL must contain at least one element.');
		}
	} elseif ($url === '') {
		return \V::app()->request->url;
	} else {
	    $url = Yii::getAlias($url);
	    if ($url[0] === '/' || $url[0] === '#' || strpos($url, '://')) {
		    return $url;
	    } else {
		    return Yii::$app->getRequest()->getBaseUrl() . '/' . $url;
		}
	}
    }



    /**
     * Constructs a URL.
     * @param string $route the controller and the action (e.g. article/read)
     * @param array $params list of GET parameters (name=>value). Both the name and value will be URL-encoded.
     * If the name is '#', the corresponding value will be treated as an anchor
     * and will be appended at the end of the URL.
     * @param string $ampersand the token separating name-value pairs in the URL. Defaults to '&'.
     * @return string the constructed URL
     */
    public function createUrl($route, $params = [], $ampersand = '&') {
	foreach($params as $i => $param)
	    if($param === null)
		$params[$i] = '';

	if(isset($params['#'])) {
	    $anchor = '#'.$params['#'];
	    unset($params['#']);
	} else
	    $anchor = '';

	$route=trim($route,'/');
	foreach($this->_rules as $i=>$rule) {
	    if(($url=$rule->createUrl($this, $route, $params, $ampersand))!==false) {
		if($rule->hasHostInfo) {
		    return $url==='' ? '/' . $anchor : $url . $anchor;
		} else {
		    return $this->getBaseUrl() . '/' . $url . $anchor;
		}
	    }
	}
	return $this->createUrlDefault($route, $params, $ampersand) . $anchor;
    }

    /**
     * Creates a URL based on default settings.
     * @param string $route the controller and the action (e.g. article/read)
     * @param array $params list of GET parameters
     * @param string $ampersand the token separating name-value pairs in the URL.
     * @return string the constructed URL
     */
    protected function createUrlDefault($route,$params,$ampersand) {
	//echo "entering cud: $route";
	$url = rtrim($this->getBaseUrl().'/'.$route,'/');
	//echo "after rtrim: $route";
	if($this->appendParams) {
	    $url=rtrim($url . '/' . $this->createPathInfo($params,'/','/'),'/');
	    return $route==='' ? $url : $url . $this->urlSuffix;
	} else {
	    if($route !== '')
		$url .= $this->urlSuffix;
	    $query = $this->createPathInfo($params,'=',$ampersand);
	    return $query==='' ? $url : $url . '?' . $query;
	}
    }

    /**
     * Parses the user request.
     * @param CHttpRequest $request the request application component
     * @return string the route (controllerID/actionID) and perhaps GET parameters in path format.
     */
    public function parseRequest($request) {
	$rawPathInfo=$request->pathInfo;
	$pathInfo=$this->removeUrlSuffix($rawPathInfo,$this->urlSuffix);
	foreach($this->_rules as $i => $rule) {
	    $r = $rule->parseUrl($this, $request, $pathInfo, $rawPathInfo);
	    if($r)
		return $r;
	}
	if($this->useStrictParsing)
	    \V::app()->error(404,"Unable to resolve the request '$pathInfo'");
	else
	    return $pathInfo;
    }

    /**
     * Parses a path info into URL segments and saves them to $_GET and $_REQUEST.
     * @param string $pathInfo path info
     */
    public function parsePathInfo($pathInfo) {
	    if($pathInfo==='')
		return;

	    $request = \V::app()->request;
	    $segs=explode('/',$pathInfo.'/');
	    $n=count($segs);
	    for($i=0;$i<$n-1;$i+=2) {
		$key=$segs[$i];
		if($key==='') continue;
		$value=$segs[$i+1];
		if(($pos=strpos($key,'['))!==false && ($m=preg_match_all('/\[(.*?)\]/',$key,$matches))>0) {
		    $name=substr($key,0,$pos);
		    for($j=$m-1;$j>=0;--$j) {
			if($matches[1][$j]==='')
			    $value=array($value);
			else
			    $value=array($matches[1][$j]=>$value);
		    }

		    if(isset($request->query[$name]) && is_array($request->query[$name]))
			$value=$request->query[$name] + $value;
		    $request->addParam('query', $name, $value);
		} else
		    $request->addParam('query', $key, $value);
	    }
	}

	/**
	 * Creates a path info based on the given parameters.
	 * @param array $params list of GET parameters
	 * @param string $equal the separator between name and value
	 * @param string $ampersand the separator between name-value pairs
	 * @param string $key this is used internally.
	 * @return string the created path info
	 */
	public function createPathInfo($params,$equal,$ampersand, $key=null) {
	    $pairs = array();
	    foreach($params as $k => $v) {
		if ($key!==null)
		    $k = $key.'['.$k.']';

		if (is_array($v))
		    $pairs[]=$this->createPathInfo($v,$equal,$ampersand, $k);
		else
		    $pairs[]=urlencode($k).$equal.urlencode($v);
	    }
	    return implode($ampersand,$pairs);
	}

	/**
	 * Removes the URL suffix from path info.
	 * @param string $pathInfo path info part in the URL
	 * @param string $urlSuffix the URL suffix to be removed
	 * @return string path info with URL suffix removed.
	 */
	public function removeUrlSuffix($pathInfo,$urlSuffix) {
	    if($urlSuffix!=='' && substr($pathInfo,-strlen($urlSuffix))===$urlSuffix)
		return substr($pathInfo,0,-strlen($urlSuffix));
	    else
		return $pathInfo;
	}

	/**
	 * Returns the base URL of the application.
	 * @return string the base URL of the application (the part after host name and before query string).
	 * If {@link showScriptName} is true, it will include the script name part.
	 * Otherwise, it will not, and the ending slashes are stripped off.
	 */
	public function getBaseUrl() {
	    if($this->_baseUrl!==null)
		return $this->_baseUrl;

	    if($this->_config['showScriptName'])
		$this->_baseUrl=\V::app()->request->scriptUrl;
	    else
		$this->_baseUrl=\V::app()->request->baseUrl;

	    return $this->_baseUrl;
	}


    /**
     * Sets the base URL of the application (the part after host name and before query string).
     * This method is provided in case the {@link baseUrl} cannot be determined automatically.
     * The ending slashes should be stripped off. And you are also responsible to remove the script name
     * if you set {@link showScriptName} to be false.
     * @param string $value the base URL of the application
     * @since 1.1.1
     */
    public function setBaseUrl($value) {
	$this->_baseUrl=$value;
    }

}


/**
 * CUrlRule represents a URL formatting/parsing rule.
 *
 * It mainly consists of two parts: route and pattern. The former classifies
 * the rule so that it only applies to specific controller-action route.
 * The latter performs the actual formatting and parsing role. The pattern
 * may have a set of named parameters.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package system.web
 * @since 1.0
 */
class Rule extends \core\VObject {

    // @var array the mapping from route param name to token name (e.g. _r1=><1>)
    public $references=array();

    // @var boolean whether host info should be considered for this rule
    public $hasHostInfo;

    // @var boolean whether the URL allows additional parameters at the end of the path info.
    public $append;

    // @var array list of parameters (name=>regular expression)
    public $params=array();

    // @var string template used to construct a URL
    public $template;

    // @var string the pattern used to match route
    public $routePattern;


    public function __construct($config = []) {
	    $defaults = array(
		'urlSuffix' => '',
		'caseSensitive' => false,
		'defaultParams' => array(),
		'matchValue' => null,
		'verb' => null,
		'parsingOnly' => false,
		'route' => '',
		'pattern' => '',
	    );
	    $config = Set::merge($defaults, $config);
	    parent::__construct($config);
	}

	public function _init() {
	    parent::_init();
	    //check the route
	    $this->route = trim($this->route, '/');
	    $tr2['/']=$tr['/']='\\/';

	    if(strpos($this->route,'<')!==false && preg_match_all('/<(\w+)>/',$this->route,$matches2)) {
		foreach($matches2[1] as $name)
		    $this->references[$name]="<$name>";
	    }

	    $this->hasHostInfo=!strncasecmp($this->pattern,'http://',7) || !strncasecmp($this->pattern,'https://',8);

	    if($this->verb!==null)
		$this->verb=preg_split('/[\s,]+/',strtoupper($this->verb),-1,PREG_SPLIT_NO_EMPTY);

	    if(preg_match_all('/<(\w+):?(.*?)?>/',$this->pattern,$matches)) {
		$tokens=array_combine($matches[1],$matches[2]);
		foreach($tokens as $name=>$value) {
		    if($value==='')
			$value='[^\/]+';
		    $tr["<$name>"]="(?P<$name>$value)";
		    if(isset($this->references[$name]))
			$tr2["<$name>"]=$tr["<$name>"];
		    else
			$this->params[$name]=$value;
		}
	    }

	    $p=rtrim($this->pattern,'*');
	    $this->append=$p!==$this->pattern;

	    $p=trim($p,'/');
	    $this->template=preg_replace('/<(\w+):?.*?>/','<$1>',$p);
	    $this->pattern='/^'.strtr($this->template,$tr).'\/';
	    if($this->append)
		$this->pattern.='/u';
	    else
		$this->pattern.='$/u';

	    if($this->references!==array())
		$this->routePattern='/^'.strtr($this->route,$tr2).'$/u';
/*
	    if(YII_DEBUG && @preg_match($this->pattern,'test')===false)
			throw new CException(Yii::t('yii','The URL pattern "{pattern}" for route "{route}" is not a valid regular expression.',
				array('{route}'=>$route,'{pattern}'=>$pattern)));
*/
	}


	/**
	 * Creates a URL based on this rule.
	 * @param CUrlManager $manager the manager
	 * @param string $route the route
	 * @param array $params list of parameters
	 * @param string $ampersand the token separating name-value pairs in the URL.
	 * @return mixed the constructed URL or false on error
	 */
	public function createUrl($manager,$route,$params,$ampersand) {
		if($this->parsingOnly)
			return false;

		$case=($this->caseSensitive)?'i':'';

		$tr=array();
		if($route!==$this->route) {
		    if($this->routePattern!==null && preg_match($this->routePattern.$case,$route,$matches)) {
			foreach($this->references as $key=>$name)
			    $tr[$name]=$matches[$key];
		    } else
			return false;
		}

		foreach($this->defaultParams as $key=>$value) {
		    if(isset($params[$key])) {
			if($params[$key]==$value)
			    unset($params[$key]);
			else
			    return false;
		    }
		}

		foreach($this->params as $key=>$value)
		    if(!isset($params[$key]))
			return false;

		if($this->matchValue) {
		    foreach($this->params as $key=>$value) {
			if(!preg_match('/\A'.$value.'\z/u'.$case,$params[$key]))
			    return false;
		    }
		}

		foreach($this->params as $key=>$value) {
		    $tr["<$key>"]=urlencode($params[$key]);
		    unset($params[$key]);
		}

		$url=strtr($this->template,$tr);

		if($this->hasHostInfo) {
		    $hostInfo=\V::app()->request->hostInfo;
		    if(stripos($url,$hostInfo)===0)
			$url=substr($url,strlen($hostInfo));
		}

		if(empty($params))
		    return $url!=='' ? $url.$this->suffix : $url;

		if($this->append)
		    $url.='/'.$manager->createPathInfo($params,'/','/').$this->suffix;
		else {
		    if($url!=='')
			$url.=$this->suffix;
		    $url.='?'.$manager->createPathInfo($params,'=',$ampersand);
		}

		return $url;
	}

    /**
     * Parses a URL based on this rule.
     * @param CUrlManager $manager the URL manager
     * @param CHttpRequest $request the request object
     * @param string $pathInfo path info part of the URL
     * @param string $rawPathInfo path info that contains the potential URL suffix
     * @return mixed the route that consists of the controller ID and action ID or false on error
     */
    public function parseUrl($manager,$request,$pathInfo,$rawPathInfo) {
	if($this->verb !== null && !in_array($request->method, $this->verb, true))
	    return false;

	$case = ($this->caseSensitive) ? 'i' : '';

	if($this->urlSuffix!==null)
	    $pathInfo=$manager->removeUrlSuffix($rawPathInfo,$this->urlSuffix);

	// URL suffix required, but not found in the requested URL
	if($manager->useStrictParsing && $pathInfo===$rawPathInfo) {
	    if($this->urlSuffix!='' && $this->urlSuffix!=='/')
		return false;
	}

	if($this->hasHostInfo) {
	    $pathInfo=strtolower($request->_getHostInfo()).rtrim('/' . $pathInfo, '/');
	}

	$pathInfo .= '/';

	if(preg_match($this->pattern . $case, $pathInfo, $matches)) {
	    foreach($this->defaultParams as $name=>$value) {
		if(!isset($request->query[$name]))
		    $request->addParam('query', $name, $value);
	    }
	    $tr = [];
	    foreach($matches as $key=>$value) {
		if(isset($this->references[$key]))
		    $tr[$this->references[$key]] = $value;
		else if(isset($this->params[$key]))
		    $request->addParam('query', $key, $value);
	    }
	    if($pathInfo!==$matches[0]) // there're additional GET params
		$manager->parsePathInfo(ltrim(substr($pathInfo,strlen($matches[0])),'/'));
	    if($this->routePattern!==null)
		return strtr($this->route,$tr);
	    else
		return $this->route;
	} else {
	    return false;
	}
    }
}
