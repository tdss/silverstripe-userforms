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
		"Name" => "Varchar",
		"Title" => "Varchar(255)",
		"Default" => "Varchar",
		"Sort" => "Int",
		"Required" => "Boolean",
		"CustomErrorMessage" => "Varchar(255)",
		"CustomRules" => "Text",
		"CustomSettings" => "Text"
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
	private static $extensions = array(
		"Versioned('Stage', 'Live')"
	);
	
	/**
	 * @return FieldList
	 */
	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->removeByName('Sort');
		$fields->removeByName('Name');
		$fields->removeByName('ParentID');

		$fields->insertBefore(new ReadonlyField(
			'Type', 
			_t('EditableFormField.TYPE', 'Type'), 
			$this->config()->get('singular_name')), 
			'Title'
		);

		return $fields;
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
	 * @todo canCreate cannot use parent
	 *
	 * @return boolean
	 */
	public function canCreate($member = null) {
		if($this->config()->get('hide_from_create')) {
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

		return false;
	}
	
	/**
	 * Publish this Form Field to the live site
	 * 
	 * Wrapper for the {@link Versioned} publish function
	 */
	public function doPublish($fromStage, $toStage, $createNewVersion = false) {
		$this->publish($fromStage, $toStage, $createNewVersion);
	}
	
	/**
	 * Delete this form from a given stage.
	 *
	 * Wrapper for the {@link Versioned} deleteFromStage function
	 */
	public function doDeleteFromStage($stage) {
		$this->deleteFromStage($stage);
	}
	
	/**
	 * Show this form on load or not
	 *
	 * @return bool
	 */
	public function getShowOnLoad() {
		return ($this->getSetting('ShowOnLoad') == "Show" || $this->getSetting('ShowOnLoad') == '') ? true : false;
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
	 * Get the path to the icon for this field type, relative to the site root.
	 *
	 * @return string
	 */
	public function getIcon() {
		return USERFORMS_DIR . '/images/' . strtolower($this->class) . '.png';
	}
	
	/**
	 * Return the custom validation fields for this field for the CMS
	 *
	 * @return array
	 */
	public function Dependencies() {
		return ($this->CustomRules) ? unserialize($this->CustomRules) : array();
	}
	
	/**
	 * Return the custom validation fields for the field
	 * 
	 * @return DataObjectSet
	 */
	public function CustomRules() {
		$output = new ArrayList();
		$fields = $this->Parent()->Fields();

		// check for existing ones
		if($rules = $this->Dependencies()) {
			foreach($rules as $rule => $data) {
				// recreate all the field object to prevent caching
				$outputFields = new ArrayList();
				
				foreach($fields as $field) {
					$new = clone $field;

					$new->isSelected = ($new->Name == $data['ConditionField']) ? true : false;
					$outputFields->push($new);
				}
				
				$output->push(new ArrayData(array(
					'FieldName' => $this->getFieldName(),
					'Display' => $data['Display'],
					'Fields' => $outputFields,
					'ConditionField' => $data['ConditionField'],
					'ConditionOption' => $data['ConditionOption'],
					'Value' => $data['Value']
				)));
			}
		}
	
		return $output;
	}
	
	/**
	 * Implement custom field Configuration on this field. Includes such things as
	 * settings and options of a given editable form field
	 *
	 * @return FieldSet
	 */
	public function getFieldConfiguration() {
		$right = new TextField(
			$this->getSettingName('RightTitle'), 
			_t('EditableFormField.RIGHTTITLE', 'Right Title'), 
			$this->getSetting('RightTitle')
		);

        $fields = FieldList::create(
            $ec,
            $right,
            $hide = new CheckboxField(
				$this->getSettingName('HideFromReports'),
				_t('EditableFormField.HIDEFROMREPORT', 'Hide from reports?'), 
				$this->getSetting('HideFromReports')
			)
        );

        $this->extend('updateFieldConfiguration', $fields);
        
        return $fields;
	}
	
	/**
	 * Append custom validation fields to the default 'Validation' 
	 * section in the editable options view
	 * 
	 * @return FieldSet
	 */
	public function getFieldValidationOptions() {
		$fields = new FieldList(
			new CheckboxField($this->getFieldName('Required'), _t('EditableFormField.REQUIRED', 'Is this field Required?'), $this->Required),
			new TextField($this->getFieldName('CustomErrorMessage'), _t('EditableFormField.CUSTOMERROR','Custom Error Message'), $this->CustomErrorMessage)
		);
		
		if(!$this->canEdit()) {
			foreach($fields as $field) {
				$field->performReadonlyTransformation();
			}
		}

        $this->extend('updateFieldValidationOptions', $fields);
		
		return $fields;
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
}
