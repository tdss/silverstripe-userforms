<?php

/**
 * @package userforms
 */
class UserFormsConditionalRuleGenerator extends Object {
	
	/**
	 * @var FieldList
	 */
	protected $fields = null;

	/**
	 * @var FieldList
	 */
	protected $actions = null;

	/**
	 * @param FieldList
	 *
	 * @return UserFormsConditionalRuleGenerator
	 */
	public function setFields(FieldList $fields) {
		$this->fields = $fields;
	}

	/**
	 * @param FieldList
	 *
	 * @return UserFormsConditionalRuleGenerator
	 */
	public function setActions(FieldList $actions) {
		$this->actions = $actions;
	}

	/**
	 * Generate the javascript for the conditional field show / hiding logic.
	 *
	 * @return void
	 */
	public function generate() {
		$default = "";
		$rules = "";

		$watch = array();
		$watchLoad = array();

		if($this->fields) {
			foreach($this->fields as $field) {
				$fieldId = $field->Name;
				
				if($field->ClassName == 'EditableFormHeading') { 
					$fieldId = 'Form_Form_'.$field->Name;
				}
				
				// Is this Field Show by Default
				if(!$field->getShowOnLoad()) {
					$default .= "$(\"#" . $fieldId . "\").hide();\n";
				}

				// Check for field dependencies / default
				if($field->Dependencies()) {
					foreach($field->Dependencies() as $dependency) {
						if(is_array($dependency) && isset($dependency['ConditionField']) && $dependency['ConditionField'] != "") {
							// get the field which is effected
							$formName = Convert::raw2sql($dependency['ConditionField']);
							$formFieldWatch = DataObject::get_one("EditableFormField", "\"Name\" = '$formName'");
							
							if(!$formFieldWatch) break;
							
							// watch out for multiselect options - radios and check boxes
							if(is_a($formFieldWatch, 'EditableDropdown')) {
								$fieldToWatch = "$(\"select[name='".$dependency['ConditionField']."']\")";	
								$fieldToWatchOnLoad = $fieldToWatch;
							}
							// watch out for checkboxs as the inputs don't have values but are 'checked
							else if(is_a($formFieldWatch, 'EditableCheckboxGroupField')) {
								$fieldToWatch = "$(\"input[name='".$dependency['ConditionField']."[".$dependency['Value']."]']\")";
								$fieldToWatchOnLoad = $fieldToWatch;
							}
							else if(is_a($formFieldWatch, 'EditableRadioField')) {
								$fieldToWatch = "$(\"input[name='".$dependency['ConditionField']."']\")";
								// We only want to trigger on load once for the radio group - hence we focus on the first option only.
								$fieldToWatchOnLoad = "$(\"input[name='".$dependency['ConditionField']."']:first\")";
							}
							else {
								$fieldToWatch = "$(\"input[name='".$dependency['ConditionField']."']\")";
								$fieldToWatchOnLoad = $fieldToWatch;
							}
							
							// show or hide?
							$view = (isset($dependency['Display']) && $dependency['Display'] == "Hide") ? "hide" : "show";
							$opposite = ($view == "show") ? "hide" : "show";
							
							// what action do we need to keep track of. Something nicer here maybe?
							// @todo encapulsation
							$action = "change";
							
							if($formFieldWatch->ClassName == "EditableTextField") {
								$action = "keyup";
							}
							
							// is this field a special option field
							$checkboxField = false;
							$radioField = false;
							if(in_array($formFieldWatch->ClassName, array('EditableCheckboxGroupField', 'EditableCheckbox'))) {
								$action = "click";
								$checkboxField = true;
							}
							else if ($formFieldWatch->ClassName == "EditableRadioField") {
								$radioField = true;
							}
							
							// Escape the values.
							$dependency['Value'] = str_replace('"', '\"', $dependency['Value']);

							// and what should we evaluate
							switch($dependency['ConditionOption']) {
								case 'IsNotBlank':
									$expression = ($checkboxField || $radioField) ? '$(this).attr("checked")' :'$(this).val() != ""';

									break;
								case 'IsBlank':
									$expression = ($checkboxField || $radioField) ? '!($(this).attr("checked"))' : '$(this).val() == ""';
									
									break;
								case 'HasValue':
									if ($checkboxField) {
										$expression = '$(this).attr("checked")';
									} else if ($radioField) {
										// We cannot simply get the value of the radio group, we need to find the checked option first.
										$expression = '$(this).parents(".field, .control-group").find("input:checked").val()=="'. $dependency['Value'] .'"';
									} else {
										$expression = '$(this).val() == "'. $dependency['Value'] .'"';
									}

									break;
								case 'ValueLessThan':
									$expression = '$(this).val() < parseFloat("'. $dependency['Value'] .'")';
									
									break;
								case 'ValueLessThanEqual':
									$expression = '$(this).val() <= parseFloat("'. $dependency['Value'] .'")';
									
									break;
								case 'ValueGreaterThan':
									$expression = '$(this).val() > parseFloat("'. $dependency['Value'] .'")';

									break;
								case 'ValueGreaterThanEqual':
									$expression = '$(this).val() >= parseFloat("'. $dependency['Value'] .'")';

									break;	
								default: // ==HasNotValue
									if ($checkboxField) {
										$expression = '!$(this).attr("checked")';
									} else if ($radioField) {
										// We cannot simply get the value of the radio group, we need to find the checked option first.
										$expression = '$(this).parents(".field, .control-group").find("input:checked").val()!="'. $dependency['Value'] .'"';
									} else {
										$expression = '$(this).val() != "'. $dependency['Value'] .'"';
									}
								
									break;
							}
	
							if(!isset($watch[$fieldToWatch])) {
								$watch[$fieldToWatch] = array();
							}
							
							$watch[$fieldToWatch][] =  array(
								'expression' => $expression,
								'field_id' => $fieldId,
								'view' => $view,
								'opposite' => $opposite
							);

							$watchLoad[$fieldToWatchOnLoad] = true;
					
						}
					}
				}
			}
		}
		
		if($watch) {
			foreach($watch as $key => $values) {
				$logic = array();

				foreach($values as $rule) {
					// Register conditional behaviour with an element, so it can be triggered from many places.
					$logic[] = sprintf(
						'if(%s) { $("#%s").%s(); } else { $("#%2$s").%s(); }', 
						$rule['expression'], 
						$rule['field_id'], 
						$rule['view'], 
						$rule['opposite']
					);
				}

				$logic = implode("\n", $logic);
				$rules .= $key.".each(function() {\n
	$(this).data('userformConditions', function() {\n
		$logic\n
	}); \n
});\n";

				$rules .= $key.".$action(function() {
	$(this).data('userformConditions').call(this);\n
});\n";
			}
		}

		if($watchLoad) {
			foreach($watchLoad as $key => $value) {
				$rules .= $key.".each(function() {
	$(this).data('userformConditions').call(this);\n
});\n";
			}
		}

		// Only add customScript if $default or $rules is defined
    	if($default  || $rules) {
			Requirements::customScript(<<<JS
				(function($) {
					$(document).ready(function() {
						$default

						$rules
					})
				})(jQuery);
JS
, 'UserFormsConditional');
		}
	}