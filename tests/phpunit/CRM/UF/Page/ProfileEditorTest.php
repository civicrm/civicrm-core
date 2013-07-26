<?php
require_once 'CiviTest/CiviUnitTestCase.php';

class CRM_UF_Page_ProfileEditorTest extends CiviUnitTestCase {
  //@todo make BAO enotice compliant  & remove the line below
  // WARNING - NEVER COPY & PASTE $_eNoticeCompliant = FALSE
  // new test classes should be compliant.
  public $_eNoticeCompliant = FALSE;
  function setUp() {
    parent::setUp();
  }

  /**
   * Spot check a few fields that should appear in schema
   */
  function testGetSchema() {
    $schema = CRM_UF_Page_ProfileEditor::getSchema(array('IndividualModel', 'ActivityModel'));
    foreach ($schema as $entityName => $entityDef) {
      foreach ($entityDef['schema'] as $fieldName => $fieldDef) {
        $this->assertNotEmpty($fieldDef['type']);
        $this->assertNotEmpty($fieldDef['title']);
        $this->assertNotEmpty($fieldDef['civiFieldType']);
      }
    }

    $this->assertEquals('Individual', $schema['IndividualModel']['schema']['first_name']['civiFieldType']);
    $this->assertEmpty($schema['IndividualModel']['schema']['first_name']['civiIsLocation']);
    $this->assertEmpty($schema['IndividualModel']['schema']['first_name']['civiIsPhone']);

    $this->assertEquals('Contact', $schema['IndividualModel']['schema']['street_address']['civiFieldType']);
    $this->assertNotEmpty($schema['IndividualModel']['schema']['street_address']['civiIsLocation']);
    $this->assertEmpty($schema['IndividualModel']['schema']['street_address']['civiIsPhone']);

    $this->assertEquals('Contact', $schema['IndividualModel']['schema']['phone_and_ext']['civiFieldType']);
    $this->assertNotEmpty($schema['IndividualModel']['schema']['phone_and_ext']['civiIsLocation']);
    $this->assertNotEmpty($schema['IndividualModel']['schema']['phone_and_ext']['civiIsPhone']);

    $this->assertEquals('Activity', $schema['ActivityModel']['schema']['activity_subject']['civiFieldType']);
    $this->assertEmpty($schema['ActivityModel']['schema']['activity_subject']['civiIsLocation']);
    $this->assertEmpty($schema['ActivityModel']['schema']['activity_subject']['civiIsPhone']);

    // don't mix up contacts and activities
    $this->assertEmpty($schema['IndividualModel']['schema']['activity_subject']);
    $this->assertEmpty($schema['ActivityModel']['schema']['street_address']);

  }
}
