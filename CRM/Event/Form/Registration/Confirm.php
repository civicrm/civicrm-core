<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.2                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2012                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2012
 * $Id$
 *
 */

/**
 * This class generates form components for processing Event
 *
 */
class CRM_Event_Form_Registration_Confirm extends CRM_Event_Form_Registration {

  /**
   * the values for the contribution db object
   *
   * @var array
   * @protected
   */
  public $_values;

  /**
   * the total amount
   *
   * @var float
   * @public
   */
  public $_totalAmount;
  
  public $_ppTypeConfirm;

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */ function preProcess() {
    parent::preProcess();
    
    // BOT - get pptype from URL on payment processor selection action
    $this->_ppTypeConfirm = CRM_Utils_Array::value('type', $_GET);
   
    $this->assign('ppTypeConfirm', FALSE);
    if ($this->_ppTypeConfirm) {
      $this->assign('ppTypeConfirm', TRUE);
      return CRM_Core_Payment_ProcessorForm::preProcess($this);
    }
    //Ends
    
    // lineItem isn't set until Register postProcess
    $this->_lineItem = $this->get('lineItem');
    $this->_params = $this->get('params');
   
    $this->_params[0]['is_pay_later'] = $this->get('is_pay_later');
    $this->assign('is_pay_later', $this->_params[0]['is_pay_later']);
   
    if ($this->_params[0]['is_pay_later']) {
      $this->assign('pay_later_receipt', $this->_values['event']['pay_later_receipt']);
    }

    CRM_Utils_Hook::eventDiscount($this, $this->_params);

    if (CRM_Utils_Array::value('discount', $this->_params[0]) &&
      CRM_Utils_Array::value('applied', $this->_params[0]['discount'])
    ) {
      $this->set('hookDiscount', $this->_params[0]['discount']);
      $this->assign('hookDiscount', $this->_params[0]['discount']);
    }

    $config = CRM_Core_Config::singleton();
    if ($this->_contributeMode == 'express') {
      $params = array();
      // rfp == redirect from paypal
      $rfp = CRM_Utils_Request::retrieve('rfp', 'Boolean',
        CRM_Core_DAO::$_nullObject, FALSE, NULL, 'GET'
      );

      //we lost rfp in case of additional participant. So set it explicitly.
      if ($rfp || CRM_Utils_Array::value('additional_participants', $this->_params[0], FALSE)) {
        $payment = CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);
        $paymentObjError = ts('The system did not record payment details for this payment and so could not process the transaction. Please report this error to the site administrator.');
        if (is_object($payment)) 
          $expressParams = $payment->getExpressCheckoutDetails($this->get('token'));
        else
          CRM_Core_Error::fatal($paymentObjError);
        
        $params['payer'] = $expressParams['payer'];
        $params['payer_id'] = $expressParams['payer_id'];
        $params['payer_status'] = $expressParams['payer_status'];

        CRM_Core_Payment_Form::mapParams($this->_bltID, $expressParams, $params, FALSE);

        // fix state and country id if present
        if (isset($params["billing_state_province_id-{$this->_bltID}"])) {
          $params["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($params["billing_state_province_id-{$this->_bltID}"]);
        }
        if (isset($params['billing_country_id'])) {
          $params["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($params["billing_country_id-{$this->_bltID}"]);
        }

        // set a few other parameters for PayPal
        $params['token'] = $this->get('token');
        $params['amount'] = $this->_params[0]['amount'];
        if (CRM_Utils_Array::value('discount', $this->_params[0])) {
          $params['discount'] = $this->_params[0]['discount'];
          $params['discountAmount'] = $this->_params[0]['discountAmount'];
          $params['discountMessage'] = $this->_params[0]['discountMessage'];
        }
        $params['amount_level'] = $this->_params[0]['amount_level'];
        $params['currencyID'] = $this->_params[0]['currencyID'];
        $params['payment_action'] = 'Sale';

        // also merge all the other values from the profile fields
      //  $values = $this->controller->exportValues('Register');
        $skipFields = array(
          'amount',
          "street_address-{$this->_bltID}",
          "city-{$this->_bltID}",
          "state_province_id-{$this->_bltID}",
          "postal_code-{$this->_bltID}",
          "country_id-{$this->_bltID}",
        );

        foreach ($values as $name => $value) {
          // skip amount field
          if (!in_array($name, $skipFields)) {
            $params[$name] = $value;
          }
        }
        $this->set('getExpressCheckoutDetails', $params);
      }
      else {
        $params = $this->get('getExpressCheckoutDetails');
      }
      $this->_params[0] = $params;
      $this->_params[0]['is_primary'] = 1;
    }
    else {
      //process only primary participant params.
      $registerParams = $this->_params[0];
      if (isset($registerParams["billing_state_province_id-{$this->_bltID}"])
        && $registerParams["billing_state_province_id-{$this->_bltID}"]
      ) {
        $registerParams["billing_state_province-{$this->_bltID}"] = CRM_Core_PseudoConstant::stateProvinceAbbreviation($registerParams["billing_state_province_id-{$this->_bltID}"]);
      }

      if (isset($registerParams["billing_country_id-{$this->_bltID}"]) && $registerParams["billing_country_id-{$this->_bltID}"]) {
        $registerParams["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($registerParams["billing_country_id-{$this->_bltID}"]);
      }
      if (isset($registerParams['credit_card_exp_date'])) {
        $registerParams['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($registerParams);
        $registerParams['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($registerParams);
      }
      if ($this->_values['event']['is_monetary']) {
        $registerParams['ip_address'] = CRM_Utils_System::ipAddress();
        $registerParams['currencyID'] = $this->_params[0]['currencyID'];
        $registerParams['payment_action'] = 'Sale';
      }
      //assign back primary participant params.
      $this->_params[0] = $registerParams;
    }

    if ($this->_values['event']['is_monetary']) {
      $this->_params[0]['invoiceID'] = $this->get('invoiceID');
    }
    $this->assign('defaultRole', FALSE);
    if (CRM_Utils_Array::value('defaultRole', $this->_params[0]) == 1) {
      $this->assign('defaultRole', TRUE);
    }

    if (!CRM_Utils_Array::value('participant_role_id', $this->_params[0]) &&
      $this->_values['event']['default_role_id']
    ) {
      $this->_params[0]['participant_role_id'] = $this->_values['event']['default_role_id'];
    }

    if (isset($this->_values['event']['confirm_title'])) {
      CRM_Utils_System::setTitle($this->_values['event']['confirm_title']);
    }

    if ($this->_pcpId) {
      $params = CRM_Contribute_Form_Contribution_Confirm::processPcp($this, $this->_params[0]);
      $this->_params[0] = $params;
    }
    
    $this->set('params', $this->_params);
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
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    //BOT - Add payment fields to the confirm page
    //If ppTypeConfirm is set then call to payment processor build form and return
    if ($this->_ppTypeConfirm) {
      $this->assign('display_name', $display_name);
      CRM_Core_Payment_ProcessorForm::buildQuickForm($this);
      return;
    }
    
    //if payment fields have to be shown on confirm page
    $this->_showPaymentFields = TRUE;
    if ( $this->_params[0]['showPaymentFields'] ) {
      $this->_showPaymentFields = False;
    }    

    $this->assign('showPaymentFields', $this->_showPaymentFields);
    if ( $this->_showPaymentFields ) {
      $this->_bypassPayment = FALSE;
      //Call to function to add payment fields to the form
      require_once 'CRM/Event/Form/Registration/Register.php';           
      CRM_Event_Form_Registration_Register::buildPaymentFields( $this );

      //Add the elements to the confirm page so as to match the errors with form elements
      $this->addElement('text', 'credit_card_type', NULL, true);
      $this->addElement('text', 'credit_card_number', NULL);
      $this->addElement('text', 'cvv2', NULL);
      $this->addElement('text', 'credit_card_exp_date', NULL);
      $this->addElement('text', 'billing_first_name', NULL);
      $this->addElement('text', 'billing_last_name', NULL);
      $this->addElement('text', 'billing_street_address-5', NULL);
      $this->addElement('text', 'billing_city-5', NULL);
      $this->addElement('text', 'billing_country_id-5', NULL);
      $this->addElement('text', 'billing_postal_code-5', NULL);
      $this->addElement('text', 'billing_state_province_id-5', NULL);
     
      $params = $this->get('params');
      //Add an element for selecting the payer for the event fees
	  $payerOptions[''] = 'Please Choose...';
      $payerOptions['payer_not_attending'] = 'Payer is not attending';
      
      foreach( $params as $k => $v ) {
         if ( (isset($v['first_name']) && $v['first_name']) || (isset($v['last_name']) && $v['last_name']) ) {
          $payerOptions[$k] = $v['first_name'].' '.$v['last_name'];
        } else {
            $payerOptions[$k] = $v['email-Primary'];
        }
        
      }
      
      $this->add('select', 'payer_id',
          ts('Which attendee is paying for the registration'),
          $payerOptions,
          TRUE
      );

      $this->add('text', 'receipt_email', ts('Payer email address'), array('size'=>35), FALSE);
      $this->assign('isConfirm', '1');
    }
    // ends
    
    $this->assignToTemplate();
    if ($this->_params[0]['amount'] || $this->_params[0]['amount'] == 0) {
      $this->_amount = array();
      
      foreach ($this->_params as $k => $v) {
        if (is_array($v)) {
          foreach (array(
            'first_name', 'last_name') as $name) {
            if (isset($v['billing_' . $name]) &&
              !isset($v[$name])
            ) {
              $v[$name] = $v['billing_' . $name];
              $this->assign('billing_' . $name);
            }
          }

          if (CRM_Utils_Array::value('first_name', $v) && CRM_Utils_Array::value('last_name', $v)) {
            $append = $v['first_name'] . ' ' . $v['last_name'];
          }
          else {
            //use an email if we have one
            foreach ($v as $v_key => $v_val) {
              if (substr($v_key, 0, 6) == 'email-') {
                $append = $v[$v_key];
              }
            }
          }

          $this->_amount[$k]['amount'] = $v['amount'];
          if (CRM_Utils_Array::value('discountAmount', $v)) {
            $this->_amount[$k]['amount'] -= $v['discountAmount'];
          }

          $this->_amount[$k]['label'] = preg_replace('//', '', $v['amount_level']) . '  -  ' . $append;
          $this->_part[$k]['info'] = CRM_Utils_Array::value('first_name', $v) . ' ' . CRM_Utils_Array::value('last_name', $v);
          if (!CRM_Utils_Array::value('first_name', $v)) {
            $this->_part[$k]['info'] = $append;
          }
          $this->_totalAmount = $this->_totalAmount + $this->_amount[$k]['amount'];
          if (CRM_Utils_Array::value('is_primary', $v)) {
            $this->set('primaryParticipantAmount', $this->_amount[$k]['amount']);
          }
        }
      }

      $this->assign('part', $this->_part);
      
      $this->set('part', $this->_part);
      $this->assign('amounts', $this->_amount);
      $this->assign('totalAmount', $this->_totalAmount);
      $this->set('totalAmount', $this->_totalAmount);
    }

    $config = CRM_Core_Config::singleton();

    $this->buildCustom($this->_values['custom_pre_id'], 'customPre', TRUE);
    $this->buildCustom($this->_values['custom_post_id'], 'customPost', TRUE);

    if ($this->_priceSetId && !CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set', $this->_priceSetId, 'is_quick_config')) {

      $this->assign('lineItem', $this->_lineItem);

    }
    //display additional participants profile.

    $participantParams = $this->_params;
    $formattedValues   = array();
    $count             = 1;
    foreach ($participantParams as $participantNum => $participantValue) {
      if ($participantNum && $participantValue != 'skip') {
        //get the customPre profile info
        if (CRM_Utils_Array::value('additional_custom_pre_id', $this->_values)) {
          $values = $groupName = array();
          CRM_Event_BAO_Event::displayProfile($participantValue,
            $this->_values['additional_custom_pre_id'],
            $groupName,
            $values
          );

          if (count($values)) {
            $formattedValues[$count]['additionalCustomPre'] = $values;
          }
          $formattedValues[$count]['additionalCustomPreGroupTitle'] = CRM_Utils_Array::value('groupTitle', $groupName);
        }
        //get the customPost profile info
        if (CRM_Utils_Array::value('additional_custom_post_id', $this->_values)) {
          $values = $groupName = array();
          foreach ($this->_values['additional_custom_post_id'] as $gids) {
            $val = array();
            CRM_Event_BAO_Event::displayProfile($participantValue,
              $gids,
              $group,
              $val
            );
            $values[$gids] = $val;
            $groupName[$gids] = $group;
          }

          if (count($values)) {
            $formattedValues[$count]['additionalCustomPost'] = $values;
          }

          if (isset($formattedValues[$count]['additionalCustomPre'])) {
            $formattedValues[$count]['additionalCustomPost'] = array_diff_assoc($formattedValues[$count]['additionalCustomPost'],
              $formattedValues[$count]['additionalCustomPre']
            );
          }

          $formattedValues[$count]['additionalCustomPostGroupTitle'] = $groupName;
        }
        $count++;
      }
    }

    if (!empty($formattedValues) && $count > 1) {
      $this->assign('addParticipantProfile', $formattedValues);
      $this->set('addParticipantProfile', $formattedValues);
    }

    //cosider total amount.
    $this->assign('isAmountzero', ($this->_totalAmount <= 0) ? TRUE : FALSE);

    if ($this->_paymentProcessor['payment_processor_type'] == 'Google_Checkout' &&
      !CRM_Utils_Array::value('is_pay_later', $this->_params[0]) && !($this->_params[0]['amount'] == 0) &&
      !$this->_allowWaitlist && !$this->_requireApproval
    ) {
      $this->_checkoutButtonName = $this->getButtonName('next', 'checkout');
      $this->add('image',
        $this->_checkoutButtonName,
        $this->_paymentProcessor['url_button'],
        array('class' => 'form-submit')
      );

      $this->addButtons(array(
          array(
            'type' => 'back',
            'name' => ts('<< Go Back'),
          ),
        )
      );
    }
    else {
      //BOT - To change the button name to Make payment if payment fields are on confirm page
      if ( $this->_showPaymentFields ) {
        $contribButton = ts('Make Payment');
      } else {
        $contribButton = ts('Continue');
      }
      //ends
      $this->addButtons(array(
          array(
            'type' => 'next',
            'name' => $contribButton,
            'isDefault' => TRUE,
            'js' => array('onclick' => "return submitOnce(this,'" . $this->_name . "','" . ts('Processing') . "');"),
          ),
          array(
            'type' => 'back',
            'spacing' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
            'name' => ts('Go Back'),
          ),
        )
      );
    }

    $defaults = array();
    $fields = array();
    if (!empty($this->_fields)) {
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }
    }
    $fields["billing_state_province-{$this->_bltID}"] = $fields["billing_country-{$this->_bltID}"] = $fields["email-{$this->_bltID}"] = 1;
    foreach ($fields as $name => $dontCare) {
      if (isset($this->_params[0][$name])) {
        $defaults[$name] = $this->_params[0][$name];
        if (substr($name, 0, 7) == 'custom_') {
          $timeField = "{$name}_time";
          if (isset($this->_params[0][$timeField])) {
            $defaults[$timeField] = $this->_params[0][$timeField];
          }
          if (isset($this->_params[0]["{$name}_id"])) {
            $defaults["{$name}_id"] = $this->_params[0]["{$name}_id"];
          }
        }
        elseif (in_array($name, CRM_Contact_BAO_Contact::$_greetingTypes)
          && !empty($this->_params[0][$name . '_custom'])
        ) {
          $defaults[$name . '_custom'] = $this->_params[0][$name . '_custom'];
        }
      }
    }

    // now fix all state country selectors
    CRM_Core_BAO_Address::fixAllStateSelects($this, $defaults);

    $this->setDefaults($defaults);
    $this->freeze();

    
    //BOT - To unfreeze the fields on the form and add form rule
    if ( $this->_showPaymentFields ) {
      //Unfreeze the payment fields
      foreach( $this->_elements as $key => $val ) {
        if ( $val->_name ) {
          if ( $val->_name == 'payment_processor' ) {
            foreach( $val->_elements as $element ) {
              $element->_flagFrozen = 0;
            }
          } 
        }
        if( $val->_attributes['name'] == 'payer_id') {
            $val->_flagFrozen = 0;          
        }
 if( $val->_attributes['name'] == 'receipt_email') {
          $val->_flagFrozen = 0;
        }
      }
      
      $this->addFormRule(array('CRM_Event_Form_Registration_Confirm', 'formRule'),
          $this
      );
    }  
    //Ends
    

    //
    //lets give meaningful status message, CRM-4320.
    $this->assign('isOnWaitlist', $this->_allowWaitlist);
    $this->assign('isRequireApproval', $this->_requireApproval);

    // Assign Participant Count to Lineitem Table
    $this->assign('pricesetFieldsCount', CRM_Price_BAO_Set::getPricesetCount($this->_priceSetId));
  }

  //BOT - Form rule added to handle the payment fields validation 
  static function formRule($fields, $files, $self) {
   if ( !isset($fields['_qf_Confirm_back']) ) {
    $errors = array();
    if ( $self->_showPaymentFields ) {
      if ( !isset($fields['payment_processor']) ) {
        $errors['payment_processor'] = ts('Payment method is a required field.');
      }
    }
    
    if( $fields['payment_processor'] ) {
      $required = array(
          'credit_card_type'   => 'Credit Card Type',
          'credit_card_number' => 'Credit Card Number',
          'cvv2'               => 'CVV',
          'billing_first_name' => 'Billing First Name',
          'billing_last_name' => 'Billing Last Name',
          'billing_street_address-5'  => 'Billing Street Address',
          'billing_city-5' => 'City',
          'billing_state_province_id-5' => 'State Province',
          'billing_postal_code-5' => 'Postal Code',
          'billing_country_id-5' => 'Country'
      );
       
      foreach ( $required as $name => $fld) {
        if ( !$fields[$name] ) {
          $errors[$name] = ts('%1 is a required field.', array(1 => $fld) );
         
        }
      }
    }

  if ( $fields['payer_id'] == 'payer_not_attending' ) {
       if (  !$fields['receipt_email'] ) {
         $errors['receipt_email'] = ts('Receipt Email is a required field.');
       }
       
     }
    // make sure that credit card number and cvv are valid
    if (CRM_Utils_Array::value('credit_card_type', $fields)) {
      if (CRM_Utils_Array::value('credit_card_number', $fields) &&
          !CRM_Utils_Rule::creditCardNumber($fields['credit_card_number'], $fields['credit_card_type'])
      ) {
       
        $errors['credit_card_number'] = ts('Please enter a valid Credit Card Number');
      }
    
      if (CRM_Utils_Array::value('cvv2', $fields) &&
          !CRM_Utils_Rule::cvv($fields['cvv2'], $fields['credit_card_type'])
      ) {
        $errors['cvv2'] = ts('Please enter a valid Credit Card Verification Number');
      }
      
      $currentDate = date('MY');
      $expDate     = date('MY', strtotime('01'.'-'.$fields['credit_card_exp_date']['M'].'-'.$fields['credit_card_exp_date']['Y']));     
      if ( !$fields['credit_card_exp_date']['M'] && !$fields['credit_card_exp_date']['Y'] ) {
        $errors['credit_card_exp_date'] = ts('Card Expiration is a required field.' );
      } elseif ( ( strtotime($currentDate) > strtotime( $expDate ) )) {
        $errors['credit_card_exp_date'] = ts('Credit card expiration date cannot be a past date.' );
      }
    }
  }
    return empty($errors) ? TRUE : $errors;
  }
  //Ends
  
  /**
   * Function to process the form
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $now           = date('YmdHis');
    $config        = CRM_Core_Config::singleton();
    $session       = CRM_Core_Session::singleton();
    $this->_params = $this->get('params');
    //BOT - handle the changes in post process
    //Instead for 'isPrimary' check for 'isPayer' in the code
    $params = $this->controller->exportValues($this->_name);
    unset($params['email-Primary']);
    unset($params['first_name']);
    unset($params['last_name']);
    $isPayLater = 0;
    if( $params['payment_processor'] == 0 ) {
      $isPayLater = 1;
    }
     
     $payer_email = $params['payer_id'];
     
     $isPayerAttending = TRUE;
     if ( $payer_email == 'payer_not_attending' ) {
       $isPayerAttending                       = FALSE;
       $this->_params['payer']                 = '';
       $this->_params['payer']                 = $params;
       $this->_params['payer']['is_pay_later'] = $isPayLater;
       $this->_params['payer']['is_payer']     = 1;
       $statuses                               = CRM_Event_PseudoConstant::participantStatus();
       $status                                 = 'Payer not attending';
       $this->_params['payer']['participant_status_id'] = $this->_params['payer']['participant_status'] = array_search($status, $statuses);
       $mapParams = array('is_primary', 'email-Primary', 'is_pay_later', 'contact_id', 'campaign_id');
       foreach ( $this->_params[0] as $k => $v ) {         
         if( !in_array( $k, $mapParams ) ) {
           $this->_params['payer'][$k] = $v;            
         }                 
       }
       $this->_params['payer']['first_name'] = $this->_params['payer']['billing_first_name'];
       $this->_params['payer']['last_name']  = $this->_params['payer']['billing_last_name'];
       $payer = 'payer';
       $participantRoles = CRM_Event_PseudoConstant::participantRole();
       $this->_params['payer']['participant_role_id'] = array_search('Payer', $participantRoles);
 $this->_params['payer']['email-Primary'] = $this->_params['payer']['receipt_email'];
     } 
         
     foreach ($this->_params as $k => $v) { 
       if ( $key != 'payer' || $key === 0 ) {
       // unset( $this->_params[$k][is_pay_later] );
         $this->_params[$k]['is_pay_later'] = $isPayLater;
       }
     
        if ( $isPayerAttending ) {
           if ( $k == $payer_email ) {
             $this->_params[$k]['is_payer']     = 1;
             $this->_params[$k]                 = array_merge($this->_params[$k] , $params);
             $this->_params[$k]['is_pay_later'] = $isPayLater;
             /*
              * BOT - changed to store the original amount of participant 
              *  as it gets overwritten further with the total amount to be paid for all participants
              */
             $this->_params[$k]['original_amount'] = $this->_params[$k]['amount']; 
             $payer = $k;
            }       
         }   
     }

     $temp[$payer] = $this->_params[$payer];
     unset($this->_params[$payer]);
     $this->_params = $temp + $this->_params;
   //Ends
    
    if (CRM_Utils_Array::value('contact_id', $this->_params[$payer])) {
      $contactID = $this->_params[$payer]['contact_id'];
    }
    else {
      $contactID = parent::getContactID();
    }
     
    // if a discount has been applied, lets now deduct it from the amount
    // and fix the fee level
    if (CRM_Utils_Array::value('discount', $this->_params[$payer]) &&
      CRM_Utils_Array::value('applied', $this->_params[$payer]['discount'])
    ) {
      foreach ($this->_params as $k => $v) {
        if (CRM_Utils_Array::value('amount', $this->_params[$k]) > 0 &&
          CRM_Utils_Array::value('discountAmount', $this->_params[$k])
        ) {
          $this->_params[$k]['amount'] -= $this->_params[$k]['discountAmount'];
          $this->_params[$k]['amount_level'] .= CRM_Utils_Array::value('discountMessage', $this->_params[$k]);
        }
      }
      
    }
$this->set('params', $this->_params);
 $this->assignToTemplate();
    // CRM-4320, lets build array of cancelled additional participant ids
    // those are drop or skip by primary at the time of confirmation.
    // get all in and then unset those we want to process.
    $cancelledIds = $this->_additionalParticipantIds;

    $params = $this->_params;
    $this->set('finalAmount', $this->_amount);
    $participantCount = array();

    //unset the skip participant from params.
    //build the $participantCount array.
    //maintain record for all participants.
    foreach ($params as $participantNum => $record) {
      if ($record == 'skip') {
        unset($params[$participantNum]);
        $participantCount[$participantNum] = 'skip';
      }
      elseif (  $participantNum || $participantNum === 0  ) {
        if ( $participantNum === 'payer') {
           continue;
        }
        $participantCount[$participantNum] = 'participant';
      }

      //lets get additional participant id to cancel.
      if ($this->_allowConfirmation && is_array($cancelledIds)) {
        $additonalId = CRM_Utils_Array::value('participant_id', $record);
        if ($additonalId && $key = array_search($additonalId, $cancelledIds)) {
          unset($cancelledIds[$key]);
        }
      }
    }

    $payment = $registerByID = $primaryCurrencyID = $contribution = NULL;
    $paymentObjError = ts('The system did not record payment details for this payment and so could not process the transaction. Please report this error to the site administrator.');

    $this->participantIDS = array();
 
   
    foreach ($params as $key => $value) {
     $this->fixLocationFields($value, $fields);
      //unset the billing parameters if it is pay later mode
      //to avoid creation of billing location

      //To set credit card parameters if payment fields are on confirm page
       if ( $this->_showPaymentFields && CRM_Utils_Array::value('is_payer', $value ) ) {
         if (isset($value["billing_state_province_id-{$this->_bltID}"])
         && $value["billing_state_province_id-{$this->_bltID}"]
         ) {
           $value["billing_state_province-{$this->_bltID}"] =                 CRM_Core_PseudoConstant::stateProvinceAbbreviation($value["billing_state_province_id-{$this->_bltID}"]);
       }
       
         if (isset($value["billing_country_id-{$this->_bltID}"]) && $value["billing_country_id-{$this->_bltID}"]) {
           $value["billing_country-{$this->_bltID}"] = CRM_Core_PseudoConstant::countryIsoCode($value["billing_country_id-{$this->_bltID}"]);
       }
         if (isset($value['credit_card_exp_date'])) {
           $value['year'] = CRM_Core_Payment_Form::getCreditCardExpirationYear($value);
           $value['month'] = CRM_Core_Payment_Form::getCreditCardExpirationMonth($value);
         }       
     }


      if ($this->_allowWaitlist || $this->_requireApproval ||
        CRM_Utils_Array::value('is_pay_later', $value) || !CRM_Utils_Array::value('is_payer', $value)
      ) {
        $billingFields = array(
          "email-{$this->_bltID}",
          "billing_first_name",
          "billing_middle_name",
          "billing_last_name",
          "billing_street_address-{$this->_bltID}",
          "billing_city-{$this->_bltID}",
          "billing_state_province-{$this->_bltID}",
          "billing_state_province_id-{$this->_bltID}",
          "billing_postal_code-{$this->_bltID}",
          "billing_country-{$this->_bltID}",
          "billing_country_id-{$this->_bltID}",
          "address_name-{$this->_bltID}",
        );

        foreach ($billingFields as $field) {
          unset($value[$field]);
        }
        if (CRM_Utils_Array::value('is_pay_later', $value)) {
          $this->_values['params']['is_pay_later'] = TRUE;
        }
      }
      
       //Unset ContactID for additional participants and set RegisterBy Id.
      if (!CRM_Utils_Array::value('is_payer', $value)) {
        $contactID = CRM_Utils_Array::value('contact_id', $value);   
        $registerByID = $this->get('registerByID');
        if ($registerByID) {
          $value['registered_by_id'] = $registerByID;
        }
      } 
      else {
        //Contact id reset for each loop iteration
        $contactID = CRM_Utils_Array::value('contact_id', $value);
        $value['amount'] = $this->_totalAmount;
      
      }
      $contactID = &$this->updateContactFields($contactID, $value, $fields);
 
      // lets store the contactID in the session
      // we dont store in userID in case the user is doing multiple
      // transactions etc
      // for things like tell a friend
      if (!parent::getContactID() && CRM_Utils_Array::value('is_payer', $value)) {
        $session->set('transaction.userID', $contactID);
      }

      $value['description'] = ts('Online Event Registration') . ': ' . $this->_values['event']['title'];
      $value['accountingCode'] = CRM_Utils_Array::value('accountingCode',
        $this->_values['event']
      );
  
      // required only if paid event
      if ($this->_values['event']['is_monetary']  ) {
        if (is_array($this->_paymentProcessor)) {
            $payment = CRM_Core_Payment::singleton($this->_mode, $this->_paymentProcessor, $this);
        }
        $pending = FALSE;
        $result = NULL;

        if ($this->_allowWaitlist || $this->_requireApproval) {
          //get the participant statuses.
          $waitingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Waiting'");
          if ($this->_allowWaitlist) {
            $value['participant_status_id'] = $value['participant_status'] = array_search('On waitlist', $waitingStatuses);
          }
          else {
            $value['participant_status_id'] = $value['participant_status'] = array_search('Awaiting approval', $waitingStatuses);
          }

          //there might be case user seleted pay later and
          //now becomes part of run time waiting list.
          $value['is_pay_later'] = FALSE;
        }
        elseif (CRM_Utils_Array::value('is_pay_later', $value) ||
          $value['amount'] == 0 ||
          $this->_contributeMode == 'checkout' ||
          $this->_contributeMode == 'notify'
        ) {
          if ($value['amount'] != 0) {
            $pending = TRUE;
            //get the participant statuses.
            $pendingStatuses = CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Pending'");
            $status = CRM_Utils_Array::value('is_pay_later', $value) ? 'Pending from pay later' : 'Pending from incomplete transaction';
			if ( $key != 'payer' || $key === 0 ) {
				$value['participant_status_id'] = $value['participant_status'] = array_search($status, $pendingStatuses);
			}
          }
        }
        elseif ($this->_contributeMode == 'express' && CRM_Utils_Array::value('is_payer', $value)) {
           if (is_object($payment)) 
            $result = &$payment->doExpressCheckout($value);
          else 
            CRM_Core_Error::fatal($paymentObjError);
        }
        elseif (CRM_Utils_Array::value('is_payer', $value)) {
          CRM_Core_Payment_Form::mapParams($this->_bltID, $value, $value, TRUE);

          if (is_object($payment)) {
            $result = &$payment->doDirectPayment($value);
          } else {
             CRM_Core_Error::fatal($paymentObjError);
          }
        }
     
        if (is_a($result, 'CRM_Core_Error')) {
         CRM_Core_Error::displaySessionError($result);
         CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/event/register', "id={$this->_eventId}"));
        }
       
        if ($result) {
          $value = array_merge($value, $result);
        }

        $value['receive_date'] = $now;
        if ($this->_allowConfirmation) {
          $value['participant_register_date'] = $this->_values['participant']['register_date'];
        }

        $createContrib = ($value['amount'] != 0) ? TRUE : FALSE;
        // force to create zero amount contribution, CRM-5095
        if (!$createContrib && ($value['amount'] == 0)
          && $this->_priceSetId && $this->_lineItem
        ) {
          $createContrib = TRUE;
        }

        if ($createContrib && CRM_Utils_Array::value('is_payer', $value) &&
          !$this->_allowWaitlist && !$this->_requireApproval
        ) {
          // if paid event add a contribution record
          //if primary participant contributing additional amount
          //append (multiple participants) to its fee level. CRM-4196.
          $isAdditionalAmount = FALSE;
          if (count($params) > 1) {
            $isAdditionalAmount = TRUE;
          }

       
          //passing contribution id is already registered.
          $contribution = &self::processContribution($this, $value, $result, $contactID,
            $pending, $isAdditionalAmount
          );

          $value['contributionID'] = $contribution->id;
          $value['contributionTypeID'] = $contribution->contribution_type_id;
          $value['receive_date'] = $contribution->receive_date;
          $value['trxn_id'] = $contribution->trxn_id;
          $value['contributionID'] = $contribution->id;
          $value['contributionTypeID'] = $contribution->contribution_type_id;
        }
        $value['contactID'] = $contactID;
        $value['eventID']   = $this->_eventId;
        $value['item_name'] = $value['description'];
      }
      
      //CRM-4453.
      if (CRM_Utils_Array::value('is_payer', $value)) {
        $primaryCurrencyID = CRM_Utils_Array::value('currencyID', $value);
      }
      if (!CRM_Utils_Array::value('currencyID', $value)) {
        $value['currencyID'] = $primaryCurrencyID;
      }
     
      if (!$pending && CRM_Utils_Array::value('is_payer', $value) &&
        !$this->_allowWaitlist && !$this->_requireApproval
      ) {
        // transactionID & receive date required while building email template
        $this->assign('trxn_id', $value['trxn_id']);
        $this->assign('receive_date', CRM_Utils_Date::mysqlToIso($value['receive_date']));
        $this->set('receiveDate', CRM_Utils_Date::mysqlToIso($value['receive_date']));
        $this->set('trxnId', CRM_Utils_Array::value('trxn_id', $value));
      }
      $value['fee_amount'] = $value['amount'];
      $this->set('value', $value);
       
      // handle register date CRM-4320
      if ($this->_allowConfirmation) {
        $registerDate = CRM_Utils_Array::value( 'participant_register_date', $params );
      }
      elseif (CRM_Utils_Array::value('participant_register_date', $params) &&
        is_array($params['participant_register_date']) &&
        !empty($params['participant_register_date'])
      ) {
        $registerDate = CRM_Utils_Date::format($params['participant_register_date']);
      }
      else {
        $registerDate = date('YmdHis');
      }
      $this->assign('register_date', $registerDate);
         
       $this->confirmPostProcess($contactID, $contribution, $payment );
    }
 
      //handle if no additional participant.
      if (!$registerByID) {
        $registerByID = $this->_participantIDS[0];
      }
      
      $this->set('participantIDs', $this->_participantIDS);
  
      // create line items, CRM-5313
      if ($this->_priceSetId &&
        !empty($this->_lineItem)
      ) {
  
        // take all processed participant ids.
        $allParticipantIds = $this->_participantIDS;
  
        // when participant re-walk wizard.
        if ($this->_allowConfirmation &&
          !empty($this->_additionalParticipantIds)
        ) {
          $allParticipantIds = array_merge(array($registerByID), $this->_additionalParticipantIds);
        }
         
         $pids = $allParticipantIds;
        $allParticipantIds = array();
        foreach( $pids as $key=> $val ) {
          if ($payer === 'payer' && $val == $registerByID )  {
            continue;
          } else {
            $allParticipantIds[] = $val;
          }
        }

        $entityTable = 'civicrm_participant';
        foreach ($this->_lineItem as $key => $value) {
          if (($value != 'skip') &&
            ($entityId = CRM_Utils_Array::value($key, $allParticipantIds))
          ) {
  
            // do cleanup line  items if participant re-walking wizard.
            if ($this->_allowConfirmation) {
              CRM_Price_BAO_LineItem::deleteLineItems($entityId, $entityTable);
            }
  
            // create line.
            foreach ($value as $line) {
              $line['entity_id'] = $entityId;
              $line['entity_table'] = $entityTable;
              CRM_Price_BAO_LineItem::create($line);
            }
          }
        }
      }
     
      //update status and send mail to cancelled additonal participants, CRM-4320
      if ($this->_allowConfirmation && is_array($cancelledIds) && !empty($cancelledIds)) {
        $cancelledId = array_search('Cancelled',
          CRM_Event_PseudoConstant::participantStatus(NULL, "class = 'Negative'")
        );
        CRM_Event_BAO_Participant::transitionParticipants($cancelledIds, $cancelledId);
      }
  
      $isTest = FALSE;
      if ($this->_action & CRM_Core_Action::PREVIEW) {
        $isTest = TRUE;
      }
  
      // for Transfer checkout.
      if (($this->_contributeMode == 'checkout' ||
          $this->_contributeMode == 'notify'
        ) &&
        !CRM_Utils_Array::value('is_pay_later', $params[0]) &&
        !$this->_allowWaitlist && !$this->_requireApproval &&
        $this->_totalAmount > 0
      ) {
  
        $primaryParticipant = $this->get('primaryParticipant');
  
        if (!CRM_Utils_Array::value('participantID', $primaryParticipant)) {
          $primaryParticipant['participantID'] = $registerByID;
        }
  
        //build an array of custom profile and assigning it to template
        $customProfile = CRM_Event_BAO_Event::buildCustomProfile($registerByID, $this->_values, NULL, $isTest);
        if (count($customProfile)) {
          $this->assign('customProfile', $customProfile);
          $this->set('customProfile', $customProfile);
        }
  
        // do a transfer only if a monetary payment greater than 0
        if ($this->_values['event']['is_monetary'] && $primaryParticipant) {
          if ($payment && is_object($payment)) 
            $payment->doTransferCheckout($primaryParticipant, 'event');
          else 
            CRM_Core_Error::fatal($paymentObjError);
        }
      }
      else {
        //otherwise send mail Confirmation/Receipt
        $primaryContactId = $this->get('primaryContactId');
      
        //build an array of cId/pId of participants
        $additionalIDs = CRM_Event_BAO_Event::buildCustomProfile($registerByID,
          NULL, $primaryContactId, $isTest,
          TRUE
        );
      //lets send  mails to all with meaningful text, CRM-4320.
        $this->assign('isOnWaitlist', $this->_allowWaitlist);
        $this->assign('isRequireApproval', $this->_requireApproval);
  
        //need to copy, since we are unsetting on the way.
        $copyParticipantCount = $participantCount;
  
        //lets carry all paticipant params w/ values.
        foreach ($additionalIDs as $participantID => $contactId) {
          $participantNum = NULL;
          if ($participantID == $registerByID) {
            $participantNum = 0;
          }
          else {
            if ($participantNum = array_search('participant', $copyParticipantCount)) {
              unset($copyParticipantCount[$participantNum]);
            }
          }
          if ($participantNum === NULL)
          break;
  
          //carry the participant submitted values.
          $this->_values['params'][$participantID] = $params[$participantNum];
        }
       
        foreach ($additionalIDs as $participantID => $contactId) {
          $participantNum = $payer;
          if ($participantID == $registerByID) {
            //set as Primary Participant
            $this->assign('isPrimary', 1);
            //build an array of custom profile and assigning it to template.
            $customProfile = CRM_Event_BAO_Event::buildCustomProfile($participantID, $this->_values, NULL, $isTest);
  
            if (count($customProfile)) {
              $this->assign('customProfile', $customProfile);
              $this->set('customProfile', $customProfile);
            }
            $this->_values['params']['additionalParticipant'] = FALSE;
          }
          else {
            //take the Additional participant number.
            $participantNum = array_search('participant', $participantCount);
              unset($participantCount[$participantNum]);
            
            $this->assign('isPrimary', 0);
            $this->assign('customProfile', NULL);
            //Additional Participant should get only it's payment information
            if ($this->_amount) {
              $amount = array();
              $params = $this->get('params');
              $amount[$participantNum]['label'] = $params[$participantNum]['amount_level'];
              $amount[$participantNum]['amount'] = $params[$participantNum]['amount'];
              $this->assign('amount', $amount);
            }
            if ($this->_lineItem) {
              $lineItems  = $this->_lineItem;
              $lineItem   = array();
              $lineItem[] = CRM_Utils_Array::value($participantNum, $lineItems);
              $this->assign('lineItem', $lineItem);
            }
            $this->_values['params']['additionalParticipant'] = TRUE;
          }
  
          //pass these variables since these are run time calculated.
          $this->_values['params']['isOnWaitlist'] = $this->_allowWaitlist;
          $this->_values['params']['isRequireApproval'] = $this->_requireApproval;
  
          //send mail to primary as well as additional participants.
          $this->assign('contactID', $contactId);
          $this->assign('participantID', $participantID);
          CRM_Event_BAO_Event::sendMail($contactId, $this->_values, $participantID, $isTest);
        }
      }
   }
  //end of function

  /**
   * Process the contribution
   *
   * @return void
   * @access public
   */
  static
  function processContribution(&$form, $params, $result, $contactID,
    $pending = FALSE, $isAdditionalAmount = FALSE
  ) {
    require_once 'CRM/Core/Transaction.php';
    $transaction = new CRM_Core_Transaction();

    $config      = CRM_Core_Config::singleton();
    $now         = date('YmdHis');
    $receiptDate = NULL;

    if ($form->_values['event']['is_email_confirm']) {
      $receiptDate = $now;
    }
    //CRM-4196
    if ($isAdditionalAmount) {
      $params['amount_level'] = $params['amount_level'] . ts(' (multiple participants)') . CRM_Core_DAO::VALUE_SEPARATOR;
    }

    $contribParams = array(
      'contact_id' => $contactID,
      'contribution_type_id' => $form->_values['event']['contribution_type_id'] ?
      $form->_values['event']['contribution_type_id'] : $params['contribution_type_id'],
      'receive_date' => $now,
      'total_amount' => $params['amount'],
      'amount_level' => $params['amount_level'],
      'invoice_id' => $params['invoiceID'],
      'currency' => $params['currencyID'],
      'source' => $params['description'],
      'is_pay_later' => CRM_Utils_Array::value('is_pay_later', $params, 0),
      'campaign_id' => CRM_Utils_Array::value('campaign_id', $params),
    );

    if (!CRM_Utils_Array::value('is_pay_later', $params)) {
      $contribParams['payment_instrument_id'] = 1;
    }

    if (!$pending && $result) {
      $contribParams += array(
        'fee_amount' => CRM_Utils_Array::value('fee_amount', $result),
        'net_amount' => CRM_Utils_Array::value('net_amount', $result, $params['amount']),
        'trxn_id' => $result['trxn_id'],
        'receipt_date' => $receiptDate,
      );
    }

    $allStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $contribParams['contribution_status_id'] = array_search('Completed', $allStatuses);
    if ($pending) {
      $contribParams['contribution_status_id'] = array_search('Pending', $allStatuses);
    }

    $contribParams['is_test'] = 0;
    if ($form->_action & CRM_Core_Action::PREVIEW || CRM_Utils_Array::value('mode', $params) == 'test') {
      $contribParams['is_test'] = 1;
    }

    $contribID = NULL;
    if (CRM_Utils_Array::value('invoice_id', $contribParams)) {
      $contribID = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
        $contribParams['invoice_id'],
        'id',
        'invoice_id'
      );
    }

    $ids = array();
    if ($contribID) {
      $ids['contribution'] = $contribID;
      $contribParams['id'] = $contribID;
    }

    //create an contribution address
    if ($form->_contributeMode != 'notify' && !CRM_Utils_Array::value('is_pay_later', $params)) {
      $contribParams['address_id'] = CRM_Contribute_BAO_Contribution::createAddress($params, $form->_bltID);
    }

    // Prepare soft contribution due to pcp or Submit Credit / Debit Card Contribution by admin.
    if (CRM_Utils_Array::value('pcp_made_through_id', $params) ||
      CRM_Utils_Array::value('soft_credit_to', $params)
    ) {

      // if its due to pcp
      if (CRM_Utils_Array::value('pcp_made_through_id', $params)) {
        $contribSoftContactId = CRM_Core_DAO::getFieldValue('CRM_PCP_DAO_PCP',
          $params['pcp_made_through_id'],
          'contact_id'
        );
      }
      else {
        $contribSoftContactId = CRM_Utils_Array::value('soft_credit_to', $params);
      }

      // Pass these details onto with the contribution to make them
      // available at hook_post_process, CRM-8908
      $contribParams['soft_credit_to'] = $params['soft_credit_to'] = $contribSoftContactId;
    }

    // create contribution record
    $contribution = CRM_Contribute_BAO_Contribution::add($contribParams, $ids);

    // process soft credit / pcp pages
    CRM_Contribute_Form_Contribution_Confirm::processPcpSoft($params, $contribution);

    // return if pending
    if ($pending || ($contribution->total_amount == 0)) {
      $transaction->commit();
      return $contribution;
    }

    // next create the transaction record
    $trxnParams = array(
      'contribution_id' => $contribution->id,
      'trxn_date' => $now,
      'trxn_type' => 'Debit',
      'total_amount' => $params['amount'],
      'fee_amount' => CRM_Utils_Array::value('fee_amount', $result),
      'net_amount' => CRM_Utils_Array::value('net_amount', $result, $params['amount']),
      'currency' => $params['currencyID'],
      'payment_processor' => $form->_paymentProcessor['payment_processor_type'],
      'trxn_id' => $result['trxn_id'],
    );

    $trxn = CRM_Core_BAO_FinancialTrxn::create($trxnParams);

    $transaction->commit();

    return $contribution;
  }

  /**
   * Fix the Location Fields
   *
   * @return void
   * @access public
   */
  public function fixLocationFields(&$params, &$fields) {
    if (!empty($this->_fields)) {
      foreach ($this->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }
    }

    if (is_array($fields)) {
      if (!array_key_exists('first_name', $fields)) {
        $nameFields = array('first_name', 'middle_name', 'last_name');
        foreach ($nameFields as $name) {
          $fields[$name] = 1;
          if (array_key_exists("billing_$name", $params)) {
            $params[$name] = $params["billing_{$name}"];
            $params['preserveDBName'] = TRUE;
          }
        }
      }
    }

    // also add location name to the array
    if ($this->_values['event']['is_monetary']) {
      $params["address_name-{$this->_bltID}"] = CRM_Utils_Array::value('billing_first_name', $params) . ' ' . CRM_Utils_Array::value('billing_middle_name', $params) . ' ' . CRM_Utils_Array::value('billing_last_name', $params);
      $fields["address_name-{$this->_bltID}"] = 1;
    }
    $fields["email-{$this->_bltID}"] = 1;
    $fields['email-Primary'] = 1;

    //if its pay later or additional participant set email address as primary.
    if ((CRM_Utils_Array::value('is_pay_later', $params) ||
        !CRM_Utils_Array::value('is_primary', $params) ||
        !$this->_values['event']['is_monetary'] ||
        $this->_allowWaitlist ||
        $this->_requireApproval
      ) &&
      CRM_Utils_Array::value("email-{$this->_bltID}", $params)
    ) {
      $params['email-Primary'] = $params["email-{$this->_bltID}"];
    }
  }

  /**
   * function to update contact fields
   *
   * @return void
   * @access public
   */
  public function updateContactFields($contactID, $params, $fields) {
    //add the contact to group, if add to group is selected for a
    //particular uf group

    // get the add to groups
    $addToGroups = array();

    if (!empty($this->_fields)) {
      foreach ($this->_fields as $key => $value) {
        if (CRM_Utils_Array::value('add_to_group_id', $value)) {
          $addToGroups[$value['add_to_group_id']] = $value['add_to_group_id'];
        }
      }
    }

    // check for profile double opt-in and get groups to be subscribed
    $subscribeGroupIds = CRM_Core_BAO_UFGroup::getDoubleOptInGroupIds($params, $contactID);

    foreach ($addToGroups as $k) {
      if (array_key_exists($k, $subscribeGroupIds)) {
        unset($addToGroups[$k]);
      }
    }

    // since we are directly adding contact to group lets unset it from mailing
    if (!empty($addToGroups)) {
      foreach ($addToGroups as $groupId) {
        if (isset($subscribeGroupIds[$groupId])) {
          unset($subscribeGroupIds[$groupId]);
        }
      }
    }
    if ($contactID) {
      $ctype = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
        $contactID,
        'contact_type'
      );
      $contactID = &CRM_Contact_BAO_Contact::createProfileContact($params,
        $fields,
        $contactID,
        $addToGroups,
        NULL,
        $ctype,
        TRUE
      );
    }
    else {

      foreach (CRM_Contact_BAO_Contact::$_greetingTypes as $greeting) {
        if (!isset($params[$greeting . '_id'])) {
          $params[$greeting . '_id'] = CRM_Contact_BAO_Contact_Utils::defaultGreeting('Individual', $greeting);
        }
      }

      $contactID = CRM_Contact_BAO_Contact::createProfileContact($params,
        $fields,
        NULL,
        $addToGroups,
        NULL,
        NULL,
        TRUE
      );
      $this->set('contactID', $contactID);
    }

    //get email primary first if exist
    $subscribtionEmail = array('email' => CRM_Utils_Array::value('email-Primary', $params));
    if (!$subscribtionEmail['email']) {
      $subscribtionEmail['email'] = CRM_Utils_Array::value("email-{$this->_bltID}", $params);
    }
    // subscribing contact to groups
    if (!empty($subscribeGroupIds) && $subscribtionEmail['email']) {
      CRM_Mailing_Event_BAO_Subscribe::commonSubscribe($subscribeGroupIds, $subscribtionEmail, $contactID);
    }

    return $contactID;
  }
}