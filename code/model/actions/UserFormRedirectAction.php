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
		'RedirectType' => "Enum('Thanks, Page, URL', 'Thanks')",
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
	public function getTitle() {
		return _t('UserFormRedirectAction.TITLE', 'Redirect User');
	}

	/**
	 * {@inheritDoc}
	 */
	public function getCMSFields() {
		$this->beforeUpdateCMSFields(function($fields) {
			$options = array(
				'Thanks' => _t('UserFormRedirectAction.BACK', 'Show thanks content'),
				'URL' => _t('UserFormRedirectAction.URL', 'A given URL')
			);

			if(class_exists('Page')) {
				$options['Page'] = _t('UserFormRedirectAction.SEPARATEPAGE', 'A separate page');			
			}

			$fields->addFieldsToTab('Root.Main', array(
				new DropdownField(
					'RedirectType', 
					_t('UserFormRedirectAction.REDIRECTTYPE', 'Redirect Type'),
					$options
				),
				$url = new TextField('RedirectURL', _t('UserFormRedirectAction.URL', 'URL'))
			));

			$url->hideUnless('RedirectType')->isEqualTo('URL');
			
			if(class_exists('Page')) {
				$fields->addFieldToTab('Root.Main', $page = new TreeDropdownField(
					'RedirectPageID',
					_t('UserFormRedirectAction.PAGE', 'Page'),
					'Page'
				));

				$page->hideUnless('RedirectType')->isEqualTo('Page');
			}
		});

		return parent::getCMSFields();
	}

	/**
	 * {@inheritDoc}
	 */
	public function getFormAction() {
		$text =  $this->SubmitButtonText;

		if(!$text) {
			$text = _t('UserFormRedirectAction.SUBMIT', 'Submit');
		}

		return new FormAction("process", $text);
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
			if (!$form->getSecurityToken()->isEnabled()) {
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

				break;
			case 'Page':
				if(class_exists('Page')) {
					$page = Page::get()->byId($this->RedirectPageID);

					if($page) {
						$link = $page->Link($this->ActionName);
					}
				}

				break;
			case 'URL':
				$link = $this->RedirectURL;

				break;
		}

		if($link) {
			$form->getController()->redirect($link);
		} else {
			$form->getController()->redirectBack();
		}
	}
}