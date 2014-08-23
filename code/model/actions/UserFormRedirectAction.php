<?php

/**
 * An {@link UserFormAction} which redirects the user to a given URL. 
 *
 * @package userforms
 */
class UserFormRedirectAction extends UserFormAction {
	
	/**
	 * {@inheritDoc}
	 */
	private static $db = array(
		'RedirectType' => "Enum('Back, Action, Page, URL', 'Action')",
		'ActionName' => 'Varchar(255)',
		'RedirectURL' => 'Varchar(255)',
		'SubmitButtonText' => 'Varchar(50)'
	);

	/**
	 * {@inheritDoc}
	 */
	private static $has_one = array(
		'RedirectPage' => 'DataObject'
	);

	/**
	 * {@inheritDoc}
	 */
	public function getCMSFields() {
		$options = array(
			'Back' => _t('UserFormRedirectAction.BACK', 'Redirect back to previous page'),
			'Action' => _t('UserFormRedirectAction.ACTION', 'An action on the current page'),
			'URL' => _t('UserFormRedirectAction.URL', 'A given URL')
		);

		if(class_exists('Page')) {
			$options['Page'] = _t('UserFormRedirectAction.SEPARATEPAGE', 'A separate page.');			
		}

		// @todo display logic?
		$this->beforeUpdatingCMSFields(function($fields) {
			$fields->addFieldsToTab('Root.Main', array(
				new OptionsetField(
					'RedirectType', 
					_t('UserFormRedirectAction.REDIRECTTYPE', 'Redirect Location'),
					$options
				),
				new TextField('ActionName', _t('UserFormRedirectAction.ACTIONNAME', 'Action name')),
				new TextField('RedirectURL', _t('UserFormRedirectAction.URL', 'URL'))
			));
			
			if(class_exists('Page')) {
				$fields->addFieldToTab('Root.Main', new TreeDropdownField(
					'RedirectPageID',
					_t('UserFormRedirectAction.PAGE', 'Page'),
					'Page'
				));
			}
		});

		return $fields;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFormAction() {
		return new FormAction("process", $this->SubmitButtonText);
	}

	/**
	 * {@inheritDoc}
	 */
	public function processForm(UserForm $form, ArrayList $submissionData) {
		// set a session variable from the security ID to stop people accessing the finished method directly
		if (isset($data['SecurityID'])) {
			Session::set('FormProcessed', $data['SecurityID']);
		} else {
			// if the form has had tokens disabled we still need to set FormProcessed
			// to allow us to get through the finished method
			if (!$this->Form()->getSecurityToken()->isEnabled()) {
				$randNum = rand(1, 1000);
				$randHash = md5($randNum);
				Session::set('FormProcessed',$randHash);
				Session::set('FormProcessedNum',$randNum);
			}
		}
		
		$link = false;

		switch($this->RedirectAction) {
			case 'Action':
				$link = $form->getController()->Link($this->ActionName);
			case 'Page':
				if(class_exists('Page')) {
					$page = Page::get()->byId($this->RedirectPageID);

					if($page) {
						$link = $page->Link($this->ActionName);
					}
				}
			case 'URL':
				$link = $this->RedirectURL;
		}

		if($link) {
			$form->getController()->redirect($link);
		} else {
			$form->getController()->redirectBack();
		}
	}
}