<?php

namespace core;

class SessionMongo extends \core\VObject implements \SessionHandlerInterface {
    protected $_collection;
    protected $_soptions;
    /**
     * Constructor
     *
     * @param  array $settings
     */
    public function __construct($config = []) {
	$defaults  = [
            'gc_maxlifetime' => 60*60*24, //1 days
            'auto_start' => true,
	];
	$config += $defaults;
	parent::__construct($config);

	//set mongodatabasecomponent
	$this->_collection = \V::app()->mongo->selectCollection(\V::app()->mongo->defaultDatabase, 'sessions');

        session_set_save_handler($this, true);
    }

    protected function _init() {

	//hook-up handlers
	if ($this->_config['auto_start']) {
	    unset($this->_config['auto_start']);
	    unset($this->_config['init']);
    	    \V::app()->hooks->addHook('before', array($this, 'open_session'));
    	}
        \V::app()->hooks->addHook('after', array($this, 'close_session'));
    }


    public function open_session() {
        session_start($this->_config);
    }

    public function close_session() {
        session_write_close();
    }

    /********************************************************************************
    * Session Handler
    *******************************************************************************/

    public function open($savePath, $sessionName) {
        return true;
    }

    public function close() {
	return true;
    }

    public function read($id) {
        $row = $this->_collection->findOne([ '_id' => $id ], [ 'projection' => [ 'data' => 1 ] ] );
        if ($row == null)
    	    return '';
        return $row['data'];
    }

    public function write($id, $data) {
	$this->_collection->updateOne(
	    [ '_id' => $id ],
	    [
		'$set' => [
		    'data' => $data,
		    'expires' => time() + $this->_config['gc_maxlifetime'],
		    '_id' => $id,
		]
	    ],
	    [ 'upsert' => true, ]
	);
	return true;
    }

    public function destroy($id) {
    	$this->_collection->deleteOne([ '_id' => $id ]);
    	return true;
    }

    public function gc($maxlifetime) {
    	return $this->_collection->deleteMany([ 'expires' => [ '$lt' => time() ] ] );
    }

}
