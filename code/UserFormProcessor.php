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
class UserFormProcessor {

	/**
	 * @param array $data
	 * @param Form $form
	 *
	 */
	public function process($data, $form, $request) {		
		$submissionData = new ArrayList();

		foreach($form->getEditableFields() as $field) {
			if(!$field->getShowInReports()) {
				continue;
			}
			
			$line = $field->getSubmittedFormField();
			$line->Name = $field->getField('Name');
			$line->Title = $field->getField('Title');
			
			if($field->hasMethod('getValueFromData')) {
				$line->Value = $field->getValueFromData($data);
			} else if($line->hasMethod('getValueFromData')) {
				$line->Value = $line->getValueFromData($field, $data);
			} else if(isset($data[$field->Name])) {
				$line->Value = $data[$field->Name];
			}

			$submissionData->push($line);
		}

		foreach($form->getEditableActions() as $action) {
			$action->processForm($form, $submissionData);
		}

		// if none of the actions have redirected the user, then manually 
		// redirect them back to the previous page.
		return $form->getController()->redirectBack();
	}
}