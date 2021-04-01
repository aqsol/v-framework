<?php

namespace util;

class ClientScript extends \core\VObject {

    public function __construct($config = []) {
	$defaults = [
	    // JavaScript code blocks
	    'scripts' => [],
	    // JavaScript files
	    'scriptFiles' => [],
	    // css code blocks - [CSS code, media type ].
	    'css' => [],
	    // CSS files (CSS URL=>media type).
	    'cssFiles' => [],
	    // registered head meta tags. Each array element represents an option
	    'metaTags' => [],
	];
	$config += $defaults;
	parent::__construct($config);
    }

    public function addPageScript($script) {
	$this->scripts[] = $script;
    }

    public function getPageScripts() {
	return $this->scripts;
    }
    
    public function renderPageScripts() {
	if (empty($this->scripts))
	    return null;

	$scripts = implode("\n", $this->scripts);
	return "<script type='text/javascript'>/*<![CDATA[*/{$scripts}/*]]>*/</script>";
    }



    public function addPageScriptFile($scriptUrl) {
	$this->scriptFiles[] = $scriptUrl;
    }

    public function renderPageScriptFiles() {
	if (empty($this->scriptFiles))
	    return null;

	$out = [];
	foreach($this->scriptFiles as $scriptUrl)
	    $out[] = \V::app()->view->html->script($scriptUrl);

	return implode("\n", $out);
    }



    public function addPageCssFile($cssUrl, $options = []) {
	$this->cssFiles[] = [ $cssUrl, $options ];
    }

    public function renderPageCssFiles() {
	if (empty($this->cssFiles))
	    return null;

	$out = [];
	foreach($this->cssFiles as $cssSpec)
	    $out[] = \V::app()->view->html->style($cssSpec[0], $cssSpec[1]);

	return implode("\n", $out);
    }



    public function addPageCss($css, $media = '') {
	$this->css[] = [ $css, $media ];
    }

    public function renderPageCss() {
	if (empty($this->css))
	    return null;

	$out = [];
	foreach($this->css as $css)
	    $out[] = \V::app()->view->html->tag('style', [ 'content' => $css[0], 'options' => [ 'media' => $css[1] ] ]);

	return implode("\n", $out);
    }


    public function addPageMeta($meta) {
	$this->metaTags[] = $meta;
    }

    public function renderPageMeta() {
	if (empty($this->metaTags))
	    return null;

	$out = [];
	foreach($this->metaTags as $meta)
	    $out[] = \V::app()->view->html->tag('meta', [], $meta);
	
	return implode("\n", $out);
    }

}