<?php

if(class_exists('MultiForm')) {
	
	/**
	 * @package userforms
	 */
	class MultiStepUserForm extends MultiForm {

		private static $allowed_actions = array(
			'finish'
		);

		private $userForm;

		public static $start_step = 'MultiStepUserFormStep';

		/**
		 * @param UserForm $userForm
		 * @param Controller $controller
		 * @param string $name
		 */
		public function __construct(UserForm $userForm) {
			$this->userForm = $userForm;

			parent::__construct($userForm->getController(), $userForm->getName());
		}

		/**
		 * @return UserForm
		 */
		public function getUserForm() {
			return $this->userForm;
		}

		/**
		 * @return int
		 */
		public function getTotalSteps() {
			$page = 1;

			foreach($this->userForm->getEditableFields() as $field) {
				if($field instanceof EditableFormPageBreak) {
					$page++;
				}
			}

			return $page;
		}

		/**
		 * @return HTML
		 */
		public function forTemplate() {
			Requirements::customScript(
				$this->userForm->renderWith('ValidationScript'), 
				'UserFormsValidation'
			);

			return parent::forTemplate();
		}

		/**
		 * @param array $data
		 * @param Form $form
		 */
		public function finish($data, $form) {
			parent::finish($data, $form);

			$steps =  MultiFormStep::get()->filter(array(
				"SessionID" => $this->session->ID
			));

			if($steps) {
				$data = array();

				foreach($steps as $step) {
					$data = array_merge($data, $step->loadData()); 
				}

				unset($data['StepIndex']);
				unset($data['action_finish']);
			}

			$this->session->delete();

			return $this->getUserForm()->process(
				$data, 
				$this->getUserForm(), 
				$this->getRequest()
			);
		}
	}
}