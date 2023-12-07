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
class CRM_Pledge_BAO_PledgeBlock extends CRM_Pledge_DAO_PledgeBlock {

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Takes an associative array and creates a pledgeBlock object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @deprecated
   * @return CRM_Pledge_DAO_PledgeBlock
   */
  public static function &create(&$params) {
    $transaction = new CRM_Core_Transaction();
    $pledgeBlock = self::add($params);

    if (is_a($pledgeBlock, 'CRM_Core_Error')) {
      $pledgeBlock->rollback();
      return $pledgeBlock;
    }

    $params['id'] = $pledgeBlock->id;

    $transaction->commit();

    return $pledgeBlock;
  }

  /**
   * Add or update pledgeBlock.
   *
   * @param array $params
   * @deprecated
   * @return CRM_Pledge_DAO_PledgeBlock
   */
  public static function add($params) {
    // FIXME: This is assuming checkbox input like ['foo' => 1, 'bar' => 0, 'baz' => 1]. Not API friendly.
    if (!empty($params['pledge_frequency_unit']) && is_array($params['pledge_frequency_unit'])) {
      $params['pledge_frequency_unit'] = array_keys(array_filter($params['pledge_frequency_unit']));
    }
    return self::writeRecord($params);
  }

  /**
   * Delete the pledgeBlock.
   *
   * @param int $id
   *   PledgeBlock id.
   *
   * @return mixed|null
   */
  public static function deletePledgeBlock($id) {
    CRM_Utils_Hook::pre('delete', 'PledgeBlock', $id);

    $transaction = new CRM_Core_Transaction();

    $results = NULL;

    $dao = new CRM_Pledge_DAO_PledgeBlock();
    $dao->id = $id;
    $results = $dao->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'PledgeBlock', $dao->id, $dao);

    return $results;
  }

  /**
   * Return Pledge  Block info in Contribution Pages.
   *
   * @param int $pageID
   *   Contribution page id.
   *
   * @return array
   */
  public static function getPledgeBlock($pageID) {
    $pledgeBlock = [];

    $dao = new CRM_Pledge_DAO_PledgeBlock();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id = $pageID;
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $pledgeBlock);
    }

    return $pledgeBlock;
  }

  /**
   * Build Pledge Block in Contribution Pages.
   *
   * @param CRM_Core_Form $form
   *
   * @throws \CRM_Core_Exception
   *
   * @deprecated since 5.68 will be removed around 5.74
   */
  public static function buildPledgeBlock($form) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    //build pledge payment fields.
    if (!empty($form->_values['pledge_id'])) {
      //get all payments required details.
      $allPayments = [];
      $returnProperties = [
        'status_id',
        'scheduled_date',
        'scheduled_amount',
        'currency',
      ];
      CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'pledge_id',
        $form->_values['pledge_id'], $allPayments, $returnProperties
      );
      // get all status
      $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

      $nextPayment = [];
      $isNextPayment = FALSE;
      $overduePayments = [];
      foreach ($allPayments as $payID => $value) {
        if ($allStatus[$value['status_id']] == 'Overdue') {
          $overduePayments[$payID] = [
            'id' => $payID,
            'scheduled_amount' => CRM_Utils_Rule::cleanMoney($value['scheduled_amount']),
            'scheduled_amount_currency' => $value['currency'],
            'scheduled_date' => CRM_Utils_Date::customFormat($value['scheduled_date'],
              '%B %d'
            ),
          ];
        }
        elseif (!$isNextPayment &&
          $allStatus[$value['status_id']] == 'Pending'
        ) {
          // get the next payment.
          $nextPayment = [
            'id' => $payID,
            'scheduled_amount' => CRM_Utils_Rule::cleanMoney($value['scheduled_amount']),
            'scheduled_amount_currency' => $value['currency'],
            'scheduled_date' => CRM_Utils_Date::customFormat($value['scheduled_date'],
              '%B %d'
            ),
          ];
          $isNextPayment = TRUE;
        }
      }

      // build check box array for payments.
      $payments = [];
      if (!empty($overduePayments)) {
        foreach ($overduePayments as $id => $payment) {
          $label = ts("%1 - due on %2 (overdue)", [
            1 => CRM_Utils_Money::format(CRM_Utils_Array::value('scheduled_amount', $payment), CRM_Utils_Array::value('scheduled_amount_currency', $payment)),
            2 => $payment['scheduled_date'] ?? NULL,
          ]);
          $paymentID = $payment['id'] ?? NULL;
          $payments[] = $form->createElement('checkbox', $paymentID, NULL, $label, ['amount' => CRM_Utils_Array::value('scheduled_amount', $payment)]);
        }
      }

      if (!empty($nextPayment)) {
        $label = ts("%1 - due on %2", [
          1 => CRM_Utils_Money::format(CRM_Utils_Array::value('scheduled_amount', $nextPayment), CRM_Utils_Array::value('scheduled_amount_currency', $nextPayment)),
          2 => $nextPayment['scheduled_date'] ?? NULL,
        ]);
        $paymentID = $nextPayment['id'] ?? NULL;
        $payments[] = $form->createElement('checkbox', $paymentID, NULL, $label, ['amount' => CRM_Utils_Array::value('scheduled_amount', $nextPayment)]);
      }
      // give error if empty or build form for payment.
      if (empty($payments)) {
        throw new CRM_Core_Exception(ts('Oops. It looks like there is no valid payment status for online payment.'));
      }
      $form->addGroup($payments, 'pledge_amount', ts('Make Pledge Payment(s):'), '<br />');
    }
    else {

      $pledgeBlock = self::getPledgeBlock($form->_id);

      // build form for pledge creation.
      $pledgeOptions = [
        '0' => ts('I want to make a one-time contribution'),
        '1' => ts('I pledge to contribute this amount every'),
      ];
      $form->addRadio('is_pledge', ts('Pledge Frequency Interval'), $pledgeOptions,
        NULL, ['<br/>']
      );
      $form->addElement('text', 'pledge_installments', ts('Installments'), ['size' => 3, 'aria-label' => ts('Installments')]);

      if (!empty($pledgeBlock['is_pledge_interval'])) {
        $form->addElement('text', 'pledge_frequency_interval', NULL, ['size' => 3, 'aria-label' => ts('Frequency Intervals')]);
      }
      else {
        $form->add('hidden', 'pledge_frequency_interval', 1);
      }
      // Frequency unit drop-down label suffixes switch from *ly to *(s)
      $freqUnitVals = explode(CRM_Core_DAO::VALUE_SEPARATOR, $pledgeBlock['pledge_frequency_unit']);
      $freqUnits = [];
      $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units');
      foreach ($freqUnitVals as $key => $val) {
        if (array_key_exists($val, $frequencyUnits)) {
          $freqUnits[$val] = !empty($pledgeBlock['is_pledge_interval']) ? "{$frequencyUnits[$val]}(s)" : $frequencyUnits[$val];
        }
      }
      $form->addElement('select', 'pledge_frequency_unit', NULL, $freqUnits, ['aria-label' => ts('Frequency Units')]);
      // CRM-18854
      if (!empty($pledgeBlock['is_pledge_start_date_visible'])) {
        if (!empty($pledgeBlock['pledge_start_date'])) {
          $defaults = [];
          $date = (array) json_decode($pledgeBlock['pledge_start_date']);
          foreach ($date as $field => $value) {
            switch ($field) {
              case 'contribution_date':
                $form->add('datepicker', 'start_date', ts('First installment payment'), [], FALSE, ['time' => FALSE]);
                $paymentDate = $value = date('Y-m-d');
                $defaults['start_date'] = $value;
                $form->assign('is_date', TRUE);
                break;

              case 'calendar_date':
                $form->add('datepicker', 'start_date', ts('First installment payment'), [], FALSE, ['time' => FALSE]);
                $defaults['start_date'] = $value;
                $form->assign('is_date', TRUE);
                $paymentDate = $value;
                break;

              case 'calendar_month':
                $month = CRM_Utils_Date::getCalendarDayOfMonth();
                $form->add('select', 'start_date', ts('Day of month installments paid'), $month);
                $paymentDate = CRM_Pledge_BAO_Pledge::getPaymentDate($value);
                $defaults['start_date'] = $paymentDate;
                break;

              default:
                break;

            }
            $form->setDefaults($defaults);
            $form->assign('start_date_display', $paymentDate);
            $form->assign('start_date_editable', FALSE);
            if (!empty($pledgeBlock['is_pledge_start_date_editable'])) {
              $form->assign('start_date_editable', TRUE);
              if ($field == 'calendar_month') {
                $form->assign('is_date', FALSE);
                $form->setDefaults(['start_date' => $value]);
              }
            }
          }
        }
      }
    }
  }

}
