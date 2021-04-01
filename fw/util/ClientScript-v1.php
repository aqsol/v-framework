<?php

namespace util;

class ClientScript extends \core\VObject {

    public function __construct($config = []) {
	    $defaults = array(
		'defaultScriptFilePosition' => 'head',
		'defaultScriptPosition' => 'head',
		// registered JavaScript code blocks (position, id => code)
		'scripts' => [],
		// registered JavaScript files (position, key => URL)
		'scriptFiles' => [],
		// registered css code blocks (key => array(CSS code, media type)).
		'css' => [],
		// registered CSS files (CSS URL=>media type).
		'cssFiles' => [],
		// registered head meta tags. Each array element represents an option array
		// that will be passed as the last parameter of {@link CHtml::metaTag}.
		'metaTags' => [
		    'page' => [],
		],
		// registered head link tags. Each array element represents an option array
		// that will be passed as the last parameter of {@link CHtml::linkTag}.
		'linkTags' => [],
	    );
	    $config += $defaults;
	    parent::__construct($config);
    }



    //  new versions
    public function getPageScripts() {
	return $this->scripts['page'];
    }
    
    public function renderPageScripts() {
	if (!isset($this->scripts['page']))
	    return null;

	$scripts = implode("\n", $this->scripts['page']);

	return "<script type='text/javascript'>/*<![CDATA[*/{$scripts}/*]]>*/</script>";
    }

    public function renderPageScriptFiles() {
	if (!isset($this->scriptFiles['page']))
	    return null;

	$out = [];
	foreach($this->scriptFiles['page'] as $scriptUrl)
	    $out[] = \V::app()->view->html->script($scriptUrl);

	return implode("\n", $out);
    }

    public function renderPageCssFiles() {
	if (!isset($this->cssFiles['page']))
	    return null;

	$out = [];
	foreach($this->cssFiles['page'] as $cssSpec)
	    $out[] = \V::app()->view->html->style($cssSpec[0], $cssSpec[1]);

	return implode("\n", $out);
    }

    public function renderPageCss() {
	if (!isset($this->css['page']))
	    return null;

	$out = [];
	foreach($this->css['page'] as $css)
	    $out[] = \V::app()->view->html->tag('style', [ 'content' => $css[0], 'options' => [ 'media' => $css[1] ] ]);

	return implode("\n", $out);
    }


    public function renderPageMeta() {
	$out = [];
	foreach($this->metaTags['page'] as $meta)
	    $out[] = \V::app()->view->html->tag('meta', [], $meta);
	
	return implode("\n", $out);
    }


    //new additions
    public function addPageScript($script) {
	$this->scripts['page'][] = $script;
    }
    public function addPageScriptFile($scriptUrl) {
	$this->scriptFiles['page'][] = $scriptUrl;
    }
    public function addPageCssFile($cssUrl, $options = []) {
	$this->cssFiles['page'][] = [ $cssUrl, $options ];
    }

    public function addPageCss($css, $media = '') {
	$this->css['page'][] = [ $css, $media ];
    }

    public function addPageMeta($meta) {
	$this->metaTags['page'][] = $meta;
    }






    /**
     * Renders the registered scripts.
     * This method is called in {@link CController::render} when it finishes
     * rendering content. CClientScript thus gets a chance to insert script tags
     * at <code>head</code> and <code>body</code> sections in the HTML output.
     * @param string $output the existing output that needs to be inserted with script tags
     */
    public function render(&$output) {
	$this->renderHead($output);
	$this->renderBodyBegin($output);
	$this->renderBodyEnd($output);
    }

    /**
     * Inserts the scripts in the head section.
     * @param string $output the output to be inserted with scripts.
     */
    public function renderHead(&$output) {
	    $html='';

//	    foreach($this->metaTags as $meta)
//		$html .= \V::app()->view->html->tag('meta', $meta)."\n";

	    foreach($this->linkTags as $link)
		$html .= \V::app()->view->html->tag('meta-link', $link)."\n";
//	    foreach($this->cssFiles as $url => $options)
//		$html .= \V::app()->view->html->style($url, $options)."\n";
//	    foreach($this->css as $css)
//		$html .= \V::app()->view->html->tag('style', array('content' => $css[0], 'options' => [ 'media' => $css[1] ]))."\n";

	    if(isset($this->scriptFiles['head'])) {
		foreach($this->scriptFiles['head'] as $scriptFile)
		    $html .= \V::app()->view->html->script($scriptFile)."\n";
	    }

	    if(isset($this->scripts['head'])) {
		$js = implode("\n", $this->scripts['head']);
		$html .= \V::app()->view->html->tag('js-block', [ 'content' => $js ])."\n";
	    }
	    if($html !== '') {
		$count = 0;
		$output = preg_replace('/(<title\b[^>]*>|<\\/head\s*>)/is','<###head###>$1',$output,1,$count);
		if($count)
		    $output=str_replace('<###head###>',$html,$output);
		else
		    $output = $html . $output;
	    }
    }

    /**
     * Inserts the scripts at the beginning of the body section.
     * @param string $output the output to be inserted with scripts.
     */
    public function renderBodyBegin(&$output) {
	$html='';
	if(isset($this->scriptFiles['bodyBegin'])) {
	    foreach($this->scriptFiles['bodyBegin'] as $scriptFile)
	    $html .= \V::app()->view->html->script($scriptFile)."\n";
	}

	if(isset($this->scripts['bodyBegin'])) {
	    $js = implode("\n", $this->scripts['bodyBegin']);
	    $html .= \V::app()->view->html->tag('js-block', [ 'content' => $js ])."\n";
	}

	if($html !== '') {
		$count=0;
		$output=preg_replace('/(<body\b[^>]*>)/is','$1<###begin###>',$output,1,$count);
		if($count)
		    $output = str_replace('<###begin###>', $html, $output);
		else
		    $output= $html . $output;
	}
    }

    /**
     * Inserts the scripts at the end of the body section.
     * @param string $output the output to be inserted with scripts.
     */
    public function renderBodyEnd(&$output) {
	    $fullPage = 0;
	    $output = preg_replace('/(<\\/body\s*>)/is','<###end###>$1',$output,1,$fullPage);
	    $html = '';

	    if(isset($this->scriptFiles['bodyEnd'])) {
		foreach($this->scriptFiles['bodyEnd'] as $scriptFile)
		    $html .= \V::app()->view->html->script($scriptFile)."\n";
	    }



	    $scripts=isset($this->scripts['bodyEnd']) ? $this->scripts['bodyEnd'] : [];

	    if(isset($this->scripts['onReady'])) {
		if($fullPage)
		    $scripts[] = "jQuery(function($) {\n".implode("\n",$this->scripts['onReady'])."\n});";
		else
		    $scripts[] = implode("\n",$this->scripts['onReady']);
	    }

	    if(isset($this->scripts['onLoad'])) {
		if($fullPage)
		    $scripts[]="jQuery(window).on('load',function() {\n".implode("\n",$this->scripts['onLoad'])."\n});";
		else
		    $scripts[]=implode("\n",$this->scripts['onLoad']);
	    }
	    if(!empty($scripts)) {
		$_scripts = implode("\n",$scripts);
		$html .= "<script type=\"text/javascript\">\n/*<![CDATA[*/\n{$_scripts}\n/*]]>*/\n</script>";
	    }

	    if($fullPage)
		$output=str_replace('<###end###>',$html,$output);
	    else
		$output = $output . $html;
    }







    /*
     * registerCssFile($url, $media) registers a css file for a media; if media is empty apply to all media types
     * registerCss($id, $css, $media) registers a css block for a media; if media is empty, apply to all media types
     * registerScriptFile($url, $position) registers a javascript file, position is 'head', 'bodyBegin', 'bodyEnd'
     * registerScript($id, $script, $position) registers a script file, position is 'head', 'bodyBegin', 'bodyEnd', 'onLoad', 'onReady'
     * registerMetaTag(array()); params: name, content, http-equiv
     * registerLinkTag(array()); params: charset href hreflang media rel rev sizes target type
     */
    public function registerCssFile($url, $options = []) {
	$defaults = [ 'media' => 'screen', ];
	$options += $defaults;
	$this->cssFiles[$url] = $options;
    }
    public function registerCss($id, $css, $media = '') {
	$this->css[$id] = [ $css, $media ];
    }
    public function registerScriptFile($url, $pos = null) {
	$pos = (empty($pos)) ? $this->_config['defaultScriptFilePosition'] : $pos;
	$this->scriptFiles[$pos][$url] = $url;
    }


    

    public function registerScript($id, $script, $pos = null) {
	$pos = (empty($pos)) ? $this->_config['defaultScriptPosition'] : $pos;
	$this->scripts[$pos][$id] = $script;
    }
    public function registerMetaTag($tags = []) {
	//$this->metaTags[] = $tags;
    }
    public function registerLinkTag($links = []) {
	$this->linkTags[] = $links;
    }
}