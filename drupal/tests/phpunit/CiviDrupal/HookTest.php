<?php
namespace CiviDrupal {

  use Civi\Test\EndToEndInterface;

  /**
   * Class HookTest
   * @package CiviDrupal
   * @group e2e
   */
  class HookTest extends \PHPUnit_Framework_TestCase implements EndToEndInterface {
    public function testFoo() {
      $arg1 = 'hello';
      $arg2 = array(
        'foo' => 123,
      );
      $this->assertNotEquals($arg2['foo'], 456);
      $this->assertNotEquals($arg2['hook_was_called'], 1);

      \CRM_Utils_Hook::singleton()
        ->invoke(
          2,
          $arg1,
          $arg2,
          \CRM_Utils_Hook::$_nullObject,
          \CRM_Utils_Hook::$_nullObject,
          \CRM_Utils_Hook::$_nullObject,
          \CRM_Utils_Hook::$_nullObject,
          'civicrm_fakeAlterableHook'
        );

      $this->assertEquals($arg2['foo'], 456);
      $this->assertEquals($arg2['hook_was_called'], 1);
    }

  }

}

namespace {

  function civicrm_civicrm_fakeAlterableHook($arg1, &$arg2) {
    if ($arg1 != 'hello') {
      throw new \Exception("Failed to receive arg1");
    }
    if ($arg2['foo'] != 123) {
      throw new \Exception("Failed to receive arg2[foo]");
    }
    $arg2['foo'] = 456;
    $arg2['hook_was_called'] = 1;
  }

}
