<?php

/**
 * EditableDateField
 *
 * Allows a user to add a date field.
 *
 * @package userforms
 */

class EditableDateField extends EditableFormField {
	
	private static $singular_name = 'Date Field';
	
	private static $plural_name = 'Date Fields';
	
	public function getCMSFields() {
		$this->beforeExtending('updateCMSFields', function($fields) {
			$default = ($this->getSetting('DefaultToToday')) ? $this->getSetting('DefaultToToday') : false;
			$label = _t('EditableFormField.DEFAULTTOTODAY', 'Default to Today?');

			$fields->push(new CheckboxField("DefaultToToday", $label, $default));
		});

		return parent::getCMSFields();
	}
	
	/**
	 * Return the form field
	 *
	 */
	public function getFormField() {
		$defaultValue = ($this->getSetting('DefaultToToday')) ? date('Y-m-d') : $this->Default;
		
		$field = new DateField( $this->Name, $this->Title, $defaultValue);
		$field->setConfig('showcalendar', true);

		return $field;
	}
	
	/**
	 * Return the validation information related to this field. This is 
	 * interrupted as a JSON object for validate plugin and used in the 
	 * PHP. 
	 *
	 * @see http://docs.jquery.com/Plugins/Validation/Methods
	 * @return Array
	 */
	public function getValidation() {
		return array_merge(parent::getValidation(), array(
			'date' => true
		));
	}
}
