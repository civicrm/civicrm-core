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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * This class generates form components for processing a participation fee block
 */
class CRM_Event_Form_EventFees {

  /**
   * Set variables up before form is built.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcess(&$form) {
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
      CRM_Core_Config::singleton()->defaultCurrency = $currency;
    }
  }

  /**
   * This function sets the default values for the form in edit/view mode.
   *
   * @param CRM_Core_Form $form
   *
   * @return array
   */
  public static function setDefaultValues(&$form) {
    $defaults = [];

    if ($form->_eventId) {
      //get receipt text and financial type
      $returnProperities = ['confirm_email_text', 'financial_type_id', 'campaign_id', 'start_date'];
      $details = [];
      CRM_Core_DAO::commonRetrieveAll('CRM_Event_DAO_Event', 'id', $form->_eventId, $details, $returnProperities);
      if (!empty($details[$form->_eventId]['financial_type_id'])) {
        $defaults[$form->_pId]['financial_type_id'] = $details[$form->_eventId]['financial_type_id'];
      }
    }

    if ($form->_pId) {
      $ids = [];
      $params = ['id' => $form->_pId];

      CRM_Event_BAO_Participant::getValues($params, $defaults, $ids);
      if ($form->_action == CRM_Core_Action::UPDATE) {
        $discounts = [];
        if (!empty($form->_values['discount'])) {
          foreach ($form->_values['discount'] as $key => $value) {
            $value = current($value);
            $discounts[$key] = $value['name'];
          }
        }

        if ($form->_discountId && !empty($discounts[$defaults[$form->_pId]['discount_id']])) {
          $form->assign('discount', $discounts[$defaults[$form->_pId]['discount_id']]);
        }

        $form->assign('fee_amount', CRM_Utils_Array::value('fee_amount', $defaults[$form->_pId]));
        $form->assign('fee_level', CRM_Utils_Array::value('fee_level', $defaults[$form->_pId]));
      }
      $defaults[$form->_pId]['send_receipt'] = 0;
    }
    else {
      $defaults[$form->_pId]['send_receipt'] = (strtotime(CRM_Utils_Array::value('start_date', $details[$form->_eventId])) >= time()) ? 1 : 0;
      if ($form->_eventId && !empty($details[$form->_eventId]['confirm_email_text'])) {
        //set receipt text
        $defaults[$form->_pId]['receipt_text'] = $details[$form->_eventId]['confirm_email_text'];
      }

      $defaults[$form->_pId]['receive_date'] = date('Y-m-d H:i:s');
    }

    //CRM-11601 we should keep the record contribution
    //true by default while adding participant
    if ($form->_action == CRM_Core_Action::ADD && !$form->_mode && $form->_isPaidEvent) {
      $defaults[$form->_pId]['record_contribution'] = 1;
    }

    //CRM-13420
    if (empty($defaults['payment_instrument_id'])) {
      $defaults[$form->_pId]['payment_instrument_id'] = key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
    }
    if ($form->_mode) {
      $config = CRM_Core_Config::singleton();
      // set default country from config if no country set
      if (empty($defaults[$form->_pId]["billing_country_id-{$form->_bltID}"])) {
        $defaults[$form->_pId]["billing_country_id-{$form->_bltID}"] = $config->defaultContactCountry;
      }

      if (empty($defaults["billing_state_province_id-{$form->_bltID}"])) {
        $defaults[$form->_pId]["billing_state_province_id-{$form->_bltID}"] = $config->defaultContactStateProvince;
      }

      $billingDefaults = $form->getProfileDefaults('Billing', $form->_contactId);
      $defaults[$form->_pId] = array_merge($defaults[$form->_pId], $billingDefaults);
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
      $priceSetId = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $form->_eventId);
    }

    if (($form->_action == CRM_Core_Action::ADD) && $form->_eventId && $discountId) {
      // this case is for add mode, where we show discount automatically
      $defaults[$form->_pId]['discount_id'] = $discountId;
    }

    if ($priceSetId) {
      // get price set default values, CRM-4090
      if (in_array(get_class($form),
        [
          'CRM_Event_Form_Participant',
          'CRM_Event_Form_Registration_Register',
          'CRM_Event_Form_Registration_AdditionalParticipant',
        ]
      )) {
        $priceSetValues = self::setDefaultPriceSet($form->_pId, $form->_eventId);
        if (!empty($priceSetValues)) {
          $defaults[$form->_pId] = array_merge($defaults[$form->_pId], $priceSetValues);
        }
      }

      if ($form->_action == CRM_Core_Action::ADD && !empty($form->_priceSet['fields'])) {
        foreach ($form->_priceSet['fields'] as $key => $val) {
          foreach ($val['options'] as $keys => $values) {
            if ($values['is_default']) {
              if (get_class($form) != 'CRM_Event_Form_Participant' && !empty($values['is_full'])) {
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
    if (!empty($defaults[$form->_pId]['participant_fee_currency'])) {
      $form->assign('fee_currency', $defaults[$form->_pId]['participant_fee_currency']);
    }

    // CRM-4395
    if ($contriId = $form->get('onlinePendingContributionId')) {
      $defaults[$form->_pId]['record_contribution'] = 1;
      $contribution = new CRM_Contribute_DAO_Contribution();
      $contribution->id = $contriId;
      $contribution->find(TRUE);
      foreach ([
        'financial_type_id',
        'payment_instrument_id',
        'contribution_status_id',
        'receive_date',
        'total_amount',
      ] as $f) {
        $defaults[$form->_pId][$f] = $contribution->$f;
      }
    }
    return $defaults[$form->_pId];
  }

  /**
   * This function sets the default values for price set.
   *
   * @param int $participantID
   * @param int $eventID
   * @param bool $includeQtyZero
   *
   * @return array
   */
  public static function setDefaultPriceSet($participantID, $eventID = NULL, $includeQtyZero = TRUE) {
    $defaults = [];
    if (!$eventID && $participantID) {
      $eventID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $participantID, 'event_id');
    }
    if (!$participantID || !$eventID) {
      return $defaults;
    }

    // get price set ID.
    $priceSetID = CRM_Price_BAO_PriceSet::getFor('civicrm_event', $eventID);
    if (!$priceSetID) {
      return $defaults;
    }

    // use line items for setdefault price set fields, CRM-4090
    $lineItems[$participantID] = CRM_Price_BAO_LineItem::getLineItems($participantID, 'participant', FALSE, $includeQtyZero);

    if (is_array($lineItems[$participantID]) &&
      !CRM_Utils_System::isNull($lineItems[$participantID])
    ) {

      $priceFields = $htmlTypes = $optionValues = [];
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
   * Get the default payment instrument id.
   *
   * @todo resolve relationship between this form & abstractEdit -which should be it's parent.
   *
   * @return int
   */
  protected static function getDefaultPaymentInstrumentId() {
    $paymentInstrumentID = CRM_Utils_Request::retrieve('payment_instrument_id', 'Integer');
    if ($paymentInstrumentID) {
      return $paymentInstrumentID;
    }
    return key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1'));
  }

}
