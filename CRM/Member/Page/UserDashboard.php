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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * This class is for building membership block on user dashboard
 */
class CRM_Member_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * List memberships for the UF user.
   *
   */
  public function listMemberships() {
    $membership = array();
    $dao = new CRM_Member_DAO_Membership();
    $dao->contact_id = $this->_contactId;
    $dao->is_test = 0;
    $dao->find();

    while ($dao->fetch()) {
      $membership[$dao->id] = array();
      CRM_Core_DAO::storeValues($dao, $membership[$dao->id]);

      //get the membership status and type values.
      $statusANDType = CRM_Member_BAO_Membership::getStatusANDTypeValues($dao->id);
      foreach (array(
        'status',
        'membership_type',
      ) as $fld) {
        $membership[$dao->id][$fld] = CRM_Utils_Array::value($fld, $statusANDType[$dao->id]);
      }
      if (!empty($statusANDType[$dao->id]['is_current_member'])) {
        $membership[$dao->id]['active'] = TRUE;
      }

      $membership[$dao->id]['renewPageId'] = CRM_Member_BAO_Membership::getContributionPageId($dao->id);
      if (!$membership[$dao->id]['renewPageId']) {
        // Membership payment was not done via online contribution page or free membership. Check for default membership renewal page from CiviMember Settings
        $defaultRenewPageId = Civi::settings()->get('default_renewal_contribution_page');
        if ($defaultRenewPageId) {
          //CRM-14831 - check if membership type is present in contrib page
          $memBlock = CRM_Member_BAO_Membership::getMembershipBlock($defaultRenewPageId);
          if (!empty($memBlock['membership_types'])) {
            $memTypes = explode(',', $memBlock['membership_types']);
            if (in_array($dao->membership_type_id, $memTypes)) {
              $membership[$dao->id]['renewPageId'] = $defaultRenewPageId;
            }
          }
        }
      }
    }

    $activeMembers = CRM_Member_BAO_Membership::activeMembers($membership);
    $inActiveMembers = CRM_Member_BAO_Membership::activeMembers($membership, 'inactive');

    $this->assign('activeMembers', $activeMembers);
    $this->assign('inActiveMembers', $inActiveMembers);
  }

  /**
   * the main function that is called when the page
   * loads, it decides the which action has to be taken for the page.
   *
   */
  public function run() {
    parent::preProcess();
    $this->listMemberships();
  }

}
