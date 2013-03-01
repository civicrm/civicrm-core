<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * form for thank-you / success page - 3rd step of online contribution process
 */
class CRM_Contribute_Form_Contribution_ThankYou extends CRM_Contribute_Form_ContributionBase {

  /**
   * membership price set status
   *
   */
  public $_useForMember;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  public function preProcess() {
    parent::preProcess();

    $this->_params   = $this->get('params');
    $this->_lineItem = $this->get('lineItem');
    $is_deductible   = $this->get('is_deductible');
    $this->assign('is_deductible', $is_deductible);
    $this->assign('thankyou_title', CRM_Utils_Array::value('thankyou_title', $this->_values));
    $this->assign('thankyou_text', CRM_Utils_Array::value('thankyou_text', $this->_values));
    $this->assign('thankyou_footer', CRM_Utils_Array::value('thankyou_footer', $this->_values));
    $this->assign('max_reminders', CRM_Utils_Array::value('max_reminders', $this->_values));
    $this->assign('initial_reminder_day', CRM_Utils_Array::value('initial_reminder_day', $this->_values));
    CRM_Utils_System::setTitle(CRM_Utils_Array::value('thankyou_title', $this->_values));
    // Make the contributionPageID avilable to the template
    $this->assign('contributionPageID', $this->_id);
    $this->assign('isShare', $this->_values['is_share']);

    $this->_params['is_pay_later'] = $this->get('is_pay_later');
    $this->assign('is_pay_later', $this->_params['is_pay_later']);
    if ($this->_params['is_pay_later']) {
      $this->assign('pay_later_receipt', $this->_values['pay_later_receipt']);
    }
  }

  /**
   * overwrite action, since we are only showing elements in frozen mode
   * no help display needed
   *
   * @return int
   * @access public
   */
  function getAction() {
    if ($this->_action & CRM_Core_Action::PREVIEW) {
      return CRM_Core_Action::VIEW | CRM_Core_Action::PREVIEW;
    }
    else {
      return CRM_Core_Action::VIEW;
    }
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $this->assignToTemplate();
    $productID        = $this->get('productID');
    $option           = $this->get('option');
    $membershipTypeID = $this->get('membershipTypeID');
    $this->assign('receiptFromEmail', CRM_Utils_Array::value('receipt_from_email', $this->_values));

    if ($productID) {
      CRM_Contribute_BAO_Premium::buildPremiumBlock($this, $this->_id, FALSE, $productID, $option);
    }
    if ($this->_priceSetId && !CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set', $this->_priceSetId, 'is_quick_config')) {
      $this->assign('lineItem', $this->_lineItem);
    } else {
      if (is_array($membershipTypeID)) {
        $membershipTypeID = current($membershipTypeID);
      }
      $this->assign('is_quick_config', 1);
      $this->_params['is_quick_config'] = 1;
    }
    $this->assign('priceSetID', $this->_priceSetId);
    $this->assign('useForMember', $this->get('useForMember'));

    $params = $this->_params;
    $honor_block_is_active = $this->get('honor_block_is_active');
    if ($honor_block_is_active &&
      ((!empty($params["honor_first_name"]) && !empty($params["honor_last_name"])) ||
        (!empty($params["honor_email"]))
      )
    ) {
      $this->assign('honor_block_is_active', $honor_block_is_active);
      $this->assign('honor_block_title', CRM_Utils_Array::value('honor_block_title', $this->_values));

      $prefix = CRM_Core_PseudoConstant::individualPrefix();
      $honor = CRM_Core_PseudoConstant::honor();
      $this->assign('honor_type', $honor[$params["honor_type_id"]]);
      $this->assign('honor_prefix', ($params["honor_prefix_id"]) ? $prefix[$params["honor_prefix_id"]] : ' ');
      $this->assign('honor_first_name', $params["honor_first_name"]);
      $this->assign('honor_last_name', $params["honor_last_name"]);
      $this->assign('honor_email', $params["honor_email"]);
    }

    $qParams = "reset=1&amp;id={$this->_id}";
    //pcp elements
    if ($this->_pcpId) {
      $qParams .= "&amp;pcpId={$this->_pcpId}";
      $this->assign('pcpBlock', TRUE);
      foreach (array(
        'pcp_display_in_roll', 'pcp_is_anonymous', 'pcp_roll_nickname', 'pcp_personal_note') as $val) {
        if (CRM_Utils_Array::value($val, $this->_params)) {
          $this->assign($val, $this->_params[$val]);
        }
      }
    }

    $this->assign( 'qParams' , $qParams );

    if ($membershipTypeID) {
      $transactionID    = $this->get('membership_trx_id');
      $membershipAmount = $this->get('membership_amount');
      $renewalMode      = $this->get('renewal_mode');
      $this->assign('membership_trx_id', $transactionID);
      $this->assign('membership_amount', $membershipAmount);
      $this->assign('renewal_mode', $renewalMode);

      CRM_Member_BAO_Membership::buildMembershipBlock($this,
        $this->_id,
        FALSE,
        $membershipTypeID,
        TRUE, NULL,
        $this->_membershipContactID
      );
    }

    $this->_separateMembershipPayment = $this->get('separateMembershipPayment');
    $this->assign("is_separate_payment", $this->_separateMembershipPayment);

    $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);
    if (CRM_Utils_Array::value('hidden_onbehalf_profile', $params)) {
      $ufJoinParams = array(
        'module' => 'onBehalf',
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $this->_id,
      );
      $OnBehalfProfile = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
      $profileId = $OnBehalfProfile[0];

      $fieldTypes     = array('Contact', 'Organization');
      $contactSubType = CRM_Contact_BAO_ContactType::subTypes('Organization');
      $fieldTypes     = array_merge($fieldTypes, $contactSubType);
      if (is_array($this->_membershipBlock) && !empty($this->_membershipBlock)) {
        $fieldTypes = array_merge($fieldTypes, array('Membership'));
      }
      else {
        $fieldTypes = array_merge($fieldTypes, array('Contribution'));
      }

      $this->buildCustom($profileId, 'onbehalfProfile', TRUE, TRUE, $fieldTypes);
    }

    $this->assign('trxn_id',
      CRM_Utils_Array::value('trxn_id',
        $this->_params
      )
    );
    $this->assign('receive_date',
      CRM_Utils_Date::mysqlToIso(CRM_Utils_Array::value('receive_date', $this->_params))
    );

    $defaults = array();
    $fields = array();
    foreach ($this->_fields as $name => $dontCare) {
      if ($name == 'onbehalf') {
        foreach ($dontCare as $key => $value) {
          $fields['onbehalf'][$key] = 1;
        }
      }
      else {
        $fields[$name] = 1;
      }
    }
    $fields['state_province'] = $fields['country'] = $fields['email'] = 1;
    $contact = $this->_params = $this->controller->exportValues('Main');

    foreach ($fields as $name => $dontCare) {
      if ($name == 'onbehalf') {
        foreach ($dontCare as $key => $value) {
          //$defaults[$key] = $contact['onbehalf'][$key];
          if (isset($contact['onbehalf'][$key])) {
          $defaults[$key] = $contact['onbehalf'][$key];
        }
          if (isset($contact['onbehalf']["{$key}_id"])) {
            $defaults["{$key}_id"] = $contact['onbehalf']["{$key}_id"];
      }

        }
      }
      elseif (isset($contact[$name])) {
        $defaults[$name] = $contact[$name];
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($contact[$timeField])) {
            $defaults[$timeField] = $contact[$timeField];
          }
        }
        elseif (in_array($name, array('addressee', 'email_greeting', 'postal_greeting'))
          && CRM_Utils_Array::value($name . '_custom', $contact)
        ) {
          $defaults[$name . '_custom'] = $contact[$name . '_custom'];
        }
      }
    }

    // now fix all state country selectors
    CRM_Core_BAO_Address::fixAllStateSelects($this, $defaults);

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
    elseif (CRM_Utils_Array::value('is_active', $data)) {
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

    $this->freeze();

    // can we blow away the session now to prevent hackery
    // CRM-9491
    $this->controller->reset();
  }
}

