<?php

/**
 * @package userforms
 * @subpackage tests
 */

class EditableFormFieldTest extends FunctionalTest {
	
	protected static $fixture_file = 'userforms/tests/EditableFormFieldTest.yml';

	protected $extraDataObjects = array(

	);
	
	public function testFormFieldPermissions() {
		$this->markTestIncomplete();
	}
	
	public function testGettingAndSettingSettings() {
		$text = $this->objFromFixture('EditableTextField', 'basic-text');
		
		$this->logInWithPermission('ADMIN');
				
		$this->assertEquals($text->getSettings(), array());
		$text->setSetting('Test', 'Value');
		$text->write();

		$this->assertEquals($text->getSetting('Test'), 'Value');
		$this->assertEquals($text->getSettings(), array('Test' => 'Value'));
		
		$text->setSetting('Foo', 'Bar');
		$text->write();
		
		$this->assertEquals($text->getSetting('Foo'), 'Bar');
		$this->assertEquals($text->getSettings(), array(
			'Test' => 'Value', 
			'Foo' => 'Bar'
		));
		
		$text->setSetting('Foo', 'Baz');
		$text->write();
		
		$this->assertEquals($text->getSetting('Foo'), 'Baz');
		$this->assertEquals($text->getSettings(), array(
			'Test' => 'Value', 'Foo' => 'Baz'
		));
	}

	public function testGetCMSFields() {
		$this->markTestIncomplete();
	}
}
