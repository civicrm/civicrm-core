<?php
namespace Civi\Test;

/**
 * This is an example of a barebones test which uses a transaction (based on CiviTestListener).
 *
 * We check that the transaction works by creating some example records in setUp(). These
 * records should fetchable while the test executes, but not during tearDownAfterClass().
 *
 * @group headless
 */
class ExampleTransactionalTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * @var array
   *   Array(int $id).
   */
  protected static $contactIds = array();

  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  protected function setUp() {
    /** @var \CRM_Contact_DAO_Contact $contact */
    $contact = \CRM_Core_DAO::createTestObject('CRM_Contact_DAO_Contact', array(
      'contact_type' => 'Individual',
    ));
    self::$contactIds[$this->getName()] = $contact->id;
  }

  /**
   * In the first test, we make make testDummy1. He exists.
   */
  public function testDummy1() {
    $this->assertTrue(is_numeric(self::$contactIds['testDummy1']) && self::$contactIds['testDummy1'] > 0);

    // Still inside transaction. Data exists.
    $dao = new \CRM_Contact_DAO_Contact();
    $dao->id = self::$contactIds['testDummy1'];
    $this->assertTrue((bool) $dao->find());
  }

  /**
   * We previously made testDummy1, but he's been lost (rolled-back).
   * However, testDummy2 now exists.
   */
  public function testDummy2() {
    $this->assertTrue(is_numeric(self::$contactIds['testDummy1']) && self::$contactIds['testDummy1'] > 0);
    $this->assertTrue(is_numeric(self::$contactIds['testDummy2']) && self::$contactIds['testDummy2'] > 0);

    // Previous contact no longer exists
    $dao = new \CRM_Contact_DAO_Contact();
    $dao->id = self::$contactIds['testDummy1'];
    $this->assertFalse((bool) $dao->find());

    // Still inside transaction. Data exists.
    $dao = new \CRM_Contact_DAO_Contact();
    $dao->id = self::$contactIds['testDummy2'];
    $this->assertTrue((bool) $dao->find());
  }

  public function tearDown() {
  }

  /**
   * Both testDummy1 and testDummy2 have been created at some point (as part of the test runs),
   * but all the data was rolled-back
   *
   * @throws \Exception
   */
  public static function tearDownAfterClass() {
    if (!is_numeric(self::$contactIds['testDummy1'])) {
      throw new \Exception("Uh oh! The static \$contactIds does not include testDummy1! Did the test fail to execute?");
    }

    if (!is_numeric(self::$contactIds['testDummy2'])) {
      throw new \Exception("Uh oh! The static \$contactIds does not include testDummy2! Did the test fail to execute?");
    }

    $dao = new \CRM_Contact_DAO_Contact();
    $dao->id = self::$contactIds['testDummy1'];
    if ($dao->find()) {
      throw new \Exception("Uh oh! testDummy1 still exists!");
    }

    $dao = new \CRM_Contact_DAO_Contact();
    $dao->id = self::$contactIds['testDummy2'];
    if ($dao->find()) {
      throw new \Exception("Uh oh! testDummy2 still exists!");
    }
  }

}
