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
 * Page for displaying list of membership statuses
 */
class CRM_Member_Page_MembershipStatus extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * Get BAO Name.
   *
   * @return string
   *   Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_Member_BAO_MembershipStatus';
  }

  /**
   * Browse all custom data groups.
   *
   *
   * @return void
   */
  public function browse() {
    // get all custom groups sorted by weight
    $membershipStatus = [];
    $dao = new CRM_Member_DAO_MembershipStatus();

    $dao->orderBy('weight');
    $dao->find();

    while ($dao->fetch()) {
      $membershipStatus[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $membershipStatus[$dao->id]);

      // form all action links
      $action = array_sum(array_keys($this->links()));
      // update enable/disable links depending on if it is is_reserved or is_active
      if (!$dao->is_reserved) {
        if ($dao->is_active) {
          $action -= CRM_Core_Action::ENABLE;
        }
        else {
          $action -= CRM_Core_Action::DISABLE;
        }
        $membershipStatus[$dao->id]['action'] = CRM_Core_Action::formLink(self::links(), $action,
          ['id' => $dao->id],
          ts('more'),
          FALSE,
          'membershipStatus.manage.action',
          'MembershipStatus',
          $dao->id
        );
      }
      $startEvent = $membershipStatus[$dao->id]['start_event'] ?? NULL;
      $endEvent = $membershipStatus[$dao->id]['end_event'] ?? NULL;
      $startEventUnit = $membershipStatus[$dao->id]['start_event_adjust_unit'] ?? NULL;
      $endEventUnit = $membershipStatus[$dao->id]['end_event_adjust_unit'] ?? NULL;
      $startEventInterval = $membershipStatus[$dao->id]['start_event_adjust_interval'] ?? NULL;
      $endEventInterval = $membershipStatus[$dao->id]['end_event_adjust_interval'] ?? NULL;

      if ($startEvent) {
        $membershipStatus[$dao->id]['start_event'] = ($startEvent == 'join_date') ? 'member since' : str_replace("_", " ", $startEvent);
      }
      if ($endEvent) {
        $membershipStatus[$dao->id]['end_event'] = ($endEvent == 'join_date') ? 'member since' : str_replace("_", " ", $endEvent);
      }
      if ($startEventUnit && $startEventInterval) {
        $membershipStatus[$dao->id]['start_event_adjust_unit_interval'] = "{$startEventInterval} {$startEventUnit}";
      }
      if ($endEventUnit && $endEventInterval) {
        $membershipStatus[$dao->id]['end_event_adjust_interval'] = "{$endEventInterval} {$endEventUnit}";
      }
    }
    // Add order changing widget to selector
    $returnURL = CRM_Utils_System::url('civicrm/admin/member/membershipStatus', "reset=1&action=browse");
    CRM_Utils_Weight::addOrder($membershipStatus, 'CRM_Member_DAO_MembershipStatus',
      'id', $returnURL
    );

    $this->assign('rows', $membershipStatus);
  }

  /**
   * Get name of edit form.
   *
   * @return string
   *   Classname of edit form.
   */
  public function editForm() {
    return 'CRM_Member_Form_MembershipStatus';
  }

  /**
   * Get edit form name.
   *
   * @return string
   *   name of this page.
   */
  public function editName() {
    return 'Membership Status';
  }

  /**
   * Get user context.
   *
   * @param null $mode
   *
   * @return string
   *   user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/member/membershipStatus';
  }

}
