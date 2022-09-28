<?php

/**
 * Class CRM_UF_Page_ProfileEditorTest
 * @group headless
 */
class CRM_UF_Page_ProfileEditorTest extends CiviUnitTestCase {

  /**
   * Spot check a few fields that should appear in schema.
   */
  public function testGetSchema() {
    $schema = CRM_UF_Page_ProfileEditor::getSchema(['IndividualModel', 'ActivityModel']);
    foreach ($schema as $entityName => $entityDef) {
      foreach ($entityDef['schema'] as $fieldName => $fieldDef) {
        $this->assertNotEmpty($fieldDef['type']);
        $this->assertNotEmpty($fieldDef['title']);
        $this->assertNotEmpty($fieldDef['civiFieldType']);
      }
    }

    $this->assertEquals('Individual', $schema['IndividualModel']['schema']['first_name']['civiFieldType']);
    $this->assertTrue(empty($schema['IndividualModel']['schema']['first_name']['civiIsLocation']));
    $this->assertTrue(empty($schema['IndividualModel']['schema']['first_name']['civiIsPhone']));

    $this->assertEquals('Contact', $schema['IndividualModel']['schema']['street_address']['civiFieldType']);
    $this->assertNotEmpty($schema['IndividualModel']['schema']['street_address']['civiIsLocation']);
    $this->assertTrue(empty($schema['IndividualModel']['schema']['street_address']['civiIsPhone']));

    $this->assertEquals('Contact', $schema['IndividualModel']['schema']['phone_and_ext']['civiFieldType']);
    $this->assertNotEmpty($schema['IndividualModel']['schema']['phone_and_ext']['civiIsLocation']);
    $this->assertNotEmpty($schema['IndividualModel']['schema']['phone_and_ext']['civiIsPhone']);

    $this->assertEquals('Activity', $schema['ActivityModel']['schema']['activity_subject']['civiFieldType']);
    $this->assertTrue(empty($schema['ActivityModel']['schema']['activity_subject']['civiIsLocation']));
    $this->assertTrue(empty($schema['ActivityModel']['schema']['activity_subject']['civiIsPhone']));

    // don't mix up contacts and activities
    $this->assertTrue(empty($schema['IndividualModel']['schema']['activity_subject']));
    $this->assertTrue(empty($schema['ActivityModel']['schema']['street_address']));

  }

  /**
   * Test that with an extension adding in UF Fields for an enttiy that isn't supplied by Core e.g. Grant
   * That an appropriate entitytype can be specfied in the backbone.marionette profile editor e.g. GrantModel
   */
  public function testGetSchemaWithHooks() {
    CRM_Utils_Hook::singleton()->setHook('civicrm_alterUFFields', [$this, 'hook_civicrm_alterUFFIelds']);
    $schema = CRM_UF_Page_ProfileEditor::getSchema(['IndividualModel', 'GrantModel']);
    $this->assertEquals('Grant', $schema['GrantModel']['schema']['grant_decision_date']['civiFieldType']);
  }

  /**
   * Tries to load up the profile schema for a model where there is no corresponding set of fields avaliable.
   */
  public function testGetSchemaWithHooksWithInvalidModel() {
    $this->expectException(CRM_Core_Exception::class);
    CRM_Utils_Hook::singleton()->setHook('civicrm_alterUFFields', [$this, 'hook_civicrm_alterUFFIelds']);
    $schema = CRM_UF_Page_ProfileEditor::getSchema(['IndividualModel', 'GrantModel', 'PledgeModel']);
  }

  public function hook_civicrm_alterUFFIelds(&$fields) {
    $fields['Grant'] = CRM_Grant_BAO_Grant::exportableFields();
  }

}
