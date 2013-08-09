<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 * This class contains Grant Application Page related functions.
 */
class CRM_Grant_BAO_GrantApplicationPage extends CRM_Grant_DAO_GrantApplicationPage {

  /**
   * takes an associative array and creates a grant application page object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Grant_DAO_GrantApplicationPage object
   * @access public
   * @static
   */
  public static function &create(&$params) {
    $dao = new CRM_Grant_DAO_GrantApplicationPage();
    $dao->copyValues($params);
    $dao->save();
    return $dao;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   * @static
   */
  static
  function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Grant_DAO_GrantApplicationPage', $id, 'is_active', $is_active);
  }

  static function setValues($id, &$values) {
    $params = array(
      'id' => $id,
    );

    CRM_Core_DAO::commonRetrieve('CRM_Grant_DAO_GrantApplicationPage', $params, $values);
    
    // get the profile ids
    $ufJoinParams = array(
      'module' => 'CiviGrant',
      'entity_table' => 'civicrm_grant_app_page',
      'entity_id' => $id,
    );
    list($values['custom_pre_id'],
      $customPostIds
    ) = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

    if (!empty($customPostIds)) {
      $values['custom_post_id'] = $customPostIds[0];
    }
    else {
      $values['custom_post_id'] = '';
    }
  }

  /**
   * Function to send the emails
   *
   * @param int     $contactID         contact id
   * @param array   $values            associated array of fields
   * @param boolean $isTest            if in test mode
   * @param boolean $returnMessageText return the message text instead of sending the mail
   *
   * @return void
   * @access public
   * @static
   */
  static function sendMail($contactID, &$values, $returnMessageText = FALSE, $fieldTypes = NULL) {
    $gIds = $params = array();
    $email = NULL;
    if (isset($values['custom_pre_id'])) {
      $preProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_pre_id']);
      if ($preProfileType == 'Grant' && CRM_Utils_Array::value('grant_id', $values)) {
        $params['custom_pre_id'] = array(array('grant_id', '=', $values['grant_id'], 0, 0));
      }

      $gIds['custom_pre_id'] = $values['custom_pre_id'];
    }

    if (isset($values['custom_post_id'])) {
      $postProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_post_id']);
      if ($postProfileType == 'Grant' && CRM_Utils_Array::value('grant_id', $values)) {
        $params['custom_post_id'] = array(array('grant_id', '=', $values['grant_id'], 0, 0));
      }

      $gIds['custom_post_id'] = $values['custom_post_id'];
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

    if (CRM_Utils_Array::value('is_email_receipt', $values) ||
      CRM_Utils_Array::value('onbehalf_dupe_alert', $values) ||
      $returnMessageText
    ) {
      $template = CRM_Core_Smarty::singleton();

      // get the billing location type
      if (!array_key_exists('related_contact', $values)) {
        $locationTypes = CRM_Core_PseudoConstant::get('CRM_Core_DAO_Address', 'location_type_id');
        $billingLocationTypeId = array_search('Billing', $locationTypes);
      }
      else {
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

      $userID = $contactID;
      if ($preID = CRM_Utils_Array::value('custom_pre_id', $values)) {
        if (CRM_Utils_Array::value('related_contact', $values)) {
          $preProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($preID);
          if (in_array('Individual', $preProfileTypes) || in_array('Contact', $postProfileTypes)) {
            //Take Individual contact ID
            $userID = CRM_Utils_Array::value('related_contact', $values);
          }
        }
        CRM_Contribute_BAO_ContributionPage::buildCustomDisplay($preID, 'customPre', $userID, $template, $params['custom_pre_id']);
      }
      $userID = $contactID;
      if ($postID = CRM_Utils_Array::value('custom_post_id', $values)) {
        if (CRM_Utils_Array::value('related_contact', $values)) {
          $postProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($postID);
          if (in_array('Individual', $postProfileTypes) || in_array('Contact', $postProfileTypes)) {
            //Take Individual contact ID
            $userID = CRM_Utils_Array::value('related_contact', $values);
          }
        }
        CRM_Contribute_BAO_ContributionPage::buildCustomDisplay($postID, 'customPost', $userID, $template, $params['custom_post_id']);
      }

      $title = isset($values['title']) ? $values['title'] : CRM_Core_DAO::getFieldValue('CRM_Grant_DAO_GrantApplicationPage', $values['id'], 'title');

      // set email in the template here
      $tplParams = array(
        'email' => $email,
        'receiptFromEmail' => CRM_Utils_Array::value('receipt_from_email', $values),
        'contactID' => $contactID,
        'displayName' => $displayName,
        'grantID' => CRM_Utils_Array::value('grant_id', $values),
        'title' => $title,
      );
    
      if ($grantTypeId = CRM_Utils_Array::value('grant_type_id', $values)) {
        $tplParams['grantTypeId'] = $grantTypeId;
        $tplParams['grantTypeName'] = CRM_Core_OptionGroup::getLabel('grant_type', $grantTypeId);
      }

      if ($grantApplicationPageId = CRM_Utils_Array::value('id', $values)) {
        $tplParams['grantApplicationPageId'] = $grantApplicationPageId;
      }
      $originalCCReceipt = CRM_Utils_Array::value('cc_receipt', $values);

      $sendTemplateParams = array(
        'groupName' => 'msg_tpl_workflow_grant',
        'valueName' => 'grant_online_receipt',
        'contactId' => $contactID,
        'tplParams' => $tplParams,
        'PDFFilename' => 'receipt.pdf',
      );

      if ($returnMessageText) {
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams);
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
        list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams);
      }
    }
  }
  
  /*
     * Construct the message to be sent by the send function
     *
     */
  function composeMessage($tplParams, $contactID, $isTest) {
    $sendTemplateParams = array(
      'groupName' => 'msg_tpl_workflow_grant',
      'valueName' => 'grant_online_receipt',
      'contactId' => $contactID,
      'tplParams' => $tplParams,
      'PDFFilename' => 'receipt.pdf',
    );
    if ($returnMessageText) {
      list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate($sendTemplateParams);
      return array(
        'subject' => $subject,
        'body' => $message,
        'to' => $displayName,
        'html' => $html,
      );
    }
  }

  /**
   * Function to get info for all sections enable/disable.
   *
   * @return array $info info regarding all sections.
   * @access public
   * @static
   */
  static function getSectionInfo($grantAppPageIds = array(
    )) {
    $info = array();
    $whereClause = NULL;
    if (is_array($grantAppPageIds) && !empty($grantAppPageIds)) {
      $whereClause = 'WHERE civicrm_grant_app_page.id IN ( ' . implode(', ', $grantAppPageIds) . ' )';
    }
 
    $sections = array(
      'settings',
      'custom',
      'thankyou',
    );
    $query = "SELECT  civicrm_grant_app_page.id as id,
civicrm_grant_app_page.grant_type_id as settings, 
civicrm_uf_join.id as custom,
civicrm_grant_app_page.thankyou_title as thankyou
FROM  civicrm_grant_app_page
LEFT JOIN  civicrm_uf_join ON ( civicrm_uf_join.entity_id = civicrm_grant_app_page.id 
AND civicrm_uf_join.entity_table = 'civicrm_grant_app_page'
AND module = 'CiviGrant'  AND civicrm_uf_join.is_active = 1 ) $whereClause";

    $grantAppPage = CRM_Core_DAO::executeQuery($query);
    while ($grantAppPage->fetch()) {
      if (!isset($info[$grantAppPage->id]) || !is_array($info[$grantAppPage->id])) {
        $info[$grantAppPage->id] = array_fill_keys(array_values($sections), FALSE);
      }
      foreach ($sections as $section) {
        if ($grantAppPage->$section) {
          $info[$grantAppPage->id][$section] = TRUE;
        }
      }
    }

    return $info;
  }
}

