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
 * Base class for writing API_Wrappers which generically manipulate the content
 * of all fields (except for some black-listed skip-fields).
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

require_once 'api/Wrapper.php';

/**
 * Class CRM_Utils_API_AbstractFieldCoder.
 */
abstract class CRM_Utils_API_AbstractFieldCoder implements API_Wrapper {

  /**
   * Get skipped fields.
   *
   * @return string[]
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
    // Strip extra numbers from custom fields e.g. custom_32_1 should be custom_32
    if (strpos($fldName, 'custom_') === 0) {
      list($fldName, $customId) = explode('_', $fldName);
      $fldName .= '_' . $customId;
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
    $matches = [];
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
  abstract public function encodeInput(&$values);

  /**
   * Decode output.
   *
   * @param string $values
   *
   * @return mixed
   */
  abstract public function decodeOutput(&$values);

  /**
   * @inheritDoc
   */
  public function fromApiInput($apiRequest) {
    $lowerAction = strtolower($apiRequest['action']);
    if ($apiRequest['version'] == 3 && in_array($lowerAction, ['get', 'create'])) {
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
    if ($apiRequest['version'] == 3 && in_array($lowerAction, ['get', 'create', 'setvalue'])) {
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
