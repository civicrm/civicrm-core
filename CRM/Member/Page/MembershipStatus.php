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
 * $Id$
 *
 */

/**
 * Page for displaying list of membership types
 */
class CRM_Member_Page_MembershipStatus extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * The action links that we need to display for the browse screen.
   *
   * @var array
   */
  public static $_links = NULL;

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
   * Get action Links.
   *
   * @return array
   *   (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/member/membershipStatus',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Membership Status'),
        ],
        CRM_Core_Action::DISABLE => [
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Membership Status'),
        ],
        CRM_Core_Action::ENABLE => [
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Membership Status'),
        ],
        CRM_Core_Action::DELETE => [
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/member/membershipStatus',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Membership Status'),
        ],
      ];
    }
    return self::$_links;
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
      if ($startEvent = CRM_Utils_Array::value('start_event', $membershipStatus[$dao->id])) {
        $membershipStatus[$dao->id]['start_event'] = ($startEvent == 'join_date') ? 'member since' : str_replace("_", " ", $startEvent);
      }
      if ($endEvent = CRM_Utils_Array::value('end_event', $membershipStatus[$dao->id])) {
        $membershipStatus[$dao->id]['end_event'] = ($endEvent == 'join_date') ? 'member since' : str_replace("_", " ", $endEvent);
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
