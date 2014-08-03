<?php

/**
 * An extension which applied to an object handles having submissions attached
 * to it.
 *
 * @package userforms
 */
class UserFormSubmissionsExtension extends DataExtension {
	
	private static $has_many = array(
		"Submissions" => "SubmittedForm"
	);

	public function updateCMSFields(FieldList $fields) {
		$fields->findOrMakeTab(
			'Root.Submissions', 
			_t('UserDefinedForm.SUBMISSIONS', 'Submissions')
		);

		$columns = $this->getAllSubmissionColumns();

		// attach every column to the print view form 
		$columns['Created'] = 'Created';

		// view the submissions
		$submissions = new GridField(
			"Submissions", 
			_t('UserDefinedForm.SUBMISSIONS', 'Submissions'),
			$this->owner->Submissions()->sort('Created', 'DESC')
		);

		$parentID = (!empty($this->ID)) ? $this->ID : 0;

		$config = new GridFieldConfig();
		$config->addComponent(new GridFieldToolbarHeader());
		$config->addComponent($sort = new GridFieldSortableHeader());
		$config->addComponent($filter = new UserFormsGridFieldFilterHeader());
		$config->addComponent(new GridFieldDataColumns());
		$config->addComponent(new GridFieldEditButton());
		$config->addComponent(new GridState_Component());
		$config->addComponent(new GridFieldDeleteAction());
		$config->addComponent($pagination = new GridFieldPaginator(25));
		$config->addComponent(new GridFieldDetailForm());
		$config->addComponent($export = new GridFieldExportButton());
		$config->addComponent($print = new GridFieldPrintButton());
		$config->addComponent(new GridFieldBulkManager());
		
		$sort->setThrowExceptionOnBadDataType(false);
		$filter->setThrowExceptionOnBadDataType(false);
		$pagination->setThrowExceptionOnBadDataType(false);
		$filter->setColumns($columns);
			
		// print configuration
		$print->setPrintHasHeader(true);
		$print->setPrintColumns($columns);

		// export configuration
		$export->setCsvHasHeader(true);
		$export->setExportColumns($columns);

		$submissions->setConfig($config);

		$fields->addFieldToTab('Root.Submissions', $submissions);
	}

	/**
	 * @return array
	 */
	protected function getAllSubmissionColumns() {
		$parentID = $this->owner->ID;

		$columnSQL = <<<SQL
SELECT "Name", "Title"
FROM "SubmittedFormField"
LEFT JOIN "SubmittedForm" ON "SubmittedForm"."ID" = "SubmittedFormField"."ParentID"
WHERE "SubmittedForm"."ParentID" = '$parentID'
ORDER BY "Title" ASC
SQL;
		return DB::query($columnSQL)->map();
	}
}