<?php

/**
 * Base class for multiple option fields such as {@link EditableDropdownField} 
 * and radio sets. 
 * 
 * Implemented as a class but should be viewed as abstract, you should 
 * instantiate a subclass such as {@link EditableDropdownField}
 *
 * @see EditableCheckboxGroupField
 * @see EditableDropdownField
 *
 * @package userforms
 */

class EditableMultipleOptionField extends EditableFormField {
	
	private static $hide_from_create = true;
	
	private static $has_many = array(
		"Options" => "EditableOption"
	);

	/**
	 * @return array
	 */
	public function getVersionedChildrenLabels() {
		return array_merge(array(
			'Options' => 'EditableOption'
		), parent::getVersionedChildrenLabels());
	}

	/**
	 * Return the form field for this object in the front end form view
	 *
	 * @return FormField
	 */
	public function getFormField() {
		return user_error('Please implement getFormField() on '. $this->class, E_USER_ERROR);
	}
}
