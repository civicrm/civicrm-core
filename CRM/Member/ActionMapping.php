<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
    $registrations->register(CRM_Member_ActionMapping::create(array(
      'id' => CRM_Member_ActionMapping::MEMBERSHIP_TYPE_MAPPING_ID,
      'entity' => 'civicrm_membership',
      'entity_label' => ts('Membership'),
      'entity_value' => 'civicrm_membership_type',
      'entity_value_label' => ts('Membership Type'),
      'entity_status' => 'auto_renew_options',
      'entity_status_label' => ts('Auto Renew Options'),
      'entity_date_start' => 'membership_join_date',
      'entity_date_end' => 'membership_end_date',
    )));
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

    $query = \CRM_Utils_SQL_Select::from("{$this->entity} e")->param($defaultParams);;
    $query['casAddlCheckFrom'] = 'civicrm_membership e';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;
    $query['casDateField'] = str_replace('membership_', 'e.', $schedule->start_action_date);

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
      $query->where("e.membership_type_id IS NULL");
    }

    $query->where("( e.is_override IS NULL OR e.is_override = 0 )");
    $query->merge($this->prepareMembershipPermissionsFilter());
    $query->where("e.status_id IN (#memberStatus)")
      ->param('memberStatus', \CRM_Member_PseudoConstant::membershipStatus(NULL, "(is_current_member = 1 OR name = 'Expired')", 'id'));

    // Why is this only for civicrm_membership?
    if ($schedule->start_action_date && $schedule->is_repeat == FALSE) {
      $query['casUseReferenceDate'] = TRUE;
    }

    return $query;
  }

  /**
   * @return array
   */
  protected function prepareMembershipPermissionsFilter() {
    $query = '
SELECT    cm.id AS owner_id, cm.contact_id AS owner_contact, m.id AS slave_id, m.contact_id AS slave_contact, cmt.relationship_type_id AS relation_type, rel.contact_id_a, rel.contact_id_b, rel.is_permission_a_b, rel.is_permission_b_a
FROM      civicrm_membership m
LEFT JOIN civicrm_membership cm ON cm.id = m.owner_membership_id
LEFT JOIN civicrm_membership_type cmt ON cmt.id = m.membership_type_id
LEFT JOIN civicrm_relationship rel ON ( ( rel.contact_id_a = m.contact_id AND rel.contact_id_b = cm.contact_id AND rel.relationship_type_id = cmt.relationship_type_id )
                                        OR ( rel.contact_id_a = cm.contact_id AND rel.contact_id_b = m.contact_id AND rel.relationship_type_id = cmt.relationship_type_id ) )
WHERE     m.owner_membership_id IS NOT NULL AND
          ( rel.is_permission_a_b = 0 OR rel.is_permission_b_a = 0)

';
    $excludeIds = array();
    $dao = \CRM_Core_DAO::executeQuery($query, array());
    while ($dao->fetch()) {
      if ($dao->slave_contact == $dao->contact_id_a && $dao->is_permission_a_b == 0) {
        $excludeIds[] = $dao->slave_contact;
      }
      elseif ($dao->slave_contact == $dao->contact_id_b && $dao->is_permission_b_a == 0) {
        $excludeIds[] = $dao->slave_contact;
      }
    }

    if (!empty($excludeIds)) {
      return \CRM_Utils_SQL_Select::fragment()
        ->where("!casContactIdField NOT IN (#excludeMemberIds)")
        ->param(array(
          '#excludeMemberIds' => $excludeIds,
        ));
    }
    return NULL;
  }

}
