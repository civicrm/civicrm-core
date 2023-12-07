<?php

/**
 * Class CRM_Utils_StringTest
 * @group headless
 */
class CRM_Utils_StringTest extends CiviUnitTestCase {

  /**
   * Set up for tests.
   */
  public function setUp(): void {
    parent::setUp();
    $this->useTransaction();
  }

  public function testBase64Url(): void {
    $examples = [
      'a' => 'YQ',
      'ab' => 'YWI',
      'abc' => 'YWJj',
      '3f>' => 'M2Y-',
    ];
    foreach ($examples as $raw => $b64) {
      $this->assertEquals($b64, CRM_Utils_String::base64UrlEncode($raw));
      $this->assertEquals($raw, CRM_Utils_String::base64UrlDecode($b64));
    }
  }

  public function testStripPathChars(): void {
    $testSet = [
      '' => '',
      NULL => NULL,
      'civicrm' => 'civicrm',
      'civicrm/dashboard' => 'civicrm/dashboard',
      'civicrm/contribute/transact' => 'civicrm/contribute/transact',
      'civicrm/<hack>attempt</hack>' => 'civicrm/_hack_attempt_/hack_',
      'civicrm dashboard & force = 1,;' => 'civicrm_dashboard___force___1__',
    ];

    foreach ($testSet as $in => $expected) {
      $out = CRM_Utils_String::stripPathChars($in);
      $this->assertEquals($out, $expected, 'Output does not match');
    }
  }

  public function testExtractName(): void {
    $cases = [
      [
        'full_name' => 'Alan',
        'first_name' => 'Alan',
      ],
      [
        'full_name' => 'Alan Arkin',
        'first_name' => 'Alan',
        'last_name' => 'Arkin',
      ],
      [
        'full_name' => '"Alan Arkin"',
        'first_name' => 'Alan',
        'last_name' => 'Arkin',
      ],
      [
        'full_name' => 'Alan A Arkin',
        'first_name' => 'Alan',
        'middle_name' => 'A',
        'last_name' => 'Arkin',
      ],
      [
        'full_name' => 'Adams, Amy',
        'first_name' => 'Amy',
        'last_name' => 'Adams',
      ],
      [
        'full_name' => 'Adams, Amy A',
        'first_name' => 'Amy',
        'middle_name' => 'A',
        'last_name' => 'Adams',
      ],
      [
        'full_name' => '"Adams, Amy A"',
        'first_name' => 'Amy',
        'middle_name' => 'A',
        'last_name' => 'Adams',
      ],
    ];
    foreach ($cases as $case) {
      $actual = [];
      CRM_Utils_String::extractName($case['full_name'], $actual);
      $this->assertEquals($actual['first_name'], $case['first_name']);
      $this->assertEquals($actual['last_name'] ?? NULL, $case['last_name'] ?? NULL);
      $this->assertEquals($actual['middle_name'] ?? NULL, $case['middle_name'] ?? NULL);
    }
  }

  /**
   * Test the ellipsify function.
   *
   * @noinspection SpellCheckingInspection
   */
  public function testEllipsify(): void {
    $maxLen = 5;
    $cases = [
      '1' => '1',
      '12345' => '12345',
      '123456' => '12...',
    ];
    foreach ($cases as $input => $expected) {
      $this->assertEquals($expected, CRM_Utils_String::ellipsify($input, $maxLen));
    }
    // test utf-8 string, CRM-18997
    $input = 'Registro de eventos on-line: Taller: "Onboarding - Cómo integrar exitosamente a los nuevos talentos dentro de su organización - Formación práctica."';
    $maxLen = 128;
    $this->assertEquals(TRUE, mb_check_encoding(CRM_Utils_String::ellipsify($input, $maxLen), 'UTF-8'));
  }

  public function testRandom(): void {
    for ($i = 0; $i < 4; $i++) {
      $actual = CRM_Utils_String::createRandom(4, 'abc');
      $this->assertEquals(4, strlen($actual));
      $this->assertMatchesRegularExpression('/^[abc]+$/', $actual);

      $actual = CRM_Utils_String::createRandom(6, '12345678');
      $this->assertEquals(6, strlen($actual));
      $this->assertMatchesRegularExpression('/^[12345678]+$/', $actual);
    }
  }

  /**
   * @return array
   */
  public function parsePrefixData(): array {
    $cases = [];
    $cases[] = ['administer CiviCRM', NULL, [NULL, 'administer CiviCRM']];
    $cases[] = ['create contributions of type Event Fee: Canada', NULL, [NULL, 'create contributions of type Event Fee: Canada']];
    $cases[] = ['administer CiviCRM', 'com_civicrm', ['com_civicrm', 'administer CiviCRM']];
    $cases[] = ['Drupal:access user profiles', NULL, ['Drupal', 'access user profiles']];
    $cases[] = ['Joomla:component:perm', NULL, ['Joomla', 'component:perm']];
    return $cases;
  }

  /**
   * @dataProvider parsePrefixData
   *
   * @param $input
   * @param $defaultPrefix
   * @param $expected
   */
  public function testParsePrefix($input, $defaultPrefix, $expected): void {
    $actual = CRM_Utils_String::parsePrefix(':', $input, $defaultPrefix);
    $this->assertEquals($expected, $actual);
  }

  /**
   * @return array
   */
  public function booleanDataProvider(): array {
    // array(0 => $input, 1 => $expectedOutput)
    $cases = [];
    $cases[] = [TRUE, TRUE];
    $cases[] = [FALSE, FALSE];
    $cases[] = [1, TRUE];
    $cases[] = [0, FALSE];
    $cases[] = ['1', TRUE];
    $cases[] = ['0', FALSE];
    $cases[] = [TRUE, TRUE];
    $cases[] = [FALSE, FALSE];
    $cases[] = ['Y', TRUE];
    $cases[] = ['N', FALSE];
    $cases[] = ['y', TRUE];
    $cases[] = ['n', FALSE];
    $cases[] = ['Yes', TRUE];
    $cases[] = ['No', FALSE];
    $cases[] = ['True', TRUE];
    $cases[] = ['False', FALSE];
    $cases[] = ['yEs', TRUE];
    $cases[] = ['nO', FALSE];
    $cases[] = ['tRuE', TRUE];
    $cases[] = ['FaLsE', FALSE];
    return $cases;
  }

  /**
   * @param mixed $input
   * @param bool $expected
   *
   * @dataProvider booleanDataProvider
   */
  public function testStrToBool($input, bool $expected): void {
    $actual = CRM_Utils_String::strtobool($input);
    $this->assertSame($expected, $actual);
  }

  public function wildcardCases(): array {
    $cases = [];
    $cases[] = ['*', ['foo.bar.1', 'foo.bar.2', 'foo.whiz', 'bang.bang']];
    $cases[] = ['foo.*', ['foo.bar.1', 'foo.bar.2', 'foo.whiz']];
    $cases[] = ['foo.bar.*', ['foo.bar.1', 'foo.bar.2']];
    $cases[] = [['foo.bar.*', 'foo.bar.2'], ['foo.bar.1', 'foo.bar.2']];
    $cases[] = [['foo.bar.2', 'foo.w*'], ['foo.bar.2', 'foo.whiz']];
    return $cases;
  }

  /**
   * @param $patterns
   * @param $expectedResults
   * @dataProvider wildcardCases
   */
  public function testFilterByWildCards($patterns, $expectedResults): void {
    $data = ['foo.bar.1', 'foo.bar.2', 'foo.whiz', 'bang.bang'];

    $actualResults = CRM_Utils_String::filterByWildcards($patterns, $data);
    $this->assertEquals($expectedResults, $actualResults);

    $patterns = (array) $patterns;
    $patterns[] = 'noise';

    $actualResults = CRM_Utils_String::filterByWildcards($patterns, $data);
    $this->assertEquals($expectedResults, $actualResults);

    $actualResults = CRM_Utils_String::filterByWildcards($patterns, $data, TRUE);
    $this->assertEquals(array_merge($expectedResults, ['noise']), $actualResults);
  }

  /**
   * @see https://issues.civicrm.org/jira/browse/CRM-20821
   * @see https://issues.civicrm.org/jira/browse/CRM-14283
   *
   * @param string $imageURL
   * @param bool $forceHttps
   * @param string $expected
   *
   * @dataProvider simplifyURLProvider
   */
  public function testSimplifyURL(string $imageURL, bool $forceHttps, string $expected): void {
    $this->assertEquals(
      $expected,
      CRM_Utils_String::simplifyURL($imageURL, $forceHttps)
    );
  }

  /**
   * Used for testNormalizeImageURL above
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @noinspection HttpUrlsUsage
   */
  public function simplifyURLProvider(): array {
    $config = CRM_Core_Config::singleton();
    $urlParts = CRM_Utils_String::simpleParseUrl($config->userFrameworkBaseURL);
    $localDomain = $urlParts['host+port'];
    if (empty($localDomain)) {
      throw new CRM_Core_Exception('Failed to determine local base URL');
    }
    $externalDomain = 'example.org';

    // Ensure that $externalDomain really is different from $localDomain
    if ($externalDomain === $localDomain) {
      $externalDomain = 'example.net';
    }

    return [
      'prototypical example' => [
        "https://$localDomain/sites/default/files/coffee-mug.jpg",
        FALSE,
        '/sites/default/files/coffee-mug.jpg',
      ],
      'external domain with https' => [
        "https://$externalDomain/sites/default/files/coffee-mug.jpg",
        FALSE,
        "https://$externalDomain/sites/default/files/coffee-mug.jpg",
      ],
      'external domain with http forced to https' => [
        "http://$externalDomain/sites/default/files/coffee-mug.jpg",
        TRUE,
        "https://$externalDomain/sites/default/files/coffee-mug.jpg",
      ],
      'external domain with http not forced' => [
        "http://$externalDomain/sites/default/files/coffee-mug.jpg",
        FALSE,
        "http://$externalDomain/sites/default/files/coffee-mug.jpg",
      ],
      'local URL' => [
        '/sites/default/files/coffee-mug.jpg',
        FALSE,
        '/sites/default/files/coffee-mug.jpg',
      ],
      'local URL without a forward slash' => [
        'sites/default/files/coffee-mug.jpg',
        FALSE,
        '/sites/default/files/coffee-mug.jpg',
      ],
      'empty input' => [
        '',
        FALSE,
        '',
      ],
    ];
  }

  /**
   * @param string $url
   * @param array $expected
   *
   * @dataProvider parseURLProvider
   */
  public function testSimpleParseUrl(string $url, array $expected): void {
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
  public function parseURLProvider(): array {
    return [
      'prototypical example' => [
        'https://example.com:8000/foo/bar/?id=1#fragment',
        [
          'host+port' => 'example.com:8000',
          'path+query' => '/foo/bar/?id=1',
        ],
      ],
      'default port example' => [
        'https://example.com/foo/bar/?id=1#fragment',
        [
          'host+port' => 'example.com',
          'path+query' => '/foo/bar/?id=1',
        ],
      ],
      'empty' => [
        '',
        [
          'host+port' => '',
          'path+query' => '',
        ],
      ],
      'path only' => [
        '/foo/bar/image.png',
        [
          'host+port' => '',
          'path+query' => '/foo/bar/image.png',
        ],
      ],
    ];
  }

  public function purifyHTMLProvider(): array {
    $tests = [];
    $tests[] = ['<span onmouseover=alert(0)>HOVER</span>', '<span>HOVER</span>'];
    $tests[] = ['<a href="https://civicrm.org" target="_blank" class="button-purple">hello</a>', '<a href="https://civicrm.org" target="_blank" class="button-purple" rel="noreferrer noopener">hello</a>'];
    return $tests;
  }

  /**
   * Test output of purifyHTML
   *
   * @param string $testString
   * @param string $expectedString
   *
   * @dataProvider purifyHTMLProvider
   */
  public function testPurifyHTML(string $testString, string $expectedString): void {
    $this->assertEquals($expectedString, CRM_Utils_String::purifyHTML($testString));
  }

  public function getGoodSerializeExamples(): array {
    $strings = [];
    $strings[] = ['a:1:{s:1:"a";s:1:"b";}'];
    $strings[] = ['d:1.2;'];
    $strings[] = ['s:3:"abc";'];
    $strings[] = ['N;'];
    $strings[] = ['a:7:{i:0;N;i:1;s:3:"abc";i:2;i:1;i:3;d:2.3;i:4;b:1;i:5;b:0;i:6;i:0;}'];
    return $strings;
  }

  /**
   * @param string $str
   *   A safe serialized value.
   *
   * @dataProvider getGoodSerializeExamples
   */
  public function testGoodSerialize(string $str): void {
    $this->assertEquals(unserialize($str), CRM_Utils_String::unserialize($str));
  }

  public function getBadSerializeExamples(): array {
    $strings = [];
    $strings[] = ['O:8:"stdClass":0:{}'];
    $strings[] = ['O:9:"Exception":7:{s:10:"*message";s:3:"abc";s:17:"ExceptionString";s:0:"";s:7:"*code";i:0;s:7:"*file";s:17:"Command line code";s:7:"*line";i:1;s:16:"ExceptionTrace";a:0:{}s:19:"ExceptionPrevious";N;}'];
    return $strings;
  }

  /**
   * @param string $str
   *   An unsafe serialized value.
   *
   * @dataProvider getBadSerializeExamples
   */
  public function testBadSerializeExamples(string $str): void {
    $this->assertFalse(CRM_Utils_String::unserialize($str));
  }

  /**
   * Test that we get a meaningful error if Smarty syntax is wrong.
   */
  public function testSmartyExceptionHandling(): void {
    try {
      CRM_Utils_String::parseOneOffStringThroughSmarty('{if}');
    }
    catch (CRM_Core_Exception $e) {
      $this->assertStringStartsWith('Message was not parsed due to invalid smarty syntax', $e->getMessage());
      return;
    }
    $this->fail('Exception expected');
  }

}
