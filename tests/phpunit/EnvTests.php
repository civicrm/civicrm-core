<?php

/**
 * The EnvTests suite allows you to specify an arbitrary mix of tests
 * using an environment variable. For example:
 *
 * env PHPUNIT_TESTS="MyFirstTest MySecondTest" phpunit EnvTests
 *
 * The PHPUNIT_TESTS variable contains a space-delimited list of test
 * names. Each name may be a class (eg "MyFirstTest") or a method
 * (eg "MyFirstTest::testFoo").
 */
class EnvTests extends \PHPUnit_Framework_TestSuite {

  /**
   * @return \EnvTests
   */
  public static function suite() {
    require_once 'CRM/Core/ClassLoader.php';
    CRM_Core_ClassLoader::singleton()->register();

    $suite = new EnvTests();
    $tests = getenv('PHPUNIT_TESTS');
    foreach (explode(' ', $tests) as $test) {
      if (strpos($test, '::') !== FALSE) {
        list ($class, $method) = explode('::', $test);
        $clazz = new \ReflectionClass($class);
        $suite->addTestMethod($clazz, $clazz->getMethod($method));
      }
      else {
        $suite->addTestSuite($test);
      }
    }
    return $suite;
  }

}
