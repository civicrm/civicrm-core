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
 * Class CRM_Member_Utils_RelationshipProcessor
 */
class CRM_Member_Utils_RelationshipProcessor {

  /**
   * Contact IDs to process.
   *
   * @var int[]
   */
  protected $contactIDs = [];

  /**
   * Memberships for related contacts.
   *
   * @var array
   */
  protected $memberships = [];

  /**
   * Is the relationship being enabled.
   *
   * @var bool
   */
  protected $active;

  /**
   * CRM_Member_Utils_RelationshipProcessor constructor.
   *
   * @param int[] $contactIDs
   * @param bool $active
   *
   * @throws \CRM_Core_Exception
   */
  public function __construct($contactIDs, $active) {
    $this->contactIDs = $contactIDs;
    $this->active = $active;
    $this->setMemberships();
  }

  /**
   * Get memberships for contact of potentially inheritable types.
   *
   * @param int $contactID
   *
   * @param array $membershipTypeIds
   *
   * @return array
   */
  public function getRelationshipMembershipsForContact(int $contactID, array $membershipTypeIds = []):array {
    $memberships = [];
    foreach ($this->memberships as $id => $membership) {
      if ((int) $membership['contact_id'] === $contactID &&
        (in_array($membership['membership_type_id'], $membershipTypeIds) || empty($membershipTypeIds))) {
        $memberships[$id] = $membership;
      }
    }
    return $memberships;
  }

  /**
   * Set the relevant memberships on the class.
   *
   * We are looking at relationships that are potentially inheritable
   * so we can filter out membership types with NULL relationship_type_id
   *
   * @throws \CRM_Core_Exception
   */
  protected function setMemberships() {
    $return = array_keys(civicrm_api3('Membership', 'getfields', [])['values']);
    $return[] = 'owner_membership_id.contact_id';
    $return[] = 'membership_type_id.relationship_type_id';
    $return[] = 'membership_type_id.relationship_direction';
    $memberships = civicrm_api3('Membership', 'get', [
      'contact_id' => ['IN' => $this->contactIDs],
      'status_id' => ['IN' => $this->getInheritableMembershipStatusIDs()],
      'membership_type_id.relationship_type_id' => ['IS NOT NULL' => TRUE],
      'return' => $return,
      'options' => ['limit' => 0],
    ])['values'];
    foreach ($memberships as $id => $membership) {
      if (!isset($memberships[$id]['inheriting_membership_ids'])) {
        $memberships[$id]['inheriting_membership_ids'] = [];
        $memberships[$id]['inheriting_contact_ids'] = [];
      }
      if (!empty($membership['owner_membership_id']) && isset($memberships[$membership['owner_membership_id']])) {
        $memberships[$membership['owner_membership_id']]['inheriting_membership_ids'][] = (int) $membership['id'];
        $memberships[$membership['owner_membership_id']]['inheriting_contact_ids'][] = (int) $membership['contact_id'];
        $membership['owner_membership_id.contact_id'] = (int) $membership['owner_membership_id.contact_id'];
      }
      // Just for the sake of having an easier parameter to access.
      $memberships[$id]['owner_contact_id'] = $membership['owner_membership_id.contact_id'] ?? NULL;

      // Ensure it is an array & use an easier parameter name.
      $memberships[$id]['relationship_type_ids'] = (array) $membership['membership_type_id.relationship_type_id'];
      $memberships[$id]['relationship_type_directions'] = (array) $membership['membership_type_id.relationship_direction'];

      foreach ($memberships[$id]['relationship_type_ids'] as $index => $relationshipType) {
        $memberships[$id]['relationship_type_keys'][] = $relationshipType . '_' . $memberships[$id]['relationship_type_directions'][$index];
      }
    }
    $this->memberships = $memberships;
  }

  /**
   * Get membership statuses that could be inherited.
   *
   * @return array
   */
  protected function getInheritableMembershipStatusIDs() {
    // @todo - clean this up - was legacy code that got moved.
    $membershipStatusRecordIds = [];
    // CRM-15829 UPDATES
    // If we're looking for active memberships we must consider pending (id: 5) ones too.
    // Hence we can't just call CRM_Member_BAO_Membership::getValues below with the active flag, is it would completely miss pending relatioships.
    // As suggested by @davecivicrm, the pending status id is fetched using the CRM_Member_PseudoConstant::membershipStatus() class and method, since these ids differ from system to system.
    $pendingStatusId = array_search('Pending', CRM_Member_PseudoConstant::membershipStatus());

    $query = 'SELECT * FROM `civicrm_membership_status`';
    if ($this->active) {
      $query .= ' WHERE `is_current_member` = 1 OR `id` = %1 ';
    }

    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$pendingStatusId, 'Integer']]);

    while ($dao->fetch()) {
      $membershipStatusRecordIds[$dao->id] = $dao->id;
    }
    return $membershipStatusRecordIds;
  }

}
