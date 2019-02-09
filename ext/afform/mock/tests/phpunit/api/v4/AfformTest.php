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

  public function getBasicDirectives() {
    return [
      ['afformExamplepage', ['title' => '', 'description' => '', 'server_route' => 'civicrm/example-page']],
      ['fakelibBareFile', ['title' => '', 'description' => '']],
      ['fakelibFoo', ['title' => '', 'description' => '']],
    ];
  }

  /**
   * This takes the bundled `examplepage` and performs some API calls on it.
   * @dataProvider getBasicDirectives
   */
  public function testGetUpdateRevert($directiveName, $originalMetadata) {
    $get = function($arr, $key) {
      return isset($arr[$key]) ? $arr[$key] : NULL;
    };

    Civi\Api4\Afform::revert()->addWhere('name', '=', $directiveName)->execute();

    $message = 'The initial Afform.get should return default data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', $directiveName)->execute();
    $this->assertEquals($directiveName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals($get($originalMetadata, 'description'), $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);

    $message = 'After updating with Afform.create, the revised data should be returned';
    $result = Civi\Api4\Afform::update()
      ->addWhere('name', '=', $directiveName)
      ->addValue('description', 'The temporary description')
      ->execute();
    $this->assertEquals($directiveName, $result[0]['name'], $message);
    $this->assertEquals('The temporary description', $result[0]['description'], $message);

    $message = 'After updating, the Afform.get API should return blended data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', $directiveName)->execute();
    $this->assertEquals($directiveName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals('The temporary description', $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);

    Civi\Api4\Afform::revert()->addWhere('name', '=', $directiveName)->execute();
    $message = 'After reverting, the final Afform.get should return default data';
    $result = Civi\Api4\Afform::get()->addWhere('name', '=', $directiveName)->execute();
    $this->assertEquals($directiveName, $result[0]['name'], $message);
    $this->assertEquals($get($originalMetadata, 'title'), $get($result[0], 'title'), $message);
    $this->assertEquals($get($originalMetadata, 'description'), $get($result[0], 'description'), $message);
    $this->assertEquals($get($originalMetadata, 'server_route'), $get($result[0], 'server_route'), $message);
    $this->assertTrue(is_array($result[0]['layout']), $message);
  }

}
