<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * Afform.Get API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v4_AfformTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   * See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->install(['org.civicrm.api4', 'org.civicrm.afform', 'org.civicrm.afform-mock'])
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    parent::setUp();
    CRM_Core_Config::singleton()->userPermissionTemp = new CRM_Core_Permission_Temp();
    CRM_Core_Config::singleton()->userPermissionTemp->grant('administer CiviCRM');
  }

  /**
   * The tearDown() method is executed after the test was executed (optional)
   * This can be used for cleanup.
   */
  public function tearDown() {
    parent::tearDown();
  }

  /**
   * This takes the bundled `examplepage` and performs some API calls on it.
   */
  public function testGetUpdateRevert() {
    Civi\Api4\Afform::revert()->addWhere('name', '=', 'afformExamplepage')->execute();

    $message = 'The initial Afform.get should return default data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', 'afformExamplepage')->execute();
    $result->indexBy('name');
    $b = (array) $result;
    $this->assertEquals('afformExamplepage', $result['afformExamplepage']['name'], $message);
    $this->assertEquals('', $result['afformExamplepage']['title'], $message);
    $this->assertEquals('', $result['afformExamplepage']['description'], $message);
    $this->assertEquals('civicrm/example-page', $result['afformExamplepage']['server_route'], $message);
    $this->assertTrue(is_array($result['afformExamplepage']['layout']), $message);

    $message = 'After updating with Afform.create, the revised data should be returned';
    $result = Civi\Api4\Afform::update()
      ->addWhere('name', '=', 'afformExamplepage')
      ->addValue('description', 'The temporary description')
      ->execute();
    $this->assertEquals('afformExamplepage', $result[0]['name'], $message);
    $this->assertEquals('The temporary description', $result[0]['description'], $message);

    $message = 'After updating, the Afform.get API should return blended data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', 'afformExamplepage')->execute();
    $this->assertEquals('afformExamplepage', $result[0]['name'], $message);
    $this->assertEquals('', $result[0]['title'], $message);
    $this->assertEquals('The temporary description', $result[0]['description'], $message);
    $this->assertEquals('civicrm/example-page', $result[0]['server_route'], $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);

    Civi\Api4\Afform::revert()->addWhere('name', '=', 'afformExamplepage')->execute();
    $message = 'After reverting, te final Afform.get should return default data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', 'afformExamplepage')->execute();
    $this->assertEquals('afformExamplepage', $result[0]['name'], $message);
    $this->assertEquals('', $result[0]['title'], $message);
    $this->assertEquals('', $result[0]['description'], $message);
    $this->assertEquals('civicrm/example-page', $result[0]['server_route'], $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);
  }

}
