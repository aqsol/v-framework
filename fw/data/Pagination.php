<?php

namespace data;

/**
 * Pagination represents information relevant to pagination of data items.
 *
 * When data needs to be rendered in multiple pages, Pagination can be used to
 * represent information such as [[totalCount|total item count]], [[pageSize|page size]],
 * [[page|current page]], etc. These information can be passed to [[\yii\widgets\LinkPager|pagers]]
 * to render pagination buttons or links.
 *
 * The following example shows how to create a pagination object and feed it
 * to a pager.
 *
    -- Controller action:
    function actionIndex() {
	$query = Article::find(array('name' => 'John'));
	$pages = new Pagination(['totalCount' => $query->count()]);
	$models = $query->offset($pages->offset)->limit($pages->limit);

	return $this->render('index', [
	    'models' => $models,
	    'pages' => $pages,
	]);
    }

    -- View:
    foreach ($models as $model) {
	// display $model here
    }

    // display pagination
    $pager = new LinkPager(array('pagination' => $pages));
    echo $pager->run();


    options:
	pageSize; default 10
	pageParam; default 'page'
	totalCount:
	currentPage
 *
 * @property integer $limit The limit of the data. This may be used to set the LIMIT value for a SQL statement
 * for fetching the current page of data. Note that if the page size is infinite, a value -1 will be returned.
 * This property is read-only.
 * @property integer $offset The offset of the data. This may be used to set the OFFSET value for a SQL
 * statement for fetching the current page of data. This property is read-only.
 * @property integer $page The zero-based current page number.
 * @property integer $pageCount Number of pages. This property is read-only.
 * @property integer $pageSize The number of items per page. If it is less than 1, it means the page size is
 * infinite, and thus a single page contains all items.
 */
class Pagination extends \core\VObject {

	public function __construct($config = array()) {
	    $defaults = array(
		'pageSize' => 10,
		'pageParam' => 'page',
		'totalCount' => 0,
		'currentPage' => 0,
	    );

	    $config += $defaults;
	    parent::__construct($config);
	}

	private $_page;

	public function _init() {
	    //get currentPage from variables
	    $this->_page = @\V::app()->request->query[$this->pageParam];
	    if(!empty($this->_page)) {
		$this->currentPage = (int)$this->_page - 1;
	    }
	    parent::_init();
	}

	public function &__get($key) {
	    //special case for formula values
	    switch ($key) {
		case 'pageCount':
		    if ($this->_config['pageSize'] < 1) {
			$this->_config['pageCount'] = $this->_config['totalCount'] > 0 ? 1 : 0;
		    } else {
        		$_totalCount = $this->_config['totalCount'] < 0 ? 0 : (int) $this->_config['totalCount'];
        		$this->_config['pageCount'] = (int) (($_totalCount + $this->_config['pageSize'] - 1) / $this->_config['pageSize']);
		    }
		    return $this->_config['pageCount'];
		case 'offset':
		case 'skip':
		    $this->_config['offset'] = $this->_config['currentPage']*$this->_config['pageSize'];
		    return $this->_config['offset'];
		case 'limit':
		    return $this->_config['pageSize'];
		case 'currentPage':
		default:
		    return parent::__get($key);
	    }
	}

	public function __set($key, $val = null) {
	    switch ($key) {
		case 'pageCount':
		case 'offset':
		case 'skip':
		case 'limit':
		    return null;
		case 'currentPage':
		default:
		    return parent::__set($key, $val);
	    }
	}

	public function createUrl($page, $absolute = false) {
    	    $page = (int) $page;

	    //get current query params
	    $params = \V::app()->request->query;
	    
    	    if ($page > 0)
        	$params[$this->pageParam] = $page + 1;
    	    else
    		unset($params[$this->pageParam]);

    	    if ($absolute) {
        	return \V::app()->createAbsoluteUrl('', $params);
    	    } else {
        	return \V::app()->createUrl('', $params);
    	    }
	}



}
