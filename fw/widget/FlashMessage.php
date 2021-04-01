<?php
namespace widget;

class FlashMessage extends \core\VObject {

    public static function create($data) {
	if (is_string($data))
	    $data = [ 'text' => $data ];
	$fm = new self($data);
	if (empty($data['title']))
	    $fm->success();
	return $fm;
    }
    
    public function error() {
	$this->_config['title'] = '<span class="badge badge-danger">' . \V::t('Error') . '</span>';
	return $this;
    }

    public function success() {
	$this->_config['title'] = '<span class="badge badge-success">' . \V::t('Success') . '</span>';
	return $this;
    }
    
    public function info() {
	$this->_config['title'] = '<span class="badge badge-info">' . \V::t('Info') . '</span>';
	return $this;
    }

    public function warning() {
	$this->_config['title'] = '<span class="badge badge-warning">' . \V::t('Warning') . '</span>';
	return $this;
    }
    
    public function add() {
	\models\Msg::add($this->_config);
    }
}
