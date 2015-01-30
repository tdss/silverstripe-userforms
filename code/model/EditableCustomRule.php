<?php

/**
 * @package userforms
 */
class EditableCustomRule extends DataObject {

	private static $condition_options = array(
		'IsBlank',
		'IsNotBlank',
		'HasValue',
		'ValueNot',
		'ValueLessThan',
		'ValueLessThanEqual',
		'ValueGreaterThan',
		'ValueGreaterThanEqual'
	);

	private static $db = array(
		'Display' => 'Enum("Show, Hide")',
		'ConditionOption' => 'Varchar',
		'FieldValue' => 'Varchar',

	);

	private static $has_one = array(
		'Parent' => 'EditableFormField',
		'ConditionField' => 'EditableFormField'
	);

	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);
}