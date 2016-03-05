<?php
/*
  +--------------------------------------------------------------------+
  | CiviCRM version 4.7                                                |
  +--------------------------------------------------------------------+
  | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

/**
 * This is a quick-and-dirty way to define a vaguely-class-ish structure. It's non-performant, abnormal,
 * and not a complete OOP system. Only use for testing/mocking.
 *
 * @code
 * $object = new CRM_Utils_FakeObject(array(
 *   'doIt' => function() {  print "It!\n"; }
 * ));
 * $object->doIt();
 * @endcode
 */
class CRM_Utils_FakeObject {
  /**
   * @param $array
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
