<?php


require dirname(__FILE__) . '/../core/VObject.php';
require dirname(__FILE__) . '/Html.php';
require dirname(__FILE__) . '/Form.php';
require dirname(__FILE__) . '/Set.php';
require dirname(__FILE__) . '/VString.php';



$form = new \util\Form;


$choices = [
    'optgroup1' => [
	'a' => 'aaaaa',
	'b' => 'bbbbb',
    ],
    'optgroup2' => [
	'c' => 'ccccc',
	'd' => 'ddddd',
    ],
];



echo $form->select('select-key', $choices, []);