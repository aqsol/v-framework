<?php

namespace widget;



/**
 * LinkPager displays a list of hyperlinks that lead to different pages of target.
 *
 * LinkPager works with a [[Pagination]] object which specifies the totally number
 * of pages and the current page number.
 *
 * Note that LinkPager only generates the necessary HTML markups. In order for it
 * to look like a real pager, you should provide some CSS styles for it.
 * With the default configuration, LinkPager should look good using Twitter Bootstrap CSS framework.
 */
class LinkPager extends Widget {
    /**
     * @var Pagination the pagination object that this pager is associated with.
     * You must set this property in order to make LinkPager work.
     */
    //public $pagination;
    /**
     * @var array HTML attributes for the pager container tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    //public $options = ['class' => 'pagination'];
    /**
     * @var array HTML attributes for the link in a pager container tag.
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    //public $linkOptions = [];
    /**
     * @var string the CSS class for the "first" page button.
     */
    //public $firstPageCssClass = 'first';
    /**
     * @var string the CSS class for the "last" page button.
     */
    //public $lastPageCssClass = 'last';
    /**
     * @var string the CSS class for the "previous" page button.
     */
    //public $prevPageCssClass = 'prev';
    /**
     * @var string the CSS class for the "next" page button.
     */
    //public $nextPageCssClass = 'next';
    /**
     * @var string the CSS class for the active (currently selected) page button.
     */
    //public $activePageCssClass = 'active';
    /**
     * @var string the CSS class for the disabled page buttons.
     */
    //public $disabledPageCssClass = 'disabled';
    /**
     * @var integer maximum number of page buttons that can be displayed. Defaults to 10.
     */
    //public $maxButtonCount = 10;
    /**
     * @var string|boolean the label for the "next" page button. Note that this will NOT be HTML-encoded.
     * If this property is false, the "next" page button will not be displayed.
     */
    //public $nextPageLabel = '&raquo;';
    /**
     * @var string|boolean the text label for the previous page button. Note that this will NOT be HTML-encoded.
     * If this property is false, the "previous" page button will not be displayed.
     */
    //public $prevPageLabel = '&laquo;';
    /**
     * @var string|boolean the text label for the "first" page button. Note that this will NOT be HTML-encoded.
     * If it's specified as true, page number will be used as label.
     * Default is false that means the "first" page button will not be displayed.
     */
    //public $firstPageLabel = false;
    /**
     * @var string|boolean the text label for the "last" page button. Note that this will NOT be HTML-encoded.
     * If it's specified as true, page number will be used as label.
     * Default is false that means the "last" page button will not be displayed.
     */
    //public $lastPageLabel = false;

    /**
     * @var boolean Hide widget when only one page exist.
     */
    //public $hideOnSinglePage = true;

    public function __construct(array $config = array()) {
	$defaults = array(
	    'pagination' => null,
	    'wrapOptions' => [ 'class' => 'paging_simple_numbers' ], // for div tag
	    'listOptions' => [ 'class' => 'pagination' ], //for ul tag

	    'itemOptions' => [ 'class' => 'paginate_button page-item' ],
	    'linkOptions' => [ 'class' => 'page-link' ],
	    'firstPageCssClass' => 'first',
	    'lastPageCssClass' => 'last',
	    'prevPageCssClass' => 'prev',
	    'nextPageCssClass' => 'next',
	    'activePageCssClass' => 'active',
	    'disabledPageCssClass' => 'disabled',
	    'maxButtonCount' => 10,
	    'nextPageLabel' => '&raquo;',
	    'prevPageLabel' => '&laquo;',
	    'firstPageLabel' => '&laquo;&laquo;', // or false
	    'lastPageLabel' => '&raquo;&raquo;', //or false
	    'hideOnSinglePage' => true,
	);
	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }

    // Initializes the view.
    public function _init() {
        if ($this->pagination === null) {
            throw new InvalidConfigException('The "pagination" property must be set.');
        }
	parent::_init();
    }


    // Executes the widget.
    public function run() {
        return $this->renderPageButtons();
    }

    // Renders the page buttons.
    protected function renderPageButtons() {
        $pageCount = $this->pagination->pageCount;
        if ($pageCount < 2 && $this->hideOnSinglePage) {
            return '';
        }

        $buttons = [];
        $currentPage = $this->pagination->currentPage;

        // first page
        $firstPageLabel = $this->firstPageLabel === true ? '1' : $this->firstPageLabel;
        if ($firstPageLabel !== false) {
            $buttons[] = $this->renderPageButton($firstPageLabel, 0, $this->firstPageCssClass, $currentPage <= 0, false);
        }

        // prev page
        if ($this->prevPageLabel !== false) {
            if (($page = $currentPage - 1) < 0) {
                $page = 0;
            }
            $buttons[] = $this->renderPageButton($this->prevPageLabel, $page, $this->prevPageCssClass, $currentPage <= 0, false);
        }

        // internal pages
        list($beginPage, $endPage) = $this->getPageRange();
        for ($i = $beginPage; $i <= $endPage; ++$i) {
            $buttons[] = $this->renderPageButton($i + 1, $i, null, false, $i == $currentPage);
        }

        // next page
        if ($this->nextPageLabel !== false) {
            if (($page = $currentPage + 1) >= $pageCount - 1) {
                $page = $pageCount - 1;
            }
            $buttons[] = $this->renderPageButton($this->nextPageLabel, $page, $this->nextPageCssClass, $currentPage >= $pageCount - 1, false);
        }

        // last page
        $lastPageLabel = $this->lastPageLabel === true ? $pageCount : $this->lastPageLabel;
        if ($lastPageLabel !== false) {
            $buttons[] = $this->renderPageButton($lastPageLabel, $pageCount - 1, $this->lastPageCssClass, $currentPage >= $pageCount - 1, false);
        }

        $content = self::html()->tag('list', array('content' => implode("\n", $buttons), 'options' => $this->listOptions), $this->listOptions);
        return self::html()->tag('block', array('content' => $content, 'options' => $this->wrapOptions), $this->wrapOptions);
    }

    // Renders a page button.
    // $label the text label for the button
    // $page the page number
    // $class the CSS class for the page button.
    // $disabled whether this page button is disabled
    // $active whether this page button is active
    protected function renderPageButton($label, $page, $class, $disabled, $active) {
	$itemOptions = $this->itemOptions;
	$classes = explode(' ', $itemOptions['class']);
	if (!empty($class))
	    $classes[] = $class;
        if ($active)
    	    $classes[] = $this->activePageCssClass;
        if ($disabled)
            $classes[] = $this->disabledPageCssClass;
            
        $itemOptions['class'] = implode(' ', $classes);
        
	$url = $this->pagination->createUrl($page);
	$content = self::html()->tag('link', array('url' => $url, 'title' => $label, 'options' => $this->linkOptions), $this->linkOptions + [ 'escape' => false ]);
	return self::html()->tag('list-item', array('options' => $itemOptions, 'content' => $content), $itemOptions + [ 'escape' => false ]);
    }

    // generates the begin and end pages that need to be displayed.
    protected function getPageRange() {
        $currentPage = $this->pagination->currentPage;
        $pageCount = $this->pagination->pageCount;

        $beginPage = max(0, $currentPage - (int) ($this->maxButtonCount / 2));
        if (($endPage = $beginPage + $this->maxButtonCount - 1) >= $pageCount) {
            $endPage = $pageCount - 1;
            $beginPage = max(0, $endPage - $this->maxButtonCount + 1);
        }

        return [$beginPage, $endPage];
    }
}
