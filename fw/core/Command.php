<?php
namespace core;

class Command extends \core\VObject {
    public function __construct($config = array()) {
	parent::__construct($config);
    }

    public function run() {
	echo "function run in class core\Command should be implemented\n";
    }
}
