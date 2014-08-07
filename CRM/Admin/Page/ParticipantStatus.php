<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */
class CRM_Admin_Page_ParticipantStatus extends CRM_Core_Page_Basic {

  public $useLivePageJS = TRUE;

  /**
   * @return string
   */
  function getBAOName() {
    return 'CRM_Event_BAO_ParticipantStatusType';
  }

  /**
   * @return array
   */
  function &links() {
    static $links = NULL;
    if ($links === NULL) {
      $links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/participant_status',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Status'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/participant_status',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Status'),
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Disable Status'),
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'ref' => 'crm-enable-disable',
          'title' => ts('Enable Status'),
        ),
      );
    }
    return $links;
  }

  function browse() {
    $statusTypes = array();

    $dao = new CRM_Event_DAO_ParticipantStatusType;
    $dao->orderBy('weight');
    $dao->find();

    $visibilities = CRM_Core_PseudoConstant::visibility();

    // these statuses are reserved, but disabled by default - so should be disablable after being enabled
    $disablable = array('On waitlist', 'Awaiting approval', 'Pending from waitlist', 'Pending from approval', 'Rejected');

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
        array('id' => $dao->id),
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
  function editForm() {
    return 'CRM_Admin_Form_ParticipantStatus';
  }

  /**
   * @return string
   */
  function editName() {
    return 'Participant Status';
  }

  /**
   * @param null $mode
   *
   * @return string
   */
  function userContext($mode = NULL) {
    return 'civicrm/admin/participant_status';
  }
}

