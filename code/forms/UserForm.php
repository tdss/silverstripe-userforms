<?php

/**
 * @package userforms
 */
class UserForm extends Form {
	
	/**
	 * A {@link DataObject} with the {@link UserFormFieldEditorExtension} added.
	 *
	 * @var DataObject $dataSource
	 */
	private $dataSource;

	/**
	 * @var array $allowed_actions
	 */
	private static $allowed_actions = array(
		'handleField',
		'httpSubmission',
		'forTemplate',
	);

	/**
	 * @param Controller $controller
	 * @param DataObject $dataSource
	 * @param string $name
	 */
	public function __construct(Controller $controller, $dataSource = null, $name = 'Form') {	
		if(!$dataSource && $controller->hasMethod('dataRecord')) {
			$dataSource = $controller->dataRecord();
		}

		if(!$dataSource && $controller->hasMethod('data')) {
			$dataSource = $controller->data();
		}
		
		if(!$dataSource || !$dataSource->hasExtension('UserFormFieldEditorExtension')) {
			throw new InvalidArgumentException('Your $dataSource must apply the UserFormFieldEditorExtension');
		}

		$this->dataSource = $dataSource;
		$this->controller = $controller;

		$lang = i18n::get_lang_from_locale(i18n::get_locale());
		Requirements::javascript(FRAMEWORK_DIR .'/thirdparty/jquery/jquery.js');
		Requirements::javascript(USERFORMS_DIR . '/thirdparty/jquery-validate/jquery.validate.min.js');
		Requirements::add_i18n_javascript(USERFORMS_DIR . '/javascript/lang');
		Requirements::javascript(USERFORMS_DIR . '/javascript/UserForm_frontend.js');
		
		Requirements::javascript(
			USERFORMS_DIR . "/thirdparty/jquery-validate/localization/messages_{$lang}.min.js"
		);
		
		Requirements::javascript(
			USERFORMS_DIR . "/thirdparty/jquery-validate/localization/methods_{$lang}.min.js"
		);

		if($this->HideFieldLabels) {
			Requirements::javascript(USERFORMS_DIR . '/thirdparty/Placeholders.js/Placeholders.min.js');
		}

		$this->setRedirectToFormOnValidationError(true);
		
		parent::__construct(
			$controller, 
			$name, 
			$this->getFieldList(), 
			$this->getActionButtonsList(), 
			$this->getRequiredFieldList()
		);

		// go through every action and make sure any modifications have taken
		// place before applying extensions. Actions may require specific 
		// markup or form modifications.
		foreach($this->getEditableActions() as $action) {
			$action->modifyForm($this);
		}

		// load the latest data from session.
		$data = Session::get("FormInfo.{$this->FormName()}.data");
		
		if(is_array($data)) {
			$this->loadDataFrom($data);
		}

		$this->extend('updateForm');
	}

	/**
	 * @return boolean
	 */
	public function validate() {
		$data = $this->getData();

		Session::set("FormInfo.{$this->FormName()}.data",$data);	
		Session::clear("FormInfo.{$this->FormName()}.errors");
		
		foreach($this->getEditableFields() as $field) {
			$this->validateField($field, $data, $this);
		}
		
		if(Session::get("FormInfo.{$this->FormName()}.errors")){
			return false;
		}

		return true;
	}

	public function validateField($field, $data, $form) {
		$messages[$field->Name] = $field->getErrorMessage()->HTML();
		$formField = $field->getFormField();

		if($field->Required && $field->CustomRules()->Count() == 0) {
			if(isset($data[$field->Name])) {
				$formField->setValue($data[$field->Name]);
			}

			if(
				!isset($data[$field->Name]) || 
				!$data[$field->Name] ||
				!$formField->validate($form->getValidator())
			) {
				$form->addErrorMessage($field->Name, $field->getErrorMessage(), 'bad');
			}
		}
	}

	/**
	 * @return SS_List
	 */
	public function getEditableFields() {
		$fields = $this->dataSource->UserFormFields();
		
		$this->extend('updateEditableFields', $fields);

		return $fields;
	}

	/**
	 * @return SS_List
	 */
	public function getEditableActions() {
		$actions = $this->dataSource->UserFormActions();

		$this->extend('updateEditableActions', $actions);

		return $actions;
	}

	/**
	 * Returns the {@link FieldList} for direct insertion into a form. 
	 * 
	 * @return FieldList
	 */
	public function getFieldList() {
		$fields = new FieldList();

		foreach($this->getEditableFields() as $editableField) {
			$field = $editableField->getFormField();

			if(!$field) {
				continue;
			}

			$field->setCustomValidationMessage(
				$editableField->getErrorMessage()
			);

			if($right = $editableField->getSetting('RightTitle')) {
				$field->setRightTitle($right);
			}

			// if this field is required add some
			if($editableField->Required) {
				$field->addExtraClass('requiredField');

				if($identifier = UserDefinedForm::config()->required_identifier) {

					$title = $field->Title() ." <span class='required-identifier'>". $identifier . "</span>";
					$field->setTitle($title);
				}
			}
			
			// if this field has an extra class
			if($editableField->getSetting('ExtraClass')) {
				$field->addExtraClass(Convert::raw2att(
					$editableField->getSetting('ExtraClass')
				));
			}

			$request = $this->controller->getRequest();

			if($var = $request->getVar($field->name)) {
				$field->value = Convert::raw2att($var);
			}

			$fields->push($field);
		}
	
		$this->extend('updateFormFields', $fields);

		return $fields;
	}

	/**
	 * @return FieldList
	 */
	public function getActionButtonsList() {
		$actions = new FieldList();

		foreach($this->getEditableActions() as $action) {
			if($btn = $action->getFormAction()) {
				$actions->push($btn);
			}
		}


		$this->extend('updateFormActions', $actions);

		return $actions;
	}

	/**
	 * @return RequiredFields
	 */
	public function getRequiredFieldList() {
		$required = $this->dataSource
			->UserFormFields()
			->filter('Required', true)
			->map('ID', 'Name');

		$required = new RequiredFields($required->values());
		
		$this->extend('updateRequiredFields', $required);

		return $required;
	}

	/**
	 * @return HTML
	 */
	public function forTemplate() {
		Requirements::customScript(
			$this->renderWith('ValidationScript'), 
			'UserFormsValidation'
		);


		$generator = UserFormsConditionalRuleGenerator::create();
		$generator->setFields($this->getEditableFields());
		$generator->generate();

		return parent::forTemplate();
	}

	/**
	 * @return DataObject
	 */
	public function getDataSource() {
		return $this->dataSource;
	}

	/**
	 * @return boolean
	 */
	public function isMultiStepForm() {
		foreach($this->getEditableFields() as $editable) {
			if($editable instanceof EditableFormPageBreak) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Process a the form.
	 *
	 * @param array $data
	 * @param UserForm $form
	 * @param SS_HTTPRequest $request
	 */
	public function process($data, $form, $request) {
		$process = Injector::inst()->create('UserFormProcessor');

		return $process->process($data, $this, $request);
	}

	/**
	 * @return MultiStepUserForm
	 */
	public function upgradeToMultiStepForm() {
		$form = MultiStepUserForm::create($this);

		return $form;
	}
}	