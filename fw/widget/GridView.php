<?php

namespace widget;


/**
 * The GridView widget is used to display data in a grid.
 *
 * It provides features like [[sorter|sorting]], [[pager|paging]] and also [[filterModel|filtering]] the data.
 *
 * A basic usage looks like the following:
 *
 * ```php
 * <?= GridView::widget([
 *     'dataProvider' => $dataProvider,
 *     'columns' => [
 *         'id',
 *         'name',
 *         'created_at',
 *         // ...
 *     ],
 * ]) ?>
 * ```
 *
 * The columns of the grid table are configured in terms of [[Column]] classes,
 * which are configured via [[columns]].
 *
 * options
 
    dataProvider;
	data\DataProvider, the data provider for the view. This property is required.

    pagination
	Pagination definition. could be an array of options for Pagination class or false for no pagination
	See [Pagination for options]

    layout = "{summary}\n{items}\n{pager}";
	the layout that determines how different sections of the list view should be organized.
	The following tokens will be replaced with the corresponding section contents:
	- `{summary}`: the summary section. See [[renderSummary()]].
	- `{items}`: the list items. See [[renderItems()]].
	- `{pager}`: the pager. See [[renderPager()]].

    summary; the HTML content to be displayed as the summary of the list view or false for no summary
	The following tokens will be replaced with the corresponding values:
	- `{:begin}`: the starting row number (1-based) currently being displayed
	- `{:end}`: the ending row number (1-based) currently being displayed
	- `{:count}`: the number of rows currently being displayed
	- `{:page}`: the page number (1-based) current being displayed
	- `{:pageCount}`: the number of pages available

    summaryOptions; = ['class' => 'summary'];
	the HTML attributes for the summary of the list view.


    columns;  array, grid column configuration. Each array element represents the configuration for one particular grid column. For example,
	[
    	    ['class' => 'widget\SerialColumn'],
	    [
		'class' => 'widget\DataColumn', // this line is optional
    		'attribute' => 'name',
		'label' => 'Name',
	    ],
	    ['class' => 'zgrid\CheckboxColumn'],
	]

    dataColumnClass;  = '\zgrid\DataColumn';
	the default data column class if the class name is not explicitly specified when configuring a data column.
	Defaults to 'zgrid\DataColumn'.

    showHeader; default true
	whether to show the header section of the grid table.

    showFooter; default true
	whether to show the footer section of the grid table.

    tableOptions; default['class' => 'table table-striped table-bordered'];
	the HTML attributes for the grid table element.

    headerRowOptions; default [];
	the HTML attributes for the table header row.

    footerRowOptions; default [];
	the HTML attributes for the table footer row.

    pager; default ['class' => 'zgrid\LinkPager'];
	the configuration for the pager widget. By default, [[LinkPager]] will be used to render the pager.
	You can use a different widget class by configuring the "class" element.
	Note that the widget must support the `pagination` property

    emptyText;
	the HTML content to be displayed when [[dataProvider]] does not have any data.

    emptyTextOptions; default = ['class' => 'empty'];
	the HTML attributes for the emptyText of the list view.

    wrapOptions; default = ['class' => 'grid-view'];
	the HTML attributes for the container tag of the grid view.

    caption; default ''
	the caption of the grid table

    captionOptions; default = [];
	the HTML attributes for the caption element.

    emptyCell; default = '&nbsp;';
	the HTML display when the content of a cell is empty

    filterModel; default null
	the model that keeps the user-entered filter data.
	When this property is set, the grid view will enable column-based filtering.
	Each data column by default will display a text field at the top that users can fill in to filter the data.

	Note that in order to show an input field for filtering, a column must have its [[DataColumn::attribute]]
	property set or have [[DataColumn::filter]] set as the HTML code for the input field.

	When this property is not set (null) the filtering feature is disabled.

    beforeRow; default null
	an anonymous function that is called once BEFORE rendering each data model.
	It should have the similar signature as [[rowOptions]]. The return result of the function will be rendered directly.

    afterRow; default null
	an anonymous function that is called once AFTER rendering each data model.
	It should have the similar signature as [[rowOptions]]. The return result of the function will be rendered directly.

    filterRowOptions; default = ['class' => 'filters'];
	the HTML attributes for the filter row element.


 */
class GridView extends BaseListView {
    const FILTER_POS_HEADER = 'header';
    const FILTER_POS_FOOTER = 'footer';
    const FILTER_POS_BODY = 'body';


    public function __construct(array $config = array()) {
	$defaults = array(
	    'dataColumnClass' => 'widget\DataColumn',
	    'wrapOptions' => [ 'class' => 'table-responsive' ],
	    'headerRowOptions' => array(),
	    'footerRowOptions' => array(),
	    'rowOptions' => array(),
	    'caption' => null,
	    'captionOptions' => array(),
	    'tableOptions' => array(
		//'class' => 'table table-striped table-bordered'
		'class' => 'table',
	    ),
	    'showHeader' => true,
	    'showFooter' => false, //true,
	    'emptyCell' => '&nbsp;',

	    'summary' => null,
	    'summaryOptions' => array(
		'class' => 'summary',
	    ),
	    'layout' => "{summary}\n{items}\n{pager}",

	    'filterModel' => null,
	    'filterRowOptions' =>  array(
		'class' => 'filters'
	    ),
	    'filterPosition' => self::FILTER_POS_BODY,

	    'beforeRow' => null,
	    'afterRow' => null,
	    'filterRoute' => null, //do not set yet, will be overwritten by ClientOptions
	    'filterSelector' => '',
	);
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }

    public $_filter_form = null;

    /**
     * @var array|Closure the HTML attributes for the table body rows. This can be either an array
     * specifying the common HTML attributes for all body rows, or an anonymous function that
     * returns an array of the HTML attributes. The anonymous function will be called once for every
     * data model returned by [[dataProvider]]. It should have the following signature:
     *
     * ```php
     * function ($model, $key, $index, $grid)
     * ```
     *
     * - `$model`: the current data model being rendered
     * - `$key`: the key value associated with the current data model
     * - `$index`: the zero-based index of the data model in the model array returned by [[dataProvider]]
     * - `$grid`: the GridView object
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    //public $rowOptions = [];

    /**
     * @var string|array the URL for returning the filtering result. [[Url::to()]] will be called to
     * normalize the URL. If not set, the current controller action will be used.
     * When the user makes change to any filter input, the current filtering inputs will be appended
     * as GET parameters to this URL.
     */
    //public $filterUrl;
    /**
     * @var string additional jQuery selector for selecting filter input fields
     */
    //public $filterSelector;
    /**
     * @var string whether the filters should be displayed in the grid view. Valid values include:
     *
     * - [[FILTER_POS_HEADER]]: the filters will be displayed on top of each column's header cell.
     * - [[FILTER_POS_BODY]]: the filters will be displayed right below each column's header cell.
     * - [[FILTER_POS_FOOTER]]: the filters will be displayed below each column's footer cell.
     */
    //public $filterPosition = self::FILTER_POS_BODY;
    /**
     * @var array the options for rendering the filter error summary.
     * Please refer to [[Html::errorSummary()]] for more details about how to specify the options.
     * @see renderErrors()
     */
    public $filterErrorSummaryOptions = ['class' => 'error-summary'];
    /**
     * @var array the options for rendering every filter error message.
     * This is mainly used by [[Html::error()]] when rendering an error message next to every filter input field.
     */
    public $filterErrorOptions = ['class' => 'help-block'];


    /**
     * Initializes the grid view.
     * This method will initialize required property values and instantiate [[columns]] objects.
     */
    public function _init() {

	parent::_init();

        if (!isset($this->filterRowOptions['id'])) {
            $this->filterRowOptions['id'] = $this->id . '-filters';
        }

	//initialize columns
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
        	//create a default datacolumn
		$class = $this->dataColumnClass;
		$_config = array(
        	    'grid' => $this,
        	    'attribute' => $column,
        	    'label' => null,
		);
    		$column = new $class($_config);
            } else {
        	$class = $this->dataColumnClass;
		if (!empty($column['class'])) {
		    $class = $column['class'];
		    unset($column['class']);
		}
		$_config = \util\Set::merge(array('grid' => $this), $column);
                $column = new $class($_config);
            }
            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }
            $this->columns[$i] = $column;
        }

	//prepare data for filtering
	if (!empty($this->filterModel) && $this->filterModel !== false) {
	    //$this->_filter_form = new Form();
	    //$this->_filter_form->config(array('binding' => $this->filterModel));
	    self::form()->create($this->filterModel);
	}

    }


    // Runs the widget.
    public function run() {
	$id = $this->id;

	//filter javascript preparation
	//prepare filter url as current route + params, but unset the filterable columns as defined
	$query_params = \V::app()->request->query;
	foreach($this->columns as $i => $column) {
	    if (!empty($column->filter)) {
		unset($query_params['__filter'][$column->attribute]);
	    }
	}
	if (empty($this->filterRoute))
	    $this->filterRoute = \V::app()->config('c_route');
        $filterUrl = \V::app()->createUrl($this->filterRoute, $query_params); //\V::app()->request->query);

        $id = $this->filterRowOptions['id'];
        $filterSelector = "#$id input, #$id select";
        if (!empty($this->filterSelector)) {
            $filterSelector .= ', ' . $this->filterSelector;
        }

	//pagination selector
	$pagerSelector = '';
	if ($this->pagination && $this->pager !== false) {
	    //get pager class
	    $class = $this->__pager->wrapOptions['class'];
	    $class = explode(' ', $class)[0];
	    $pagerSelector = "#{$this->id} ul.$class a";
	}
	

	$js_data = array('filterUrl' => $filterUrl, 'filterSelector' => $filterSelector, 'pagerSelector' => $pagerSelector);
	$options = json_encode($js_data);

	//TODO: adapt javascript
	if (1) {
	    \V::app()->clientscript->registerScriptFile(\V::app()->am->publishFile(dirname(__FILE__).'/yii2.gridView.js'), 'bodyEnd');
	    \V::app()->clientscript->registerScript('grid-view', "jQuery('#$id').yiiGridView($options);", 'onReady');
	}


//        if ($this->dataProvider->count() > 0) {
        $content = preg_replace_callback("/{\\w+}/", function ($matches) {
        	//echo "run: rendering {$matches[0]}\n";
    	    $content = $this->renderSection($matches[0]);
	    return $content === false ? $matches[0] : $content;
        }, $this->layout);
//        } else {
//            $content = $this->renderEmpty();
//        }

        return self::html()->tag('block', array('content' => $content, 'options' => $this->wrapOptions), $this->wrapOptions);
    }

    // Renders a section of the specified name, If the named section is not supported, false will be returned.
    // $name - the section name, e.g., `{summary}`, `{items}`.
    public function renderSection($name) {
        switch ($name) {
            case '{summary}':
                return $this->renderSummary();
            case '{items}':
                return $this->renderItems();
            case '{pager}':
                return $this->renderPager();
            case '{errors}':
                return $this->renderErrors();
            default:
                return false;
        }
    }

    /**
     * Renders validator errors of filter model.
     * @return string the rendering result.
     */
    public function renderErrors() {
        if ($this->filterModel instanceof Model && $this->filterModel->hasErrors()) {
            return Html::errorSummary($this->filterModel, $this->filterErrorSummaryOptions);
        } else {
            return '';
        }
    }

    // Renders the data models for the grid view.
    public function renderItems() {

    	    $caption = $this->renderCaption();
    	    $columnGroup = $this->renderColumnGroup();
    	    $tableHeader = $this->showHeader ? $this->renderTableHeader() : false;
    	    $tableBody = $this->renderTableBody();
    	    $tableFooter = $this->showFooter ? $this->renderTableFooter() : false;
    	    $content = array_filter([
        	$caption,
        	$columnGroup,
        	$tableHeader,
        	$tableFooter,
        	$tableBody,
    	    ]);

	    return self::html()->tag('table', array('options' => $this->tableOptions, 'content' => implode("\n", $content)), $this->tableOptions);
    }

    // Renders the caption element.
    public function renderCaption() {
        if (!empty($this->caption)) {
            return self::html()->tag('<caption{:options}>{:content}</caption>', array('content' => $this->caption, 'options' => $this->captionOptions), $this->captionOptions);
        } else {
            return false;
        }
    }

    /**
     * Renders the column group HTML.
     * @return bool|string the column group HTML or `false` if no column group should be rendered.
     */
    public function renderColumnGroup() {
        $requireColumnGroup = false;
        foreach ($this->columns as $column) {
            /* @var $column Column */
            if (!empty($column->options)) {
                $requireColumnGroup = true;
                break;
            }
        }
        if ($requireColumnGroup) {
            $cols = [];
            foreach ($this->columns as $column) {
                $cols[] = Html::tag('col', '', $column->options);
            }

            return Html::tag('colgroup', implode("\n", $cols));
        } else {
            return false;
        }
    }

    /**
     * Renders the table header.
     * @return string the rendering result.
     */
    public function renderTableHeader() {
        $cells = [];
        foreach ($this->columns as $column) {
            /* @var $column Column */
            $cells[] = $column->renderHeaderCell();
        }
        $content = $this->html()->tag('table-row', array('content' => implode('', $cells)), $this->headerRowOptions);

        if ($this->filterPosition == self::FILTER_POS_HEADER) {
            $content = $this->renderFilters() . $content;
        } elseif ($this->filterPosition == self::FILTER_POS_BODY) {
            $content .= $this->renderFilters();
        }

        return "<thead>\n" . $content . "\n</thead>";
    }

    // Renders the table footer.
    public function renderTableFooter() {
        $cells = [];
        foreach ($this->columns as $column) {
            /* @var $column Column */
            $cells[] = $column->renderFooterCell();
        }
        $content = Widget::html()->tag('table-row', array('content' => implode('', $cells), 'options' => $this->footerRowOptions), $this->footerRowOptions);
        if ($this->filterPosition == self::FILTER_POS_FOOTER) {
            $content .= $this->renderFilters();
        }

        return "<tfoot>\n" . $content . "\n</tfoot>";
    }

    /**
     * Renders the filter.
     * @return string the rendering result.
     */
    public function renderFilters() {
        if ($this->filterModel !== null) {
	    //echo "renderFilter: rendering";
            $cells = [];
            foreach ($this->columns as $column) {
                /* @var $column Column */
                $cells[] = $column->renderFilterCell();
            }
            return self::html()->tag('table-row', array('content' => implode('', $cells), 'options' => $this->filterRowOptions), $this->filterRowOptions);
        } else {
            return '';
        }
    }




    /**
     * Renders the table body.
     * @return string the rendering result.
     */
    public function renderTableBody() {
        //$models = array_values($this->dataProvider->getModels());
        //$keys = $this->dataProvider->getKeys();
        $rows = [];
    
        foreach ($this->dataProvider as $model) {
            if (is_callable($this->beforeRow)) {
                $row = call_user_func($this->beforeRow, $model, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }

            $rows[] = $this->renderTableRow($model);

            if ($this->afterRow !== null) {
                $row = call_user_func($this->afterRow, $model, $this);
                if (!empty($row)) {
                    $rows[] = $row;
                }
            }
        }

        if (empty($rows)) {
            $colspan = count($this->columns);

            return "<tbody>\n<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>\n</tbody>";
        } else {
            return "<tbody>\n" . implode("\n", $rows) . "\n</tbody>";
        }
    }


    /**
     * Renders a table row with the given data model
     * @param mixed $model the data model to be rendered
     * @return string the rendering result
     */
    public function renderTableRow($model) {
        $cells = [];
        /* @var $column Column */
        foreach ($this->columns as $column) {
            $cells[] = $column->renderContentCell($model);
        }
	if (is_callable($this->rowOptions) && $model !== null)
	    $options = call_user_func($this->rowOptions, $model, $this);
	else
	    $options = $this->rowOptions;

        $options['data-key'] = (string)$model->key();

        return Widget::html()->tag('table-row', array('content' => implode('', $cells)), $options);
    }


}
