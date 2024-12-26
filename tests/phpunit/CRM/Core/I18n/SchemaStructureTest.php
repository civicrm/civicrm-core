<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */
/**
 * Class CRM_Core_I18n_SchemaTest
 * @group headless
 * @group locale
 */
class CRM_Core_I18n_SchemaStructureTest extends CiviUnitTestCase {

  public function testSchemaStructure(): void {
    $columns = CRM_Core_I18n_SchemaStructure::columns();
    $this->assertStringContainsString("varchar(64) NOT NULL", $columns['civicrm_location_type']['display_name']);

    $indices = CRM_Core_I18n_SchemaStructure::indices();
    $this->assertEquals([
      'name' => 'UI_title_extends',
      'field' => ['title', 'extends'],
      'unique' => 1,
    ], $indices['civicrm_custom_group']['UI_title_extends']);

    $tables = CRM_Core_I18n_SchemaStructure::tables();
    $this->assertContains('civicrm_relationship_type', $tables);

    // This function is pretty stupid but we'll test it anyway.
    // Feel free to remove or update this part of the test if updating the function to be less stupid.
    $widgets = CRM_Core_I18n_SchemaStructure::widgets();
    $this->assertEquals(['type' => "Text", 'required' => "true"], $widgets['civicrm_location_type']['display_name']);
  }

}
