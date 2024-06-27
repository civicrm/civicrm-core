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

namespace Civi\ActionSchedule;

use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;

/**
 * Interface MappingInterface
 * @package Civi\ActionSchedule
 */
interface MappingInterface extends SpecProviderInterface {

  /**
   * Unique identifier of this mapping type.
   *
   * Should return a "machine_name" style string (same output as `getName()`)
   * Note: Some legacy implementations return an int. Don't follow those examples.
   * @return string|int
   */
  public function getId();

  /**
   * Unique name of this mapping type.
   *
   * Should return a "machine_name" style string (should be the same as `getId()`).
   * @return string
   */
  public function getName(): string;

  /**
   * Name of the table belonging to the main entity e.g. `civicrm_activity`
   * @param \CRM_Core_DAO_ActionSchedule $actionSchedule
   * @return string
   */
  public function getEntityTable(\CRM_Core_DAO_ActionSchedule $actionSchedule): string;

  /**
   * Main entity name e.g. `Activity`
   * @return string
   */
  public function getEntityName(): string;

  /**
   * Label of this mapping type as shown in the "Entity" dropdown-select on the form.
   * @return string
   */
  public function getLabel();

  /**
   * Get option list for the `entity_value` field.
   */
  public function getValueLabels(): array;

  /**
   * Get option list for the `entity_status` field.
   *
   * @param array|null $entityValue
   *   Selected value(s) of the `entity_value` field.
   */
  public function getStatusLabels(?array $entityValue): array;

  /**
   * Get option list for `start_action_date` & `end_date` fields.
   *
   * @param array|null $entityValue
   *   Selected value(s) of the `entity_value` field.
   * @return array
   */
  public function getDateFields(?array $entityValue = NULL): array;

  /**
   * Get the option list for `limit_to` (non-associative format)
   *
   * @return array
   */
  public static function getLimitToOptions(): array;

  /**
   * Get option list for `recipient` field.
   *
   * Note: A single schedule may filter on *zero* or *one* recipient types.
   * When an admin chooses a value, it's stored in $schedule->recipient.
   *
   * @return array
   *   Ex: ['assignee' => 'Activity Assignee', ...].
   */
  public static function getRecipientTypes(): array;

  /**
   * Get option list for `recipient_listing` field.
   *
   * @param string $recipientType
   *   Value of `recipient` field
   * @return array
   *   Ex: [1 => 'Attendee', 2 => 'Volunteer', ...].
   * @see getRecipientTypes
   */
  public function getRecipientListing($recipientType): array;

  /**
   * Check if the user has permission to create a reminder for given `entity_value`.
   *
   * This function is called by the form to escalate permissions so that less-privileged users can
   * create a reminder for a particular entity even if they do not have 'administer CiviCRM data'.
   *
   * Return FALSE and the default permission of 'administer CiviCRM data' will be enforced.
   *
   * Note that `entity_value` is a serialized field, so will be passed as an array, even though
   * more than one value doesn't make sense in the context of embedding the ScheduledReminder form
   * on a page belonging to a single entity.
   *
   * @param array $entityValue
   * @return bool
   */
  public function checkAccess(array $entityValue): bool;

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
   */
  public function createQuery($schedule, $phase, $defaultParams): \CRM_Utils_SQL_Select;

  /**
   * Determine whether a schedule based on this mapping should
   * reset the reminder state if the trigger date changes.
   *
   * @return bool
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   */
  public function resetOnTriggerDateChange($schedule): bool;

  /**
   * Determine whether a schedule based on this mapping should
   * send to additional contacts.
   */
  public function sendToAdditional($entityId): bool;

  public function getBccRecipients(\CRM_Core_DAO_ActionSchedule $schedule): ?array;

  public function getAlternateRecipients(\CRM_Core_DAO_ActionSchedule $schedule): ?array;

}
