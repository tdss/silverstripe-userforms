<?php

/**
 * Class which represents a stop or break in the form which is on another page.
 * 
 * @package userforms
 */

 class EditableFormPageBreak extends EditableFormField {

	private static $singular_name = 'Page Break';

 	public function getFormField() {
 		return null;
 	}

 	public function getShowInReports() {
 		return false;
 	}
 }