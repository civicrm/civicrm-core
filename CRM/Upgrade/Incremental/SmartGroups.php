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
 * Class to handled upgrading any saved searches with changed patterns.
 */
class CRM_Upgrade_Incremental_SmartGroups {

  /**
   * Perform updates specified by upgrade function.
   */
  public function updateGroups($actions) {
    foreach ($actions as $func => $fields) {
      if ($func == 'renameField') {
        foreach ($fields as $fieldConversion) {
          $this->{$func}($fieldConversion['old'], $fieldConversion['new']);
        }
      }
      else {
        $this->{$func}($fields);
      }
    }
  }

  /**
   * Convert any
   * @param array $fields
   */
  public function datePickerConversion($fields) {
    $fieldPossibilities = $relativeFieldNames = [];
    $relativeDateMappings = [
      'activity_date_time' => 'activity',
      'participant_register_date' => 'participant',
      'receive_date' => 'contribution',
      'contribution_cancel_date' => 'contribution_cancel',
      'membership_join_date' => 'member_join',
      'membership_start_date' => 'member_start',
      'membership_end_date' => 'member_end',
      'pledge_payment_scheduled_date' => 'pledge_payment',
      'pledge_create_date' => 'pledge_create',
      'pledge_end_date' => 'pledge_end',
      'pledge_start_date' => 'pledge_start',
      'case_start_date' => 'case_from',
      'case_end_date' => 'case_to',
      'mailing_job_start_date' => 'mailing_date',
      'relationship_start_date' => 'relation_start',
      'relationship_end_date' => 'relation_end',
      'event' => 'event',
      'created_date' => 'log',
      'modified_date' => 'log',
    ];

    foreach ($fields as $field) {
      foreach ($this->getSearchesWithField($field) as $savedSearch) {
        // Only populate field possibilities as we go to convert each field
        $fieldPossibilities[] = $field;
        $fieldPossibilities[] = $field . '_high';
        $fieldPossibilities[] = $field . '_low';
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
          if (!is_array($formValue)) {
            if ($index === $relativeFieldName) {
              $hasRelative = TRUE;
              if (!empty($formValue)) {
                $isRelative = TRUE;
              }
              continue;
            }
            elseif ($index === 'event_low' || $index === 'event_high') {
              if ($isRelative || (!$isRelative && $formValue === '')) {
                unset($formValues[$index]);
              }
              else {
                $isHigh = substr($index, -5, 5) === '_high';
                $formValues[$index] = $this->getConvertedDateValue($formValue, $isHigh);
              }
            }
            continue;
          }
          if (!isset($formValue[0])) {
            // Any actual criteria will have this key set but skip any weird lines
            continue;
          }
          if ($formValue[0] === $relativeFieldName && !empty($formValue[2])) {
            $hasRelative = TRUE;
          }
          if ($formValue[0] === $relativeFieldName && empty($formValue[2])) {
            unset($formValues[$index]);
          }
          elseif (in_array($formValue[0], $fieldPossibilities)) {
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
          elseif (!$hasRelative) {
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
        if (is_array($formValue)) {
          if (isset($formValue[0]) && $formValue[0] === $oldName) {
            $formValues[$index][0] = $newName;
          }
        }
        elseif ($index === $oldName) {
          $formValues[$newName] = $formValue;
          unset($formValues[$oldName]);
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
    $apiGet = \Civi\Api4\SavedSearch::get(FALSE);
    $apiGet->addSelect('id', 'form_values');
    $apiGet->addWhere('form_values', 'LIKE', "%{$field}%");
    // Avoid error if column hasn't been added yet by pending upgrades
    if (version_compare(\CRM_Core_BAO_Domain::version(), '5.24', '>=')) {
      // Exclude SearchKit searches
      $apiGet->addWhere('api_entity', 'IS NULL');
    }
    return $apiGet->execute()->column(NULL, 'id');
  }

  /**
   * Convert the log_date saved search date fields to their correct name
   * default to switching to created_date as that is what the code did originally
   */
  public function renameLogFields() {
    $addedDate = TRUE;
    foreach ($this->getSearchesWithField('log_date') as $savedSearch) {
      $formValues = $savedSearch['form_values'];
      foreach ($formValues as $index => $formValue) {
        if (isset($formValue[0]) && $formValue[0] === 'log_date') {
          if ($formValue[2] == 2) {
            $addedDate = FALSE;
          }
        }
        if (isset($formValue[0]) && ($formValue[0] === 'log_date_high' || $formValue[0] === 'log_date_low')) {
          $isHigh = substr($index, -5, 5) === '_high';
          if ($addedDate) {
            $fieldName = 'created_date';
          }
          else {
            $fieldName = 'modified_date';
          }
          if ($isHigh) {
            $fieldName .= '_high';
          }
          else {
            $fieldName .= '_low';
          }
          $formValues[$index][0] = $fieldName;
        }
      }
      if ($formValues !== $savedSearch['form_values']) {
        civicrm_api3('SavedSearch', 'create', ['id' => $savedSearch['id'], 'form_values' => $formValues]);
      }
    }
  }

  /**
   * Convert Custom date fields in smart groups
   */
  public function convertCustomSmartGroups() {
    $custom_date_fields = CRM_Core_DAO::executeQuery("SELECT id FROM civicrm_custom_field WHERE data_type = 'Date' AND is_search_range = 1");
    while ($custom_date_fields->fetch()) {
      $savedSearches = $this->getSearchesWithField('custom_' . $custom_date_fields->id);
      foreach ($savedSearches as $savedSearch) {
        $form_values = $savedSearch['form_values'];
        foreach ($form_values as $index => $formValues) {
          if (isset($formValues[0]) && $formValues[0] === 'custom_' . $custom_date_fields->id && is_array($formValues[2])) {
            if (isset($formValues[2]['BETWEEN'])) {
              $form_values[] = ['custom_' . $custom_date_fields->id . '_low', '=', $this->getConvertedDateValue($formValues[2]['BETWEEN'][0], FALSE)];
              $form_values[] = ['custom_' . $custom_date_fields->id . '_high', '=', $this->getConvertedDateValue($formValues[2]['BETWEEN'][1], TRUE)];
              unset($form_values[$index]);
            }
            if (isset($formValues[2]['>='])) {
              $form_values[] = ['custom_' . $custom_date_fields->id . '_low', '=', $this->getConvertedDateValue($formValues[2]['>='], FALSE)];
              unset($form_values[$index]);
            }
            if (isset($formValues[2]['<='])) {
              $form_values[] = ['custom_' . $custom_date_fields->id . '_high', '=', $this->getConvertedDateValue($formValues[2]['<='], TRUE)];
              unset($form_values[$index]);
            }
          }
        }
        if ($form_values !== $savedSearch['form_values']) {
          civicrm_api3('SavedSearch', 'create', ['id' => $savedSearch['id'], 'form_values' => $form_values]);
        }
      }
    }
  }

}
