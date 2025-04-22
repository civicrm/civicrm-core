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
 * This defines the scheduled-reminder functionality for CiviMember
 * memberships. It allows one to target reminders based on join date
 * or end date, with additional filtering based on membership-type.
 */
class CRM_Member_ActionMapping extends \Civi\ActionSchedule\MappingBase {

  /**
   * Note: This value is an integer for legacy reasons; but going forward any new
   * action mapping classes should return a string from `getId` instead of using a constant.
   */
  const MEMBERSHIP_TYPE_MAPPING_ID = 4;

  public function getId() {
    return self::MEMBERSHIP_TYPE_MAPPING_ID;
  }

  public function getName(): string {
    return 'membership_type';
  }

  public function getEntityName(): string {
    return 'Membership';
  }

  public function modifyApiSpec(\Civi\Api4\Service\Spec\RequestSpec $spec) {
    $spec->getFieldByName('entity_value')
      ->setLabel(ts('Membership Type'));
    $spec->getFieldByName('entity_status')
      ->setLabel(ts('Auto Renew Options'));
  }

  public function getValueLabels(): array {
    return CRM_Member_PseudoConstant::membershipType();
  }

  public function getStatusLabels(?array $entityValue): array {
    foreach (array_filter($entityValue ?? []) as $membershipType) {
      if (\CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $membershipType, 'auto_renew')) {
        return \CRM_Core_OptionGroup::values('auto_renew_options');
      }
    }
    return [];
  }

  public function getDateFields(?array $entityValue = NULL): array {
    return [
      'join_date' => ts('Member Since'),
      'start_date' => ts('Membership Start Date'),
      'end_date' => ts('Membership Expiration Date'),
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
  public function createQuery($schedule, $phase, $defaultParams): CRM_Utils_SQL_Select {
    $selectedValues = (array) \CRM_Utils_Array::explodePadded($schedule->entity_value);
    $selectedStatuses = (array) \CRM_Utils_Array::explodePadded($schedule->entity_status);

    $query = \CRM_Utils_SQL_Select::from('civicrm_membership e')->param($defaultParams);
    $query['casAddlCheckFrom'] = 'civicrm_membership e';
    $query['casContactIdField'] = 'e.contact_id';
    $query['casEntityIdField'] = 'e.id';
    $query['casContactTableAlias'] = NULL;

    // Leaving this in case of legacy databases
    $query['casDateField'] = str_replace('membership_', 'e.', ($schedule->start_action_date ?? ''));

    // Options currently are just 'join_date', 'start_date', and 'end_date':
    // they need an alias
    if (!str_starts_with($query['casDateField'], 'e.')) {
      $query['casDateField'] = 'e.' . $query['casDateField'];
    }

    // Exclude the renewals that are cancelled or failed.
    $nonRenewStatusIds = [
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', 'Cancelled'),
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', 'Failed'),
    ];

    // FIXME: Numbers should be constants.
    if (in_array(2, $selectedStatuses)) {
      //auto-renew memberships
      $query->join('cr', 'INNER JOIN civicrm_contribution_recur cr on e.contribution_recur_id = cr.id');
      $query->where("cr.contribution_status_id NOT IN (#nonRenewStatusIds)")
        ->param('nonRenewStatusIds', $nonRenewStatusIds);
    }
    elseif (in_array(1, $selectedStatuses)) {
      // non-auto-renew memberships
      // Include the renewals that were cancelled or Failed.
      $query->join('cr', 'LEFT JOIN civicrm_contribution_recur cr on e.contribution_recur_id = cr.id');
      $query->where("e.contribution_recur_id IS NULL OR (
        e.contribution_recur_id IS NOT NULL AND cr.contribution_status_id IN (#nonRenewStatusIds)
        )")
        ->param('nonRenewStatusIds', $nonRenewStatusIds);
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
    $query->where("e.is_override = 0");

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

  /**
   * Determine whether a schedule based on this mapping should
   * reset the reminder state if the trigger date changes.
   *
   * @return bool
   *
   * @param \CRM_Core_DAO_ActionSchedule $schedule
   */
  public function resetOnTriggerDateChange($schedule): bool {
    if ($schedule->absolute_date !== NULL) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

}
