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
 *  Include parent class definition
 */
require_once 'CiviTest/CiviTestSuite.php';

/**
 *  Class containing all CRM test suites
 *
 * @package   CiviCRM
 */
class E2E_AllTests extends CiviTestSuite {
  private static $instance = NULL;

  /**
   */
  private static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  /**
   *  Build test suite dynamically.
   */
  public static function suite() {
    $inst = self::getInstance();
    return $inst->implSuite(__FILE__);
  }

}
