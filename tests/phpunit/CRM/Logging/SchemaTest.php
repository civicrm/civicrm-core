<?php

/**
 * Class CRM_Logging_SchmeaTest
 * @group headless
 */
class CRM_Logging_SchemaTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  public function queryExamples() {
    $examples = [];
    $examples[] = ["`modified_date` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'When the mailing (or closely related entity) was created or modified or deleted.'", "`modified_date` timestamp NULL  COMMENT 'When the mailing (or closely related entity) was created or modified or deleted.'"];
    $examples[] = ["`modified_date` timestamp NULL DEFAULT current_timestamp ON UPDATE current_timestamp COMMENT 'When the mailing (or closely related entity) was created or modified or deleted.'", "`modified_date` timestamp NULL  COMMENT 'When the mailing (or closely related entity) was created or modified or deleted.'"];
    return $examples;
  }

  /**
   * Tests the function fixTimeStampAndNotNullSQL in CRM_Logging_Schema
   *
   * @dataProvider queryExamples
   */
  public function testQueryRewrite($query, $expectedQuery) {
    $this->assertEquals($expectedQuery, CRM_Logging_Schema::fixTimeStampAndNotNullSQL($query));
  }

  public function testLogEngine() {
    $schema = new CRM_Logging_Schema();
    $schema->enableLogging();
    $log_table = CRM_Core_DAO::executeQuery("SHOW CREATE TABLE log_civicrm_acl");
    while ($log_table->fetch()) {
      $this->assertRegexp('/ENGINE=ARCHIVE/', $log_table->Create_Table);
    }
    $schema->disableLogging();
    $schema->dropAllLogTables();
  }

}
