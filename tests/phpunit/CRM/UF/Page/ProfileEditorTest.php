<?php

/**
 * Class CRM_UF_Page_ProfileEditorTest
 * @group headless
 */
class CRM_UF_Page_ProfileEditorTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Spot check a few fields that should appear in schema.
   */
  public function testGetSchema() {
    $schema = CRM_UF_Page_ProfileEditor::getSchema(array('IndividualModel', 'ActivityModel'));
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

}
