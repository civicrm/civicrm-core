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
   * @deprecated since 5.69 will be removed around 5.74
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function preProcess(&$form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    //as when call come from register.php
    if (!$form->_eventId) {
      $form->_eventId = CRM_Utils_Request::retrieve('eventId', 'Positive', $form);
    }

    $form->_pId = CRM_Utils_Request::retrieve('participantId', 'Positive', $form);
    $form->_discountId = CRM_Utils_Request::retrieve('discountId', 'Positive', $form);

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
   * @param \CRM_Event_Form_Participant|\CRM_Event_Form_Registration_AdditionalParticipant|\CRM_Event_Form_Registration_Register $form
   *
   * @return array
   */
  public static function setDefaultValues($form): array {
    $defaults = [];
    $billingLocationTypeID = CRM_Core_BAO_LocationType::getBilling();

    if ($form->_pId) {
      $ids = [];
      $params = ['id' => $form->_pId];

      CRM_Event_BAO_Participant::getValues($params, $defaults, $ids);
      $defaults += $defaults[$form->_pId];
      unset($defaults[$form->_pId]);
      if ($form->_action == CRM_Core_Action::UPDATE) {
        $discounts = [];
        if (!empty($form->_values['discount'])) {
          foreach ($form->_values['discount'] as $key => $value) {
            $value = current($value);
            $discounts[$key] = $value['name'];
          }
        }

        if ($form->_discountId && !empty($discounts[$defaults['discount_id']])) {
          $form->assign('discount', $discounts[$defaults['discount_id']]);
        }

        $form->assign('fee_amount', $defaults['fee_amount'] ?? NULL);
        $form->assign('fee_level', $defaults['fee_level'] ?? NULL);
      }
    }

    if ($form->_mode) {
      $config = CRM_Core_Config::singleton();
      // set default country from config if no country set
      if (empty($defaults["billing_country_id-{$billingLocationTypeID}"])) {
        $defaults["billing_country_id-{$billingLocationTypeID}"] = $config->defaultContactCountry;
      }

      if (empty($defaults["billing_state_province_id-{$billingLocationTypeID}"])) {
        $defaults["billing_state_province_id-{$billingLocationTypeID}"] = $config->defaultContactStateProvince;
      }

      $billingDefaults = $form->getProfileDefaults('Billing', $form->_contactId);
      $defaults = array_merge($defaults, $billingDefaults);
    }

    // if user has selected discount use that to set default
    if (isset($form->_discountId)) {
      $defaults['discount_id'] = $form->_discountId;

      //hack to set defaults for already selected discount value
      if ($form->_action == CRM_Core_Action::UPDATE && !$form->_originalDiscountId) {
        $form->_originalDiscountId = $defaults['discount_id'];
        if ($form->_originalDiscountId) {
          $defaults['discount_id'] = $form->_originalDiscountId;
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
      $defaults['discount_id'] = $discountId;
    }

    if ($priceSetId) {
      // get price set default values, CRM-4090
      if (in_array(get_class($form),
        [
          'CRM_Event_Form_Participant',
          'CRM_Event_Form_Task_Register',
          'CRM_Event_Form_Registration_Register',
          'CRM_Event_Form_Registration_AdditionalParticipant',
        ]
      )) {
        $priceSetValues = self::setDefaultPriceSet($form->_pId, $form->_eventId);
        if (!empty($priceSetValues)) {
          $defaults = array_merge($defaults, $priceSetValues);
        }
      }

      if ($form->_action == CRM_Core_Action::ADD && !empty($form->_priceSet['fields'])) {
        foreach ($form->_priceSet['fields'] as $key => $val) {
          foreach ($val['options'] as $keys => $values) {
            if ($values['is_default']) {
              if (!in_array(get_class($form), ['CRM_Event_Form_Participant', 'CRM_Event_Form_Task_Register']) && !empty($values['is_full'])) {
                continue;
              }

              if ($val['html_type'] == 'CheckBox') {
                $defaults["price_{$key}"][$keys] = 1;
              }
              else {
                $defaults["price_{$key}"] = $keys;
              }
            }
          }
        }
      }

      $form->assign('totalAmount', $defaults['fee_amount'] ?? NULL);
      if ($form->_action == CRM_Core_Action::UPDATE) {
        $fee_level = $defaults['fee_level'];
        CRM_Event_BAO_Participant::fixEventLevel($fee_level);
        $form->assign('fee_level', $fee_level);
        $form->assign('fee_amount', $defaults['fee_amount'] ?? NULL);
      }
    }

    //CRM-4453
    if (!empty($defaults['participant_fee_currency'])) {
      $form->assign('fee_currency', $defaults['participant_fee_currency']);
      $form->assign('currency', $defaults['participant_fee_currency']);
    }

    // CRM-4395
    if ($contriId = $form->get('onlinePendingContributionId')) {
      $defaults['record_contribution'] = 1;
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
        $defaults[$f] = $contribution->$f;
      }
    }
    return $defaults;
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
        $priceFieldId = $items['price_field_id'] ?? NULL;
        $priceOptionId = $items['price_field_value_id'] ?? NULL;
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
        $htmlType = $htmlTypes[$fieldId] ?? NULL;
        if (!$htmlType) {
          continue;
        }

        if ($htmlType == 'Text') {
          $defaults["price_{$fieldId}"] = $items['qty'];
        }
        else {
          $fieldOptValues = $priceFields[$fieldId] ?? NULL;
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
