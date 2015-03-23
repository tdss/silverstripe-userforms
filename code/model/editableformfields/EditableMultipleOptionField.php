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

	public function getCMSFields() {
		$this->beforeUpdateCMSFields(function(FieldList $fields) {
			$options = new GridField('Options', _t('EditableMultipleOptionField.OPTIONS', 'Options'), $this->Options());
			$fields->push($options);

			$config = new GridFieldConfig();
			$config->addComponent(new GridFieldButtonRow('before'));
			$config->addComponent(new GridFieldAddNewInlineButton());
			$config->addComponent(new GridState_Component());
			$config->addComponent(new GridFieldDeleteAction());
			$config->addComponent(new GridFieldOrderableRows('Sort'));
			$config->addComponent((new GridFieldEditableColumns())->setDisplayFields(array(
				'Title' => function($record, $column, $grid) {
					return TextField::create($column, "&nbsp;")->
						setTitle('Hi')
						->setAttribute('placeholder', _t('UserDefinedForm.TITLE', 'Title'));
				}
			)));

			$options->setConfig($config);
		});

		return parent::getCMSFields();
	}
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
