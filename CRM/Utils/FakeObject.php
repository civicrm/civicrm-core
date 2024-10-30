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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This is a quick-and-dirty way to define a vaguely-class-ish structure. It's non-performant, abnormal,
 * and not a complete OOP system. Only use for testing/mocking.
 *
 * ```
 * $object = new CRM_Utils_FakeObject(array(
 *   'doIt' => function() {  print "It!\n"; }
 * ));
 * $object->doIt();
 * ```
 */
class CRM_Utils_FakeObject {

  /**
   * @var array
   */
  protected $array;

  /**
   * @param array $array
   */
  public function __construct($array) {
    $this->array = $array;
  }

  /**
   * @param string $name
   * @param $arguments
   *
   * @throws Exception
   */
  public function __call($name, $arguments) {
    if (isset($this->array[$name]) && is_callable($this->array[$name])) {
      return call_user_func_array($this->array[$name], $arguments);
    }
    else {
      throw new Exception("Call to unimplemented method: $name");
    }
  }

}
