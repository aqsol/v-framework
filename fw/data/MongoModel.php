<?php
namespace data;
use \util\Set;
use \data\Validator;

/*

-- find type of operations
***$posts = Model::find(array('published'=>true), array('limit' => 10, 'skip' => 10, 'sort' => ...));

***$post = Model::findOne(array('conditions' => array('published'=>true)), array('name' => true));
***$post = Model::findOne(array('published'=>true), array('name' => true));

$posts = Model::findDistinct(array(...));
$posts = Model::findGroupBy(array('conditions' => ..., 'groupby' => ...));
***$post = Model::findById('123456', array('name' => true));
***$post = Model::findById(new MongoId('123456'), array('name' => true));

-- create
***$post = Model::create(array('title' => 'some title', 'date' => '2013-01-01'), array('exists' => true));
or
***$post = Model::create();

***$post->title = 'a title';
***$post->author = 'john';

-- create/update
***$post->save();
***Model::update(array('conditions' => array(), 'update' => array()), array('options' => ...));

-- delete
***$post->delete(array('fsync'=>true))
***Model::delete(array('conditions' => array()));
***Model::delete(array('_id' => '12345'));
***Model::deleteById(new MongoId('123456'));
***Model::deleteById('123456');

-- statistics
***$count = Model::count(array('conditions' => array('published'=>true)));
***$count = Model::count(array('published' => true));

-- others
***$mixed = Mapper::findAndModify(array('conditions' => ..., 'update' => ..., 'fields' => ...), array('new' =>, 'upsert' =>));
$coll = Mapper::collection($collectionName);
Model::createIndex(array('sid' => 1, 'ttt' => -1), array('unuique' => true));

-- allow massive assignment or return all data
***$record->data(array('title' => 'Lorem Ipsum', 'value' => 42));
***$fields = $record->data();
*/

class MongoModel extends \data\FormModel {

    //a static array of collection objects, indexed by namespace
    protected static $_collections = [];

    protected static $database = null;
    protected static $collection = null;

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
    public function __construct(array $config = []) {
	$defaults = [
	    'options' => [
		'exists' => false,
	    ],
	];

	$config = Set::merge($defaults, $config);
	parent::__construct($config);
    }

    public static function _collection() {
	if (empty(static::$database))
	    static::$database = \V::app()->mongo->defaultDatabase;

	$ns = static::$database . '.' . static::$collection;

	//\V::app()->log->debug('MongoModel::_collection: ns=' . $ns);
	if (isset(self::$_collections[$ns]))
	    return self::$_collections[$ns];

	//make the connection to the database, using 'mongo' component from app
	//return self::$_collections[$ns] = \V::app()->mongo->selectCollection(static::database(), static::collection());
	return self::$_collections[$ns] = \V::app()->mongo->selectCollection(static::$database, static::$collection);
    }

    public static function to_oid($value) {
	try {
	    return new \MongoDB\BSON\ObjectId($value);
	} catch( \MongoDB\Driver\Exception\InvalidArgumentException $e) {
	    return null;
	}
    }


    public static function __callstatic($method, $params) {
	//unoptimized
	return call_user_func_array([ self::_collection(), $method ], $params);
    }

    public function beforeSave() {
	if ($this->exists()) {
	    $this->u_ts = new \MongoDB\BSON\UTCDateTime();
	} else {
	    $this->c_ts = new \MongoDB\BSON\UTCDateTime();
	}
    }

    public function afterSave() {
    }

    private function __insertOne($options = []) {
	    try {
		//insert
		unset($this->_data['_id']);
		$result = self::_collection()->insertOne($this->_data, $options);
		if ($result->getInsertedCount() == 1) {
		    $this->_data['_id'] = $result->getInsertedId();
		    $this->_config['exists'] = true;
		    $this->afterSave();
		    return true;
		} else {
		    return false;
		}
	    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
		//return $e;
		return false;
	    } catch (MongoDB\Exception\InvalidArgumentException $e) {
		//return $e;
		return false;
	    } catch (MongoDB\Driver\Exception\RuntimeException $e) {
		return false;
	    }
    }

    private function __updateOne($options = []) {
	    // get a list of modified fields and save only those
	    //$modified = $this->modified();
	    //\V::app()->log->debug('model_debug', print_r($modified, true));

	    //\V::app()->log->debug('Class: ' . get_called_class() . ': updated: ' . print_r($this->_updated, true));

	    try {
		//update
		$__id = $this->_data['_id'];
		unset($this->_data['_id']);
		$result = self::_collection()->updateOne(['_id' => $__id ], [ '$set' => $this->_data ], $options);
		//bring back
		$this->_data['_id'] = $__id;

		if ($result->getModifiedCount() == 1) {
		    $this->afterSave();
		    return true;
		} else {
		    return false;
		}
	    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
		//return $e;
		return false;
	    } catch (MongoDB\Exception\InvalidArgumentException $e) {
		//return $e;
		return false;
	    } catch (MongoDB\Driver\Exception\RuntimeException $e) {
		return false;
	    }
    }


    public function save($options = [] ) {
	$defaults = array('fsync' => false);
	$options += $defaults;

	$this->beforeSave();

	if ($this->_config['exists'] == true) {
	    return $this->__updateOne($options);
	} else {
	    return $this->__insertOne($options);
	}
    }

    /**
     * A flag indicating whether or not this record exists.
     *
     * @return boolean `True` if the record was `read` from the data-source, or has been `create`d
     *         and `save`d. Otherwise `false`.
     */
    public function exists() {
	    if (isset($this->_config['exists']))
		return $this->_config['exists'];
	    else
		return false;
    }

    public function key() {
	    if (isset($this->_config['exists']))
		return $this->_id;
	    else
		return false;
    }


    public function afterFind() {
    }


    public static function dbg_backtrace($args_data) {
	    \V::app()->_dbg('Class: ' . get_called_class() . ': ' . print_r($args_data, true));
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
	    \V::app()->_dbg("\n" . implode("\n", $lines));
	    \V::app()->_dbg("--------------------------------------------");
    }


    public static function findOne(array $filter = [], array $options = []) {
    	    //\V::app()->_dbg("MongoModel::findOne" . print_r($filter, true));
	    //trace
	    //self::dbg_backtrace(print_r($filter, true));


            if (array_key_exists('_id', $filter) && (!($filter['_id'] instanceof \MongoDB\BSON\ObjectId)) )
                $filter['_id'] = new \MongoDB\BSON\ObjectId($filter['_id']);

            $result = null;
            try {
                $result = self::_collection()->findOne($filter, $options);
            } catch (MongoDB\Exception\UnsupportedException $e) {
                $result = false;
                return $result;
            } catch (MongoDB\Exception\InvalidArgumentException $e) {
                $result = false;
                return $result;
            } catch (MongoDB\Driver\Exception\RuntimeException $e) {
                $result = false;
                return $result;
            }

	    //if the result is falsy, return it as-is
            if (!$result)
                return $result;

            if (empty($options['projection'])) {
                $class = get_called_class();

		$modelOptions = ( !empty($options['modelOptions']) ? $options['modelOptions'] : []);

                $object = new $class([
                    'data' => $result,
                    'options' => [ 'exists' => true, ] + $modelOptions,
                ]);
                $object->afterFind();
                return $object;
            }
            //returns raw object
            return $object = (object) $result;
    }


    public static function findById($id = '', array $options = []) {
	    if (!($id instanceof \MongoDB\BSON\ObjectId))
		$id = new \MongoDB\BSON\ObjectId($id);

	    return self::findOne([ '_id' => $id ], $options);
    }




    // ----------------------------------------------------------------------
    // finders & counters methods
    public static function find(array $filter = [], array $options = []) {
    	//\V::app()->_dbg("MongoModel::find: " . print_r($filter, true));
	//trace
	//self::dbg_backtrace(print_r($filter, true));

	//special case for id
        if (array_key_exists('_id', $filter) && is_string($filter['_id']) )
            $filter['_id'] = new \MongoDB\BSON\ObjectId($filter['_id']);

	//if projection is not empty, return a standard object
        $class = ( empty($options['projection']) ? get_called_class() : false );

	return new MongoDataProvider([
	    'class' => $class,
	    'filter' => $filter,
	    'collection' => self::_collection(),
	    'options' => $options,
	]);
    }



    //--------------------------------------------------------------------------------
    // delete
    public function delete(array $options = []) {
	if (($this->_config['exists'] === false) || !isset($this->_data['_id'])) {
	    $this->_data = [];
	    $this->_config['exists'] = false;
	    return true;
	}
	//convert id to a MongoId Object
	$id = $this->_data['_id'];
	if (!($id instanceof \MongoDB\BSON\ObjectId))
	    $id = new MongoDB\BSON\ObjectId($id);

	$result = self::_collection()->deleteOne( [ '_id' => $id ], $options);
	if ($result->getDeletedCount() == 1) {
	    $this->_config['exists'] = false;
	    $this->_data = [];
	    return true;
	}

	return false;
    }


    public static function deleteById($id = '', array $options = array()) {
	if (!($id instanceof \MongoDB\BSON\ObjectId))
	    $id = new MongoDB\BSON\ObjectId($id);
	return self::_collection()->deleteOne( [ '_id' => $id ], $options );
    }

    //---------------------------------------------------------------------------------
    // functions offered by driver, here just by convenience and some conversions

    public static function count(array $filter = [], array $options = []) {
	//special case for id
        if (array_key_exists('_id', $filter) && (!($filter['_id'] instanceof \MongoDB\BSON\ObjectId)) )
            $filter['_id'] = new \MongoDB\BSON\ObjectId($filter['_id']);

	return self::_collection()->count($filter, $options);
    }

    public static function updateMany($filter, $update, array $options = []) {
	//special case for id
        if (array_key_exists('_id', $filter) && (!($filter['_id'] instanceof \MongoDB\BSON\ObjectId)) )
            $filter['_id'] = new \MongoDB\BSON\ObjectId($filter['_id']);

	return self::_collection()->updateMany($filter, $update, $options);
    }

    public static function deleteMany($filter, array $options = []) {
	//special case for id
        if (array_key_exists('_id', $filter) && (!($filter['_id'] instanceof \MongoDB\BSON\ObjectId)) )
            $filter['_id'] = new \MongoDB\BSON\ObjectId($filter['_id']);

	return self::_collection()->deleteMany($filter, $options);
    }

    public static function aggregate(array $pipeline, array $options = []) {
	return self::_collection()->aggregate($pipeline, $options);
    }

    public static function distinct($fieldName, $filter = [], array $options = []) {
	//special case for id
	if (array_key_exists('_id', $filter) && (!($filter['_id'] instanceof \MongoDB\BSON\ObjectId)) )
	    $filter['_id'] = new \MongoDB\BSON\ObjectId($filter['_id']);

	return self::_collection()->distinct($fieldName, $filter,  $options);
    }

    public function validates(array $options = array()) {
	$defaults = [
	    'rules' => $this->validates,
	    'events' => $this->_config['exists'] ? 'update' : 'create',
	];
	$options += $defaults;
	return parent::validates($options);
    }


}
