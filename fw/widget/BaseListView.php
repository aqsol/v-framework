<?php

namespace widget;

/**
 * BaseListView is a base class for widgets displaying data from data provider
 * such as ListView and GridView.
 *
 * It provides features like sorting, paging and also filtering the data.
 *
 * For more details and usage information on BaseListView, see the [guide article on data widgets](guide:output-data-widgets).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
 

    /**
     * @var array the HTML attributes for the container tag of the list view.
     * The "tag" element specifies the tag name of the container element and defaults to "div".
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    //public $wrapOptions = [];
    /**
     * @var \yii\data\DataProviderInterface the data provider for the view. This property is required.
     */
    //public $dataProvider;
    /**
     * @var string the HTML content to be displayed as the summary of the list view.
     * If you do not want to show the summary, you may set it with an empty string.
     *
     * The following tokens will be replaced with the corresponding values:
     *
     * - `{begin}`: the starting row number (1-based) currently being displayed
     * - `{end}`: the ending row number (1-based) currently being displayed
     * - `{count}`: the number of rows currently being displayed
     * - `{totalCount}`: the total number of rows available
     * - `{page}`: the page number (1-based) current being displayed
     * - `{pageCount}`: the number of pages available
     */
    //public $summary;
    /**
     * @var array the HTML attributes for the summary of the list view.
     * The "tag" element specifies the tag name of the summary element and defaults to "div".
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    //public $summaryOptions = ['class' => 'summary'];
    /**
     * @var bool whether to show an empty list view if [[dataProvider]] returns no data.
     * The default value is false which displays an element according to the [[emptyText]]
     * and [[emptyTextOptions]] properties.
     */
    //public $showOnEmpty = false;
    /**
     * @var string|false the HTML content to be displayed when [[dataProvider]] does not have any data.
     * When this is set to `false` no extra HTML content will be generated.
     * The default value is the text "No results found." which will be translated to the current application language.
     * @see showOnEmpty
     * @see emptyTextOptions
     */
    //public $emptyText;
    /**
     * @var array the HTML attributes for the emptyText of the list view.
     * The "tag" element specifies the tag name of the emptyText element and defaults to "div".
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    //public $emptyTextOptions = ['class' => 'empty'];
    /**
     * @var string the layout that determines how different sections of the list view should be organized.
     * The following tokens will be replaced with the corresponding section contents:
     *
     * - `{summary}`: the summary section. See [[renderSummary()]].
     * - `{items}`: the list items. See [[renderItems()]].
     * - `{sorter}`: the sorter. See [[renderSorter()]].
     * - `{pager}`: the pager. See [[renderPager()]].
     */
    //public $layout = "{summary}\n{items}\n{pager}";
    /**
     * @var array the configuration for the pager widget. By default, [[LinkPager]] will be
     * used to render the pager. You can use a different widget class by configuring the "class" element.
     * Note that the widget must support the `pagination` property which will be populated with the
     * [[\yii\data\BaseDataProvider::pagination|pagination]] value of the [[dataProvider]] and will overwrite this value.
     */
    //public $pager = [];
    /**
     * @var array the configuration for the sorter widget. By default, [[LinkSorter]] will be
     * used to render the sorter. You can use a different widget class by configuring the "class" element.
     * Note that the widget must support the `sort` property which will be populated with the
     * [[\yii\data\BaseDataProvider::sort|sort]] value of the [[dataProvider]] and will overwrite this value.
     */
    //public $sorter = [];


 
abstract class BaseListView extends \widget\Widget {

    //helper objects; initialized in _init, depending on configuration params
    public $__pagination = null;
    public $__sort = null;
    public $__pager = null;
    public $__sorter = null;


    public function __construct($config = []) {
	$defaults = [
	    'wrapOptions' => [],
	    'dataProvider' => null,
	    'summary' => null,
	    'summaryOptions' => ['class' => 'summary'],
	    'showOnEmpty' => false,
	    'emptyText' => null,
	    'emptyTextOptions' => ['class' => 'empty'],
	    'layout' => "{summary}\n{items}\n{pager}",
	    'pager' => [
		'class' => 'widget\LinkPager',
	    ],
	    'sorter' => [
		'class' => 'widget\LinkSorter',
	    ],
	    'pagination' => [
		'class' => 'data\Pagination',
		'pageSize' => 10,
	    ],
	    'sort' => [
		'class' => 'data\Sort',
		'attributes' => [],
	    ],
	];

	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }



    /**
     * Renders the data models.
     * @return string the rendering result.
     */
    abstract public function renderItems();

    /**
     * Initializes the view.
     */
    public function _init() {
        parent::_init();
        if ($this->dataProvider === null) {
            throw new \ErrorException('The "dataProvider" property must be set.');
        }
        if ($this->emptyText === null)
    	    $this->emptyText = \V::t('No results found');

        if (!isset($this->wrapOptions['id'])) {
            $this->wrapOptions['id'] = $this->id;
        }


	//pagination
        if ($this->pagination !== false) {
    	    $class = $this->pagination['class'];
    	    unset($this->pagination['class']);
    	    $this->pagination['totalCount'] = $this->dataProvider->count();
    	    $this->__pagination = new $class($this->pagination);
    	}

	// if pagination object exists, set limits on dataprovider
    	if ($this->__pagination) {
    	    $this->dataProvider->skip($this->__pagination->skip);
    	    $this->dataProvider->limit($this->__pagination->limit);
    	}
    	
    	//if pagination && pager != false, initialize pager
    	if ($this->__pagination && $this->pager != false) {
    	    $class = $this->pager['class'];
    	    unset($this->pager['class']);
    	    
    	    $this->pager['pagination'] = $this->__pagination;
    	    $this->__pager = new $class($this->pager);
    	}

	//sort
	if ($this->sort !== false) {
    	    $class = $this->sort['class'];
    	    unset($this->sort['class']);
    	    $this->__sort = new $class($this->sort);
	}

    	//if sort && sorter != false, initialize sorter
    	if ($this->__sort && $this->sorter != false) {
    	    $class = $this->sorter['class'];
    	    unset($this->sorter['class']);
    	    
    	    $this->sorter['sort'] = $this->__sort;
    	    $this->__sorter = new $class($this->sorter);
    	}
    	
	if ($this->__sort) {
	    $this->dataProvider->sort($this->__sort->sort);
	}

    }

    /**
     * Runs the widget.
     */
    public function run() {
        if ($this->showOnEmpty || $this->dataProvider->count() > 0) {
            $content = preg_replace_callback('/{\\w+}/', function ($matches) {
                $content = $this->renderSection($matches[0]);

                return $content === false ? $matches[0] : $content;
            }, $this->layout);
        } else {
            $content = $this->renderEmpty();
        }

        return $this->html()->tag('block', array('content' => $content, 'options' => $this->wrapOptions), $this->wrapOptions);

    }

    /**
     * Renders a section of the specified name.
     * If the named section is not supported, false will be returned.
     * @param string $name the section name, e.g., `{summary}`, `{items}`.
     * @return string|bool the rendering result of the section, or false if the named section is not supported.
     */
    public function renderSection($name) {
        switch ($name) {
            case '{summary}':
                return $this->renderSummary();
            case '{items}':
                return $this->renderItems();
            case '{pager}':
                return $this->renderPager();
            case '{sorter}':
                return $this->renderSorter();
            default:
                return false;
        }
    }

    /**
     * Renders the HTML content indicating that the list view has no data.
     * @return string the rendering result
     * @see emptyText
     */
    public function renderEmpty() {
        if ($this->emptyText === false) {
            return '';
        }
	return self::html()->tag('block', [ 'content' => $this->emptyText, 'options' => $this->emptyTextOptions], $this->emptyTextOptions + ['escape' => false]);
    }

    /**
     * Renders the summary text.
     */
    public function renderSummary() {

	$count = $this->dataProvider->count();
	//if ($count <= 0)
	//    return '';


	if ($this->__pagination) {
            $begin = $this->__pagination->currentPage * $this->__pagination->pageSize + 1;
            $end = $begin + $this->__pagination->pageSize - 1;
            if ($begin > $end) {
                $begin = $end;
            }
            if ($end > $count)
        	$end = $count;
            $page = $this->__pagination->currentPage + 1;
            $pageCount = $this->__pagination->pageCount;
            $template = \V::t('Showing <b>{:begin}-{:end}</b> of <b>{:count}</b>.');
	} else {
            $begin = $page = $pageCount = 1;
            $end = $count;
            $template = \V::t('Total <b>{:count}</b> items.');
	}

	if (is_string($this->summary))
	    $template = $this->summary;

        $content = \util\VString::insert($template, array(
                'begin' => $begin,
                'end' => $end,
                'count' => $count,
                'page' => $page,
                'pageCount' => $pageCount,
        ));

        return self::html()->tag('block', array('content' => $content, 'options' => $this->summaryOptions), $this->summaryOptions);
    }

    /**
     * Renders the pager.
     * @return string the rendering result
     */
    public function renderPager() {
	if (!$this->__pagination)
	    return '';
	    
	if ($this->__pagination->pageCount <= 1)
	    return '';

	if ($this->__pager)
	    return $this->__pager->run();
    }

    /**
     * Renders the sorter.
     * @return string the rendering result
     */
    public function renderSorter() {
	if (!$this->__sort)
	    return '';
	if (empty($this->sort->attributes))
	    return '';
	
	if ($this->dataProvider->count() <= 0)
	    return '';


	if ($this->__sorter)
	    return $this->__sorter->run();
    }
}
