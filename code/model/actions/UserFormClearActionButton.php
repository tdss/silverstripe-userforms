<?php

/**
 * A {@link UserFormAction} which provides a button to clear the form.
 *
 * @package userform
 */
class UserFormClearFormAction extends UserFormAction {
	
	/**
	 * {@inheritDoc}
	 */
	public function getTitle() {
		return _t('UserFormClearFormAction.RESETFORM', 'Reset Form Button');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFormAction() {
		return new ResetFormAction(
			"clearForm", 
			_t('UserFormClearFormAction', 'Clear')
		);
	}	
}