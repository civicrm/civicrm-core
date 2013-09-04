<?php
require_once 'CiviTest/CiviUnitTestCase.php';
class CRM_Utils_StringTest extends CiviUnitTestCase {
  //@todo make BAO enotice compliant  & remove the line below
  // WARNING - NEVER COPY & PASTE $_eNoticeCompliant = FALSE
  // new test classes should be compliant.
  public $_eNoticeCompliant = FALSE;
  function get_info() {
    return array(
      'name' => 'String Test',
      'description' => 'Test String Functions',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function testStripPathChars() {
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

  function testExtractName() {
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
      $this->assertEquals($actual['last_name'], $case['last_name']);
      $this->assertEquals($actual['middle_name'], $case['middle_name']);
    }
  }

  function testEllipsify() {
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

  function testRandom() {
    for ($i = 0; $i < 4; $i++) {
      $actual = CRM_Utils_String::createRandom(4, 'abc');
      $this->assertEquals(4, strlen($actual));
      $this->assertRegExp('/^[abc]+$/', $actual);

      $actual = CRM_Utils_String::createRandom(6, '12345678');
      $this->assertEquals(6, strlen($actual));
      $this->assertRegExp('/^[12345678]+$/', $actual);
    }
  }

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
   */
  public function testParsePrefix($input, $defaultPrefix, $expected) {
    $actual = CRM_Utils_String::parsePrefix(':', $input, $defaultPrefix);
    $this->assertEquals($expected, $actual);
  }
}

