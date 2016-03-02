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

    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_LIKE,
      CRM_Utils_QueryFormatter::MODE_NONE,
      '%first second%',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_LIKE,
      CRM_Utils_QueryFormatter::MODE_PHRASE,
      '%first second%',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_LIKE,
      CRM_Utils_QueryFormatter::MODE_WILDPHRASE,
      '%first second%',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_LIKE,
      CRM_Utils_QueryFormatter::MODE_WILDWORDS,
      '%first%second%',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_LIKE,
      CRM_Utils_QueryFormatter::MODE_WILDWORDS_SUFFIX,
      '%first%second%',
    );

    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTS,
      CRM_Utils_QueryFormatter::MODE_NONE,
      'first second',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTS,
      CRM_Utils_QueryFormatter::MODE_PHRASE,
      '"first second"',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTS,
      CRM_Utils_QueryFormatter::MODE_WILDPHRASE,
      '"*first second*"',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTS,
      CRM_Utils_QueryFormatter::MODE_WILDWORDS,
      '*first* *second*',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTS,
      CRM_Utils_QueryFormatter::MODE_WILDWORDS_SUFFIX,
      'first* second*',
    );

    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTSBOOL,
      CRM_Utils_QueryFormatter::MODE_NONE,
      '+first +second',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTSBOOL,
      CRM_Utils_QueryFormatter::MODE_PHRASE,
      '+"first second"',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTSBOOL,
      CRM_Utils_QueryFormatter::MODE_WILDPHRASE,
      '+"*first second*"',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTSBOOL,
      CRM_Utils_QueryFormatter::MODE_WILDWORDS,
      '+*first* +*second*',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SQL_FTSBOOL,
      CRM_Utils_QueryFormatter::MODE_WILDWORDS_SUFFIX,
      '+first* +second*',
    );

    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SOLR,
      CRM_Utils_QueryFormatter::MODE_NONE,
      'first second',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SOLR,
      CRM_Utils_QueryFormatter::MODE_PHRASE,
      '"first second"',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SOLR,
      CRM_Utils_QueryFormatter::MODE_WILDPHRASE,
      '"*first second*"',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SOLR,
      CRM_Utils_QueryFormatter::MODE_WILDWORDS,
      '*first* *second*',
    );
    $cases[] = array(
      'first second',
      CRM_Utils_QueryFormatter::LANG_SOLR,
      CRM_Utils_QueryFormatter::MODE_WILDWORDS_SUFFIX,
      'first* second*',
    );

    // If user supplies wildcards, then ignore mode.
    foreach (array(
               CRM_Utils_QueryFormatter::MODE_NONE,
               CRM_Utils_QueryFormatter::MODE_WILDPHRASE,
               CRM_Utils_QueryFormatter::MODE_WILDWORDS,
               CRM_Utils_QueryFormatter::MODE_WILDWORDS_SUFFIX,
             ) as $mode) {
      $cases[] = array(
        'first% second',
        CRM_Utils_QueryFormatter::LANG_SQL_LIKE,
        $mode,
        'first% second',
      );
      $cases[] = array(
        'first% second',
        CRM_Utils_QueryFormatter::LANG_SQL_FTS,
        $mode,
        'first* second',
      );
      $cases[] = array(
        'first% second',
        CRM_Utils_QueryFormatter::LANG_SQL_FTSBOOL,
        $mode,
        '+first* +second',
      );
      $cases[] = array(
        'first% second',
        CRM_Utils_QueryFormatter::LANG_SOLR,
        $mode,
        'first* second',
      );
      $cases[] = array(
        'first second%',
        CRM_Utils_QueryFormatter::LANG_SQL_LIKE,
        $mode,
        'first second%',
      );
      $cases[] = array(
        'first second%',
        CRM_Utils_QueryFormatter::LANG_SQL_FTS,
        $mode,
        'first second*',
      );
      $cases[] = array(
        'first second%',
        CRM_Utils_QueryFormatter::LANG_SQL_FTSBOOL,
        $mode,
        '+first +second*',
      );
      $cases[] = array(
        'first second%',
        CRM_Utils_QueryFormatter::LANG_SOLR,
        $mode,
        'first second*',
      );
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
