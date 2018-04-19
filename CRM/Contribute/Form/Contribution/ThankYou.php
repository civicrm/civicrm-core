<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */

/**
 * Form for thank-you / success page - 3rd step of online contribution process.
 */
class CRM_Contribute_Form_Contribution_ThankYou extends CRM_Contribute_Form_ContributionBase {

  /**
   * Membership price set status.
   */
  public $_useForMember;

  /**
   * Tranxaaction Id of the current contribution
   */
  public $_trxnId;

  /**
   * Set variables up before form is built.
   */
  public function preProcess() {
    parent::preProcess();

    $this->_params = $this->get('params');
    $this->_lineItem = $this->get('lineItem');
    $this->_useForMember = $this->get('useForMember');
    $is_deductible = $this->get('is_deductible');
    $this->assign('is_deductible', $is_deductible);
    $this->assign('thankyou_title', CRM_Utils_Array::value('thankyou_title', $this->_values));
    $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->_values));
    $this->assign('thankyou_footer', CRM_Utils_Array::value('thankyou_footer', $this->_values));
    $this->assign('max_reminders', CRM_Utils_Array::value('max_reminders', $this->_values));
    $this->assign('initial_reminder_day', CRM_Utils_Array::value('initial_reminder_day', $this->_values));
    CRM_Utils_System::setTitle(CRM_Utils_Array::value('thankyou_title', $this->_values));
    // Make the contributionPageID available to the template
    $this->assign('contributionPageID', $this->_id);
    $this->assign('isShare', $this->_values['is_share']);

    $this->_params['is_pay_later'] = $this->get('is_pay_later');
    $this->assign('is_pay_later', $this->_params['is_pay_later']);
    if ($this->_params['is_pay_later']) {
      $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
    }
    $this->assign('is_for_organization', CRM_Utils_Array::value('is_for_organization', $this->_params));
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
   */
  public function buildQuickForm() {
    // FIXME: Some of this code is identical to Confirm.php and should be broken out into a shared function
    $this->assignToTemplate();
    $this->_ccid = $this->get('ccid');
    $productID = $this->get('productID');
    $option = $this->get('option');
    $membershipTypeID = $this->get('membershipTypeID');
    $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));

    if ($productID) {
      CRM_Contribute_BAO_Premium::buildPremiumBlock($this, $this->_id, FALSE, $productID, $option);
    }

    $params = $this->_params;
    $invoiceSettings = Civi::settings()->get('contribution_invoice_settings');
    $invoicing = CRM_Utils_Array::value('invoicing', $invoiceSettings);
    // Make a copy of line items array to use for display only
    $tplLineItems = $this->_lineItem;
    if ($invoicing) {
      $getTaxDetails = FALSE;
      $taxTerm = CRM_Utils_Array::value('tax_term', $invoiceSettings);
      foreach ($this->_lineItem as $key => $value) {
        foreach ($value as $k => $v) {
          if (isset($v['tax_rate'])) {
            if ($v['tax_rate'] != '') {
              $getTaxDetails = TRUE;
              // Cast to float to display without trailing zero decimals
              $tplLineItems[$key][$k]['tax_rate'] = (float) $v['tax_rate'];
            }
          }
        }
      }
      $this->assign('getTaxDetails', $getTaxDetails);
      $this->assign('taxTerm', $taxTerm);
      $this->assign('totalTaxAmount', $params['tax_amount']);
    }

    if ($this->_priceSetId && !CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $this->_priceSetId, 'is_quick_config')) {
      $this->assign('lineItem', $tplLineItems);
    }
    else {
      if (is_array($membershipTypeID)) {
        $membershipTypeID = current($membershipTypeID);
      }
      $this->assign('is_quick_config', 1);
      $this->_params['is_quick_config'] = 1;
    }
    $this->assign('priceSetID', $this->_priceSetId);
    $this->assign('useForMember', $this->get('useForMember'));

    if (!empty($this->_values['honoree_profile_id']) && !empty($params['soft_credit_type_id'])) {
      $softCreditTypes = CRM_Core_OptionGroup::values("soft_credit_type", FALSE);

      $this->assign('soft_credit_type', $softCreditTypes[$params['soft_credit_type_id']]);
      CRM_Contribute_BAO_ContributionSoft::formatHonoreeProfileFields($this, $params['honor']);

      $fieldTypes = array('Contact');
      $fieldTypes[] = CRM_Core_BAO_UFGroup::getContactType($this->_values['honoree_profile_id']);
      $this->buildCustom($this->_values['honoree_profile_id'], 'honoreeProfileFields', TRUE, 'honor', $fieldTypes);
    }

    $qParams = "reset=1&amp;id={$this->_id}";
    //pcp elements
    if ($this->_pcpId) {
      $qParams .= "&amp;pcpId={$this->_pcpId}";
      $this->assign('pcpBlock', TRUE);
      foreach (array(
                 'pcp_display_in_roll',
                 'pcp_is_anonymous',
                 'pcp_roll_nickname',
                 'pcp_personal_note',
               ) as $val) {
        if (!empty($this->_params[$val])) {
          $this->assign($val, $this->_params[$val]);
        }
      }
    }

    $this->assign('qParams', $qParams);

    if ($membershipTypeID) {
      $transactionID = $this->get('membership_trx_id');
      $membershipAmount = $this->get('membership_amount');
      $renewalMode = $this->get('renewal_mode');
      $this->assign('membership_trx_id', $transactionID);
      $this->assign('membership_amount', $membershipAmount);
      $this->assign('renewal_mode', $renewalMode);

      $this->buildMembershipBlock(
        $this->_membershipContactID,
        FALSE,
        $membershipTypeID,
        TRUE,
        NULL
      );

      if (!empty($params['auto_renew'])) {
        $this->assign('auto_renew', TRUE);
      }
    }

    $this->_separateMembershipPayment = $this->get('separateMembershipPayment');
    $this->assign("is_separate_payment", $this->_separateMembershipPayment);

    if (empty($this->_ccid)) {
      $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
      $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);
    }
    if (!empty($this->_values['onbehalf_profile_id']) &&
      !empty($params['onbehalf']) &&
      ($this->_values['is_for_organization'] == 2 ||
        !empty($params['is_for_organization'])
      ) && empty($this->_ccid)
    ) {
      $fieldTypes = array('Contact', 'Organization');
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes = array_merge($fieldTypes, $contactSubType);
      if (is_array($this->_membershipBlock) && !empty($this->_membershipBlock)) {
        $fieldTypes = array_merge($fieldTypes, array('Membership'));
      }
      else {
        $fieldTypes = array_merge($fieldTypes, array('Contribution'));
      }

      $this->buildCustom($this->_values['onbehalf_profile_id'], 'onbehalfProfile', TRUE, 'onbehalf', $fieldTypes);
    }

    $this->_trxnId = CRM_Utils_Array::value('trxn_id', $this->_params);

    $this->assign('trxn_id', $this->_trxnId);

    $this->assign('receive_date',
      CRM_Utils_Date::mysqlToIso(CRM_Utils_Array::value('receive_date', $this->_params))
    );

    $defaults = array();
    $fields = array();
    foreach ($this->_fields as $name => $dontCare) {
      if ($name != 'onbehalf' || $name != 'honor') {
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
        elseif (in_array($name, array(
              'addressee',
              'email_greeting',
              'postal_greeting',
            )) && !empty($contact[$name . '_custom'])
        ) {
          $defaults[$name . '_custom'] = $contact[$name . '_custom'];
        }
      }
    }

    $this->_submitValues = array_merge($this->_submitValues, $defaults);

    $this->setDefaults($defaults);

    $values['entity_id'] = $this->_id;
    $values['entity_table'] = 'civicrm_contribution_page';

    CRM_Friend_BAO_Friend::retrieve($values, $data);
    $tellAFriend = FALSE;
    if ($this->_pcpId) {
      if ($this->_pcpBlock['is_tellfriend_enabled']) {
        $this->assign('friendText', ts('Tell a Friend'));
        $subUrl = "eid={$this->_pcpId}&blockId={$this->_pcpBlock['id']}&pcomponent=pcp";
        $tellAFriend = TRUE;
      }
    }
    elseif (!empty($data['is_active'])) {
      $friendText = $data['title'];
      $this->assign('friendText', $friendText);
      $subUrl = "eid={$this->_id}&pcomponent=contribute";
      $tellAFriend = TRUE;
    }

    if ($tellAFriend) {
      if ($this->_action & CRM_Core_Action::PREVIEW) {
        $url = CRM_Utils_System::url("civicrm/friend",
          "reset=1&action=preview&{$subUrl}"
        );
      }
      else {
        $url = CRM_Utils_System::url("civicrm/friend",
          "reset=1&{$subUrl}"
        );
      }
      $this->assign('friendURL', $url);
    }

    $isPendingOutcome = TRUE;
    try {
      // A payment notification update could have come in at any time. Check at the last minute.
      $contributionStatusID = civicrm_api3('Contribution', 'getvalue', array(
        'id' => CRM_Utils_Array::value('contributionID', $params),
        'return' => 'contribution_status_id',
        'invoice_id' => CRM_Utils_Array::value('invoiceID', $params),
      ));
      if (CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionStatusID) === 'Pending'
        && !empty($params['payment_processor_id'])
      ) {
        $isPendingOutcome = TRUE;
      }
      else {
        $isPendingOutcome = FALSE;
      }
    }
    catch (CiviCRM_API3_Exception $e) {

    }
    $this->assign('isPendingOutcome', $isPendingOutcome);

    $this->freeze();

    // can we blow away the session now to prevent hackery
    // CRM-9491
    $this->controller->reset();
  }

}
