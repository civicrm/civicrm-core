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
 * Base class for writing API_Wrappers which generically manipulate the content
 * of all fields (except for some black-listed skip-fields).
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2015
 */

require_once 'api/Wrapper.php';

/**
 * Class CRM_Utils_API_AbstractFieldCoder.
 */
abstract class CRM_Utils_API_AbstractFieldCoder implements API_Wrapper {

  /**
   * Get skipped fields.
   *
   * @return array<string>
   *   List of field names
   */
  public function getSkipFields() {
    return NULL;
  }

  /**
   * Is field skipped.
   *
   * @param string $fldName
   *
   * @return bool
   *   TRUE if encoding should be skipped for this field
   */
  public function isSkippedField($fldName) {
    $skipFields = $this->getSkipFields();
    if ($skipFields === NULL) {
      return FALSE;
    }

    // Field should be skipped
    if (in_array($fldName, $skipFields)) {
      return TRUE;
    }
    // Field is multilingual and after cutting off _xx_YY should be skipped (CRM-7230)…
    if ((preg_match('/_[a-z][a-z]_[A-Z][A-Z]$/', $fldName) && in_array(substr($fldName, 0, -6), $skipFields))) {
      return TRUE;
    }
    // Field can take multiple entries, eg. fieldName[1], fieldName[2], etc.
    // We remove the index and check again if the fieldName in the list of skipped fields.
    $matches = array();
    if (preg_match('/^(.*)\[\d+\]/', $fldName, $matches) && in_array($matches[1], $skipFields)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Going to filter the submitted values.
   *
   * @param array|string $values the field value from the API
   */
  public abstract function encodeInput(&$values);

  /**
   * Decode output.
   *
   * @param string $values
   *
   * @return mixed
   */
  public abstract function decodeOutput(&$values);

  /**
   * @inheritDoc
   */
  public function fromApiInput($apiRequest) {
    $lowerAction = strtolower($apiRequest['action']);
    if ($apiRequest['version'] == 3 && in_array($lowerAction, array('get', 'create'))) {
      // note: 'getsingle', 'replace', 'update', and chaining all build on top of 'get'/'create'
      foreach ($apiRequest['params'] as $key => $value) {
        // Don't apply escaping to API control parameters (e.g. 'api.foo' or 'options.foo')
        // and don't apply to other skippable fields
        if (!$this->isApiControlField($key) && !$this->isSkippedField($key)) {
          $this->encodeInput($apiRequest['params'][$key]);
        }
      }
    }
    elseif ($apiRequest['version'] == 3 && $lowerAction == 'setvalue') {
      if (isset($apiRequest['params']['field']) && isset($apiRequest['params']['value'])) {
        if (!$this->isSkippedField($apiRequest['params']['field'])) {
          $this->encodeInput($apiRequest['params']['value']);
        }
      }
    }
    return $apiRequest;
  }

  /**
   * @inheritDoc
   */
  public function toApiOutput($apiRequest, $result) {
    $lowerAction = strtolower($apiRequest['action']);
    if ($apiRequest['version'] == 3 && in_array($lowerAction, array('get', 'create', 'setvalue'))) {
      foreach ($result as $key => $value) {
        // Don't apply escaping to API control parameters (e.g. 'api.foo' or 'options.foo')
        // and don't apply to other skippable fields
        if (!$this->isApiControlField($key) && !$this->isSkippedField($key)) {
          $this->decodeOutput($result[$key]);
        }
      }
    }
    // setvalue?
    return $result;
  }

  /**
   * @param $key
   *
   * @return bool
   */
  protected function isApiControlField($key) {
    return (FALSE !== strpos($key, '.'));
  }

}
