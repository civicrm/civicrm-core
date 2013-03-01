<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * class for managing a http request
 *
 */
class CRM_Utils_Request {

  /**
   * We only need one instance of this object. So we use the singleton
   * pattern and cache the instance in this variable
   *
   * @var object
   * @access private
   * @static
   */
  static private $_singleton = NULL;

  /**
   * class constructor
   */ 
  function __construct() {}

  /**
   * Retrieve a value from the request (GET/POST/REQUEST)
   *
   * @param $name    name of the variable to be retrieved
   * @param $type    type of the variable (see CRM_Utils_Type for details)
   * @param $store   session scope where variable is stored
   * @param $abort   is this variable required
   * @param $default default value of the variable if not present
   * @param $method  where should we look for the variable
   *
   * @return mixed the value of the variable
   * @access public
   * @static
   *
   */
  static function retrieve($name, $type, &$store = NULL, $abort = FALSE, $default = NULL, $method = 'REQUEST') {

    // hack to detect stuff not yet converted to new style
    if (!is_string($type)) {
      CRM_Core_Error::backtrace();
      CRM_Core_Error::fatal(ts("Please convert retrieve call to use new function signature"));
    }

    $value = NULL;
    switch ($method) {
      case 'GET':
        $value = CRM_Utils_Array::value($name, $_GET);
        break;

      case 'POST':
        $value = CRM_Utils_Array::value($name, $_POST);
        break;

      default:
        $value = CRM_Utils_Array::value($name, $_REQUEST);
        break;
    }

    if (isset($value) &&
      (CRM_Utils_Type::validate($value, $type, $abort, $name) === NULL)
    ) {
      $value = NULL;
    }

    if (!isset($value) && $store) {
      $value = $store->get($name);
    }

    if (!isset($value) && $abort) {
      CRM_Core_Error::fatal(ts("Could not find valid value for %1", array(1 => $name)));
    }

    if (!isset($value) && $default) {
      $value = $default;
    }

    // minor hack for action
    if ($name == 'action' && is_string($value)) {
      $value = CRM_Core_Action::resolve($value);
    }

    if (isset($value) && $store) {
      $store->set($name, $value);
    }

    return $value;
  }
}

