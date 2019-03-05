<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * @copyright CiviCRM LLC (c) 2004-2019
 *
 * Class to handled upgrading any saved searches with changed patterns.
 */
class CRM_Upgrade_Incremental_SmartGroups {

  /**
   * Perform updates specified by upgrade function.
   */
  public function updateGroups($actions) {
    foreach ($actions as $func => $fields) {
      $this->{$func}($fields);
    }
  }

  /**
   * Convert any
   * @param array $fields
   */
  public function datePickerConversion($fields) {
    $fieldPossibilities = $relativeFieldNames = [];
    foreach ($fields as $field) {
      $fieldPossibilities[] = $field;
      $fieldPossibilities[] = $field . '_high';
      $fieldPossibilities[] = $field . '_low';
    }
    $relativeDateMappings = ['activity_date_time' => 'activity'];

    foreach ($fields as $field) {
      foreach ($this->getSearchesWithField($field) as $savedSearch) {
        $formValues = $savedSearch['form_values'];
        $isRelative = $hasRelative = FALSE;
        $relativeFieldName = $field . '_relative';

        if (!empty($relativeDateMappings[$field]) && isset($formValues['relative_dates'])) {
          if (!empty($formValues['relative_dates'][$relativeDateMappings[$field]])) {
            $formValues[] = [$relativeFieldName, '=', $savedSearch['form_values']['relative_dates'][$relativeDateMappings[$field]]];
            unset($formValues['relative_dates'][$relativeDateMappings[$field]]);
            $isRelative = TRUE;
          }
        }
        foreach ($formValues as $index => $formValue) {
          if (!isset($formValue[0])) {
            // Any actual criteria will have this key set but skip any weird lines
            continue;
          }
          if (in_array($formValue[0], $fieldPossibilities)) {
            if ($isRelative) {
              unset($formValues[$index]);
            }
            else {
              $isHigh = substr($formValue[0], -5, 5) === '_high';
              $formValues[$index][2] = $this->getConvertedDateValue($formValue[2], $isHigh);
            }
          }
        }
        if (!$isRelative) {
          if (!in_array($relativeFieldName, $relativeFieldNames)) {
            $relativeFieldNames[] = $relativeFieldName;
            $formValues[] = [$relativeFieldName, '=', 0];
          }
        }
        if ($formValues !== $savedSearch['form_values']) {
          civicrm_api3('SavedSearch', 'create', ['id' => $savedSearch['id'], 'form_values' => $formValues]);
        }
      }
    }
  }

  /**
   * Conversion routine for a form change change from = string to IN array.
   *
   * For example a checkbox expected [$fieldName, '=', 1]
   * whereas select expects [$fieldName, 'IN', [1]]
   *
   * @param string $field
   */
  public function convertEqualsStringToInArray($field) {
    foreach ($this->getSearchesWithField($field) as $savedSearch) {
      $formValues = $savedSearch['form_values'];
      foreach ($formValues as $index => $formValue) {
        if ($formValue[0] === $field && !is_array($formValue[2]) && $formValue[1] === '=') {
          $formValues[$index][1] = 'IN';
          $formValues[$index][2] = [$formValue[2]];
        }
      }

      if ($formValues !== $savedSearch['form_values']) {
        civicrm_api3('SavedSearch', 'create', ['id' => $savedSearch['id'], 'form_values' => $formValues]);
      }
    }
  }

  /**
   * Get converted date value.
   *
   * @param string $dateValue
   * @param bool $isEndOfDay
   *   Is this the upper value in a search range? If so alter the time to
   *   get the end of day if none set.
   *
   * @return string
   *   $dateValue
   */
  protected function getConvertedDateValue($dateValue, $isEndOfDay) {
    if (date('Y-m-d', strtotime($dateValue)) !== $dateValue
      && date('Y-m-d H:i:s', strtotime($dateValue)) !== $dateValue
    ) {
      $dateValue = date('Y-m-d H:i:s', strtotime(CRM_Utils_Date::processDate($dateValue)));
      if ($isEndOfDay) {
        $dateValue = str_replace('00:00:00', '23:59:59', $dateValue);
      }
    }
    return $dateValue;
  }

  /**
   * Rename a smartgroup field.
   *
   * @param string $oldName
   * @param string $newName
   */
  public function renameField($oldName, $newName) {
    foreach ($this->getSearchesWithField($oldName) as $savedSearch) {
      $formValues = $savedSearch['form_values'];
      foreach ($formValues as $index => $formValue) {
        if ($formValue[0] === $oldName) {
          $formValues[$index][0] = $newName;
        }
      }

      if ($formValues !== $savedSearch['form_values']) {
        civicrm_api3('SavedSearch', 'create', ['id' => $savedSearch['id'], 'form_values' => $formValues]);
      }
    }
  }

  /**
   * Rename pairs of fields
   *
   * @param array $pairs
   *  Array or arrays of pairs - e.g
   *  [
   *    ['old' => 'activity_date', 'new' => 'activity_date_time'],
   *    ['old' => 'activity_date_low', 'new' => 'activity_date_time_low'],
   *    ['old' => 'activity_date_high', 'new' => 'activity_date_time_high'],
   *    ['old' => 'activity_date_relative', 'new' => 'activity_date_time_relative'],
   *  ]
   */
  public function renameFields($pairs) {
    foreach ($pairs as $pair) {
      $this->renameField($pair['old'], $pair['new']);
    }
  }

  /**
   * @param $field
   * @return mixed
   */
  protected function getSearchesWithField($field) {
    $savedSearches = civicrm_api3('SavedSearch', 'get', [
      'options' => ['limit' => 0],
      'form_values' => ['LIKE' => "%{$field}%"],
    ])['values'];
    return $savedSearches;

  }

}
