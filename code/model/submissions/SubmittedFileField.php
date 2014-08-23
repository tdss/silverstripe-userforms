<?php

/**
 * A file uploaded on a {@link UserDefinedForm} and attached to a single 
 * {@link SubmittedForm}.
 *
 * @package userforms
 */

class SubmittedFileField extends SubmittedFormField {
	
	private static $has_one = array(
		"UploadedFile" => "File"
	);
	
	/**
	 * Return the value of this field for inclusion into things such as 
	 * reports.
	 * 
	 * @return string
	 */
	public function getFormattedValue() {
		$name = $this->getName();
		$link = $this->getLink();
		$title = _t('SubmittedFileField.DOWNLOADFILE', 'Download File');
		
		if($link) {
			return DBField::create_field('HTMLText', sprintf(
				'%s - <a href="%s" target="_blank">%s</a>', 
				$name, $link, $title
			));
		}
		
		return false;
	}
	
	/**
	 * Return the value for this field in the CSV export.
	 *
	 * @return string
	 */
	public function getExportValue() {
		return ($link = $this->getLink()) ? $link : "";
	}

	/**
	 * Return the link for the file attached to this submitted form field.
	 * 
	 * @return string
	 */
	public function getLink() {
		if($file = $this->UploadedFile()) {
			if(trim($file->getFilename(), '/') != trim(ASSETS_DIR,'/')) {
				return $this->UploadedFile()->URL;
			}
		}
	}
	
	/**
	 * Return the name of the file, if present
	 *
	 * @return string
	 */
	public function getName() {
		if($this->UploadedFile()) {
			return $this->UploadedFile()->Name;
		}
	}

	public function getValueFromData($field, $data, $form) {
		if(isset($_FILES[$field->Name])) {
			$folder = $field->getFormField()->getFolderName();
			
			$upload = new Upload();
			
			$file = new File();
			$file->ShowInSearch = 0;

			try {
				$upload->loadIntoFile($_FILES[$field->Name], $file, $foldername);
			} catch( ValidationException $e ) {
				$form->addErrorMessage(
					$field->Name, 
					$e->getResult()->message(), 
					'bad'
				);

				return false;
			}

			// write file to form field
			$submittedField->UploadedFileID = $file->ID;
			
			// attach a file only if lower than 1MB
			if($file->getAbsoluteSize() < 1024*1024*1){
				return $file->Link();
			}
		}
	}
}