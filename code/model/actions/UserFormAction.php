<?php

/**
 * Base 'abstract' class for actions that should be triggered when a user 
 * submits a {@link UserForm}.
 *
 * @package userforms
 */
class UserFormAction extends DataObject {
	
	/**
	 * @var array $db
	 */
	private static $db = array(
		'Sort' => 'Int'
	);

	/**
	 * @var array $has_one
	 */
	private static $has_one = array(
		'Parent' => 'DataObject'
	);

	/**
	 * Nice name for this action in the CMS editor
	 *
	 * @return string
	 */
	public function getTitle() {
		return __CLASS__;
	}

	/**
	 * @param Member
	 *
	 * @return boolean
	 */
	public function canCreate($member = null) {
		if($this->Parent()) {
			return $this->Parent()->canCreate($member);
		}

		return parent::canCreate($member);
	}

	/**
	 * @param Member
	 *
	 * @return boolean
	 */
	public function canView($member = null) {
		return $this->Parent()->canView($member);
	}
	
	/**
	 * @param Member
	 *
	 * @return boolean
	 */
	public function canEdit($member = null) {
		return $this->Parent()->canEdit($member);
	}
	
	/**
	 * @param Member
	 *
	 * @return boolean
	 */
	public function canDelete($member = null) {
		return $this->Parent()->canDelete($member);
	}

	/**
	 * Modifies the form to include whatever needs to be included for this 
	 * action. Could be inserting additional markup in a {@link FormField}.
	 *
	 * @param UserForm $form
	 */
	public function modifyForm(UserForm $form) {
		//
	}

	/**
	 * Returns an optional {@link FormAction} to be included.
	 *
	 * @param UserForm $form
	 *
	 * @return FormAction
	 */
	public function getFormAction() {
		//
	}

	/**
	 * Callback for when the user has submitted the form. 
	 *
	 * @param UserForm $form
	 * @param ArrayList $submissionData
	 */
	public function processForm(UserForm $form, ArrayList $submissionData) {
		//
	}
}