<<<<<<< HEAD
<?php
require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Utils_MailTest
 */
class CRM_Utils_MailTest extends CiviUnitTestCase {
  /**
   * @return array
   */
  function get_info() {
    return array(
      'name' => 'Mail Test',
      'description' => 'Test RFC822 formatting',
      'group' => 'CiviCRM BAO Tests',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * test case for add( )
   * test with empty params.
   */
  function testFormatRFC822() {

    $values = array(
      array('name' => "Test User",
        'email' => "foo@bar.com",
        'result' => "Test User <foo@bar.com>",
      ),
      array(
        'name' => '"Test User"',
        'email' => "foo@bar.com",
        'result' => "Test User <foo@bar.com>",
      ),
      array(
        'name' => "User, Test",
        'email' => "foo@bar.com",
        'result' => '"User, Test" <foo@bar.com>',
      ),
      array(
        'name' => '"User, Test"',
        'email' => "foo@bar.com",
        'result' => '"User, Test" <foo@bar.com>',
      ),
      array(
        'name' => '"Test User"',
        'email' => "foo@bar.com",
        'result' => '"Test User" <foo@bar.com>',
        'useQuote' => TRUE,
      ),
      array(
        'name' => "User, Test",
        'email' => "foo@bar.com",
        'result' => '"User, Test" <foo@bar.com>',
        'useQuote' => TRUE,
      ),
    );
    foreach ($values as $value) {
      $result = CRM_Utils_Mail::formatRFC822Email($value['name'],
        $value['email'],
        CRM_Utils_Array::value('useQuote', $value, FALSE)
      );
      $this->assertEquals($result, $value['result'], 'Expected encoding does not match');
    }
  }
}

=======
<?php

/**
 * Class CRM_Utils_MailTest
 * @group headless
 */
class CRM_Utils_MailTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test case for add( )
   * test with empty params.
   */
  public function testFormatRFC822() {

    $values = array(
      array(
        'name' => "Test User",
        'email' => "foo@bar.com",
        'result' => "Test User <foo@bar.com>",
      ),
      array(
        'name' => '"Test User"',
        'email' => "foo@bar.com",
        'result' => "Test User <foo@bar.com>",
      ),
      array(
        'name' => "User, Test",
        'email' => "foo@bar.com",
        'result' => '"User, Test" <foo@bar.com>',
      ),
      array(
        'name' => '"User, Test"',
        'email' => "foo@bar.com",
        'result' => '"User, Test" <foo@bar.com>',
      ),
      array(
        'name' => '"Test User"',
        'email' => "foo@bar.com",
        'result' => '"Test User" <foo@bar.com>',
        'useQuote' => TRUE,
      ),
      array(
        'name' => "User, Test",
        'email' => "foo@bar.com",
        'result' => '"User, Test" <foo@bar.com>',
        'useQuote' => TRUE,
      ),
    );
    foreach ($values as $value) {
      $result = CRM_Utils_Mail::formatRFC822Email($value['name'],
        $value['email'],
        CRM_Utils_Array::value('useQuote', $value, FALSE)
      );
      $this->assertEquals($result, $value['result'], 'Expected encoding does not match');
    }
  }

}
>>>>>>> refs/remotes/civicrm/master
