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
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */
class CRM_Pledge_BAO_PledgeBlock extends CRM_Pledge_DAO_PledgeBlock {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * pledgeBlock id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Pledge_BAO_PledgeBlock object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $pledgeBlock = new CRM_Pledge_DAO_PledgeBlock();
    $pledgeBlock->copyValues($params);
    if ($pledgeBlock->find(TRUE)) {
      CRM_Core_DAO::storeValues($pledgeBlock, $defaults);
      return $pledgeBlock;
    }
    return NULL;
  }

  /**
   * takes an associative array and creates a pledgeBlock object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Pledge_BAO_PledgeBlock object
   * @access public
   * @static
   */
  static function &create(&$params) {
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
   * function to add pledgeBlock
   *
   * @param array $params reference array contains the values submitted by the form
   *
   * @access public
   * @static
   *
   * @return object
   */
  static function add(&$params) {

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::pre('edit', 'PledgeBlock', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'PledgeBlock', NULL, $params);
    }

    $pledgeBlock = new CRM_Pledge_DAO_PledgeBlock();

    //fix for pledge_frequency_unit
    $freqUnits = CRM_Utils_Array::value('pledge_frequency_unit', $params);

    if ($freqUnits && is_array($freqUnits)) {
      unset($params['pledge_frequency_unit']);
      $newFreqUnits = array();
      foreach ($freqUnits as $k => $v) {
        if ($v) {
          $newFreqUnits[$k] = $v;
        }
      }

      $freqUnits = $newFreqUnits;
      if (is_array($freqUnits) && !empty($freqUnits)) {
        $freqUnits = implode(CRM_Core_DAO::VALUE_SEPARATOR, array_keys($freqUnits));
        $pledgeBlock->pledge_frequency_unit = $freqUnits;
      }
      else {
        $pledgeBlock->pledge_frequency_unit = '';
      }
    }

    $pledgeBlock->copyValues($params);
    $result = $pledgeBlock->save();

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'PledgeBlock', $pledgeBlock->id, $pledgeBlock);
    }
    else {
      CRM_Utils_Hook::post('create', 'Pledge', $pledgeBlock->id, $pledgeBlock);
    }

    return $result;
  }

  /**
   * Function to delete the pledgeBlock
   *
   * @param int $id  pledgeBlock id
   *
   * @access public
   * @static
   */
  static function deletePledgeBlock($id) {
    CRM_Utils_Hook::pre('delete', 'PledgeBlock', $id, CRM_Core_DAO::$_nullArray);

    $transaction = new CRM_Core_Transaction();

    $results = NULL;

    $dao     = new CRM_Pledge_DAO_PledgeBlock();
    $dao->id = $id;
    $results = $dao->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'PledgeBlock', $dao->id, $dao);

    return $results;
  }

  /**
   * Function to return Pledge  Block info in Contribution Pages
   *
   * @param int $pageId contribution page id
   *
   * @static
   */
  static function getPledgeBlock($pageID) {
    $pledgeBlock = array();

    $dao               = new CRM_Pledge_DAO_PledgeBlock();
    $dao->entity_table = 'civicrm_contribution_page';
    $dao->entity_id    = $pageID;
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $pledgeBlock);
    }

    return $pledgeBlock;
  }

  /**
   * Function to build Pledge Block in Contribution Pages
   *
   * @param obj $form
   * @static
   */
  static function buildPledgeBlock($form) {
    //build pledge payment fields.
    if (CRM_Utils_Array::value('pledge_id', $form->_values)) {
      //get all payments required details.
      $allPayments = array();
      $returnProperties = array('status_id', 'scheduled_date', 'scheduled_amount');
      CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'pledge_id',
        $form->_values['pledge_id'], $allPayments, $returnProperties
      );
      //get all status
      $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

      $nextPayment     = array();
      $isNextPayment   = FALSE;
      $overduePayments = array();
      $now             = date('Ymd');
      foreach ($allPayments as $payID => $value) {
        if ($allStatus[$value['status_id']] == 'Overdue') {
          $overduePayments[$payID] = array(
            'id' => $payID,
            'scheduled_amount' => CRM_Utils_Rule::cleanMoney($value['scheduled_amount']),
            'scheduled_date' => CRM_Utils_Date::customFormat($value['scheduled_date'],
              '%B %d'
            ),
          );
        }
        elseif (!$isNextPayment &&
          $allStatus[$value['status_id']] == 'Pending'
        ) {
          //get the next payment.
          $nextPayment = array(
            'id' => $payID,
            'scheduled_amount' => CRM_Utils_Rule::cleanMoney($value['scheduled_amount']),
            'scheduled_date' => CRM_Utils_Date::customFormat($value['scheduled_date'],
              '%B %d'
            ),
          );
          $isNextPayment = TRUE;
        }
      }

      //build check box array for payments.
      $payments = array();
      if (!empty($overduePayments)) {
        foreach ($overduePayments as $id => $payment) {
          $key = ts("$%1 - due on %2 (overdue)", array(1 => CRM_Utils_Array::value('scheduled_amount', $payment),
              2 => CRM_Utils_Array::value('scheduled_date', $payment),
            ));
          $payments[$key] = CRM_Utils_Array::value('id', $payment);
        }
      }

      if (!empty($nextPayment)) {
        $key = ts("$%1 - due on %2", array(1 => CRM_Utils_Array::value('scheduled_amount', $nextPayment),
            2 => CRM_Utils_Array::value('scheduled_date', $nextPayment),
          ));
        $payments[$key] = CRM_Utils_Array::value('id', $nextPayment);
      }
      //give error if empty or build form for payment.
      if (empty($payments)) {
        CRM_Core_Error::fatal(ts("Oops. It looks like there is no valid payment status for online payment."));
      }
      else {
        $form->assign('is_pledge_payment', TRUE);
        $form->addCheckBox('pledge_amount', ts('Make Pledge Payment(s):'), $payments);
      }
    }
    else {

      $pledgeBlock = self::getPledgeBlock($form->_id);

      //build form for pledge creation.
      $pledgeOptions = array('0' => ts('I want to make a one-time contribution'),
        '1' => ts('I pledge to contribute this amount every'),
      );
      $form->addRadio('is_pledge', ts('Pledge Frequency Interval'), $pledgeOptions,
        NULL, array('<br/>')
      );
      $form->addElement('text', 'pledge_installments', ts('Installments'), array('size' => 3));

      if (CRM_Utils_Array::value('is_pledge_interval', $pledgeBlock)) {
        $form->assign('is_pledge_interval', CRM_Utils_Array::value('is_pledge_interval', $pledgeBlock));
        $form->addElement('text', 'pledge_frequency_interval', NULL, array('size' => 3));
      }
      else {
        $form->add('hidden', 'pledge_frequency_interval', 1);
      }
      //Frequency unit drop-down label suffixes switch from *ly to *(s)
      $freqUnitVals   = explode(CRM_Core_DAO::VALUE_SEPARATOR, $pledgeBlock['pledge_frequency_unit']);
      $freqUnits      = array();
      $frequencyUnits = CRM_Core_OptionGroup::values('recur_frequency_units');
      foreach ($freqUnitVals as $key => $val) {
        if (array_key_exists($val, $frequencyUnits)) {
          $freqUnits[$val] = CRM_Utils_Array::value('is_pledge_interval', $pledgeBlock) ? "{$frequencyUnits[$val]}(s)" : $frequencyUnits[$val];
        }
      }
      $form->addElement('select', 'pledge_frequency_unit', NULL, $freqUnits);
    }
  }
}

