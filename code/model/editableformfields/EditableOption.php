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
	
	private static $summary_fields = array(
		'Title'
	);

	private static $has_one = array(
		"Parent" => "EditableMultipleOptionField"
	);
	
	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);

    public function getEscapedTitle() {
        return Convert::raw2att($this->Title);
    }

    public function onBeforeWrite() {
    	parent::onBeforeWrite();

    	if(!$this->Sort) {
			$parentID = ($this->ParentID) ? $this->ParentID : 0;
			
			$this->Sort = DB::prepared_query(
				"SELECT MAX(\"Sort\") + 1 FROM \"EditableOption\" WHERE \"ParentID\" = ?", 
				array($parentID)
			)->value();
		}
    }
}
