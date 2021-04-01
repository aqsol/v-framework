<?php
namespace data;
use \util\Set;
use \data\Validator;
/*

-- create
***$post = FormModel::create(array('title' => 'some title', 'date' => '2013-01-01'));
or
***$post = FormModel::create();

***$post->title = 'a title';
***$post->author = 'john';

-- allow massive assignment or return all data
***$record->data(array('title' => 'Lorem Ipsum', 'value' => 42));
***$fields = $record->data();
*/

class FormModel extends \core\VObject {
    /**
     * Criteria for data validation.
     *
     * Example usage:
     * {{{
     * public $validates = array(
     *     'title' => 'please enter a title',
     *     'email' => array(
     *         array('notEmpty', 'message' => 'Email is empty.'),
     *         array('email', 'message' => 'Email is not valid.'),
     *     )
     * );
     * }}}
     *
     * @var array
     */
    public $validates = [];

    // Associative array of the entity's fields and values.
    protected $_data = [];
    protected $_errors = [];
    protected $_updated = [];
    protected $_odata = [];

    /**
     * Creates a new record object with default values.
     *
     * Options defined:
     * - 'data' _array_: Data to enter into the record. Defaults to an empty array.
     *   Defaults to `null`.
     *
     * @param array $config
     * @return object Record object.
     */
    public function __construct(array $config = [] ) {
	$defaults = [
	    'data' => [],
	    'validates' => [],
	    'options' => [],
	];
	$config = Set::merge($defaults, $config);
	$this->_data = $config['data'];
	$this->validates = $config['validates'];
	unset($config['data']);
	unset($config['validates']);
	parent::__construct($config['options']);
    }

    public static function create(array $data = [], array $options = []) {
	$class = get_called_class();
	return new $class(compact('data', 'options'));
    }


    public function _init() {
	parent::_init();


	$data = (array)$this->_data;
	$this->data($data);
	$this->_odata = $this->_data;
	$this->_updated = [];

    }

    /**
     * Allows several properties to be assigned at once or return the current data, i.e.:
     * {{{
     * $record->data(array('title' => 'Lorem Ipsum', 'value' => 42, '__notassigned' => '5555'));
     * $data = $record->data();
     * keys beginning with '__' will not be assigned!!!
     * }}}
     *
     * @param array $data An associative array of fields and values to assign to this `Entity`
     *        instance.
     * @return void
     */
    public function data($data = null) {
	switch(true) {
	    //get all data
	    case ($data === null):
		return $this->_data;
		break;
	    //array => set
	    case is_array($data):
		foreach ($data as $name => $value)
		    $this->__set($name, $value);
		return;
		break;
	    //string, no dot
	    case is_string($data) && !strpos($data, '.'):
		//echo "no dot:" . print_r($this->__get($data), true) . '/';
		return $this->__get($data);
		break;
	    //string, with dot
	    case is_string($data) && strpos($data, '.'):
		$data = explode('.', $data);
		$first = array_shift($data);
		$rv = $this->__get($first);
		$cur = & $rv;
		foreach ($data as $seg) {
		    if (!isset($cur[$seg]))
			return null;

		    $cur = $cur[$seg];
		}
		return $cur;
		break;
	}
    }

    public function key() {
	return $this->_id;
    }


    public function exists() {
	return false;
    }

    public function modified() {
	return !empty($this->_updated);
    }

    /**
     * Access the errors of the record.
     *
     * @see lithium\data\Entity::$_errors
     * @param array|string $field If an array, overwrites `$this->_errors` if it is empty,
     *        if not, merges the errors with the current values. If a string, and `$value`
     *        is not `null`, sets the corresponding key in `$this->_errors` to `$value`.
     *        Setting `$field` to `false` will reset the current state.
     * @param string $value Value to set.
     * @return mixed Either the `$this->_errors` array, or single value from it.
     */
    public function errors($field = null, $value = null) {
	if ($field === false) {
	    return ($this->_errors = array());
	}
	if ($field === null) {
	    return $this->_errors;
	}
	if (is_array($field)) {
	    return ($this->_errors = array_merge_recursive($this->_errors, $field));
	}
	if ($value === null && isset($this->_errors[$field])) {
	    return $this->_errors[$field];
	}
	if ($value !== null) {
	    if (array_key_exists($field, $this->_errors)) {
		$current = $this->_errors[$field];
		return ($this->_errors[$field] = array_merge((array) $current, (array) $value));
	    }
	    return ($this->_errors[$field] = $value);
	}
	return $value;
    }

    /**
     * Overloading for reading inaccessible properties.
     *
     * @param string $name Property name.
     * @return mixed Result.
     */
    public function &__get($name) {
	if (isset($this->_data[$name]))
	    return $this->_data[$name];

	$null = null;
	return $null;
    }

    /**
     * Overloading for writing to inaccessible properties.
     *
     * @param string $name Property name.
     * @param string $value Property value.
     * @return mixed Result.
     */
    public function __set($name, $value = null) {
	if (is_array($name) && !$value)
	    return array_map(array(&$this, '__set'), array_keys($name), array_values($name));

	$this->_data[$name] = $value;

	//changes
	$ovalue = $this->_odata[$name] ?? null;
	if ($ovalue != $value)
	    $this->_updated[$name] = $value;
    }



    /**
     * Overloading for calling `isset()` or `empty()` on inaccessible properties.
     *
     * @param string $name Property name.
     * @return mixed Result.
     */
    public function __isset($name) {
	return isset($this->_data[$name]);
    }

    public function __unset($name) {
	unset($this->_data[$name]);
    }


    /**
     * An important part of describing the business logic of a model class is defining the
     * validation rules. In Lithium models, rules are defined through the `$validates` class
     * property, and are used by this method before saving to verify the correctness of the data
     * being sent to the backend data source.
     *
     * Note that these are application-level validation rules, and do not
     * interact with any rules or constraints defined in your data source. If such constraints fail,
     * an exception will be thrown by the database layer. The `validates()` method only checks
     * against the rules defined in application code.
     *
     * This method uses the `Validator` class to perform data validation. An array representation of
     * the entity object to be tested is passed to the `check()` method, along with the model's
     * validation rules. Any rules defined in the `Validator` class can be used to validate fields.
     * See the `Validator` class to add custom rules, or override built-in rules.
     *
     * @see lithium\data\Model::$validates
     * @see lithium\util\Validator::check()
     * @see lithium\data\Entity::errors()
     * @param string $entity Model entity to validate. Typically either a `Record` or `Document`
     *        object. In the following example:
     *        {{{
     *            $post = Posts::create($data);
     *            $success = $post->validates();
     *        }}}
     *        The `$entity` parameter is equal to the `$post` object instance.
     * @param array $options Available options:
     *        - `'rules'` _array_: If specified, this array will _replace_ the default
     *          validation rules defined in `$validates`.
     *        - `'events'` _mixed_: A string or array defining one or more validation
     *          _events_. Events are different contexts in which data events can occur, and
     *          correspond to the optional `'on'` key in validation rules. For example, by
     *          default, `'events'` is set to either `'create'` or `'update'`, depending on
     *          whether `$entity` already exists. Then, individual rules can specify
     *          `'on' => 'create'` or `'on' => 'update'` to only be applied at certain times.
     *          Using this parameter, you can set up custom events in your rules as well, such
     *          as `'on' => 'login'`. Note that when defining validation rules, the `'on'` key
     *          can also be an array of multiple events.
     * @return boolean Returns `true` if all validation rules on all fields succeed, otherwise
     *         `false`. After validation, the messages for any validation failures are assigned to
     *         the entity, and accessible through the `errors()` method of the entity object.
     * @filter
     */
    public function validates(array $options = []) {
	$defaults = array(
	    'rules' => $this->validates,
	    'events' => array(),
	);
	$options += $defaults;
	$this->errors(false);

	$rules = $options['rules'];
	unset($options['rules']);

	if ($errors = Validator::check($this->data(), $rules, $options))
	    $this->errors($errors);

	return empty($errors);
    }


}

