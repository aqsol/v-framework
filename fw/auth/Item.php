<?php

namespace auth;

/**
 * Item represents an authorization item.
 * An authorization item can be an operation or a role.
 * They form an authorization hierarchy. Items on higher levels of the hierarchy
 * inherit the permissions represented by items on lower levels.
 * A user may be assigned one or several authorization items (called [[Assignment]] assignments).
 * He can perform an operation only when it is among his assigned items.
 */
class Item extends \core\VObject {
    public function __construct($config = array()) {
	$defaults = array(
	    'description' => '',
	    'bizRule' => null,
	    'data' => null,
	    'type' => null,
	    'name' => null,
	);
	$config += $defaults;
	parent::__construct($config);
    }

}
