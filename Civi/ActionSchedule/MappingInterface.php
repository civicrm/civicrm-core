<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
   * Just return an empty array.
   *
   * There's only one context where this returns actual data -- when using
   * something like 'Limit To: Participant Role: Attendee or Speaker'.
   * Unfortunately, that use-case has several other hacky, hard-coded bits
   * which make it work. New entities can't take advantage of this because
   * they don't have similar hacky bits. More generally, all the "Recipients"/
   * "Limit To"/"Also Include" stuff needs a rethink.
   *
   * @deprecated
   * @param string $recipientType
   * @return array
   *   Array(mixed $name => string $label).
   *   Ex: array(1 => 'Attendee', 2 => 'Volunteer').
   */
  public function getRecipientListing($recipientType);

  /**
   * Just return an empty array.
   *
   * There are two contexts where this returns actual data -- when using
   * Activities with the "Recipient" option, or whe using Events with the
   * "Limit To:" option.  However, the mechanisms around these do not
   * work the same and rely on on hacky, hard-coded bits in the UI.
   * More generally, all the "Recipients"/"Limit To"/"Also Include" stuff
   * needs a rethink.
   *
   * @param bool|NULL $noThanksJustKidding
   *   This is ridiculous and should not exist.
   *   If true, don't do our main job.
   * @return array
   *   array(mixed $value => string $label).
   *   Ex: array('assignee' => 'Activity Assignee').
   */
  public function getRecipientTypes($noThanksJustKidding = FALSE);

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
