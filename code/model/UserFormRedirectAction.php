<?php

class UserFormRedirectAction extends UserFormProcessAction {
	
	public function getCMSFields() {
		$onCompleteFieldSet = new CompositeField(
			$label = new LabelField('OnCompleteMessageLabel',_t('UserDefinedForm.ONCOMPLETELABEL', 'Show on completion')),
			$editor = new HtmlEditorField( "OnCompleteMessage", "", _t('UserDefinedForm.ONCOMPLETEMESSAGE', $this->OnCompleteMessage))
		);

		$onCompleteFieldSet->addExtraClass('field');
		
		$editor->setRows(3);
		$label->addExtraClass('left');		

	}
}