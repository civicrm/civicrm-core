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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

/**
 * This class generates form components for processing a participation fee block
 */
class CRM_Event_Form_EventFees {

  /**
   * Function to set variables up before form is built
   *
   * @return void
   * @access public
   */
  static function preProcess(&$form) {
    //as when call come from register.php
    if (!$form->_eventId) {
      $form->_eventId = CRM_Utils_Request::retrieve('eventId', 'Positive', $form);
    }

    $form->_pId = CRM_Utils_Request::retrieve('participantId', 'Positive', $form);
    $form->_discountId = CRM_Utils_Request::retrieve('discountId', 'Positive', $form);

    $form->_fromEmails = CRM_Event_BAO_Event::getFromEmailIds($form->_eventId);

    //CRM-6907 set event specific currency.
    if ($form->_eventId &&
      ($currency = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $form->_eventId, 'currency'))
    ) {
      $config = CRM_Core_Config::singleton();
      $config->defaultCurrency = $currency;
    }
  }

  /**
   * This function sets the default values for the form in edit/view mode
   * the default values are retrieved from the database
   *
   * @access public
   *
   * @return None
   */
  static function setDefaultValues(&$form) {
    $defaults = array();

    if ($form->_eventId) {
      //get receipt text and financial type
      $returnProperities = array( 'confirm_email_text', 'financial_type_id', 'campaign_id' );
      $details = array();
      CRM_Core_DAO::commonRetrieveAll('CRM_Event_DAO_Event', 'id', $form->_eventId, $details, $returnProperities);
      if ( CRM_Utils_Array::value( 'financial_type_id', $details[$form->_eventId] ) ) {
        $defaults[$form->_pId]['financial_type_id'] = $details[$form->_eventId]['financial_type_id'];
      }
    }

    if ($form->_pId) {
      $ids = array();
      $params = array('id' => $form->_pId);

      CRM_Event_BAO_Participant::getValues($params, $defaults, $ids);
      if ($form->_action == CRM_Core_Action::UPDATE) {
        $discounts = array();
        if (!empty($form->_values['discount'])) {
          foreach ($form->_values['discount'] as $key => $value) {
            $value = current($value);
            $discounts[$key] = $value['name'];
          }
        }

        if ($form->_discountId && CRM_Utils_Array::value($defaults[$form->_pId]['discount_id'], $discounts)) {
          $form->assign('discount', $discounts[$defaults[$form->_pId]['discount_id']]);
        }

        $form->assign('fee_amount', CRM_Utils_Array::value('fee_amount', $defaults[$form->_pId]));
        $form->assign('fee_level', CRM_Utils_Array::value('fee_level', $defaults[$form->_pId]));
      }
      $defaults[$form->_pId]['send_receipt'] = 0;
    }
    else {
      $defaults[$form->_pId]['send_receipt'] = 1;
      if ($form->_eventId && CRM_Utils_Array::value('confirm_email_text', $details[$form->_eventId])) {
        //set receipt text
        $defaults[$form->_pId]['receipt_text'] = $details[$form->_eventId]['confirm_email_text'];
      }

      list($defaults[$form->_pId]['receive_date']) = CRM_Utils_Date::setDateDefaults();
    }

    //CRM-11601 we should keep the record contribution 
    //true by default while adding participant
     if ($form->_action == CRM_Core_Action::ADD && !$form->_mode && $form->_isPaidEvent) {
      $defaults[$form->_pId]['record_contribution'] = 1;
    }

    if ($form->_mode) {
      $fields = array();

      foreach ($form->_fields as $name => $dontCare) {
        $fields[$name] = 1;
      }

      $names = array(
        'first_name', 'middle_name', 'last_name', "street_address-{$form->_bltID}",
        "city-{$form->_bltID}", "postal_code-{$form->_bltID}", "country_id-{$form->_bltID}",
        "state_province_id-{$form->_bltID}",
      );
      foreach ($names as $name) {
        $fields[$name] = 1;
      }

      $fields["state_province-{$form->_bltID}"] = 1;
      $fields["country-{$form->_bltID}"] = 1;
      $fields["email-{$form->_bltID}"] = 1;
      $fields['email-Primary'] = 1;

      if ($form->_contactId) {
        CRM_Core_BAO_UFGroup::setProfileDefaults($form->_contactId, $fields, $form->_defaults);
      }

      // use primary email address if billing email address is empty
      if (empty($form->_defaults["email-{$form->_bltID}"]) &&
        !empty($form->_defaults['email-Primary'])
      ) {
        $defaults[$form->_pId]["email-{$form->_bltID}"] = $form->_defaults['email-Primary'];
      }

      foreach ($names as $name) {
        if (!empty($form->_defaults[$name])) {
          $defaults[$form->_pId]['billing_' . $name] = $form->_defaults[$name];
        }
      }

      $config = CRM_Core_Config::singleton();
      // set default country from config if no country set
      if (!CRM_Utils_Array::value("billing_country_id-{$form->_bltID}", $defaults[$form->_pId])) {
        $defaults[$form->_pId]["billing_country_id-{$form->_bltID}"] = $config->defaultContactCountry;
      }

      //             // hack to simplify credit card entry for testing
      //             $defaults[$form->_pId]['credit_card_type']     = 'Visa';
      //             $defaults[$form->_pId]['credit_card_number']   = '4807731747657838';
      //             $defaults[$form->_pId]['cvv2']                 = '000';
      //             $defaults[$form->_pId]['credit_card_exp_date'] = array( 'Y' => '2012', 'M' => '05' );
    }


    // if user has selected discount use that to set default
    if (isset($form->_discountId)) {
      $defaults[$form->_pId]['discount_id'] = $form->_discountId;

      //hack to set defaults for already selected discount value
      if ($form->_action == CRM_Core_Action::UPDATE && !$form->_originalDiscountId) {
        $form->_originalDiscountId = $defaults[$form->_pId]['discount_id'];
        if ($form->_originalDiscountId) {
          $defaults[$form->_pId]['discount_id'] = $form->_originalDiscountId;
        }
      }
      $discountId = $form->_discountId;
    }
    else {
      $discountId = CRM_Core_BAO_Discount::findSet($form->_eventId, 'civicrm_event');
    }
    
    if ($discountId) {
      $priceSetId = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Discount', $discountId, 'price_set_id');
    }
    else {
      $priceSetId = CRM_Price_BAO_Set::getFor('civicrm_event', $form->_eventId);
    }

    if (($form->_action == CRM_Core_Action::ADD) && $form->_eventId && $discountId) {
      // this case is for add mode, where we show discount automatically
        $defaults[$form->_pId]['discount_id'] = $discountId;
    }


    if ($priceSetId) {
      // get price set default values, CRM-4090
      if (in_array(get_class($form),
          array(
            'CRM_Event_Form_Participant',
            'CRM_Event_Form_Registration_Register',
            'CRM_Event_Form_Registration_AdditionalParticipant',
          )
        )) {
        $priceSetValues = self::setDefaultPriceSet($form->_pId, $form->_eventId);
        if (!empty($priceSetValues)) {
          $defaults[$form->_pId] = array_merge($defaults[$form->_pId], $priceSetValues);
        }
      }
              
      if ($form->_action == CRM_Core_Action::ADD && CRM_Utils_Array::value('fields', $form->_priceSet)) {
        foreach ($form->_priceSet['fields'] as $key => $val) {
          foreach ($val['options'] as $keys => $values) {
            if ($values['is_default']) {
              if (get_class($form) != 'CRM_Event_Form_Participant' &&
                CRM_Utils_Array::value('is_full', $values)
              ) {
                continue;
              }

              if ($val['html_type'] == 'CheckBox') {
                $defaults[$form->_pId]["price_{$key}"][$keys] = 1;
              }
              else {
                $defaults[$form->_pId]["price_{$key}"] = $keys;
              }
            }
          }
        }
      }

      $form->assign('totalAmount', CRM_Utils_Array::value('fee_amount', $defaults[$form->_pId]));
      if ($form->_action == CRM_Core_Action::UPDATE) {
        $fee_level = $defaults[$form->_pId]['fee_level'];
        CRM_Event_BAO_Participant::fixEventLevel($fee_level);
        $form->assign('fee_level', $fee_level);
        $form->assign('fee_amount', CRM_Utils_Array::value('fee_amount', $defaults[$form->_pId]));
      }
    }

    //CRM-4453
    if (CRM_Utils_Array::value('participant_fee_currency', $defaults[$form->_pId])) {
      $form->assign('fee_currency', $defaults[$form->_pId]['participant_fee_currency']);
    }

    // CRM-4395
    if ($contriId = $form->get('onlinePendingContributionId')) {
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $contriId;
      $contribution->find( true );
      foreach( array('financial_type_id', 'payment_instrument_id','contribution_status_id', 'receive_date', 'total_amount' ) as $f ) {
        if ($f == 'receive_date') {
          list($defaults[$form->_pId]['receive_date']) = CRM_Utils_Date::setDateDefaults($contribution->$f);
        }
        else {
          $defaults[$form->_pId][$f] = $contribution->$f;
        }
      }
    }
    return $defaults[$form->_pId];
  }

  /**
   * This function sets the default values for price set.
   *
   * @access public
   *
   * @return None
   */
  static function setDefaultPriceSet($participantID, $eventID = NULL) {
    $defaults = array();
    if (!$eventID && $participantID) {
      $eventID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $participantID, 'event_id');
    }
    if (!$participantID || !$eventID) {
      return $defaults;
    }

    // get price set ID.
    $priceSetID = CRM_Price_BAO_Set::getFor('civicrm_event', $eventID);
    if (!$priceSetID) {
      return $defaults;
    }

    // use line items for setdefault price set fields, CRM-4090
    $lineItems[$participantID] = CRM_Price_BAO_LineItem::getLineItems($participantID);

    if (is_array($lineItems[$participantID]) &&
      !CRM_Utils_System::isNull($lineItems[$participantID])
    ) {

      $priceFields = $htmlTypes = $optionValues = array();
      foreach ($lineItems[$participantID] as $lineId => $items) {
        $priceFieldId = CRM_Utils_Array::value('price_field_id', $items);
        $priceOptionId = CRM_Utils_Array::value('price_field_value_id', $items);
        if ($priceFieldId && $priceOptionId) {
          $priceFields[$priceFieldId][] = $priceOptionId;
        }
      }

      if (empty($priceFields)) {
        return $defaults;
      }

      // get all price set field html types.
      $sql = '
SELECT  id, html_type
  FROM  civicrm_price_field
 WHERE  id IN (' . implode(',', array_keys($priceFields)) . ')';
      $fieldDAO = CRM_Core_DAO::executeQuery($sql);
      while ($fieldDAO->fetch()) {
        $htmlTypes[$fieldDAO->id] = $fieldDAO->html_type;
      }

      foreach ($lineItems[$participantID] as $lineId => $items) {
        $fieldId = $items['price_field_id'];
        $htmlType = CRM_Utils_Array::value($fieldId, $htmlTypes);
        if (!$htmlType) {
          continue;
        }

        if ($htmlType == 'Text') {
          $defaults["price_{$fieldId}"] = $items['qty'];
        }
        else {
          $fieldOptValues = CRM_Utils_Array::value($fieldId, $priceFields);
          if (!is_array($fieldOptValues)) {
            continue;
          }

          foreach ($fieldOptValues as $optionId) {
            if ($htmlType == 'CheckBox') {
              $defaults["price_{$fieldId}"][$optionId] = TRUE;
            }
            else {
              $defaults["price_{$fieldId}"] = $optionId;
              break;
            }
          }
        }
      }
    }

    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  static function buildQuickForm(&$form) {
    if ($form->_eventId) {
      $form->_isPaidEvent = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Event', $form->_eventId, 'is_monetary');
      if ($form->_isPaidEvent) {
        $form->addElement('hidden', 'hidden_feeblock', 1);
      }

      // make sure this is for backoffice registration.
      if ($form->getName() == 'Participant') {
        $eventfullMsg = CRM_Event_BAO_Participant::eventFullMessage($form->_eventId, $form->_pId);
        $form->addElement('hidden', 'hidden_eventFullMsg', $eventfullMsg, array('id' => 'hidden_eventFullMsg'));
      }
    }

    if ($form->_pId) {
      if (CRM_Core_DAO::getFieldValue('CRM_Event_DAO_ParticipantPayment',
          $form->_pId, 'contribution_id', 'participant_id'
        )) {
        $form->_online = TRUE;
      }
    }

    if ($form->_isPaidEvent) {
      $params = array('id' => $form->_eventId);
      CRM_Event_BAO_Event::retrieve($params, $event);

      //retrieve custom information
      $form->_values = array();
      CRM_Event_Form_Registration::initEventFee($form, $event['id']);
      CRM_Event_Form_Registration_Register::buildAmount($form, TRUE, $form->_discountId);
      $lineItem = array();
      if (!CRM_Utils_System::isNull(CRM_Utils_Array::value('line_items', $form->_values))) {
        $lineItem[] = $form->_values['line_items'];
      }
      $form->assign('lineItem', empty($lineItem) ? FALSE : $lineItem);
      $discounts = array();
      if (!empty($form->_values['discount'])) {
        foreach ($form->_values['discount'] as $key => $value) {
          $value = current($value);
          $discounts[$key] = $value['name'];
        }

        $element = $form->add('select', 'discount_id',
          ts('Discount Set'),
          array(
            0 => ts('- select -')) + $discounts,
          FALSE,
          array('onchange' => "buildFeeBlock( {$form->_eventId}, this.value );")
        );

        if ($form->_online) {
          $element->freeze();
        }
      }
      if ($form->_mode) {
        CRM_Core_Payment_Form::buildCreditCard($form, TRUE);
      }
      elseif (!$form->_mode) {
        $form->addElement('checkbox', 'record_contribution', ts('Record Payment?'), NULL,
          array('onclick' => "return showHideByValue('record_contribution','','payment_information','table-row','radio',false);")
        );

        $form->add('select', 'financial_type_id',
          ts( 'Financial Type' ),
          array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::financialType()
        );

        $form->addDate('receive_date', ts('Received'), FALSE, array('formatType' => 'activityDate'));

        $form->add('select', 'payment_instrument_id',
          ts('Paid By'),
          array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument(),
          FALSE, array('onChange' => "return showHideByValue('payment_instrument_id','4','checkNumber','table-row','select',false);")
        );
        // don't show transaction id in batch update mode
        $path = CRM_Utils_System::currentPath();
        $form->assign('showTransactionId', FALSE);
        if ($path != 'civicrm/contact/search/basic') {
          $form->add('text', 'trxn_id', ts('Transaction ID'));
          $form->addRule('trxn_id', ts('Transaction ID already exists in Database.'),
            'objectExists', array('CRM_Contribute_DAO_Contribution', $form->_eventId, 'trxn_id')
          );
          $form->assign('showTransactionId', TRUE);
        }

        $allowStatuses = array();
        $statuses = CRM_Contribute_PseudoConstant::contributionStatus();
        if ($form->get('onlinePendingContributionId')) {
          $statusNames = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
          foreach ($statusNames as $val => $name) {
            if (in_array($name, array(
              'In Progress', 'Overdue'))) {
              continue;
            }
            $allowStatuses[$val] = $statuses[$val];
          }
        }
        else {
          $allowStatuses = $statuses;
        }
        $form->add('select', 'contribution_status_id',
          ts('Payment Status'), $allowStatuses
        );

        $form->add('text', 'check_number', ts('Check Number'),
          CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'check_number')
        );

        $form->add('text', 'total_amount', ts('Total Amount'),
          CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution', 'total_amount')
        );
      }
    }
    else {
      $form->add('text', 'amount', ts('Event Fee(s)'));
    }
    $form->assign('onlinePendingContributionId', $form->get('onlinePendingContributionId'));

    $form->assign('paid', $form->_isPaidEvent);

    $form->addElement('checkbox',
      'send_receipt',
      ts('Send Confirmation?'), NULL,
      array('onclick' => "showHideByValue('send_receipt','','notice','table-row','radio',false); showHideByValue('send_receipt','','from-email','table-row','radio',false);")
    );

    $form->add('select', 'from_email_address', ts('Receipt From'), $form->_fromEmails['from_email_id']);

    $form->add('textarea', 'receipt_text', ts('Confirmation Message'));

    // Retrieve the name and email of the contact - form will be the TO for receipt email ( only if context is not standalone)
    if ($form->_context != 'standalone') {
      if ($form->_contactId) {
        list($form->_contributorDisplayName,
          $form->_contributorEmail
        ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($form->_contactId);
        $form->assign('email', $form->_contributorEmail);
      }
      else {
        //show email block for batch update for event
        $form->assign('batchEmail', TRUE);
      }
    }

    $mailingInfo = CRM_Core_BAO_Setting::getItem(CRM_Core_BAO_Setting::MAILING_PREFERENCES_NAME,
      'mailing_backend'
    );
    $form->assign('outBound_option', $mailingInfo['outBound_option']);
    $form->assign('hasPayment', $form->_paymentId);
  }
}

