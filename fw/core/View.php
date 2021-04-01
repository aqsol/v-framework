<?php

namespace core;

class View extends \core\VObject {

    private $_classes = [];

    public function __construct($config = []) {
	$defaults  = [
	    'views_path' => 'views', //relative to app directory
	    'layouts_path' => 'views/layouts', //relative to app directory
	    'default_layout' => 'main',
	    'classes' => [
		'html' => '\util\Html',
		'form' => '\util\Form',
	    ],
	    'raw' => false,
	];
	$cfg = \util\Set::merge($defaults, $config);
	parent::__construct($cfg);
    }

    public function &__get($name) {
	//if the component is defined in classes, get it from _classes; create it if needed
	if (isset($this->_config['classes'][$name])) {
	    if (isset($this->_classes[$name]))
		return $this->_classes[$name];
	    $class = $this->_config['classes'][$name];
	    $this->_classes[$name] = new $class;
	    return $this->_classes[$name];
	}
	if (isset($this->_config[$name]))
	    return $this->_config[$name];
	$null = null;
	return $null;
    }


    /**
     * Renders output with given template
     *
     * @param string $template Name of the template to be rendererd
     * @param array  $args     Args for view
     */
    //web
    public function renderFile($template, $data = []) {
        extract($data);
        ob_start();

	if ($template[0] != '/')
	    $template = APP . '/' . $this->_config['views_path'] . '/' . $template . '.php';

        require $template;
        return ob_get_clean();
    }


    //shortcut to render for no layout and default return
    public function renderBlock($template, $data = [], $return = true, $render_cscript = false) {
	return $this->render($template, $data, $layout = false, $return, $render_cscript);
    }

    public function render($template, $data = [], $layout = null, $return = false, $render_cscript = true) {
	//render the template
	// if template starts with '/' then it is treated as absolute path and  no further processing done
	if ($template[0] != '/')
	    $template = APP . '/' . $this->_config['views_path'] . '/' . $template . '.php';

        $content = $output = $this->renderFile($template, $data);

	if (!$this->_config['raw']) {
	    //whether to use a layout
	    if ($layout !== false) {
		//render via layout => determine layout file
		if ($layout === null) {
		    $layout = $this->_config['default_layout'];
		}
		$layout = APP . '/' . $this->_config['layouts_path'] . '/' . $layout . '.php';
		$output = $this->renderFile($layout, compact('content'));
	    }

//	    //here output contains all we need, with or without layout
//	    //if there is a clientscript component, use it's render method
//	    if (isset(\V::app()->clientscript) && $render_cscript) {
//		\V::app()->clientscript->render($output);
//	    }

	}

	if ($return)
	    return $output;

	return \V::app()->response->write($output);
    }

}
