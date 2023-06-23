<?php

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class CRM_Core_InnoDBIndexerTest
 * @group headless
 */
class CRM_Core_InnoDBIndexerTest extends TestCase implements HeadlessInterface, HookInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and
   * sqlFile(). See:
   * https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless(): Test\CiviEnvBuilder {
    return Test::headless()
      ->install(['legacycustomsearches'])
      ->apply();
  }

  /**
   * Indices to be created or removed.
   *
   * @var array
   */
  protected $indices = [];

  /**
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $idx = new CRM_Core_InnoDBIndexer(FALSE, []);
    foreach (array_keys($this->indices) as $table) {
      foreach ($idx->dropIndexSql($table) as $sql) {
        CRM_Core_DAO::executeQuery($sql);
      }
    }
    $this->assertFullTextIndexesNotPresent();
    parent::tearDown();
  }

  public function testHasDeclaredIndex(): void {
    $this->indices = [
      'civicrm_contact' => [
        ['first_name', 'last_name'],
        ['foo'],
      ],
      'civicrm_email' => [
        ['whiz'],
      ],
    ];
    $idx = new CRM_Core_InnoDBIndexer(TRUE, $this->indices);

    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['first_name', 'last_name']));
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['last_name', 'first_name']));
    // not sure if this is right behavior
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['first_name']));
    // not sure if this is right behavior
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['last_name']));
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_contact', ['foo']));
    $this->assertFalse($idx->hasDeclaredIndex('civicrm_contact', ['whiz']));

    $this->assertFalse($idx->hasDeclaredIndex('civicrm_email', ['first_name', 'last_name']));
    $this->assertFalse($idx->hasDeclaredIndex('civicrm_email', ['foo']));
    $this->assertTrue($idx->hasDeclaredIndex('civicrm_email', ['whiz']));
  }

  /**
   * When disabled, there is no FTS index, so queries that rely on FTS index fail.
   */
  public function testDisabled(): void {
    $this->indices = [
      'civicrm_contact' => [
        ['first_name', 'last_name'],
      ],
    ];
    $idx = new CRM_Core_InnoDBIndexer(FALSE, $this->indices);
    $idx->fixSchemaDifferences();

    try {
      CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_contact WHERE MATCH(first_name,last_name) AGAINST ("joe")');
      $this->fail('Missed expected exception');
    }
    catch (Exception $e) {
      $this->assertTrue(TRUE, 'Received expected exception');
    }
  }

  /**
   * When enabled, the FTS index is created, so queries that rely on FTS work.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testEnabled(): void {
    $this->indices = [
      'civicrm_contact' => [
        ['first_name', 'last_name'],
      ],
    ];
    $idx = new CRM_Core_InnoDBIndexer(TRUE, $this->indices);
    $idx->fixSchemaDifferences();
    CRM_Core_DAO::executeQuery('SELECT id FROM civicrm_contact WHERE MATCH(first_name,last_name) AGAINST ("joe")');
  }

  /**
   * Assert that all indices have been removed.
   *
   * @throws \CRM_Core_Exception
   */
  protected function assertFullTextIndexesNotPresent(): void {
    $this->assertEmpty(CRM_Core_DAO::singleValueQuery("
  SELECT GROUP_CONCAT(CONCAT(table_name, ' ', index_name))
  FROM information_Schema.STATISTICS
  WHERE table_schema = DATABASE()
    AND index_type = 'FULLTEXT'"), 'Full text indices should have been removed');
  }

}
