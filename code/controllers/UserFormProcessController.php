<?php

/**
 * Process a {@link UserForm} that is submitted through the site.
 *
 * The final result from this process is a {@link UserFormRedirectProcessAction}
 * which takes the user to a given URL. Optionally, additional classes which
 * implement the {@link IUserFormProccessAction} interface will get picked up
 * and be allowed to perform post submit actions.
 *
 * @package userforms
 */
class UserFormProcessController extends Controller {
	
	/**
	 * @param array $data
	 * @param Form $form
	 *
	 */
	public function process($data, $form) {
		if(!$form->getController()->canView()) {
			return $this->httpError(403);
		}

		$submission =  Injector::inst()->create('SubmittedForm');
		$submission->SubmittedByID = Member::currentUserID();
		$submission->ParentID = $this->ID;
		
		$submissionData = new ArrayList();

		foreach($form->getFieldList() as $field) {
			if(!$field->getShowInReports()) {
				continue;
			}
			
			$line = $field->getSubmittedFormField();
			$line->Name = $field->getField('Name');
			$line->Title = $field->getField('Title');
			
			if($field->hasMethod('getValueFromData')) {
				$line->Value = $field->getValueFromData($data);
			} else if($line->hasMethod('getValueFromData') {
				$line->Value = $line->getValueFromData($field, $data);
			} else if(isset($data[$field->Name])) {
				$line->Value = $data[$field->Name];
			}

			$submissionData->push($line);
		}
	}
}