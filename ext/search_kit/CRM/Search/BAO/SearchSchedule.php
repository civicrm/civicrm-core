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

use CRM_Search_ExtensionUtil as E;

/**
 * Search Schedule BAO
 */
class CRM_Search_BAO_SearchSchedule extends CRM_Search_DAO_SearchSchedule {

  /**
   * Pseudoconstant options for the `format` field.
   *
   * @return array
   */
  public static function getFormats(): array {
    return [
      'csv' => E::ts('CSV'),
      'xlsx' => E::ts('Excel'),
      'ods' => E::ts('OpenOffice'),
      'pdf' => E::ts('PDF'),
    ];
  }

  /**
   * Pseudoconstant options for the `frequency_unit` field.
   *
   * Labels are pluralized according to `frequency_interval` when available.
   *
   * @param string|null $fieldName
   * @param array $params
   * @return array
   */
  public static function getFrequencyUnits(?string $fieldName = NULL, array $params = []): array {
    $count = $params['values']['frequency_interval'] ?? 1;
    return array_intersect_key(
      CRM_Core_SelectValues::getRecurringFrequencyUnits($count),
      array_flip(['day', 'week', 'month', 'year'])
    );
  }

}
