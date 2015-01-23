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
		$grid = $this->getFieldEditorGrid();

		if($fields->fieldByName('Root')) {
			$fields->findOrMakeTab(
				'Root.Form', 
				_t('UserDefinedForm.FORM', 'Form')
			);

			$fields->addFieldToTab("Root.Form", $grid);

			return $fields;
		} else {
			$fields->push($grid);
		}
	}

	/**
	 * @return GridField
	 */
	public function getFieldEditorGrid() {
		$grid = new GridField(
			"UserFormFields",
			_t('UserDefinedForm.FIELDS', 'Fields'),
			$this->owner->UserFormFields()
		);

		$config = new GridFieldConfig();
		$config->addComponent(new GridFieldButtonRow('before'));
		$config->addComponent(new GridFieldToolbarHeader());
		$config->addComponent(new GridFieldAddNewInlineButton());
		$config->addComponent(new GridFieldEditButton());
		$config->addComponent(new GridState_Component());
		$config->addComponent(new GridFieldDeleteAction());
		$config->addComponent(new GridFieldOrderableRows('Sort'));
		// $config->addComponent(new GridFieldExpandableForm());
		$config->addComponent(new GridFieldDetailForm());
		$config->addComponent((new GridFieldEditableColumns())->setDisplayFields(array(
			'ClassName' => function($record, $column, $grid) {
				$classes = new DropdownField($column, '', $this->getEditableFormClasses());
				$classes->addExtraClass('classselector');

				return $classes;
			},
			'Title' => function($record, $column, $grid) {
        		return TextField::create($column, '	')->setAttribute('placeholder', _t('UserDefinedForm.TITLE', 'Title'));
		},
		'Sort' => function($record, $column, $grid) {
			return HiddenField::create($column, '', $record->Sort);
    		}
		)));

		$grid->setConfig($config);

		return $grid;
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
	 * @return array
	 */
	public function getEditableFormClasses() {
		$classes = ClassInfo::getValidSubClasses('EditableFormField');
		$result = array();

		foreach($classes as $class => $title) {
			if($class == "EditableFormField") {
				continue;
			}

			if(!is_string($class)) {
				$class = $title;
				$title = singleton($class)->i18n_singular_name();
			}

			if(!singleton($class)->canCreate()) {
				continue;
			}

			$result[$class] = $title;
		}

		return $result;
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

		foreach($this->owner->UserFormFields() as $field) {
			$field->doPublish('Stage', 'Live');
		}
	}


	/**
	 * When unpublishing the record it has to remove all the fields from the 
	 * live database table.
	 *
	 * @return void
	 */
	public function onAfterUnpublish() {
		foreach($this->owner->UserFormFields() as $field) {
			$field->doDeleteFromStage('Live');
		}
	}

	/**
	 * @param DataObject $record
	 * @param boolean $doWrite
	 *
	 * @return DataObject
	 */
	public function onAfterDuplicate($record, $doWrite = true) {
		foreach($record->UserFormFields() as $field) {
			$newField = $field->duplicate();
			$newField->ParentID = $this->owner->ID;
			$newField->ParentClass = $this->owner->ClassName;
			$newField->write();
		}
		
		foreach($this->UserFormActions() as $action) {
			$newAction = $action->duplicate();
			$newAction->ParentID = $page->ID;
			$newAction->ParentClass = $page->ClassName;
			$newAction->write();
		}
		
		// Rewrite CustomRules
		foreach($page->UserFormFields() as $field) {
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

			$field->write();
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
}