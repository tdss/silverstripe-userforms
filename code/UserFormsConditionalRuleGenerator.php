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
	 * @param SS_List
	 *
	 * @return UserFormsConditionalRuleGenerator
	 */
	public function setFields($fields) {
		$this->fields = $fields;
	}

	/**
	 * @param SS_List
	 *
	 * @return UserFormsConditionalRuleGenerator
	 */
	public function setActions($actions) {
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
				$fieldId = 'UserForm_Form_' .$field->Name;
				
				if($field->HideOnLoad) {
					$default .= "$(\"#" . $fieldId . "_Holder\").hide();\n";
				}

				// Check for field dependencies / default
				if($field->CustomRules()) {
					foreach($field->CustomRules() as $dependency) {
						if($formFieldWatch = $dependency->ConditionField()) {
							if(is_a($formFieldWatch, 'EditableDropdown')) {
								$fieldToWatch = "$(\"select[name='".$formFieldWatch->Name."']\")";	

								$fieldToWatchOnLoad = $fieldToWatch;
							}
							// watch out for checkboxs as the inputs don't have values but are 'checked
							else if(is_a($formFieldWatch, 'EditableCheckboxGroupField')) {
								$fieldToWatch = "$(\"input[name='".$formFieldWatch->Name."[".$dependency->FieldValue."]']\")";

								$fieldToWatchOnLoad = $fieldToWatch;
							}
							else if(is_a($formFieldWatch, 'EditableRadioField')) {
								$fieldToWatch = "$(\"input[name='".$formFieldWatch->Name."']\")";
								// We only want to trigger on load once for the radio group - hence we focus on the first option only.
								$fieldToWatchOnLoad = "$(\"input[name='".$formFieldWatch->Name."']:first\")";
							}
							else {
								$fieldToWatch = "$(\"input[name='".$formFieldWatch->Name."']\")";
								$fieldToWatchOnLoad = $fieldToWatch;
							}
							
							// show or hide?
							$view = strtolower($dependency->Display);
							$opposite = ($view == "show") ? "hide" : "show";
				
							$action = "change";
							
							if(is_a($formFieldWatch, "EditableTextField")) {
								$action = "keyup";
							}
							
							// is this field a special option field
							$checkboxField = false;
							$radioField = false;

							if(in_array($formFieldWatch->ClassName, array('EditableCheckboxGroupField', 'EditableCheckbox'))) {
								$action = "click";
								$checkboxField = true;
							} else if ($formFieldWatch->ClassName == "EditableRadioField") {
								$radioField = true;
							}
							
							// Escape the values.
							$value = str_replace('"', '\"', $dependency->FieldValue);

							// and what should we evaluate
							switch($dependency->ConditionOption) {
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
										$expression = '$(this).parents(".field, .control-group").find("input:checked").val()=="'. $dependency->FieldValue .'"';
									} else {
										$expression = '$(this).val() == "'. $dependency->FieldValue .'"';
									}

									break;
								case 'ValueLessThan':
									$expression = '$(this).val() < parseFloat("'. $dependency->FieldValue .'")';
									
									break;
								case 'ValueLessThanEqual':
									$expression = '$(this).val() <= parseFloat("'. $dependency->FieldValue .'")';
									
									break;
								case 'ValueGreaterThan':
									$expression = '$(this).val() > parseFloat("'. $dependency->FieldValue .'")';

									break;
								case 'ValueGreaterThanEqual':
									$expression = '$(this).val() >= parseFloat("'. $dependency->FieldValue .'")';

									break;	
								default: // ==HasNotValue
									if ($checkboxField) {
										$expression = '!$(this).attr("checked")';
									} else if ($radioField) {
										// We cannot simply get the value of the radio group, we need to find the checked option first.
										$expression = '$(this).parents(".field, .control-group").find("input:checked").val()!="'. $dependency->FieldValue .'"';
									} else {
										$expression = '$(this).val() != "'. $dependency->FieldValue .'"';
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
						'if(%s) { $("#%s_Holder").%s(); } else { $("#%2$s_Holder").%s(); }', 
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
}