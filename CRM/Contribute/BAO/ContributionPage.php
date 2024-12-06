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

use Civi\Api4\Contribution;
use Civi\Api4\LineItem;
use Civi\Core\Event\PostEvent;
use Civi\Core\HookInterface;

/**
 * This class contains Contribution Page related functions.
 */
class CRM_Contribute_BAO_ContributionPage extends CRM_Contribute_DAO_ContributionPage implements HookInterface {

  /**
   * @deprecated
   * @param array $params
   * @return CRM_Contribute_DAO_ContributionPage
   */
  public static function create($params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::writeRecord($params);
  }

  /**
   * Callback for hook_civicrm_post().
   *
   * @param \Civi\Core\Event\PostEvent $event
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public static function self_hook_civicrm_post(PostEvent $event): void {
    CRM_Core_PseudoConstant::flush();
    Civi::cache('metadata')->clear();
  }

  /**
   * @deprecated - this bypasses hooks.
   * @param int $id
   * @param bool $is_active
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionPage', $id, 'is_active', $is_active);
  }

  /**
   * Load values for a contribution page.
   *
   * @param int $id
   * @param array $values
   */
  public static function setValues($id, &$values) {
    $modules = ['CiviContribute', 'soft_credit', 'on_behalf'];
    $values['custom_pre_id'] = $values['custom_post_id'] = NULL;

    $params = ['id' => $id];
    CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_ContributionPage', $params, $values);

    // get the profile ids
    $ufJoinParams = [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $id,
    ];

    // retrieve profile id as also unserialize module_data corresponding to each $module
    foreach ($modules as $module) {
      $ufJoinParams['module'] = $module;
      $ufJoin = new CRM_Core_DAO_UFJoin();
      $ufJoin->copyValues($ufJoinParams);
      if ($module == 'CiviContribute') {
        $ufJoin->orderBy('weight asc');
        $ufJoin->find();
        while ($ufJoin->fetch()) {
          if ($ufJoin->weight == 1) {
            $values['custom_pre_id'] = $ufJoin->uf_group_id;
          }
          else {
            $values['custom_post_id'] = $ufJoin->uf_group_id;
          }
        }
      }
      else {
        $ufJoin->find(TRUE);
        if (!$ufJoin->is_active) {
          continue;
        }
        $params = CRM_Contribute_BAO_ContributionPage::formatModuleData($ufJoin->module_data, TRUE, $module);
        $values = array_merge($params, $values);
        if ($module == 'soft_credit') {
          $values['honoree_profile_id'] = $ufJoin->uf_group_id;
          $values['honor_block_is_active'] = $ufJoin->is_active;
        }
        else {
          $values['onbehalf_profile_id'] = $ufJoin->uf_group_id;
        }
      }
    }
  }

  /**
   * Send the emails.
   *
   * @param int $contactID
   *   Contact id.
   * @param array $values
   *   Associated array of fields.
   * @param bool $isTest
   *   If in test mode.
   * @param bool $returnMessageText
   *   Return the message text instead of sending the mail.
   *
   * @param array $fieldTypes
   *
   * @throws \CRM_Core_Exception
   */
  public static function sendMail($contactID, $values, $isTest = FALSE, $returnMessageText = FALSE, $fieldTypes = NULL) {
    $gIds = [];
    $params = ['custom_pre_id' => [], 'custom_post_id' => []];
    $email = NULL;

    // We are trying to fight the good fight against leaky variables (CRM-17519) so let's get really explicit
    // about ensuring the variables we want for the template are defined.
    // @todo add to this until all tpl params are explicit in this function and not waltzing around the codebase.
    // Next stage is to remove this & ensure there are no e-notices - ie. all are set before they hit this fn.
    $valuesRequiredForTemplate = [
      'customPre',
      'customPost',
      'customPre_grouptitle',
      'customPost_grouptitle',
      'useForMember',
      'amount',
      'receipt_date',
      'is_pay_later',
    ];

    foreach ($valuesRequiredForTemplate as $valueRequiredForTemplate) {
      if (!isset($values[$valueRequiredForTemplate])) {
        $values[$valueRequiredForTemplate] = NULL;
      }
    }

    if (isset($values['custom_pre_id'])) {
      $preProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_pre_id']);
      if ($preProfileType == 'Membership' && !empty($values['membership_id'])) {
        $params['custom_pre_id'] = [
          [
            'member_id',
            '=',
            $values['membership_id'],
            0,
            0,
          ],
        ];
      }
      elseif ($preProfileType == 'Contribution' && !empty($values['contribution_id'])) {
        $params['custom_pre_id'] = [
          [
            'contribution_id',
            '=',
            $values['contribution_id'],
            0,
            0,
          ],
        ];
      }

      $gIds['custom_pre_id'] = $values['custom_pre_id'];
    }

    if (isset($values['custom_post_id'])) {
      $postProfileType = CRM_Core_BAO_UFField::getProfileType($values['custom_post_id']);
      if ($postProfileType == 'Membership' && !empty($values['membership_id'])) {
        $params['custom_post_id'] = [
          [
            'member_id',
            '=',
            $values['membership_id'],
            0,
            0,
          ],
        ];
      }
      elseif ($postProfileType == 'Contribution' && !empty($values['contribution_id'])) {
        $params['custom_post_id'] = [
          [
            'contribution_id',
            '=',
            $values['contribution_id'],
            0,
            0,
          ],
        ];
      }

      $gIds['custom_post_id'] = $values['custom_post_id'];
    }

    if (!empty($values['is_for_organization'])) {
      if (!empty($values['membership_id'])) {
        $params['onbehalf_profile'] = [
          [
            'member_id',
            '=',
            $values['membership_id'],
            0,
            0,
          ],
        ];
      }
      elseif (!empty($values['contribution_id'])) {
        $params['onbehalf_profile'] = [
          [
            'contribution_id',
            '=',
            $values['contribution_id'],
            0,
            0,
          ],
        ];
      }
    }

    //check whether it is a test drive
    if ($isTest && !empty($params['custom_pre_id'])) {
      $params['custom_pre_id'][] = [
        'contribution_test',
        '=',
        1,
        0,
        0,
      ];
    }

    if ($isTest && !empty($params['custom_post_id'])) {
      $params['custom_post_id'][] = ['contribution_test', '=', 1, 0, 0];
    }

    if (!$returnMessageText && !empty($gIds)) {
      //send notification email if field values are set (CRM-1941)
      foreach ($gIds as $key => $gId) {
        if ($gId) {
          $email = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFGroup', $gId, 'notify');
          if ($email) {
            $val = CRM_Core_BAO_UFGroup::checkFieldsEmptyValues($gId, $contactID, $params[$key] ?? NULL, TRUE);
            CRM_Core_BAO_UFGroup::commonSendMail($contactID, $val);
          }
        }
      }
    }

    if (!empty($values['is_email_receipt']) || !empty($values['onbehalf_dupe_alert']) ||
      $returnMessageText
    ) {
      $template = CRM_Core_Smarty::singleton();

      if (!array_key_exists('related_contact', $values)) {
        [$displayName, $email] = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID, FALSE, CRM_Core_BAO_LocationType::getBilling());
      }
      // get primary location email if no email exist( for billing location).
      if (!$email) {
        [$displayName, $email] = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      }
      if (empty($displayName)) {
        [$displayName, $email] = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      }

      //for display profile need to get individual contact id,
      //hence get it from related_contact if on behalf of org true CRM-3767
      //CRM-5001 Contribution/Membership:: On Behalf of Organization,
      //If profile GROUP contain the Individual type then consider the
      //profile is of Individual ( including the custom data of membership/contribution )
      //IF Individual type not present in profile then it is consider as Organization data.
      $userID = $contactID;
      $preID = $values['custom_pre_id'] ?? NULL;
      if ($preID) {
        if (!empty($values['related_contact'])) {
          $preProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($preID);
          if (in_array('Individual', $preProfileTypes) || in_array('Contact', $preProfileTypes)) {
            //Take Individual contact ID
            $userID = $values['related_contact'] ?? NULL;
          }
        }
        [$values['customPre_grouptitle'], $values['customPre']] = self::getProfileNameAndFields($preID, $userID, $params['custom_pre_id']);
      }
      $userID = $contactID;
      $postID = $values['custom_post_id'] ?? NULL;
      if ($postID) {
        if (!empty($values['related_contact'])) {
          $postProfileTypes = CRM_Core_BAO_UFGroup::profileGroups($postID);
          if (in_array('Individual', $postProfileTypes) || in_array('Contact', $postProfileTypes)) {
            //Take Individual contact ID
            $userID = $values['related_contact'] ?? NULL;
          }
        }
        [$values['customPost_grouptitle'], $values['customPost']] = self::getProfileNameAndFields($postID, $userID, $params['custom_post_id']);
      }
      // Assign honoree values for the receipt.
      $honorValues = $values['honor'] ?? ['honor_profile_id' => NULL, 'honor_id' => NULL, 'honor_profile_values' => []];
      foreach (CRM_Contribute_BAO_ContributionSoft::getHonorTemplateVariables(
        $honorValues['honor_profile_id'] ? (int) $honorValues['honor_profile_id'] : NULL,
        $honorValues['honor_id'] ? (int) $honorValues['honor_id'] : NULL,
        $honorValues['honor_profile_values'] ?? [],
      ) as $honorFieldName => $honorFieldValue) {
        $template->assign($honorFieldName, $honorFieldValue);
      }

      $title = $values['title'] ?? CRM_Contribute_BAO_Contribution_Utils::getContributionPageTitle($values['contribution_page_id']);

      // Set email variables explicitly to avoid leaky smarty variables.
      // All of these will be assigned to the template, replacing any that might be assigned elsewhere.
      $tplParams = [
        'email' => $email,
        'receiptFromEmail' => $values['receipt_from_email'] ?? NULL,
        'contactID' => $contactID,
        'displayName' => $displayName,
        'contributionID' => $values['contribution_id'] ?? NULL,
        'contributionOtherID' => $values['contribution_other_id'] ?? NULL,
        // CRM-5095
        'lineItem' => $values['lineItem'] ?? NULL,
        // CRM-5095
        'priceSetID' => $values['priceSetID'] ?? NULL,
        'title' => $title,
        'isShare' => $values['is_share'] ?? NULL,
        'thankyou_title' => $values['thankyou_title'] ?? NULL,
        'customPre' => $values['customPre'],
        'customPre_grouptitle' => $values['customPre_grouptitle'],
        'customPost' => $values['customPost'],
        'customPost_grouptitle' => $values['customPost_grouptitle'],
        'useForMember' => $values['useForMember'],
        'amount' => $values['amount'],
        'is_pay_later' => $values['is_pay_later'],
        'receipt_date' => !$values['receipt_date'] ? NULL : date('YmdHis', strtotime($values['receipt_date'])),
        'pay_later_receipt' => $values['pay_later_receipt'] ?? NULL,
        'honor_block_is_active' => $values['honor_block_is_active'] ?? NULL,
        'contributionStatus' => $values['contribution_status'] ?? NULL,
        'currency' => CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $values['contribution_id'], 'currency') ?? CRM_Core_Config::singleton()->defaultCurrency,
      ];

      if (!empty($values['financial_type_id'])) {
        $tplParams['financialTypeId'] = $values['financial_type_id'];
        $tplParams['financialTypeName'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
          $values['financial_type_id']);
        // Legacy support
        $tplParams['contributionTypeName'] = $tplParams['financialTypeName'];
      }

      $contributionPageId = $values['id'] ?? NULL;
      if ($contributionPageId) {
        $tplParams['contributionPageId'] = $contributionPageId;
      }

      // address required during receipt processing (pdf and email receipt)
      $displayAddress = $values['address'] ?? NULL;
      if ($displayAddress) {
        $tplParams['address'] = $displayAddress;
      }

      // CRM-6976
      $originalCCReceipt = $values['cc_receipt'] ?? NULL;

      // cc to related contacts of contributor OR the one who
      // signs up. Is used for cases like - on behalf of
      // contribution / signup ..etc
      if (array_key_exists('related_contact', $values)) {
        [$ccDisplayName, $ccEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($values['related_contact']);
        $ccMailId = "{$ccDisplayName} <{$ccEmail}>";

        //@todo - this is the only place in this function where  $values is altered - but I can't find any evidence it is used
        $values['cc_receipt'] = !empty($values['cc_receipt']) ? ($values['cc_receipt'] . ',' . $ccMailId) : $ccMailId;

        // reset primary-email in the template
        $tplParams['email'] = $ccEmail;

        $tplParams['onBehalfName'] = $displayName;
        $tplParams['onBehalfEmail'] = $email;

        if (!empty($values['onbehalf_profile_id'])) {
          self::buildCustomDisplay($values['onbehalf_profile_id'], 'onBehalfProfile', $contactID, $template, $params['onbehalf_profile'], $fieldTypes);
        }
      }

      // use either the contribution or membership receipt, based on whether it’s a membership-related contrib or not
      $tokenContext = ['contactId' => (int) $contactID];
      if (!empty($tplParams['contributionID'])) {
        $tokenContext['contributionId'] = $tplParams['contributionID'];
      }
      if (!empty($values['membership_id'])) {
        $tokenContext['membershipId'] = $values['membership_id'];
      }
      $sendTemplateParams = [
        'workflow' => !empty($values['membership_id']) ? 'membership_online_receipt' : 'contribution_online_receipt',
        'contactId' => $contactID,
        'tplParams' => $tplParams,
        'tokenContext' => $tokenContext,
        'isTest' => $isTest,
        'PDFFilename' => 'receipt.pdf',
        'modelProps' => $values['modelProps'] ?? [],
      ];

      if ($returnMessageText) {
        [$sent, $subject, $message, $html] = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
        return [
          'subject' => $subject,
          'body' => $message,
          'to' => $displayName,
          'html' => $html,
        ];
      }

      if (empty($values['receipt_from_email'])) {
        [$values['receipt_from_name'], $values['receipt_from_email']] = CRM_Core_BAO_Domain::getNameAndEmail();
      }

      if ($values['is_email_receipt']) {
        $sendTemplateParams['from'] = ($values['receipt_from_name'] ?? '') . ' <' . $values['receipt_from_email'] . '>';
        $sendTemplateParams['toName'] = $displayName;
        $sendTemplateParams['toEmail'] = $email;
        $sendTemplateParams['cc'] = $values['cc_receipt'] ?? NULL;
        $sendTemplateParams['bcc'] = $values['bcc_receipt'] ?? NULL;
        //send email with pdf invoice
        if (Civi::settings()->get('invoice_is_email_pdf')) {
          $sendTemplateParams['isEmailPdf'] = TRUE;
          $sendTemplateParams['contributionId'] = $values['contribution_id'];
        }
        [$sent, $subject, $message] = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }

      // send duplicate alert, if dupe match found during on-behalf-of processing.
      if (!empty($values['onbehalf_dupe_alert'])) {
        $sendTemplateParams['groupName'] = 'msg_tpl_workflow_contribution';
        $sendTemplateParams['workflow'] = 'contribution_dupalert';
        $sendTemplateParams['from'] = ts('Automatically Generated') . " <{$values['receipt_from_email']}>";
        $sendTemplateParams['toName'] = $values['receipt_from_name'] ?? NULL;
        $sendTemplateParams['toEmail'] = $values['receipt_from_email'] ?? NULL;
        $sendTemplateParams['tplParams']['onBehalfID'] = $contactID;
        $sendTemplateParams['tplParams']['receiptMessage'] = $message;

        // fix cc and reset back to original, CRM-6976
        $sendTemplateParams['cc'] = $originalCCReceipt;

        CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }
    }
  }

  /**
   * Get the profile title and fields.
   *
   * @param int $gid
   * @param int $cid
   * @param array $params
   * @param array $fieldTypes
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected static function getProfileNameAndFields($gid, $cid, $params, $fieldTypes = []) {
    $groupTitle = NULL;
    $values = [];
    if ($gid) {
      if (CRM_Core_BAO_UFGroup::filterUFGroups($gid, $cid)) {
        $fields = CRM_Core_BAO_UFGroup::getFields($gid, FALSE, CRM_Core_Action::VIEW, NULL, NULL, FALSE, NULL, FALSE, NULL, CRM_Core_Permission::CREATE, NULL);
        foreach ($fields as $k => $v) {
          if (!$groupTitle) {
            $groupTitle = $v['groupDisplayTitle'];
          }
          // suppress all file fields from display and formatting fields
          if (
            $v['data_type'] === 'File' || $v['name'] === 'image_URL' || $v['field_type'] === 'Formatting') {
            unset($fields[$k]);
          }

          if (!empty($fieldTypes) && (!in_array($v['field_type'], $fieldTypes))) {
            unset($fields[$k]);
          }
        }

        CRM_Core_BAO_UFGroup::getValues($cid, $fields, $values, FALSE, $params);
      }
    }
    return [$groupTitle, $values];
  }

  /**
   * Send the emails for Recurring Contribution Notification.
   *
   * @param int $contributionID
   * @param string $type
   *   TxnType.
   *   Contribution page id.
   * @param object $recur
   *
   * @throws \CRM_Core_Exception
   */
  public static function recurringNotify($contributionID, $type, $recur): void {
    $contribution = Contribution::get(FALSE)
      ->addWhere('id', '=', $contributionID)
      ->setSelect([
        'contribution_page_id',
        'contact_id',
        'contribution_recur_id',
        'contribution_recur_id.is_email_receipt',
        'contribution_page_id.title',
        'contribution_page_id.is_email_receipt',
        'contribution_page_id.receipt_from_name',
        'contribution_page_id.receipt_from_email',
        'contribution_page_id.cc_receipt',
        'contribution_page_id.bcc_receipt',
      ])
      ->execute()->first();

    $isMembership = !empty(LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contributionID)
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->addSelect('id')->execute()->first());

    if ($contribution['contribution_recur_id.is_email_receipt'] || $contribution['contribution_page_id.is_email_receipt']) {
      if ($contribution['contribution_page_id.receipt_from_email']) {
        $receiptFromName = $contribution['contribution_page_id.receipt_from_name'];
        $receiptFromEmail = $contribution['contribution_page_id.receipt_from_email'];
      }
      else {
        [$receiptFromName, $receiptFromEmail] = CRM_Core_BAO_Domain::getNameAndEmail();
      }

      $receiptFrom = "$receiptFromName <$receiptFromEmail>";
      [$displayName, $email] = CRM_Contact_BAO_Contact_Location::getEmailDetails($contribution['contact_id'], FALSE);
      $templatesParams = [
        'groupName' => 'msg_tpl_workflow_contribution',
        'workflow' => 'contribution_recurring_notify',
        'contactId' => $contribution['contact_id'],
        'tplParams' => [
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
          'auto_renew_membership' => $isMembership,
        ],
        'from' => $receiptFrom,
        'toName' => $displayName,
        'toEmail' => $email,
      ];
      //CRM-13811
      $templatesParams['cc'] = $contribution['contribution_page_id.cc_receipt'];
      $templatesParams['bcc'] = $contribution['contribution_page_id.cc_receipt'];
      if ($recur->id) {
        // in some cases its just recurringNotify() thats called for the first time and these urls don't get set.
        // like in PaypalPro, & therefore we set it here additionally.
        $template = CRM_Core_Smarty::singleton();
        $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($recur->id, 'recur', 'obj');
        $url = $paymentProcessor->subscriptionURL($recur->id, 'recur', 'cancel');
        $template->assign('cancelSubscriptionUrl', $url);

        $url = $paymentProcessor->subscriptionURL($recur->id, 'recur', 'billing');
        $template->assign('updateSubscriptionBillingUrl', $url);

        $url = $paymentProcessor->subscriptionURL($recur->id, 'recur', 'update');
        $template->assign('updateSubscriptionUrl', $url);
      }

      [$sent] = CRM_Core_BAO_MessageTemplate::sendTemplate($templatesParams);

      if ($sent) {
        CRM_Core_Error::debug_log_message('Success: mail sent for recurring notification.');
      }
      else {
        CRM_Core_Error::debug_log_message('Failure: mail not sent for recurring notification.');
      }
    }
  }

  /**
   * Add the custom fields for contribution page (ie profile).
   *
   * @deprecated assigning values to smarty like this is risky because
   *  - it is hard to debug since $name is used in the assign
   *  - it is potentially 'leaky' - it's better to do this on the form
   *  or close to where it is used / required. See CRM-17519 for leakage e.g.
   *
   * @param int $gid
   *   Uf group id.
   * @param string $name
   * @param int $cid
   *   Contact id.
   * @param $template
   * @param array $params
   *   Params to build component whereclause.
   *
   * @param array|null $fieldTypes
   */
  public static function buildCustomDisplay($gid, $name, $cid, &$template, &$params, $fieldTypes = NULL) {
    [$groupTitle, $values] = self::getProfileNameAndFields($gid, $cid, $params, $fieldTypes);
    if (!empty($values)) {
      $template->assign($name, $values);
    }
    $template->assign($name . "_grouptitle", $groupTitle);
  }

  /**
   * Make a copy of a contribution page, including all the blocks in the page.
   *
   * @param int $id
   *   The contribution page id to copy.
   *
   * @return CRM_Contribute_DAO_ContributionPage
   */
  public static function copy($id) {
    $session = CRM_Core_Session::singleton();

    $fieldsFix = [
      'prefix' => [
        'title' => ts('Copy of') . ' ',
      ],
      'replace' => [
        'created_id' => $session->get('userID'),
        'created_date' => date('YmdHis'),
      ],
    ];
    $copy = CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_ContributionPage', [
      'id' => $id,
    ], NULL, $fieldsFix);

    //copying all the blocks pertaining to the contribution page
    $copyPledgeBlock = CRM_Core_DAO::copyGeneric('CRM_Pledge_DAO_PledgeBlock', [
      'entity_id' => $id,
      'entity_table' => 'civicrm_contribution_page',
    ], [
      'entity_id' => $copy->id,
    ]);

    $copyMembershipBlock = CRM_Core_DAO::copyGeneric('CRM_Member_DAO_MembershipBlock', [
      'entity_id' => $id,
      'entity_table' => 'civicrm_contribution_page',
    ], [
      'entity_id' => $copy->id,
    ]);

    $copyUFJoin = CRM_Core_DAO::copyGeneric('CRM_Core_DAO_UFJoin', [
      'entity_id' => $id,
      'entity_table' => 'civicrm_contribution_page',
    ], [
      'entity_id' => $copy->id,
    ]);

    $copyWidget = CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_Widget', [
      'contribution_page_id' => $id,
    ], [
      'contribution_page_id' => $copy->id,
    ]);

    //copy price sets
    CRM_Price_BAO_PriceSet::copyPriceSet('civicrm_contribution_page', $id, $copy->id);

    $copyTellFriend = CRM_Core_DAO::copyGeneric('CRM_Friend_DAO_Friend', [
      'entity_id' => $id,
      'entity_table' => 'civicrm_contribution_page',
    ], [
      'entity_id' => $copy->id,
    ]);

    $copyPersonalCampaignPages = CRM_Core_DAO::copyGeneric('CRM_PCP_DAO_PCPBlock', [
      'entity_id' => $id,
      'entity_table' => 'civicrm_contribution_page',
    ], [
      'entity_id' => $copy->id,
      'target_entity_id' => $copy->id,
    ]);

    $copyPremium = CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_Premium', [
      'entity_id' => $id,
      'entity_table' => 'civicrm_contribution_page',
    ], [
      'entity_id' => $copy->id,
    ]);
    $premiumQuery = "
SELECT id
FROM civicrm_premiums
WHERE entity_table = 'civicrm_contribution_page'
      AND entity_id ={$id}";

    $premiumDao = CRM_Core_DAO::executeQuery($premiumQuery);
    while ($premiumDao->fetch()) {
      if ($premiumDao->id) {
        CRM_Core_DAO::copyGeneric('CRM_Contribute_DAO_PremiumsProduct', [
          'premiums_id' => $premiumDao->id,
        ], [
          'premiums_id' => $copyPremium->id,
        ]);
      }
    }

    $copy->save();

    CRM_Utils_Hook::copy('ContributionPage', $copy, $id);

    return $copy;
  }

  /**
   * Get info for all sections enable/disable.
   *
   * @param array $contribPageIds
   * @return array
   *   info regarding all sections.
   */
  public static function getSectionInfo($contribPageIds = []) {
    $info = [];
    $whereClause = NULL;
    if (is_array($contribPageIds) && !empty($contribPageIds)) {
      $whereClause = 'WHERE civicrm_contribution_page.id IN ( ' . implode(', ', $contribPageIds) . ' )';
    }

    $sections = [
      'settings',
      'amount',
      'membership',
      'custom',
      'thankyou',
      'pcp',
      'widget',
      'premium',
    ];

    if (function_exists('tellafriend_civicrm_config')) {
      $sections[] = 'friend';
    }

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
   * Get or Set honor/on_behalf params for processing module_data or setting default values.
   *
   * @param array $params :
   * @param bool $setDefault : If yes then returns array to used for setting default value afterward
   * @param string $module : processing module_data for which module? e.g. soft_credit, on_behalf
   *
   * @return array|string
   */
  public static function formatModuleData($params, $setDefault, $module) {
    $tsLocale = CRM_Core_I18n::getLocale();
    $config = CRM_Core_Config::singleton();
    $json = $jsonDecode = NULL;
    $multilingual = CRM_Core_I18n::isMultilingual();

    $moduleDataFormat = [
      'soft_credit' => [
        1 => 'soft_credit_types',
        'multilingual' => [
          'honor_block_title',
          'honor_block_text',
        ],
      ],
      'on_behalf' => [
        1 => 'is_for_organization',
        'multilingual' => [
          'for_organization',
        ],
      ],
    ];

    //When we are fetching the honor params respecting both multi and mono lingual state
    //and setting it to default param of Contribution Page's Main and Setting form
    if ($setDefault) {
      $jsonDecode = json_decode($params);
      $jsonDecode = (array) $jsonDecode->$module;
      if ($multilingual && !empty($jsonDecode[$tsLocale])) {
        //multilingual state
        foreach ($jsonDecode[$tsLocale] as $column => $value) {
          $jsonDecode[$column] = $value;
        }
        unset($jsonDecode[$tsLocale]);
      }
      elseif (!empty($jsonDecode['default'])) {
        //monolingual state, or an undefined value in multilingual
        $jsonDecode += (array) $jsonDecode['default'];
        unset($jsonDecode['default']);
      }
      return $jsonDecode;
    }

    //check and handle multilingual honoree params
    if (!$multilingual) {
      //if in singlelingual state simply return the array format
      $json = [$module => NULL];
      foreach ($moduleDataFormat[$module] as $key => $attribute) {
        if ($key === 'multilingual') {
          $json[$module]['default'] = [];
          foreach ($attribute as $attr) {
            $json[$module]['default'][$attr] = $params[$attr];
          }
        }
        else {
          $json[$module][$attribute] = $params[$attribute];
        }
      }
      $json = json_encode($json);
    }
    else {
      //if in multilingual state then retrieve the module_data against this contribution and
      //merge with earlier module_data json data to current so not to lose earlier multilingual module_data information
      $json = [$module => NULL];
      foreach ($moduleDataFormat[$module] as $key => $attribute) {
        if ($key === 'multilingual') {
          $json[$module][$tsLocale] = [];
          foreach ($attribute as $attr) {
            $json[$module][$tsLocale][$attr] = $params[$attr];
          }
        }
        else {
          $json[$module][$attribute] = $params[$attribute];
        }
      }

      $ufJoinDAO = new CRM_Core_DAO_UFJoin();
      $ufJoinDAO->module = $module;
      $ufJoinDAO->entity_id = $params['id'];
      $ufJoinDAO->find(TRUE);
      $jsonData = json_decode($ufJoinDAO->module_data ?? '');
      if ($jsonData) {
        $json[$module] = array_merge((array) $jsonData->$module, $json[$module]);
      }
      $json = json_encode($json);
    }
    return $json;
  }

  /**
   * Generate html for pdf in confirmation receipt email  attachment.
   * @param int $contributionId
   *   Contribution Page Id.
   * @param int $userID
   *   Contact id for contributor.
   * @return array
   */
  public static function addInvoicePdfToEmail($contributionId, $userID) {
    $contributionID = [$contributionId];
    $contactId = [$userID];
    $pdfParams = [
      'output' => 'pdf_invoice',
      'forPage' => 'confirmpage',
    ];
    $pdfHtml = CRM_Contribute_Form_Task_Invoice::printPDF($contributionID, $pdfParams, $contactId);
    return $pdfHtml;
  }

  /**
   * Helper to determine if the page supports separate membership payments.
   *
   * @param int $id form id
   *
   * @return bool
   *   isSeparateMembershipPayment
   * @deprecated
   */
  public static function getIsMembershipPayment($id) {
    CRM_Core_Error::deprecatedFunctionWarning('api');
    $membershipBlocks = civicrm_api3('membership_block', 'get', [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $id,
      'sequential' => TRUE,
    ]);
    if (!$membershipBlocks['count']) {
      return FALSE;
    }
    return $membershipBlocks['values'][0]['is_separate_payment'];
  }

}
