<?php

/**
 * Class CRM_Utils_StringTest
 * @group headless
 */
class CRM_Utils_StringTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  public function testStripPathChars() {
    $testSet = array(
      '' => '',
      NULL => NULL,
      'civicrm' => 'civicrm',
      'civicrm/dashboard' => 'civicrm/dashboard',
      'civicrm/contribute/transact' => 'civicrm/contribute/transact',
      'civicrm/<hack>attempt</hack>' => 'civicrm/_hack_attempt_/hack_',
      'civicrm dashboard & force = 1,;' => 'civicrm_dashboard___force___1__',
    );

    foreach ($testSet as $in => $expected) {
      $out = CRM_Utils_String::stripPathChars($in);
      $this->assertEquals($out, $expected, "Output does not match");
    }
  }

  public function testExtractName() {
    $cases = array(
      array(
        'full_name' => 'Alan',
        'first_name' => 'Alan',
      ),
      array(
        'full_name' => 'Alan Arkin',
        'first_name' => 'Alan',
        'last_name' => 'Arkin',
      ),
      array(
        'full_name' => '"Alan Arkin"',
        'first_name' => 'Alan',
        'last_name' => 'Arkin',
      ),
      array(
        'full_name' => 'Alan A Arkin',
        'first_name' => 'Alan',
        'middle_name' => 'A',
        'last_name' => 'Arkin',
      ),
      array(
        'full_name' => 'Adams, Amy',
        'first_name' => 'Amy',
        'last_name' => 'Adams',
      ),
      array(
        'full_name' => 'Adams, Amy A',
        'first_name' => 'Amy',
        'middle_name' => 'A',
        'last_name' => 'Adams',
      ),
      array(
        'full_name' => '"Adams, Amy A"',
        'first_name' => 'Amy',
        'middle_name' => 'A',
        'last_name' => 'Adams',
      ),
    );
    foreach ($cases as $case) {
      $actual = array();
      CRM_Utils_String::extractName($case['full_name'], $actual);
      $this->assertEquals($actual['first_name'], $case['first_name']);
      $this->assertEquals(CRM_Utils_Array::value('last_name', $actual), CRM_Utils_Array::value('last_name', $case));
      $this->assertEquals(CRM_Utils_Array::value('middle_name', $actual), CRM_Utils_Array::value('middle_name', $case));
    }
  }

  public function testEllipsify() {
    $maxLen = 5;
    $cases = array(
      '1' => '1',
      '12345' => '12345',
      '123456' => '12...',
    );
    foreach ($cases as $input => $expected) {
      $this->assertEquals($expected, CRM_Utils_String::ellipsify($input, $maxLen));
    }
    // test utf-8 string, CRM-18997
    $input = 'Registro de eventos on-line: Taller: "Onboarding - Cómo integrar exitosamente a los nuevos talentos dentro de su organización - Formación práctica."';
    $maxLen = 128;
    $this->assertEquals(TRUE, mb_check_encoding(CRM_Utils_String::ellipsify($input, $maxLen), 'UTF-8'));
  }

  public function testRandom() {
    for ($i = 0; $i < 4; $i++) {
      $actual = CRM_Utils_String::createRandom(4, 'abc');
      $this->assertEquals(4, strlen($actual));
      $this->assertRegExp('/^[abc]+$/', $actual);

      $actual = CRM_Utils_String::createRandom(6, '12345678');
      $this->assertEquals(6, strlen($actual));
      $this->assertRegExp('/^[12345678]+$/', $actual);
    }
  }

  /**
   * @return array
   */
  public function parsePrefixData() {
    $cases = array();
    $cases[] = array('administer CiviCRM', NULL, array(NULL, 'administer CiviCRM'));
    $cases[] = array('administer CiviCRM', 'com_civicrm', array('com_civicrm', 'administer CiviCRM'));
    $cases[] = array('Drupal:access user profiles', NULL, array('Drupal', 'access user profiles'));
    $cases[] = array('Joomla:component:perm', NULL, array('Joomla', 'component:perm'));
    return $cases;
  }

  /**
   * @dataProvider parsePrefixData
   * @param $input
   * @param $defaultPrefix
   * @param $expected
   */
  public function testParsePrefix($input, $defaultPrefix, $expected) {
    $actual = CRM_Utils_String::parsePrefix(':', $input, $defaultPrefix);
    $this->assertEquals($expected, $actual);
  }

  /**
   * @return array
   */
  public function booleanDataProvider() {
    $cases = array(); // array(0 => $input, 1 => $expectedOutput)
    $cases[] = array(TRUE, TRUE);
    $cases[] = array(FALSE, FALSE);
    $cases[] = array(1, TRUE);
    $cases[] = array(0, FALSE);
    $cases[] = array('1', TRUE);
    $cases[] = array('0', FALSE);
    $cases[] = array(TRUE, TRUE);
    $cases[] = array(FALSE, FALSE);
    $cases[] = array('Y', TRUE);
    $cases[] = array('N', FALSE);
    $cases[] = array('y', TRUE);
    $cases[] = array('n', FALSE);
    $cases[] = array('Yes', TRUE);
    $cases[] = array('No', FALSE);
    $cases[] = array('True', TRUE);
    $cases[] = array('False', FALSE);
    $cases[] = array('yEs', TRUE);
    $cases[] = array('nO', FALSE);
    $cases[] = array('tRuE', TRUE);
    $cases[] = array('FaLsE', FALSE);
    return $cases;
  }

  /**
   * @param $input
   * @param bool $expected
   *     * @dataProvider booleanDataProvider
   */
  public function testStrToBool($input, $expected) {
    $actual = CRM_Utils_String::strtobool($input);
    $this->assertTrue($expected === $actual);
  }

  public function startEndCases() {
    $cases = array();
    $cases[] = array('startsWith', 'foo', '', TRUE);
    $cases[] = array('startsWith', 'foo', 'f', TRUE);
    $cases[] = array('startsWith', 'foo', 'fo', TRUE);
    $cases[] = array('startsWith', 'foo', 'foo', TRUE);
    $cases[] = array('startsWith', 'foo', 'fooo', FALSE);
    $cases[] = array('startsWith', 'foo', 'o', FALSE);
    $cases[] = array('endsWith', 'foo', 'f', FALSE);
    $cases[] = array('endsWith', 'foo', '', TRUE);
    $cases[] = array('endsWith', 'foo', 'o', TRUE);
    $cases[] = array('endsWith', 'foo', 'oo', TRUE);
    $cases[] = array('endsWith', 'foo', 'foo', TRUE);
    $cases[] = array('endsWith', 'foo', 'fooo', FALSE);
    $cases[] = array('endsWith', 'foo*', '*', TRUE);
    return $cases;
  }

  /**
   * @param string $func
   *   One of: 'startsWith' or 'endsWith'.
   * @param $string
   * @param $fragment
   * @param $expectedResult
   * @dataProvider startEndCases
   */
  public function testStartEndWith($func, $string, $fragment, $expectedResult) {
    $actualResult = \CRM_Utils_String::$func($string, $fragment);
    $this->assertEquals($expectedResult, $actualResult, "Checking $func($string,$fragment)");
  }

  public function wildcardCases() {
    $cases = array();
    $cases[] = array('*', array('foo.bar.1', 'foo.bar.2', 'foo.whiz', 'bang.bang'));
    $cases[] = array('foo.*', array('foo.bar.1', 'foo.bar.2', 'foo.whiz'));
    $cases[] = array('foo.bar.*', array('foo.bar.1', 'foo.bar.2'));
    $cases[] = array(array('foo.bar.*', 'foo.bar.2'), array('foo.bar.1', 'foo.bar.2'));
    $cases[] = array(array('foo.bar.2', 'foo.w*'), array('foo.bar.2', 'foo.whiz'));
    return $cases;
  }

  /**
   * @param $patterns
   * @param $expectedResults
   * @dataProvider wildcardCases
   */
  public function testFilterByWildCards($patterns, $expectedResults) {
    $data = array('foo.bar.1', 'foo.bar.2', 'foo.whiz', 'bang.bang');

    $actualResults = CRM_Utils_String::filterByWildcards($patterns, $data);
    $this->assertEquals($expectedResults, $actualResults);

    $patterns = (array) $patterns;
    $patterns[] = 'noise';

    $actualResults = CRM_Utils_String::filterByWildcards($patterns, $data, FALSE);
    $this->assertEquals($expectedResults, $actualResults);

    $actualResults = CRM_Utils_String::filterByWildcards($patterns, $data, TRUE);
    $this->assertEquals(array_merge($expectedResults, array('noise')), $actualResults);
  }

  /**
   * CRM-20821
   * CRM-14283
   *
   * @param string $imageURL
   * @param book $forceHttps
   * @param string $expected
   *
   * @dataProvider simplifyURLProvider
   */
  public function testSimplifyURL($imageURL, $forceHttps, $expected) {
    $this->assertEquals(
      $expected,
      CRM_Utils_String::simplifyURL($imageURL, $forceHttps)
    );
  }

  /**
   * Used for testNormalizeImageURL above
   *
   * @return array
   */
  public function simplifyURLProvider() {
    $config = CRM_Core_Config::singleton();
    $urlParts = CRM_Utils_String::simpleParseUrl($config->userFrameworkBaseURL);
    $localDomain = $urlParts['host+port'];
    if (empty($localDomain)) {
      throw new \Exception("Failed to determine local base URL");
    }
    $externalDomain = 'example.org';

    // Ensure that $externalDomain really is different from $localDomain
    if ($externalDomain == $localDomain) {
      $externalDomain = 'example.net';
    }

    return array(
      'prototypical example' => array(
        "https://$localDomain/sites/default/files/coffee-mug.jpg",
        FALSE,
        '/sites/default/files/coffee-mug.jpg',
      ),
      'external domain with https' => array(
        "https://$externalDomain/sites/default/files/coffee-mug.jpg",
        FALSE,
        "https://$externalDomain/sites/default/files/coffee-mug.jpg",
      ),
      'external domain with http forced to https' => array(
        "http://$externalDomain/sites/default/files/coffee-mug.jpg",
        TRUE,
        "https://$externalDomain/sites/default/files/coffee-mug.jpg",
      ),
      'external domain with http not forced' => array(
        "http://$externalDomain/sites/default/files/coffee-mug.jpg",
        FALSE,
        "http://$externalDomain/sites/default/files/coffee-mug.jpg",
      ),
      'local URL' => array(
        "/sites/default/files/coffee-mug.jpg",
        FALSE,
        "/sites/default/files/coffee-mug.jpg",
      ),
      'local URL without a forward slash' => array(
        "sites/default/files/coffee-mug.jpg",
        FALSE,
        "/sites/default/files/coffee-mug.jpg",
      ),
      'empty input' => array(
        '',
        FALSE,
        '',
      ),
    );
  }

  /**
   * @param string $url
   * @param array $expected
   *
   * @dataProvider parseURLProvider
   */
  public function testSimpleParseUrl($url, $expected) {
    $this->assertEquals(
      $expected,
      CRM_Utils_String::simpleParseUrl($url)
    );
  }

  /**
   * Used for testSimpleParseUrl above
   *
   * @return array
   */
  public function parseURLProvider() {
    return array(
      "prototypical example" => array(
        "https://example.com:8000/foo/bar/?id=1#fragment",
        array(
          'host+port' => "example.com:8000",
          'path+query' => "/foo/bar/?id=1",
        ),
      ),
      "default port example" => array(
        "https://example.com/foo/bar/?id=1#fragment",
        array(
          'host+port' => "example.com",
          'path+query' => "/foo/bar/?id=1",
        ),
      ),
      "empty" => array(
        "",
        array(
          'host+port' => "",
          'path+query' => "",
        ),
      ),
      "path only" => array(
        "/foo/bar/image.png",
        array(
          'host+port' => "",
          'path+query' => "/foo/bar/image.png",
        ),
      ),
    );
  }

  public function purifyHTMLProvider() {
    $tests = [];
    $tests[] = ['<span onmouseover=alert(0)>HOVER</span>', '<span>HOVER</span>'];
    $tests[] = ['<a href="https://civicrm.org" target="_blank" class="button-purple">hello</a>', '<a href="https://civicrm.org" target="_blank" class="button-purple">hello</a>'];
    return $tests;
  }

  /**
   * Test ouput of purifyHTML
   * @param string $testString
   * @param string $expectedString
   * @dataProvider purifyHTMLProvider
   */
  public function testPurifyHTML($testString, $expectedString) {
    $this->assertEquals($expectedString, CRM_Utils_String::purifyHTML($testString));
  }

}
