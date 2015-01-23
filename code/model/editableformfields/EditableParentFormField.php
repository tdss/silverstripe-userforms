<?php

/**
 * A {@link EditableFormField} which users should not directly create, this 
 * instead encapsulates the publishing and unpublishing logic for form fields 
 * that have versioned dependancies
 *
 * @package userforms
 */
class EditableParentFormField extends EditableFormField {
	
	/**
	 * @return array
	 */
	public function getVersionedChildrenLabels() {
		throw new Exception('Define getVersionedChildrenLabels() on your '. get_class($this));
	}

	/**
	 * Publishing Versioning support.
	 *
	 * When publishing it needs to handle copying across / publishing each of 
	 * the individual field options
	 * 
	 * @return void
	 */
	public function doPublish($fromStage, $toStage, $createNewVersion = false) {
		foreach($this->getVersionedChildrenLabels() as $label => $class) {
			$live = Versioned::get_by_stage($class, "Live", "\"$class\".\"ParentID\" = $this->ID");

			if($live) {
				foreach($live as $record) {
					$record->delete();
				}
			}

			foreach($this->getComponents($label) as $inst) {
				$inst->publish($fromStage, $toStage, $createNewVersion);
			}
		}

		$this->publish($fromStage, $toStage, $createNewVersion);
	}
	
	/**
	 * @return void
	 */
	public function doDeleteFromStage($stage) {
		foreach($this->getVersionedChildrenLabels() as $label => $class) {
			foreach($this->getComponents($label) as $inst) {
				$inst->deleteFromStage($stage);
			}
		}
		
		$this->deleteFromStage($stage);
	}
	
	/**
	 * @return void
	 */
	public function delete() {
		foreach($this->getVersionedChildrenLabels() as $label => $class) {
			foreach($this->getComponents($label) as $inst) {
				$inst->delete();
			}
		}
		
		parent::delete(); 
	}
	
	/**
	 * @return DataObject
	 */
	public function duplicate($doWrite = true) {
		$clonedNode = parent::duplicate();
		
		foreach($this->getVersionedChildrenLabels() as $label => $class) {
			foreach($this->getComponents($label) as $inst) {
				$newField = $inst->duplicate();
				$newField->ParentID = $clonedNode->ID;
				$newField->write();
			}
		}
		
		return $clonedNode;
	}
}