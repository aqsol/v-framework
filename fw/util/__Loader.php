<?php

namespace util;

class Loader {
    public $ns_map = [];
    public $paths = [];
    public $debug = false;

    public function __construct($config = []) {
	$this->ns_map = $config['ns_map'];
	$this->paths = $config['paths'];
	$this->debug = $config['debug'];
    }

    public function al_dbg($message) {
	$fp = fopen(APP . '/../applogs/autoloader.log', 'a+');
    	$res = fwrite($fp, date('c') . ':' . (string)$message . PHP_EOL);
    	fclose($fp);
    }

    public function addNamespace($prefix, $base_dir, $prepend = false) {
	// normalize namespace prefix
        $prefix = trim($prefix, '\\') . '\\';
                        
        // normalize the base directory with a trailing separator
        $base_dir = rtrim($base_dir, DIRECTORY_SEPARATOR) . '/';
                                        
        // initialize the namespace prefix array
        if (empty($this->ns_map[$prefix]))
    	    $this->ns_map[$prefix] = [];

	// retain the base directory for the namespace prefix
	//echo "autoload addnamespace: add prefix=`$prefix`, basedir=`$base_dir`<br>\n";
        if ($prepend) {
            array_unshift($this->ns_map[$prefix], $base_dir);
        } else {
            array_push($this->ns_map[$prefix], $base_dir);
        }
    }

    public function handler($class) {
	$this->al_dbg('');
	$this->al_dbg("loader_map: requested class=$class");
	$_class=strtr(ltrim($class, "\\"), "\\", '/');

    	// try to load by prefix mapping
        $prefix = $class;
                
        // work backwards through the namespace names of the fully-qualified class name to find a mapped file name
        while (false !== $pos = strrpos($prefix, '\\')) {
    	    // retain the trailing namespace separator in the prefix
    	    $prefix = substr($class, 0, $pos + 1);

            // the rest is the relative class name
            $relative_class = substr($class, $pos + 1);

            // try to load a mapped file for the prefix and relative class
            //$mapped_file = $this->loadMappedFile($prefix, $relative_class);
    	    // are there any base directories for this namespace prefix?
	    if ($this->debug)
    		$this->al_dbg("loader_map: check prefix `$prefix`...");

            if (!empty($this->ns_map[$prefix])) {
            	// look through base directories for this namespace prefix
		if ($this->debug)
		    $this->al_dbg("loader_map: prefix found");

                foreach( $this->ns_map[$prefix] as $base_dir) {
            	    // replace the namespace prefix with the base directory,
            	    // replace namespace separators with directory separators
                    // in the relative class name, append with .php
                    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
                    if ($this->debug)
                    	$this->al_dbg("loader_map: try file=`$file`");

                    // if the mapped file exists, require it & return
                    if (is_file($file)) {
                        // yes, we're done
			if ($this->debug)
		    	    $this->al_dbg("loader_map: loading file `$file`");
                        return require($file);
                    }
                }
            }
            // remove the trailing namespace separator for the next iteration
            // of strrpos()
            $prefix = rtrim($prefix, '\\');
        }	    

	//try to load a class by looking in paths
	foreach ($this->paths as $pfx) {
		$pfx = trim($pfx);
		if ($this->debug)
		    $this->al_dbg("loader_path: check file=".$pfx.$_class.".php");
		if (is_file($file=$pfx.$_class.'.php') || is_file($file=$pfx.strtolower($_class).'.php')) {
		    if ($this->debug)
		    	$this->al_dbg("loader_path: loading file `$file`");
		    return require($file);
		}
	}

        return false;
    }
}