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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This form is loaded when custom data is loaded by ajax.
 *
 * The forms ALSO need to call enough functions from Form_CustomData
 * to ensure the fields they need are added to the form or the values will be
 * ignored in post process (ie. quick form will filter them out).
 *
 * This form never submits & hence has no post process.
 */
class CRM_Custom_Form_CustomDataByType extends CRM_Core_Form {

  /**
   * @var array
   */
  protected $groupTree;

  /**
   * @var array
   */
  private $groupCount;

  /**
   * @var int|mixed|string|null
   */
  private $groupID;

  /**
   * Preprocess function.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess(): void {

    $customDataType = CRM_Utils_Request::retrieve('type', 'String', NULL, TRUE);
    $subType = CRM_Utils_Request::retrieve('subType', 'String');
    $this->groupCount = CRM_Utils_Request::retrieve('cgcount', 'Positive');
    $this->groupID = $groupID = CRM_Utils_Request::retrieve('groupID', 'Positive');
    $onlySubType = CRM_Utils_Request::retrieve('onlySubtype', 'Boolean');

    $this->_action = CRM_Utils_Request::retrieve('action', 'Alphanumeric');
    $this->assign('cdType', FALSE);
    $this->assign('cgCount', $this->groupCount);

    $contactTypes = CRM_Contact_BAO_ContactType::contactTypeInfo();
    if (array_key_exists($customDataType, $contactTypes)) {
      $this->assign('contactId', CRM_Utils_Request::retrieve('entityID', 'Positive'));
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree(CRM_Utils_Request::retrieve('type', 'String'),
      NULL,
      CRM_Utils_Request::retrieve('entityID', 'Positive'),
      $groupID,
      $subType,
      CRM_Utils_Request::retrieve('subName', 'String'),
      TRUE,
      $onlySubType
    );

    // GJ-PATCH-START: Participant custom-group filtering
    //
    // The Participant Edit form can receive custom groups from multiple
    // event registration profiles. Only the custom groups configured for
    // this participant's registration role should be included in the
    // group tree.
    //
    // The participant's event and registration role are determined from
    // the participant record. The corresponding event registration
    // configuration is then used to resolve the configured custom-group
    // IDs. This avoids hard-coding event-specific custom-group IDs.
    //
    // Note that UF group IDs and custom-group IDs are different ID spaces.
    // The helper resolves the UF groups configured for the event
    // registration profile to their corresponding custom-group IDs before
    // the group tree is filtered.
    if ($customDataType === 'Participant') {

      $participantID = CRM_Utils_Request::retrieve(
        'entityID',
        'Positive'
      );

      if ($participantID) {
        $allowedGroupIDs = $this->getParticipantCustomGroupIDs($participantID);

        foreach (array_keys($groupTree) as $treeGroupID) {
          if (!in_array((int) $treeGroupID, $allowedGroupIDs, TRUE)) {
            unset($groupTree[$treeGroupID]);
          }
        }
      }
    }
    // GJ-PATCH-END: Participant custom-group filtering

    // we should use simplified formatted groupTree
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, $this->groupCount, $this);

    if (isset($this->groupTree) && is_array($this->groupTree)) {
      $keys = array_keys($groupTree);
      foreach ($keys as $key) {
        $this->groupTree[$key] = $groupTree[$key];
      }
    }
    else {
      $this->groupTree = $groupTree;
    }

    $this->assign('suppressForm', TRUE);
    $this->controller->_generateQFKey = FALSE;
  }

  /**
   * Set defaults.
   *
   * @return array
   */
  public function setDefaultValues(): array {
    $defaults = [];
    CRM_Core_BAO_CustomGroup::setDefaults($this->groupTree, $defaults, FALSE, FALSE, $this->get('action'));
    return $defaults;
  }

  /**
   * Build quick form.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm(): void {
    $this->addElement('hidden', 'hidden_custom', 1);
    $this->addElement('hidden', "hidden_custom_group_count[{$this->groupID}]", $this->groupCount);
    CRM_Core_BAO_CustomGroup::buildQuickForm($this, $this->groupTree);
  }

  // GJ-PATCH-START: Participant custom-group mapping
  /**
   * Determine the custom group IDs configured for a participant's
   * event registration role.
   *
   * The participant's registration role determines whether the primary
   * event registration configuration or the additional-participant
   * configuration is used. The configured UF groups are then resolved
   * through their UF fields to the corresponding custom groups.
   *
   * @param int $participantID
   *
   * @return int[]
   */
  private function getParticipantCustomGroupIDs(int $participantID): array {
    static $cache = [];

    if (isset($cache[$participantID])) {
      return $cache[$participantID];
    }

    $participant = new CRM_Event_DAO_Participant();
    $participant->id = $participantID;

    if (!$participant->find(TRUE)) {
      return $cache[$participantID] = [];
    }

    $eventID = (int) $participant->event_id;

    $module = $participant->registered_by_id
      ? 'CiviEvent_Additional'
      : 'CiviEvent';

    $sql = "
      SELECT DISTINCT cf.custom_group_id
        FROM civicrm_uf_join uj
        INNER JOIN civicrm_uf_field uf
          ON uf.uf_group_id = uj.uf_group_id
        INNER JOIN civicrm_custom_field cf
          ON cf.id = CAST(SUBSTRING(uf.field_name, 8) AS UNSIGNED)
       WHERE uj.entity_table = 'civicrm_event'
         AND uj.entity_id = %1
         AND uj.module = %2
         AND uj.is_active = 1
         AND cf.custom_group_id IS NOT NULL
    ";

    $params = [
      1 => [$eventID, 'Integer'],
      2 => [$module, 'String'],
    ];

    $customGroupIDs = [];

    $result = CRM_Core_DAO::executeQuery($sql, $params);

    while ($result->fetch()) {
      $customGroupIDs[] = (int) $result->custom_group_id;
    }

    return $cache[$participantID] = $customGroupIDs;
  }
  // GJ-PATCH-END: Participant custom-group mapping

}
