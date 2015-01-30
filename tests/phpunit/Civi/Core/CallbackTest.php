<?php

namespace Civi\Core {
  require_once 'CiviTest/CiviUnitTestCase.php';

  /**
   * Class CallbackTest
   * @package Civi\Core
   */
  class CallbackTest extends \CiviUnitTestCase {
    /**
     * Test callback for a global function.
     */
    public function testGlobalFunc() {
      // Note: civi_core_callback_dummy is implemented at the bottom of this file.
      $cb = Callback::create('civi_core_callback_dummy');
      $this->assertEquals('civi_core_callback_dummy', $cb);

      $expected = 'global dummy received foo';
      $actual = call_user_func($cb, 'foo');
      $this->assertEquals($expected, $actual);
    }

    /**
     * Test callback for a static function.
     */
    public function testStatic() {
      $cb = Callback::create('Civi\Core\CallbackTest::dummy');
      $this->assertEquals(array('Civi\Core\CallbackTest', 'dummy'), $cb);

      $expected = 'static dummy received foo';
      $actual = call_user_func($cb, 'foo');
      $this->assertEquals($expected, $actual);
    }

    /**
     * Test callback for an API.
     */
    public function testApi() {
      // Note: Callbacktest.Ping API is implemented at the bottom of this file.
      $cb = Callback::create('api3://Callbacktest/ping?first=@1');
      $expected = 'api dummy received foo';
      $actual = call_user_func($cb, 'foo');
      $this->assertEquals($expected, $actual);
    }

    /**
     * Test callback for an object in the container.
     */
    public function testContainer() {
      // Note: CallbackTestExampleService is implemented at the bottom of this file.
      Container::singleton()->set('callbackTestService', new CallbackTestExampleService());
      $cb = Callback::create('obj://callbackTestService/ping');
      $expected = 'service dummy received foo';
      $actual = call_user_func($cb, 'foo');
      $this->assertEquals($expected, $actual);
    }

    /**
     * Test callback for an invalid object in the container.
     *
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ExceptionInterface
     */
    public function testContainerWithInvalidService() {
      $cb = Callback::create('obj://totallyNonexistentService/ping');
      call_user_func($cb, 'foo');
    }

    /**
     * @param string $arg1
     *   Dummy value to pass through.
     * @return array
     */
    public static function dummy($arg1) {
      return "static dummy received $arg1";
    }

  }

  /**
   * Class CallbackTestExampleService
   *
   * @package Civi\Core
   */
  class CallbackTestExampleService {

    /**
     * @param string $arg1
     *   Dummy value to pass through.
     * @return string
     */
    public function ping($arg1) {
      return "service dummy received $arg1";
    }

  }
}

namespace {
  /**
   * @param string $arg1
   *   Dummy value to pass through.
   * @return string
   */
  function civi_core_callback_dummy($arg1) {
    return "global dummy received $arg1";
  }

  /**
   * @param array $params
   *   API parameters.
   * @return array
   */
  function civicrm_api3_callbacktest_ping($params) {
    return civicrm_api3_create_success("api dummy received " . $params['first']);
  }
}
