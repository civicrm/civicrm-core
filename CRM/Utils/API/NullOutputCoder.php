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
 * Work-around for CRM-13120 - The "create" action incorrectly returns string literal "null"
 * when the actual value is NULL or "". Rewrite the output.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
   * Going to filter the submitted values across XSS vulnerability.
   *
   * @param array|string $values
   */
  public function encodeInput(&$values) {
  }

  /**
   * Decode output.
   *
   * @param array $values
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
   * To api output.
   *
   * @param array $apiRequest
   * @param array $result
   *
   * @return array
   */
  public function toApiOutput($apiRequest, $result) {
    $lowerAction = strtolower($apiRequest['action']);
    if ($lowerAction === 'create') {
      return parent::toApiOutput($apiRequest, $result);
    }
    else {
      return $result;
    }
  }

}
