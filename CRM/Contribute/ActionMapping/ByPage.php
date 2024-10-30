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
 * Class CRM_Contribute_ActionMapping_ByPage
 *
 * This defines the scheduled-reminder functionality for contribution
 * entities. It is useful for sending a reminder based on:
 *  - The receipt-date, cancel-date, or thankyou-date.
 *  - The page on which the contribution was made.
 */
class CRM_Contribute_ActionMapping_ByPage extends CRM_Contribute_ActionMapping {

  /**
   * @return string
   */
  public function getName(): string {
    return 'contribpage';
  }

  /**
   * Get a printable label for this mapping type.
   *
   * @return string
   */
  public function getLabel(): string {
    return ts('Contribution Page');
  }

  public function modifyApiSpec(\Civi\Api4\Service\Spec\RequestSpec $spec) {
    parent::modifyApiSpec($spec);
    $spec->getFieldByName('entity_value')
      ->setLabel(ts('Contribution Page'));
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
    return CRM_Contribute_BAO_Contribution::buildOptions('contribution_page_id', 'get', []);
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
      $query->where("e.contribution_page_id IN (@selectedValues)")
        ->param('selectedValues', $selectedValues);
    }
    if (!empty($selectedStatuses)) {
      $query->where("e.contribution_status_id IN (#selectedStatuses)")
        ->param('selectedStatuses', $selectedStatuses);
    }

    return $query;
  }

}
