<?php


/**
 * Email that gets sent to the people listed in the Email Recipients when a 
 * submission is made.
 *
 * @package userforms
 */

class UserFormSubmissionEmail extends Email {
	
	protected $ss_template = "UserFormSubmissionEmail";

	protected $data;

	public function __construct($submittedFields = null) {
		parent::__construct($submittedFields);
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
