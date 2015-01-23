<?php

/**
 * @package userforms
 */
class UserFormActionEditorExtension extends DataExtension {
	
	private static $has_many = array(
		"UserFormActions" => "UserFormAction"
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
		$config->addComponent($classes = new GridFieldAddNewMultiClass());
		$config->addComponent(new GridState_Component());
		$config->addComponent(new GridFieldAddExistingSearchButton());
		$config->addComponent(new GridFieldDeleteAction());
		$config->addComponent(new GridFieldDetailForm());
		$config->addComponent(new GridFieldOrderableRows('Sort'));

		$valid = ClassInfo::subclassesFor('UserFormAction');
		unset($valid['UserFormAction']);

		foreach($valid as $k => $v) {
			$valid[$k] = singleton($v)->getTitle();
		}
		
		$classes->setClasses($valid);
		$actions->setConfig($config);

		$fields->addFieldToTab('Root.Actions', $actions);
	}

	public function onAfterWrite() {
		if(!Config::inst()->get('UserFormActionEditorExtension', 'disable_default_action')) {
			if($this->owner->UserFormActions()->Count() == 0) {
				$action = UserFormSaveAction::create();
				$action->write();

				$action = UserFormRedirectAction::create();
				$action->RedirectType = 'Thanks';
				$action->ActionName = 'thanks';
				$action->ParentID = $this->owner->ID;
				$action->ParentClass = get_class($this->owner);

				$action->write();
			}
		}
	}
}