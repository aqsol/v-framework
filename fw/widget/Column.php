<?php

namespace widget;

/**
 * Column is the base class of all [[GridView]] column classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
/*
    the grid view object that owns this column.
    $object->grid

    the header cell text content or callable `function ($model, $column)`
    $object->header

    the header HTML options array or callable `function ($model, $column)` that returns an HTML options array
    $object->headerOptions

    the footer cell text content or callable `function ($model, $column)`
    $object->footer

    the footer HTML options array or callable `function ($model, $column)` that returns an HTML options array
    $object->headerOptions

    the content cell text content or callable `function ($model, $column)`
    $object->content;

    the content HTML options array or callable `function ($model, $column)` that returns an HTML options array
    $object->contentOptions

    whether this column is visible. Defaults to true.
    $object->visible

*/

class Column extends \core\VObject {
 
    public function __construct(array $config = array()) {
	$defaults = array(
	    'grid' => null,
	    'visible' => true,

	    'header' => null,
	    'headerOptions' => array(),

	    'footer' => null,
	    'footerOptions' => array(),
	    
	    'content' => null,
	    'contentOptions' => array(),
	    
	    'filter' => null,
	    'filterOptions' => array(),

	);
	if (!isset($config['grid']))
	    throw new \InvalidArgumentException('column: missing grid in constructor');

	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }




    // Renders the header cell.
    public function renderHeaderCell($model = null) {
	if (is_callable($this->headerOptions) && $model !== null)
	    $options = call_user_func($this->headerOptions, $model, $this);
	else
	    $options = $this->headerOptions;
        return Widget::html()->tag('table-header', array('content' => $this->renderHeaderCellContent($model), 'options' => $options), $options);
    }

    protected function renderHeaderCellContent($model = null) {
	if (is_callable($this->header) && $model !== null)
	    return call_user_func($this->header, $model, $this);
	if (is_string($this->header) && trim($this->header !== ''))
	    return $this->header;
        return $this->grid->emptyCell;
    }



    // Renders the footer cell.
    public function renderFooterCell($model = null) {
	if (is_callable($this->footerOptions) && $model !== null)
	    $options = call_user_func($this->footerOptions, $model, $this);
	else
	    $options = $this->footerOptions;
        return Widget::html()->tag('table-cell', array('content' => $this->renderFooterCellContent($model), 'options' => $options), $options);
    }

    protected function renderFooterCellContent($model = null) {
	if (is_callable($this->footer) && $model !== null)
	    return call_user_func($this->footer, $model, $this);
	if (is_string($this->footer) && trim($this->footer !== ''))
	    return $this->footer;
        return $this->grid->emptyCell;
    }

    public function renderContentCell($model = null) {
	if (is_callable($this->contentOptions) && $model !== null)
	    $options = call_user_func($this->contentOptions, $model, $this);
	else
	    $options = $this->contentOptions;
        return Widget::html()->tag('table-cell', array('content' => $this->renderContentCellContent($model), 'options' => $options), $options);
    }

    protected function renderContentCellContent($model = null) {
	if (is_callable($this->content) && $model !== null)
	    return call_user_func($this->content, $model, $this);
	if (is_string($this->content) && trim($this->content !== ''))
	    return $this->content;
        return $this->grid->emptyCell;
    }

    // Renders the filter cell.
    public function renderFilterCell($model = null) {
	if (is_callable($this->filterOptions) && $model !== null)
	    $options = call_user_func($this->filterOptions, $model, $this);
	else
	    $options = $this->filterOptions;
        return Widget::html()->tag('table-cell', array('content' => $this->renderFilterCellContent($model), 'options' => $options), $options);
    }

    // Renders the filter cell content.
    // The default implementation simply renders a space.
    protected function renderFilterCellContent($model = null) {
        return $this->grid->emptyCell;
    }
}
