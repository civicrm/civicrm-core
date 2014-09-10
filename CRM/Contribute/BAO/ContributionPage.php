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

/**
 * This class contains Contribution Page related functions.
 */
class CRM_Contribute_BAO_ContributionPage extends CRM_Contribute_DAO_ContributionPage {

  /**
   * takes an associative array and creates a contribution page object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Contribute_DAO_ContributionPage object
   * @access public
   * @static
   */
  public static function &create(&$params) {
    $financialTypeId = NULL;
    if (!empty($params['id']) && !CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $params['id'], NULL, 1)) {
      $financialTypeId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $params['id'], 'financial_type_id');
    }
    $dao = new CRM_Contribute_DAO_ContributionPage();
    $dao->copyValues($params);
    $dao->save();
    if ($financialTypeId && !empty($params['financial_type_id']) && $financialTypeId != $params['financial_type_id']) {
      CRM_Price_BAO_PriceFieldValue::updateFinancialType($params['id'], 'civicrm_contribution_page', $params['financial_type_id']);
    }
    return $dao;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on success, null otherwise
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionPage', $id, 'is_active', $is_active);
  }

  /**
   * @param $id
   * @param $values
   */
  static function setValues($id, &$values) {
    $params = array(
      'id' => $id,
    );

    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $values);

    // get the profile ids
    $ufJoinParams = array(
      'module' => 'CiviContribute',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $id,
    );
    list($values['custom_pre_id'], $customPostIds) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

    if (!empty($customPostIds)) {
      $values['custom_post_id'] = $customPostIds[0];
    }
    else {
      $values['custom_post_id'] = '';
    }
    // // add an accounting code also
    // if ($values ['financial_type_id']) {
    //   $values ['accountingCode'] = CRM_Core_DAO::getFieldValue( 'CRM_Financial_DAO_FinancialType', $values ['financial_type_id'], 'accounting_code' );
    // }
    }

  /**
   * Function to send the emails
   *
   * @param int $contactID contact id
   * @param array $values associated array of fields
   * @param boolean $isTest if in test mode
   * @param boolean $returnMessageText return the message text instead of sending the mail
   *
   * @param null $fieldTypes
   *
   * @return void
   * @access public
   * @static
   */
  static function sendMail($contactID, $values, $isTest = FALSE, $returnMessageText = FALSE, $fieldTypes = NULL) {
    $gIds = $params = array();
    $email = NULL;
    if (isset($values['custom_pre_id'])) {
      $preProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_pre_id']);
      if ($preProfileType == 'Membership' && !empty($values['membership_id'])) {
        $params['custom_pre_id'] = array(
          array(
            'member_id',
            '=',
            $values['membership_id'],
            0,
            0,
          ),
        );
      }
      elseif ($preProfileType == 'Contribution' && !empty($values['contribution_id'])) {
        $params['custom_pre_id'] = array(
          array(
            'contribution_id',
            '=',
            $values['contribution_id'],
            0,
            0,
          ),
        );
      }

      $gIds['custom_pre_id'] = $values['custom_pre_id'];
    }

    if (isset($values['custom_post_id'])) {
      $postProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_post_id']);
      if ($postProfileType == 'Membership' && !empty($values['membership_id'])) {
        $params['custom_post_id'] = array(
          array(
            'member_id',
            '=',
            $values['membership_id'],
            0,
            0,
          ),
        );
      }
      elseif ($postProfileType == 'Contribution' && !empty($values['contribution_id'])) {
        $params['custom_post_id'] = array(
          array(
            'contribution_id',
            '=',
            $values['contribution_id'],
            0,
            0,
          ),
        );
      }

      $gIds['custom_post_id'] = $values['custom_post_id'];
    }

    if (!empty($values['is_for_organization'])) {
      if (!empty($values['membership_id'])) {
        $params['onbehalf_profile'] = array(
          array(
            'member_id',
            '=',
            $values['membership_id'],
            0,
            0,
          ),
        );
      }
      elseif (!empty($values['contribution_id'])) {
        $params['onbehalf_profile'] = array(
          array(
            'contribution_id',
            '=',
            $values['contribution_id'],
            0,
            0,
          ),
        );
      }
    }

    //check whether it is a test drive
    if ($isTest && !empty($params['custom_pre_id'])) {
      $params['custom_pre_id'][] = array(
        'contribution_test',
        '=',
        1,
        0,
        0,
      );
    }

    if ($isTest && !empty($params['custom_post_id'])) {
      $params['custom_post_id'][] = array(
        'contribution_test',
        '=',
        1,
        0,
        0,
      );
    }

    if (!$returnMessageText && !empty($gIds)) {
      //send notification email if field values are set (CRM-1941)
      foreach ($gIds as $key => $gId) {
        if ($gId) {
          $email = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gId, 'notify');
          if ($email) {
            $val = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($gId, $contactID, CRM_Utils_Array::value($key, $params), true );
            CRM_Core_BAO_UFGroup::commonSendMail($contactID, $val);
          }
        }
      }
    }

    if (!empty($values['is_email_receipt']) || !empty($values['onbehalf_dupe_alert']) ||
      $returnMessageText
    ) {
      $template = CRM_Core_Smarty::singleton();

      // get the billing location type
      if (!array_key_exists('related_contact', $values)) {
        $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id', array(), 'validate');
        $billingLocationTypeId = array_search('Billing', $locationTypes);
      }
      else {
        // presence of related contact implies onbehalf of org case,
        // where location type is set to default.
        $locType = CRM_Core_BAO_LocationType::getDefault();
        $billingLocationTypeId = $locType->id;
      }

      if (!array_key_exists('related_contact', $values)) {
        list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID, FALSE, $billingLocationTypeId);
      }
      // get primary location email if no email exist( for billing location).
      if (!$email) {
        list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      }
      if (empty($displayName)) {
        list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      }

      //for display profile need to get individual contact id,
      //hence get it from related_contact if on behalf of org true CRM-3767
      //CRM-5001 Contribution/Membership:: On Behalf of Organization,
      //If profile GROUP contain the Individual type then consider the
      //profile is of Individual ( including the custom data of membership/contribution )
      //IF Individual type not present in profile then it is consider as Organization data.
      $userID = $contactID;
      if ($preID = CRM_Utils_Array::value('custom_pre_id', $values)) {
        if (!empty($values['related_contact'])) {
          $preProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($preID);
          //@todo - following line should not refer to undefined $postProfileTypes? figure out way to test
          if (in_array('Individual', $preProfileTypes) || in_array('Contact', $postProfileTypes)) {
            //Take Individual contact ID
            $userID = CRM_Utils_Array::value('related_contact', $values);
          }
        }
        self::buildCustomDisplay($preID, 'customPre', $userID, $template, $params['custom_pre_id']);
      }
      $userID = $contactID;
      if ($postID = CRM_Utils_Array::value('custom_post_id', $values)) {
        if (!empty($values['related_contact'])) {
          $postProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($postID);
          if (in_array('Individual', $postProfileTypes) || in_array('Contact', $postProfileTypes)) {
            //Take Individual contact ID
            $userID = CRM_Utils_Array::value('related_contact', $values);
          }
        }
        self::buildCustomDisplay($postID, 'customPost', $userID, $template, $params['custom_post_id']);
      }
      if (isset($values['honor'])) {
        $honorValues = $values['honor'];
        CRM_Contribute_BAO_ContributionSoft::formatHonoreeProfileFields(
          $template,
          $honorValues['honor_profile_values'],
          $honorValues['honor_profile_id'],
          $honorValues['honor_id']
        );
      }

      $title = isset($values['title']) ? $values['title'] : CRM_Contribute_PseudoConstant::contributionPage($values['contribution_page_id']);

      // set email in the template here
      $tplParams = array(
        'email' => $email,
        'receiptFromEmail' => CRM_Utils_Array::value('receipt_from_email', $values),
        'contactID' => $contactID,
        'displayName' => $displayName,
        'contributionID' => CRM_Utils_Array::value('contribution_id', $values),
        'contributionOtherID' => CRM_Utils_Array::value('contribution_other_id', $values),
        // CRM-5095
        'lineItem' => CRM_Utils_Array::value('lineItem', $values),
        // CRM-5095
        'priceSetID' => CRM_Utils_Array::value('priceSetID', $values),
        'title' => $title,
        'isShare' => CRM_Utils_Array::value('is_share', $values),
      );

      if ($contributionTypeId = CRM_Utils_Array::value('financial_type_id', $values)) {
        $tplParams['contributionTypeId'] = $contributionTypeId;
        $tplParams['contributionTypeName'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
          $contributionTypeId);
      }

      if ($contributionPageId = CRM_Utils_Array::value('id', $values)) {
        $tplParams['contributionPageId'] = $contributionPageId;
      }

      // address required during receipt processing (pdf and email receipt)
      if ($displayAddress = CRM_Utils_Array::value('address', $values)) {
        $tplParams['address'] = $displayAddress;
      }

      // CRM-6976
      $originalCCReceipt = CRM_Utils_Array::value('cc_receipt', $values);

      // cc to related contacts of contributor OR the one who
      // signs up. Is used for cases like - on behalf of
      // contribution / signup ..etc
      if (array_key_exists('related_contact', $values)) {
        list($ccDisplayName, $ccEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($values['related_contact']);
        $ccMailId = "{$ccDisplayName} <{$ccEmail}>";

        //@todo - this is the only place in this function where  $values is altered - but I can't find any evidence it is used
        $values['cc_receipt'] = !empty($values['cc_receipt']) ? ($values['cc_receipt'] . ',' . $ccMailId) : $ccMailId;

        // reset primary-email in the template
        $tplParams['email'] = $ccEmail;

        $tplParams['onBehalfName'] = $displayName;
        $tplParams['onBehalfEmail'] = $email;

        $ufJoinParams = array(
          'module' => 'onBehalf',
          'entity_table' => 'civicrm_contribution_page',
          'entity_id' => $values['id'],
        );
        $OnBehalfProfile = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);
        $profileId = $OnBehalfProfile[0];
        $userID = $contactID;
        self::buildCustomDisplay($profileId, 'onBehalfProfile', $userID, $template, $params['onbehalf_profile'], $fieldTypes);
      }

      // use either the contribution or membership receipt, based on whether it’s a membership-related contrib or not
      $sendTemplateParams = array(
        'groupName' => !empty($values['isMembership']) ? 'msg_tpl_workflow_membership' : 'msg_tpl_workflow_contribution',
        'valueName' => !empty($values['isMembership']) ? 'membership_online_receipt' : 'contribution_online_receipt',
        'contactId' => $contactID,
        'tplParams' => $tplParams,
        'isTest' => $isTest,
        'PDFFilename' => 'receipt.pdf',
      );

      if ($returnMessageText) {
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        return array(
          'subject' => $subject,
          'body' => $message,
          'to' => $displayName,
          'html' => $html,
        );
      }

      if ($values['is_email_receipt']) {
        $sendTemplateParams['from'] = CRM_Utils_Array::value('receipt_from_name', $values) . ' <' . $values['receipt_from_email'] . '>';
        $sendTemplateParams['toName'] = $displayName;
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = CRM_Utils_Array::value('cc_receipt', $values);
        $sendTemplateParams['bcc'] = CRM_Utils_Array::value('bcc_receipt', $values);
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }

      // send duplicate alert, if dupe match found during on-behalf-of processing.
      if (!empty($values['onbehalf_dupe_alert'])) {
        $sendTemplateParams['groupName'] = 'msg_tpl_workflow_contribution';
        $sendTemplateParams['valueName'] = 'contribution_dupalert';
        $sendTemplateParams['from'] = ts('Automatically Generated') . " <{$values['receipt_from_email']}>";
        $sendTemplateParams['toName'] = CRM_Utils_Array::value('receipt_from_name', $values);
        $sendTemplateParams['toEmail'] = CRM_Utils_Array::value('receipt_from_email', $values);
        $sendTemplateParams['tplParams']['onBehalfID'] = $contactID;
        $sendTemplateParams['tplParams']['receiptMessage'] = $message;

        // fix cc and reset back to original, CRM-6976
        $sendTemplateParams['cc'] = $originalCCReceipt;

        CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }
    }
  }

  /*
     * Construct the message to be sent by the send function
     *
     */
  /**
   * @param $tplParams
   * @param $contactID
   * @param $isTest
   *
   * @return array
   */
  function composeMessage($tplParams, $contactID, $isTest) {
    $sendTemplateParams = array(
      'groupName' => $tplParams['membershipID'] ? 'msg_tpl_workflow_membership' : 'msg_tpl_workflow_contribution',
      'valueName' => $tplParams['membershipID'] ? 'membership_online_receipt' : 'contribution_online_receipt',
      'contactId' => $contactID,
      'tplParams' => $tplParams,
      'isTest' => $isTest,
      'PDFFilename' => 'receipt.pdf',
    );
    if ($returnMessageText) {
      list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      return array(
        'subject' => $subject,
        'body' => $message,
        'to' => $displayName,
        'html' => $html,
      );
    }
  }

  /**
   * Function to send the emails for Recurring Contribution Notication
   *
   * @param string $type txnType
   * @param int $contactID contact id for contributor
   * @param int $pageID contribution page id
   * @param object $recur object of recurring contribution table
   * @param bool|object $autoRenewMembership is it a auto renew membership.
   *
   * @return void
   * @access public
   * @static
   */
  static function recurringNotify($type, $contactID, $pageID, $recur, $autoRenewMembership = FALSE) {
    $value = array();
    if ($pageID) {
      CRM_Core_DAO::commonRetrieveAll('CRM_Contribute_DAO_ContributionPage', 'id', $pageID, $value, array(
        'title',
        'is_email_receipt',
        'receipt_from_name',
        'receipt_from_email',
        'cc_receipt',
        'bcc_receipt',
      ));
    }

    $isEmailReceipt = CRM_Utils_Array::value('is_email_receipt', $value[$pageID]);
    $isOfflineRecur = FALSE;
    if (!$pageID && $recur->id) {
      $isOfflineRecur = TRUE;
    }
    if ($isEmailReceipt || $isOfflineRecur) {
      if ($pageID) {
        $receiptFrom = '"' . CRM_Utils_Array::value('receipt_from_name', $value[$pageID]) . '" <' . $value[$pageID]['receipt_from_email'] . '>';

        $receiptFromName = $value[$pageID]['receipt_from_name'];
        $receiptFromEmail = $value[$pageID]['receipt_from_email'];
      }
      else {
        $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
        $receiptFrom = "$domainValues[0] <$domainValues[1]>";
        $receiptFromName = $domainValues[0];
        $receiptFromEmail = $domainValues[1];
      }

      list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID, FALSE);
      $templatesParams = array(
        'groupName' => 'msg_tpl_workflow_contribution',
        'valueName' => 'contribution_recurring_notify',
        'contactId' => $contactID,
        'tplParams' => array(
          'recur_frequency_interval' => $recur->frequency_interval,
          'recur_frequency_unit' => $recur->frequency_unit,
          'recur_installments' => $recur->installments,
          'recur_start_date' => $recur->start_date,
          'recur_end_date' => $recur->end_date,
          'recur_amount' => $recur->amount,
          'recur_txnType' => $type,
          'displayName' => $displayName,
          'receipt_from_name' => $receiptFromName,
          'receipt_from_email' => $receiptFromEmail,
          'auto_renew_membership' => $autoRenewMembership,
        ),
        'from' => $receiptFrom,
        'toName' => $displayName,
        'toEmail' => $email,
      );
      //CRM-13811
      if ($pageID) {
        $templatesParams['cc'] = CRM_Utils_Array::value('cc_receipt', $value[$pageID]);
        $templatesParams['bcc'] = CRM_Utils_Array::value('bcc_receipt', $value[$pageID]);
      }
      if ($recur->id) {
        // in some cases its just recurringNotify() thats called for the first time and these urls don't get set.
        // like in PaypalPro, & therefore we set it here additionally.
        $template = CRM_Core_Smarty::singleton();
        $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($recur->id, 'recur', 'obj');
        $url = $paymentProcessor->subscriptionURL($recur->id, 'recur');
        $template->assign('cancelSubscriptionUrl', $url);

        $url = $paymentProcessor->subscriptionURL($recur->id, 'recur', 'billing');
        $template->assign('updateSubscriptionBillingUrl', $url);

        $url = $paymentProcessor->subscriptionURL($recur->id, 'recur', 'update');
        $template->assign('updateSubscriptionUrl', $url);
      }

      list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplate::sendTemplate($templatesParams);

      if ($sent) {
        CRM_Core_Error::debug_log_message('Success: mail sent for recurring notification.');
      }
      else {
        CRM_Core_Error::debug_log_message('Failure: mail not sent for recurring notification.');
      }
    }
  }

  /**
   * Function to add the custom fields for contribution page (ie profile)
   *
   * @param int $gid uf group id
   * @param string $name
   * @param int $cid contact id
   * @param $template
   * @param array $params params to build component whereclause
   *
   * @param null $fieldTypes
   *
   * @return void
   * @access public
   * @static
   */
  public static function buildCustomDisplay($gid, $name, $cid, &$template, &$params, $fieldTypes = NULL) {
    if ($gid) {
      if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $cid)) {
        $values = array();
        $groupTitle = NULL;
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW, NULL, NULL, FALSE, NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL);
        foreach ($fields as $k => $v) {
          if (!$groupTitle) {
            $groupTitle = $v["groupTitle"];
          }
          // suppress all file fields from display and formatting fields
          if (
            CRM_Utils_Array::value('data_type', $v, '') == 'File' ||
            CRM_Utils_Array::value('name', $v, '') == 'image_URL' ||
            CRM_Utils_Array::value('field_type', $v) == 'Formatting'
          ) {
            unset($fields[$k]);
          }

          if (!empty($fieldTypes) && (!in_array($v['field_type'], $fieldTypes))) {
            unset($fields[$k]);
          }
        }

        if ($groupTitle) {
          $template->assign($name . "_grouptitle", $groupTitle);
        }

        CRM_Core_BAO_UFGroup::getValues($cid, $fields, $values, FALSE, $params);

        if (count($values)) {
          $template->assign($name, $values);
        }
      }
    }
  }

  /**
   * This function is to make a copy of a contribution page, including
   * all the blocks in the page
   *
   * @param int $id the contribution page id to copy
   *
   * @return the copy object
   * @access public
   * @static
   */
  static function copy($id) {
    $fieldsFix = array(
      'prefix' => array(
        'title' => ts('Copy of') . ' ',
      ),
    );
    $copy = &CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_ContributionPage', array(
        'id' => $id,
      ), NULL, $fieldsFix);

    //copying all the blocks pertaining to the contribution page
    $copyPledgeBlock = &CRM_Core_DAO::copyGeneric('CRM_Pledge_DAO_PledgeBlock', array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ), array(
        'entity_id' => $copy->id,
      ));

    $copyMembershipBlock = &CRM_Core_DAO::copyGeneric('CRM_Member_DAO_MembershipBlock', array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ), array(
        'entity_id' => $copy->id,
      ));

    $copyUFJoin = &CRM_Core_DAO::copyGeneric('CRM_Core_DAO_UFJoin', array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ), array(
        'entity_id' => $copy->id,
      ));

    $copyWidget = &CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_Widget', array(
        'contribution_page_id' => $id,
      ), array(
        'contribution_page_id' => $copy->id,
      ));

    //copy price sets
    CRM_Price_BAO_PriceSet::copyPriceSet('civicrm_contribution_page', $id, $copy->id);

    $copyTellFriend = &CRM_Core_DAO::copyGeneric('CRM_Friend_DAO_Friend', array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ), array(
        'entity_id' => $copy->id,
      ));

    $copyPersonalCampaignPages = &CRM_Core_DAO::copyGeneric('CRM_PCP_DAO_PCPBlock', array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ), array(
        'entity_id' => $copy->id,
      ));

    $copyPremium = &CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_Premium', array(
        'entity_id' => $id,
        'entity_table' => 'civicrm_contribution_page',
      ), array(
        'entity_id' => $copy->id,
      ));
    $premiumQuery = "
SELECT id
FROM civicrm_premiums
WHERE entity_table = 'civicrm_contribution_page'
      AND entity_id ={$id}";

    $premiumDao = CRM_Core_DAO::executeQuery($premiumQuery, CRM_Core_DAO::$_nullArray);
    while ($premiumDao->fetch()) {
      if ($premiumDao->id) {
        $copyPremiumProduct = & CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_PremiumsProduct', array(
          'premiums_id' => $premiumDao->id,
        ), array(
          'premiums_id' => $copyPremium->id,
        ));
      }
    }

    $copy->save();

    CRM_Utils_Hook::copy('ContributionPage', $copy);

    return $copy;
  }

  /**
   * Function to check if contribution page contains payment
   * processor that supports recurring payment
   *
   * @param int $contributionPageId Contribution Page Id
   *
   * @return boolean true if payment processor supports recurring
   * else false
   *
   * @access public
   * @static
   */
  static function checkRecurPaymentProcessor($contributionPageId) {
    //FIXME
    $sql = "
  SELECT pp.is_recur
  FROM   civicrm_contribution_page  cp,
         civicrm_payment_processor  pp
  WHERE  cp.payment_processor = pp.id
    AND  cp.id = {$contributionPageId}
";

    if ($recurring = &CRM_Core_DAO::singleValueQuery($sql, CRM_Core_DAO::$_nullArray)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Function to get info for all sections enable/disable.
   *
   * @param array $contribPageIds
   * @return array $info info regarding all sections.
   * @access public
   * @static
   */
  static function getSectionInfo($contribPageIds = array()) {
    $info = array();
    $whereClause = NULL;
    if (is_array($contribPageIds) && !empty($contribPageIds)) {
      $whereClause = 'WHERE civicrm_contribution_page.id IN ( ' . implode(', ', $contribPageIds) . ' )';
    }

    $sections = array(
      'settings',
      'amount',
      'membership',
      'custom',
      'thankyou',
      'friend',
      'pcp',
      'widget',
      'premium',
    );
    $query = "
   SELECT  civicrm_contribution_page.id as id,
           civicrm_contribution_page.financial_type_id as settings,
           amount_block_is_active as amount,
           civicrm_membership_block.id as membership,
           civicrm_uf_join.id as custom,
           civicrm_contribution_page.thankyou_title as thankyou,
           civicrm_tell_friend.id as friend,
           civicrm_pcp_block.id as pcp,
           civicrm_contribution_widget.id as widget,
           civicrm_premiums.id as premium
     FROM  civicrm_contribution_page
LEFT JOIN  civicrm_membership_block    ON ( civicrm_membership_block.entity_id = civicrm_contribution_page.id
                                            AND civicrm_membership_block.entity_table = 'civicrm_contribution_page'
                                            AND civicrm_membership_block.is_active = 1 )
LEFT JOIN  civicrm_uf_join             ON ( civicrm_uf_join.entity_id = civicrm_contribution_page.id
                                            AND civicrm_uf_join.entity_table = 'civicrm_contribution_page'
                                            AND module = 'CiviContribute'
                                            AND civicrm_uf_join.is_active = 1 )
LEFT JOIN  civicrm_tell_friend         ON ( civicrm_tell_friend.entity_id = civicrm_contribution_page.id
                                            AND civicrm_tell_friend.entity_table = 'civicrm_contribution_page'
                                            AND civicrm_tell_friend.is_active = 1)
LEFT JOIN  civicrm_pcp_block           ON ( civicrm_pcp_block.entity_id = civicrm_contribution_page.id
                                            AND civicrm_pcp_block.entity_table = 'civicrm_contribution_page'
                                            AND civicrm_pcp_block.is_active = 1 )
LEFT JOIN  civicrm_contribution_widget ON ( civicrm_contribution_widget.contribution_page_id = civicrm_contribution_page.id
                                            AND civicrm_contribution_widget.is_active = 1 )
LEFT JOIN  civicrm_premiums            ON ( civicrm_premiums.entity_id = civicrm_contribution_page.id
                                            AND civicrm_premiums.entity_table = 'civicrm_contribution_page'
                                            AND civicrm_premiums.premiums_active = 1 )
           $whereClause";

    $contributionPage = CRM_Core_DAO::executeQuery($query);
    while ($contributionPage->fetch()) {
      if (!isset($info[$contributionPage->id]) || !is_array($info[$contributionPage->id])) {
        $info[$contributionPage->id] = array_fill_keys(array_values($sections), FALSE);
      }
      foreach ($sections as $section) {
        if ($contributionPage->$section) {
          $info[$contributionPage->id][$section] = TRUE;
        }
      }
    }

    return $info;
  }

  /**
   * Get options for a given field.
   * @see CRM_Core_DAO::buildOptions
   *
   * @param String $fieldName
   * @param String $context: @see CRM_Core_DAO::buildOptionsContext
   * @param Array  $props: whatever is known about this dao object
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = array()) {
    $params = array();
    // Special logic for fields whose options depend on context or properties
    switch ($fieldName) {
      case 'financial_type_id':
        // Fixme - this is going to ignore context, better to get conditions, add params, and call PseudoConstant::get
        return CRM_Financial_BAO_FinancialType::getIncomeFinancialType();
        break;
    }
    return CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, $params, $context);
  }

  /**
   * Get or Set multilingually affected honor params for processing module_data or setting default values.
   *
   * @param Array|String $params: Array when we need to format it according to language state or String as a json encode
   * @param Boolean      $setDefault: If yes then returns array to used for setting default value afterward
   *
   * @return array|string
   */
  public static function formatMultilingualHonorParams($params, $setDefault = FALSE) {
    $config = CRM_Core_Config::singleton();
    $sctJson = $sctJsonDecode = NULL;
    $domain = new CRM_Core_DAO_Domain();
    $domain->find(TRUE);

    //When we are fetching the honor params respecting both multi and mono lingual state
    //and setting it to default param of Contribution Page's Main and Setting form
    if ($setDefault) {
      $sctJsonDecode = json_decode($params);
      $sctJsonDecode = (array) $sctJsonDecode->soft_credit;
      if (!$domain->locales && !empty($sctJsonDecode['default'])) {
        //monolingual state
        $sctJsonDecode += (array) $sctJsonDecode['default'];
      }
      elseif (!empty($sctJsonDecode[$config->lcMessages])) {
        //multilingual state
        foreach ($sctJsonDecode[$config->lcMessages] as $column => $value) {
          $sctJsonDecode[$column] = $value;
        }
        unset($sctJsonDecode[$config->lcMessages]);
      }
      return $sctJsonDecode;
    }

    //check and handle multilingual honoree params
    if (!$domain->locales) {
      //if in singlelingual state simply return the array format
      $sctJson = json_encode(
        array(
          'soft_credit' => array(
            'soft_credit_types' => $params['soft_credit_types'],
            'default' => array(
              'honor_block_title' => $params['honor_block_title'],
              'honor_block_text' => $params['honor_block_text']
            )
          )
        )
      );
    }
    else {
      //if in multilingual state then retrieve the module_data against this contribution and
      //merge with earlier module_data json data to current so not to lose earlier multilingual module_data information
      $sctJson =  array(
        'soft_credit' => array(
          'soft_credit_types' => $params['soft_credit_types'],
          $config->lcMessages => array (
            'honor_block_title' => $params['honor_block_title'],
            'honor_block_text' => $params['honor_block_text']
          )
        )
      );

      $ufJoinDAO = new CRM_Core_DAO_UFJoin();
      $ufJoinDAO->module = 'soft_credit';
      $ufJoinDAO->entity_id = $params['id'];
      $ufJoinDAO->find(TRUE);
      $jsonData = json_decode($ufJoinDAO->module_data);
      if ($jsonData) {
        $sctJson['soft_credit'] = array_merge((array)$jsonData->soft_credit, $sctJson['soft_credit']);
      }
      $sctJson = json_encode($sctJson);
    }
    return $sctJson;
  }


  /**
   * helper to determine if the page supports separate membership payments
   * @param integer id form id
   *
   * @return bool isSeparateMembershipPayment
   */
  static function getIsMembershipPayment($id) {
    $membershipBlocks = civicrm_api3('membership_block', 'get', array('entity_table' => 'civicrm_contribution_page', 'entity_id' => $id, 'sequential' => TRUE));
    if(!$membershipBlocks['count']) {
      return FALSE;
    }
    return $membershipBlocks['values'][0]['is_separate_payment'];
  }
}

