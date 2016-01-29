<?php

/**
 * Class CRM_Utils_StringTest
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

}
