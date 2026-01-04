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

use Civi\Api4\Membership;

/**
 * Form for thank-you / success page - 3rd step of online contribution process.
 */
class CRM_Contribute_Form_Contribution_ThankYou extends CRM_Contribute_Form_ContributionBase {

  /**
   * Membership price set status.
   * @var bool
   */
  public $_useForMember;

  /**
   * Tranxaaction Id of the current contribution
   * @var string
   */
  public $_trxnId;

  /**
   * Set variables up before form is built.
   *
   * @throws \CRM_Contribute_Exception_InactiveContributionPageException
   */
  public function preProcess(): void {
    parent::preProcess();

    $this->_params = $this->get('params');
    $this->_useForMember = $this->get('useForMember');
    $this->assign('thankyou_title', $this->_values['thankyou_title'] ?? NULL);
    $this->assign('thankyou_text', $this->_values['thankyou_text'] ?? NULL);
    $this->assign('thankyou_footer', $this->_values['thankyou_footer'] ?? NULL);
    $this->assign('max_reminders', $this->_values['max_reminders'] ?? NULL);
    $this->assign('initial_reminder_day', $this->_values['initial_reminder_day'] ?? NULL);
    $this->assignTotalAmounts();
    // Link (button) for users to create their own Personal Campaign page
    if ($linkText = CRM_PCP_BAO_PCP::getPcpBlockStatus($this->getContributionPageID(), 'contribute')) {
      $linkTextUrl = CRM_Utils_System::url('civicrm/contribute/campaign',
        'action=add&reset=1&pageId=' . $this->getContributionPageID() . '&component=contribute',
        FALSE, NULL, TRUE
      );
    }
    $this->assign('linkTextUrl', $linkTextUrl ?? NULL);
    $this->assign('linkText', $linkText);
    $this->setTitle($this->_values['thankyou_title'] ?? NULL);
    // Make the contributionPageID available to the template
    $this->assign('contributionPageID', $this->_id);
    $this->assign('isShare', $this->_values['is_share']);

    $this->_params['is_pay_later'] = $this->get('is_pay_later');
    $this->assign('is_pay_later', $this->_params['is_pay_later']);
    if ($this->_params['is_pay_later']) {
      $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
    }
    $this->assign('is_for_organization', $this->_params['is_for_organization'] ?? NULL);
  }

  /**
   * Overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   */
  public function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    // FIXME: Some of this code is identical to Confirm.php and should be broken out into a shared function
    $this->assignToTemplate();
    $this->_ccid = $this->getExistingContributionID();
    $option = $this->get('option');
    $membershipTypeID = $this->get('membershipTypeID');
    $this->assign('receiptFromEmail', $this->_values['receipt_from_email'] ?? NULL);

    if ($this->getProductID()) {
      $this->buildPremiumsBlock(FALSE, $option);
    }
    else {
      $this->assign('products');
    }

    $params = $this->_params;
    $this->assign('getTaxDetails', (bool) $this->order->getTotalTaxAmount());
    $this->assign('totalTaxAmount', $this->order->getTotalTaxAmount());
    $this->assign('taxTerm', \Civi::settings()->get('tax_term'));
    $this->assign('lineItem', $this->isQuickConfig() ? NULL : [$this->getPriceSetID() => $this->order->getLineItems()]);
    if (!$this->isQuickConfig()) {
      if (is_array($membershipTypeID)) {
        $membershipTypeID = current($membershipTypeID);
      }
      $this->_params['is_quick_config'] = 1;
    }
    $this->assign('is_quick_config', $this->isQuickConfig());
    $this->assign('priceSetID', $this->getPriceSetID());
    $this->assign('useForMember', $this->get('useForMember'));

    if (!empty($this->_values['honoree_profile_id']) && !empty($params['soft_credit_type_id'])) {
      $softCreditTypes = CRM_Core_OptionGroup::values('soft_credit_type', FALSE);

      $this->assign('soft_credit_type', $softCreditTypes[$params['soft_credit_type_id']]);
      CRM_Contribute_BAO_ContributionSoft::formatHonoreeProfileFields($this, $params['honor']);

      $fieldTypes = ['Contact'];
      $fieldTypes[] = CRM_Core_BAO_UFGroup::getContactType($this->_values['honoree_profile_id']);
      $this->buildCustom($this->_values['honoree_profile_id'], 'honoreeProfileFields', TRUE, 'honor', $fieldTypes);
    }
    else {
      $this->assign('honoreeProfileFields');
    }

    $qParams = "reset=1&amp;id={$this->_id}";
    //pcp elements
    $this->assign('pcpBlock', FALSE);
    if ($this->_pcpId) {
      $qParams .= "&amp;pcpId={$this->_pcpId}";

      // display honor roll data only if it's enabled for the PCP page
      if (!empty($this->_pcpInfo['is_honor_roll'])) {
        $this->assign('pcpBlock', TRUE);
        foreach ([
          'pcp_display_in_roll',
          'pcp_is_anonymous',
          'pcp_roll_nickname',
          'pcp_personal_note',
        ] as $val) {
          if (!empty($this->_params[$val])) {
            $this->assign($val, $this->_params[$val]);
          }
        }
      }
    }

    $this->assign('qParams', $qParams);
    $this->assign('membershipBlock', $this->_membershipBlock);
    $this->assign('membership_trx_id', $this->get('membership_trx_id'));
    if ($membershipTypeID) {
      $membershipAmount = $this->get('membership_amount');
      $renewalMode = $this->get('renewal_mode');
      $this->assign('membership_amount', $membershipAmount);
      $this->assign('renewal_mode', $renewalMode);

      $this->buildMembershipBlock(
        $this->_membershipContactID,
        $membershipTypeID
      );

      if (!empty($params['auto_renew'])) {
        $this->assign('auto_renew', TRUE);
      }
    }

    $this->_separateMembershipPayment = $this->get('separateMembershipPayment');
    $this->assign('is_separate_payment', $this->_separateMembershipPayment);

    if (empty($this->getExistingContributionID())) {
      $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
      $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);
    }
    if (!empty($this->_values['onbehalf_profile_id']) &&
      !empty($params['onbehalf']) &&
      ($this->_values['is_for_organization'] == 2 ||
        !empty($params['is_for_organization'])
      ) && empty($this->getExistingContributionID())
    ) {
      $fieldTypes = ['Contact', 'Organization'];
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes = array_merge($fieldTypes, $contactSubType);
      if (is_array($this->_membershipBlock) && !empty($this->_membershipBlock)) {
        $fieldTypes = array_merge($fieldTypes, ['Membership']);
      }
      else {
        $fieldTypes = array_merge($fieldTypes, ['Contribution']);
      }

      $this->buildCustom($this->_values['onbehalf_profile_id'], 'onbehalfProfile', TRUE, 'onbehalf', $fieldTypes);
    }
    else {
      $this->assign('onbehalfProfile');
    }

    $this->_trxnId = $this->_params['trxn_id'] ?? NULL;

    $this->assign('trxn_id', $this->_trxnId);

    $this->assign('receive_date',
      CRM_Utils_Date::mysqlToIso($this->_params['receive_date'] ?? NULL)
    );

    $defaults = [];
    $fields = [];
    foreach ($this->_fields as $name => $dontCare) {
      if ($name !== 'onbehalf' || $name !== 'honor') {
        $fields[$name] = 1;
      }
    }
    $fields['state_province'] = $fields['country'] = $fields['email'] = 1;
    $contact = $this->_params = $this->controller->exportValues('Main');

    foreach ($fields as $name => $dontCare) {
      if (isset($contact[$name])) {
        $defaults[$name] = $contact[$name];
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($contact[$timeField])) {
            $defaults[$timeField] = $contact[$timeField];
          }
        }
        elseif (in_array($name, [
          'addressee',
          'email_greeting',
          'postal_greeting',
        ]) && !empty($contact[$name . '_custom'])
        ) {
          $defaults[$name . '_custom'] = $contact[$name . '_custom'];
        }
      }
    }

    $this->_submitValues = array_merge($this->_submitValues, $defaults);
    $this->setDefaults($defaults);

    $values['entity_id'] = $this->_id;
    $values['entity_table'] = 'civicrm_contribution_page';

    $tellAFriend = FALSE;
    $friendURL = NULL;
    $friendText = NULL;

    if (function_exists('tellafriend_civicrm_config')) {
      CRM_Friend_BAO_Friend::retrieve($values, $data);
      if ($this->_pcpId) {
        if ($this->_pcpBlock['is_tellfriend_enabled']) {
          $friendText = ts('Tell a Friend');
          $subUrl = "eid={$this->_pcpId}&blockId={$this->_pcpBlock['id']}&pcomponent=pcp";
          $tellAFriend = TRUE;
        }
      }
      elseif (!empty($data['is_active'])) {
        $friendText = $data['title'];
        $subUrl = "eid={$this->_id}&pcomponent=contribute";
        $tellAFriend = TRUE;
      }
    }

    if ($tellAFriend) {
      if ($this->_action & CRM_Core_Action::PREVIEW) {
        $friendURL = CRM_Utils_System::url('civicrm/friend',
          "reset=1&action=preview&{$subUrl}"
        );
      }
      else {
        $friendURL = CRM_Utils_System::url('civicrm/friend',
          "reset=1&{$subUrl}"
        );
      }
    }

    $this->assign('friendText', $friendText);
    $this->assign('friendURL', $friendURL);
    $this->assign('isPendingOutcome', $this->isPendingOutcome($params));
    $this->assign('paymentProcessorName', $this->getPaymentProcessorValue('frontend_title'));
    $this->freeze();

    // can we blow away the session now to prevent hackery
    // CRM-9491
    $this->controller->reset();
  }

  /**
   * Build Membership  Block in Contribution Pages.
   * @todo this was shared on CRM_Contribute_Form_ContributionBase but we are refactoring and simplifying for each
   *   step (main/confirm/thankyou)
   *
   * @param int $cid
   *   Contact checked for having a current membership for a particular membership.
   * @param int|array $selectedMembershipTypeID
   *   Selected membership id.
   *
   * @return bool
   *   Is this a separate membership payment
   *
   * @throws \CRM_Core_Exception
   */
  private function buildMembershipBlock($cid, $selectedMembershipTypeID = NULL) {
    $separateMembershipPayment = FALSE;
    if ($this->_membershipBlock) {

      $membershipTypeIds = $membershipTypes = [];
      $membershipPriceset = (!empty($this->_priceSetId) && $this->_useForMember);

      $autoRenewMembershipTypeOptions = [];

      $separateMembershipPayment = $this->_membershipBlock['is_separate_payment'] ?? NULL;

      if ($membershipPriceset) {
        foreach ($this->_priceSet['fields'] as $pField) {
          if (empty($pField['options'])) {
            continue;
          }
          foreach ($pField['options'] as $opId => $opValues) {
            if (empty($opValues['membership_type_id'])) {
              continue;
            }
            $membershipTypeIds[$opValues['membership_type_id']] = $opValues['membership_type_id'];
          }
        }
      }
      elseif (!empty($this->_membershipBlock['membership_types'])) {
        $membershipTypeIds = explode(',', $this->_membershipBlock['membership_types']);
      }

      if (!empty($membershipTypeIds)) {
        $membershipTypeValues = CRM_Member_BAO_Membership::buildMembershipTypeValues($this, $membershipTypeIds);
        $endDate = NULL;

        // Check if we support auto-renew on this contribution page
        // FIXME: If any of the payment processors do NOT support recurring you cannot setup an
        //   auto-renew payment even if that processor is not selected.
        $allowAutoRenewOpt = TRUE;
        if (is_array($this->_paymentProcessors)) {
          foreach ($this->_paymentProcessors as $id => $val) {
            if ($id && !$val['is_recur']) {
              $allowAutoRenewOpt = FALSE;
            }
          }
        }
        foreach ($membershipTypeIds as $value) {
          $memType = $membershipTypeValues[$value];
          if ($selectedMembershipTypeID != NULL) {
            if ($memType['id'] == $selectedMembershipTypeID) {
              $this->assign('minimum_fee', $memType['minimum_fee'] ?? NULL);
              $membershipTypes[] = $memType;
            }
          }
          elseif ($memType['is_active']) {

            if ($allowAutoRenewOpt) {
              $isAvailableAutoRenew = $this->_membershipBlock['auto_renew'][$value] ?? 1;
              $autoRenewMembershipTypeOptions["autoRenewMembershipType_{$value}"] = (int) $memType['auto_renew'] * $isAvailableAutoRenew;
            }
            else {
              $autoRenewMembershipTypeOptions["autoRenewMembershipType_{$value}"] = 0;
            }

            //add membership type.
            if ($cid) {
              //show current membership, skip pending and cancelled membership records,
              //because we take first membership record id for renewal
              $membership = Membership::get(FALSE)
                ->addSelect('end_date', 'membership_type_id', 'membership_type_id.duration_unit:name')
                ->addWhere('contact_id', '=', $cid)
                ->addWhere('membership_type_id', '=', $memType['id'])
                ->addWhere('status_id:name', 'NOT IN', ['Cancelled', 'Pending'])
                // @todo - this FALSE is dubious but respects the previous code behaviour.
                // This code is from a previously shared function & likely not relevant on the
                // thank you form anyway....
                ->addWhere('is_test', '=', FALSE)
                ->addOrderBy('end_date', 'DESC')
                ->execute()
                ->first();

              if ($membership && $membership['membership_type_id.duration_unit:name'] !== 'lifetime') {
                $this->assign('renewal_mode', TRUE);
                $memType['current_membership'] = $membership['end_date'];
                if (!$endDate) {
                  $endDate = $memType['current_membership'];
                }
                if ($memType['current_membership'] < $endDate) {
                  $endDate = $memType['current_membership'];
                }
              }
            }
            $membershipTypes[] = $memType;
          }
        }
      }

      $this->assign('showRadio', FALSE);
      $this->assign('membershipTypes', $membershipTypes);
      $this->assign('autoRenewMembershipTypeOptions', json_encode($autoRenewMembershipTypeOptions));
      //give preference to user submitted auto_renew value.
      $takeUserSubmittedAutoRenew = (!empty($_POST) || $this->isSubmitted());
      $this->assign('takeUserSubmittedAutoRenew', $takeUserSubmittedAutoRenew);

      // Assign autorenew option (0:hide,1:optional,2:required) so we can use it in confirmation etc.
      $autoRenewOption = CRM_Price_BAO_PriceSet::checkAutoRenewForPriceSet($this->_priceSetId);
      //$selectedMembershipTypeID is retrieved as an array for membership priceset if multiple
      //options for different organisation is selected on the contribution page.
      if (is_numeric($selectedMembershipTypeID) && isset($membershipTypeValues[$selectedMembershipTypeID]['auto_renew'])) {
        $this->assign('autoRenewOption', $membershipTypeValues[$selectedMembershipTypeID]['auto_renew']);
      }
      else {
        $this->assign('autoRenewOption', $autoRenewOption);
      }
    }

    return $separateMembershipPayment;
  }

  /**
   * Is the outcome of this contribution still pending.
   *
   * @param array $params
   *
   * @return bool
   */
  protected function isPendingOutcome(array $params): bool {
    if (empty($params['payment_processor_id'])) {
      return FALSE;
    }
    try {
      // A payment notification update could have come in at any time. Check at the last minute.
      civicrm_api3('Contribution', 'getvalue', [
        'id' => $params['contributionID'] ?? NULL,
        'contribution_status_id' => 'Pending',
        'is_test' => '',
        'return' => 'id',
        'invoice_id' => $params['invoiceID'] ?? NULL,
      ]);
      return TRUE;
    }
    catch (CRM_Core_Exception $e) {
      return FALSE;
    }
  }

}
