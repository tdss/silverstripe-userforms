<?php

if(class_exists('MultiForm')) {

	/**
	 * @package userforms
	 */
	class MultiStepUserFormStep extends MultiFormStep {

		private static $db = array(
			'StepIndex' => 'Int'
		);

		/**
		 * @return UserForm
		 */
		public function getUserForm() {
			return $this->getForm()->getUserForm();
		}

		/**
		 * @return FieldList
		 */
		public function getFields() {
			$page = 0;
			$fields = new FieldList();

			foreach($this->getEditableFields() as $field) {
				if($formField = $field->getFormField()) {
					$fields->push($formField);
				}
			}

			$fields->push(new HiddenField('StepIndex', '', $this->StepIndex));

			$this->extend('updateFormFields', $fields);

			return $fields;
		}

		/**
		 * @return ArrayList
		 */
		public function getEditableFields() {
			$editable = new ArrayList();
			$page = 0;

			foreach($this->getUserForm()->getEditableFields() as $field) {
				if($field instanceof EditableFormPageBreak) {
					$page++;

					continue;
				}

				if($page == $this->StepIndex) {
					$editable->push($field);
				}
			}

			return $editable;
		}

		/**
		 * @return RequiredFields
		 */
		public function getValidator() {
			$fields = $this->getEditableFields();
			$required = new RequiredFields();

			foreach($fields as $field) {
				if($field->Required) {
					$required->addRequiredField($field->getName());
				}
			}
		
			$this->extend('updateValidator', $required);

			return $required;
		}

		/**
		 * @param array $data
		 * @param Form $form
		 *
		 * @return boolean
		 */
		public function validateStep($data, $form) {
			Session::set("FormInfo.{$this->FormName()}.data",$data);	
			Session::clear("FormInfo.{$this->FormName()}.errors");
			
			foreach($this->getEditableFields() as $field) {
				$this->validateField($field, $data, $form);
			}
			
			if(Session::get("FormInfo.{$this->FormName()}.errors")){
				return false;
			}

			return true;
		}

		/**
		 * @return MultiStepUserFormStep
		 */
		public function getNextStep() {
			if(!$this->isFinalStep()) {
				$next = MultiStepUserFormStep::get()->filter(array(
					'SessionID' => $this->getForm()->getSession()->ID,
					'StepIndex' => $this->StepIndex + 1
				))->first();

				if(!$next) {
					$next = MultiStepUserFormStep::create();
					$next->StepIndex = $this->StepIndex + 1;
					$next->SessionID = $this->getForm()->getSession()->ID;
				}
				
				return $next;
			}

			return null;
		}

		/**
		 * @return MultiStepUserFormStep
		 */
		public function getPrevStep() {
			if($this->StepIndex) {
				$previous = MultiStepUserFormStep::get()->filter(array(
					'SessionID' => $this->getForm()->getSession()->ID,
					'StepIndex' => $this->StepIndex - 1
				))->first();

				return $previous;
			}

			return null;
		}

		/**
		 * @return boolean
		 */
		public function canGoBack() {
			return ($this->StepIndex && $this->StepIndex !== 0);
		}

		/**
		 * @return boolean
		 */
		public function isFinalStep() {
			return ($this->StepIndex == ($this->getForm()->getTotalSteps() - 1));
		}
	}
}