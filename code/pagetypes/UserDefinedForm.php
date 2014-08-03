<?php

/**
 * An optional page type which provides a CMS editable version of the form.
 *
 * @package userforms
 */
if(class_exists('Page')) {

class UserDefinedForm extends Page {
	
	/**
	 * @var string
	 *
	 * @config
	 */
	private static $description = 'Adds a customizable form.';
	
	/**
	 * @var array Fields on the user defined form page.
	 */
	private static $db = array(
		"ClearButtonText" => "Varchar",
		"OnCompleteMessage" => "HTMLText",
		'EnableLiveValidation' => 'Boolean',
		'HideFieldLabels' => 'Boolean'
	);
	
	/**
	 * @var array Default values of variables when this page is created
	 */ 
	private static $defaults = array(
		'Content' => '$UserDefinedForm',
		'DisableSaveSubmissions' => 0,
		'OnCompleteMessage' => '<p>Thanks, we\'ve received your submission.</p>'
	);

	/**
	 * @var array
	 */
	private static $extensions = array(
		'UserFormFieldEditorExtension',
		'UserFormActionEditorExtension',
		'UserFormSubmissionsExtension'
	);
}

/**
 * Controller for the {@link UserDefinedForm} page type.
 *
 * @package userforms
 */

class UserDefinedForm_Controller extends Page_Controller {
	
	private static $allowed_actions = array(
		'index',
		'Form',
		'finished'
	);

	public function init() {
		parent::init();
		
		// load the jquery
		$lang = i18n::get_lang_from_locale(i18n::get_locale());
		Requirements::javascript(FRAMEWORK_DIR .'/thirdparty/jquery/jquery.js');
		Requirements::javascript(USERFORMS_DIR . '/thirdparty/jquery-validate/jquery.validate.min.js');
		Requirements::add_i18n_javascript(USERFORMS_DIR . '/javascript/lang');
		Requirements::javascript(USERFORMS_DIR . '/javascript/UserForm_frontend.js');
		
		Requirements::javascript(
			USERFORMS_DIR . "/thirdparty/jquery-validate/localization/messages_{$lang}.min.js"
		);
		
		Requirements::javascript(
			USERFORMS_DIR . "/thirdparty/jquery-validate/localization/methods_{$lang}.min.js"
		);

		if($this->HideFieldLabels) {
			Requirements::javascript(USERFORMS_DIR . '/thirdparty/Placeholders.js/Placeholders.min.js');
		}
	}
	
	/**
	 * Using $UserDefinedForm in the Content area of the page shows where the 
	 * form should be rendered into. If it does not exist then default back to 
	 * $Form location.
	 *
	 * @return array
	 */
	public function index() {
		if($this->Content && $form = $this->Form()) {
			$hasLocation = stristr($this->Content, '$UserDefinedForm');

			if($hasLocation) {
				$content = str_ireplace('$UserDefinedForm', $form->forTemplate(), $this->Content);

				return array(
					'Content' => DBField::create_field('HTMLText', $content),
					'Form' => ""
				);
			}
		}

		return array(
			'Content' => DBField::create_field('HTMLText', $this->Content),
			'Form' => $this->Form()
		);
	}


	/**
	 * Get the form for the page. Form can be modified by calling {@link updateForm()}
	 * on a UserDefinedForm extension.
	 *
	 * @return Form|false
	 */
	public function Form() {
		return new UserForm($this);
	}
	
	/**
	 * This action handles rendering the "finished" message, which is 
	 * customizable by editing the ReceivedFormSubmission template.
	 *
	 * @return ViewableData
	 */
	public function finished() {
		return $this->customise(array(
			'Content' => $this->customise(
				array(
					'Link' => $referrer
				))->renderWith('ReceivedFormSubmission'),
			'Form' => ''
		));
	}
}
}