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
 * Class CRM_Contribute_ActionMapping_ByType
 *
 * This defines the scheduled-reminder functionality for contribution
 * entities. It is useful for sending a reminder based on:
 *  - The receipt-date, cancel-date, or thankyou-date.
 *  - The type of contribution.
 */
class CRM_Contribute_ActionMapping_ByType extends CRM_Contribute_ActionMapping {

  /**
   * @return string
   */
  public function getName(): string {
    return 'contribtype';
  }

  /**
   * Get a printable label for this mapping type.
   *
   * @return string
   */
  public function getLabel(): string {
    return ts('Contribution Type');
  }

  public function modifyApiSpec(\Civi\Api4\Service\Spec\RequestSpec $spec) {
    parent::modifyApiSpec($spec);
    $spec->getFieldByName('entity_value')
      ->setLabel(ts('Financial Type'));
    $spec->getFieldByName('recipient_listing')
      ->setRequired($spec->getValue('limit_to') && $spec->getValue('recipient') === 'soft_credit_type');
  }

  /**
   * Get a list of value options.
   *
   * @return array
   *   Array(string $value => string $label).
   *   Ex: array(123 => 'Phone Call', 456 => 'Meeting').
   * @throws CRM_Core_Exception
   */
  public function getValueLabels(): array {
    return CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'get', []);
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
  public static function getRecipientTypes(): array {
    $types = parent::getRecipientTypes();
    $types['soft_credit_type'] = ts('Soft Credit Role');
    return $types;
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
  public function getRecipientListing($recipientType): array {
    switch ($recipientType) {
      case 'soft_credit_type':
        return \CRM_Core_OptionGroup::values('soft_credit_type', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'name');

      default:
        return [];
    }
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
  public function createQuery($schedule, $phase, $defaultParams): CRM_Utils_SQL_Select {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("civicrm_contribution e")->param($defaultParams);
    $query['casAddlCheckFrom'] = 'civicrm_contribution e';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;

    // $schedule->start_action_date is user-supplied data. validate.
    if (empty($schedule->absolute_date) && !array_key_exists($schedule->start_action_date, $this->getDateFields())) {
      throw new CRM_Core_Exception("Invalid date field");
    }
    $query['casDateField'] = $schedule->start_action_date ?? '';
    if (empty($query['casDateField']) && $schedule->absolute_date) {
      $query['casDateField'] = "'" . CRM_Utils_Type::escape($schedule->absolute_date, 'String') . "'";
    }
    // build where clause
    if (!empty($selectedValues)) {
      $query->where("e.financial_type_id IN (@selectedValues)")
        ->param('selectedValues', $selectedValues);
    }
    if (!empty($selectedStatuses)) {
      $query->where("e.contribution_status_id IN (#selectedStatuses)")
        ->param('selectedStatuses', $selectedStatuses);
    }

    if ($schedule->recipient_listing && $schedule->limit_to == 1) {
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
