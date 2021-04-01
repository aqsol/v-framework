<?php

namespace widget;
/**
 * The ListView widget is used to display data from data
 * provider. Each data model is rendered using the view
 * specified.
 *
 * For more details and usage information on ListView, see the [guide article on data widgets](guide:output-data-widgets).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
    /**
     * @var Closure an anonymous function that is called once BEFORE rendering each data model.
     * It should have the following signature:
     *
     * ```php
     * function ($model, $key, $index, $widget)
     * ```
     *
     * - `$model`: the current data model being rendered
     * - `$key`: the key value associated with the current data model
     * - `$index`: the zero-based index of the data model in the model array returned by [[dataProvider]]
     * - `$widget`: the ListView object
     *
     * The return result of the function will be rendered directly.
     * Note: If the function returns `null`, nothing will be rendered before the item.
     * @see renderBeforeItem
     * @since 2.0.11
     */
    // public $beforeItem;
    /**
     * @var Closure an anonymous function that is called once AFTER rendering each data model.
     *
     * It should have the same signature as [[beforeItem]].
     *
     * The return result of the function will be rendered directly.
     * Note: If the function returns `null`, nothing will be rendered after the item.
     * @see renderAfterItem
     * @since 2.0.11
     */
    // public $afterItem;

    /**
     * @var array|Closure the HTML attributes for the container of the rendering result of each data model.
     * This can be either an array specifying the common HTML attributes for rendering each data item,
     * or an anonymous function that returns an array of the HTML attributes. The anonymous function will be
     * called once for every data model returned by [[dataProvider]].
     * The "tag" element specifies the tag name of the container element and defaults to "div".
     * If "tag" is false, it means no container element will be rendered.
     *
     * If this property is specified as an anonymous function, it should have the following signature:
     *
     * ```php
     * function ($model, $key, $index, $widget)
     * ```
     *
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    //public $itemOptions = [];

    /**
     * @var string|callable the name of the view for rendering each data item, or a callback (e.g. an anonymous function)
     * for rendering each data item. If it specifies a view name, the following variables will
     * be available in the view:
     *
     * - `$model`: mixed, the data model
     * - `$key`: mixed, the key value associated with the data item
     * - `$index`: integer, the zero-based index of the data item in the items array returned by [[dataProvider]].
     * - `$widget`: ListView, this widget instance
     *
     * Note that the view name is resolved into the view file by the current context of the [[view]] object.
     *
     * If this property is specified as a callback, it should have the following signature:
     *
     * ```php
     * function ($model, $key, $index, $widget)
     * ```
     */
    //public $itemView;


class ListView extends BaseListView {
    public function __construct($config = []) {
	$defaults = [
	    'wrapOptions' => ['class' => 'media-list media-list-hover'],
	    'beforeItem' => null,
	    'afterItem' => null,
	    'itemOptions' => [ 'tag' => 'div' ],
	    'itemView' => '',
	];

	$config = \util\Set::merge($defaults, $config);
	parent::__construct($config);
	
    }

    /**
     * @var array additional parameters to be passed to [[itemView]] when it is being rendered.
     * This property is used only when [[itemView]] is a string representing a view name.
     */
    //public $viewParams = [];
    /**
     * @var string the HTML code to be displayed between any two consecutive items.
     */
    //public $separator = "\n";
    /**
     * @var array the HTML attributes for the container tag of the list view.
     * The "tag" element specifies the tag name of the container element and defaults to "div".
     * @see \yii\helpers\Html::renderTagAttributes() for details on how attributes are being rendered.
     */
    //public $options = ['class' => 'list-view'];



    /**
     * Renders all data models.
     * @return string the rendering result
     */
    public function renderItems() {
        $rows = [];
        foreach ($this->dataProvider as $model) {
            $rows[] = $this->renderBeforeItem($model);
            $rows[] = $this->renderItem($model);
	    $rows[] = $this->renderAfterItem($model);
        }

        return implode("\n", $rows);
    }

    /**
     * Calls [[beforeItem]] closure, returns execution result.
     * If [[beforeItem]] is not a closure, `null` will be returned.
     *
     * @param mixed $model the data model to be rendered
     * @param mixed $key the key value associated with the data model
     * @param int $index the zero-based index of the data model in the model array returned by [[dataProvider]].
     * @return string|null [[beforeItem]] call result or `null` when [[beforeItem]] is not a closure
     * @see beforeItem
     * @since 2.0.11
     */
    protected function renderBeforeItem($model) {
        if (is_callable($this->beforeItem))
            return call_user_func($this->beforeItem, $model, $this);

        return null;
    }

    /**
     * Calls [[afterItem]] closure, returns execution result.
     * If [[afterItem]] is not a closure, `null` will be returned.
     *
     * @param mixed $model the data model to be rendered
     * @param mixed $key the key value associated with the data model
     * @param int $index the zero-based index of the data model in the model array returned by [[dataProvider]].
     * @return string|null [[afterItem]] call result or `null` when [[afterItem]] is not a closure
     * @see afterItem
     * @since 2.0.11
     */
    protected function renderAfterItem($model) {
        if (is_callable($this->afterItem))
            return call_user_func($this->afterItem, $model, $this);

        return null;
    }

    /**
     * Renders a single data model.
     * @param mixed $model the data model to be rendered
     * @param mixed $key the key value associated with the data model
     * @param int $index the zero-based index of the data model in the model array returned by [[dataProvider]].
     * @return string the rendering result
     */
    public function renderItem($model) {
	//prepare options
        if (is_callable($this->itemOptions))
            $options = call_user_func($this->itemOptions, $model, $this);
        else
            $options = $this->itemOptions;
        
	switch(true) {
	    //a callable, executed as such
	    //SC: 2019/02/03: if callable return false or empty, do not render this item
	    case is_callable($this->itemView):
		$content = call_user_func($this->itemView, $model, $this);
		if (empty($content))
		    return null;
		break;
	    case empty($this->itemView):
		$content = '<pre>' . print_r($model, true) . '</pre>';
		break;
	}
	$tag = $options['tag'];
	unset($options['tag']);
//        return self::html()->tag('block', [ 'content' => $content, 'options' => $options], $options + ['escape' => false]);
        return self::html()->tag('tag', [ 'name' => $tag, 'content' => $content, 'options' => $options], $options + ['escape' => false]);


    }
}
