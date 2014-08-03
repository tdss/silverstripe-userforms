<?php

/**
 * A {@link UserForms} can have multiply members / emails to email the 
 * submission to and custom subjects.
 * 
 * @package userforms
 */
class UserFormEmailProcessAction extends DataObject {
	
	private static $db = array(
		'EmailAddress' => 'Varchar(200)',
		'EmailSubject' => 'Varchar(200)',
		'EmailFrom' => 'Varchar(200)',
		'EmailReplyTo' => 'Varchar(200)',
		'EmailBody' => 'Text',
		'SendPlain' => 'Boolean',
		'HideFormData' => 'Boolean'
	);
	
	private static $has_one = array(
		'Form' => 'DataObject',
		'SendEmailFromField' => 'EditableFormField',
		'SendEmailToField' => 'EditableFormField'
	);
	
	private static $summary_fields = array(
		'EmailAddress',
		'EmailSubject',
		'EmailFrom'
	);

	/**
	 * @return FieldList
	 */
	public function getCMSFields() {
		$fields = new FieldList(
			new TextField('EmailSubject', _t('UserDefinedForm.EMAILSUBJECT', 'Email subject')),
			new LiteralField('EmailFromContent', '<p>'._t(
				'UserDefinedForm.EmailFromContent',
				"The from address allows you to set who the email comes from. On most servers this ".
				"will need to be set to an email address on the same domain name as your site. ".
				"For example on yoursite.com the from address may need to be something@yoursite.com. ".
				"You can however, set any email address you wish as the reply to address."
			) . "</p>"),
			new TextField('EmailFrom', _t('UserDefinedForm.FROMADDRESS','Send email from')),
			new TextField('EmailReplyTo', _t('UserDefinedForm.REPLYADDRESS', 'Email for reply to')),
			new TextField('EmailAddress', _t('UserDefinedForm.SENDEMAILTO','Send email to')),
			new CheckboxField('HideFormData', _t('UserDefinedForm.HIDEFORMDATA', 'Hide form data from email?')),
			new CheckboxField('SendPlain', _t('UserDefinedForm.SENDPLAIN', 'Send email as plain text? (HTML will be stripped)')),
			new TextareaField('EmailBody', _t('UserDefinedForm.EMAILBODY','Body'))
		);
		
		if($this->Form()) {
			$dropdowns = array();

			$validEmailFields = DataObject::get("EditableEmailField", "\"ParentID\" = '" . (int)$this->FormID . "'");
			$multiOptionFields = DataObject::get("EditableMultipleOptionField", "\"ParentID\" = '" . (int)$this->FormID . "'");
			
			// if they have email fields then we could send from it
			if($validEmailFields) {
				$fields->insertAfter($dropdowns[] = new DropdownField(
					'SendEmailFromFieldID',
					_t('UserDefinedForm.ORSELECTAFIELDTOUSEASFROM', '.. or select a field to use as reply to address'),
					$validEmailFields->map('ID', 'Title')
				), 'EmailReplyTo');
			}

			// if they have multiple options
			if($multiOptionFields || $validEmailFields) {

				if($multiOptionFields && $validEmailFields) {
					$multiOptionFields = $multiOptionFields->toArray();
					$multiOptionFields = array_merge(
						$multiOptionFields,
						$validEmailFields->toArray()
					);

					$multiOptionFields = ArrayList::create($multiOptionFields);
				}
				else if(!$multiOptionFields) {
					$multiOptionFields = $validEmailFields;	
				}
				
				$multiOptionFields = $multiOptionFields->map('ID', 'Title');
					$fields->insertAfter($dropdowns[] = new DropdownField(
						'SendEmailToFieldID',
						_t('UserDefinedForm.ORSELECTAFIELDTOUSEASTO', '.. or select a field to use as the to address'),
					 $multiOptionFields
				), 'EmailAddress');
			}

			if($dropdowns) {
				foreach($dropdowns as $dropdown) {
					$dropdown->setHasEmptyDefault(true);
					$dropdown->setEmptyString(" ");
				}
			}
		}

		$this->extend('updateCMSFields', $fields);

		return $fields;
	}

	/**
	 * @param Member
	 *
	 * @return boolean
	 */
	public function canCreate($member = null) {
		return $this->Form()->canCreate();
	}

	/**
	 * @param Member
	 *
	 * @return boolean
	 */
	public function canView($member = null) {
		return $this->Form()->canView();
	}
	
	/**
	 * @param Member
	 *
	 * @return boolean
	 */
	public function canEdit($member = null) {
		return $this->Form()->canEdit();
	}
	
	/**
	 * @param Member
	 *
	 * @return boolean
	 */
	public function canDelete($member = null) {
		return $this->Form()->canDelete();
	}

	/**
	 *
	 */
	public function process() {
		$emailData = array(
			"Sender" => Member::currentUser(),
			"Fields" => $submittedFields
		);
		
		$this->extend('updateEmailData', $emailData, $attachments);
		
		// email users on submit.
		if($recipients = $this->FilteredEmailRecipients($data, $form)) {
			$email = new UserDefinedForm_SubmittedFormEmail($submittedFields); 
			
			if($attachments) {
				foreach($attachments as $file) {
					if($file->ID != 0) {
						$email->attachFile(
							$file->Filename, 
							$file->Filename, 
							HTTP::get_mime_type($file->Filename)
						);
					}
				}
			}

			foreach($recipients as $recipient) {
				$email->populateTemplate($recipient);
				$email->populateTemplate($emailData);
				$email->setFrom($recipient->EmailFrom);
				$email->setBody($recipient->EmailBody);
				$email->setSubject($recipient->EmailSubject);
				$email->setTo($recipient->EmailAddress);
				
				if($recipient->EmailReplyTo) {
					$email->setReplyTo($recipient->EmailReplyTo);
				}

				// check to see if they are a dynamic reply to. eg based on a email field a user selected
				if($recipient->SendEmailFromField()) {
					$submittedFormField = $submittedFields->find('Name', $recipient->SendEmailFromField()->Name);

					if($submittedFormField && is_string($submittedFormField->Value)) {
						$email->setReplyTo($submittedFormField->Value);
					}
				}
				// check to see if they are a dynamic reciever eg based on a dropdown field a user selected
				if($recipient->SendEmailToField()) {
					$submittedFormField = $submittedFields->find('Name', $recipient->SendEmailToField()->Name);
					
					if($submittedFormField && is_string($submittedFormField->Value)) {
						$email->setTo($submittedFormField->Value);	
					}
				}
				
				$this->extend('updateEmail', $email, $recipient, $emailData);

				if($recipient->SendPlain) {
					$body = strip_tags($recipient->EmailBody) . "\n ";
					if(isset($emailData['Fields']) && !$recipient->HideFormData) {
						foreach($emailData['Fields'] as $Field) {
							$body .= $Field->Title .' - '. $Field->Value ." \n";
						}
					}

					$email->setBody($body);
					$email->sendPlain();
				}
				else {
					$email->send();	
				}
			}
		}
		
	}
}
