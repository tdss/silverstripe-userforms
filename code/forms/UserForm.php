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
	 * @param Controller $controller
	 * @param DataObject $dataSource
	 * @param string $name
	 */
	public function __construct(Controller $controller, $dataSource = null, $name = 'Form') {	
		if(!$dataSource && $controller->hasMethod('dataRecord')) {
			$dataSource = $controller->dataRecord();
		}

		if(!$dataSource->hasExtension('UserFormFieldEditorExtension')) {
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

		$data = Session::get("FormInfo.{$this->FormName()}.data");
		
		if(is_array($data)) {
			$this->loadDataFrom($data);
		}

		$this->extend('updateForm', $controller);
		$this->setFormAction('UserFormProcessController/process');
	}

	/**
	 * @return boolean
	 */
	public function validate() {
		Session::set("FormInfo.{$this->FormName()}.data",$data);	
		Session::clear("FormInfo.{$this->FormName()}.errors");
		
		foreach($this->getFieldList() as $field) {
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
		
		if(Session::get("FormInfo.{$this->FormName()}.errors")){
			return false;
		}

		return true;
	}

	/**
	 * @return FieldList
	 */
	public function getFieldList() {
		$fields = new FieldList();

		foreach($this->dataSource->UserFormFields() as $editableField) {
			$field = $editableField->getFormField();

			if(!$field) {
				break;
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
		$submitText = ($this->SubmitButtonText) ? $this->SubmitButtonText : _t('UserDefinedForm.SUBMITBUTTON', 'Submit');
		$clearText = ($this->ClearButtonText) ? $this->ClearButtonText : _t('UserDefinedForm.CLEARBUTTON', 'Clear');
		
		$actions = new FieldList(
			new FormAction("process", $submitText)
		);

		if($this->ShowClearButton) {
			$actions->push(new ResetFormAction("clearForm", $clearText));
		}
		
		$this->extend('updateFormActions', $actions);

		return $actions;
	}

	/**
	 * @return RequiredFields
	 */
	public function getRequiredFieldList() {
		// Generate required field validator
		$requiredNames = $this->dataSource
			->UserFormFields()
			->filter('Required', true)
			->column('Name');

		$required = new RequiredFields($requiredNames);
		
		$this->extend('updateRequiredFields', $required);

		return $required;
	}

	/**
	 * @return RelationalList
	 */
	public function getProcessActions() {
		return $this->controller->UserFormActions();
	}

	/**
	 * @return HTML
	 */
	public function forTemplate() {
		Requirements::customScript($this->renderWith('ValidationScript'), 'UserFormsValidation');

		return parent::forTemplate();
	}
}	