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
   * Version we are upgrading to.
   *
   * @var string
   */
  protected $upgradeVersion;

  /**
   * @return string
   */
  public function getUpgradeVersion() {
    return $this->upgradeVersion;
  }

  /**
   * @param string $upgradeVersion
   */
  public function setUpgradeVersion($upgradeVersion) {
    $this->upgradeVersion = $upgradeVersion;
  }

  /**
   * CRM_Upgrade_Incremental_MessageTemplates constructor.
   *
   * @param string $upgradeVersion
   */
  public function __construct($upgradeVersion) {
    $this->setUpgradeVersion($upgradeVersion);
  }

  /**
   * Get any conversions required for saved smart groups.
   *
   * @return array
   */
  public function getSmartGroupConversions() {
    return [
      [
        'version' => '5.11.alpha1',
        'upgrade_descriptors' => [ts('Upgrade grant smart groups to datepicker format')],
        'actions' => [
          'function' => 'datepickerConversion',
          'fields' => [
            'grant_application_received_date',
            'grant_decision_date',
            'grant_money_transfer_date',
            'grant_due_date'
          ]
        ]
      ]
    ];
  }

  /**
   * Convert any
   * @param array $fields
   */
  public function datePickerConversion($fields) {
    $fieldPossibilities = [];
    foreach ($fields as $field) {
      $fieldPossibilities[] = $field;
      $fieldPossibilities[] = $field . '_high';
      $fieldPossibilities[] = $field . '_low';
    }

    foreach ($fields as $field) {
      $savedSearches = civicrm_api3('SavedSearch', 'get', [
        'options' => ['limit' => 0],
        'form_values' => ['LIKE' => "%{$field}%"],
      ])['values'];
      foreach ($savedSearches as $savedSearch) {
        $formValues = $savedSearch['form_values'];
        foreach ($formValues as $index => $formValue) {
          if (in_array($formValue[0], $fieldPossibilities)) {
            $formValues[$index][2] = $this->getConvertedDateValue($formValue[2]);
          }
        }
        if ($formValues !== $savedSearch['form_values']) {
          civicrm_api3('SavedSearch', 'create', ['id' => $savedSearch['id'], 'form_values' => $formValues]);
        }
      }
    }
  }

  /**
   * Update message templates.
   */
  public function updateGroups() {
    $conversions = $this->getSmartGroupConversionsToApply();
    foreach ($conversions as $conversion) {
      $function = $conversion['function'];
      $this->{$function}($conversion['fields']);
    }
  }

  /**
   * Get any required template updates.
   *
   * @return array
   */
  public function getSmartGroupConversionsToApply() {
    $conversions = $this->getSmartGroupConversions();
    $return = [];
    foreach ($conversions as $conversion) {
      if ($conversion['version'] === $this->getUpgradeVersion()) {
        $return[] = $conversion['actions'];
      }
    }
    return $return;
  }

  /**
   * Get converted date value.
   *
   * @param string $dateValue
   *
   * @return string
   *   $dateValue
   */
  protected function getConvertedDateValue($dateValue) {
    if (date('Y-m-d', strtotime($dateValue)) !== $dateValue
      && date('Y-m-d H:i:s', strtotime($dateValue)) !== $dateValue
    ) {
      $dateValue = date('Y-m-d H:i:s', strtotime(CRM_Utils_Date::processDate($dateValue)));
    }
    return $dateValue;
  }

}
