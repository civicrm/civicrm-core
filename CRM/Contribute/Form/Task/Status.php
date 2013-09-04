<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
 *
 */

/**
 * This class provides the functionality to email a group of
 * contacts.
 */
class CRM_Contribute_Form_Task_Status extends CRM_Contribute_Form_Task {

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var boolean
   */
  public $_single = FALSE;

  protected $_rows;

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE
    );

    if ($id) {
      $this->_contributionIds = array($id);
      $this->_componentClause = " civicrm_contribution.id IN ( $id ) ";
      $this->_single = TRUE;
      $this->assign('totalSelectedContributions', 1);
    }
    else {
      parent::preProcess();
    }

    // check that all the contribution ids have pending status
    $query = "
SELECT count(*)
FROM   civicrm_contribution
WHERE  contribution_status_id != 2
AND    {$this->_componentClause}";
    $count = CRM_Core_DAO::singleValueQuery($query,
      CRM_Core_DAO::$_nullArray
    );
    if ($count != 0) {
      CRM_Core_Error::statusBounce(ts('Please select only online contributions with Pending status.'));
    }

    // we have all the contribution ids, so now we get the contact ids
    parent::setContactIDs();
    $this->assign('single', $this->_single);
  }

  /**
   * Build the form
   *
   * @access public
   *
   * @return void
   */
  public function buildQuickForm() {
    $status = CRM_Contribute_PseudoConstant::contributionStatus();
    unset($status[2]);
    unset($status[5]);
    unset($status[6]);
    $this->add('select', 'contribution_status_id',
      ts('Contribution Status'),
      $status,
      TRUE
    );

    $contribIDs = implode(',', $this->_contributionIds);
    $query = "
SELECT c.id            as contact_id,
       co.id           as contribution_id,
       c.display_name  as display_name,
       co.total_amount as amount,
       co.receive_date as receive_date,
       co.source       as source,
       co.payment_instrument_id as paid_by,
       co.check_number as check_no
FROM   civicrm_contact c,
       civicrm_contribution co
WHERE  co.contact_id = c.id
AND    co.id IN ( $contribIDs )";
    $dao = CRM_Core_DAO::executeQuery($query,
      CRM_Core_DAO::$_nullArray
    );

    // build a row for each contribution id
    $this->_rows   = array();
    $attributes    = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution');
    $defaults      = array();
    $now           = date("m/d/Y");
    $paidByOptions = array('' => ts('- select -')) + CRM_Contribute_PseudoConstant::paymentInstrument();

    while ($dao->fetch()) {
      $row['contact_id'] = $dao->contact_id;
      $row['contribution_id'] = $dao->contribution_id;
      $row['display_name'] = $dao->display_name;
      $row['amount'] = $dao->amount;
      $row['source'] = $dao->source;
      $row['trxn_id'] = &$this->addElement('text', "trxn_id_{$row['contribution_id']}", ts('Transaction ID'));
      $this->addRule("trxn_id_{$row['contribution_id']}",
        ts('This Transaction ID already exists in the database. Include the account number for checks.'),
        'objectExists',
        array('CRM_Contribute_DAO_Contribution', $dao->contribution_id, 'trxn_id')
      );


      $row['fee_amount'] = &$this->add('text', "fee_amount_{$row['contribution_id']}", ts('Fee Amount'),
        $attributes['fee_amount']
      );
      $this->addRule("fee_amount_{$row['contribution_id']}", ts('Please enter a valid amount.'), 'money');
      $defaults["fee_amount_{$row['contribution_id']}"] = 0.0;

      $row['trxn_date'] = &$this->addDate("trxn_date_{$row['contribution_id']}", FALSE,
        ts('Receipt Date'), array('formatType' => 'activityDate')
      );
      $defaults["trxn_date_{$row['contribution_id']}"] = $now;

      $this->add("text", "check_number_{$row['contribution_id']}", ts('Check Number'));
      $defaults["check_number_{$row['contribution_id']}"] = $dao->check_no;

      $this->add("select", "payment_instrument_id_{$row['contribution_id']}", ts('Paid By'), $paidByOptions);
      $defaults["payment_instrument_id_{$row['contribution_id']}"] = $dao->paid_by;

      $this->_rows[] = $row;
    }

    $this->assign_by_ref('rows', $this->_rows);
    $this->setDefaults($defaults);
    $this->addButtons(array(
        array(
          'type' => 'next',
          'name' => ts('Update Pending Status'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'back',
          'name' => ts('Cancel'),
        ),
      )
    );

    $this->addFormRule(array('CRM_Contribute_Form_Task_Status', 'formRule'));
  }

  /**
   * global validation rules for the form
   *
   * @param array $fields posted values of the form
   *
   * @return array list of errors to be posted back to the form
   * @static
   * @access public
   */
  static function formRule($fields) {
    $seen = $errors = array();
    foreach ($fields as $name => $value) {
      if (strpos($name, 'trxn_id_') !== FALSE) {
        if ($fields[$name]) {
          if (array_key_exists($value, $seen)) {
            $errors[$name] = ts('Transaction ID\'s must be unique. Include the account number for checks.');
          }
          $seen[$value] = 1;
        }
      }

      if ((strpos($name, 'check_number_') !== FALSE) && $value) {
        $contribID = substr($name, 13);

        if ($fields["payment_instrument_id_{$contribID}"] != CRM_Core_OptionGroup::getValue('payment_instrument', 'Check', 'name')) {
          $errors["payment_instrument_id_{$contribID}"] = ts("Paid By should be Check when a check number is entered for a contribution.");
        }
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return None
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);
    $statusID = CRM_Utils_Array::value('contribution_status_id', $params);

    $baseIPN = new CRM_Core_Payment_BaseIPN();

    $transaction = new CRM_Core_Transaction();

    // get the missing pieces for each contribution
    $contribIDs = implode(',', $this->_contributionIds);
    $details    = self::getDetails($contribIDs);
    $template   = CRM_Core_Smarty::singleton();

    // for each contribution id, we just call the baseIPN stuff
    foreach ($this->_rows as $row) {
      $input = $ids = $objects = array();
      $input['component'] = $details[$row['contribution_id']]['component'];

      $ids['contact'] = $row['contact_id'];
      $ids['contribution'] = $row['contribution_id'];
      $ids['contributionRecur'] = NULL;
      $ids['contributionPage'] = NULL;
      $ids['membership'] = CRM_Utils_Array::value('membership', $details[$row['contribution_id']]);
      $ids['participant'] = CRM_Utils_Array::value('participant', $details[$row['contribution_id']]);
      $ids['event'] = CRM_Utils_Array::value('event', $details[$row['contribution_id']]);

      if (!$baseIPN->validateData($input, $ids, $objects, FALSE)) {
        CRM_Core_Error::fatal();
      }

      $contribution = &$objects['contribution'];

      $contributionStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL,
        'name'
      );

      if ($statusID == array_search('Cancelled', $contributionStatuses)) {
        $baseIPN->cancelled($objects, $transaction);
        $transaction->commit();
        continue;
      }
      elseif ($statusID == array_search('Failed', $contributionStatuses)) {
        $baseIPN->failed($objects, $transaction);
        $transaction->commit();
        continue;
      }

      // status is not pending
      if ($contribution->contribution_status_id != array_search('Pending',
          $contributionStatuses
        )) {
        $transaction->commit();
        continue;
      }

      // set some fake input values so we can reuse IPN code
      $input['amount'] = $contribution->total_amount;
      $input['is_test'] = $contribution->is_test;
      $input['fee_amount'] = $params["fee_amount_{$row['contribution_id']}"];
      $input['check_number'] = $params["check_number_{$row['contribution_id']}"];
      $input['payment_instrument_id'] = $params["payment_instrument_id_{$row['contribution_id']}"];
      $input['net_amount'] = $contribution->total_amount - $input['fee_amount'];

      if (!empty($params["trxn_id_{$row['contribution_id']}"])) {
        $input['trxn_id'] = trim($params["trxn_id_{$row['contribution_id']}"]);
      }
      else {
        $input['trxn_id'] = $contribution->invoice_id;
      }
      $input['trxn_date'] = CRM_Utils_Date::processDate($params["trxn_date_{$row['contribution_id']}"]);

      $baseIPN->completeTransaction($input, $ids, $objects, $transaction, FALSE);

      // reset template values before processing next transactions
      $template->clearTemplateVars();
    }

    CRM_Core_Session::setStatus(ts('Contribution status has been updated for selected record(s).'), ts('Status Updated'), 'success');
  }

  static function &getDetails($contributionIDs) {
    $query = "
SELECT    c.id              as contribution_id,
          c.contact_id      as contact_id     ,
          mp.membership_id  as membership_id  ,
          pp.participant_id as participant_id ,
          p.event_id        as event_id
FROM      civicrm_contribution c
LEFT JOIN civicrm_membership_payment  mp ON mp.contribution_id = c.id
LEFT JOIN civicrm_participant_payment pp ON pp.contribution_id = c.id
LEFT JOIN civicrm_participant         p  ON pp.participant_id  = p.id
WHERE     c.id IN ( $contributionIDs )";

    $rows = array();
    $dao = CRM_Core_DAO::executeQuery($query,
      CRM_Core_DAO::$_nullArray
    );
    $rows = array();

    while ($dao->fetch()) {
      $rows[$dao->contribution_id]['component'] = $dao->participant_id ? 'event' : 'contribute';
      $rows[$dao->contribution_id]['contact'] = $dao->contact_id;
      if ($dao->membership_id) {
        if (!array_key_exists('membership', $rows[$dao->contribution_id])) {
          $rows[$dao->contribution_id]['membership'] = array();
        }
        $rows[$dao->contribution_id]['membership'][] = $dao->membership_id;
      }
      if ($dao->participant_id) {
        $rows[$dao->contribution_id]['participant'] = $dao->participant_id;
      }
      if ($dao->event_id) {
        $rows[$dao->contribution_id]['event'] = $dao->event_id;
      }
    }
    return $rows;
  }
}

