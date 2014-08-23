<?php

/**
 * An email that gets sent to a given email address, managed through a 
 * {@link UserFormEmailAction} instance attached to the form.
 *
 * @package userforms
 */

class UserFormSubmissionEmail extends Email {
	

	/**
	 * @param UserFormEmailAction $recipient
	 * @param array $data
	 */
	public function __construct($recipient, $data) {
		parent::__construct();
		
		$this->populateTemplate($data);

		$this->setSubject($recipient->EmailSubject);
		$this->setTo($recipient->getCalculatedToEmailAddress($data));
				
		if($recipient->EmailReplyTo) {
			$this->setReplyTo($recipient->getCalculatedRelyToAddress($data));
		}

		if($recipient->SendPlain) {
			$body = strip_tags($recipient->EmailBody) . "\n ";
			
			if(isset($data['Fields']) && !$recipient->HideFormData) {
				foreach($data['Fields'] as $field) {
					$body .= $field->Title .' - '. $field->Value ." \n";
				}
			}

			$this->setBody($body);
		} else {
			$this->setBody($recipient->EmailBody);
		}



		$this->setTemplate("UserFormSubmissionEmail");
	}
	
	/**	
	 * Set the "Reply-To" header with an email address rather than append as
	 * {@link Email::replyTo} does. 
	 *
	 * @param string $email The email address to set the "Reply-To" header to
 	 */
	public function setReplyTo($email) {
		$this->customHeaders['Reply-To'] = $email;
	}  
}
