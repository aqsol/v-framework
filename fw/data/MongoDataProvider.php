<?php
namespace data;

class MongoDataProvider extends \core\VObject implements \Iterator {

	private $__iterator = null;
	private $__count = null;
	
	//receives:
	// 'class' => object class to be created
	// 'collection' => colletion to run query on

	// 'filter' => query filter
	// 'options' => sort, skip, limit, hint

	public function __construct(array $config = []) {
	    if (!isset($config['options']))
		$config['options'] = [];
	    if (!isset($config['options']['sort']))
		$config['options']['sort'] = [];
	    if (!isset($config['options']['skip']))
		$config['options']['skip'] = 0;
	    if (!isset($config['options']['limit']))
		$config['options']['limit'] = 0;

	    //echo '<pre>' . print_r($config, true) . '</pre>';

	    parent::__construct($config);
	}


	private function iterator() {
	    if ($this->__iterator !== null)
		return $this->__iterator;

	    $__cursor = $this->collection->find($this->filter, $this->options);
	    $this->__iterator = new \IteratorIterator($__cursor);
	    $this->__iterator->rewind();
	    return $this->__iterator;
	}

	public function skip($val = null) {
	    if ($val !== null && !$this->iterator) {
		$this->options['skip'] = $val;
	    }
	    return $this;
	}

	public function limit($val = null) {
	    if ($val !== null && !$this->iterator) {
		$this->options['limit'] = $val;
	    }
	    return $this;
	}

	public function sort($val = null) {
	    if ($val !== null && !$this->iterator) {
		$this->options['sort'] = $val;
	    }
	    return $this;
	}


	public function count() {
	    if ($this->__count !== null)
		return $this->__count;

	    return $this->__count = $this->collection->count($this->filter, $this->options);
	}


	public function next() {
	    return $this->iterator()->next();
	}

	public function key() {
	    return $this->iterator()->key();
	}

	public function valid() {
	    return $this->iterator()->valid();
	}

	public function rewind() {
	    return $this->iterator()->rewind();
	}


        public function current() {
            $class = $this->class;
            $result = $this->iterator()->current();

            if ($class) {
		$modelOptions = ( !empty($this->options['modelOptions']) ? $this->options['modelOptions'] : [] );
                $object = new $class([
            	    'data' => $result,
            	    'options' => [ 'exists' => true ] + $modelOptions,
            	]);
                $object->afterFind();
                return $object;
            }
            return $object = (object)$result;
        }

	//SC: 2019-10-20
	public function toArray() {
	    $data = [];
	    foreach($this as $obj)
		$data[] = $obj;
	    return $data;
	}
}