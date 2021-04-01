<?php

namespace core;

class User extends \core\VObject {

    public $session_key;
    public $flash_key;

    public function __construct($config = array()) {
	$defaults = array(
	    'guestName' => 'guest',
	    'session.key' => 'user', //'user.auth',
	    'flash.key' => 'user.flash',
	    'login.url' => '/site/login',
	    'identity.cookie' => '_identity',
	    'autologin' => true,
	    'cookie.domain' => 'xxx',
	);
	$config += $defaults;
	parent::__construct($config);
    }

    protected function _init() {
	$this->session_key = $this->_config['session.key'];
	$this->flash_key = $this->_config['flash.key'];
	//initialize so that component can load data before any request
    	\V::app()->hooks->addHook('on:start', [ $this, 'initialize' ]);
    }

    // Initializes the application component.
    public function initialize($auto_login = true) {
	//if the key does not exist, initialize to defaults
	if (!isset($_SESSION['user'])) {
	    $_SESSION['user'] = [
		'id' => null,
		'name' => $this->guestName,
		'isGuest' => true,
	    ];
	}
	//refresh userIP in session
	$_SESSION['user']['userIP'] = \V::app()->request->userIP;

	if (isset(\V::app()->csrf))
	    \V::app()->csrf->getToken(false); // do not refresh token if present


	if (!$auto_login)
	    return;

	//check autologin
	if ($this->isGuest && $this->_config['autologin']) {
	    $cookie_name = $this->_config['identity.cookie'];
	    if (isset(\V::app()->request->cookies[$cookie_name])) {
		$data = \V::app()->security->decrypt(\V::app()->request->cookies[$cookie_name]);
		$data = unserialize($data);
		if ($data) {
		    $identity = unserialize($data['identity']);
		    $this->login($identity, $duration = $data['duration']);
		}
	    }
	}
    }

    public function afterLogin() {
    }


    // The user identity information will be saved in session storage
    public function login($identity, $duration = 0) {
	//\V::app()->log->debug('User::login: enter....');
	//if (\V::app()->session !== null)
	//    \V::app()->session->regenerateID(true);

	$saveReturnUrl = $_SESSION['user']['returnUrl'] ?? null;
	$this->data($identity->getData());

	if($saveReturnUrl)
	    $_SESSION['user']['returnUrl'] = $saveReturnUrl;

	$_SESSION['user']['isGuest'] = ($_SESSION['user']['id'] === null);

	//autologin cookie
	if (($duration > 0) && $this->_config['autologin']) {
	    $this->sendIdentityCookie($identity, $duration);
	}

	if (isset(\V::app()->csrf))
	    \V::app()->csrf->getToken(true); //refresh token


	$this->afterLogin();
	return $_SESSION['user']['isGuest'];
    }

    // Logs out the current user. Remove authentication-related session data.
    public function logout($destroySession=true) {
	\V::app()->session->destroy(session_id());
	unset($_SESSION['user']);
	$this->removeIdentityCookie();
	$this->initialize($auto_login = false);
    }

    // Returns the URL that the user should be redirected to after successful login.
    // This property is usually used by the login action. If the login is successful,
    // the action should read this property and use it to redirect the user browser.
    public function getReturnUrl($defaultUrl=null) {
	return $_SESSION['user']['returnUrl'] ?? $defaultUrl;
    }


    //SC: wip....
    public function sendIdentityCookie($identity, $duration) {
	$data = [
	    'duration' => $duration,
	    'identity' => serialize($identity),
	];
	$data = \V::app()->security->encrypt(serialize($data));
	$cookie = new \http\Cookie([
	    'name' => $this->_config['identity.cookie'],
	    'value' => $data,
	    'expire' => time() + $duration,
	    'httpOnly' => true,
	    'domain' => $this->_config['cookie.domain'],
	]);
	\V::app()->response->addCookie($cookie);
    }
    
    public function removeIdentityCookie() {
	$cookie = new \http\Cookie([
	    'name' => $this->_config['identity.cookie'],
	    'value' => '',
	    'expire' => time() - 3600,
	    'httpOnly' => true,
	    'domain' => $this->_config['cookie.domain'],
	]);
	\V::app()->response->addCookie($cookie);
    }
    

    public function getLoginUrl() {
	return $this->_config['login.url'];
    }

    // Redirects the user browser to the login page.
    // Before the redirection, the current URL (if it's not an AJAX url) will be
    // kept in {@link returnUrl} so that the user browser may be redirected back
    // to the current page after successful login. Make sure you set {@link loginUrl}
    // so that the user browser can be redirected to the specified login URL after
    // calling this method.
    public function loginRequired() {
	if(!\V::app()->request->isAjax) {
	    $_SESSION['user']['returnUrl'] = str_replace(\V::app()->request->baseUrl, '', \V::app()->request->url);

    	    $url = $this->_config['login.url'];
	    if($url !== null)
		\V::app()->response->redirect($url);
	    
	    \V::app()->error(403, \V::t('Login Required'));
	}

	\V::app()->error(403, \V::t('Login Required'));
    }

    //get/set all user data as stored in session
    public function data($data = null) {
	if ($data === null)
	    return $_SESSION['user'];
	else
	    $_SESSION['user'] = $data;
    }


    public function &__get($name) {
	if (!isset($_SESSION['user'][$name])) {
	    $_SESSION['user'][$name] = null;
	}
	return $_SESSION['user'][$name];

    }

    public function __set($name, $value = null) {
	if($value===null)
	    unset($_SESSION['user'][$name]);
	else
	    $_SESSION['user'][$name] = $value;
    }

    public function __isset($name) {
	return isset($_SESSION['user'][$name]);
    }




    // --------------------------------------------------------------------------------
    //user flash section
    public function setFlash($key, $message) {
	$_SESSION[$this->flash_key][$key] = $message;
    }

    public function getFlash($key) {
	if (isset($_SESSION[$this->flash_key][$key])) {
	    $message = $_SESSION[$this->flash_key][$key];
	    unset($_SESSION[$this->flash_key][$key]);
	    return $message;
	}
	return null;
    }

    public function getFlashes() {
	if (isset($_SESSION[$this->flash_key])) {
	    $flashes = $_SESSION[$this->flash_key];
	    unset($_SESSION[$this->flash_key]);
	    return $flashes;
	}
	return [];
    }


    public function hasFlash($key) {
	return isset($_SESSION[$this->flash_key][$key]) ? true : false;
    }

    public function hasFlashes() {
	return empty($_SESSION[$this->flash_key]) ? false : true;
    }

    //permissions
    public function can($itemName, $params = []) {
	// if the component exists
	if (isset(\V::app()->auth)) {
	    return \V::app()->auth->checkAccess((string)\V::app()->user->id, $itemName, $params);
	}
	return true;
    }


}
