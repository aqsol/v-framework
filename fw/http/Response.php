<?php

namespace http;

// Response
// This is a simple abstraction over top an HTTP response. This provides methods to set the HTTP status, the HTTP headers, and the HTTP body.

class Response extends \core\VObject {


    /**
     * @var array HTTP response codes and messages
     */
    protected static $messages = array(
        //Informational 1xx
        100 => '100 Continue',
        101 => '101 Switching Protocols',
        //Successful 2xx
        200 => '200 OK',
        201 => '201 Created',
        202 => '202 Accepted',
        203 => '203 Non-Authoritative Information',
        204 => '204 No Content',
        205 => '205 Reset Content',
        206 => '206 Partial Content',
        //Redirection 3xx
        300 => '300 Multiple Choices',
        301 => '301 Moved Permanently',
        302 => '302 Found',
        303 => '303 See Other',
        304 => '304 Not Modified',
        305 => '305 Use Proxy',
        306 => '306 (Unused)',
        307 => '307 Temporary Redirect',
        //Client Error 4xx
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        402 => '402 Payment Required',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        405 => '405 Method Not Allowed',
        406 => '406 Not Acceptable',
        407 => '407 Proxy Authentication Required',
        408 => '408 Request Timeout',
        409 => '409 Conflict',
        410 => '410 Gone',
        411 => '411 Length Required',
        412 => '412 Precondition Failed',
        413 => '413 Request Entity Too Large',
        414 => '414 Request-URI Too Long',
        415 => '415 Unsupported Media Type',
        416 => '416 Requested Range Not Satisfiable',
        417 => '417 Expectation Failed',
        422 => '422 Unprocessable Entity',
        423 => '423 Locked',
        //Server Error 5xx
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        502 => '502 Bad Gateway',
        503 => '503 Service Unavailable',
        504 => '504 Gateway Timeout',
        505 => '505 HTTP Version Not Supported'
    );

    protected function _init() {
	$this->_config['body'] = '';
	$this->_config['status'] = 200;
	$this->_config['headers'] = [ 'Content-Type' => 'text/html' ];
	parent::_init();
    }

    public function redirect($url, $terminate = true, $status = 302) {
	if (\V::app()->request->isAjax) {
	    //redirect via ajax: change status and set X-Redirect
	    $this->_config['headers']['X-Redirect'] = $url;
	    $this->_config['status'] = 200;
	} else {
	    //normal redirect, via location and status code=200
	    $this->_config['headers']['Location'] = $url;
	    $this->_config['status'] = $status;
	}

	$this->_config['body'] = '';

	if($terminate)
	    \V::app()->end();
    }

    public function write($body = '', $replace = false) {
	if ($replace)
	    $this->_config['body'] = '';
	
	$this->_config['body'] .= $body;
    }


    public function addCookie($cookie) {
	setcookie($cookie->name, $cookie->value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
    }

    public function removeCookie($cookie) {
	setcookie($cookie->name, '', $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
    }

    public function output() {
	$status = $this->_config['status'];
	if (in_array($status, [ 204, 304 ])) {
            unset($this->_config['headers']['Content-Type']);
            $this->_config['body'] = '';
	}

        //Send headers
        if (!headers_sent()) {
            //Send status
            if (strpos(PHP_SAPI, 'cgi') === 0) {
                header(sprintf('Status: %s', $this->getMessageForCode($status)));
            } else {
                header(sprintf('HTTP/1.1 %s', $this->getMessageForCode($status)));
            }

            //Send headers
            foreach ($this->_config['headers'] as $name => $value) {
                $hValues = explode("\n", $value);
                foreach ($hValues as $hVal)
                    header("$name: $hVal", false);

            }
        }

        //Send body
    	echo $this->_config['body'];
    }

    public function getMessageForCode($status) {
        if (isset(self::$messages[$status])) {
            return self::$messages[$status];
        } else {
            return null;
        }
    }


}
