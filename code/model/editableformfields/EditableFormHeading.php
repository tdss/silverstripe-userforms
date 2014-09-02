<?php

/**
 * Allows an editor to insert a generic heading into a field.
 *
 * @package userforms
 */

class EditableFormHeading extends EditableFormField {

	private static $singular_name = 'Heading';
	
	private static $plural_name = 'Headings';
	
	public function getCMSFields() {
		$this->beforeExtending('updateCMSFields', function($fields) {
			$levels = array(
				'1' => '1',
				'2' => '2',
				'3' => '3',
				'4' => '4',
				'5' => '5',
				'6' => '6'
			);
		
			$level = ($this->getSetting('Level')) ? $this->getSetting('Level') : 3;
			$label = _t('EditableFormHeading.LEVEL', 'Select Heading Level');

			$fields->push(new DropdownField(
				"Level", $label, $levels, $level
			));

			$fields->push(
				new CheckboxField(
					$this->getSettingName('HideFromReports'),
					_t('EditableLiteralField.HIDEFROMREPORT', 'Hide from reports?'), 
					$this->getSetting('HideFromReports')
				)
			);
		});

		return parent::getCMSFields();
	}

	public function getFormField() {
		$labelField = new HeaderField($this->Name, $this->Title, $this->getSetting('Level'));
		$labelField->addExtraClass('FormHeading');
		
		return $labelField;
	}
	
	public function getShowInReports() {
		return (!$this->getSetting('HideFromReports'));
	}
}
