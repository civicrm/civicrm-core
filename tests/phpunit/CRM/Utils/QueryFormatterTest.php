<?php

/**
 * Class CRM_Utils_QueryFormatterTest
 * @group headless
 */
class CRM_Utils_QueryFormatterTest extends CiviUnitTestCase {

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
    $idx = new CRM_Core_InnoDBIndexer(self::supportsFts(), array(
      'civicrm_contact' => array(
        array('first_name', 'last_name'),
      ),
    ));
    $idx->fixSchemaDifferences();
    $rows = array(
      array(1, 'someone@example.com'),
      array(2, 'this is someone@example.com!'),
      array(3, 'first second'),
      array(4, 'zeroth first second'),
      array(5, 'zeroth first second third'),
      array(6, 'never say never'),
      array(7, 'first someone@example.com second'),
      array(8, 'first someone'),
      array(9, 'firstly someone'),
    );
    foreach ($rows as $row) {
      CRM_Core_DAO::executeQuery("INSERT INTO civicrm_fts_example (id,name) VALUES (%1, %2)",
        array(
          1 => array($row[0], 'Int'),
          2 => array($row[1], 'String'),
        ));
    }
  }

  public static function tearDownAfterClass() {
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
    $cases = array();

    $allEmailRows = array(1, 2, 7);

    $cases[] = array('someone@example.com', 'like', 'simple', '%someone@example.com%', $allEmailRows);
    $cases[] = array('someone@example.com', 'like', 'phrase', '%someone@example.com%', $allEmailRows);
    $cases[] = array('someone@example.com', 'like', 'wildphrase', '%someone@example.com%', $allEmailRows);
    $cases[] = array('someone@example.com', 'like', 'wildwords', '%someone@example.com%', $allEmailRows);
    $cases[] = array('someone@example.com', 'like', 'wildwords-suffix', '%someone@example.com%', $allEmailRows);

    $cases[] = array('someone@example.com', 'fts', 'simple', 'someone@example.com', $allEmailRows);
    $cases[] = array('someone@example.com', 'fts', 'phrase', '"someone@example.com"', $allEmailRows);
    $cases[] = array('someone@example.com', 'fts', 'wildphrase', '"*someone@example.com*"', $allEmailRows);
    $cases[] = array('someone@example.com', 'fts', 'wildwords', '*someone* *example*', $allEmailRows);
    $cases[] = array('someone@example.com', 'fts', 'wildwords-suffix', 'someone* example*', $allEmailRows);

    $cases[] = array('someone@example.com', 'ftsbool', 'simple', '+"someone" +"example"', $allEmailRows);
    $cases[] = array('someone@example.com', 'ftsbool', 'phrase', '+"someone@example.com"', $allEmailRows);
    $cases[] = array('someone@example.com', 'ftsbool', 'wildphrase', '+"*someone@example.com*"', $allEmailRows);
    $cases[] = array('someone@example.com', 'ftsbool', 'wildwords', '+*someone* +*example*', $allEmailRows);
    $cases[] = array('someone@example.com', 'ftsbool', 'wildwords-suffix', '+someone* +example*', $allEmailRows);

    $cases[] = array('first second', 'like', 'simple', '%first second%', array(3, 4, 5));
    $cases[] = array('first second', 'like', 'phrase', '%first second%', array(3, 4, 5));
    $cases[] = array('first second', 'like', 'wildphrase', '%first second%', array(3, 4, 5));
    $cases[] = array('first second', 'like', 'wildwords', '%first%second%', array(3, 4, 5, 7));
    $cases[] = array('first second', 'like', 'wildwords-suffix', '%first%second%', array(3, 4, 5, 7));

    $cases[] = array('first second', 'fts', 'simple', 'first second', array(3, 4, 5));
    $cases[] = array('first second', 'fts', 'phrase', '"first second"', array(3, 4, 5));
    $cases[] = array('first second', 'fts', 'wildphrase', '"*first second*"', array(3, 4, 5));
    $cases[] = array('first second', 'fts', 'wildwords', '*first* *second*', array(3, 4, 5, 7));
    $cases[] = array('first second', 'fts', 'wildwords-suffix', 'first* second*', array(3, 4, 5, 7));

    $cases[] = array('first second', 'ftsbool', 'simple', '+"first" +"second"', array(3, 4, 5));
    $cases[] = array('first second', 'ftsbool', 'phrase', '+"first second"', array(3, 4, 5));
    $cases[] = array('first second', 'ftsbool', 'wildphrase', '+"*first second*"', array(3, 4, 5));
    $cases[] = array('first second', 'ftsbool', 'wildwords', '+*first* +*second*', array(3, 4, 5, 7));
    $cases[] = array('first second', 'ftsbool', 'wildwords-suffix', '+first* +second*', array(3, 4, 5, 7));

    $cases[] = array('first second', 'solr', 'simple', 'first second', NULL);
    $cases[] = array('first second', 'solr', 'phrase', '"first second"', NULL);
    $cases[] = array('first second', 'solr', 'wildphrase', '"*first second*"', NULL);
    $cases[] = array('first second', 'solr', 'wildwords', '*first* *second*', NULL);
    $cases[] = array('first second', 'solr', 'wildwords-suffix', 'first* second*', NULL);

    $cases[] = array('someone@', 'ftsbool', 'simple', '+"someone"', $allEmailRows);
    $cases[] = array('@example.com', 'ftsbool', 'simple', '+"example.com"', $allEmailRows);

    // If user supplies wildcards, then ignore mode.
    foreach (array(
      'simple',
      'wildphrase',
      'wildwords',
      'wildwords-suffix',
    ) as $mode) {
      $cases[] = array('first% second', 'like', $mode, 'first% second', array(3, 7));
      $cases[] = array('first% second', 'fts', $mode, 'first* second', array(3, 7));
      $cases[] = array('first% second', 'ftsbool', $mode, '+first* +second', array(3, 7));
      $cases[] = array('first% second', 'solr', $mode, 'first* second', NULL);
      $cases[] = array('first second%', 'like', $mode, 'first second%', array(3));
      $cases[] = array('first second%', 'fts', $mode, 'first second*', array(3));
      $cases[] = array('first second%', 'ftsbool', $mode, '+first +second*', array(3));
      $cases[] = array('first second%', 'solr', $mode, 'first second*', NULL);
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
      elseif (in_array($language, array('fts', 'ftsbool'))) {
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
