<?php

/**
 * A {@link UserFormAction} which simply takes the submission data and
 * saves it to the database.
 *
 * @package userforms
 */
class UserFormSaveAction extends UserFormAction {

	public function getCMSFields() {
		$fields = parent::getCMSFields();

		$fields->addFieldToTab('Root.Main', new LiteralField('SaveDescription', 
			_t('UserFormSaveAction.SAVEDESCRIPTION', 'This will save the information submitted in the form to the database. Submissions can be viewed under the submissions tab.')
		));

		return $fields;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getTitle() {
		return _t('UserFormSaveSubmissionAction.TITLE', 'Save Submission to Database');
	}

	/**
	 * {@inheritDoc}
	 */
	public function processForm(UserForm $form, ArrayList $submissionData) {
		$submission =  Injector::inst()->create('SubmittedForm');

		$submission->SubmittedByID = Member::currentUserID();
		$submission->ParentID = $form->getDataSource()->ID;
		$submission->ParentClass = get_class($form->getDataSource());
		$submission->write();

		foreach($submissionData as $field) {
			$field->ParentID = $submission->ID;
			$field->write();
		}
	}
}