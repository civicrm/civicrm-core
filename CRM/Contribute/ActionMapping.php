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
 * This defines the scheduled-reminder functionality for contribution
 * entities. It is useful for sending a reminder based on:
 *  - The receipt-date, cancel-date, or thankyou-date.
 *  - The type of contribution.
 * @service
 * @internal
 */
abstract class CRM_Contribute_ActionMapping extends \Civi\ActionSchedule\MappingBase {

  public function getEntityName(): string {
    return 'Contribution';
  }

  public function getStatusHeader(): string {
    return ts('Contribution Status');
  }

  /**
   * Get a list of status options.
   *
   * @param string|int $value
   * @return array
   * @throws CRM_Core_Exception
   */
  public function getStatusLabels($value): array {
    return CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'get', []);
  }

  public function getDateFields(): array {
    return [
      'receive_date' => ts('Receive Date'),
      'cancel_date' => ts('Cancel Date'),
      'receipt_date' => ts('Receipt Date'),
      'thankyou_date' => ts('Thank You Date'),
    ];
  }

}
