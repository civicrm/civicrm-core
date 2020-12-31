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

namespace Civi\Test;

/**
 * Class InvasiveExample
 *
 * This is a dummy/placeholder for use with InvasiveTest.
 *
 * @package Civi\Test
 */
class InvasiveExample {

  private $privateField = 10;

  private static $protectedStaticField = 20;

  /**
   * @return int
   */
  private function getPrivateField(): int {
    return $this->privateField;
  }

  /**
   * @param int $privateField
   */
  private function setPrivateField(int $privateField) {
    $this->privateField = $privateField;
  }

  private function twiddlePrivateField(&$output) {
    $output = $this->privateField * 100;
    return $this->privateField * 10000;
  }

  /**
   * @return int
   */
  protected static function getProtectedStaticField(): int {
    return self::$protectedStaticField;
  }

  /**
   * @param int $protectedStaticField
   */
  protected static function setProtectedStaticField(int $protectedStaticField) {
    self::$protectedStaticField = $protectedStaticField;
  }

}
