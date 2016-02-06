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

namespace Civi\ActionSchedule;

/**
 * Interface MappingInterface
 * @package Civi\ActionSchedule
 */
interface MappingInterface {

  /**
   * @return mixed
   */
  public function getId();

  /**
   * @return string
   */
  public function getEntity();

  /**
   * Get a printable label for this mapping type.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Get a printable label to use as the header on the 'value' filter.
   *
   * @return string
   */
  public function getValueHeader();

  /**
   * Get a printable label to use as the header on the 'status' filter.
   *
   * @return string
   */
  public function getStatusHeader();

  /**
   * Get a list of value options.
   *
   * @return array
   *   Array(string $value => string $label).
   *   Ex: array(123 => 'Phone Call', 456 => 'Meeting').
   */
  public function getValueLabels();

  /**
   * Get a list of status options.
   *
   * @param string|int $value
   *   The list of status options may be contingent upon the selected filter value.
   *   This is the selected filter value.
   * @return array
   *   Array(string $value => string $label).
   *   Ex: Array(123 => 'Completed', 456 => 'Scheduled').
   */
  public function getStatusLabels($value);

  /**
   * Get a list of available date fields.
   *
   * @return array
   *   Array(string $fieldName => string $fieldLabel).
   */
  public function getDateFields();

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
  public function getRecipientTypes();

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
  public function getRecipientListing($recipientType);

  /**
   * Determine whether a schedule based on this mapping is sufficiently
   * complete.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   * @return array
   *   Array (string $code => string $message).
   *   List of error messages.
   */
  public function validateSchedule($schedule);

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
  public function createQuery($schedule, $phase, $defaultParams);

}
