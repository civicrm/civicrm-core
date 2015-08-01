<<<<<<< HEAD
<?php

/**
 *  Include parent class definition
 */
require_once 'CiviTest/CiviTestSuite.php';

/**
 *  Class containing all test suites
 *
 *  @package   CiviCRM
 */
class CRM_Case_AllTests extends CiviTestSuite {
  private static $instance = NULL;

  /**
   *
   */
  private static function getInstance() {
    if (is_null(self::$instance)) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  /**
   *  Build test suite dynamically
   */
  public static function suite() {
    $inst = self::getInstance();
    return $inst->implSuite(__FILE__);
  }
}
=======
<?php

/**
 *  Include parent class definition
 */
require_once 'CiviTest/CiviTestSuite.php';

/**
 *  Class containing all test suites
 *
 * @package   CiviCRM
 */
class CRM_Case_AllTests extends CiviTestSuite {
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
>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e
