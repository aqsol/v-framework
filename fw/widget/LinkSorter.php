<?php

namespace widget;


use \data\Sort;
/**
 * LinkSorter creates a link to sort an attribute.
 *
 * LinkSorter works with a [[Sort]] object which creates sort options
 *
 */
class LinkSorter extends Widget {

	public function __construct(array $config = array()) {
	    $defaults = array(
		'sort' => null,
		'linkOptions' => array(),
		'sortDescPrepend' => '<i class="fa fa-sort-desc"></i> ',
		'sortAscPrepend' => '<i class="fa fa-sort-asc"></i> ',
		'sortPrepend' => '<i class="fa fa-sort"></i>',
	    );
	    $config = \util\Set::merge($defaults, $config);
	    parent::__construct($config);
	}

	// Initializes the view.
	public function _init() {
	    parent::_init();
    	    if ($this->sort === null) {
        	throw new InvalidConfigException('The "sort" property must be set.');
    	    }
	}


	// Executes the widget.
	public function run() {
	}



	/**
	 * Generates a hyperlink that links to the sort action to sort by the specified attribute.
	 * Based on the sort direction, the CSS class of the generated hyperlink will be appended
	 * with "asc" or "desc".
	 * @param string $attribute the attribute name by which the data should be sorted by.
	 * @param array $options additional HTML attributes for the hyperlink tag.
	 * There is one special attribute `label` which will be used as the label of the hyperlink.
	 * If this is not set, the label defined in [[attributes]] will be used.
	 * If no label is defined, [[\yii\helpers\Inflector::camel2words()]] will be called to get a label.
	 * Note that it will not be HTML-encoded.
	 * @return string the generated hyperlink
	 * @throws InvalidConfigException if the attribute is unknown
	 */
	public function link($attribute, $options = []) {
	
	    $options = \util\Set::merge($this->linkOptions, $options);
	    $prepend = null;
	    if (isset($this->sort->sort[$attribute])) {
		$prepend = ($this->sort->sort[$attribute] === Sort::SORT_DESC) ? $this->sortDescPrepend : $this->sortAscPrepend;
	    }

	    if ($prepend === null && !empty($this->sort->attributes[$attribute])) {
		$prepend = ($this->sort->attributes[$attribute] === Sort::SORT_DESC) ? $this->sortDescPrepend : $this->sortAscPrepend;
	    }

	    if ($prepend === null)
		$prepend = $this->sortPrepend;

    	    $url = $this->sort->createUrl($attribute);
    	    $options['data-sort'] = $this->sort->createSortParam($attribute);

    	    if (isset($options['label'])) {
        	$label = $options['label'];
        	unset($options['label']);
    	    } else {
    		$label = $attribute;
    	    }

	    $_html = new \util\Html;
	    return Widget::html()->tag('link', array('url' => $url, 'title' => $prepend.$label, 'options' => $options), $options + ['escape' => false]);
	}
}