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
 * form to process actions on Membership
 */
class CRM_Member_Form_MembershipBlock extends CRM_Contribute_Form_ContributionPage {

  /**
   * store membership price set id
   */
  protected $_memPriceSetId = NULL;

  /**
   * This function sets the default values for the form. Note that in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return void
   */
  function setDefaultValues() {
    //parent::setDefaultValues();
    $defaults = array();
    if (isset($this->_id)) {
      $defaults = CRM_Member_BAO_Membership::getMembershipBlock($this->_id);
    }
    $defaults['member_is_active'] = $defaults['is_active'];

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
      $pFIDs = array();
      if ($priceSetId) {
        CRM_Core_DAO::commonRetrieveAll('CRM_Price_DAO_PriceField', 'price_set_id', $priceSetId, $pFIDs, $return = array('html_type', 'name'));
        foreach ($pFIDs as $pid => $pValue) {
          if ($pValue['html_type'] == 'Radio' && $pValue['name'] == 'membership_amount') {
            $defaults['mem_price_field_id'] = $pValue['id'];
          }
        }

        if (!empty($defaults['mem_price_field_id'])) {
          $options = array();
          $priceFieldOptions = CRM_Price_BAO_PriceFieldValue::getValues($defaults['mem_price_field_id'], $options, 'id', 1);
          foreach ($options as $k => $v) {
            $newMembershipType[$v['membership_type_id']] = 1;
            if (!empty($defaults['auto_renew'])) {
              $defaults["auto_renew_".$v['membership_type_id']] = $defaults['auto_renew'][$v['membership_type_id']];
            }
          }
          $defaults['membership_type'] = $newMembershipType;
        }
      }
    }

    return $defaults;
  }

  /**
   * Function to actually build the form
   *
   * @return void
   * @access public
   */
  public function buildQuickForm() {
    $membershipTypes = CRM_Member_BAO_MembershipType::getMembershipTypes();

    if (!empty($membershipTypes)) {
      $this->addElement('checkbox', 'member_is_active', ts('Membership Section Enabled?'));

      $this->addElement('text', 'new_title', ts('Title - New Membership'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipBlock', 'new_title'));

      $this->addWysiwyg('new_text', ts('Introductory Message - New Memberships'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipBlock', 'new_text'));

      $this->addElement('text', 'renewal_title', ts('Title - Renewals'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipBlock', 'renewal_title'));

      $this->addWysiwyg('renewal_text', ts('Introductory Message - Renewals'), CRM_Core_DAO::getAttribute('CRM_Member_DAO_MembershipBlock', 'renewal_text'));

      $this->addElement('checkbox', 'is_required', ts('Require Membership Signup'));
      $this->addElement('checkbox', 'display_min_fee', ts('Display Membership Fee'));
      $this->addElement('checkbox', 'is_separate_payment', ts('Separate Membership Payment'));

      $paymentProcessor = CRM_Core_PseudoConstant::paymentProcessor(FALSE, FALSE, 'is_recur = 1');
      $paymentProcessorIds = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
        $this->_id, 'payment_processor'
      );
      $paymentProcessorId = explode(CRM_Core_DAO::VALUE_SEPARATOR, $paymentProcessorIds);
      $isRecur = TRUE;
      foreach ($paymentProcessorId as $dontCare => $id) {
        if (!array_key_exists($id, $paymentProcessor)) {
          $isRecur = FALSE;
          continue;
        }
      }

      $membership = $membershipDefault = $params = array();
      foreach ($membershipTypes as $k => $v) {
        $membership[] = $this->createElement('advcheckbox', $k, NULL, $v);
        $membershipDefault[] = $this->createElement('radio', NULL, NULL, NULL, $k);
        $membershipRequired[$k] = NULL;
        if ($isRecur) {
          $autoRenew        = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipType', $k, 'auto_renew');
          $membershipRequired[$k] = $autoRenew;
          $autoRenewOptions = array();
          if ($autoRenew) {
            $autoRenewOptions = array(ts('Not offered'), ts('Give option'), ts('Required'));
            $this->addElement('select', "auto_renew_$k", ts('Auto-renew'), $autoRenewOptions);
            //CRM-15573
            if($autoRenew == 2) {
              $this->freeze("auto_renew_$k");
              $params['id'] = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_MembershipBlock', $this->_id , 'id', 'entity_id');
            }
            $this->_renewOption[$k] = $autoRenew;
          }
        }
      }

      //CRM-15573
      if (!empty($params['id'])) {
        $params['membership_types'] = serialize($membershipRequired);
        CRM_Member_BAO_MembershipBlock::create($params);
      }
      $this->add('hidden', "mem_price_field_id", '', array('id' => "mem_price_field_id"));
      $this->assign('is_recur', $isRecur);
      if (isset($this->_renewOption)) {
        $this->assign('auto_renew', $this->_renewOption);
      }
      $this->addGroup($membership, 'membership_type', ts('Membership Types'));
      $this->addGroup($membershipDefault, 'membership_type_default', ts('Membership Types Default'))->setAttribute('allowClear', TRUE);

      $this->addFormRule(array('CRM_Member_Form_MembershipBlock', 'formRule'), $this->_id);
    }
    $price = CRM_Price_BAO_PriceSet::getAssoc(FALSE, 'CiviMember');
    if (CRM_Utils_System::isNull($price)) {
      $this->assign('price', FALSE);
    }
    else {
      $this->assign('price', TRUE);
    }
    $this->add('select', 'member_price_set_id', ts('Membership Price Set'), (array('' => ts('- none -')) + $price));

    $session = CRM_Core_Session::singleton();
    $single = $session->get('singleForm');
    if ($single) {
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => ts('Save'),
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'isDefault' => TRUE,
          ),
          array(
            'type' => 'cancel',
            'name' => ts('Cancel'),
          ),
        )
      );
    }
    else {
      parent::buildQuickForm();
    }
  }

  /**
   * Function for validation
   *
   * @param array $params (ref.) an assoc array of name/value pairs
   *
   * @param $files
   * @param null $contributionPageId
   *
   * @return mixed true or array of errors
   * @access public
   * @static
   */
  static function formRule($params, $files, $contributionPageId = NULL) {
    $errors = array();

    if (!empty($params['member_price_set_id'])) {
      //check if this price set has membership type both auto-renew and non-auto-renew memberships.
      $bothTypes =  CRM_Price_BAO_PriceSet::checkMembershipPriceSet($params['member_price_set_id']);

      //check for supporting payment processors
      //if both auto-renew and non-auto-renew memberships
      if ($bothTypes) {
          $paymentProcessorIds = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage',
            $contributionPageId, 'payment_processor'
            );

          $paymentProcessorId = explode(CRM_Core_DAO::VALUE_SEPARATOR, $paymentProcessorIds);

        if (!empty($paymentProcessorId)) {
          $paymentProcessorType = CRM_Core_PseudoConstant::paymentProcessorType(false, null, 'name');
          foreach($paymentProcessorId as $pid) {
            if ($pid) {
              $paymentProcessorTypeId = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessor',
                $pid, 'payment_processor_type_id'
                );
            }
            if (!($paymentProcessorTypeId == CRM_Utils_Array::key('PayPal', $paymentProcessorType) ||
              ($paymentProcessorTypeId == CRM_Utils_Array::key('AuthNet', $paymentProcessorType)))) {
              $errors['member_price_set_id'] = ts('The membership price set associated with this online contribution allows a user to select BOTH an auto-renew AND a non-auto-renew membership. This requires submitting multiple processor transactions, and is not supported for one or more of the payment processors enabled under the Fees tab.');
            }

          }
        }
      }
    }
    if (!empty($params['member_is_active'])) {

      // don't allow price set w/ membership signup, CRM-5095
      if ($contributionPageId && ($setID = CRM_Price_BAO_PriceSet::getFor('civicrm_contribution_page', $contributionPageId, NULL, 1))) {

        $extends = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $setID, 'extends');
        if ($extends != CRM_Core_Component::getComponentID('CiviMember')) {
          $errors['member_is_active'] = ts('You cannot enable both Membership Signup and a Contribution Price Set on the same online contribution page.');
          return $errors;
        }
      }

      if ($contributionPageId && !empty($params['member_price_set_id']) &&
        CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_ContributionPage', $contributionPageId, 'amount_block_is_active')) {
        $errors['member_price_set_id'] = ts('You cannot use Membership Price Sets with the Contribution Amounts section. However, a membership price set may include additional fields for non-membership options that requires an additional fee (e.g. magazine subscription) or an additional voluntary contribution.');
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
        $membershipType = array_values($params['membership_type']);
        if (array_sum($membershipType) == 0) {
          $errors['membership_type'] = ts('Please select at least one Membership Type to include in the Membership section of this page.');
        }
        elseif (array_sum($membershipType) > CRM_Price_Form_Field::NUM_OPTION) {
          // for CRM-13079
          $errors['membership_type'] = ts('You cannot select more than %1 choices. For more complex functionality, please use a Price Set.', array(1 => CRM_Price_Form_Field::NUM_OPTION));
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
   * Process the form
   *
   * @return void
   * @access public
   */
  public function postProcess() {
    // get the submitted form values.
    $params = $this->controller->exportValues($this->_name);
    $deletePriceSet = 0;
    if ($params['membership_type']) {
      // we do this in case the user has hit the forward/back button
      $dao               = new CRM_Member_DAO_MembershipBlock();
      $dao->entity_table = 'civicrm_contribution_page';
      $dao->entity_id    = $this->_id;
      $dao->find(TRUE);
      $membershipID = $dao->id;
      if ($membershipID) {
        $params['id'] = $membershipID;
      }

      $membershipTypes = array();
      if (is_array($params['membership_type'])) {
        foreach ($params['membership_type'] as $k => $v) {
          if ($v) {
            $membershipTypes[$k] = CRM_Utils_Array::value("auto_renew_$k", $params);
          }
        }
      }

      // check for price set.
      $priceSetID = CRM_Utils_Array::value('member_price_set_id', $params);
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
            $timeSec = explode(".", microtime(true));
            $setParams['name'] = $pageTitle . '_' . date('is', $timeSec[0]) . $timeSec[1];
          }
          $setParams['is_quick_config'] = 1;
          $setParams['extends'] = CRM_Core_Component::getComponentID('CiviMember');
          $setParams['financial_type_id'] = CRM_Utils_Array::value( 'financial_type_id', $this->_values );
          $priceSet = CRM_Price_BAO_PriceSet::create($setParams);
          $priceSetID = $priceSet->id;
          $fieldParams['price_set_id'] = $priceSet->id;
        }
        elseif ($usedPriceSetId) {
          $setParams['extends'] = CRM_Core_Component::getComponentID('CiviMember');
          $setParams['financial_type_id'] = CRM_Utils_Array::value( 'financial_type_id', $this->_values );
          $setParams['id'] = $usedPriceSetId;
          $priceSet = CRM_Price_BAO_PriceSet::create($setParams);
          $priceSetID = $priceSet->id;
          $fieldParams['price_set_id'] = $priceSet->id;
        }
        else {
          $fieldParams['id'] = CRM_Utils_Array::value('mem_price_field_id', $params);
          $priceSetID = CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceField', CRM_Utils_Array::value('mem_price_field_id', $params), 'price_set_id');
        }
        $editedFieldParams = array(
          'price_set_id' => $priceSetID,
          'name' => 'membership_amount',
        );
        $editedResults = array();
        CRM_Price_BAO_PriceField::retrieve($editedFieldParams, $editedResults);
        if (empty($editedResults['id'])) {
          $fieldParams['name'] = strtolower(CRM_Utils_String::munge('Membership Amount', '_', 245));
          $fieldParams['label'] = !empty($params['new_title']) ? $params['new_title'] : ts('Membership');
          if (empty($params['mem_price_field_id'])) {
            CRM_Utils_Weight::updateOtherWeights('CRM_Price_DAO_PriceField', 0, 1, array('price_set_id' => $priceSetID));
          }
          $fieldParams['weight'] = 1;
        }
        else {
          $fieldParams['id'] = CRM_Utils_Array::value('id', $editedResults);
        }

        $fieldParams['is_active'] = 1;
        $fieldParams['html_type'] = 'Radio';
        $fieldParams['is_required'] = !empty($params['is_required']) ? 1 : 0;
        $fieldParams['is_display_amounts'] = !empty($params['display_min_fee']) ? 1 : 0;
        $rowCount = 1;
        $options = array();
        if (!empty($fieldParams['id'])) {
          CRM_Core_PseudoConstant::populate($options, 'CRM_Price_DAO_PriceFieldValue', TRUE, 'membership_type_id', NULL, " price_field_id = {$fieldParams['id']} ");
        }

        foreach ($membershipTypes as $memType => $memAutoRenew) {
          if ($priceFieldID = CRM_Utils_Array::key($memType, $options)) {
            $fieldParams['option_id'][$rowCount] = $priceFieldID;
            unset($options[$priceFieldID]);
          }
          $membetype = CRM_Member_BAO_MembershipType::getMembershipTypeDetails($memType);
          $fieldParams['option_label'][$rowCount]       = CRM_Utils_Array::value('name', $membetype);
          $fieldParams['option_amount'][$rowCount]      = CRM_Utils_Array::value('minimum_fee', $membetype, 0);
          $fieldParams['option_weight'][$rowCount]      = CRM_Utils_Array::value('weight', $membetype);
          $fieldParams['option_description'][$rowCount] = CRM_Utils_Array::value('description', $membetype);
          $fieldParams['default_option']                = CRM_Utils_Array::value('membership_type_default', $params);
          $fieldParams['option_financial_type_id'] [$rowCount] = CRM_Utils_Array::value('financial_type_id', $membetype);

          $fieldParams['membership_type_id'][$rowCount] = $memType;
          // [$rowCount] = $membetype[''];
          $rowCount++;
        }
        foreach ($options as $priceFieldID => $memType) {
          CRM_Price_BAO_PriceFieldValue::setIsActive($priceFieldID, '0');
        }
        $priceField = CRM_Price_BAO_PriceField::create($fieldParams);
      }
      elseif (!$priceSetID){
        $deletePriceSet = 1;
      }

      $params['is_required'] = CRM_Utils_Array::value('is_required', $params, FALSE);
      $params['is_active'] = CRM_Utils_Array::value('member_is_active', $params, FALSE);

      if ($priceSetID) {
        $params['membership_types'] = 'null';
        $params['membership_type_default'] = CRM_Utils_Array::value('membership_type_default', $params, 'null');
        $params['membership_types']        = serialize( $membershipTypes );
        $params['display_min_fee']         = CRM_Utils_Array::value('display_min_fee', $params, FALSE);
        $params['is_separate_payment']     = CRM_Utils_Array::value('is_separate_payment', $params, FALSE);
      }
      $params['entity_table'] = 'civicrm_contribution_page';
      $params['entity_id'] = $this->_id;

      $dao = new CRM_Member_DAO_MembershipBlock();
      $dao->copyValues($params);
      $dao->save();

      if ($priceSetID && $params['is_active']) {
        CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $this->_id, $priceSetID);
      }

      if ($deletePriceSet || !CRM_Utils_Array::value('member_is_active', $params, FALSE)) {

        if ($this->_memPriceSetId) {
          $pFIDs = array();
          $conditionParams = array(
            'price_set_id' => $this->_memPriceSetId,
            'html_type' => 'radio',
            'name' => 'contribution_amount',
          );

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
   * @access public
   */
  public function getTitle() {
    return ts('Memberships');
  }
}

