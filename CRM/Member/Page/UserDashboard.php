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
 * This class is for building membership block on user dashboard
 */
class CRM_Member_Page_UserDashboard extends CRM_Contact_Page_View_UserDashBoard {

  /**
   * List memberships for the UF user.
   *
   */
  public function listMemberships() {
    $membership = [];
    $dao = new CRM_Member_DAO_Membership();
    $dao->contact_id = $this->_contactId;
    $dao->is_test = 0;
    $dao->orderBy('end_date DESC');
    $dao->find();

    while ($dao->fetch()) {
      $membership[$dao->id] = [];
      CRM_Core_DAO::storeValues($dao, $membership[$dao->id]);

      //get the membership status and type values.
      $statusANDType = CRM_Member_BAO_Membership::getStatusANDTypeValues($dao->id);
      foreach (['status', 'membership_type'] as $fld) {
        $membership[$dao->id][$fld] = $statusANDType[$dao->id][$fld] ?? NULL;
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

    // Add Recurring Links (if allowed)
    $this->buildMemberLinks($activeMembers);
    $this->buildMemberLinks($inActiveMembers);

    $this->assign('activeMembers', $activeMembers);
    $this->assign('inActiveMembers', $inActiveMembers);

  }

  /**
   * Helper function to build appropriate Member links
   */
  public function buildMemberLinks(&$members) {
    if (!empty($members)) {
      foreach ($members as $id => &$member) {
        if (empty($member['contribution_recur_id'])) {
          continue;
        }

        $paymentProcessor = NULL;
        try {
          $contributionRecur = \Civi\Api4\ContributionRecur::get(FALSE)
            ->addSelect('payment_processor_id')
            ->addWhere('id', '=', $member['contribution_recur_id'])
            ->execute()
            ->first();

          if (!empty($contributionRecur['payment_processor_id'])) {
            $paymentProcessor = \Civi\Api4\PaymentProcessor::get(FALSE)
              ->addWhere('id', '=', $contributionRecur['payment_processor_id'])
              ->execute()
              ->first();
          }
        }
        catch (Exception $e) {
          Civi::log()->warning('Member/UserDashboard: Unable to retrieve recur information ' . $e->getMessage());
          continue;
        }

        if (!empty($paymentProcessor)) {
          $paymentObject = Civi\Payment\System::singleton()->getByProcessor($paymentProcessor);
          $member['cancelSubscriptionUrl'] = $paymentObject->subscriptionURL($member['membership_id'], 'membership', 'cancel');
          $member['updateSubscriptionBillingUrl'] = $paymentObject->subscriptionURL($member['membership_id'], 'membership', 'billing');
          $member['updateSubscriptionUrl'] = $paymentObject->subscriptionURL($member['membership_id'], 'membership', 'update');
        }
      }
    }

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
