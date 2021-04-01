<?php

namespace auth;

use auth\Item;

/**
 * PhpManager represents an authorization manager that stores authorization
 * information in terms of a PHP script file.
 *
 * The authorization data will be saved to and loaded from a file
 * specified by [[authFile]], which defaults to 'protected/data/rbac.php'.
 *
 * PhpManager is mainly suitable for authorization data that is not too big
 * (for example, the authorization data for a personal blog system).
 *
 * @property array $authItems The authorization items of the specific type.
 *
 */
class PhpManager extends \auth\Manager {
    public function __construct($config = array()) {
	$defaults = array(
	    /**
	     * @var string the path of the PHP script that contains the authorization data.
	     * If not set, it will be using 'protected/data/rbac.php' as the data file.
	     * Make sure this file is writable by the Web server process if the authorization
	     * needs to be changed.
	     * @see loadFromFile
	     * @see saveToFile
	     */
	    'authFile' => '',
	    'items' => [], // itemName => item
	    'children' => [], // itemName, childName => child
	    'assignments' => [], // userId, itemName => assignment
	);
	$config += $defaults;
	parent::__construct($config);
    }

    /**
     * Initializes the application component.
     * This method overrides parent implementation by loading the authorization data
     * from PHP script.
     */
    public function _init() {
	//echo "initializing rbac";
	if ($this->authFile !== false) {
	    $this->load();
	}
	parent::_init();
    }


	/**
	 * Creates an authorization item.
	 * An authorization item represents an action permission (e.g. creating a post).
	 * It has two types: operation and role.
	 * Authorization items form a hierarchy. Higher level items inheirt permissions representing
	 * by lower level items.
	 * @param string $name the item name. This must be a unique identifier.
	 * @param string $type the item type ('op': operation, 'role': role).
	 * @param string $description description of the item
	 * @param string $bizRule business rule associated with the item. This is a piece of
	 * PHP code that will be executed when [[checkAccess()]] is called for the item.
	 * @param mixed $data additional data associated with the item.
	 * @return Item the authorization item
	 * @throws Exception if an item with the same name already exists
	 */
	public function createItem($name, $type, $description = '', $bizRule = null, $data = null) {
		if (isset($this->items[$name])) {
			throw new \Exception('An item with this name already exists.');
		}
		return $this->_config['items'][$name] = new Item(array(
			//'manager' => $this,
			'name' => $name,
			'type' => $type,
			'description' => $description,
			'bizRule' => $bizRule,
			'data' => $data,
		));
	}


	/**
	 * Performs access check for the specified user.
	 * @param mixed $userId the user ID. This can be either an integer or a string representing
	 * @param string $itemName the name of the operation that need access check
	 * the unique identifier of a user. See [[User::id]].
	 * @param array $params name-value pairs that would be passed to biz rules associated
	 * with the tasks and roles assigned to the user. A param with name 'userId' is added to
	 * this array, which holds the value of `$userId`.
	 * @return boolean whether the operations can be performed by the user.
	 */
	public function checkAccess($userId, $itemName, $params = array()) {
	
//		//SC: 19.07.2018: prevent userId being an object; if so, cast to string
//		if (is_object($userId))
//		    $userId = (string)$userId;
		if (!isset($this->items[$itemName])) {
			return false;
		}
		/** @var $item Item */
		$item = $this->items[$itemName];
		//Yii::trace('Checking permission: ' . $item->getName(), __METHOD__);
		if (!isset($params['userId'])) {
			$params['userId'] = $userId;
		}
		if ($this->executeBizRule($item->bizRule, $params, $item->data)) {
			if (in_array($itemName, $this->defaultRoles)) {
				return true;
			}
			if (isset($this->assignments[$userId][$itemName])) {
				/** @var $assignment Assignment */
				$assignment = $this->assignments[$userId][$itemName];
				if ($this->executeBizRule($assignment->bizRule, $params, $assignment->data)) {
					return true;
				}
			}
			foreach ($this->children as $parentName => $children) {
				if (isset($children[$itemName]) && $this->checkAccess($userId, $parentName, $params)) {
					return true;
				}
			}
		}
		return false;
	}



    /**
     * Adds an item as a child of another item.
     * @param string $itemName the parent item name
     * @param string $childName the child item name
     * @return boolean whether the item is added successfully
     * @throws Exception if either parent or child doesn't exist.
     * @throws InvalidCallException if item already has a child with $itemName or if a loop has been detected.
     */
    public function addItemChild($itemName, $childName) {
	if (!isset($this->items[$childName]))
	    throw new \Exception("'$childName' does not exist.");

	if (!isset($this->items[$itemName]))
	    throw new \Exception("'$itemName' does not exist.");

	/** @var $child Item */
	$child = $this->items[$childName];
	/** @var $item Item */
	$item = $this->items[$itemName];
	$this->checkItemChildType($item->type, $child->type);
	if ($this->detectLoop($itemName, $childName))
	    throw new \Exception("Cannot add '$childName' as child of '$itemName': loop detected.");

	if (isset($this->children[$itemName][$childName]))
	    throw new \Exception("The item '$itemName' already has a child '$childName'.");

	$this->_config['children'][$itemName][$childName] = $this->items[$childName];
	return true;
    }

    /**
     * Removes a child from its parent.
     * Note, the child item is not deleted. Only the parent-child relationship is removed.
     * @param string $itemName the parent item name
     * @param string $childName the child item name
     * @return boolean whether the removal is successful
     */
    public function removeItemChild($itemName, $childName) {
	if (isset($this->children[$itemName][$childName])) {
	    unset($this->children[$itemName][$childName]);
	    return true;
	}
	return false;
    }

    /**
     * Returns a value indicating whether a child exists within a parent.
     * @param string $itemName the parent item name
     * @param string $childName the child item name
     * @return boolean whether the child exists
     */
    public function hasItemChild($itemName, $childName) {
	return isset($this->children[$itemName][$childName]);
    }

    /**
     * Returns the children of the specified item.
     * @param mixed $names the parent item name. This can be either a string or an array.
     * The latter represents a list of item names.
     * @return Item[] all child items of the parent
     */
    public function getItemChildren($names) {
	if (is_string($names))
	    return isset($this->children[$names]) ? $this->children[$names] : [];

	$children = [];
	foreach ($names as $name) {
	    if (isset($this->children[$name]))
		$children = array_merge($children, $this->children[$name]);
	}
	return $children;
    }

    /**
     * Assigns an authorization item to a user.
     * @param mixed $userId the user ID (see [[User::id]])
     * @param string $itemName the item name
     * @param string $bizRule the business rule to be executed when [[checkAccess()]] is called
     * for this particular authorization item.
     * @param mixed $data additional data associated with this assignment
     * @return Assignment the authorization assignment information.
     * @throws InvalidParamException if the item does not exist or if the item has already been assigned to the user
     */
    public function assign($userId, $itemName, $bizRule = null, $data = null) {
	if (!isset($this->items[$itemName]))
	    throw new \Exception("Unknown authorization item '$itemName'.");

	if (isset($this->assignments[$userId][$itemName]))
	    throw new \Exception("Authorization item '$itemName' has already been assigned to user '$userId'.");

	return $this->_config['assignments'][$userId][$itemName] = new Assignment([
	    //'manager' => $this,
	    'userId' => $userId,
	    'itemName' => $itemName,
	    'bizRule' => $bizRule,
	    'data' => $data,
	]);
    }

    /**
     * Revokes an authorization assignment from a user.
     * @param mixed $userId the user ID (see [[User::id]])
     * @param string $itemName the item name
     * @return boolean whether removal is successful
     */
    public function revoke($userId, $itemName) {
	if (isset($this->assignments[$userId][$itemName])) {
	    unset($this->assignments[$userId][$itemName]);
	    return true;
	}
	return false;
    }

    /**
     * Returns a value indicating whether the item has been assigned to the user.
     * @param mixed $userId the user ID (see [[User::id]])
     * @param string $itemName the item name
     * @return boolean whether the item has been assigned to the user.
     */
    public function isAssigned($userId, $itemName) {
	return isset($this->assignments[$userId][$itemName]);
    }

    /**
     * Returns the item assignment information.
     * @param mixed $userId the user ID (see [[User::id]])
     * @param string $itemName the item name
     * @return Assignment the item assignment information. Null is returned if
     * the item is not assigned to the user.
     */
    public function getAssignment($userId, $itemName) {
	return isset($this->assignments[$userId][$itemName]) ? $this->assignments[$userId][$itemName] : null;
    }

    /**
     * Returns the item assignments for the specified user.
     * @param mixed $userId the user ID (see [[User::id]])
     * @return Assignment[] the item assignment information for the user. An empty array will be
     * returned if there is no item assigned to the user.
     */
    public function getAssignments($userId) {
	return isset($this->assignments[$userId]) ? $this->assignments[$userId] : array();
    }

    /**
     * Returns the authorization items of the specific type and user.
     * @param mixed $userId the user ID. Defaults to null, meaning returning all items even if
     * they are not assigned to a user.
     * @param integer $type the item type (0: operation, 1: task, 2: role). Defaults to null,
     * meaning returning all items regardless of their type.
     * @return Item[] the authorization items of the specific type.
     */
    public function getItems($userId = null, $type = null) {
	if ($userId === null && $type === null)
	    return $this->items;

	$items = [];
	if ($userId === null) {
	    foreach ($this->items as $name => $item) {
		/** @var $item Item */
		if ($item->type == $type)
		    $items[$name] = $item;
	    }
	} elseif (isset($this->assignments[$userId])) {
	    foreach ($this->assignments[$userId] as $assignment) {
		/** @var $assignment Assignment */
		$name = $assignment->itemName;
		if (isset($this->items[$name]) && ($type === null || $this->items[$name]->type == $type))
		    $items[$name] = $this->items[$name];
	    }
	}
	return $items;
    }


    /**
     * Removes the specified authorization item.
     * @param string $name the name of the item to be removed
     * @return boolean whether the item exists in the storage and has been removed
     */
    public function removeItem($name) {
	if (isset($this->items[$name])) {
	    foreach ($this->children as &$children) {
		unset($children[$name]);
	    }
	    foreach ($this->assignments as &$assignments) {
		unset($assignments[$name]);
	    }
	    unset($this->items[$name]);
	    return true;
	}
	return false;
    }

    /**
     * Returns the authorization item with the specified name.
     * @param string $name the name of the item
     * @return Item the authorization item. Null if the item cannot be found.
     */
    public function getItem($name) {
	return isset($this->items[$name]) ? $this->items[$name] : null;
    }


    /**
     * Saves authorization data into persistent storage.
     * If any change is made to the authorization data, please make
     * sure you call this method to save the changed data into persistent storage.
     */
    public function save() {
	$items = [];
	foreach ($this->items as $name => $item) {
	    /** @var $item Item */
	    $items[$name] = [
		'type' => $item->type,
		'description' => $item->description,
		'bizRule' => $item->bizRule,
		'data' => $item->data,
	    ];
	    if (isset($this->children[$name])) {
		foreach ($this->children[$name] as $child) {
		    /** @var $child Item */
		    $items[$name]['children'][] = $child->name;
		}
	    }
	}

	foreach ($this->assignments as $userId => $assignments) {
	    foreach ($assignments as $name => $assignment) {
		/** @var $assignment Assignment */
		if (isset($items[$name])) {
		    $items[$name]['assignments'][$userId] = [
			'bizRule' => $assignment->bizRule,
			'data' => $assignment->data,
		    ];
		}
	    }
	}

	$this->saveToFile($items, $this->authFile);
    }

    /**
     * Loads authorization data.
     */
    public function load() {
	$this->assignments = array();
	$this->children = array();
	$this->items = array();

	$items = $this->loadFromFile($this->authFile);

	foreach ($items as $name => $item) {
	    $this->items[$name] = new Item(array(
				//'manager' => $this,
				'name' => $name,
				'type' => $item['type'],
				'description' => $item['description'],
				'bizRule' => $item['bizRule'],
				'data' => $item['data'],
	    ));
	}

	foreach ($items as $name => $item) {
			if (isset($item['children'])) {
				foreach ($item['children'] as $childName) {
					if (isset($this->items[$childName])) {
						$this->children[$name][$childName] = $this->items[$childName];
					}
				}
			}
			if (isset($item['assignments'])) {
				foreach ($item['assignments'] as $userId => $assignment) {
					$this->assignments[$userId][$name] = new Assignment(array(
						//'manager' => $this,
						'userId' => $userId,
						'itemName' => $name,
						'bizRule' => $assignment['bizRule'],
						'data' => $assignment['data'],
					));
				}
			}
	}
    }

    /**
     * Checks whether there is a loop in the authorization item hierarchy.
     * @param string $itemName parent item name
     * @param string $childName the name of the child item that is to be added to the hierarchy
     * @return boolean whether a loop exists
     */
    protected function detectLoop($itemName, $childName) {
	if ($childName === $itemName)
	    return true;

	if (!isset($this->children[$childName], $this->items[$itemName]))
	    return false;

	foreach ($this->children[$childName] as $child) {
	    /** @var $child Item */
	    if ($this->detectLoop($itemName, $child->name))
		return true;
	}
	return false;
    }

    /**
     * Loads the authorization data from a PHP script file.
     * @param string $file the file path.
     * @return array the authorization data
     * @see saveToFile
     */
    protected function loadFromFile($file) {
	if (is_file($file))
	    return require($file);

	return [];
    }

    /**
     * Saves the authorization data to a PHP script file.
     * @param array $data the authorization data
     * @param string $file the file path.
     * @see loadFromFile
     */
    protected function saveToFile($data, $file) {
	file_put_contents($file, "<?php\nreturn " . var_export($data, true) . ";\n", LOCK_EX);
    }
}
