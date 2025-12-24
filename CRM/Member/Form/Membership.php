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

use Civi\Api4\ContributionRecur;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for offline membership form.
 */
class CRM_Member_Form_Membership extends CRM_Member_Form {

  /**
   * If this is set (to 'test' or 'live') then the payment processor will be shown on the form to take a payment.
   *
   * @var string|null
   */
  public $_mode;

  protected $_memTypeSelected;

  /**
   * Contact ID of the member.
   *
   * @var int
   */
  public $_contactID = NULL;

  /**
   * Set entity fields to be assigned to the form.
   */
  protected function setEntityFields() {
    $this->entityFields = [
      'join_date' => [
        'name' => 'join_date',
        'description' => ts('Member Since'),
      ],
      'start_date' => [
        'name' => 'start_date',
        'description' => ts('Start Date'),
      ],
      'end_date' => [
        'name' => 'end_date',
        'description' => ts('End Date'),
      ],
    ];
  }

  /**
   * Set the delete message.
   *
   * We do this from the constructor in order to do a translation.
   */
  public function setDeleteMessage() {
    $this->deleteMessage = '<span class="font-red bold">'
      . ts('WARNING: Deleting this membership will also delete any related payment (contribution) records.')
      . ' '
      . ts('This action cannot be undone.')
      . '</span><p>'
      . ts('Consider modifying the membership status instead if you want to maintain an audit trail and avoid losing payment data. You can set the status to Cancelled by editing the membership and clicking the Status Override checkbox.')
      . '</p><p>'
      . ts("Click 'Delete' if you want to continue.") . '</p>';
  }

  /**
   * Overriding this entity trait function as not yet tested.
   *
   * We continue to rely on legacy handling.
   */
  public function addFormButtons() {}

  /**
   * Get selected membership type from the form values.
   *
   * @param array $priceSet
   * @param array $params
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getSelectedMemberships($priceSet, $params) {
    $memTypeSelected = [];
    $priceFieldIDS = self::getPriceFieldIDs($params, $priceSet);
    if (isset($params['membership_type_id']) && !empty($params['membership_type_id'][1])) {
      $memTypeSelected = [$params['membership_type_id'][1] => $params['membership_type_id'][1]];
    }
    else {
      foreach ($priceFieldIDS as $priceFieldId) {
        if ($id = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceFieldValue', $priceFieldId, 'membership_type_id')) {
          $memTypeSelected[$id] = $id;
        }
      }
    }
    return $memTypeSelected;
  }

  /**
   * Extract price set fields and values from $params.
   *
   * @param array $params
   * @param array $priceSet
   *
   * @return array
   */
  public static function getPriceFieldIDs($params, $priceSet) {
    $priceFieldIDS = [];
    if (isset($priceSet['fields']) && is_array($priceSet['fields'])) {
      foreach ($priceSet['fields'] as $fieldId => $field) {
        if (!empty($params['price_' . $fieldId])) {
          if (is_array($params['price_' . $fieldId])) {
            foreach ($params['price_' . $fieldId] as $priceFldVal => $isSet) {
              if ($isSet) {
                $priceFieldIDS[] = $priceFldVal;
              }
            }
          }
          elseif (!$field['is_enter_qty']) {
            $priceFieldIDS[] = $params['price_' . $fieldId];
          }
        }
      }
    }
    return $priceFieldIDS;
  }

  /**
   * Form preProcess function.
   *
   * @throws \CRM_Core_Exception
   */
  public function preProcess() {
    // This string makes up part of the class names, differentiating them (not sure why) from the membership fields.
    $this->assign('formClass', 'membership');
    parent::preProcess();

    // get price set id.
    $this->_priceSetId = $_GET['priceSetId'] ?? NULL;
    // This is assigned only for the purposes of displaying the price block
    // INSTEAD of the edit form. ie when the form is being overloaded
    // via ajax (which is a long-standing anti-pattern - in
    // some cases we have moved that overload behaviour
    // to a separate form but note that it is necessary to
    // add the fields to QuickForm when the form
    // has been submitted so they appear in submittedValues().
    $this->assign('priceSetId', $_GET['priceSetId'] ?? NULL);

    if ($this->_action & CRM_Core_Action::DELETE) {
      $contributionID = CRM_Member_BAO_MembershipPayment::getLatestContributionIDFromLineitemAndFallbackToMembershipPayment($this->_id);
      // check delete permission for contribution
      if ($this->_id && $contributionID && !CRM_Core_Permission::checkActionPermission('CiviContribute', $this->_action)) {
        CRM_Core_Error::statusBounce(ts("This Membership is linked to a contribution. You must have 'delete in CiviContribute' permission in order to delete this record."));
      }
    }
    $mems_by_org = [];
    if ($this->_action & CRM_Core_Action::ADD) {
      if ($this->_contactID) {
        //check whether contact has a current membership so we can alert user that they may want to do a renewal instead
        $contactMemberships = [];
        $memParams = ['contact_id' => $this->_contactID];
        CRM_Member_BAO_Membership::getValues($memParams, $contactMemberships, TRUE);
        $cMemTypes = [];
        foreach ($contactMemberships as $mem) {
          $cMemTypes[] = $mem['membership_type_id'];
        }
        if (count($cMemTypes) > 0) {
          foreach ($cMemTypes as $memTypeID) {
            $memberorgs[$memTypeID] = CRM_Member_BAO_MembershipType::getMembershipType($memTypeID)['member_of_contact_id'];
          }
          foreach ($contactMemberships as $mem) {
            $mem['member_of_contact_id'] = $memberorgs[$mem['membership_type_id']] ?? NULL;
            if (!empty($mem['membership_end_date'])) {
              $mem['membership_end_date'] = CRM_Utils_Date::customFormat($mem['membership_end_date']);
            }
            $mem['membership_type'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType',
              $mem['membership_type_id'],
              'name', 'id'
            );
            $mem['membership_status'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipStatus',
              $mem['status_id'],
              'label', 'id'
            );
            $mem['renewUrl'] = CRM_Utils_System::url('civicrm/contact/view/membership',
              "reset=1&action=renew&cid={$this->_contactID}&id={$mem['id']}&context=membership&selectedChild=member"
              . ($this->_mode ? '&mode=live' : '')
            );
            $mem['membershipTab'] = CRM_Utils_System::url('civicrm/contact/view',
              "reset=1&force=1&cid={$this->_contactID}&selectedChild=member"
            );
            $mems_by_org[$mem['member_of_contact_id']] = $mem;
          }
        }
      }
      else {
        // In standalone mode we don't have a contact id yet so lookup will be done client-side with this script:
        $resources = CRM_Core_Resources::singleton();
        $resources->addScriptFile('civicrm', 'templates/CRM/Member/Form/MembershipStandalone.js');
        $passthru = [
          'typeorgs' => CRM_Member_BAO_MembershipType::getMembershipTypeOrganization(),
          'memtypes' => CRM_Member_BAO_Membership::buildOptions('membership_type_id'),
          'statuses' => CRM_Member_BAO_Membership::buildOptions('status_id'),
        ];
        $resources->addSetting(['existingMems' => $passthru]);
      }
    }
    $this->assign('existingContactMemberships', $mems_by_org);

    if (!$this->_memType) {
      $params = CRM_Utils_Request::exportValues();
      if (!empty($params['membership_type_id'][1])) {
        $this->_memType = $params['membership_type_id'][1];
      }
    }

    $this->assign('customDataType', 'Membership');
    $this->assign('customDataSubType', $this->getMembershipValue('membership_type_id'));

    $this->setPageTitle(ts('Membership'));
  }

  /**
   * Set default values for the form.
   */
  public function setDefaultValues() {

    if ($this->_priceSetId) {
      return CRM_Price_BAO_PriceSet::setDefaultPriceSet($this, $defaults);
    }

    $defaults = parent::setDefaultValues();

    //setting default join date and receive date
    if ($this->_action == CRM_Core_Action::ADD) {
      $defaults['receive_date'] = CRM_Utils_Time::date('Y-m-d H:i:s');
    }

    $defaults['num_terms'] = 1;

    if (!empty($defaults['id'])) {
      $contributionId = CRM_Core_DAO::singleValueQuery("
SELECT contribution_id
FROM civicrm_membership_payment
WHERE membership_id = $this->_id
ORDER BY contribution_id
DESC limit 1");

      if ($contributionId) {
        $defaults['record_contribution'] = $contributionId;
      }
    }
    else {
      if ($this->_contactID) {
        $defaults['contact_id'] = $this->_contactID;
      }
    }

    //set Soft Credit Type to Gift by default
    $scTypes = CRM_Core_OptionGroup::values('soft_credit_type');
    $defaults['soft_credit_type_id'] = CRM_Utils_Array::value(ts('Gift'), array_flip($scTypes));

    //CRM-13420
    if (empty($defaults['payment_instrument_id'])) {
      $defaults['payment_instrument_id'] = key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
    }

    // User must explicitly choose to send a receipt in both add and update mode.
    $defaults['send_receipt'] = 0;

    if ($this->_action & CRM_Core_Action::UPDATE) {
      // in this mode by default uncheck this checkbox
      unset($defaults['record_contribution']);
    }

    $subscriptionCancelled = FALSE;
    if (!empty($defaults['id'])) {
      $subscriptionCancelled = CRM_Member_BAO_Membership::isSubscriptionCancelled((int) $this->_id);
    }

    $alreadyAutoRenew = FALSE;
    if (!empty($defaults['contribution_recur_id']) && !$subscriptionCancelled) {
      $defaults['auto_renew'] = 1;
      $alreadyAutoRenew = TRUE;
    }
    $this->assign('alreadyAutoRenew', $alreadyAutoRenew);

    $this->assign('member_is_test', $defaults['member_is_test'] ?? NULL);
    $this->assign('membership_status_id', $defaults['status_id'] ?? NULL);
    $this->assign('is_pay_later', !empty($defaults['is_pay_later']));

    if ($this->_mode) {
      $defaults = $this->getBillingDefaults($defaults);
    }

    //setting default join date if there is no join date
    if (empty($defaults['join_date'])) {
      $defaults['join_date'] = CRM_Utils_Time::date('Y-m-d');
    }
    $this->assign('endDate', $defaults['membership_end_date'] ?? NULL);

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {

    $this->buildQuickEntityForm();
    $this->assign('currency_symbol', CRM_Core_BAO_Country::defaultCurrencySymbol());
    $isUpdateToExistingRecurringMembership = $this->isUpdateToExistingRecurringMembership();
    // build price set form.
    $buildPriceSet = FALSE;
    if ($this->isAjaxOverLoadMode() || !empty($_POST['price_set_id'])) {
      if (!empty($_POST['price_set_id'])) {
        $buildPriceSet = TRUE;
      }
      $getOnlyPriceSetElements = TRUE;
      if (!$this->_priceSetId) {
        $this->_priceSetId = $_POST['price_set_id'];
        $getOnlyPriceSetElements = FALSE;
      }

      $this->buildMembershipPriceSet();

      $optionsMembershipTypes = [];
      foreach ($this->getPriceFieldMetaData() as $pField) {
        if (empty($pField['options'])) {
          continue;
        }
        foreach ($pField['options'] as $opId => $opValues) {
          $optionsMembershipTypes[$opId] = $opValues['membership_type_id'] ?: 0;
        }
      }

      $this->assign('autoRenewOption', CRM_Price_BAO_PriceSet::checkAutoRenewForPriceSet($this->_priceSetId));

      $this->assign('optionsMembershipTypes', $optionsMembershipTypes);
      $this->assign('contributionType', $this->_priceSet['financial_type_id'] ?? NULL);

      // get only price set form elements.
      if ($getOnlyPriceSetElements) {
        return;
      }
    }

    // use to build form during form rule.
    $this->assign('buildPriceSet', $buildPriceSet);

    if ($this->_action & CRM_Core_Action::ADD) {
      $buildPriceSet = FALSE;
      $priceSets = CRM_Price_BAO_PriceSet::getAssoc(FALSE, 'CiviMember');
      if (!empty($priceSets)) {
        $buildPriceSet = TRUE;
      }

      if ($buildPriceSet) {
        $this->add('select', 'price_set_id', ts('Choose price set'),
          [
            '' => ts('Choose price set'),
          ] + $priceSets,
          NULL, ['onchange' => "buildAmount( this.value );"]
        );
      }
    }
    $this->assign('hasPriceSets', $buildPriceSet ?? NULL);

    if ($this->_action & CRM_Core_Action::DELETE) {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Delete'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
      return;
    }

    $contactField = $this->addEntityRef('contact_id', ts('Member'), ['create' => TRUE, 'api' => ['extra' => ['email']]], TRUE);
    if ($this->_context !== 'standalone') {
      $contactField->freeze();
    }

    $selOrgMemType[0][0] = $selMemTypeOrg[0] = ts('- select -');

    // Throw status bounce when no Membership type or priceset is present
    if (empty($this->allMembershipTypeDetails) && empty($priceSets)
    ) {
      CRM_Core_Error::statusBounce(ts("You either do not have all the permissions needed for this page, or the membership types haven't been fully configured."));
    }
    // retrieve all memberships
    $allMembershipInfo = [];
    foreach ($this->allMembershipTypeDetails as $key => $values) {

      $memberOfContactId = $values['member_of_contact_id'] ?? NULL;
      if (empty($selMemTypeOrg[$memberOfContactId])) {
        $selMemTypeOrg[$memberOfContactId] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $memberOfContactId,
          'display_name',
          'id'
        );

        $selOrgMemType[$memberOfContactId][0] = ts('- select -');
      }
      if (empty($selOrgMemType[$memberOfContactId][$key])) {
        $selOrgMemType[$memberOfContactId][$key] = $values['name'] ?? NULL;
      }

      $totalAmount = $values['minimum_fee'] ?? 0;
      // build membership info array, which is used when membership type is selected to:
      // - set the payment information block
      // - set the max related block
      $allMembershipInfo[$key] = [
        'financial_type_id' => $values['financial_type_id'] ?? NULL,
        'total_amount' => CRM_Utils_Money::formatLocaleNumericRoundedForDefaultCurrency($totalAmount),
        'total_amount_numeric' => $totalAmount,
        'auto_renew' => $values['auto_renew'] ?? NULL,
        'tax_rate' => $values['tax_rate'],
        'has_related' => isset($values['relationship_type_id']),
        'max_related' => $values['max_related'] ?? NULL,
      ];
    }

    $this->assign('allMembershipInfo', json_encode($allMembershipInfo));

    // show organization by default, if only one organization in
    // the list
    if (count($selMemTypeOrg) == 2) {
      unset($selMemTypeOrg[0], $selOrgMemType[0][0]);
    }
    //sort membership organization and type, CRM-6099
    natcasesort($selMemTypeOrg);
    foreach ($selOrgMemType as $index => $orgMembershipType) {
      natcasesort($orgMembershipType);
      $selOrgMemType[$index] = $orgMembershipType;
    }

    $memTypeJs = [
      'onChange' => "buildMaxRelated(this.value,true); CRM.buildCustomData('Membership', this.value);",
    ];

    if (!empty($this->_recurPaymentProcessors)) {
      $memTypeJs['onChange'] = "" . $memTypeJs['onChange'] . " buildAutoRenew(this.value, null, '{$this->_mode}');";
    }

    $this->add('text', 'max_related', ts('Max related'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_Membership', 'max_related')
    );

    $sel = &$this->addElement('hierselect',
      'membership_type_id',
      ts('Membership Organization and Type'),
      $memTypeJs
    );

    $sel->setOptions([$selMemTypeOrg, $selOrgMemType]);

    if ($this->_action & CRM_Core_Action::ADD) {
      $this->add('number', 'num_terms', ts('Number of Terms'), ['size' => 6]);
    }

    $this->add('text', 'source', ts('Membership Source'),
      CRM_Core_DAO::getAttribute('CRM_Member_DAO_Membership', 'source')
    );

    //CRM-7362 --add campaigns.
    $campaignId = NULL;
    if ($this->_id) {
      $campaignId = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->_id, 'campaign_id');
    }
    CRM_Campaign_BAO_Campaign::addCampaign($this, $campaignId);

    if (!$this->_mode) {
      $this->add('select', 'status_id', ts('Membership Status'),
        ['' => ts('- select -')] + CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label')
      );

      $statusOverride = $this->addElement('select', 'is_override', ts('Status Override?'),
        CRM_Member_StatusOverrideTypes::getSelectOptions()
      );

      $this->add('datepicker', 'status_override_end_date', ts('Status Override End Date'), '', FALSE, ['minDate' => CRM_Utils_Time::date('Y-m-d'), 'time' => FALSE]);

      $this->addElement('checkbox', 'record_contribution', ts('Record Membership Payment?'));

      $this->add('text', 'total_amount', ts('Amount'));
      $this->addRule('total_amount', ts('Please enter a valid amount.'), 'money');

      $this->add('datepicker', 'receive_date', ts('Contribution Date'), [], FALSE, ['time' => TRUE]);

      $this->add('select', 'payment_instrument_id',
        ts('Payment Method'),
        ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::paymentInstrument(),
        FALSE, ['onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);"]
      );
      $this->add('text', 'trxn_id', ts('Transaction ID'));
      $this->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
        'objectExists', [
          'CRM_Contribute_DAO_Contribution',
          $this->_id,
          'trxn_id',
        ]
      );

      $this->add('select', 'contribution_status_id',
        ts('Payment Status'), CRM_Contribute_BAO_Contribution_Utils::getPendingAndCompleteStatuses()
      );
      $this->add('text', 'check_number', ts('Check Number'),
        CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'check_number')
      );
    }
    else {
      //add field for amount to allow an amount to be entered that differs from minimum
      $this->add('text', 'total_amount', ts('Amount'));
    }
    $this->add('select', 'financial_type_id',
      ts('Financial Type'),
      ['' => ts('- select -')] + CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes, $this->_action)
    );

    $this->addElement('checkbox', 'is_different_contribution_contact', ts('Record Payment from a Different Contact?'));

    $this->addSelect('soft_credit_type_id', ['entity' => 'contribution_soft']);
    $this->addEntityRef('soft_credit_contact_id', ts('Payment From'), ['create' => TRUE]);

    $this->addElement('checkbox',
      'send_receipt',
      ts('Send Confirmation and Receipt?'), NULL,
      ['onclick' => "showEmailOptions()"]
    );

    $this->add('select', 'from_email_address', ts('Receipt From'), $this->_fromEmails);

    $this->add('textarea', 'receipt_text', ts('Receipt Message'));

    // Retrieve the name and email of the contact - this will be the TO for receipt email
    if ($this->_contactID) {
      [$this->_memberDisplayName, $this->_memberEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($this->_contactID);
    }
    $this->assign('emailExists', $this->_memberEmail);
    $this->assign('displayName', $this->_memberDisplayName);

    if ($isUpdateToExistingRecurringMembership && CRM_Member_BAO_Membership::isCancelSubscriptionSupported($this->_id)) {
      $this->assign('cancelAutoRenew',
        CRM_Utils_System::url('civicrm/contribute/unsubscribe', "reset=1&mid={$this->_id}")
      );
    }

    $this->assign('isRecur', $isUpdateToExistingRecurringMembership);

    $this->addFormRule(['CRM_Member_Form_Membership', 'formRule'], $this);
    $mailingInfo = Civi::settings()->get('mailing_backend');
    $this->assign('isEmailEnabledForSite', ($mailingInfo['outBound_option'] != 2));

    parent::buildQuickForm();
  }

  /**
   * Build the price set form.
   *
   * @return void
   *
   * @deprecated this should be updated to align with the other forms that use getOrder()
   */
  private function buildMembershipPriceSet() {
    $form = $this;

    $this->_priceSet = $this->getOrder()->getPriceSetMetadata();
    $validPriceFieldIds = array_keys($this->getPriceFieldMetaData());

    // Mark which field should have the auto-renew checkbox, if any. CRM-18305
    // This is probably never set & relates to another form from previously shared code.
    if (!empty($form->_membershipTypeValues) && is_array($form->_membershipTypeValues)) {
      $autoRenewMembershipTypes = [];
      foreach ($form->_membershipTypeValues as $membershipTypeValue) {
        if ($membershipTypeValue['auto_renew']) {
          $autoRenewMembershipTypes[] = $membershipTypeValue['id'];
        }
      }
      foreach ($form->getPriceFieldMetaData() as $field) {
        if (array_key_exists('options', $field) && is_array($field['options'])) {
          foreach ($field['options'] as $option) {
            if (!empty($option['membership_type_id'])) {
              if (in_array($option['membership_type_id'], $autoRenewMembershipTypes)) {
                $form->_priceSet['auto_renew_membership_field'] = $field['id'];
                // Only one field can offer auto_renew memberships, so break here.
                // May not relate to this form? From previously shared code.
                break;
              }
            }
          }
        }
      }
    }
    $form->assign('priceSet', $form->_priceSet);

    $checklifetime = FALSE;
    foreach ($this->getPriceFieldMetaData() as $id => $field) {
      $options = $field['options'] ?? NULL;
      if (!is_array($options) || !in_array($id, $validPriceFieldIds)) {
        continue;
      }
      if (!empty($options)) {
        CRM_Price_BAO_PriceField::addQuickFormElement($form,
          'price_' . $field['id'],
          $field['id'],
          FALSE,
          $field['is_required'] ?? FALSE,
          NULL,
          $options
        );
      }
    }
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   *
   * @param array $files
   * @param CRM_Member_Form_Membership $self
   *
   * @return bool|array
   *   mixed true or array of errors
   *
   * @throws \CRM_Core_Exception
   * @throws CRM_Core_Exception
   */
  public static function formRule($params, $files, $self) {
    $errors = [];

    $priceSetId = $self->getPriceSetID();
    $priceSetDetails = $self->getPriceSetDetails($params);

    $selectedMemberships = self::getSelectedMemberships($priceSetDetails[$priceSetId], $params);

    if (!empty($params['price_set_id'])) {
      CRM_Price_BAO_PriceField::priceSetValidation($priceSetId, $params, $errors);

      $priceFieldIDS = self::getPriceFieldIDs($params, $priceSetDetails[$priceSetId]);

      if (!empty($priceFieldIDS)) {
        $ids = implode(',', $priceFieldIDS);

        $count = CRM_Price_BAO_PriceSet::getMembershipCount($ids);
        foreach ($count as $occurrence) {
          if ($occurrence > 1) {
            $errors['_qf_default'] = ts('Select at most one option associated with the same membership type.');
          }
        }
      }
      // Return error if empty $self->_memTypeSelected
      if (empty($errors) && empty($selectedMemberships)) {
        $errors['_qf_default'] = ts('Select at least one membership option.');
      }
      if (!$self->_mode && empty($params['record_contribution'])) {
        $errors['record_contribution'] = ts('Record Membership Payment is required when you use a price set.');
      }
    }
    else {
      if (empty($params['membership_type_id'][1])) {
        $errors['membership_type_id'] = ts('Please select a membership type.');
      }
      $numterms = $params['num_terms'] ?? NULL;
      if ($numterms && intval($numterms) != $numterms) {
        $errors['num_terms'] = ts('Please enter an integer for the number of terms.');
      }

      if (($self->_mode || isset($params['record_contribution'])) && empty($params['financial_type_id'])) {
        $errors['financial_type_id'] = ts('Please enter the financial Type.');
      }
    }

    if (!empty($errors) && (count($selectedMemberships) > 1)) {
      $memberOfContacts = CRM_Member_BAO_MembershipType::getMemberOfContactByMemTypes($selectedMemberships);
      $duplicateMemberOfContacts = array_count_values($memberOfContacts);
      foreach ($duplicateMemberOfContacts as $countDuplicate) {
        if ($countDuplicate > 1) {
          $errors['_qf_default'] = ts('Please do not select more than one membership associated with the same organization.');
        }
      }
    }

    if (!empty($errors)) {
      return $errors;
    }

    if (!empty($params['record_contribution']) && empty($params['payment_instrument_id'])) {
      $errors['payment_instrument_id'] = ts('Payment Method is a required field.');
    }

    if (!empty($params['is_different_contribution_contact'])) {
      if (empty($params['soft_credit_type_id'])) {
        $errors['soft_credit_type_id'] = ts('Please Select a Soft Credit Type');
      }
      if (empty($params['soft_credit_contact_id'])) {
        $errors['soft_credit_contact_id'] = ts('Please select a contact');
      }
    }

    if (!empty($params['payment_processor_id'])) {
      // validate payment instrument (e.g. credit card number)
      CRM_Core_Payment_Form::validatePaymentInstrument($params['payment_processor_id'], $params, $errors, NULL);
    }

    if (!empty($params['join_date'])) {
      $joinDate = CRM_Utils_Date::processDate($params['join_date']);

      foreach ($selectedMemberships as $memType) {
        $startDate = NULL;
        if (!empty($params['start_date'])) {
          $startDate = CRM_Utils_Date::processDate($params['start_date']);
        }

        // if end date is set, ensure that start date is also set
        // and that end date is later than start date
        $endDate = NULL;
        if (!empty($params['end_date'])) {
          $endDate = CRM_Utils_Date::processDate($params['end_date']);
        }

        $membershipDetails = CRM_Member_BAO_MembershipType::getMembershipType($memType);
        if ($startDate && ($membershipDetails['period_type'] ?? NULL) === 'rolling') {
          if ($startDate < $joinDate) {
            $errors['start_date'] = ts('Start date must be the same or later than Member since.');
          }
        }

        if ($endDate) {
          if ($membershipDetails['duration_unit'] === 'lifetime') {
            // Check if status is NOT cancelled or similar. For lifetime memberships, there is no automated
            // process to update status based on end-date. The user must change the status now.
            $result = civicrm_api3('MembershipStatus', 'get', [
              'sequential' => 1,
              'is_current_member' => 0,
            ]);
            $tmp_statuses = $result['values'];
            $status_ids = [];
            foreach ($tmp_statuses as $cur_stat) {
              $status_ids[] = $cur_stat['id'];
            }

            if (empty($params['status_id']) || in_array($params['status_id'], $status_ids) == FALSE) {
              $errors['status_id'] = ts('A current lifetime membership cannot have an end date. You can either remove the end date or change the status to a non-current status like Cancelled, Expired, or Deceased.');
            }

            if (!empty($params['is_override']) && !CRM_Member_StatusOverrideTypes::isPermanent($params['is_override'])) {
              $errors['is_override'] = ts('Because you set an End Date for a lifetime membership, This must be set to "Override Permanently"');
            }
          }
          else {
            if (!$startDate) {
              $errors['start_date'] = ts('Start date must be set if end date is set.');
            }
            if ($endDate < $startDate) {
              $errors['end_date'] = ts('End date must be the same or later than start date.');
            }
          }
        }

        // Default values for start and end dates if not supplied on the form.
        $defaultDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType($memType,
          $joinDate,
          $startDate,
          $endDate
        );

        if (!$startDate) {
          $startDate = $defaultDates['start_date'] ?? NULL;
        }
        if (!$endDate) {
          $endDate = $defaultDates['end_date'] ?? NULL;
        }

        //CRM-3724, check for availability of valid membership status.
        if ((empty($params['is_override']) || CRM_Member_StatusOverrideTypes::isNo($params['is_override'])) && !isset($errors['_qf_default'])) {
          $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate($startDate,
            $endDate,
            $joinDate,
            'now',
            TRUE,
            $memType,
            $params
          );
          if (empty($calcStatus)) {
            $url = CRM_Utils_System::url('civicrm/admin/member/membershipStatus', 'reset=1&action=browse');
            $errors['_qf_default'] = ts('There is no valid Membership Status available for selected membership dates.');
            $status = ts('Oops, it looks like there is no valid membership status available for the given membership dates. You can <a href="%1">Configure Membership Status Rules</a>.', [1 => $url]);
            if (!$self->_mode) {
              $status .= ' ' . ts('OR You can sign up by setting Status Override? to something other than "NO".');
            }
            CRM_Core_Session::setStatus($status, ts('Membership Status Error'), 'error');
          }
        }
      }
    }
    else {
      $errors['join_date'] = ts('Please enter the Member Since.');
    }

    if (!empty($params['is_override']) && CRM_Member_StatusOverrideTypes::isOverridden($params['is_override']) && empty($params['status_id'])) {
      $errors['status_id'] = ts('Please enter the Membership status.');
    }

    if (!empty($params['is_override']) && CRM_Member_StatusOverrideTypes::isUntilDate($params['is_override'])) {
      if (empty($params['status_override_end_date'])) {
        $errors['status_override_end_date'] = ts('Please enter the Membership override end date.');
      }
    }

    //total amount condition arise when membership type having no
    //minimum fee
    if (isset($params['record_contribution'])) {
      if (CRM_Utils_System::isNull($params['total_amount'])) {
        $errors['total_amount'] = ts('Please enter the contribution.');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Get price field metadata.
   *
   * The returned value is an array of arrays where each array
   * is an id-keyed price field and an 'options' key has been added to that
   * array for any options.
   *
   * @api  - this is not yet being used by the form - only by a test but
   * follows standard methodology so should stay the same.
   *
   * @return array
   */
  public function getPriceFieldMetaData(): array {
    $this->_priceSet['fields'] = $this->getOrder()->getPriceFieldsMetadata();
    return $this->_priceSet['fields'];
  }

  /**
   * @return \CRM_Financial_BAO_Order
   * @throws \CRM_Core_Exception
   */
  protected function getOrder(): CRM_Financial_BAO_Order {
    if (!$this->order) {
      $this->initializeOrder();
    }
    return $this->order;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function initializeOrder(): void {
    $this->order = new CRM_Financial_BAO_Order();
    $this->order->setPriceSetID($this->getPriceSetID());
    $this->order->setForm($this);
    $this->order->setPriceSelectionFromUnfilteredInput($this->getSubmittedValues());
  }

  /**
   * Process the form submission.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_Member_BAO_Membership::del($this->_id);
      return;
    }
    // get the submitted form values.
    $this->_params = $this->controller->exportValues($this->_name);
    $this->prepareStatusOverrideValues();

    $this->submit();

    $this->setUserContext();
  }

  /**
   * Prepares the values related to status override.
   */
  private function prepareStatusOverrideValues() {
    $this->setOverrideDateValue();
    $this->convertIsOverrideValue();
  }

  /**
   * Sets status override end date to empty value if
   * the selected override option is not 'until date'.
   */
  private function setOverrideDateValue() {
    if (!CRM_Member_StatusOverrideTypes::isUntilDate($this->_params['is_override'] ?? NULL)) {
      $this->_params['status_override_end_date'] = '';
    }
  }

  /**
   * Convert the value of selected (status override?)
   * option to TRUE if it indicate an overridden status
   * or FALSE otherwise.
   */
  private function convertIsOverrideValue() {
    $this->_params['is_override'] = CRM_Member_StatusOverrideTypes::isOverridden($this->_params['is_override'] ?? CRM_Member_StatusOverrideTypes::NO);
  }

  /**
   * Send email receipt.
   *
   * @param array $formValues
   *
   * @throws \CRM_Core_Exception
   *
   * @deprecated
   *   This function was shared with Batch_Entry which had limited overlap
   *   & needs rationalising.
   *
   */
  protected function emailReceipt($formValues) {
    // retrieve 'from email id' for acknowledgement
    $receiptFrom = $formValues['from_email_address'] ?? NULL;

    // @todo figure out how much of the stuff below is genuinely shared with the batch form & a logical shared place.
    // @todo - as of 5.74 module is noisy deprecated - can stop assigning around 5.80.
    $this->assign('module', 'Membership');

    if (!empty($formValues['is_renew'])) {
      $this->assign('receiptType', 'membership renewal');
    }
    else {
      $this->assign('receiptType', 'membership signup');
    }
    // @todo - as of 5.74 form values is noisy deprecated - can stop assigning around 5.80.
    $this->assign('formValues', $formValues);

    if ((empty($this->_contributorDisplayName) || empty($this->_contributorEmail))) {
      // in this case the form is being called statically from the batch editing screen
      // having one class in the form layer call another statically is not greate
      // & we should aim to move this function to the BAO layer in future.
      // however, we can assume that the contact_id passed in by the batch
      // function will be the recipient
      [$this->_contributorDisplayName, $this->_contributorEmail]
        = CRM_Contact_BAO_Contact_Location::getEmailDetails($formValues['contact_id']);
      if (empty($this->_receiptContactId)) {
        $this->_receiptContactId = $formValues['contact_id'];
      }
    }

    CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'workflow' => 'membership_offline_receipt',
        'from' => $receiptFrom,
        'toName' => $this->_contributorDisplayName,
        'toEmail' => $this->_contributorEmail,
        'PDFFilename' => ts('receipt') . '.pdf',
        'isEmailPdf' => Civi::settings()->get('invoice_is_email_pdf'),
        'isTest' => (bool) ($this->_action & CRM_Core_Action::PREVIEW),
        'modelProps' => [
          'userEnteredText' => $this->getSubmittedValue('receipt_text'),
          'contributionID' => $formValues['contribution_id'],
          'contactID' => $this->_receiptContactId,
          'membershipID' => $this->getMembershipID(),
        ],
      ]
    );
  }

  /**
   * Submit function.
   *
   * This is also accessed by unit tests.
   *
   * @throws \CRM_Core_Exception
   */
  public function submit(): void {
    $this->storeContactFields($this->_params);
    $this->beginPostProcess();

    $params = $softParams = [];

    $this->processBillingAddress($this->getContributionContactID(), (string) $this->_contributorEmail);
    $formValues = $this->_params;
    $formValues = $this->setPriceSetParameters($formValues);

    if ($this->_id) {
      $params['id'] = $this->_id;
    }

    // Set variables that we normally get from context.
    // In form mode these are set in preProcess.
    //TODO: set memberships, fixme
    $this->setContextVariables($formValues);

    $this->_memTypeSelected = self::getSelectedMemberships(
      $this->_priceSet,
      $formValues
    );
    $formValues['financial_type_id'] = $this->getFinancialTypeID();

    $isQuickConfig = $this->_priceSet['is_quick_config'];

    $lineItem = [$this->order->getPriceSetID() => $this->order->getLineItems()];

    $params['tax_amount'] = $this->order->getTotalTaxAmount();
    $params['total_amount'] = $this->order->getTotalAmount();
    $params['contact_id'] = $this->_contactID;

    $params = array_merge($params, $this->getFormMembershipParams());
    $membershipTypeValues = $this->getMembershipParameters();

    //CRM-13981, allow different person as a soft-contributor of chosen type
    if ($this->_contributorContactID != $this->_contactID) {
      $params['contribution_contact_id'] = $this->_contributorContactID;
      if (!empty($formValues['soft_credit_type_id'])) {
        $softParams['soft_credit_type_id'] = $formValues['soft_credit_type_id'];
        $softParams['contact_id'] = $this->_contactID;
      }
    }

    $pendingMembershipStatusId = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending');

    if (!empty($formValues['record_contribution'])) {
      $recordContribution = [
        'total_amount',
        'payment_instrument_id',
        'trxn_id',
        'contribution_status_id',
        'check_number',
        'receive_date',
        'card_type_id',
        'pan_truncation',
      ];

      foreach ($recordContribution as $f) {
        $params[$f] = $formValues[$f] ?? NULL;
      }
      $params['financial_type_id'] = $this->getFinancialTypeID();
      $params['campaign_id'] = $this->getSubmittedValue('campaign_id');

      $params['contribution_source'] = $this->getContributionSource();

      $completedContributionStatusId = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');
      if (($params['contribution_status_id'] ?? NULL) != $completedContributionStatusId) {
        if (empty($params['is_override'])) {
          $params['status_id'] = $pendingMembershipStatusId;
          $params['skipStatusCal'] = TRUE;
        }
        $params['is_pay_later'] = 1;
        $this->assign('is_pay_later', 1);
      }

      if ($this->getSubmittedValue('send_receipt')) {
        $params['receipt_date'] = $formValues['receive_date'] ?? NULL;
      }

    }

    // process line items, until no previous line items.
    if (!empty($lineItem)) {
      $params['lineItems'] = $lineItem;
      $params['processPriceSet'] = TRUE;
    }

    if ($this->_mode) {
      $params['total_amount'] = $this->order->getTotalAmount();
      $params['financial_type_id'] = $this->getFinancialTypeID();

      //get the payment processor id as per mode. Try removing in favour of beginPostProcess.
      $params['payment_processor_id'] = $formValues['payment_processor_id'] = $this->getPaymentProcessorID();
      $params['register_date'] = CRM_Utils_Time::date('YmdHis');

      // add all the additional payment params we need
      $formValues['amount'] = $this->order->getTotalAmount();
      $formValues['currencyID'] = $this->getCurrency();
      $formValues['description'] = ts("Contribution submitted by a staff person using member's credit card for signup");
      $formValues['invoiceID'] = $this->getInvoiceID();
      $formValues['financial_type_id'] = $this->getFinancialTypeID();

      // at this point we've created a contact and stored its address etc
      // all the payment processors expect the name and address to be in the
      // so we copy stuff over to first_name etc.
      $paymentParams = $formValues;
      $paymentParams['frequency_unit'] = $this->getFrequencyUnit();
      $paymentParams['frequency_interval'] = $this->getFrequencyInterval();

      $paymentParams['contactID'] = $this->_contributorContactID;
      //CRM-10377 if payment is by an alternate contact then we need to set that person
      // as the contact in the payment params
      if ($this->_contributorContactID != $this->_contactID) {
        if (!empty($formValues['soft_credit_type_id'])) {
          $softParams['contact_id'] = $params['contact_id'];
          $softParams['soft_credit_type_id'] = $formValues['soft_credit_type_id'];
        }
      }
      if ($this->getSubmittedValue('send_receipt')) {
        $paymentParams['email'] = $this->_contributorEmail;
      }

      // This is a candidate for shared beginPostProcess function.
      // @todo Do we need this now we have $this->formatParamsForPaymentProcessor() ?
      CRM_Core_Payment_Form::mapParams(NULL, $formValues, $paymentParams, TRUE);
      // CRM-7137 -for recurring membership,
      // we do need contribution and recurring records.
      $result = NULL;

      $this->_params = $formValues;
      $contributionAddressID = CRM_Contribute_BAO_Contribution::createAddress($this->getSubmittedValues());
      $contribution = civicrm_api3('Order', 'create',
        [
          'contact_id' => $this->_contributorContactID,
          'address_id' => $contributionAddressID,
          'line_items' => $this->getLineItemForOrderApi(),
          'is_test' => $this->isTest(),
          'campaign_id' => $this->getSubmittedValue('campaign_id'),
          'source' => $paymentParams['source'] ?? $paymentParams['description'] ?? NULL,
          'payment_instrument_id' => $this->getPaymentInstrumentID(),
          'financial_type_id' => $this->getFinancialTypeID(),
          'receive_date' => $this->getReceiveDate(),
          'tax_amount' => $this->order->getTotalTaxAmount(),
          'total_amount' => $this->order->getTotalAmount(),
          'invoice_id' => $this->getInvoiceID(),
          'currency' => $this->getCurrency(),
          'receipt_date' => $this->getSubmittedValue('send_receipt') ? date('YmdHis') : NULL,
          'contribution_recur_id' => $this->getContributionRecurID(),
          'skipCleanMoney' => TRUE,
        ]
      );
      $this->ids['Contribution'] = $contribution['id'];
      $this->setMembershipIDsFromOrder($contribution);

      //create new soft-credit record, CRM-13981
      if ($softParams) {
        $softParams['contribution_id'] = $contribution['id'];
        $softParams['currency'] = $this->getCurrency();
        $softParams['amount'] = $this->order->getTotalAmount();
        CRM_Contribute_BAO_ContributionSoft::add($softParams);
      }

      $paymentParams['contactID'] = $this->_contactID;
      $paymentParams['contributionID'] = $contribution['id'];

      $paymentParams['contributionRecurID'] = $this->getContributionRecurID();
      $paymentParams['is_recur'] = $this->isCreateRecurringContribution();
      $params['contribution_id'] = $paymentParams['contributionID'];
      $params['contribution_recur_id'] = $this->getContributionRecurID();

      $paymentStatus = NULL;

      if ($this->order->getTotalAmount() > 0.0) {
        $payment = $this->_paymentProcessor['object'];
        $payment->setBackOffice(TRUE);
        try {
          $result = $payment->doPayment($paymentParams);
          $formValues = array_merge($formValues, $result);
          $paymentStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $formValues['payment_status_id']);
          if (!empty($params['contribution_id']) && $paymentStatus === 'Completed') {
            civicrm_api3('Payment', 'create', [
              'fee_amount' => $result['fee_amount'] ?? 0,
              'total_amount' => $this->order->getTotalAmount(),
              'payment_instrument_id' => $this->getPaymentInstrumentID(),
              'trxn_id' => $result['trxn_id'],
              'contribution_id' => $params['contribution_id'],
              'is_send_contribution_notification' => FALSE,
              'card_type_id' => $this->getCardTypeID(),
              'pan_truncation' => $this->getPanTruncation(),
            ]);
          }
        }
        catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
          if (!empty($paymentParams['contributionID'])) {
            CRM_Contribute_BAO_Contribution::failPayment($paymentParams['contributionID'], $this->_contactID,
              $e->getMessage());
          }
          if ($this->getContributionRecurID()) {
            CRM_Contribute_BAO_ContributionRecur::deleteRecurContribution($this->getContributionRecurID());
          }

          CRM_Core_Session::singleton()->setStatus($e->getMessage());
          CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view/membership',
            "reset=1&action=add&cid={$this->_contactID}&context=membership&mode={$this->_mode}"
          ));

        }
      }

      if ($paymentStatus !== 'Completed') {
        $params['status_id'] = $pendingMembershipStatusId;
        $params['skipStatusCal'] = TRUE;
        // as membership is pending set dates to null.
        foreach ($this->_memTypeSelected as $memType) {
          $membershipTypeValues[$memType]['joinDate'] = NULL;
          $membershipTypeValues[$memType]['startDate'] = NULL;
          $membershipTypeValues[$memType]['endDate'] = NULL;
        }
      }
      $now = CRM_Utils_Time::date('YmdHis');
      $params['receive_date'] = CRM_Utils_Time::date('Y-m-d H:i:s');
      $params['invoice_id'] = $this->getInvoiceID();
      $params['contribution_source'] = $this->getContributionSource();
      $params['source'] = $formValues['source'] ?: $params['contribution_source'];
      $params['trxn_id'] = $result['trxn_id'] ?? NULL;
      $params['is_test'] = $this->isTest();
      $params['receipt_date'] = NULL;
      if ($this->getSubmittedValue('send_receipt') && $paymentStatus === 'Completed') {
        // @todo this should be updated by the send function once sent rather than
        // set here.
        $params['receipt_date'] = $now;
      }

      $this->set('params', $formValues);
      $this->assign('trxn_id', $result['trxn_id'] ?? NULL);
      $this->assign('receive_date',
        CRM_Utils_Date::mysqlToIso($params['receive_date'])
      );

      // required for creating membership for related contacts
      $params['action'] = $this->_action;

      // create membership record
      foreach ($this->_memTypeSelected as $memType) {
        $membershipParams = array_merge($membershipTypeValues[$memType], $params);
        if (isset($result['fee_amount'])) {
          $membershipParams['fee_amount'] = $result['fee_amount'];
        }
        // This is required to trigger the recording of the membership contribution in the
        // CRM_Member_BAO_Membership::Create function.
        // @todo stop setting this & 'teach' the create function to respond to something
        // appropriate as part of our 2-step always create the pending contribution & then finally add the payment
        // process -
        // @see http://wiki.civicrm.org/confluence/pages/viewpage.action?pageId=261062657#Payments&AccountsRoadmap-Movetowardsalwaysusinga2-steppaymentprocess
        $membershipParams['contribution_status_id'] = $result['payment_status_id'] ?? NULL;
        // The earlier process created the line items (although we want to get rid of the earlier one in favour
        // of a single path!
        unset($membershipParams['lineItems']);
        $membershipParams['payment_instrument_id'] = $this->getPaymentInstrumentID();
        $params['contribution'] = $membershipParams['contribution'] ?? NULL;
        unset($params['lineItems']);
      }

    }
    else {
      $params['action'] = $this->_action;

      foreach ($lineItem[$this->_priceSetId] as $id => $lineItemValues) {
        if (empty($lineItemValues['membership_type_id'])) {
          continue;
        }

        // @todo figure out why receive_date isn't being set right here.
        if (empty($params['receive_date'])) {
          $params['receive_date'] = CRM_Utils_Time::date('Y-m-d H:i:s');
        }
        $membershipParams = array_merge($params, $membershipTypeValues[$lineItemValues['membership_type_id']]);

        // If is_override is empty then status_id="" (because it's a hidden field). That will trigger a recalculation in CRM_Member_BAO_Membership::create
        //   unless is_override = TRUE or skipStatusCal = TRUE. But skipStatusCal also skips date calculations.
        // If we are recording a contribution we *do* want to trigger a recalculation of membership status so it can go from Pending->New/Current
        // So here we check if status_id is empty, default (ie. status in database) is pending and that we are not recording a contribution -
        //   If all those are true then we skip the status calculation and explicitly set the pending status (to avoid a DB constraint status_id=0).
        // Test cover in `CRM_Member_Form_MembershipTest::testOverrideSubmit()`.
        $isPaymentPending = FALSE;
        if ($this->getMembershipID()) {
          $contributionId = CRM_Member_BAO_MembershipPayment::getLatestContributionIDFromLineitemAndFallbackToMembershipPayment($this->getMembershipID());
          if ($contributionId) {
            $isPaymentPending = \Civi\Api4\Contribution::get(FALSE)
              ->addSelect('contribution_status_id:name')
              ->addWhere('id', '=', $contributionId)
              ->execute()
              ->first()['contribution_status_id:name'] === 'Pending';
          }
        }
        if (empty($membershipParams['status_id'])
          && !empty($this->_defaultValues['status_id'])
          && !$this->getSubmittedValue('record_contribution')
          && (int) $this->_defaultValues['status_id'] === $pendingMembershipStatusId
          && $isPaymentPending
        ) {
          $membershipParams['status_id'] = $this->_defaultValues['status_id'];
        }

        if (!empty($softParams)) {
          $params['soft_credit'] = $softParams;
        }
        unset($membershipParams['contribution_status_id']);
        $membershipParams['skipLineItem'] = TRUE;
        unset($membershipParams['lineItems']);
        $this->setMembership((array) CRM_Member_BAO_Membership::create($membershipParams));
        $lineItem[$this->_priceSetId][$id]['entity_id'] = $this->membership['id'];
        $lineItem[$this->_priceSetId][$id]['entity_table'] = 'civicrm_membership';

      }
      $params['lineItems'] = $lineItem;
      if (!empty($formValues['record_contribution'])) {
        CRM_Member_BAO_Membership::recordMembershipContribution($params);
      }
    }

    $this->updateContributionOnMembershipTypeChange($params);

    if (($this->_action & CRM_Core_Action::UPDATE)) {
      $this->addStatusMessage($this->getStatusMessageForUpdate());
    }
    elseif (($this->_action & CRM_Core_Action::ADD)) {
      $this->addStatusMessage($this->getStatusMessageForCreate());
    }

    // This would always be true as we always add price set id into both
    // quick config & non quick config price sets.
    if (!empty($lineItem[$this->_priceSetId])) {
      foreach ($lineItem[$this->_priceSetId] as & $priceFieldOp) {
        if (!empty($priceFieldOp['membership_type_id'])) {
          $priceFieldOp['start_date'] = $membershipTypeValues[$priceFieldOp['membership_type_id']]['start_date'] ? CRM_Utils_Date::formatDateOnlyLong($membershipTypeValues[$priceFieldOp['membership_type_id']]['start_date']) : '-';
          $priceFieldOp['end_date'] = $membershipTypeValues[$priceFieldOp['membership_type_id']]['end_date'] ? CRM_Utils_Date::formatDateOnlyLong($membershipTypeValues[$priceFieldOp['membership_type_id']]['end_date']) : '-';
        }
        else {
          $priceFieldOp['start_date'] = $priceFieldOp['end_date'] = 'N/A';
        }
      }
    }
    $this->assign('lineItem', !empty($lineItem) && !$isQuickConfig ? $lineItem : FALSE);

    $contributionId = $this->ids['Contribution'] ?? CRM_Member_BAO_MembershipPayment::getLatestContributionIDFromLineitemAndFallbackToMembershipPayment($this->getMembershipID());
    $membershipIds = $this->_membershipIDs;
    if ($this->getSubmittedValue('send_receipt') && $contributionId && !empty($membershipIds)) {
      $contributionStatus = \Civi\Api4\Contribution::get(FALSE)
        ->addSelect('contribution_status_id:name')
        ->addWhere('id', '=', $contributionId)
        ->execute()
        ->first();
      if ($contributionStatus['contribution_status_id:name'] === 'Completed') {
        $formValues['contact_id'] = $this->_contactID;
        $formValues['contribution_id'] = $contributionId;
        // receipt_text_signup is no longer used in receipts from 5.47
        // but may linger in some sites that have not updated their
        // templates.
        $formValues['receipt_text_signup'] = $this->getSubmittedValue('receipt_text');
        // send email receipt
        $this->assignBillingName();
        $this->emailMembershipReceipt($formValues);
        $this->addStatusMessage(ts('A membership confirmation and receipt has been sent to %1.', [1 => $this->_contributorEmail]));
      }
    }

    CRM_Core_Session::setStatus($this->getStatusMessage(), ts('Complete'), 'success');
    $this->setStatusMessage();

    // finally set membership id if already not set
    if (!$this->_id) {
      $this->_id = $this->getMembershipID();
    }
  }

  /**
   * Update related contribution of a membership if update_contribution_on_membership_type_change
   *   contribution setting is enabled and type is changed on edit
   *
   * @param array $inputParams
   *      submitted form values
   *
   * @throws \CRM_Core_Exception
   */
  protected function updateContributionOnMembershipTypeChange($inputParams) {
    if (Civi::settings()->get('update_contribution_on_membership_type_change') &&
    // on update
      ($this->_action & CRM_Core_Action::UPDATE) &&
    // if ID is present
      $this->_id &&
    // if selected membership doesn't match with earlier membership
      !in_array($this->_memType, $this->_memTypeSelected)
    ) {
      if ($this->isCreateRecurringContribution()) {
        CRM_Core_Session::setStatus(ts('Associated recurring contribution cannot be updated on membership type change.'), ts('Error'), 'error');
        return;
      }

      // retrieve the related contribution ID
      $contributionID = CRM_Member_BAO_MembershipPayment::getLatestContributionIDFromLineitemAndFallbackToMembershipPayment($this->getMembershipID());

      // get price fields of chosen price-set
      $priceSetDetails = CRM_Utils_Array::value(
        $this->_priceSetId,
        CRM_Price_BAO_PriceSet::getSetDetail(
          $this->_priceSetId,
          TRUE,
          TRUE
        )
      );

      // add price field information in $inputParams
      self::addPriceFieldByMembershipType($inputParams, $priceSetDetails['fields'], $this->getMembership()['membership_type_id']);

      // update related contribution and financial records
      CRM_Price_BAO_LineItem::changeFeeSelections(
        $inputParams,
        $this->getMembershipID(),
        'membership',
        $contributionID,
        $this
      );
      CRM_Core_Session::setStatus(ts('Associated contribution is updated on membership type change.'), ts('Success'), 'success');
    }
  }

  /**
   * Add selected price field information in $formValues
   *
   * @param array $formValues
   *      submitted form values
   * @param array $priceFields
   *     Price fields of selected Priceset ID
   * @param int $membershipTypeID
   *     Selected membership type ID
   *
   */
  public static function addPriceFieldByMembershipType(&$formValues, $priceFields, $membershipTypeID) {
    foreach ($priceFields as $priceFieldID => $priceField) {
      if (isset($priceField['options']) && count($priceField['options'])) {
        foreach ($priceField['options'] as $option) {
          if ($option['membership_type_id'] == $membershipTypeID) {
            $formValues["price_{$priceFieldID}"] = $option['id'];
            break;
          }
        }
      }
    }
  }

  /**
   * Set context in session.
   */
  protected function setUserContext() {
    $buttonName = $this->controller->getButtonName();
    $session = CRM_Core_Session::singleton();

    if ($buttonName == $this->getButtonName('upload', 'new')) {
      if ($this->_context === 'standalone') {
        $url = CRM_Utils_System::url('civicrm/member/add',
          'reset=1&action=add&context=standalone'
        );
      }
      else {
        $url = CRM_Utils_System::url('civicrm/contact/view/membership',
          "reset=1&action=add&context=membership&cid={$this->_contactID}"
        );
      }
    }
    else {
      $url = CRM_Utils_System::url('civicrm/contact/view',
        "reset=1&cid={$this->_contactID}&selectedChild=member"
      );
      // Refresh other tabs with related data
      $this->ajaxResponse['updateTabs'] = [
        '#tab_activity' => TRUE,
      ];
      if (CRM_Core_Permission::access('CiviContribute')) {
        $this->ajaxResponse['updateTabs']['#tab_contribute'] = CRM_Contact_BAO_Contact::getCountComponent('contribution', $this->_contactID);
      }
    }
    $session->replaceUserContext($url);
  }

  /**
   * Get status message for updating membership.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getStatusMessageForUpdate(): string {
    foreach ($this->getCreatedMemberships() as $membership) {
      $endDate = $membership['end_date'] ?? NULL;
    }
    $statusMsg = ts('Membership for %1 has been updated.', [1 => htmlentities($this->_memberDisplayName)]);
    if ($endDate) {
      $endDate = CRM_Utils_Date::customFormat($endDate);
      $statusMsg .= ' ' . ts('The Membership Expiration Date is %1.', [1 => $endDate]);
    }
    return $statusMsg;
  }

  /**
   * Get status message for create action.
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  protected function getStatusMessageForCreate(): string {
    foreach ($this->getCreatedMemberships() as $membership) {
      $statusMsg[$membership['membership_type_id']] = ts('%1 membership for %2 has been added.', [
        1 => $this->allMembershipTypeDetails[$membership['membership_type_id']]['name'],
        2 => htmlentities($this->_memberDisplayName),
      ]);

      $memEndDate = $membership['end_date'] ?? NULL;

      if ($memEndDate) {
        $memEndDate = CRM_Utils_Date::formatDateOnlyLong($memEndDate);
        $statusMsg[$membership['membership_type_id']] .= ' ' . ts('The new Membership Expiration Date is %1.', [1 => $memEndDate]);
      }
    }
    $statusMsg = implode('<br/>', $statusMsg);
    return $statusMsg ?? '';
  }

  /**
   */
  protected function setStatusMessage() {
    //CRM-15187
    // display message when membership type is changed
    if (($this->_action & CRM_Core_Action::UPDATE) && $this->getMembershipID() && !in_array($this->_memType, $this->_memTypeSelected)) {
      $lineItems = CRM_Price_BAO_LineItem::getLineItems($this->getMembershipID(), 'membership');
      if (empty($lineItems)) {
        return;
      }

      $maxID = max(array_keys($lineItems));
      $lineItem = $lineItems[$maxID];
      $membershipTypeDetails = $this->allMembershipTypeDetails[$this->getMembership()['membership_type_id']];
      if ($membershipTypeDetails['financial_type_id'] != $lineItem['financial_type_id']) {
        CRM_Core_Session::setStatus(
          ts('The financial types associated with the old and new membership types are different. You may want to edit the contribution associated with this membership to adjust its financial type.'),
          ts('Warning')
        );
      }
      if ($membershipTypeDetails['minimum_fee'] != $lineItem['line_total']) {
        CRM_Core_Session::setStatus(
          ts('The cost of the old and new membership types are different. You may want to edit the contribution associated with this membership to adjust its amount.'),
          ts('Warning')
        );
      }
    }
  }

  /**
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function isUpdateToExistingRecurringMembership() {
    $isRecur = FALSE;
    if ($this->_action & CRM_Core_Action::UPDATE
      && CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $this->getEntityId(),
        'contribution_recur_id')
      && !CRM_Member_BAO_Membership::isSubscriptionCancelled((int) $this->getEntityId())) {

      $isRecur = TRUE;
    }
    return $isRecur;
  }

  /**
   * Send a receipt for the membership.
   *
   * @param array $formValues
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected function emailMembershipReceipt($formValues) {
    $customValues = $this->getCustomValuesForReceipt();
    $this->assign('customValues', $customValues);
    $this->assign('total_amount', $this->order->getTotalAmount());

    if ($this->_mode) {
      // @todo move this outside shared code as Batch entry just doesn't
      $this->assign('address', CRM_Utils_Address::getFormattedBillingAddressFieldsFromParameters($this->_params));

      $valuesForForm = CRM_Contribute_Form_AbstractEditPayment::formatCreditCardDetails($this->_params);
      $this->assignVariables($valuesForForm, ['credit_card_exp_date', 'credit_card_type', 'credit_card_number']);
      $this->assign('is_pay_later', 0);
      $this->assign('isPrimary', 1);
    }
    //insert financial type name in receipt.
    $formValues['contributionType_name'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
      $this->getFinancialTypeID()
    );
    $this->emailReceipt($formValues);
    return TRUE;
  }

  /**
   * Filter the custom values from the input parameters (for display in the email).
   *
   * @todo move this to the WorkFlowMessageTrait.
   *
   * @return array
   */
  protected function getCustomValuesForReceipt(): array {
    $customFieldValues = $this->getCustomFieldValues();
    $customValues = [];
    foreach ($customFieldValues as $customFieldString => $submittedValue) {
      if ($submittedValue === NULL) {
        // This would occur when the field is no longer appropriate - ie changing
        // from a membership type with the field to one without it.
        continue;
      }
      $customFieldID = (int) CRM_Core_BAO_CustomField::getKeyID($customFieldString);
      $value = CRM_Core_BAO_CustomField::displayValue($this->getSubmittedValue($customFieldString), $customFieldID);
      $customValues[CRM_Core_BAO_CustomField::getField($customFieldID)['label']] = $value;
    }
    return $customValues;
  }

  /**
   * Get the custom fields tat are on the form with the submitted values.
   *
   * @internal I think it would be good to make this field
   * available as an api supported method - maybe on the form custom data trait
   * but I feel like we might want to talk about the format of the returned results
   * (key-value vs array, custom field ID keys vs custom_, filters/ multiple functions
   * with different formatting) so keeping private for now.
   *
   * @return array
   */
  private function getCustomFieldValues(): array {
    $customFields = [];
    foreach ($this->_elements as $element) {
      $customFieldID = CRM_Core_BAO_CustomField::getKeyID($element->getName());
      if ($customFieldID) {
        $customFields[$element->getName()] = $this->getSubmittedValue($element->getName());
      }
    }
    return $customFields;
  }

  /**
   * Get the selected memberships as a string of labels.
   *
   * @return string
   */
  protected function getSelectedMembershipLabels(): string {
    $return = [];
    foreach ($this->_memTypeSelected as $membershipTypeID) {
      $return[] = $this->allMembershipTypeDetails[$membershipTypeID]['name'];
    }
    return implode(', ', $return);
  }

  /**
   * Get the recurring contribution id, if one is applicable.
   *
   * If the recurring contribution is applicable and not yet
   * created it will be created at this stage.
   *
   * @return int|null
   *
   * @throws \CRM_Core_Exception
   */
  protected function getContributionRecurID():?int {
    if (!array_key_exists('ContributionRecur', $this->ids)) {
      $this->createRecurringContribution();
    }
    return $this->ids['ContributionRecur'];
  }

  /**
   * Create the recurring contribution record if the form submission requires it.
   *
   * This function was copied from another form & needs cleanup.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createRecurringContribution(): void {
    if (!$this->isCreateRecurringContribution()) {
      $this->ids['ContributionRecur'] = NULL;
      return;
    }
    $recurParams = ['contact_id' => $this->getContributionContactID()];
    $recurParams['amount'] = $this->order->getTotalAmount();
    // for the legacyProcessRecurringContribution function to be reached auto_renew must be true
    $recurParams['auto_renew'] = TRUE;
    $recurParams['frequency_unit'] = $this->getFrequencyUnit();
    $recurParams['frequency_interval'] = $this->getFrequencyInterval();
    $recurParams['financial_type_id'] = $this->getFinancialTypeID();
    $recurParams['currency'] = $this->getCurrency();
    $recurParams['payment_instrument_id'] = $this->getPaymentInstrumentID();
    $recurParams['is_test'] = $this->isTest();
    $recurParams['create_date'] = $recurParams['modified_date'] = CRM_Utils_Time::date('YmdHis');
    $recurParams['start_date'] = $this->getReceiveDate();
    $recurParams['invoice_id'] = $this->getInvoiceID();
    $recurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending');
    $recurParams['payment_processor_id'] = $this->getPaymentProcessorID();
    $recurParams['is_email_receipt'] = (bool) $this->getSubmittedValue('send_receipt');
    // we need to add a unique trxn_id to avoid a unique key error
    // in paypal IPN we reset this when paypal sends us the real trxn id, CRM-2991
    $recurParams['trxn_id'] = $this->getInvoiceID();
    $recurParams['campaign_id'] = $this->getSubmittedValue('campaign_id');
    $this->ids['ContributionRecur'] = ContributionRecur::create(FALSE)->setValues($recurParams)->execute()->first()['id'];
  }

  /**
   * Is the form being submitted in test mode.
   *
   * @return bool
   */
  protected function isTest(): bool {
    return ($this->_mode === 'test') ? TRUE : FALSE;
  }

  /**
   * Get the financial type id relevant to the contribution.
   *
   * Financial type id is optional when price sets are in use.
   * Otherwise they are required for the form to submit.
   *
   * @return int
   */
  protected function getFinancialTypeID(): int {
    return (int) $this->getSubmittedValue('financial_type_id') ?: $this->order->getFinancialTypeID();
  }

  /**
   * Get the membership type, if any, to be recurred.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getRecurMembershipType(): array {
    foreach ($this->order->getRenewableMembershipTypes() as $type) {
      return $type;
    }
    return [];
  }

  /**
   * Get the frequency interval.
   *
   * @return int|null
   * @throws \CRM_Core_Exception
   */
  protected function getFrequencyInterval(): ?int {
    $membershipType = $this->getRecurMembershipType();
    return empty($membershipType) ? NULL : (int) $membershipType['duration_interval'];
  }

  /**
   * Get the frequency interval.
   *
   * @return string|null
   * @throws \CRM_Core_Exception
   */
  protected function getFrequencyUnit(): ?string {
    $membershipType = $this->getRecurMembershipType();
    return empty($membershipType) ? NULL : (string) $membershipType['duration_unit'];
  }

  /**
   * Get values that should be passed to all membership create actions.
   *
   * These parameters are generic to all memberships created from the form,
   * whether a single membership or multiple by price set (although
   * the form will not expose all in the latter case.
   *
   * By referencing the submitted values directly we can call this
   * from anywhere in postProcess and get the same result (protects
   * against breakage if code is moved around).
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getFormMembershipParams(): array {
    $params = [
      'status_id' => $this->getSubmittedValue('status_id'),
      'source' => $this->getSubmittedValue('source') ?? $this->getContributionSource(),
      'contact_id' => $this->getMembershipContactID(),
      'is_override' => $this->getSubmittedValue('is_override'),
      'status_override_end_date' => $this->getSubmittedValue('status_override_end_date'),
      'campaign_id' => $this->getSubmittedValue('campaign_id'),
      'custom' => CRM_Core_BAO_CustomField::postProcess($this->getSubmittedValues(),
        $this->_id,
        'Membership'
      ),
      // fix for CRM-3724
      // when is_override false ignore is_admin statuses during membership
      // status calculation. similarly we did fix for import in CRM-3570.
      'exclude_is_admin' => !$this->getSubmittedValue('is_override'),
      'contribution_recur_id' => $this->getContributionRecurID(),
    ];
    $params += $this->getSubmittedCustomFields(4);
    return $params;
  }

  /**
   * Is it necessary to create a recurring contribution.
   *
   * @return bool
   */
  protected function isCreateRecurringContribution(): bool {
    return $this->_mode && $this->getSubmittedValue('auto_renew');
  }

  /**
   * Get the payment processor ID.
   *
   * @return int
   */
  public function getPaymentProcessorID(): int {
    return (int) ($this->getSubmittedValue('payment_processor_id') ?: $this->_paymentProcessor['id']);
  }

  /**
   * Get memberships submitted through the form submission.
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getCreatedMemberships(): array {
    return civicrm_api3('Membership', 'get', ['id' => ['IN' => $this->_membershipIDs]])['values'];
  }

  /**
   * Get parameters for membership create for all memberships to be created.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getMembershipParameters(): array {
    $membershipTypeValues = [];
    foreach ($this->_memTypeSelected as $memType) {
      $membershipTypeValues[$memType]['membership_type_id'] = $memType;
      if (is_numeric($this->getSubmittedValue('max_related'))) {
        // The BAO will set from the membership type is not passed in but we should
        // not set this if we don't need to to let the BAO do it's thing.
        $membershipTypeValues[$memType]['max_related'] = $this->getSubmittedValue('max_related');
      }
    }
    // Really we don't need to do all this unless one of the join dates
    // has been left empty by the submitter - ie in an ADD scenario but not really
    // valid when editing & it would possibly not get the number of terms right
    // so ideally the fields would be required on edit & the below would only
    // be called on ADD
    foreach ($this->order->getMembershipLineItems() as $membershipLineItem) {
      if ($this->getAction() === CRM_Core_Action::ADD && $this->isQuickConfig()) {
        $memTypeNumTerms = $this->getSubmittedValue('num_terms') ?: 1;
      }
      else {
        // The submitted value is hidden when a price set is selected so
        // although it is present it should be ignored.
        $memTypeNumTerms = $membershipLineItem['membership_num_terms'];
      }
      $calcDates = CRM_Member_BAO_MembershipType::getDatesForMembershipType(
        $membershipLineItem['membership_type_id'],
        $this->getSubmittedValue('join_date'),
        $this->getSubmittedValue('start_date'),
        $this->getSubmittedValue('end_date'),
        $memTypeNumTerms
      );
      $membershipTypeValues[$membershipLineItem['membership_type_id']]['join_date'] = $calcDates['join_date'];
      $membershipTypeValues[$membershipLineItem['membership_type_id']]['start_date'] = $calcDates['start_date'];
      $membershipTypeValues[$membershipLineItem['membership_type_id']]['end_date'] = $calcDates['end_date'];
    }

    return $membershipTypeValues;
  }

  /**
   * Get the value for the contribution source.
   *
   * @return string
   */
  protected function getContributionSource(): string {
    [$userName] = CRM_Contact_BAO_Contact_Location::getEmailDetails(CRM_Core_Session::getLoggedInContactID());
    $userName = htmlentities($userName);
    if ($this->_mode) {
      return ts('%1 Membership Signup: Credit card or direct debit (by %2)',
        [1 => $this->getSelectedMembershipLabels(), 2 => $userName]
      );
    }
    if ($this->getSubmittedValue('source')) {
      return $this->getSubmittedValue('source');
    }
    return ts('%1 Membership: Offline signup (by %2)', [
      1 => $this->getSelectedMembershipLabels(),
      2 => $userName,
    ]);
  }

  /**
   * Get the receive date for the contribution.
   *
   * @return string $receive_date
   */
  protected function getReceiveDate(): string {
    return $this->getSubmittedValue('receive_date') ?: date('YmdHis');
  }

  /**
   * Set membership IDs.
   *
   * @param array $ids
   */
  protected function setMembershipIDs(array $ids): void {
    $this->_membershipIDs = $ids;
  }

  /**
   * Get the created or edited membership ID.
   *
   * @return int|null
   */
  public function getMembershipID(): ?int {
    return $this->_membershipIDs[0] ?? NULL;
  }

  /**
   * Get the membership (or last membership) created or edited on this form.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getMembership(): array {
    if (empty($this->membership)) {
      $this->membership = civicrm_api3('Membership', 'get', ['id' => $this->getMembershipID()])['values'][$this->getMembershipID()];
    }
    return $this->membership;
  }

  /**
   * Setter for membership.
   *
   * @param array $membership
   */
  protected function setMembership(array $membership): void {
    if (!in_array($membership['id'], $this->_membershipIDs, TRUE)) {
      $this->_membershipIDs[] = (int) $membership['id'];
    }
    $this->membership = $membership;
  }

  /**
   * Get line items formatted for the Order api.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function getLineItemForOrderApi(): array {
    $lineItems = [];
    foreach ($this->order->getLineItems() as $line) {
      $params = [];
      if (!empty($line['membership_type_id'])) {
        $params = $this->getMembershipParamsForType((int) $line['membership_type_id']);
      }
      $lineItems[] = [
        'line_item' => [$line['price_field_value_id'] => $line],
        'params' => $params,
      ];
    }
    return $lineItems;
  }

  /**
   * Get the parameters for the given membership type.
   *
   * @param int $membershipTypeID
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  protected function getMembershipParamsForType(int $membershipTypeID) {
    return array_merge($this->getFormMembershipParams(), $this->getMembershipParameters()[$membershipTypeID]);
  }

  /**
   * @param array $contribution
   */
  protected function setMembershipIDsFromOrder(array $contribution): void {
    $ids = [];
    foreach ($contribution['values'][$contribution['id']]['line_item'] as $line) {
      if ($line['entity_table'] ?? '' === 'civicrm_membership') {
        $ids[] = (int) $line['entity_id'];
      }
    }
    $this->setMembershipIDs($ids);
  }

}
