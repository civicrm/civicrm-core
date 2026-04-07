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

use Civi\Api4\PriceSetEntity;

/**
 * form to process actions on Membership
 */
class CRM_Member_Form_MembershipBlock extends CRM_Contribute_Form_ContributionPage {

  /**
   * Store membership price set id
   * @var int
   */
  protected $_memPriceSetId;

  /**
   * Set variables up before form is built.
   */
  public function preProcess(): void {
    parent::preProcess();
    $this->setSelectedChild('membership');
  }

  /**
   * Set default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   */
  public function setDefaultValues(): ?array {
    $defaults = [];
    if ($this->getContributionPageID()) {
      $defaults = CRM_Member_BAO_Membership::getMembershipBlock($this->getContributionPageID());
    }
    $defaults['member_is_active'] = $defaults['is_active'] ?? FALSE;

    // Set Display Minimum Fee default to true if we are adding a new membership block
    if (!isset($defaults['id'])) {
      $defaults['display_min_fee'] = 1;
    }
    else {
      $this->assign('membershipBlockId', $defaults['id']);
    }
    if ($this->_id &&
      ($priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id, 3, 1))
    ) {
      $defaults['member_price_set_id'] = $priceSetId;
      $this->_memPriceSetId = $priceSetId;
    }
    else {
      // for membership_types
      // if ( isset( $defaults['membership_types'] ) ) {
      $priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id, 3);
      $this->assign('isQuick', 1);
      $this->_memPriceSetId = $priceSetId;
      $pFIDs = [];
      if ($priceSetId) {
        CRM_Core_DAO::commonRetrieveAll('CRM_Price_DAO_PriceField', 'price_set_id', $priceSetId, $pFIDs, $return = [
          'html_type',
          'name',
          'label',
        ]);
        foreach ($pFIDs as $pValue) {
          if ($pValue['html_type'] === 'Radio' && $pValue['name'] === 'membership_amount') {
            $defaults['mem_price_field_id'] = $pValue['id'];
            $defaults['membership_type_label'] = $pValue['label'];
          }
        }

        if (!empty($defaults['mem_price_field_id'])) {
          $options = [];
          $priceFieldOptions = CRM_Price_BAO_PriceFieldValue::getValues($defaults['mem_price_field_id'], $options, 'id', 1);
          foreach ($options as $v) {
            $newMembershipType[$v['membership_type_id']] = 1;
            if (!empty($defaults['auto_renew'])) {
              $defaults['auto_renew_' . $v['membership_type_id']] = $defaults['auto_renew'][$v['membership_type_id']];
            }
          }
          $defaults['membership_type'] = $newMembershipType;
        }
      }
    }

    return $defaults;
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $membershipTypes = \Civi\Api4\MembershipType::get(FALSE)
      ->addWhere('is_active', '=', TRUE)
      ->addOrderBy('weight', 'ASC')
      ->execute()
      ->column('title', 'id');

    if (!empty($membershipTypes)) {
      $this->addElement('checkbox', 'member_is_active', ts('Membership Section Enabled?'));

      $this->addElement('text', 'new_title', ts('Title - New Membership'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipBlock', 'new_title'));

      $this->add('wysiwyg', 'new_text', ts('Introductory Message - New Memberships'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipBlock', 'new_text'));

      $this->addElement('text', 'renewal_title', ts('Title - Renewals'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipBlock', 'renewal_title'));

      $this->add('wysiwyg', 'renewal_text', ts('Introductory Message - Renewals'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipBlock', 'renewal_text'));

      $this->addElement('checkbox', 'is_required', ts('Require Membership Signup'));
      $this->addElement('checkbox', 'display_min_fee', ts('Display Membership Fee'));
      $this->addElement('checkbox', 'is_separate_payment', ts('Separate Membership Payment'));
      $this->addElement('text', 'membership_type_label', ts('Membership Types Label'), ['placeholder' => ts('Membership')]);

      $paymentProcessor = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, 'is_recur = 1');
      $paymentProcessorIds = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id, 'payment_processor'
      );
      $paymentProcessorId = explode(CRM_Core_DAO::VALUE_SEPARATOR, $paymentProcessorIds ?? '');
      $isRecur = TRUE;
      foreach ($paymentProcessorId as $id) {
        if (!array_key_exists($id, $paymentProcessor)) {
          $isRecur = FALSE;
          continue;
        }
      }

      $membership = $membershipDefault = [];
      $renewOption = [];
      foreach ($membershipTypes as $k => $v) {
        $membership[] = $this->createElement('advcheckbox', $k, NULL, $v);
        $membershipDefault[$k] = NULL;
        $membershipRequired[$k] = NULL;
        if ($isRecur) {
          $autoRenew = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $k, 'auto_renew');
          $membershipRequired[$k] = $autoRenew;
          if ($autoRenew) {
            $autoRenewOptions = [ts('Not offered'), ts('Give option'), ts('Required')];
            $this->addElement('select', "auto_renew_$k", ts('Auto-renew'), $autoRenewOptions);
            //CRM-15573
            if ($autoRenew == 2) {
              $this->freeze("auto_renew_$k");
            }
            $renewOption[$k] = $autoRenew;
          }
        }
      }

      $this->add('hidden', 'mem_price_field_id', '', ['id' => 'mem_price_field_id']);
      $this->assign('is_recur', $isRecur);
      $this->assign('auto_renew', $renewOption);
      $this->addGroup($membership, 'membership_type', ts('Membership Types'));
      $this->addRadio('membership_type_default', ts('Membership Types Default'), $membershipDefault, ['allowClear' => TRUE]);

      $this->addFormRule(['CRM_Member_Form_MembershipBlock', 'formRule'], $this->_id);
    }
    $price = CRM_Price_BAO_PriceSet::getAssoc(FALSE, 'CiviMember');
    if (CRM_Utils_System::isNull($price)) {
      $this->assign('price', FALSE);
    }
    else {
      $this->assign('price', TRUE);
    }

    $this->addSelect('member_price_set_id', [
      'entity' => 'PriceSet',
      'option_url' => 'civicrm/admin/price',
      'label' => ts('Membership Price Set'),
      'name' => 'price_set_id',
      'options' => $price,
    ]);

    $session = CRM_Core_Session::singleton();
    $single = $session->get('singleForm');
    if ($single) {
      $this->addButtons([
        [
          'type' => 'next',
          'name' => ts('Save'),
          'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
          'isDefault' => TRUE,
        ],
        [
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ],
      ]);
    }
    else {
      parent::buildQuickForm();
    }
  }

  /**
   * Validation.
   *
   * @param array $params
   *   (ref.) an assoc array of name/value pairs.
   * @param $files
   * @param int $contributionPageId
   *
   * @return bool|array
   *   mixed true or array of errors
   * @throws \CRM_Core_Exception
   */
  public static function formRule(array $params, $files, $contributionPageId = NULL) {
    $errors = [];

    if (!empty($params['member_price_set_id'])) {
      //check if this price set has membership type both auto-renew and non-auto-renew memberships.
      $bothTypes = CRM_Price_BAO_PriceSet::isMembershipPriceSetContainsMixOfRenewNonRenew($params['member_price_set_id']);

      //check for supporting payment processors
      //if both auto-renew and non-auto-renew memberships
      if ($bothTypes) {
        $paymentProcessorIds = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
          $contributionPageId, 'payment_processor'
        );

        $paymentProcessorId = explode(CRM_Core_DAO::VALUE_SEPARATOR, $paymentProcessorIds);

        if (!empty($paymentProcessorId)) {
          foreach ($paymentProcessorId as $pid) {
            if ($pid) {
              $processor = Civi\Payment\System::singleton()->getById($pid);
              if (!$processor->supports('MultipleConcurrentPayments')) {
                $errors['member_price_set_id'] = ts('The membership price set associated with this online contribution allows a user to select BOTH an auto-renew AND a non-auto-renew membership. This requires submitting multiple processor transactions, and is not supported for one or more of the payment processors enabled under the Amounts tab.');
              }
            }
          }
        }
      }
    }
    if (!empty($params['member_is_active'])) {
      // Don't allow Contribution price set w/ membership signup, CRM-5095.
      $priceSetNotExtendingMembership = PriceSetEntity::get(FALSE)
        ->addSelect('id')
        ->addJoin('PriceSet AS price_set', 'LEFT', ['price_set_id', '=', 'price_set.id'])
        ->addWhere('entity_table', '=', 'civicrm_contribution_page')
        ->addWhere('entity_id', '=', $contributionPageId)
        ->addWhere('price_set.extends:name', 'NOT CONTAINS', 'CiviMember')
        ->addWhere('price_set.is_quick_config', '=', 0)
        ->execute()
        ->first();
      if ($priceSetNotExtendingMembership) {
        $errors['member_is_active'] = ts('You cannot enable both Membership Signup and a Contribution Price Set on the same online contribution page.');
        return $errors;
      }

      if (!empty($params['member_price_set_id'])) {
        return $errors;
      }

      if (!isset($params['membership_type']) ||
        (!is_array($params['membership_type']))
      ) {
        $errors['membership_type'] = ts('Please select at least one Membership Type to include in the Membership section of this page.');
      }
      else {
        $membershipType = array_map('intval', array_values($params['membership_type']));
        $isRecur = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $contributionPageId, 'is_recur');
        if (array_sum($membershipType) == 0) {
          $errors['membership_type'] = ts('Please select at least one Membership Type to include in the Membership section of this page.');
        }
        elseif (array_sum($membershipType) > CRM_Price_Form_Field::NUM_OPTION) {
          // for CRM-13079
          $errors['membership_type'] = ts('You cannot select more than %1 choices. For more complex functionality, please use a Price Set.', [1 => CRM_Price_Form_Field::NUM_OPTION]);
        }
        elseif ($isRecur) {
          if (empty($params['is_separate_payment']) && array_sum($membershipType) != 0) {
            $errors['is_separate_payment'] = ts('You need to enable Separate Membership Payment when online contribution page is configured for both Membership and Recurring Contribution');
          }
          elseif (!empty($params['is_separate_payment'])) {
            foreach ($params['membership_type'] as $mt => $dontCare) {
              if (!empty($params["auto_renew_$mt"])) {
                $errors["auto_renew_$mt"] = ts('You cannot enable both Recurring Contributions and Auto-renew memberships on the same online contribution page');
                break;
              }
            }
          }
        }
      }

      //for CRM-1302
      //if Membership status is not present, then display an error message
      $dao = new CRM_Member_BAO_MembershipStatus();
      if (!$dao->find()) {
        $errors['_qf_default'] = ts('Add status rules, before configuring membership');
      }

      //give error if default is selected for an unchecked membership type
      if (!empty($params['membership_type_default']) && !$params['membership_type'][$params['membership_type_default']]) {
        $errors['membership_type_default'] = ts('Can\'t set default option for an unchecked membership type.');
      }

      if ($contributionPageId) {
        $amountBlock = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $contributionPageId, 'amount_block_is_active');

        if (!$amountBlock && !empty($params['is_separate_payment'])) {
          $errors['is_separate_payment'] = ts('Please enable the contribution amount section to use this option.');
        }
      }

    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form.
   *
   * @throws \CRM_Core_Exception
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
    $deletePriceSet = 0;
    if ($params['membership_type']) {
      // we do this in case the user has hit the forward/back button
      $dao = new CRM_Member_DAO_MembershipBlock();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id = $this->_id;
      $dao->find(TRUE);
      $membershipID = $dao->id;
      if ($membershipID) {
        $params['id'] = $membershipID;
      }

      $membershipTypes = [];
      if (is_array($params['membership_type'])) {
        foreach ($params['membership_type'] as $k => $v) {
          if ($v) {
            $membershipTypes[$k] = $params["auto_renew_$k"] ?? NULL;
          }
        }
      }

      if ($this->_id && !empty($params['member_price_set_id'])) {
        CRM_Core_DAO::setFieldValue('CRM_Contribute_DAO_ContributionPage', $this->_id, 'amount_block_is_active', 0);
      }

      // check for price set.
      $priceSetID = $params['member_price_set_id'] ?? NULL;
      if (!empty($params['member_is_active']) && is_array($membershipTypes) && !$priceSetID) {
        $usedPriceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $this->_id, 2);
        if (empty($params['mem_price_field_id']) && !$usedPriceSetId) {
          $pageTitle = strtolower(CRM_Utils_String::munge($this->_values['title'], '_', 245));
          $setParams['title'] = $this->_values['title'];
          if (!CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceSet', $pageTitle, 'id', 'name')) {
            $setParams['name'] = $pageTitle;
          }
          elseif (!CRM_Core_DAO::getFieldValue('CRM_Price_BAO_PriceSet', $pageTitle . '_' . $this->_id, 'id', 'name')) {
            $setParams['name'] = $pageTitle . '_' . $this->_id;
          }
          else {
            $timeSec = explode('.', microtime(TRUE));
            $setParams['name'] = $pageTitle . '_' . date('is', $timeSec[0]) . $timeSec[1];
          }
          $setParams['is_quick_config'] = 1;
          $setParams['extends'] = CRM_Core_Component::getComponentID('CiviMember');
          $setParams['financial_type_id'] = $this->_values['financial_type_id'] ?? NULL;
          $priceSet = CRM_Price_BAO_PriceSet::create($setParams);
          $priceSetID = $priceSet->id;
          $fieldParams['price_set_id'] = $priceSet->id;
        }
        elseif ($usedPriceSetId) {
          $setParams['extends'] = CRM_Core_Component::getComponentID('CiviMember');
          $setParams['financial_type_id'] = $this->_values['financial_type_id'] ?? NULL;
          $setParams['id'] = $usedPriceSetId;
          $priceSet = CRM_Price_BAO_PriceSet::create($setParams);
          $priceSetID = $priceSet->id;
          $fieldParams['price_set_id'] = $priceSet->id;
        }
        else {
          $fieldParams['id'] = $params['mem_price_field_id'] ?? NULL;
          $priceSetID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', $params['mem_price_field_id'] ?? NULL, 'price_set_id');
        }
        $editedFieldParams = [
          'price_set_id' => $priceSetID,
          'name' => 'membership_amount',
        ];
        $editedResults = [];
        CRM_Price_BAO_PriceField::retrieve($editedFieldParams, $editedResults);
        if (empty($editedResults['id'])) {
          $fieldParams['name'] = 'membership_amount';
          if (empty($params['mem_price_field_id'])) {
            CRM_Utils_Weight::updateOtherWeights('CRM_Price_DAO_PriceField', NULL, 1, ['price_set_id' => $priceSetID]);
          }
          $fieldParams['weight'] = 1;
        }
        else {
          $fieldParams['id'] = $editedResults['id'] ?? NULL;
        }

        $fieldParams['label'] = !empty($params['membership_type_label']) ? $params['membership_type_label'] : ts('Membership');
        $fieldParams['is_active'] = 1;
        $fieldParams['html_type'] = 'Radio';
        $fieldParams['is_required'] = !empty($params['is_required']) ? 1 : 0;
        $fieldParams['is_display_amounts'] = !empty($params['display_min_fee']) ? 1 : 0;
        $rowCount = 1;
        $options = [];
        if (!empty($fieldParams['id'])) {
          CRM_Core_PseudoConstant::populate($options, 'CRM_Price_DAO_PriceFieldValue', TRUE, 'membership_type_id', NULL, " price_field_id = {$fieldParams['id']} ");
        }

        foreach ($membershipTypes as $memType => $memAutoRenew) {
          if ($priceFieldID = CRM_Utils_Array::key($memType, $options)) {
            $fieldParams['option_id'][$rowCount] = $priceFieldID;
            unset($options[$priceFieldID]);
          }
          $membershipType = CRM_Member_BAO_MembershipType::getMembershipType($memType);
          $fieldParams['option_label'][$rowCount] = $membershipType['frontend_title'];
          $fieldParams['option_amount'][$rowCount] = $membershipType['minimum_fee'];
          $fieldParams['option_weight'][$rowCount] = $membershipType['weight'];
          $fieldParams['option_description'][$rowCount] = $membershipType['description'];
          $fieldParams['default_option'] = $params['membership_type_default'] ?? NULL;
          $fieldParams['option_financial_type_id'][$rowCount] = $membershipType['financial_type_id'];

          $fieldParams['membership_type_id'][$rowCount] = $memType;
          $rowCount++;
        }
        foreach ($options as $priceFieldID => $memType) {
          CRM_Price_BAO_PriceFieldValue::setIsActive($priceFieldID, '0');
        }
        CRM_Price_BAO_PriceField::create($fieldParams);
      }
      elseif (!$priceSetID) {
        $deletePriceSet = 1;
      }

      $params['is_required'] = $params['is_required'] ?? FALSE;
      $params['is_active'] = $params['member_is_active'] ?? FALSE;

      if ($priceSetID) {
        $params['membership_type_default'] = $params['membership_type_default'] ?? 'null';
        $params['membership_types'] = serialize($membershipTypes);
        $params['display_min_fee'] = $params['display_min_fee'] ?? FALSE;
        $params['is_separate_payment'] = $params['is_separate_payment'] ?? FALSE;
      }
      $params['entity_table'] = 'civicrm_contribution_page';
      $params['entity_id'] = $this->_id;

      $dao = new CRM_Member_DAO_MembershipBlock();
      $dao->copyValues($params);
      $dao->save();

      if ($priceSetID && $params['is_active']) {
        CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $this->_id, $priceSetID);
      }

      if ($deletePriceSet || empty($params['member_is_active'])) {

        if ($this->_memPriceSetId) {
          $pFIDs = [];
          $conditionParams = [
            'price_set_id' => $this->_memPriceSetId,
            'html_type' => 'radio',
            'name' => 'contribution_amount',
          ];

          CRM_Core_DAO::commonRetrieve('CRM_Price_DAO_PriceField', $conditionParams, $pFIDs);
          if (empty($pFIDs['id'])) {
            CRM_Price_BAO_PriceSet::removeFrom('civicrm_contribution_page', $this->_id);
            CRM_Price_BAO_PriceSet::setIsQuickConfig($this->_memPriceSetId, '0');
          }
          else {

            CRM_Price_BAO_PriceField::setIsActive($params['mem_price_field_id'], '0');
          }
        }
      }
    }
    parent::endPostProcess();
  }

  /**
   * Return a descriptive name for the page, used in wizard header
   *
   * @return string
   */
  public function getTitle(): string {
    return ts('Memberships');
  }

}
