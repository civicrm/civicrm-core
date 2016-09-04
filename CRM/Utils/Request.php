<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * Class for managing a http request
 */
class CRM_Utils_Request {

  /**
   * Get a unique ID for the request.
   *
   * This unique ID is assigned to mysql when the connection is opened and is
   * available in PHP.
   *
   * The intent is that it is available for logging purposes and for triggers.
   *
   * The resulting string is 17 characters long. This consists of 13 characters of uniqid
   * and 4 more random characters.
   *
   * Uniqid is unique to the microsecond - to make it more unique we add 4 more characters
   * but stop short of the full 23 character string that a prefix would generate.
   *
   * It is intended that this string will be saved to log tables so striking a balance between
   * uniqueness and length is important. Note that I did check & lining up with byte values
   * (e.g 16 characters) does not confer any benefits. Using a CHAR field rather than VARCHAR
   * may improve speed, if indexed.
   *
   * @return string
   */
  public static function id() {
    if (!isset(\Civi::$statics[__CLASS__]['id'])) {
      \Civi::$statics[__CLASS__]['id'] = uniqid() . CRM_Utils_String::createRandom(CRM_Utils_String::ALPHANUMERIC, 4);
    }
    return \Civi::$statics[__CLASS__]['id'];
  }

  /**
   * Retrieve a value from the request (GET/POST/REQUEST)
   *
   * @param string $name
   *   Name of the variable to be retrieved.
   * @param string $type
   *   Type of the variable (see CRM_Utils_Type for details).
   * @param object $store
   *   Session scope where variable is stored.
   * @param bool $abort
   *   TRUE, if the variable is required.
   * @param mixed $default
   *   Default value of the variable if not present.
   * @param string $method
   *   Where to look for the variable - 'GET', 'POST' or 'REQUEST'.
   *
   * @return mixed
   *   The value of the variable
   */
  public static function retrieve($name, $type, &$store = NULL, $abort = FALSE, $default = NULL, $method = 'REQUEST') {

    // hack to detect stuff not yet converted to new style
    if (!is_string($type)) {
      CRM_Core_Error::backtrace();
      CRM_Core_Error::fatal(ts("Please convert retrieve call to use new function signature"));
    }

    $value = NULL;
    switch ($method) {
      case 'GET':
        $value = self::getValue($name, $_GET);
        break;

      case 'POST':
        $value = self::getValue($name, $_POST);
        break;

      default:
        $value = self::getValue($name, $_REQUEST);
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

  /**
   * @param string $name
   *   Name of the variable to be retrieved.
   *
   * @param array $method - '$_GET', '$_POST' or '$_REQUEST'.
   *
   * @return mixed
   *    The value of the variable
   */
  public static function getValue($name, $method) {
    if (isset($method[$name])) {
      return $method[$name];
    }
    // CRM-18384 - decode incorrect keys generated when &amp; is present in url
    foreach ($method as $key => $value) {
      if (strpos($key, 'amp;') !== FALSE) {
        $method[str_replace('amp;', '', $key)] = $method[$key];
        if (isset($method[$name])) {
          return $method[$name];
        }
        else {
          continue;
        }
      }
    }
    return NULL;
  }

  /**
   * This is a replacement for $_REQUEST which includes $_GET/$_POST
   * but excludes $_COOKIE / $_ENV / $_SERVER.
   *
   * @return array
   */
  public static function exportValues() {
    // For more discussion of default $_REQUEST handling, see:
    // http://www.php.net/manual/en/reserved.variables.request.php
    // http://www.php.net/manual/en/ini.core.php#ini.request-order
    // http://www.php.net/manual/en/ini.core.php#ini.variables-order

    $result = array();
    if ($_GET) {
      $result = array_merge($result, $_GET);
    }
    if ($_POST) {
      $result = array_merge($result, $_POST);
    }
    return $result;
  }

}
