<?php

/**
 * @package userforms
 */
class UserFormSubmitAction extends DataObject {
	
	private static $db = array(
		'Sort' => 'Int'
	);

	private static $has_one = array(
		'Parent' => 'DataObject'
	);

	public function getTitle() {

	}

	public function getButton() {
		
		$referrer = (isset($data['Referrer'])) ? '?referrer=' . urlencode($data['Referrer']) : "";


		// set a session variable from the security ID to stop people accessing the finished method directly
		if (isset($data['SecurityID'])) {
			Session::set('FormProcessed',$data['SecurityID']);
		} else {
			// if the form has had tokens disabled we still need to set FormProcessed
			// to allow us to get through the finshed method
			if (!$this->Form()->getSecurityToken()->isEnabled()) {
				$randNum = rand(1, 1000);
				$randHash = md5($randNum);
				Session::set('FormProcessed',$randHash);
				Session::set('FormProcessedNum',$randNum);
			}
		}
		
		return $this->redirect($this->Link() . 'finished' . $referrer);
	}
}