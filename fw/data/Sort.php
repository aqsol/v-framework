<?php

namespace data;

/**
 * Sort represents information relevant to sorting.
 *
 * When data needs to be sorted according to one or several attributes,
 * we can use Sort to represent the sorting information and generate
 * appropriate hyperlinks that can lead to sort actions.
 *
 * A typical usage example is as follows,

    function actionIndex() {
	$sort = new Sort([
	    'attributes' => [
		'age', // default sorting
		[ 'name' => Sort::SORT_ASC ], // ascending
		[ 'size' => Sort::SORT_DESC ], //descending
	    ],
	    'sortParam' => 'sort',
	    'separator' => ',',
	]);

	$models = Article::find(['status' => 1])->sort($sort->sort);

	return $this->render('index', [
	    'models' => $models,
	    'sort' => $sort,
	]);
    }

 * ```
 *
 * View:
 *
 * ```php
 * // display links leading to sort actions
 * echo $sort->link('name') . ' | ' . $sort->link('age');
 *
 * foreach ($models as $model) {
 *     // display $model here
 * }
 * ```
 *
 * In the above, we declare two [[attributes]] that support sorting: name and age.
 * We pass the sort information to the Article query so that the query results are
 * sorted by the orders specified by the Sort object. In the view, we show two hyperlinks
 * that can lead to pages with the data sorted by the corresponding attributes.
 *
 */
class Sort extends \core\VObject {

	const SORT_ASC = 1;
	const SORT_DESC = -1;


	public function __construct($config = array()) {
	    $defaults = array(
		'attributes' => array(),
		'sortParam' => 'sort',
		'separator' => ',',
		'sort' => array(),
	    );

	    $config += $defaults;
	    parent::__construct($config);
	}

	// Normalizes the [[attributes]] property.
	public function _init() {
	    // Normalizes the attributes property.
    	    $_attrs = [];
    	    foreach ($this->attributes as $a) {
    		if (is_array($a)) {
    		    //SC: 2018-08-27: change each with foreach/break
    		    foreach($a as $key => $val) { break; }
    		    //list($key, $val) = each($a);
    		    $_attrs[$key] = $val;
    		} else {
    		    $_attrs[$a] = null;
    		}
    	    }
    	    $this->attributes = $_attrs;


	    //get sort order from request
            $params = \V::app()->request->query;
            
            if (isset($params[$this->sortParam])) {
                $_attrs = explode($this->separator, $params[$this->sortParam]);
                foreach ($_attrs as $a) {
                    $desc = false;
                    if (strncmp($a, '-', 1) === 0) {
                        $desc = true;
                        $a = substr($a, 1);
                    }

                    if (array_key_exists($a, $this->attributes)) {
                        $this->sort[$a] = $desc ? self::SORT_DESC : self::SORT_ASC;
                    }
                }
            }

	    //add also non-null values from attributes
	    foreach($this->attributes as $a => $s) {
		if ($s !== null && empty($this->sort[$a]))
		    $this->sort[$a] = $s;
	    }
	    //$this->sort = \util\Set::merge($this->attributes, $this->sort);
	}



	/**
	 * Creates a URL for sorting the data by the specified attribute.
	 * This method will consider the current sorting status given by [[attributeOrders]].
	 * For example, if the current page already sorts the data by the specified attribute in ascending order,
	 * then the URL created will lead to a page that sorts the data by the specified attribute in descending order.
	 * @param string $attribute the attribute name
	 * @param boolean $absolute whether to create an absolute URL. Defaults to `false`.
	 * @return string the URL for sorting. False if the attribute is invalid.
	 * @throws InvalidConfigException if the attribute is unknown
	 * @see attributeOrders
	 * @see params
	 */
	public function createUrl($attribute, $absolute = false) {
    	    $params = \V::app()->request->query;
    	    $params[$this->sortParam] = $this->createSortParam($attribute);
    	    if ($absolute) {
        	return \V::app()->createAbsoluteUrl('', $params);
    	    } else {
        	return \V::app()->createUrl('', $params);
    	    }
	}


	/**
	 * Creates the sort variable for the specified attribute.
	 * The newly created sort variable can be used to create a URL that will lead to
	 * sorting by the specified attribute.
	 * @param string $attribute the attribute name
	 * @return string the value of the sort variable
	 * @throws InvalidConfigException if the specified attribute is not defined in [[attributes]]
	 */
	public function createSortParam($attribute) {
    	    if (!array_key_exists($attribute, $this->attributes)) {
        	throw new InvalidConfigException("Unknown attribute: $attribute");
    	    }
    	    
	    //operate on a copy of sorts
    	    $_sort_copy = $this->sort;
    	    switch(@$_sort_copy[$attribute]) {
    		case self::SORT_ASC:
    		    $new_dir = self::SORT_DESC;
    		    break;
    		case self::SORT_DESC:
    		    $new_dir = self::SORT_ASC;
    		    break;
    		default:
    		    $new_dir = self::SORT_ASC;
    		    break;
    	    }

    	    $_sort_copy[$attribute] = $new_dir;
    	    
    	    
    	    $sorts = [];
    	    foreach ($_sort_copy  as $attribute => $direction) {
    		if ($direction === null)
    		    continue;
        	$sorts[] = $direction === self::SORT_DESC ? '-' . $attribute : $attribute;
    	    }

    	    return implode($this->separator, $sorts);
	}

}
