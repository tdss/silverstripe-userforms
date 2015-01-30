<?php

/**
 * EditableTextField
 *
 * @package userforms
 */

class EditableTextField extends EditableFormField {

	private static $singular_name = 'Text Field';
	
	private static $plural_name = 'Text Fields';
	
	public function getCMSFields() {
		$this->beforeExtending('updateCMSFields', function($fields) {
			$min = ($this->getSetting('MinLength')) ? $this->getSetting('MinLength') : '';
			$max = ($this->getSetting('MaxLength')) ? $this->getSetting('MaxLength') : '';
			
			$rows = ($this->getSetting('Rows')) ? $this->getSetting('Rows') : '1';
			
			$fields->push(new FieldGroup(
				_t('EditableTextField.TEXTLENGTH', 'Text length'),
				new NumericField($this->getSettingName('MinLength'), "", $min),
				new NumericField($this->getSettingName('MaxLength'), " - ", $max)
			));

			$fields->push(new NumericField('Rows', _t('EditableTextField.NUMBERROWS',
				'Number of rows'), $rows
			));
		});

		return parent::getCMSFields();		
	}

	/**
	 * @return TextareaField|TextField
	 */
	public function getFormField() {
		if($this->getSetting('Rows') && $this->getSetting('Rows') > 1) {
			$taf = new TextareaField($this->Name, $this->Title);
			$taf->setRows($this->getSetting('Rows'));

			return $taf;
		}
		
		return new TextField($this->Name, $this->Title, null, $this->getSetting('MaxLength'));
		
	}
	
	/**
	 * Return the validation information related to this field. This is 
	 * interrupted as a JSON object for validate plugin and used in the PHP. 
	 *
	 * @see http://docs.jquery.com/Plugins/Validation/Methods
	 * @return array
	 */
	public function getValidation() {
		$options = parent::getValidation();
		
		if($this->getSetting('MinLength')) {
			$options['minlength'] = $this->getSetting('MinLength');
		}
			
		if($this->getSetting('MaxLength')) {
			$options['maxlength'] = $this->getSetting('MaxLength');
		}
		
		return $options;
	}
}
