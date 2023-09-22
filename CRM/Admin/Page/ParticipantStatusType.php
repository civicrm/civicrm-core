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
class CRM_Admin_Page_ParticipantStatusType extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * Get BAO name.
   *
   * @return string
   */
  public function getBAOName() {
    return 'CRM_Event_BAO_ParticipantStatusType';
  }

  public function browse() {
    $statusTypes = [];

    $dao = new CRM_Event_DAO_ParticipantStatusType();
    $dao->orderBy('weight');
    $dao->find();

    $visibilities = CRM_Core_PseudoConstant::visibility();

    // these statuses are reserved, but disabled by default - so should be disablable after being enabled
    $disablable = [
      'On waitlist',
      'Awaiting approval',
      'Pending from waitlist',
      'Pending from approval',
      'Rejected',
    ];

    while ($dao->fetch()) {
      CRM_Core_DAO::storeValues($dao, $statusTypes[$dao->id]);
      $action = array_sum(array_keys($this->links()));
      if ($dao->is_reserved) {
        $action -= CRM_Core_Action::DELETE;
        if (!in_array($dao->name, $disablable)) {
          $action -= CRM_Core_Action::DISABLE;
        }
      }
      $action -= $dao->is_active ? CRM_Core_Action::ENABLE : CRM_Core_Action::DISABLE;
      $statusTypes[$dao->id]['action'] = CRM_Core_Action::formLink(
        self::links(),
        $action,
        ['id' => $dao->id],
        ts('more'),
        FALSE,
        'participantStatusType.manage.action',
        'ParticipantStatusType',
        $dao->id
      );
      $statusTypes[$dao->id]['visibility'] = $visibilities[$dao->visibility_id];
    }
    $this->assign('rows', $statusTypes);
  }

  /**
   * @return string
   */
  public function editForm() {
    return 'CRM_Admin_Form_ParticipantStatusType';
  }

  /**
   * @return string
   */
  public function editName() {
    return 'Participant Status';
  }

  /**
   * @param null $mode
   *
   * @return string
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/participant_status';
  }

}
