<?php

/**
 * Base class for multiple option fields such as {@link EditableDropdownField} 
 * and radio sets. 
 * 
 * Implemented as a class but should be viewed as abstract, you should 
 * instantiate a subclass such as {@link EditableDropdownField}
 *
 * @see EditableCheckboxGroupField
 * @see EditableDropdownField
 *
 * @package userforms
 */

class EditableMultipleOptionField extends EditableFormField {
	
	private static $hide_from_create = true;
	
	private static $has_many = array(
		"Options" => "EditableOption"
	);
	
	/**
	 * Publishing Versioning support.
	 *
	 * When publishing it needs to handle copying across / publishing
	 * each of the individual field options
	 * 
	 * @return void
	 */
	public function doPublish($fromStage, $toStage, $createNewVersion = false) {
		$live = Versioned::get_by_stage("EditableOption", "Live", "\"EditableOption\".\"ParentID\" = $this->ID");

		if($live) {
			foreach($live as $option) {
				$option->delete();
			}
		}
		
		if($this->Options()) {
			foreach($this->Options() as $option) {
				$option->publish($fromStage, $toStage, $createNewVersion);
			}
		}
		
		$this->publish($fromStage, $toStage, $createNewVersion);
	}
	
	/**
	 * Unpublishing Versioning support.
	 * 
	 * When unpublishing the field it has to remove all options attached.
	 *
	 * @return void
	 */
	public function doDeleteFromStage($stage) {
		if($this->Options()) {
			foreach($this->Options() as $option) {
				$option->deleteFromStage($stage);
			}
		}
		
		$this->deleteFromStage($stage);
	}
	
	/**
	 * Deletes all the options attached to this field before deleting the 
	 * field. Keeps stray options from floating around
	 *
	 * @return void
	 */
	public function delete() {
		$options = $this->Options();

		if($options) {
			foreach($options as $option) {
				$option->delete();
			}
		}
		
		parent::delete(); 
	}
	
	/**
	 * Duplicate a pages content. We need to make sure all the fields attached 
	 * to that page go with it
	 * 
	 * @return DataObject
	 */
	public function duplicate($doWrite = true) {
		$clonedNode = parent::duplicate();
		
		if($this->Options()) {
			foreach($this->Options() as $field) {
				$newField = $field->duplicate();
				$newField->ParentID = $clonedNode->ID;
				$newField->write();
			}
		}
		
		return $clonedNode;
	}

	/**
	 * Return the form field for this object in the front end form view
	 *
	 * @return FormField
	 */
	public function getFormField() {
		return user_error('Please implement getFormField() on '. $this->class, E_USER_ERROR);
	}
}
