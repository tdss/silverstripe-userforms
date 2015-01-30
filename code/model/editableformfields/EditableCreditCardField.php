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
	
	/**
	 * @return CreditCardField
	 */
	public function getFormField() {
		return CreditCardField::create($this->Name, $this->Title);
	}
}