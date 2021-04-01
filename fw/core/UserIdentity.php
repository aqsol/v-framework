<?php

namespace core;

class UserIdentity {

    const ERROR_NONE=0;
    const ERROR_USERNAME_INVALID=1;
    const ERROR_PASSWORD_INVALID=2;

    public $username;
    public $password;

    public $data = [];

    public function __construct($username,$password) {
	$this->username = $username;
	$this->password = $password;
	$this->data['id'] = $username;
	$this->data['name'] = $username;
    }

    // Authenticates a user based on {@link username} and {@link password}.
    // Derived classes should override this method, or an exception will be thrown.
    public function authenticate() {
	throw new \ErrorException(get_class($this) . '::authenticate() must be implemented.');

	/*
	    if ($this->password == 'some password') {
		$this->data['id'] = 'some id';
		$this->data['name'] = 'some name';
		return self::ERROR_NONE;
	    } else {
		return self::ERROR_PASSWORD_INVALID;
	    }
	*/
    }

    //to allow overloading in child
    public function getData() {
	return $this->data;
    }
}
