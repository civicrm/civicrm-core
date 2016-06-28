<?php

/**
 * Class CRM_Utils_QueryFormatterTest
 * @group headless
 */
class CRM_Utils_QueryFormatterTest extends CiviUnitTestCase {

  /**
   * Generate data for tests to iterate through.
   *
   * @return array
   */
  public function dataProvider() {
    // Array(0=>$inputText, 1=>$language, 2=>$options, 3=>$expectedText).
    $cases = array();

    $cases[] = array('someone@example.com', 'like', 'simple', '%someone@example.com%');
    $cases[] = array('someone@example.com', 'like', 'phrase', '%someone@example.com%');
    $cases[] = array('someone@example.com', 'like', 'wildphrase', '%someone@example.com%');
    $cases[] = array('someone@example.com', 'like', 'wildwords', '%someone@example.com%');
    $cases[] = array('someone@example.com', 'like', 'wildwords-suffix', '%someone@example.com%');

    $cases[] = array('someone@example.com', 'fts', 'simple', 'someone@example.com');
    $cases[] = array('someone@example.com', 'fts', 'phrase', '"someone@example.com"');
    $cases[] = array('someone@example.com', 'fts', 'wildphrase', '"*someone@example.com*"');
    $cases[] = array('someone@example.com', 'fts', 'wildwords', '*someone* *example*'); // (1)
    $cases[] = array('someone@example.com', 'fts', 'wildwords-suffix', 'someone* example*'); // (1)

    $cases[] = array('someone@example.com', 'ftsbool', 'simple', '+someone +example'); // (1)
    $cases[] = array('someone@example.com', 'ftsbool', 'phrase', '+"someone@example.com"');
    $cases[] = array('someone@example.com', 'ftsbool', 'wildphrase', '+"*someone@example.com*"');
    $cases[] = array('someone@example.com', 'ftsbool', 'wildwords', '+*someone* +*example*'); // (1)
    $cases[] = array('someone@example.com', 'ftsbool', 'wildwords-suffix', '+someone* +example*'); // (1)

    // Note: The examples marked with (1) are suspicious cases where

    $cases[] = array('first second', 'like', 'simple', '%first second%');
    $cases[] = array('first second', 'like', 'phrase', '%first second%');
    $cases[] = array('first second', 'like', 'wildphrase', '%first second%');
    $cases[] = array('first second', 'like', 'wildwords', '%first%second%');
    $cases[] = array('first second', 'like', 'wildwords-suffix', '%first%second%');

    $cases[] = array('first second', 'fts', 'simple', 'first second');
    $cases[] = array('first second', 'fts', 'phrase', '"first second"');
    $cases[] = array('first second', 'fts', 'wildphrase', '"*first second*"');
    $cases[] = array('first second', 'fts', 'wildwords', '*first* *second*');
    $cases[] = array('first second', 'fts', 'wildwords-suffix', 'first* second*');

    $cases[] = array('first second', 'ftsbool', 'simple', '+first +second');
    $cases[] = array('first second', 'ftsbool', 'phrase', '+"first second"');
    $cases[] = array('first second', 'ftsbool', 'wildphrase', '+"*first second*"');
    $cases[] = array('first second', 'ftsbool', 'wildwords', '+*first* +*second*');
    $cases[] = array('first second', 'ftsbool', 'wildwords-suffix', '+first* +second*');

    $cases[] = array('first second', 'solr', 'simple', 'first second');
    $cases[] = array('first second', 'solr', 'phrase', '"first second"');
    $cases[] = array('first second', 'solr', 'wildphrase', '"*first second*"');
    $cases[] = array('first second', 'solr', 'wildwords', '*first* *second*');
    $cases[] = array('first second', 'solr', 'wildwords-suffix', 'first* second*');

    // If user supplies wildcards, then ignore mode.
    foreach (array(
               'simple',
               'wildphrase',
               'wildwords',
               'wildwords-suffix',
             ) as $mode) {
      $cases[] = array('first% second', 'like', $mode, 'first% second');
      $cases[] = array('first% second', 'fts', $mode, 'first* second');
      $cases[] = array('first% second', 'ftsbool', $mode, '+first* +second');
      $cases[] = array('first% second', 'solr', $mode, 'first* second');
      $cases[] = array('first second%', 'like', $mode, 'first second%');
      $cases[] = array('first second%', 'fts', $mode, 'first second*');
      $cases[] = array('first second%', 'ftsbool', $mode, '+first +second*');
      $cases[] = array('first second%', 'solr', $mode, 'first second*');
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
   *
   * @dataProvider dataProvider
   */
  public function testFormat($text, $language, $mode, $expectedText) {
    $formatter = new CRM_Utils_QueryFormatter($mode);
    $actualText = $formatter->format($text, $language);
    $this->assertEquals($expectedText, $actualText);
  }

}
