<?php

/**
 * Process a {@link UserForm} that has been submitted through the site.
 *
 * The final result from this process is always a redirection. It will iterate
 * through each of the {@link UserFormAction} instances attached to this
 * form and call {@link UserFormAction::processForm()} on each. 
 *
 * {@link UserFormRedirectAction} can redirect the user to the correct location
 * or the script will fallback to going to the previous page.
 *
 * @package userforms
 */
class UserFormProcessor extends Object {

	/**
	 * @param array $data
	 * @param Form $form
	 *
	 */
	public function process($data, $form, $request) {		
		$submissionCells = new ArrayList();

		$this->extend('onBeforeFormProcess', $data, $form);

		foreach($form->getEditableFields() as $field) {
			if(!$field->getShowInReports()) {
				continue;
			}
			
			$this->processCell($data, $field, $submissionCells);
		}

		foreach($form->getEditableActions() as $action) {
			$action->processForm($form, $submissionCells);
		}

		$this->extend('onAfterFormProcess', $data, $form);

		// if none of the actions have redirected the user, then manually 
		// redirect them back to the previous page.
		return $form->getController()->redirectBack();
	}

	/**
	 * @param array $data
	 * @param EditableFormField $field
	 * @param ArrayList $submissionCells
	 */
	public function processCell($data, $field, $submissionCells) {
		$this->extend('onBeforeProcessCell', $data, $field, $submissionCells);

		$cell = $field->getSubmittedFormField();
		$cell->Name = $field->Name;
		$cell->Title = $field->Title;
			
		if($field->hasMethod('getValueFromData')) {
			$cell->Value = $field->getValueFromData($data, $submissionCells);
		} else if($cell->hasMethod('getValueFromData')) {
			$cell->Value = $cell->getValueFromData($field, $data, $submissionCells);
		} else if(isset($data[$field->Name])) {
			$cell->Value = $data[$field->Name];
		}

		$submissionCells->push($cell);

		if($field->hasMethod('postProcessCell')) {
			$field->postProcessCell($data, $submissionCells);
		} else if($cell->hasMethod('postProcessCell')) {
			$cell->postProcessCell($field, $data, $submissionCells);
		}

		$this->extend('onAfterProcessCell', $data, $field, $submissionCells);
	}
}