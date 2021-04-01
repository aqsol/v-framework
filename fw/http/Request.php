<?php
namespace http;

class Request extends \core\VObject {

    // Strip slashes from string or array
    protected static function _stripSlashes($rawData) {
        return is_array($rawData) ? array_map(array('self', '_stripSlashes'), $rawData) : stripslashes($rawData);
    }


    protected function _init() {
	if(isset($_GET))
	    $_GET = $this->_stripSlashes($_GET);
	if(isset($_POST))
	    $_POST = $this->_stripSlashes($_POST);
	if(isset($_REQUEST))
	    $_REQUEST = $this->_stripSlashes($_REQUEST);
	if(isset($_COOKIE))
	    $_COOKIE = $this->_stripSlashes($_COOKIE);

	//get query data
	$this->_config['query'] = $_GET;
	$this->_config['data'] = $_POST;
	$this->_config['cookies'] = $_COOKIE;
	$this->_config['files'] = $_FILES;


	//get request method
	if (isset($_POST['_method']))
	    $this->_config['method'] = strtoupper($_POST['_method']);
	elseif (isset($_SERVER['REQUEST_METHOD']))
	    $this->_config['method'] = strtoupper($_SERVER['REQUEST_METHOD']);


	if (isset($_POST['_method']) && $_POST['_method'] == 'DELETE') {
	    //delete via post
	    $this->_config['delete'] = $_POST;
	    unset($this->_config['delete']['_method']);
	} elseif ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
	    $this->_config['delete'] = $this->_getRestParams();
	} else {
	    $this->_config['delete'] = array();
	}

	if (isset($_POST['_method']) && $_POST['_method'] == 'PUT') {
	    //put via post
	    $this->_config['put'] = $_POST;
	    unset($this->_config['put']['_method']);
	} elseif ($_SERVER['REQUEST_METHOD'] == 'PUT') {
	    $this->_config['put'] = $this->_getRestParams();
	} else {
	    $this->_config['put'] = [];
	}

	unset($_POST['_method']);


	$this->_config['referrer'] = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
	$this->_config['userAgent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
//	$this->_config['browser'] = get_browser($userAgent,true);
	$this->_config['httpAccept'] = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;

	$this->_config['userIP'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1';

	$this->_config['secure'] = !empty($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'],'off');
	$this->_config['port'] = !$this->_config['secure'] && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 80;
	$this->_config['securePort'] = $this->_config['secure'] && isset($_SERVER['SERVER_PORT']) ? (int)$_SERVER['SERVER_PORT'] : 443;
	$this->_config['schema'] = $this->_config['secure'] ? 'https://' : 'http://';


	$this->_config['baseUrl'] = rtrim(dirname($_SERVER['PHP_SELF']), '\\/');
	$this->_config['requestUri'] = $_SERVER['REQUEST_URI'];
	$this->_config['url'] = $_SERVER['REQUEST_URI'];
	$this->_config['hostInfo'] = $this->_getHostInfo();
	$this->_config['scriptUrl'] = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_SERVER['PHP_SELF'];
	$this->_config['pathInfo'] = $this->_getPathInfo();
	$this->_config['httpHost'] = rtrim(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'], '.');



	$this->_config['isPost'] = $this->_config['method'] == 'POST';
	$this->_config['isDelete'] = $this->_config['method'] == 'DELETE';
	$this->_config['isPut'] = $this->_config['method'] == 'PUT';
	$this->_config['isAjax'] = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']==='XMLHttpRequest';
	$this->_config['isFlash'] = isset($_SERVER['HTTP_USER_AGENT']) && (stripos($_SERVER['HTTP_USER_AGENT'],'Shockwave')!==false || stripos($_SERVER['HTTP_USER_AGENT'],'Flash')!==false);

	//get script filename
	$this->_config['scriptFile'] = realpath($_SERVER['SCRIPT_FILENAME']);
    }

    public function getQueryParam($key, $default = null) {
	if (isset($this->_config['query'][$key]))
	    return $this->_config['query'][$key];
	return $default;
    }

    public function getDataParam($key, $default = null) {
	if (isset($this->_config['data'][$key]))
	    return $this->_config['data'][$key];
	return $default;
    }

    public function addParam($type = 'query', $name, $value) {
	if (is_array($this->_config[$type]))
	    $this->_config[$type][$name] = $value;
	else
	    $this->_config[$type] = [ $name => $value ];
    }


    /**
     * Returns the PUT or DELETE request parameters.
     * @return array the request parameters
     * @since 1.1.7
     */
    protected function _getRestParams() {
	$result = [];
	if(function_exists('mb_parse_str'))
	    mb_parse_str(file_get_contents('php://input'), $result);
	else
	    parse_str(file_get_contents('php://input'), $result);
	return $result;
    }

    public function _getHostInfo($secure = null, $hostInfo = null, $port = null) {
	if ($secure === null) {
	    $secure = $this->_config['secure'];
	}
	if($secure)
	    $schema='https://';
	else
	    $schema='http://';

	if ($hostInfo === null) {
	    if(isset($_SERVER['HTTP_HOST']))
		$hostInfo = $_SERVER['HTTP_HOST'];
	    else
		$hostInfo = $_SERVER['SERVER_NAME'];
	}
	//SC: 20180622: added a rtrim to hostInfo
	rtrim($hostInfo, '.');


	if ($port === null) {
	    $port = $secure ? $this->_config['securePort'] : $this->_config['port'];
	}
	$portInfo = '';
	if(($port!==80 && !$secure) || ($port!==443 && $secure))
		$portInfo = ':' . $port;

	return $schema . $hostInfo . $portInfo;
    }


    /**
     * Returns the path info of the currently requested URL.
     * This refers to the part that is after the entry script and before the question mark.
     * The starting and ending slashes are stripped off.
     */
    public function _getPathInfo() {
	$pathInfo = $this->_config['requestUri'];

	if(($pos=strpos($pathInfo,'?')) !== false)
	    $pathInfo=substr($pathInfo, 0, $pos);

	$pathInfo = urldecode($pathInfo);

	$scriptUrl = $this->_config['scriptUrl'];
	$baseUrl = $this->_config['baseUrl'];

	if(strpos($pathInfo,$scriptUrl) === 0)
	    $pathInfo = substr($pathInfo, strlen($scriptUrl));
	else if($baseUrl === '' || strpos($pathInfo,$baseUrl) === 0)
	    $pathInfo = substr($pathInfo, strlen($baseUrl));
	else if(strpos($_SERVER['PHP_SELF'],$scriptUrl) === 0)
	    $pathInfo = substr($_SERVER['PHP_SELF'],strlen($scriptUrl));
	else
	    throw new \ErrorException('Request is unable to determine the path info of the request.');

	return trim($pathInfo,'/');
    }
}


