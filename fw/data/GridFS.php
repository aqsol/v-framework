<?php
namespace data;

use \util\Set;

class GridFS extends \core\VObject {

	public $_bucket;

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
	    $defaults = array(
		'bucketName' => 'fs',
		'database' => null,
		'chunkSizeBytes' => 128 * 1024, //128K
	    );
	    $config = Set::merge($defaults, $config);

	    parent::__construct($config);
	}


	public function _init() {
	    //initialize the bucket
	    if (empty($this->database))
		$this->database = \V::app()->mongo->defaultDatabase;

	    $this->_bucket = \V::app()->mongo->selectDatabase($this->database)->selectGridFSBucket([
		'bucketName' => $this->bucketName,
		'chunkSizeBytes' => $this->chunkSizeBytes,
	    ]);
	    
	}

	public function __call($method, $params) {
	    //unoptimized
	    return call_user_func_array(array($this->_bucket, $method), $params);
	}

}
