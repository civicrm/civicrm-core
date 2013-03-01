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
class CRM_Pledge_BAO_Pledge extends CRM_Pledge_DAO_Pledge {

  /**
   * static field for all the pledge information that we can potentially export
   *
   * @var array
   * @static
   */
  static $_exportableFields = NULL;

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * pledge id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
   * @return object CRM_Pledge_BAO_Pledge object
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $pledge = new CRM_Pledge_DAO_Pledge();
    $pledge->copyValues($params);
    if ($pledge->find(TRUE)) {
      CRM_Core_DAO::storeValues($pledge, $defaults);
      return $pledge;
    }
    return NULL;
  }

  /**
   * function to add pledge
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
      CRM_Utils_Hook::pre('edit', 'Pledge', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'Pledge', NULL, $params);
    }

    $pledge = new CRM_Pledge_DAO_Pledge();

    // if pledge is complete update end date as current date
    if ($pledge->status_id == 1) {
      $pledge->end_date = date('Ymd');
    }

    $pledge->copyValues($params);

    // set currency for CRM-1496
    if (!isset($pledge->currency)) {
      $config = CRM_Core_Config::singleton();
      $pledge->currency = $config->defaultCurrency;
    }

    $result = $pledge->save();

    if (CRM_Utils_Array::value('id', $params)) {
      CRM_Utils_Hook::post('edit', 'Pledge', $pledge->id, $pledge);
    }
    else {
      CRM_Utils_Hook::post('create', 'Pledge', $pledge->id, $pledge);
    }

    return $result;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params input parameters to find object
   * @param array $values output values of the object
   * @param array $returnProperties  if you want to return specific fields
   *
   * @return array associated array of field values
   * @access public
   * @static
   */
  static function &getValues(&$params, &$values, $returnProperties = NULL) {
    if (empty($params)) {
      return NULL;
    }
    CRM_Core_DAO::commonRetrieve('CRM_Pledge_BAO_Pledge', $params, $values, $returnProperties);
    return $values;
  }

  /**
   * takes an associative array and creates a pledge object
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_Pledge_BAO_Pledge object
   * @access public
   * @static
   */
  static function &create(&$params) {
    //FIXME: a cludgy hack to fix the dates to MySQL format
    $dateFields = array('start_date', 'create_date', 'acknowledge_date', 'modified_date', 'cancel_date', 'end_date');
    foreach ($dateFields as $df) {
      if (isset($params[$df])) {
        $params[$df] = CRM_Utils_Date::isoToMysql($params[$df]);
      }
    }

    $transaction = new CRM_Core_Transaction();

    $paymentParams = array();
    $paymentParams['status_id'] = CRM_Utils_Array::value('status_id', $params);
    if (CRM_Utils_Array::value('installment_amount', $params)) {
      $params['amount'] = $params['installment_amount'] * $params['installments'];
    }

    //get All Payments status types.
    $paymentStatusTypes = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    //update the pledge status only if it does NOT come from form
    if (!isset($params['pledge_status_id'])) {
      if (isset($params['contribution_id'])) {
        if ($params['installments'] > 1) {
          $params['status_id'] = array_search('In Progress', $paymentStatusTypes);
        }
      }
      else {
        if (!empty($params['id'])) {
          $params['status_id'] = CRM_Pledge_BAO_PledgePayment::calculatePledgeStatus($params['id']);
        }
        else {
          $params['status_id'] = array_search('Pending', $paymentStatusTypes);
        }
      }
    }

    $pledge = self::add($params);
    if (is_a($pledge, 'CRM_Core_Error')) {
      $pledge->rollback();
      return $pledge;
    }

    //handle custom data.
    if (CRM_Utils_Array::value('custom', $params) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_pledge', $pledge->id);
    }

    // skip payment stuff inedit mode
    if (!isset($params['id']) ||
      CRM_Utils_Array::value('is_pledge_pending', $params)
    ) {


      //if pledge is pending delete all payments and recreate.
      if (CRM_Utils_Array::value('is_pledge_pending', $params)) {
        CRM_Pledge_BAO_PledgePayment::deletePayments($pledge->id);
      }

      //building payment params
      $paymentParams['pledge_id'] = $pledge->id;
      $paymentKeys = array(
        'amount', 'installments', 'scheduled_date', 'frequency_unit', 'currency',
        'frequency_day', 'frequency_interval', 'contribution_id', 'installment_amount', 'actual_amount',
      );
      foreach ($paymentKeys as $key) {
        $paymentParams[$key] = CRM_Utils_Array::value($key, $params, NULL);
      }
      CRM_Pledge_BAO_PledgePayment::create($paymentParams);
    }

    $transaction->commit();

    $url = CRM_Utils_System::url('civicrm/contact/view/pledge',
      "action=view&reset=1&id={$pledge->id}&cid={$pledge->contact_id}&context=home"
    );

    $recentOther = array();
    if (CRM_Core_Permission::checkActionPermission('CiviPledge', CRM_Core_Action::UPDATE)) {
      $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/pledge',
        "action=update&reset=1&id={$pledge->id}&cid={$pledge->contact_id}&context=home"
      );
    }
    if (CRM_Core_Permission::checkActionPermission('CiviPledge', CRM_Core_Action::DELETE)) {
      $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/pledge',
        "action=delete&reset=1&id={$pledge->id}&cid={$pledge->contact_id}&context=home"
      );
    }

    $config            = CRM_Core_Config::singleton();
        $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $title             = CRM_Contact_BAO_Contact::displayName($pledge->contact_id) . ' - (' . ts('Pledged') . ' ' . CRM_Utils_Money::format($pledge->amount, $pledge->currency) . ' - ' . $contributionTypes[$pledge->financial_type_id] . ')';

    // add the recently created Pledge
    CRM_Utils_Recent::add($title,
      $url,
      $pledge->id,
      'Pledge',
      $pledge->contact_id,
      NULL,
      $recentOther
    );

    return $pledge;
  }

  /**
   * Function to delete the pledge
   *
   * @param int $id  pledge id
   *
   * @access public
   * @static
   *
   */
  static function deletePledge($id) {
    CRM_Utils_Hook::pre('delete', 'Pledge', $id, CRM_Core_DAO::$_nullArray);

    $transaction = new CRM_Core_Transaction();

    //check for no Completed Payment records with the pledge
    $payment = new CRM_Pledge_DAO_PledgePayment();
    $payment->pledge_id = $id;
    $payment->find();

    while ($payment->fetch()) {
      //also delete associated contribution.
      if ($payment->contribution_id) {
        CRM_Contribute_BAO_Contribution::deleteContribution($payment->contribution_id);
      }
      $payment->delete();
    }

    $dao     = new CRM_Pledge_DAO_Pledge();
    $dao->id = $id;
    $results = $dao->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Pledge', $dao->id, $dao);

    // delete the recently created Pledge
    $pledgeRecent = array(
      'id' => $id,
      'type' => 'Pledge',
    );
    CRM_Utils_Recent::del($pledgeRecent);

    return $results;
  }

  /**
   * function to get the amount details date wise.
   */
  static function getTotalAmountAndCount($status = NULL, $startDate = NULL, $endDate = NULL) {
    $where = array();
    $select = $from = $queryDate = NULL;
    //get all status
    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $statusId = array_search($status, $allStatus);

    switch ($status) {
      case 'Completed':
        $statusId = array_search('Cancelled', $allStatus);
        $where[] = 'status_id != ' . $statusId;
        break;

      case 'Cancelled':
        $where[] = 'status_id = ' . $statusId;
        break;

      case 'In Progress':
        $where[] = 'status_id = ' . $statusId;
        break;

      case 'Pending':
        $where[] = 'status_id = ' . $statusId;
        break;

      case 'Overdue':
        $where[] = 'status_id = ' . $statusId;
        break;
    }

    if ($startDate) {
      $where[] = "create_date >= '" . CRM_Utils_Type::escape($startDate, 'Timestamp') . "'";
    }
    if ($endDate) {
      $where[] = "create_date <= '" . CRM_Utils_Type::escape($endDate, 'Timestamp') . "'";
    }

    $whereCond = implode(' AND ', $where);

    $query = "
SELECT sum( amount ) as pledge_amount, count( id ) as pledge_count, currency
FROM   civicrm_pledge
WHERE  $whereCond AND is_test=0
GROUP BY  currency
";
    $start   = substr($startDate, 0, 8);
    $end     = substr($endDate, 0, 8);
    $pCount  = 0;
    $pamount = array();
    $dao     = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
    while ($dao->fetch()) {
      $pCount += $dao->pledge_count;
      $pamount[] = CRM_Utils_Money::format($dao->pledge_amount, $dao->currency);
    }

    $pledge_amount = array('pledge_amount' => implode(', ', $pamount),
      'pledge_count' => $pCount,
      'purl' => CRM_Utils_System::url('civicrm/pledge/search',
        "reset=1&force=1&pstatus={$statusId}&pstart={$start}&pend={$end}&test=0"
      ),
    );

    $where = array();
    $statusId = array_search($status, $allStatus);
    switch ($status) {
      case 'Completed':
        $select    = 'sum( total_amount ) as received_pledge , count( cd.id ) as received_count';
        $where[]   = 'cp.status_id = ' . $statusId . ' AND cp.contribution_id = cd.id AND cd.is_test=0';
        $queryDate = 'receive_date';
        $from      = ' civicrm_contribution cd, civicrm_pledge_payment cp';
        break;

      case 'Cancelled':
        $select    = 'sum( total_amount ) as received_pledge , count( cd.id ) as received_count';
        $where[]   = 'cp.status_id = ' . $statusId . ' AND cp.contribution_id = cd.id AND cd.is_test=0';
        $queryDate = 'receive_date';
        $from      = ' civicrm_contribution cd, civicrm_pledge_payment cp';
        break;

      case 'Pending':
        $select    = 'sum( scheduled_amount )as received_pledge , count( cp.id ) as received_count';
        $where[]   = 'cp.status_id = ' . $statusId . ' AND pledge.is_test=0';
        $queryDate = 'scheduled_date';
        $from      = ' civicrm_pledge_payment cp INNER JOIN civicrm_pledge pledge on cp.pledge_id = pledge.id';
        break;

      case 'Overdue':
        $select    = 'sum( scheduled_amount ) as received_pledge , count( cp.id ) as received_count';
        $where[]   = 'cp.status_id = ' . $statusId . ' AND pledge.is_test=0';
        $queryDate = 'scheduled_date';
        $from      = ' civicrm_pledge_payment cp INNER JOIN civicrm_pledge pledge on cp.pledge_id = pledge.id';
        break;
    }

    if ($startDate) {
      $where[] = " $queryDate >= '" . CRM_Utils_Type::escape($startDate, 'Timestamp') . "'";
    }
    if ($endDate) {
      $where[] = " $queryDate <= '" . CRM_Utils_Type::escape($endDate, 'Timestamp') . "'";
    }

    $whereCond = implode(' AND ', $where);

    $query = "
 SELECT $select, cp.currency
 FROM $from
 WHERE  $whereCond
 GROUP BY  cp.currency
";
    if ($select) {
      // CRM_Core_Error::debug($status . ' start:' . $startDate . '- end:' . $endDate, $query);
      $dao    = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
      $amount = array();
      $count  = 0;

      while ($dao->fetch()) {
        $count += $dao->received_count;
        $amount[] = CRM_Utils_Money::format($dao->received_pledge, $dao->currency);
      }

      if ($count) {
        return array_merge($pledge_amount, array('received_amount' => implode(', ', $amount),
            'received_count' => $count,
            'url' => CRM_Utils_System::url('civicrm/pledge/search',
              "reset=1&force=1&status={$statusId}&start={$start}&end={$end}&test=0"
            ),
          ));
      }
    }
    else {
      return $pledge_amount;
    }
    return NULL;
  }

  /**
   * Function to get list of pledges In Honor of contact Ids
   *
   * @param int $honorId In Honor of Contact ID
   *
   * @return return the list of pledge fields
   *
   * @access public
   * @static
   */
  static function getHonorContacts($honorId) {
    $params = array();
    $honorDAO = new CRM_Pledge_DAO_Pledge();
    $honorDAO->honor_contact_id = $honorId;
    $honorDAO->find();

    //get all status.
    while ($honorDAO->fetch()) {
      $params[$honorDAO->id] = array(
        'honorId' => $honorDAO->contact_id,
        'amount' => $honorDAO->amount,
        'status' => CRM_Contribute_PseudoConstant::contributionStatus($honorDAO->status_id),
        'create_date' => $honorDAO->create_date,
        'acknowledge_date' => $honorDAO->acknowledge_date,
        'type' => CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
          $honorDAO->financial_type_id, 'name'
        ),
        'display_name' => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
          $honorDAO->contact_id, 'display_name'
        ),
      );
    }
    return $params;
  }

  /**
   * Function to send Acknowledgment and create activity.
   *
   * @param object $form form object.
   * @param array  $params (reference ) an assoc array of name/value pairs.
   * @access public
   *
   * @return None.
   */
  function sendAcknowledgment(&$form, $params) {
    //handle Acknowledgment.
    $allPayments = $payments = array();

    //get All Payments status types.
    $paymentStatusTypes = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $returnProperties = array('status_id', 'scheduled_amount', 'scheduled_date', 'contribution_id');
    //get all paymnets details.
    CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'pledge_id', $params['id'], $allPayments, $returnProperties);

    if (!empty($allPayments)) {
      foreach ($allPayments as $payID => $values) {
        $contributionValue = $contributionStatus = array();
        if (isset($values['contribution_id'])) {
          $contributionParams = array('id' => $values['contribution_id']);
          $returnProperties = array('contribution_status_id', 'receive_date');
          CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_Contribution',
            $contributionParams, $contributionStatus, $returnProperties
          );
          $contributionValue = array(
            'status' => CRM_Utils_Array::value('contribution_status_id', $contributionStatus),
            'receive_date' => CRM_Utils_Array::value('receive_date', $contributionStatus),
          );
        }
        $payments[$payID] = array_merge($contributionValue,
          array('amount' => CRM_Utils_Array::value('scheduled_amount', $values),
            'due_date' => CRM_Utils_Array::value('scheduled_date', $values),
          )
        );

        //get the first valid payment id.
        if (!isset($form->paymentId) && ($paymentStatusTypes[$values['status_id']] == 'Pending' ||
            $paymentStatusTypes[$values['status_id']] == 'Overdue'
          )) {
          $form->paymentId = $values['id'];
        }
      }
    }
    //end

    //assign pledge fields value to template.
    $pledgeFields = array(
      'create_date', 'total_pledge_amount', 'frequency_interval', 'frequency_unit',
      'installments', 'frequency_day', 'scheduled_amount', 'currency',
    );
    foreach ($pledgeFields as $field) {
      if (CRM_Utils_Array::value($field, $params)) {
        $form->assign($field, $params[$field]);
      }
    }

    //assign all payments details.
    if ($payments) {
      $form->assign('payments', $payments);
    }

    //assign honor fields.
    $honor_block_is_active = FALSE;
    //make sure we have values for it
    if (CRM_Utils_Array::value('honor_type_id', $params) &&
      ((!empty($params['honor_first_name']) && !empty($params['honor_last_name'])) ||
        (!empty($params['honor_email']))
      )
    ) {
      $honor_block_is_active = TRUE;
      $prefix = CRM_Core_PseudoConstant::individualPrefix();
      $honor = CRM_Core_PseudoConstant::honor();
      $form->assign('honor_type', $honor[$params['honor_type_id']]);
      $form->assign('honor_prefix', $prefix[$params['honor_prefix_id']]);
      $form->assign('honor_first_name', $params['honor_first_name']);
      $form->assign('honor_last_name', $params['honor_last_name']);
      $form->assign('honor_email', $params['honor_email']);
    }
    $form->assign('honor_block_is_active', $honor_block_is_active);

    //handle domain token values
    $domain = CRM_Core_BAO_Domain::getDomain();
    $tokens = array('domain' => array('name', 'phone', 'address', 'email'),
      'contact' => CRM_Core_SelectValues::contactTokens(),
    );
    $domainValues = array();
    foreach ($tokens['domain'] as $token) {
      $domainValues[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain);
    }
    $form->assign('domain', $domainValues);

    //handle contact token values.
    $ids = array($params['contact_id']);
    $fields = array_merge(array_keys(CRM_Contact_BAO_Contact::importableFields()),
      array('display_name', 'checksum', 'contact_id')
    );
    foreach ($fields as $key => $val) {
      $returnProperties[$val] = TRUE;
    }
    $details = CRM_Utils_Token::getTokenDetails($ids,
      $returnProperties,
      TRUE, TRUE, NULL,
      $tokens,
      get_class($form)
    );
    $form->assign('contact', $details[0][$params['contact_id']]);

    //handle custom data.
    if (CRM_Utils_Array::value('hidden_custom', $params)) {
      $groupTree    = CRM_Core_BAO_CustomGroup::getTree('Pledge', CRM_Core_DAO::$_nullObject, $params['id']);
      $pledgeParams = array(array('pledge_id', '=', $params['id'], 0, 0));
      $customGroup  = array();
      // retrieve custom data
      foreach ($groupTree as $groupID => $group) {
        $customFields = $customValues = array();
        if ($groupID == 'info') {
          continue;
        }
        foreach ($group['fields'] as $k => $field) {
          $field['title'] = $field['label'];
          $customFields["custom_{$k}"] = $field;
        }

        //to build array of customgroup & customfields in it
        CRM_Core_BAO_UFGroup::getValues($params['contact_id'], $customFields, $customValues, FALSE, $pledgeParams);
        $customGroup[$group['title']] = $customValues;
      }

      $form->assign('customGroup', $customGroup);
    }

    //handle acknowledgment email stuff.
    list($pledgerDisplayName,
      $pledgerEmail
    ) = CRM_Contact_BAO_Contact_Location::getEmailDetails($params['contact_id']);

    //check for online pledge.
    $session = CRM_Core_Session::singleton();
    if (CRM_Utils_Array::value('receipt_from_email', $params)) {
      $userName = CRM_Utils_Array::value('receipt_from_name', $params);
      $userEmail = CRM_Utils_Array::value('receipt_from_email', $params);
    }
    elseif (CRM_Utils_Array::value('from_email_id', $params)) {
      $receiptFrom = $params['from_email_id'];
    }
    elseif ($userID = $session->get('userID')) {
      //check for loged in user.
      list($userName, $userEmail) = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);
    }
    else {
      //set the domain values.
      $userName = CRM_Utils_Array::value('name', $domainValues);
      $userEmail = CRM_Utils_Array::value('email', $domainValues);
    }

    if (!isset($receiptFrom)) {
      $receiptFrom = "$userName <$userEmail>";
    }

    list($sent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
      array(
        'groupName' => 'msg_tpl_workflow_pledge',
        'valueName' => 'pledge_acknowledge',
        'contactId' => $params['contact_id'],
        'from' => $receiptFrom,
        'toName' => $pledgerDisplayName,
        'toEmail' => $pledgerEmail,
      )
    );

    //check if activity record exist for this pledge
    //Acknowledgment, if exist do not add activity.
    $activityType = 'Pledge Acknowledgment';
    $activity = new CRM_Activity_DAO_Activity();
    $activity->source_record_id = $params['id'];
    $activity->activity_type_id = CRM_Core_OptionGroup::getValue('activity_type',
      $activityType,
      'name'
    );
    $config = CRM_Core_Config::singleton();

    $details = 'Total Amount ' . CRM_Utils_Money::format($params['total_pledge_amount'], CRM_Utils_Array::value('currency', $params)) . ' To be paid in ' . $params['installments'] . ' installments of ' . CRM_Utils_Money::format($params['scheduled_amount'], CRM_Utils_Array::value('currency', $params)) . ' every ' . $params['frequency_interval'] . ' ' . $params['frequency_unit'] . '(s)';

    if (!$activity->find()) {
      $activityParams = array(
        'subject' => $subject,
        'source_contact_id' => $params['contact_id'],
        'source_record_id' => $params['id'],
        'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
          $activityType,
          'name'
        ),
        'activity_date_time' => CRM_Utils_Date::isoToMysql($params['acknowledge_date']),
        'is_test' => $params['is_test'],
        'status_id' => 2,
        'details' => $details,
        'campaign_id' => CRM_Utils_Array::value('campaign_id', $params),
      );

      //lets insert assignee record.
      if (CRM_Utils_Array::value('contact_id', $params)) {
        $activityParams['assignee_contact_id'] = $params['contact_id'];
      }

      if (is_a(CRM_Activity_BAO_Activity::create($activityParams), 'CRM_Core_Error')) {
        CRM_Core_Error::fatal("Failed creating Activity for acknowledgment");
      }
    }
  }

  /**
   * combine all the exportable fields from the lower levels object
   *
   * @return array array of exportable Fields
   * @access public
   * @static
   */
  static function &exportableFields() {
    if (!self::$_exportableFields) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = array();
      }

      $fields = CRM_Pledge_DAO_Pledge::export();

      //export campaign title.
      if (isset($fields['pledge_campaign_id'])) {
        $fields['pledge_campaign'] = array('title' => ts('Campaign Title'));
      }

      $fields = array_merge($fields, CRM_Pledge_DAO_PledgePayment::export());

      //set title to calculated fields
      $calculatedFields = array('pledge_total_paid' => array('title' => ts('Total Paid')),
        'pledge_balance_amount' => array('title' => ts('Balance Amount')),
        'pledge_next_pay_date' => array('title' => ts('Next Payment Date')),
        'pledge_next_pay_amount' => array('title' => ts('Next Payment Amount')),
        'pledge_payment_paid_amount' => array('title' => ts('Paid Amount')),
        'pledge_payment_paid_date' => array('title' => ts('Paid Date')),
        'pledge_payment_status' => array('title' => ts('Pledge Payment Status'),
          'name' => 'pledge_payment_status',
          'data_type' => CRM_Utils_Type::T_STRING,
        ),
      );


      $pledgeFields = array(
        'pledge_status' => array('title' => 'Pledge Status',
          'name' => 'pledge_status',
          'data_type' => CRM_Utils_Type::T_STRING,
        ),
        'pledge_frequency_unit' => array(
          'title' => 'Pledge Frequency Unit',
          'name' => 'pledge_frequency_unit',
          'data_type' => CRM_Utils_Type::T_ENUM,
        ),
        'pledge_frequency_interval' => array(
          'title' => 'Pledge Frequency Interval',
          'name' => 'pledge_frequency_interval',
          'data_type' => CRM_Utils_Type::T_INT,
        ),
        'pledge_contribution_page_id' => array(
          'title' => 'Pledge Contribution Page Id',
          'name' => 'pledge_contribution_page_id',
          'data_type' => CRM_Utils_Type::T_INT,
        ),
      );

      $fields = array_merge($fields, $pledgeFields, $calculatedFields);

      // add custom data
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Pledge'));
      self::$_exportableFields = $fields;
    }

    return self::$_exportableFields;
  }

  /**
   * Function to get pending or in progress pledges
   *
   * @param int $contactID contact id
   *
   * @return array associated array of pledge id(s)
   * @static
   */
  static function getContactPledges($contactID) {
    $pledgeDetails = array();
    $pledgeStatuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    $status = array();

    //get pending and in progress status
    foreach (array(
      'Pending', 'In Progress', 'Overdue') as $name) {
      if ($statusId = array_search($name, $pledgeStatuses)) {
        $status[] = $statusId;
      }
    }
    if (empty($status)) {
      return $pledgeDetails;
    }

    $statusClause = " IN (" . implode(',', $status) . ")";

    $query = "
 SELECT civicrm_pledge.id id
 FROM civicrm_pledge
 WHERE civicrm_pledge.status_id  {$statusClause}
  AND civicrm_pledge.contact_id = %1
";

    $params[1] = array($contactID, 'Integer');
    $pledge = CRM_Core_DAO::executeQuery($query, $params);

    while ($pledge->fetch()) {
      $pledgeDetails[] = $pledge->id;
    }

    return $pledgeDetails;
  }

  /**
   * Function to get pledge record count for a Contact
   *
   * @param int $contactId Contact ID
   *
   * @return int count of pledge records
   * @access public
   * @static
   */
  static function getContactPledgeCount($contactID) {
    $query = "SELECT count(*) FROM civicrm_pledge WHERE civicrm_pledge.contact_id = {$contactID} AND civicrm_pledge.is_test = 0";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  public function updatePledgeStatus($params) {

    $returnMessages = array();

    $sendReminders = CRM_Utils_Array::value('send_reminders', $params, FALSE);

    $allStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');

    //unset statues that we never use for pledges
    foreach (array(
      'Completed', 'Cancelled', 'Failed') as $statusKey) {
      if ($key = CRM_Utils_Array::key($statusKey, $allStatus)) {
        unset($allStatus[$key]);
      }
    }

    $statusIds = implode(',', array_keys($allStatus));
    $updateCnt = 0;

    $query = "
SELECT  pledge.contact_id              as contact_id,
        pledge.id                      as pledge_id,
        pledge.amount                  as amount,
        payment.scheduled_date         as scheduled_date,
        pledge.create_date             as create_date,
        payment.id                     as payment_id,
        pledge.currency                as currency,
        pledge.contribution_page_id    as contribution_page_id,
        payment.reminder_count         as reminder_count,
        pledge.max_reminders           as max_reminders,
        payment.reminder_date          as reminder_date,
        pledge.initial_reminder_day    as initial_reminder_day,
        pledge.additional_reminder_day as additional_reminder_day,
        pledge.status_id               as pledge_status,
        payment.status_id              as payment_status,
        pledge.is_test                 as is_test,
        pledge.campaign_id             as campaign_id,
        SUM(payment.scheduled_amount)  as amount_due,
        ( SELECT sum(civicrm_pledge_payment.actual_amount)
        FROM civicrm_pledge_payment
        WHERE civicrm_pledge_payment.status_id = 1
        AND  civicrm_pledge_payment.pledge_id = pledge.id
        ) as amount_paid
        FROM      civicrm_pledge pledge, civicrm_pledge_payment payment
        WHERE     pledge.id = payment.pledge_id
        AND     payment.status_id IN ( {$statusIds} ) AND pledge.status_id IN ( {$statusIds} )
        GROUP By  payment.id
        ";

    $dao = CRM_Core_DAO::executeQuery($query);

    $now = date('Ymd');
    $pledgeDetails = $contactIds = $pledgePayments = $pledgeStatus = array();
    while ($dao->fetch()) {
      $checksumValue = CRM_Contact_BAO_Contact_Utils::generateChecksum($dao->contact_id);

      $pledgeDetails[$dao->payment_id] = array(
        'scheduled_date' => $dao->scheduled_date,
        'amount_due' => $dao->amount_due,
        'amount' => $dao->amount,
        'amount_paid' => $dao->amount_paid,
        'create_date' => $dao->create_date,
        'contact_id' => $dao->contact_id,
        'pledge_id' => $dao->pledge_id,
        'checksumValue' => $checksumValue,
        'contribution_page_id' => $dao->contribution_page_id,
        'reminder_count' => $dao->reminder_count,
        'max_reminders' => $dao->max_reminders,
        'reminder_date' => $dao->reminder_date,
        'initial_reminder_day' => $dao->initial_reminder_day,
        'additional_reminder_day' => $dao->additional_reminder_day,
        'pledge_status' => $dao->pledge_status,
        'payment_status' => $dao->payment_status,
        'is_test' => $dao->is_test,
        'currency' => $dao->currency,
        'campaign_id' => $dao->campaign_id,
      );

      $contactIds[$dao->contact_id] = $dao->contact_id;
      $pledgeStatus[$dao->pledge_id] = $dao->pledge_status;

      if (CRM_Utils_Date::overdue(CRM_Utils_Date::customFormat($dao->scheduled_date, '%Y%m%d'),
          $now
        ) && $dao->payment_status != array_search('Overdue', $allStatus)) {
        $pledgePayments[$dao->pledge_id][$dao->payment_id] = $dao->payment_id;
      }
    }

    // process the updating script...

    foreach ($pledgePayments as $pledgeId => $paymentIds) {
      // 1. update the pledge /pledge payment status. returns new status when an update happens
      $returnMessages[] = "Checking if status update is needed for Pledge Id: {$pledgeId} (current status is {$allStatus[$pledgeStatus[$pledgeId]]})";

      $newStatus = CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeId, $paymentIds,
        array_search('Overdue', $allStatus), NULL, 0, FALSE, TRUE
      );
      if ($newStatus != $pledgeStatus[$pledgeId]) {
        $returnMessages[] = "- status updated to: {$allStatus[$newStatus]}";
        $updateCnt += 1;
      }
    }

    if ($sendReminders) {
      // retrieve domain tokens
      $domain = CRM_Core_BAO_Domain::getDomain();
      $tokens = array('domain' => array('name', 'phone', 'address', 'email'),
        'contact' => CRM_Core_SelectValues::contactTokens(),
      );

      $domainValues = array();
      foreach ($tokens['domain'] as $token) {
        $domainValues[$token] = CRM_Utils_Token::getDomainTokenReplacement($token, $domain);
      }

      //get the domain email address, since we don't carry w/ object.
      $domainValue = CRM_Core_BAO_Domain::getNameAndEmail();
      $domainValues['email'] = $domainValue[1];

      // retrieve contact tokens

      // this function does NOT return Deceased contacts since we don't want to send them email
      list($contactDetails) = CRM_Utils_Token::getTokenDetails($contactIds,
        NULL,
        FALSE, FALSE, NULL,
        $tokens, 'CRM_UpdatePledgeRecord'
      );

      // assign domain values to template
      $template = CRM_Core_Smarty::singleton();
      $template->assign('domain', $domainValues);

      //set receipt from
      $receiptFrom = '"' . $domainValues['name'] . '" <' . $domainValues['email'] . '>';

      foreach ($pledgeDetails as $paymentId => $details) {
        if (array_key_exists($details['contact_id'], $contactDetails)) {
          $contactId = $details['contact_id'];
          $pledgerName = $contactDetails[$contactId]['display_name'];
        }
        else {
          continue;
        }

        if (empty($details['reminder_date'])) {
          $nextReminderDate = new DateTime($details['scheduled_date']);
          $nextReminderDate->modify("-" . $details['initial_reminder_day'] . "day");
          $nextReminderDate = $nextReminderDate->format("Ymd");
        }
        else {
          $nextReminderDate = new DateTime($details['reminder_date']);
          $nextReminderDate->modify("+" . $details['additional_reminder_day'] . "day");
          $nextReminderDate = $nextReminderDate->format("Ymd");
        }
        if (($details['reminder_count'] < $details['max_reminders'])
          && ($nextReminderDate <= $now)
        ) {

          $toEmail = $doNotEmail = $onHold = NULL;

          if (!empty($contactDetails[$contactId]['email'])) {
            $toEmail = $contactDetails[$contactId]['email'];
          }

          if (!empty($contactDetails[$contactId]['do_not_email'])) {
            $doNotEmail = $contactDetails[$contactId]['do_not_email'];
          }

          if (!empty($contactDetails[$contactId]['on_hold'])) {
            $onHold = $contactDetails[$contactId]['on_hold'];
          }

          // 2. send acknowledgement mail
          if ($toEmail && !($doNotEmail || $onHold)) {
            //assign value to template
            $template->assign('amount_paid', $details['amount_paid'] ? $details['amount_paid'] : 0);
            $template->assign('contact', $contactDetails[$contactId]);
            $template->assign('next_payment', $details['scheduled_date']);
            $template->assign('amount_due', $details['amount_due']);
            $template->assign('checksumValue', $details['checksumValue']);
            $template->assign('contribution_page_id', $details['contribution_page_id']);
            $template->assign('pledge_id', $details['pledge_id']);
            $template->assign('scheduled_payment_date', $details['scheduled_date']);
            $template->assign('amount', $details['amount']);
            $template->assign('create_date', $details['create_date']);
            $template->assign('currency', $details['currency']);
            list($mailSent, $subject, $message, $html) = CRM_Core_BAO_MessageTemplates::sendTemplate(
              array(
                'groupName' => 'msg_tpl_workflow_pledge',
                'valueName' => 'pledge_reminder',
                'contactId' => $contactId,
                'from' => $receiptFrom,
                'toName' => $pledgerName,
                'toEmail' => $toEmail,
              )
            );

            // 3. update pledge payment details
            if ($mailSent) {
              CRM_Pledge_BAO_PledgePayment::updateReminderDetails($paymentId);
              $activityType = 'Pledge Reminder';
              $activityParams = array(
                'subject' => $subject,
                'source_contact_id' => $contactId,
                'source_record_id' => $paymentId,
                'assignee_contact_id' => $contactId,
                'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
                  $activityType,
                  'name'
                ),
                'activity_date_time' => CRM_Utils_Date::isoToMysql($now),
                'due_date_time' => CRM_Utils_Date::isoToMysql($details['scheduled_date']),
                'is_test' => $details['is_test'],
                'status_id' => 2,
                'campaign_id' => $details['campaign_id'],
              );
              if (is_a(civicrm_api('activity', 'create', $activityParams), 'CRM_Core_Error')) {
                $returnMessages[] = "Failed creating Activity for acknowledgment";
                return array('is_error' => 1, 'message' => $returnMessages);
              }
              $returnMessages[] = "Payment reminder sent to: {$pledgerName} - {$toEmail}";
            }
          }
        }
      }
      // end foreach on $pledgeDetails
    }
    // end if ( $sendReminders )
    $returnMessages[] = "{$updateCnt} records updated.";

    return array('is_error' => 0, 'messages' => implode("\n\r", $returnMessages));
  }

  /**
   * Mark a pledge (and any outstanding payments) as cancelled.
   *
   * @param int $pledgeID
   */
  public static function cancel($pledgeID) {
    $statuses = array_flip(CRM_Contribute_PseudoConstant::contributionStatus());
    $paymentIDs = self::findCancelablePayments($pledgeID);
    CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeID, $paymentIDs, NULL,
      $statuses['Cancelled'], 0, FALSE, TRUE
    );
  }

  /**
   * Find payments which can be safely canceled.
   *
   * @param int $pledgeID
   * @return array of int (civicrm_pledge_payment.id)
   */
  public static function findCancelablePayments($pledgeID) {
    $statuses = array_flip(CRM_Contribute_PseudoConstant::contributionStatus());

    $paymentDAO = new CRM_Pledge_DAO_PledgePayment();
    $paymentDAO->pledge_id = $pledgeID;
    $paymentDAO->whereAdd(sprintf("status_id IN (%d,%d)",
      $statuses['Overdue'],
      $statuses['Pending']
    ));
    $paymentDAO->find();

    $paymentIDs = array();
    while ($paymentDAO->fetch()) {
      $paymentIDs[] = $paymentDAO->id;
    }
    return $paymentIDs;
  }
}

