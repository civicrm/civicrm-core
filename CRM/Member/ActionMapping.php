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
 * Class CRM_Member_ActionMapping
 *
 * This defines the scheduled-reminder functionality for CiviMember
 * memberships. It allows one to target reminders based on join date
 * or end date, with additional filtering based on membership-type.
 */
class CRM_Member_ActionMapping extends \Civi\ActionSchedule\Mapping {

  /**
   * The value for civicrm_action_schedule.mapping_id which identifies the
   * "Membership Type" mapping.
   *
   * Note: This value is chosen to match legacy DB IDs.
   */
  const MEMBERSHIP_TYPE_MAPPING_ID = 4;

  /**
   * Register CiviMember-related action mappings.
   *
   * @param \Civi\ActionSchedule\Event\MappingRegisterEvent $registrations
   */
  public static function onRegisterActionMappings(\Civi\ActionSchedule\Event\MappingRegisterEvent $registrations) {
    $registrations->register(CRM_Member_ActionMapping::create([
      'id' => CRM_Member_ActionMapping::MEMBERSHIP_TYPE_MAPPING_ID,
      'entity' => 'civicrm_membership',
      'entity_label' => ts('Membership'),
      'entity_value' => 'civicrm_membership_type',
      'entity_value_label' => ts('Membership Type'),
      'entity_status' => 'auto_renew_options',
      'entity_status_label' => ts('Auto Renew Options'),
    ]));
  }

  /**
   * Get a list of available date fields.
   *
   * @return array
   *   Array(string $fieldName => string $fieldLabel).
   */
  public function getDateFields() {
    return [
      'join_date' => ts('Membership Join Date'),
      'start_date' => ts('Membership Start Date'),
      'end_date' => ts('Membership End Date'),
    ];
  }

  /**
   * Generate a query to locate recipients who match the given
   * schedule.
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   *   The schedule as configured by the administrator.
   * @param string $phase
   *   See, e.g., RecipientBuilder::PHASE_RELATION_FIRST.
   * @param array $defaultParams
   *
   * @return \CRM_Utils_SQL_Select
   * @see RecipientBuilder
   */
  public function createQuery($schedule, $phase, $defaultParams) {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from("{$this->entity} e")->param($defaultParams);
    $query['casAddlCheckFrom'] = 'civicrm_membership e';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;

    // Leaving this in case of legacy databases
    $query['casDateField'] = str_replace('membership_', 'e.', $schedule->start_action_date);

    // Options currently are just 'join_date', 'start_date', and 'end_date':
    // they need an alias
    if (strpos($query['casDateField'], 'e.') !== 0) {
      $query['casDateField'] = 'e.' . $query['casDateField'];
    }

    // FIXME: Numbers should be constants.
    if (in_array(2, $selectedStatuses)) {
      //auto-renew memberships
      $query->where("e.contribution_recur_id IS NOT NULL");
    }
    elseif (in_array(1, $selectedStatuses)) {
      $query->where("e.contribution_recur_id IS NULL");
    }

    if (!empty($selectedValues)) {
      $query->where("e.membership_type_id IN (@memberTypeValues)")
        ->param('memberTypeValues', $selectedValues);
    }
    else {
      // FIXME: The membership type is never null, so nobody will ever get a
      // reminder if no membership types are selected.  Either this should be a
      // validation on the reminder form or all types should get a reminder if
      // no types are selected.
      $query->where("e.membership_type_id IS NULL");
    }

    // FIXME: This makes a lot of sense for renewal reminders, but a user
    // scheduling another kind of reminder might not expect members to be
    // excluded if they have status overrides.  Ideally there would be some kind
    // of setting per reminder.
    $query->where("( e.is_override IS NULL OR e.is_override = 0 )");

    // FIXME: Similarly to overrides, excluding contacts who can't edit the
    // primary member makes sense in the context of renewals (see CRM-11342) but
    // would be a surprise for other use cases.
    $query->merge($this->prepareMembershipPermissionsFilter());

    // FIXME: A lot of undocumented stuff happens with regard to
    // `is_current_member`, and this is no exception.  Ideally there would be an
    // opportunity to pick statuses when setting up the scheduled reminder
    // rather than making the assumptions here.
    $query->where("e.status_id IN (#memberStatus)")
      ->param('memberStatus', \CRM_Member_PseudoConstant::membershipStatus(NULL, "(is_current_member = 1 OR name = 'Expired')", 'id'));

    // Why is this only for civicrm_membership?
    if ($schedule->start_action_date && $schedule->is_repeat == FALSE) {
      $query['casUseReferenceDate'] = TRUE;
    }

    return $query;
  }

  /**
   * Filter out the memberships that are inherited from a contact that the
   * recipient cannot edit.
   *
   * @return CRM_Utils_SQL_Select
   */
  protected function prepareMembershipPermissionsFilter() {
    $joins = [
      'cm' => 'LEFT JOIN civicrm_membership cm ON cm.id = e.owner_membership_id',
      'rela' => 'LEFT JOIN civicrm_relationship rela ON rela.contact_id_a = e.contact_id AND rela.contact_id_b = cm.contact_id AND rela.is_permission_a_b = #editPerm',
      'relb' => 'LEFT JOIN civicrm_relationship relb ON relb.contact_id_a = cm.contact_id AND relb.contact_id_b = e.contact_id AND relb.is_permission_b_a = #editPerm',
    ];

    return \CRM_Utils_SQL_Select::fragment()
      ->join(NULL, $joins)
      ->param('#editPerm', CRM_Contact_BAO_Relationship::EDIT)
      ->where('!( e.owner_membership_id IS NOT NULL AND rela.id IS NULL and relb.id IS NULL )');
  }

}
