<?php

/**
 * An {@link UserFormAction} which sends a email as the process action.
 *
 * @package userforms
 */
class UserFormEmailAction extends UserFormAction {
	
	/**
	 * @var array $db
	 */
	private static $db = array(
		'EmailAddress' => 'Varchar(200)',
		'EmailSubject' => 'Varchar(200)',
		'EmailReplyTo' => 'Varchar(200)',
		'EmailBody' => 'Text',
		'SendPlain' => 'Boolean',
		'HideFormData' => 'Boolean'
	);
	
	/**
	 * @var array $has_one
	 */
	private static $has_one = array(
		'SendEmailFromField' => 'EditableFormField',
		'SendEmailToField' => 'EditableFormField'
	);
	
	/**
	 * @var array $summary_fields
	 */
	private static $summary_fields = array(
		'EmailAddress',
		'EmailSubject',
		'EmailFrom'
	);

	/**
	 * {@inheritDoc}
	 */
	public function getTitle() {
		$title = _t('UserFormEmailAction.TITLE', 'Send Email Notification');

		if($this->ID) {
			$title .= ' ('. $this->EmailAddress . ', '. $this->EmailSubject .')';
		}

		return $title;
	}

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
			new TextField('EmailReplyTo', _t('UserDefinedForm.REPLYADDRESS', 'Email for reply to')),
			new TextField('EmailAddress', _t('UserDefinedForm.SENDEMAILTO','Send email to')),
			new CheckboxField('HideFormData', _t('UserDefinedForm.HIDEFORMDATA', 'Hide form data from email?')),
			new CheckboxField('SendPlain', _t('UserDefinedForm.SENDPLAIN', 'Send email as plain text? (HTML will be stripped)')),
			new TextareaField('EmailBody', _t('UserDefinedForm.EMAILBODY','Body'))
		);
		
		if($this->Parent()) {
			$editable = $this->Parent()->UserFormFields();

			$emailFields = $editable->filterByCallback(function($item, $list) {
				return ($item instanceof EditableEmailField);
			});

			$multiFields = $editable->filterByCallback(function($item, $list) {
				return ($item instanceof EditableMultipleOptionField);
			});
			
			// if they have email fields then we could send from it
			if($emailFields) {
				$fields->insertAfter(
					DropdownField::create(
						'SendEmailFromFieldID',
						_t('UserDefinedForm.ORSELECTAFIELDTOUSEASFROM', '.. or select a field to use as reply to address'),
						$emailFields->map('ID', 'Title')
					)->setHasEmptyDefault(true)->setEmptyString(' '), 
					'EmailReplyTo'
				);
			}

			// if they have multiple options
			if($multiFields) {
				$fields->insertAfter(
					DropdownField::create(
						'SendEmailToFieldID',
						_t('UserDefinedForm.ORSELECTAFIELDTOUSEASTO', '.. or select a field to use as the to address'),
					 $multiFields
					)->setHasEmptyDefault(true)->setEmptyString(' '), 
					'EmailAddress'
				);
			}
		}

		return $fields;
	}

	/**
	 * Return the email address that we should send this mail to. 
	 *
	 * @return string
	 */
	public function getCalculatedToEmailAddress($submissionData) {
		if($field = $this->SendEmailToField()) {
			$submitted = $submissionData->find(
				'Name', $field->Name
			);
					
			if($submitted && is_string($submitted->Value)) {
				return $submitted->Value;
			}
		}
		
		return $this->EmailAddress;		
	}

	/**
	 * Return the email address that we should add as the reply to. 
	 *
	 * @return string
	 */
	public function getCalculatedRelyToAddress($submissionData) {
		if($field = $this->SendEmailFromField()) {
			$submitted = $submissionData->find(
				'Name', $field->Name
			);
					
			if($submitted && is_string($submitted->Value)) {
				return $submitted->Value;
			}
		}
		
		return $this->EmailReplyTo;		
	}

	/**
	 * {@inheritdoc}
	 */
	public function processForm(UserForm $form, ArrayList $submissionData) {
		$data = array(
			"Sender" => Member::currentUser(),
			"Fields" => $submissionData
		);
		
		$email = new UserFormSubmissionEmail($this, $data); 
			
		foreach($submissionData as $field) {
			if($field instanceof SubmittedFileField) {
				if($file = $field->UploadedFile()) {
					$email->attachFile(
						$file->Filename, 
						$file->Filename, 
						HTTP::get_mime_type($file->Filename)
					);
				}
			}
		}

		if($this->SendPlain) {
			$email->sendPlain();
		} else {
			$email->send();
		}
	}
}
