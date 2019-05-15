<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
class HelloTest extends PHPUnit_Framework_TestCase {
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
