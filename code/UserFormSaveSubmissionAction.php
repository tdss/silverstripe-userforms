<?php

/**
 * @package userforms
 */
class UserFormSaveSubmissionAction extends UserFormProcessAction {
	
	private static $db = array(

	);

	/**
	 * @return FieldList
	 */
	public function getCMSFields() {

	}
	/**
	 * @return string
	 */
	public function getTitle() {
		return _t('UserFormSaveSubmissionAction.TITLE', 'Save Submission to Database');
	}

	/**
	 * @return null
	 */
	public function getButton() {
		return null;
	}

	/**
	 * @return void
	 */
	public function process($form, $submission, $submissionData) {
		$submission->write();

		foreach($submissionData as $line) {
			$line->ParentID = $submission->ID;
			$line->write();
		}

		return true;
	}
}