<?php

use Civi\Test;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use PHPUnit\Framework\TestCase;

/**
 * FIXME - Add test description.
 *
 * Tips:
 *  - With HookInterface, you may implement CiviCRM hooks directly in the test
 * class. Simply create corresponding functions (e.g. "hook_civicrm_post(...)"
 * or similar).
 *  - With TransactionalInterface, any data changes made by setUp() or
 * test****() functions will rollback automatically -- as long as you don't
 * manipulate schema or truncate tables. If this test needs to manipulate
 * schema or truncate tables, then either: a. Do all that using setupHeadless()
 * and Civi\Test. b. Disable TransactionalInterface, and handle all
 * setup/teardown yourself.
 *
 * @group headless
 */
class CRM_Utils_QueryFormatterTest extends TestCase implements HeadlessInterface, HookInterface {

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

  public function createExampleTable() {
    CRM_Core_DAO::executeQuery('
      DROP TABLE IF EXISTS civicrm_fts_example
    ');
    CRM_Core_DAO::executeQuery('
      CREATE TABLE civicrm_fts_example (
        id int(10) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
        PRIMARY KEY (id)
      )
    ');
    $idx = new CRM_Core_InnoDBIndexer(self::supportsFts(), [
      'civicrm_contact' => [
        ['first_name', 'last_name'],
      ],
    ]);
    $idx->fixSchemaDifferences();
    $rows = [
      [1, 'someone@example.com'],
      [2, 'this is someone@example.com!'],
      [3, 'first second'],
      [4, 'zeroth first second'],
      [5, 'zeroth first second third'],
      [6, 'never say never'],
      [7, 'first someone@example.com second'],
      [8, 'first someone'],
      [9, 'firstly someone'],
    ];
    foreach ($rows as $row) {
      CRM_Core_DAO::executeQuery('INSERT INTO civicrm_fts_example (id,name) VALUES (%1, %2)',
        [
          1 => [$row[0], 'Int'],
          2 => [$row[1], 'String'],
        ]);
    }
  }

  public function tearDown(): void {
    parent::tearDown();
    $idx = new CRM_Core_InnoDBIndexer(FALSE, [
      'civicrm_contact' => [
        ['first_name', 'last_name'],
      ],
    ]);
    $idx->fixSchemaDifferences();
  }

  public static function tearDownAfterClass(): void {
    CRM_Core_DAO::executeQuery('DROP TABLE IF EXISTS civicrm_fts_example');
    parent::tearDownAfterClass();
  }

  /**
   * Generate data for tests to iterate through.
   *
   * Note: These examples are not locked in stone -- but do exercise
   * discretion in revising them!
   *
   * @return array
   */
  public function dataProvider() {
    // Array(0=>$inputText, 1=>$language, 2=>$options, 3=>$expectedText, 4=>$matchingIds).
    $cases = [];

    $allEmailRows = [1, 2, 7];

    $cases[] = ['someone@example.com', 'like', 'simple', '%someone@example.com%', $allEmailRows];
    $cases[] = ['someone@example.com', 'like', 'phrase', '%someone@example.com%', $allEmailRows];
    $cases[] = ['someone@example.com', 'like', 'wildphrase', '%someone@example.com%', $allEmailRows];
    $cases[] = ['someone@example.com', 'like', 'wildwords', '%someone@example.com%', $allEmailRows];
    $cases[] = ['someone@example.com', 'like', 'wildwords-suffix', '%someone@example.com%', $allEmailRows];

    $cases[] = ['someone@example.com', 'fts', 'simple', 'someone@example.com', $allEmailRows];
    $cases[] = ['someone@example.com', 'fts', 'phrase', '"someone@example.com"', $allEmailRows];
    $cases[] = ['someone@example.com', 'fts', 'wildphrase', '"*someone@example.com*"', $allEmailRows];
    $cases[] = ['someone@example.com', 'fts', 'wildwords', '*someone* *example*', $allEmailRows];
    $cases[] = ['someone@example.com', 'fts', 'wildwords-suffix', 'someone* example*', $allEmailRows];

    $cases[] = ['someone@example.com', 'ftsbool', 'simple', '+"someone" +"example"', $allEmailRows];
    $cases[] = ['someone@example.com', 'ftsbool', 'phrase', '+"someone@example.com"', $allEmailRows];
    $cases[] = ['someone@example.com', 'ftsbool', 'wildphrase', '+"*someone@example.com*"', $allEmailRows];
    $cases[] = ['someone@example.com', 'ftsbool', 'wildwords', '+*someone* +*example*', $allEmailRows];
    $cases[] = ['someone@example.com', 'ftsbool', 'wildwords-suffix', '+someone* +example*', $allEmailRows];

    $cases[] = ['first second', 'like', 'simple', '%first second%', [3, 4, 5]];
    $cases[] = ['first second', 'like', 'phrase', '%first second%', [3, 4, 5]];
    $cases[] = ['first second', 'like', 'wildphrase', '%first second%', [3, 4, 5]];
    $cases[] = ['first second', 'like', 'wildwords', '%first%second%', [3, 4, 5, 7]];
    $cases[] = ['first second', 'like', 'wildwords-suffix', '%first%second%', [3, 4, 5, 7]];

    $cases[] = ['first second', 'fts', 'simple', 'first second', [3, 4, 5]];
    $cases[] = ['first second', 'fts', 'phrase', '"first second"', [3, 4, 5]];
    $cases[] = ['first second', 'fts', 'wildphrase', '"*first second*"', [3, 4, 5]];
    $cases[] = ['first second', 'fts', 'wildwords', '*first* *second*', [3, 4, 5, 7]];
    $cases[] = ['first second', 'fts', 'wildwords-suffix', 'first* second*', [3, 4, 5, 7]];

    $cases[] = ['first second', 'ftsbool', 'simple', '+"first" +"second"', [3, 4, 5]];
    $cases[] = ['first second', 'ftsbool', 'phrase', '+"first second"', [3, 4, 5]];
    $cases[] = ['first second', 'ftsbool', 'wildphrase', '+"*first second*"', [3, 4, 5]];
    $cases[] = ['first second', 'ftsbool', 'wildwords', '+*first* +*second*', [3, 4, 5, 7]];
    $cases[] = ['first second', 'ftsbool', 'wildwords-suffix', '+first* +second*', [3, 4, 5, 7]];

    $cases[] = ['first second', 'solr', 'simple', 'first second', NULL];
    $cases[] = ['first second', 'solr', 'phrase', '"first second"', NULL];
    $cases[] = ['first second', 'solr', 'wildphrase', '"*first second*"', NULL];
    $cases[] = ['first second', 'solr', 'wildwords', '*first* *second*', NULL];
    $cases[] = ['first second', 'solr', 'wildwords-suffix', 'first* second*', NULL];

    $cases[] = ['someone@', 'ftsbool', 'simple', '+"someone"', $allEmailRows];
    $cases[] = ['@example.com', 'ftsbool', 'simple', '+"example.com"', $allEmailRows];

    // If user supplies wildcards, then ignore mode.
    foreach ([
      'simple',
      'wildphrase',
      'wildwords',
      'wildwords-suffix',
    ] as $mode) {
      $cases[] = ['first% second', 'like', $mode, 'first% second', [3, 7]];
      $cases[] = ['first% second', 'fts', $mode, 'first* second', [3, 7]];
      $cases[] = ['first% second', 'ftsbool', $mode, '+first* +second', [3, 7]];
      $cases[] = ['first% second', 'solr', $mode, 'first* second', NULL];
      $cases[] = ['first second%', 'like', $mode, 'first second%', [3]];
      $cases[] = ['first second%', 'fts', $mode, 'first second*', [3]];
      $cases[] = ['first second%', 'ftsbool', $mode, '+first +second*', [3]];
      $cases[] = ['first second%', 'solr', $mode, 'first second*', NULL];
    }

    return $cases;
  }

  /**
   * Test format.
   *
   * @param string $text
   * @param string $language
   * @param string $mode
   * @param string $expectedText
   * @param array|NULL $expectedRowIds
   *
   * @dataProvider dataProvider
   */
  public function testFormat($text, $language, $mode, $expectedText, $expectedRowIds) {
    $formatter = new CRM_Utils_QueryFormatter($mode);
    $actualText = $formatter->format($text, $language);
    $this->assertEquals($expectedText, $actualText);

    if ($expectedRowIds !== NULL) {
      if ($language === 'like') {
        $this->createExampleTable();
        $this->assertSqlIds($expectedRowIds, "SELECT id FROM civicrm_fts_example WHERE " . $formatter->formatSql('civicrm_fts_example', 'name', $text));
      }
      elseif (in_array($language, ['fts', 'ftsbool'])) {
        if ($this->supportsFts()) {
          $this->createExampleTable();
          $this->assertSqlIds($expectedRowIds, "SELECT id FROM civicrm_fts_example WHERE " . $formatter->formatSql('civicrm_fts_example', 'name', $text));
        }
      }
      elseif ($language === 'solr') {
        // Skip. Don't have solr test harness.
      }
      else {
        $this->fail("Cannot asset expectedRowIds with unrecognized language $language");
      }
    }
  }

  public static function supportsFts() {
    return version_compare(CRM_Core_DAO::singleValueQuery('SELECT VERSION()'), '5.6.0', '>=');
  }

  /**
   * @param array $expectedRowIds
   * @param string $sql
   */
  private function assertSqlIds($expectedRowIds, $sql) {
    $actualRowIds = CRM_Utils_Array::collect('id',
      CRM_Core_DAO::executeQuery($sql)->fetchAll());
    sort($actualRowIds);
    sort($expectedRowIds);
    $this->assertEquals($expectedRowIds, $actualRowIds);
  }

}
