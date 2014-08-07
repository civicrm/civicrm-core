<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * Work-around for CRM-13120 - The "create" action incorrectly returns string literal "null"
 * when the actual value is NULL or "". Rewrite the output.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 */

require_once 'api/Wrapper.php';

/**
 * Class CRM_Utils_API_NullOutputCoder
 */
class CRM_Utils_API_NullOutputCoder extends CRM_Utils_API_AbstractFieldCoder {

  /**
   * @var CRM_Utils_API_NullOutputCoder
   */
  private static $_singleton = NULL;

  /**
   * @return CRM_Utils_API_NullOutputCoder
   */
  public static function singleton() {
    if (self::$_singleton === NULL) {
      self::$_singleton = new CRM_Utils_API_NullOutputCoder();
    }
    return self::$_singleton;
  }

  /**
   * This function is going to filter the
   * submitted values across XSS vulnerability.
   *
   * @param array|string $values
   *
   * @internal param bool $castToString If TRUE, all scalars will be filtered (and therefore cast to strings)
   *    If FALSE, then non-string values will be preserved
   */
  public function encodeInput(&$values) {
  }

  /**
   * @param $values
   * @param bool $castToString
   */
  public function decodeOutput(&$values, $castToString = FALSE) {
    if (is_array($values)) {
      foreach ($values as &$value) {
        $this->decodeOutput($value, TRUE);
      }
    }
    elseif ($castToString || is_string($values)) {
      if ($values === 'null') {
        $values = '';
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  /**
   * @param $apiRequest
   * @param $result
   *
   * @return modified
   */
  public function toApiOutput($apiRequest, $result) {
    $lowerAction = strtolower($apiRequest['action']);
    if ($lowerAction === 'create') {
      return parent::toApiOutput($apiRequest, $result);
    } else {
      return $result;
    }
  }
}
