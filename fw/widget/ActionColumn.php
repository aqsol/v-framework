<?php

namespace widget;


/**
 * ActionColumn is a column for the [[GridView]] widget that displays buttons for viewing and manipulating the items.
 *
 * To add an ActionColumn to the gridview, add it to the [[GridView::columns|columns]] configuration as follows:
 *
 * ```php
 * 'columns' => [
 *     // ...
 *     [
 *         'class' => ActionColumn::className(),
 *         // you may configure additional properties here
 *     ],
 * ]
 * ```
 */
class ActionColumn extends Column {

    public function __construct(array $config = array()) {
	$defaults = array(
	    'controller' => null,
	    
	    'content' => null,
	    'contentOptions' => array(),
	    
	    'buttons' => array(),
	    'buttonOptions' => array(),

	    'urlCreator' => null,
	    'template' => '{view} {update} {delete}',

	);
	if (!isset($config['grid']))
	    throw new \InvalidArgumentException('column: missing grid in constructor');

	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
    }
    /**
     * @var string the ID of the controller that should handle the actions specified here.
     * If not set, it will use the currently active controller. This property is mainly used by
     * [[urlCreator]] to create URLs for different actions. The value of this property will be prefixed
     * to each action name to form the route of the action.
     */
    //public $controller;
    /**
     * @var string the template used for composing each cell in the action column.
     * Tokens enclosed within curly brackets are treated as controller action IDs (also called *button names*
     * in the context of action column). They will be replaced by the corresponding button rendering callbacks
     * specified in [[buttons]]. For example, the token `{view}` will be replaced by the result of
     * the callback `buttons['view']`. If a callback cannot be found, the token will be replaced with an empty string.
     *
     * As an example, to only have the view, and update button you can add the ActionColumn to your GridView columns as follows:
     *
     * ```
     * ['class' => 'yii\grid\ActionColumn', 'template' => '{view} {update}'],
     * ```
     *
     * @see buttons
     */
    //public $template = '{view} {update} {delete}';
    /**
     * @var array button rendering callbacks. The array keys are the button names (without curly brackets),
     * and the values are the corresponding button rendering callbacks. The callbacks should use the following
     * signature:
     *
     * ```php
     * function ($url, $model, $key) {
     *     // return the button HTML code
     * }
     * ```
     *
     * where `$url` is the URL that the column creates for the button, `$model` is the model object
     * being rendered for the current row, and `$key` is the key of the model in the data provider array.
     *
     * You can add further conditions to the button, for example only display it, when the model is
     * editable (here assuming you have a status field that indicates that):
     *
     * ```php
     * [
     *     'update' => function ($url, $model, $column) {
     *         return $model->status === 'editable' ? Html::a('Update', $url) : '';
     *     },
     * ],
     * ```
     */
    //public $buttons = [];
    /**
     * @var callable a callback that creates a button URL using the specified model information.
     * The signature of the callback should be the same as that of [[createUrl()]].
     * If this property is not set, button URLs will be created using [[createUrl()]].
     */
    //public $urlCreator;


    //html options to be applied to the [[initDefaultButtons()|default buttons]].
    //public $buttonOptions = [];


    public function _init() {
        parent::_init();
        $this->initDefaultButtons();
    }

    //Initializes the default button rendering callbacks.
    protected function initDefaultButtons() {
        if (!isset($this->buttons['view'])) {
            $this->buttons['view'] = function ($url, $model, $column) {
        	$title = \V::t('View');
		$options = array(
                    'aria-label' => $title,
                    'data-pjax' => '0',
		);
		$options = \util\Set::merge($options, $column->buttonOptions);
                return Widget::html()->tag('link', array('title' => '<i class="fa fa-eye"></i>', 'url' => $url, 'options' => $options), $options + ['escape' => false]);
            };
        }
        if (!isset($this->buttons['update'])) {
            $this->buttons['update'] = function ($url, $model, $column) {
        	$title = \V::t('Update');
                $options = array(
                    'aria-label' => $title,
                    'data-pjax' => '0',
                );
		$options = \util\Set::merge($options, $column->buttonOptions);
                return Widget::html()->tag('link', array('title' => '<i class="fa fa-pencil"></i>', 'url' => $url, 'options' => $options), $options + ['escape' => false]);
            };
        }
        if (!isset($this->buttons['delete'])) {
            $this->buttons['delete'] = function ($url, $model, $column) {
        	$title = \V::t('Delete');
                $options = array(
                    'aria-label' => $title,
                    'data-confirm' => \V::t('Are you sure you want to delete this item?'),
                    'data-method' => 'post',
                    'data-id' => (string)$model->key(),
                    //'data-pjax' => '0',
                );
		$options = \util\Set::merge($options, $column->buttonOptions);
                return Widget::html()->tag('link', array('title' => '<i class="fa fa-trash"></i>', 'url' => $url, 'options' => $options), $options + ['escape' => false]);
            };
        }
    }

    /**
     * Creates a URL for the given action and model.
     * This method is called for each button and each row.
     * @param string $action the button name (or action ID)
     * @param \yii\db\ActiveRecord $model the data model
     * @param mixed $key the key associated with the data model
     * @param integer $index the current row index
     * @return string the created URL
     */
    public function createUrl($action, $model) {
        if (is_callable($this->urlCreator)) {
            return call_user_func($this->urlCreator, $action, $model);
        } else {
    	    if (!empty($this->controller))
    		$controller = $this->controller;
    	    else
    		$controller = \V::app()->config('c_controller');
    	
    	    return \V::app()->createUrl($controller . '/' . $action, array('id' => (string) $model->key()));
        }
    }

    protected function renderContentCellContent($model = null) {
        return preg_replace_callback('/\\{([\w\-\/]+)\\}/', function ($matches) use ($model) {
            $name = $matches[1];
            if (isset($this->buttons[$name])) {
                $url = $this->createUrl($name, $model);

                return call_user_func($this->buttons[$name], $url, $model, $this);
            } else {
                return '';
            }
        }, $this->template);
    }
}
