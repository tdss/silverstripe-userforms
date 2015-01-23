<?php

/**
 * @package userforms
 */
class EditableRecordList extends EditableParentFormField {
	
	private static $singular_name = 'Record List';
	
	private static $plural_name = 'Record List';
	
	private static $extensions = array(
		'UserFormFieldEditorExtension'
	);

	/**
	 * @return array
	 */
	public function getVersionedChildrenLabels() {
		return array(
			'UserFormFields' => 'EditableFormField'
		);
	}

	/**
	 * @return FieldList
	 */
	public function getCMSFields() {
		$this->beforeExtending('updateCMSFields', function($fields) {
			$min = ($this->getSetting('MinRecords')) ? $this->getSetting('MinRecords') : '';
			$max = ($this->getSetting('MaxRecords')) ? $this->getSetting('MaxRecords') : '';
			
			/*
			$fields->push(new FieldGroup(
				_t('EditableRecordList.RECORDLIMITS', 'Record Limits'),
				new NumericField('MinRecords', "", $min),
				new NumericField('MaxRecords', " - ", $max)
			));
			*/
		});

		$this->afterExtending('updateCMSFields', function($fields) {
			$fields->removeByName('Default');
			$fields->removeByName('Required');
			$fields->removeByName('CustomErrorMessage');
		});

		return parent::getCMSFields();		
	}

	/**
	 * @return FieldGroup
	 */
	public function getFormField() {
		$fields = FieldGroup::create(
			HeaderField::create($this->Name, $this->Title)
		);

		$fields->addExtraClass('udf_nested');
		$nestedFields = new FieldGroup();
		$nestedFields->addExtraClass('udf_nested_nest');

		foreach($this->UserFormFields() as $editableField) {
			$field = $editableField->getFormField();
			$field->setName($this->Name .'[Records][1]['. $editableField->Name .']');

			$nestedFields->push($field);
		}

		$fields->push($nestedFields);

		$fields->push(LiteralField::create($this->Name . '[Add]', sprintf(
			"<a href='#' class='udf_add_record'>%s</a>", _t('EditableRecordList.ADD', 'Add')
		)));

		$fields->setAttribute('data-max-add', $this->getSetting('MaxRecords'));

		return $fields;
	}

	/**
	 * Go through all the nested form fields and retrieve the information for
	 * the additional cells.
	 */
	public function getValueFromData($data, $submissionCells) {
		$processor = UserFormProcessor::create();

		if(isset($data[$this->Name]) && isset($data[$this->Name]['Records'])) {
			foreach($data[$this->Name]['Records'] as $id => $fields) {
				foreach($fields as $field => $value) {
					if (preg_match("/(\d+)$/", $field, $matches)) {
						$editable = EditableFormField::get()->byId($matches[1]);

						if($editable && $editable->canView()) {
							$processor->processCell($fields, $editable, $submissionCells);
						}
					}
				}
			}
		}

		return null;
	}
}