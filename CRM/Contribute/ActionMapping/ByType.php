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

use Civi\ActionSchedule\RecipientBuilder;

/**
 * Class CRM_Contribute_ActionMapping_ByType
 *
 * This defines the scheduled-reminder functionality for contribution
 * entities. It is useful for sending a reminder based on:
 *  - The receipt-date, cancel-date, or thankyou-date.
 *  - The type of contribution.
 */
class CRM_Contribute_ActionMapping_ByType implements \Civi\ActionSchedule\MappingInterface {

  /**
   * The value for civicrm_action_schedule.mapping_id which identifies the
   * "Contribution Page" mapping.
   */
  const MAPPING_ID = 'contribtype';

  /**
   * Register Activity-related action mappings.
   *
   * @param \Civi\ActionSchedule\Event\MappingRegisterEvent $registrations
   */
  public static function onRegisterActionMappings(\Civi\ActionSchedule\Event\MappingRegisterEvent $registrations) {
    $registrations->register(new static());
  }

  /**
   * @return mixed
   */
  public function getId() {
    return self::MAPPING_ID;
  }

  /**
   * @return string
   */
  public function getEntity() {
    return 'civicrm_contribution';
  }

  /**
   * Get a printable label for this mapping type.
   *
   * @return string
   */
  public function getLabel() {
    return ts('Contribution Type');
  }

  /**
   * Get a printable label to use as the header on the 'value' filter.
   *
   * @return string
   */
  public function getValueHeader() {
    return ts('Financial Type');
  }

  /**
   * Get a printable label to use as the header on the 'status' filter.
   *
   * @return string
   */
  public function getStatusHeader() {
    return ts('Contribution Status');
  }

  /**
   * Get a list of value options.
   *
   * @return array
   *   Array(string $value => string $label).
   *   Ex: array(123 => 'Phone Call', 456 => 'Meeting').
   * @throws CRM_Core_Exception
   */
  public function getValueLabels() {
    return CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'get', array());
  }

  /**
   * Get a list of status options.
   *
   * @param string|int $value
   *   The list of status options may be contingent upon the selected filter value.
   *   This is the selected filter value.
   * @return array
   *   Array(string $value => string $label).
   *   Ex: Array(123 => 'Completed', 456 => 'Scheduled').
   * @throws CRM_Core_Exception
   */
  public function getStatusLabels($value) {
    return CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'get', array());
  }

  /**
   * Get a list of available date fields.
   *
   * @return array
   *   Array(string $fieldName => string $fieldLabel).
   */
  public function getDateFields() {
    return array(
      'receive_date' => ts('Receive Date'),
      'cancel_date' => ts('Cancel Date'),
      'receipt_date' => ts('Receipt Date'),
      'thankyou_date' => ts('Thank You Date'),
    );
  }

  /**
   * Get a list of recipient types.
   *
   * Note: A single schedule may filter on *zero* or *one* recipient types.
   * When an admin chooses a value, it's stored in $schedule->recipient.
   *
   * @return array
   *   array(string $value => string $label).
   *   Ex: array('assignee' => 'Activity Assignee').
   */
  public function getRecipientTypes() {
    return array(
      'soft_credit_type' => ts('Soft Credit Role'),
    );
  }

  /**
   * Get a list of recipients which match the given type.
   *
   * Note: A single schedule may filter on *multiple* recipients.
   * When an admin chooses value(s), it's stored in $schedule->recipient_listing.
   *
   * @param string $recipientType
   *   Ex: 'participant_role'.
   * @return array
   *   Array(mixed $name => string $label).
   *   Ex: array(1 => 'Attendee', 2 => 'Volunteer').
   * @see getRecipientTypes
   */
  public function getRecipientListing($recipientType) {
    switch ($recipientType) {
      case 'soft_credit_type':
        return \CRM_Core_OptionGroup::values('soft_credit_type', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name');

      default:
        return array();
    }
  }

  /**
   * Determine whether a schedule based on this mapping is sufficiently
   * complete.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   * @return array
   *   Array (string $code => string $message).
   *   List of error messages.
   */
  public function validateSchedule($schedule) {
    return array();
  }

  /**
   * Generate a query to locate contacts who match the given
   * schedule.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   * @param array $defaultParams
   *   Default parameters that should be included with query.
   * @return \CRM_Utils_SQL_Select
   * @see RecipientBuilder
   * @throws CRM_Core_Exception
   */
  public function createQuery($schedule, $phase, $defaultParams) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("civicrm_contribution e")->param($defaultParams);;
    $query['casAddlCheckFrom'] = 'civicrm_contribution e';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;

    // $schedule->start_action_date is user-supplied data. validate.
    if (!array_key_exists($schedule->start_action_date, $this->getDateFields())) {
      throw new CRM_Core_Exception("Invalid date field");
    }
    $query['casDateField'] = $schedule->start_action_date;

    // build where clause
    if (!empty($selectedValues)) {
      $query->where("e.financial_type_id IN (@selectedValues)")
        ->param('selectedValues', $selectedValues);
    }
    if (!empty($selectedStatuses)) {
      $query->where("e.contribution_status_id IN (#selectedStatuses)")
        ->param('selectedStatuses', $selectedStatuses);
    }

    if ($schedule->recipient_listing && $schedule->limit_to) {
      switch ($schedule->recipient) {
        case 'soft_credit_type':
          $query['casContactIdField'] = 'soft.contact_id';
          $query->join('soft', 'INNER JOIN civicrm_contribution_soft soft ON soft.contribution_id = e.id')
            ->where("soft.soft_credit_type_id IN (#recipList)")
            ->param('recipList', \CRM_Utils_Array::explodePadded($schedule->recipient_listing));
          break;
      }
    }

    return $query;
  }

}
