<?php

/**
 * @package userforms
 */
class UserFormFieldEditorExtension extends DataExtension {

	/**
	 * @var string Required Identifier
	 *
	 * @config
	 */
	private static $required_identifier = null;

	/**
	 * @var array
	 */
	private static $has_many = array(
		"UserFormFields" => "EditableFormField"
	);


	/**
	 * @return FieldList
	 */
	public function updateCMSFields(FieldList $fields) {
		$fields->findOrMakeTab(
			'Root.Form', 
			_t('UserDefinedForm.FORM', 'Form')
		);

		$fields->findOrMakeTab(
			'Root.Options', 
			_t('UserDefinedForm.CONFIGURATION', 'Configuration')
		);

		$form = new GridField(
			"UserFormFields",
			_t('UserDefinedForm.FIELDS', 'Fields'),
			$this->owner->UserFormFields()
		);

		$config = new GridFieldConfig();
		$config->addComponent(new GridFieldButtonRow('before'));
		$config->addComponent(new GridFieldToolbarHeader());
		$config->addComponent(new GridFieldAddNewMultiClass());
		$config->addComponent(new GridFieldAddExistingSearchButton());
		$config->addComponent(new GridFieldDataColumns());
		$config->addComponent(new GridFieldEditButton());
		$config->addComponent(new GridState_Component());
		$config->addComponent(new GridFieldDeleteAction());
		$config->addComponent(new GridFieldDetailForm());
		$config->addComponent(new GridFieldOrderableRows('Sort'));
		$config->addComponent((new GridFieldEditableColumns())->setDisplayFields(array(
			'Title' => function($record, $column, $grid) {
        		return new TextField($column);
    		}
		)));

		$form->setConfig($config);

		$fields->addFieldToTab("Root.Form", $form);
		
		return $fields;
	}

	/**
	 * Custom options for the form. You can extend the built in options by 
	 * using {@link updateFormOptions()}
	 *
	 * @return FieldList
	 */
	public function getFormOptions() {
		$submit = ($this->SubmitButtonText) ? $this->SubmitButtonText : _t('UserDefinedForm.SUBMITBUTTON', 'Submit');
		$clear = ($this->ClearButtonText) ? $this->ClearButtonText : _t('UserDefinedForm.CLEARBUTTON', 'Clear');
		
		$options = new FieldList(
			new TextField("SubmitButtonText", _t('UserDefinedForm.TEXTONSUBMIT', 'Text on submit button:'), $submit),
			new TextField("ClearButtonText", _t('UserDefinedForm.TEXTONCLEAR', 'Text on clear button:'), $clear),
			new CheckboxField("ShowClearButton", _t('UserDefinedForm.SHOWCLEARFORM', 'Show Clear Form Button'), $this->ShowClearButton),
			new CheckboxField("EnableLiveValidation", _t('UserDefinedForm.ENABLELIVEVALIDATION', 'Enable live validation')),
			new CheckboxField("HideFieldLabels", _t('UserDefinedForm.HIDEFIELDLABELS', 'Hide field labels'))
		);
		
		$this->extend('updateFormOptions', $options);
		
		return $options;
	}

	/**
	 * When publishing this page, ensure that relations are published along with
	 * the original record.
	 *
	 * @return void
	 */
	public function onAfterPublish() {
		$parentID = $this->owner->ID;
		
		// remove fields on the live table which could have been orphaned.
		$live = Versioned::get_by_stage("EditableFormField", "Live", array(
			"ParentID" => $parentID
		));

		if($live) {
			foreach($live as $field) {
				$field->doDeleteFromStage('Live');
			}
		}

		if($fields = $this->owner->Fields()) {
			foreach($fields as $field) {
				$field->doPublish('Stage', 'Live');
			}
		}
	}


	/**
	 * When unpublishing the record it has to remove all the fields from the 
	 * live database table.
	 *
	 * @return void
	 */
	public function onAfterUnpublish() {
		if($this->owner->Fields()) {
			foreach($this->owner->Fields() as $field) {
				$field->doDeleteFromStage('Live');
			}
		}
	}

	/**
	 * @param DataObject $record
	 * @param boolean $doWrite
	 *
	 * @return DataObject
	 */
	public function onAfterDuplicate($record, $doWrite = true) {
		
		// the form fields
		if($record->Fields()) {
			foreach($record->Fields() as $field) {
				$newField = $field->duplicate();
				$newField->ParentID = $this->owner->ID;
				$newField->ParentClass = $this->owner->ClassName;
				$newField->write();

				$this->afterDuplicateField($page, $field, $newField);
			}
		}
		
		// the emails
		if($this->EmailRecipients()) {
			foreach($this->EmailRecipients() as $email) {
				$newEmail = $email->duplicate();
				$newEmail->FormID = $page->ID;
				$newEmail->write();
			}
		}
		
		// Rewrite CustomRules
		if($page->Fields()) {
			foreach($page->Fields() as $field) {
				// Rewrite name to make the CustomRules-rewrite below work.
				$field->Name = $field->ClassName . $field->ID;
				$rules = unserialize($field->CustomRules);

				if (count($rules) && isset($rules[0]['ConditionField'])) {
					$from = $rules[0]['ConditionField'];

					if (array_key_exists($from, $this->fieldsFromTo)) {
						$rules[0]['ConditionField'] = $this->fieldsFromTo[$from];
						$field->CustomRules = serialize($rules);
					}
				}

				$field->Write();
			}
		}

		return $page;
	}


	/**
	 * Return if this form has been modified on the stage site and not 
	 * published. This is used on the workflow module and for a couple 
	 * highlighting things.
	 *
	 * @return boolean
	 */
	public function updateIsModifiedOnStage(&$modified) {
		// if the parent record is modified then we should be modified.
		if($modified) {
			return $modified;
		}

		if($this->owner->Fields()) {
			foreach($this->owner->Fields() as $field) {
				if($field->getIsModifiedOnStage()) {
					$modified = true;
				
					break;
				}
			}
		}
	}

	/**
	 * Allow overriding the EmailRecipients on a {@link DataExtension}
	 * so you can customise who receives an email.
	 * Converts the RelationList to an ArrayList so that manipulation
	 * of the original source data isn't possible.
	 *
	 * @return ArrayList
	 */
	public function FilteredEmailRecipients($data = null, $form = null) {
		$recipients = new ArrayList($this->getComponents('EmailRecipients')->toArray());
		$this->extend('updateFilteredEmailRecipients', $recipients, $data, $form);

		return $recipients;
	}
}