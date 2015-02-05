<?php

/**
 * @package userforms
 */

class EditableFormField extends DataObject {
	
	/**
	 * @var string
	 */
	private static $default_sort = "Sort ASC";

	/**
	 * @var array
	 */
	private static $summary_fields = array(
		'Title'
	);

	/**
	 * @var array
	 */
	private static $db = array(
		"Title" => "Varchar(255)",
		"Default" => "Varchar",
		"Sort" => "Int",
		"Required" => "Boolean",
		"CustomErrorMessage" => "Varchar(255)",
		"CustomRules" => "Text",
		"CustomSettings" => "Text",
		"HideOnLoad" => 'Boolean'
	);

	/**
	 * @var array
	 */
	private static $has_one = array(
		"Parent" => "DataObject",
	);

	/**
	 * @var array
	 */
	private static $has_many = array(
		"CustomRules" => "EditableCustomRule.Parent"
	);
	
	/**
	 * @var array
	 */
	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);
	
	/**
	 * @return FieldList
	 */
	public function getCMSFields() {
		$fields = $this->scaffoldFormFields(array(
			'includeRelations' => false,
			'tabbed' => false,
			'ajaxSafe' => true
		));

		$fields->removeByName('Sort');
		$fields->removeByName('ParentID');
		$fields->removeByName('VersionID');
		$fields->removeByName('CustomRules');
		$fields->removeByName('CustomSettings');
		$fields->removeByName('HideOnLoad');
		$fields->removeByName('CustomRules');

		$fields->insertBefore(new ReadonlyField(
			'Type', 
			_t('EditableFormField.TYPE', 'Type'), 
			$this->config()->get('singular_name')), 
			'Title'
		);

		$grid = new GridField(
			"CustomRules",
			_t('EditableFormField.CUSTOMRULES', 'Custom Rules'),
			$this->CustomRules()
		);

		$config = new GridFieldConfig();
		$config->addComponent(new GridFieldButtonRow('before'));
		$config->addComponent(new GridFieldToolbarHeader());
		$config->addComponent(new GridFieldAddNewInlineButton());
		$config->addComponent(new GridState_Component());
		$config->addComponent(new GridFieldDeleteAction());
		$config->addComponent((new GridFieldEditableColumns())->setDisplayFields(array(
			'Display' => '',
			'ConditionFieldID' => function($record, $column, $grid) {
				return DropdownField::create($column, '', EditableFormField::get()->filter(array(
					'ParentID' => $this->ParentID
				))->exclude(array(
					'ID' => $this->ID
				))->map('ID', 'Title'));
			},
			'ConditionOption' => function($record, $column, $grid) {
				$options = Config::inst()->get('EditableCustomRule', 'condition_options');
				$options = array_combine($options, $options);

				return DropdownField::create($column, '', $options);
			},
			'FieldValue' => function($record, $column, $grid) {
				return TextField::create($column);
			},
			'Parent' => function($record, $column, $grid) {
				return HiddenField::create($column, '', $record->ParentID);
    		}
		)));

		$grid->setConfig($config);
		$count = sprintf(" (%s)", $this->CustomRules()->Count());

		$fields->push(new ToggleCompositeField(
			'CustomRulesSection', 
			_t('EditableFormField.CUSTOMRULES', 'Custom Rules') . $count,
			array(
				new CheckboxField('HideOnLoad'),
				$grid
			)
		));

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	/**
	 * @return FieldList
	 */
	public function getExandableFormFields() {
		return $this->getCMSFields();
	}

	/**
	 * Save custom settings
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();

		$fields = $this->toMap();
		$settings = $this->getSettings();

		foreach($fields as $field => $value) {
			if(preg_match("/^CustomSettings\[((\w)+)\]$/", $field, $matches)) {
				$settings[$matches[1]] = $value;
			}
		}

		$this->setSettings($settings);
	}

	/**
	 * Publishing Versioning support.
	 *
	 * When publishing it needs to handle copying across / publishing each of 
	 * the individual field options
	 * 
	 * @return void
	 */
	public function doPublish($fromStage, $toStage, $createNewVersion = false) {
		foreach($this->getVersionedChildrenLabels() as $label => $class) {
			$live = Versioned::get_by_stage($class, "Live", "\"$class\".\"ParentID\" = $this->ID");

			if($live) {
				foreach($live as $record) {
					$record->delete();
				}
			}

			foreach($this->getComponents($label) as $inst) {
				$inst->publish($fromStage, $toStage, $createNewVersion);
			}
		}

		$this->publish($fromStage, $toStage, $createNewVersion);
	}
	
	/**
	 * @return void
	 */
	public function doDeleteFromStage($stage) {
		foreach($this->getVersionedChildrenLabels() as $label => $class) {
			foreach($this->getComponents($label) as $inst) {
				$inst->deleteFromStage($stage);
			}
		}
		
		$this->deleteFromStage($stage);
	}
	
	/**
	 * @return void
	 */
	public function delete() {
		foreach($this->getVersionedChildrenLabels() as $label => $class) {
			foreach($this->getComponents($label) as $inst) {
				$inst->delete();
			}
		}
		
		parent::delete(); 
	}
	
	/**
	 * @return DataObject
	 */
	public function duplicate($doWrite = true) {
		$clonedNode = parent::duplicate();
		
		foreach($this->getVersionedChildrenLabels() as $label => $class) {
			foreach($this->getComponents($label) as $inst) {
				$newField = $inst->duplicate();
				$newField->ParentID = $clonedNode->ID;
				$newField->write();
			}
		}
		
		return $clonedNode;
	}
	
	/**
	 * Flag indicating that this field will set its own error message via data-msg='' attributes
	 * 
	 * @return bool
	 */
	public function getSetsOwnError() {
		return false;
	}
	
	/**
	 * Can this instance be created?
	 *
	 * @return boolean
	 */
	public function canCreate($member = null) {
		if($this->config()->get('hide_from_create')) {
			return false;
		}

		if(in_array(get_class($this), $this->config()->get('blacklisted_form_fields'))) {
			return false;
		}

		return parent::canCreate($member);
	}
	
	/**
	 * Return whether a user can delete this form field based on whether they 
	 * can edit the page.
	 *
	 * @return bool
	 */
	public function canDelete($member = null) {
		return $this->canEdit($member);
	}
	
	/**
	 * Return whether a user can edit this form field based on whether they can 
	 * edit the page.
	 *
	 * @return bool
	 */
	public function canEdit($member = null) {
		if($this->Parent()) {
			return $this->Parent()->canEdit($member);
		}

		return true;
	}

	/**
	 * @param Member $member
	 *
	 * @return bool
	 */
	public function canView($member = null) {
		return $this->canEdit($member);
	}
	
	/**
	 * To prevent having tables for each fields minor settings we store it as 
	 * a serialized array in the database. 
	 * 
	 * @return Array Return all the Settings
	 */
	public function getSettings() {
		return (!empty($this->CustomSettings)) ? unserialize($this->CustomSettings) : array();
	}
	
	/**
	 * Set the custom settings for this field as we store the minor details in
	 * a serialized array in the database
	 *
	 * @param Array the custom settings
	 */
	public function setSettings($settings = array()) {
		$this->CustomSettings = serialize($settings);
	}
	
	/**
	 * Set a given field setting. Appends the option to the settings or overrides
	 * the existing value
	 *
	 * @param String key 
	 * @param String value
	 */
	public function setSetting($key, $value) {
		$settings = $this->getSettings();
		$settings[$key] = $value;
		
		$this->setSettings($settings);
	}

	/**
	 * Return just one custom setting or empty string if it does
	 * not exist
	 *
	 * @param String Value to use as key
	 * @return String
	 */
	public function getSetting($setting) {
		$settings = $this->getSettings();
		
		if(isset($settings) && count($settings) > 0) {
			if(isset($settings[$setting])) {
				return $settings[$setting];
			}
		}

		return '';
	}

	/**
	 * @return string
	 */
	public function getSettingName($name) {
		return sprintf("CustomSettings[%s]", $name);
	}
	
	/**
	 * Get the path to the icon for this field type, relative to the site root.
	 *
	 * @return string
	 */
	public function getIcon() {
		return USERFORMS_DIR . '/images/' . strtolower($this->class) . '.png';
	}

	/**
	 * @return string
	 */
	public function getName() {
		return sprintf("%s%s", $this->ClassName, $this->ID);
	}

	/**
	 * Return a {@link FormField{} to appear on the front end. Implement on your 
	 * subclass.
	 *
	 * @return FormField
	 */
	public function getFormField() {
		user_error(
			"Please implement a getFormField() on your EditableFormClass ". $this->ClassName, 
			E_USER_ERROR
		);
	}
	
	/**
	 * Return the instance of a {@link SubmittedFormField} class.
	 *
	 * @return SubmittedFormField
	 */
	public function getSubmittedFormField() {
		return Injector::inst()->create('SubmittedFormField');
	}
	
	
	/**
	 * Show this form field (and its related value) in the reports and in
	 * emails.
	 *
	 * @return bool
	 */
	public function getShowInReports() {
		return (!$this->getSetting('HideFromReports'));
	}
 
	/**
	 * Return the validation information related to this field. This is 
	 * interrupted as a JSON object for validate plugin and used in the PHP. 
	 *
	 * @see http://docs.jquery.com/Plugins/Validation/Methods
	 *
	 * @return array
	 */
	public function getValidation() {
		return $this->Required
			? array('required' => true)
			: array();
	}
	
	/**
	 * @return JSON
	 */
	public function getValidationJSON() {
		return Convert::raw2json($this->getValidation());
	}
	
	/**
	 * Return the error message for this field. Either uses the custom
	 * one (if provided) or the default SilverStripe message
	 *
	 * @return Varchar
	 */
	public function getErrorMessage() {
		$title = strip_tags("'". ($this->Title ? $this->Title : $this->Name) . "'");
		$standard = sprintf(_t('Form.FIELDISREQUIRED', '%s is required').'.', $title);
		
		// only use CustomErrorMessage if it has a non empty value
		$errorMessage = (!empty($this->CustomErrorMessage)) ? $this->CustomErrorMessage : $standard;
		
		return DBField::create_field('Varchar', $errorMessage);
	}

	/**
	 * @return array
	 */
	public function getVersionedChildrenLabels() {
		return array(
			'CustomRules' => 'EditableCustomRule'
		);
	}
}
