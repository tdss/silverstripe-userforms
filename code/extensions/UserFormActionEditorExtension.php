<?php

/**
 * @package userforms
 */
class UserFormActionEditorExtension extends DataExtension {
	
	private static $has_many = array(
		"UserFormActions" => "UserFormSubmitAction"
	);
	
	public function updateCMSFields(FieldList $fields) {
		$fields->findOrMakeTab(
			'Root.Actions', 
			_t('UserDefinedForm.ACTIONS', 'Actions')
		);

		$actions = new GridField(
			"UserFormActions", 
			_t('UserDefinedForm.ACTIONS', 'Actions'),
			$this->owner->UserFormActions()
		);

		$config = new GridFieldConfig();
		$config->addComponent(new GridFieldToolbarHeader());
		$config->addComponent(new GridFieldButtonRow('before'));
		$config->addComponent(new GridFieldDataColumns());
		$config->addComponent(new GridFieldEditButton());
		$config->addComponent(new GridFieldAddNewMultiClass());
		$config->addComponent(new GridState_Component());
		$config->addComponent(new GridFieldAddExistingSearchButton());
		$config->addComponent(new GridFieldDeleteAction());
		$config->addComponent(new GridFieldDetailForm());
		$config->addComponent(new GridFieldOrderableRows('Sort'));

		$actions->setConfig($config);

		$fields->addFieldToTab('Root.Actions', $actions);
	}
}