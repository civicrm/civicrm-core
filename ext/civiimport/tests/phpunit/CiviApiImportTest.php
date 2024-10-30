<?php

use Civi\Api4\Import;
use Civi\Api4\UserJob;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\CiviEnvBuilder;
use PHPUnit\Framework\TestCase;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test class.
 *    Simply create corresponding functions (e.g. "hook_civicrm_post(...)" or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or test****() functions will
 *    rollback automatically -- as long as you don't manipulate schema or truncate tables.
 *    If this test needs to manipulate schema or truncate tables, then either:
 *       a. Do all that using setupHeadless() and Civi\Test.
 *       b. Disable TransactionalInterface, and handle all setup/teardown yourself.
 *
 * @group headless
 */
class CiviApiImportTest extends TestCase implements HeadlessInterface, HookInterface {

  /**
   * Setup used when HeadlessInterface is implemented.
   *
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   *
   * @link https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   *
   * @return \Civi\Test\CiviEnvBuilder
   *
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install('org.civicrm.search_kit')
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function tearDown():void {
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS abc');
    parent::tearDown();
  }

  /**
   * Test the CRUD api actions work for the Import classes.
   *
   * @throws \CRM_Core_Exception
   */
  public function testApiActions():void {
    $this->createUserJobTable();
    $userJobParameters = [
      'metadata' => [
        'DataSource' => ['table_name' => 'abc', 'column_headers' => ['External Identifier', 'Amount Given', 'Contribution Date', 'Financial Type', 'In honor']],
        'submitted_values' => [
          'contactType' => 'Individual',
          'contactSubType' => '',
          'dataSource' => 'CRM_Import_DataSource_SQL',
          'onDuplicate' => CRM_Import_Parser::DUPLICATE_SKIP,
          'dedupe_rule_id' => NULL,
          'dateFormats' => CRM_Utils_Date::DATE_yyyy_mm_dd,
        ],
        'import_mappings' => [
          ['name' => 'external_identifier'],
          ['name' => 'total_amount'],
          ['name' => 'receive_date'],
          ['name' => 'financial_type_id'],
          [],
        ],
      ],
      'status_id:name' => 'draft',
      'job_type' => 'contribution_import',
    ];
    $userJobID = UserJob::create()->setValues($userJobParameters)->execute()->first()['id'];
    $importFields = Import::getFields($userJobID)->execute();
    $this->assertEquals('abc', $importFields[0]['table_name']);
    $this->assertEquals('_id', $importFields[0]['column_name']);
    $this->assertEquals('amount_given', $importFields[4]['column_name']);
    $this->assertEquals('abc', $importFields[4]['table_name']);

    Import::create($userJobID)->setValues([
      'external_identifier' => 678,
      'amount_given' => 80,
      'receive_date' => NULL,
      '_status' => 'NEW',
      'soft_credit_to' => '',
    ])->execute();

    $import = Import::get($userJobID)->setSelect(['external_identifier', 'amount_given', '_status'])->execute()->first();
    $rowID = $import['_id'];
    $this->assertEquals('80', $import['amount_given']);

    Import::update($userJobID)->setValues([
      'amount_given' => NULL,
      '_id' => $rowID,
      '_status' => 'IMPORTED',
    ])->execute();

    $import = Import::get($userJobID)->setSelect(['external_identifier', 'amount_given', '_status'])->execute()->first();
    $this->assertEquals(NULL, $import['amount_given']);

    Import::save($userJobID)->setRecords([
      [
        'external_identifier' => 999,
        'amount_given' => 9,
        '_status' => 'ERROR',
        '_id' => $rowID,
      ],
    ])->execute();

    $import = Import::get($userJobID)->setSelect(['external_identifier', 'amount_given', '_status'])->addWhere('_id', '=', $rowID)->execute()->first();
    $this->assertEquals(9, $import['amount_given']);

    Import::save($userJobID)->setRecords([
      [
        'external_identifier' => 777,
        '_id' => $rowID,
        '_status' => 'ERROR',
      ],
    ])->execute();

    $import = Import::get($userJobID)->setSelect(['external_identifier', 'amount_given', '_status'])->addWhere('_id', '=', $rowID)->execute()->first();
    $this->assertEquals(777, $import['external_identifier']);

    $validate = Import::validate($userJobID)->addWhere('_id', '=', $rowID)->setLimit(1)->execute()->first();
    $this->assertEquals('Missing required fields: Contribution ID OR Invoice Reference OR Transaction ID OR Financial Type ID', $validate['_status_message']);
    $this->assertEquals('ERROR', $validate['_status']);

    Import::update($userJobID)->setValues(['financial_type' => 'Donation'])->addWhere('_id', '=', $rowID)->execute();
    $validate = Import::validate($userJobID)->addWhere('_id', '=', $rowID)->setLimit(1)->execute()->first();
    $this->assertEquals('', $validate['_status_message']);
    $this->assertEquals('VALID', $validate['_status']);
    $imported = Import::import($userJobID)->addWhere('_id', '=', $rowID)->setLimit(1)->execute()->first();
    $this->assertEquals('ERROR', $imported['_status']);
    $this->assertEquals('No matching Contact found', $imported['_status_message']);

    // Update the table with a new table name & check the api still works.
    // This relies on the change in table name being detected & caches being
    // flushed.
    CRM_Core_DAO::executeQuery('DROP TABLE abc');
    $this->createUserJobTable('xyz');
    $userJobParameters['metadata']['DataSource']['table_name'] = 'xyz';
    UserJob::update(FALSE)->addWhere('id', '=', $userJobID)->setValues($userJobParameters)->execute();
    // This is our new table, with nothing in it, but we if we api-call & don't get an exception so we are winning.
    Import::get($userJobID)->setSelect(['external_identifier', 'amount_given', '_status'])->addWhere('_id', '=', $rowID)->execute()->first();
  }

  /**
   * Create a table for our Import api.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  private function createUserJobTable($tableName = 'abc'): void {
    CRM_Core_DAO::executeQuery("CREATE TABLE IF NOT EXISTS `" . $tableName . "` (
      `external_identifier` text DEFAULT NULL,
      `amount_given` text DEFAULT NULL,
      `receive_date` text DEFAULT NULL,
      `financial_type` text DEFAULT NULL,
      `soft_credit_to` text DEFAULT NULL,
      `_entity_id` int(11) DEFAULT NULL,
      `_status` varchar(32) NOT NULL DEFAULT 'NEW',
      `_status_message` longtext DEFAULT NULL,
      `_id` int(11) NOT NULL AUTO_INCREMENT,
      PRIMARY KEY (`_id`),
      KEY `_id` (`_id`),
      KEY `_status` (`_status`)
    ) ENGINE=InnoDB");
  }

}
