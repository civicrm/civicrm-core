<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * @file HelloTest.php
 *
 * This is a simple test to make sure that you have phpunit
 * correctly installed and working. The call will look something like:
 *
 * <code>
 *   scripts/phpunit HelloTest
 * </code>
 *
 * If your script (which would need to be in HelloTest.php) is found and runs,
 * UR DOIN IT RIGHT!
 */

/**
 * Class HelloTest
 */
class HelloTest extends PHPUnit\Framework\TestCase {
  /**
   * contains the object handle of the string class
   * @var string
   */
  public $abc;

  /**
   * @param string|null $name
   */
  public function __construct($name = NULL) {
    parent::__construct($name);
  }

  /**
   * Called before the test functions will be executed.
   * this function is defined in PHPUnit_TestCase and overwritten
   * here
   */
  public function setUp() {
    // create a new instance of String with the
    // string 'abc'
    $this->abc = "hello";
  }

  /**
   * Called after the test functions are executed.
   * this function is defined in PHPUnit_TestCase and overwritten
   * here.
   */
  public function tearDown() {
    // delete your instance
    unset($this->abc);
  }

  /**
   * test the toString function.
   */
  public function testHello() {
    $result = $this->abc;
    $expected = 'hello';
    $this->assertEquals($result, $expected);
  }

}
