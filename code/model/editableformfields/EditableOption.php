<?php

/**
 * Base class for an individual option within {@link EditableMultipleOptionField} 
 * fields such as the dropdowns and checkboxes.
 * 
 * @package userforms
 */

class EditableOption extends DataObject {
	
	private static $default_sort = "Sort";

	private static $db = array(
		"Name" => "Varchar(255)",
		"Title" => "Varchar(255)",
		"Default" => "Boolean",
		"Sort" => "Int"
	);
	
	private static $has_one = array(
		"Parent" => "EditableMultipleOptionField",
	);
	
	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);

    public function getEscapedTitle() {
        return Convert::raw2att($this->Title);
    }
}
