<?php

/**
 * @package userforms
 */
class EditableCreditCardField extends EditableFormField {
	
	private static $singular_name = 'Credit Card Field';
	
	private static $plural_name = 'Credit Card Fields';
	
	public function getSetsOwnError() {
		return true;
	}

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->push(CheckboxField::create(
			$this->getSettingName('CaptureExpiryDate'), 
			_t('EditableCreditCardField.CAPTUREEXPIRYDATE', 'Capture expiry date?'), 
			$this->getSetting('CaptureExpiryDate')
		));

		return $fields;
	}
	
	/**
	 * @return CreditCardField
	 */
	public function getFormField() {
		$cc = CreditCardField::create($this->Name, $this->Title);

		if($this->getSetting('CaptureExpiryDate')) {
			$months = range(1, 12);
			$years = range(date('Y'), date('Y') + 20);

			$month = DropdownField::create($this->Name . '_ExpiryMonth', _t('EditableCreditCardField.EXPIRYMONTH', 'Expiry Month'), $months);
			$year = DropdownField::create($this->Name . '_ExpiryYear', _t('EditableCreditCardField.EXPIRYYEAR', 'Expiry Year'), $years);

			return new FieldGroup($cc, $month, $year);
		}

		return $cc;
	}

	public function getValueFromData($data, $submissionList) {
		$result = '';
		$entries = (isset($data[$this->Name])) ? $data[$this->Name] : false;
		
		if($entries) {
			$result .= $entries;

			if(isset($data[$this->Name . '_ExpiryMonth']) && isset($data[$this->Name .'_ExpiryYear'])) {
				$result .= sprintf(' (%s/%s)', $data[$this->Name . '_ExpiryMonth'], $data[$this->Name .'_ExpiryYear']);
			}
		}

		return $result;
	}
}