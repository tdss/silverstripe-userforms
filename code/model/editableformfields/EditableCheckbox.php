<?php
/**
 * EditableCheckbox
 *
 * A user modifiable checkbox on a UserDefinedForm
 * 
 * @package userforms
 */

class EditableCheckbox extends EditableFormField {
	
	private static $singular_name = 'Checkbox Field';
	
	private static $plural_name = 'Checkboxes';
	
	public function getCMSFields() {
		$this->beforeExtending('updateCMSFields', function($fields) {
			$fields->push(new CheckboxField(
				"CheckedByDefault", 
				_t('EditableFormField.CHECKEDBYDEFAULT', 'Checked by Default?')
			));
		});

		return parent::getCMSFields();
	}
	
	public function getFormField() {
		return new CheckboxField(
			$this->Name, 
			$this->Title, 
			$this->getSetting('CheckedByDefault')
		);
	}
	
	public function getValueFromData($data, $submissionList) {
		$value = (isset($data[$this->Name])) ? $data[$this->Name] : false;
		
		return ($value) ? _t('EditableFormField.YES', 'Yes') : _t('EditableFormField.NO', 'No');
	}
}