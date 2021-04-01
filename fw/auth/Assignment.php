<?php

namespace auth;

/**
 * Assignment represents an assignment of a role to a user.
 * It includes additional assignment information such as [[bizRule]] and [[data]].
 * Do not create a Assignment instance using the 'new' operator.
 * Instead, call [[Manager::assign()]].
 *
 */
class Assignment extends \core\VObject {
    public function __construct($config = array()) {
	$defaults = array(
	    //'manager' => null,
	    'bizRule' => null,
	    'data' => null,
	    'userId' => null,
	    'itemName' => null,
	);
	$config += $defaults;
	parent::__construct($config);
    }

}
