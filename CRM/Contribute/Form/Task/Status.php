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

/**
 * This class provides the functionality to email a group of contacts.
 */
class CRM_Contribute_Form_Task_Status extends CRM_Contribute_Form_Task {

  /**
   * Are we operating in "single mode", i.e. updating the task of only
   * one specific contribution?
   *
   * @var bool
   */
  public $_single = FALSE;

  protected $_rows;

  /**
   * Build all the data structures needed to build the form.
   */
  public function preProcess() {
    $id = CRM_Utils_Request::retrieve('id', 'Positive',
      $this, FALSE
    );

    if ($id) {
      $this->_contributionIds = [$id];
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
    $count = CRM_Core_DAO::singleValueQuery($query);
    if ($count != 0) {
      CRM_Core_Error::statusBounce(ts('Please select only online contributions with Pending status.'));
    }

    // we have all the contribution ids, so now we get the contact ids
    parent::setContactIDs();
    $this->assign('single', $this->_single);
  }

  /**
   * Build the form object.
   *
   * @throws \CRM_Core_Exception
   */
  public function buildQuickForm() {
    $this->add('checkbox', 'is_email_receipt', ts('Send e-mail receipt'));
    $this->setDefaults(['is_email_receipt' => 1]);

    $contribIDs = implode(',', $this->getIDs());
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
    $dao = CRM_Core_DAO::executeQuery($query);

    // build a row for each contribution id
    $this->_rows = [];
    $attributes = CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_Contribution');
    $defaults = [];
    $now = date('Y-m-d');
    $paidByOptions = ['' => ts('- select -')] + CRM_Contribute_PseudoConstant::paymentInstrument();

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
        ['CRM_Contribute_DAO_Contribution', $dao->contribution_id, 'trxn_id']
      );

      $row['fee_amount'] = &$this->add('text', "fee_amount_{$row['contribution_id']}", ts('Fee Amount'),
        $attributes['fee_amount']
      );
      $this->addRule("fee_amount_{$row['contribution_id']}", ts('Please enter a valid amount.'), 'money');
      $defaults["fee_amount_{$row['contribution_id']}"] = 0.0;

      $row['trxn_date'] = $this->add('datepicker', "trxn_date_{$row['contribution_id']}", ts('Transaction Date'), [], FALSE, ['time' => FALSE]);
      $defaults["trxn_date_{$row['contribution_id']}"] = $now;

      $this->add('text', "check_number_{$row['contribution_id']}", ts('Check Number'));
      $defaults["check_number_{$row['contribution_id']}"] = $dao->check_no;

      $this->add('select', "payment_instrument_id_{$row['contribution_id']}", ts('Payment Method'), $paidByOptions);
      $defaults["payment_instrument_id_{$row['contribution_id']}"] = $dao->paid_by;

      $this->_rows[] = $row;
    }

    $this->assign('rows', $this->_rows);
    $this->setDefaults($defaults);
    $this->addButtons([
      [
        'type' => 'next',
        'name' => ts('Record Payments'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'back',
        'name' => ts('Cancel'),
      ],
    ]);

    $this->addFormRule(['CRM_Contribute_Form_Task_Status', 'formRule']);
  }

  /**
   * Global validation rules for the form.
   *
   * @param array $fields
   *   Posted values of the form.
   *
   * @return array
   *   list of errors to be posted back to the form
   */
  public static function formRule($fields) {
    $seen = $errors = [];
    foreach ($fields as $name => $value) {
      if (str_contains($name, 'trxn_id_')) {
        if ($fields[$name]) {
          if (array_key_exists($value, $seen)) {
            $errors[$name] = ts('Transaction ID\'s must be unique. Include the account number for checks.');
          }
          $seen[$value] = 1;
        }
      }

      if ((str_contains($name, 'check_number_')) && $value) {
        $contribID = substr($name, 13);

        if ($fields["payment_instrument_id_{$contribID}"] != CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check')) {
          $errors["payment_instrument_id_{$contribID}"] = ts('Payment Method should be Check when a check number is entered for a contribution.');
        }
      }
    }
    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Process the form after the input has been submitted and validated.
   */
  public function postProcess() {
    $params = $this->controller->exportValues($this->_name);

    // submit the form with values.
    self::processForm($this, $params);

    CRM_Core_Session::setStatus(ts('Payments have been recorded for selected record(s).'), ts('Payments recorded'), 'success');
  }

  /**
   * Process the form with submitted params.
   *
   * Also supports unit test.
   *
   * @param CRM_Core_Form $form
   * @param array $params
   *
   * @throws \Exception
   */
  public static function processForm($form, $params) {
    foreach ($form->_rows as $row) {
      $contribData = civicrm_api3('Contribution', 'getSingle', ['id' => $row['contribution_id']]);
      $trxnParams = [
        'contribution_id' => $row['contribution_id'],
        // We are safe assuming that payments will be for the total amount of
        // the contribution because the contributions must be in "Pending"
        // status.
        'total_amount' => $contribData['total_amount'],
        'fee_amount' => $params["fee_amount_{$row['contribution_id']}"],
        'check_number' => $params["check_number_{$row['contribution_id']}"],
        'payment_instrument_id' => $params["payment_instrument_id_{$row['contribution_id']}"],
        'net_amount' => $contribData['total_amount'] - $params["fee_amount_{$row['contribution_id']}"],
        // Not sure why to default to invoice_id, but that's what the form has
        // been doing historically
        'trxn_id' => $params["trxn_id_{$row['contribution_id']}"] ?? $contribData['invoice_id'],
        'trxn_date' => $params["trxn_date_{$row['contribution_id']}"] ?? 'now',
        'is_send_contribution_notification' => !empty($params['is_email_receipt']),
      ];
      $result = civicrm_api3('Payment', 'create', $trxnParams);
    }
  }

}
