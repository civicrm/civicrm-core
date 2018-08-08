<?php

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * Afform.Get API Test Case
 * This is a generic test class implemented with PHPUnit.
 * @group headless
 */
class api_v3_AfformTest extends \PHPUnit_Framework_TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use \Civi\Test\Api3TestTrait;

  /**
   * Civi\Test has many helpers, like install(), uninstall(), sql(), and sqlFile().
   * See: https://github.com/civicrm/org.civicrm.testapalooza/blob/master/civi-test.md
   */
  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  /**
   * The setup() method is executed before the test is executed (optional).
   */
  public function setUp() {
    parent::setUp();
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
    $this->callAPISuccess('Afform', 'revert', ['name' => 'examplepage']);

    $message = 'The initial Afform.get should return default data';
    $result = $this->callAPISuccess('Afform', 'get', ['name' => 'examplepage']);
    $this->assertEquals('examplepage', $result['values']['examplepage']['id'], $message);
    $this->assertEquals('examplepage', $result['values']['examplepage']['name'], $message);
    $this->assertEquals('', $result['values']['examplepage']['title'], $message);
    $this->assertEquals('', $result['values']['examplepage']['description'], $message);
    $this->assertEquals('civicrm/example-page', $result['values']['examplepage']['server_route'], $message);
    $this->assertTrue(is_array($result['values']['examplepage']['layout']), $message);

    $message = 'After updating with Afform.create, the revised data should be returned';
    $result = $this->callAPISuccess('Afform', 'create', [
      'name' => 'examplepage',
      'description' => 'The temporary description',
    ]);
    $this->assertEquals('examplepage', $result['values']['name'], $message);
    $this->assertEquals('The temporary description', $result['values']['description'], $message);

    $message = 'After updating, the Afform.get API should return blended data';
    $result = $this->callAPISuccess('Afform', 'get', ['name' => 'examplepage']);
    $this->assertEquals('examplepage', $result['values']['examplepage']['id'], $message);
    $this->assertEquals('examplepage', $result['values']['examplepage']['name'], $message);
    $this->assertEquals('', $result['values']['examplepage']['title'], $message);
    $this->assertEquals('The temporary description', $result['values']['examplepage']['description'], $message);
    $this->assertEquals('civicrm/example-page', $result['values']['examplepage']['server_route'], $message);
    $this->assertTrue(is_array($result['values']['examplepage']['layout']), $message);

    $this->callAPISuccess('Afform', 'revert', ['name' => 'examplepage']);
    $message = 'After reverting, te final Afform.get should return default data';
    $result = $this->callAPISuccess('Afform', 'get', ['name' => 'examplepage']);
    $this->assertEquals('examplepage', $result['values']['examplepage']['id'], $message);
    $this->assertEquals('examplepage', $result['values']['examplepage']['name'], $message);
    $this->assertEquals('', $result['values']['examplepage']['title'], $message);
    $this->assertEquals('', $result['values']['examplepage']['description'], $message);
    $this->assertEquals('civicrm/example-page', $result['values']['examplepage']['server_route'], $message);
    $this->assertTrue(is_array($result['values']['examplepage']['layout']), $message);
  }

}
