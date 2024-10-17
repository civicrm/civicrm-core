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
class CRM_Pledge_BAO_Pledge extends CRM_Pledge_DAO_Pledge {

  /**
   * Static field for all the pledge information that we can potentially export.
   *
   * @var array
   */
  public static $_exportableFields = NULL;

  /**
   * @deprecated
   * @param array $params
   * @param array $defaults
   * @return self|null
   */
  public static function retrieve($params, &$defaults) {
    CRM_Core_Error::deprecatedFunctionWarning('API');
    return self::commonRetrieve(self::class, $params, $defaults);
  }

  /**
   * Add pledge.
   *
   * @param array $params
   *   Reference array contains the values submitted by the form.
   *
   * @return CRM_Pledge_DAO_Pledge
   */
  public static function add(array $params): CRM_Pledge_DAO_Pledge {
    CRM_Core_Error::deprecatedFunctionWarning('v4 api');
    $hook = empty($params['id']) ? 'create' : 'edit';
    CRM_Utils_Hook::pre($hook, 'Pledge', $params['id'] ?? NULL, $params);

    $pledge = new CRM_Pledge_DAO_Pledge();

    // if pledge is complete update end date as current date
    if ($pledge->status_id == 1) {
      $pledge->end_date = date('Ymd');
    }

    $pledge->copyValues($params);

    // set currency for CRM-1496
    if (!isset($pledge->currency)) {
      $pledge->currency = CRM_Core_Config::singleton()->defaultCurrency;
    }

    $result = $pledge->save();

    CRM_Utils_Hook::post($hook, 'Pledge', $pledge->id, $pledge);

    return $result;
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   * @param array $returnProperties
   *   If you want to return specific fields.
   *
   * @return array
   *   associated array of field values
   */
  public static function &getValues(&$params, &$values, $returnProperties = NULL) {
    if (empty($params)) {
      return NULL;
    }
    CRM_Core_DAO::commonRetrieve('CRM_Pledge_BAO_Pledge', $params, $values, $returnProperties);
    return $values;
  }

  /**
   * Takes an associative array and creates a pledge object.
   *
   * @param array $params
   *   Assoc array of name/value pairs.
   *
   * @return CRM_Pledge_DAO_Pledge
   * @throws \CRM_Core_Exception
   */
  public static function create(array $params): CRM_Pledge_DAO_Pledge {
    $action = empty($params['id']) ? 'create' : 'edit';
    if ($action === 'create') {
      $defaults = [
        'currency' => CRM_Core_Config::singleton()->defaultCurrency,
        'installments' => (int) self::fields()['installments']['default'],
        'scheduled_date' => $params['start_date'] ?? date('Ymd'),
      ];
      $params = array_merge($defaults, $params);
    }

    $isRecalculatePledgePayment = self::isPaymentsRequireRecalculation($params);
    $transaction = new CRM_Core_Transaction();

    $paymentParams = [];
    if (!empty($params['installment_amount'])) {
      $params['amount'] = $params['installment_amount'] * $params['installments'];
    }

    if (!isset($params['pledge_status_id']) && !isset($params['status_id'])) {
      if (isset($params['contribution_id'])) {
        if ($params['installments'] > 1) {
          $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_Pledge', 'status_id', 'In Progress');
        }
      }
      else {
        if (!empty($params['id'])) {
          $params['status_id'] = CRM_Pledge_BAO_PledgePayment::calculatePledgeStatus($params['id']);
        }
        else {
          $params['status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_Pledge', 'status_id', 'Pending');
        }
      }
    }
    $paymentParams['status_id'] = $params['status_id'] ?? NULL;

    $pledge = self::writeRecord($params);

    // skip payment stuff in edit mode
    if (empty($params['id']) || $isRecalculatePledgePayment) {

      // if pledge is pending delete all payments and recreate.
      if ($isRecalculatePledgePayment) {
        CRM_Pledge_BAO_PledgePayment::deletePayments($pledge->id);
      }

      // building payment params
      $paymentParams['pledge_id'] = $pledge->id;
      $paymentKeys = [
        'amount',
        'installments',
        'scheduled_date',
        'frequency_unit',
        'currency',
        'frequency_day',
        'frequency_interval',
        'contribution_id',
        'installment_amount',
        'actual_amount',
      ];
      foreach ($paymentKeys as $key) {
        $paymentParams[$key] = $params[$key] ?? NULL;
      }
      CRM_Pledge_BAO_PledgePayment::createMultiple($paymentParams);
    }

    $transaction->commit();

    $url = CRM_Utils_System::url('civicrm/contact/view/pledge',
      "action=view&reset=1&id={$pledge->id}&cid={$pledge->contact_id}&context=home"
    );

    $recentOther = [];
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

    $contributionTypes = CRM_Contribute_PseudoConstant::financialType();
    $title = CRM_Contact_BAO_Contact::displayName($pledge->contact_id) . ' - (' . ts('Pledged') . ' ' . CRM_Utils_Money::format($pledge->amount, $pledge->currency) . ' - ' . ($contributionTypes[$pledge->financial_type_id] ?? '') . ')';

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
   * Is this a change to an existing pending pledge requiring payment schedule
   * changes.
   *
   * If the pledge is pending the code (slightly lazily) deletes & recreates
   * pledge payments.
   *
   * If the payment dates or amounts have been manually edited then this can
   * cause data loss. We can mitigate this to some extent by making sure we
   * have a change that could potentially affect the schedule (rather than just
   * a custom data change or similar).
   *
   * This calculation needs to be performed before update takes place as
   * previous & new pledges are compared.
   *
   * @param array $params
   *
   * @return bool
   */
  protected static function isPaymentsRequireRecalculation($params) {
    if (empty($params['is_pledge_pending']) || empty($params['id'])) {
      return FALSE;
    }
    $scheduleChangingParameters = [
      'amount',
      'frequency_unit',
      'frequency_interval',
      'frequency_day',
      'installments',
      'start_date',
    ];
    $existingPledgeDAO = new CRM_Pledge_BAO_Pledge();
    $existingPledgeDAO->id = $params['id'];
    $existingPledgeDAO->find(TRUE);
    foreach ($scheduleChangingParameters as $parameter) {
      if ($parameter == 'start_date') {
        if (strtotime($params[$parameter]) != strtotime($existingPledgeDAO->$parameter)) {
          return TRUE;
        }
      }
      elseif ($params[$parameter] != $existingPledgeDAO->$parameter) {
        return TRUE;
      }
    }
  }

  /**
   * Delete the pledge.
   *
   * @param int $id
   *   Pledge id.
   *
   * @return mixed
   */
  public static function deletePledge($id) {
    CRM_Utils_Hook::pre('delete', 'Pledge', $id);

    $transaction = new CRM_Core_Transaction();

    // check for no Completed Payment records with the pledge
    $payment = new CRM_Pledge_DAO_PledgePayment();
    $payment->pledge_id = $id;
    $payment->find();

    while ($payment->fetch()) {
      // also delete associated contribution.
      if ($payment->contribution_id) {
        CRM_Contribute_BAO_Contribution::deleteContribution($payment->contribution_id);
      }
      $payment->delete();
    }

    $dao = new CRM_Pledge_DAO_Pledge();
    $dao->id = $id;
    $results = $dao->delete();

    $transaction->commit();

    CRM_Utils_Hook::post('delete', 'Pledge', $dao->id, $dao);

    return $results;
  }

  /**
   * Get the amount details date wise.
   *
   * @param string $status
   * @param string $startDate
   * @param string $endDate
   *
   * @return array
   */
  public static function getTotalAmountAndCount($status = NULL, $startDate = NULL, $endDate = NULL): array {
    $where = [];
    $select = $from = $queryDate = NULL;
    $statusId = CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_Pledge', 'status_id', $status);

    switch ($status) {
      case 'Completed':
        $where[] = 'status_id != ' . CRM_Core_PseudoConstant::getKey('CRM_Pledge_BAO_Pledge', 'status_id', 'Cancelled');
        break;

      case 'Cancelled':
      case 'In Progress':
      case 'Pending':
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

    $pledgeCounts = 0;
    $pledgeAmounts = [];
    $dao = CRM_Core_DAO::executeQuery($query);
    while ($dao->fetch()) {
      $pledgeCounts += $dao->pledge_count;
      $pledgeAmounts[] = CRM_Utils_Money::format($dao->pledge_amount, $dao->currency);
    }

    $start = $startDate ? substr($startDate, 0, 8) : '';
    $end = $endDate ? substr($endDate, 0, 8) : '';
    $pledge_amount = [
      'pledge_amount' => implode(', ', $pledgeAmounts),
      'pledge_count' => $pledgeCounts,
      'purl' => CRM_Utils_System::url('civicrm/pledge/search',
        "reset=1&force=1&pstatus={$statusId}&pstart={$start}&pend={$end}&test=0"
      ),
    ];

    $where = [];
    switch ($status) {
      case 'Completed':
        $select = 'sum( total_amount ) as received_pledge , count( cd.id ) as received_count';
        $where[] = 'cp.status_id = ' . $statusId . ' AND cp.contribution_id = cd.id AND cd.is_test=0';
        $queryDate = 'receive_date';
        $from = ' civicrm_contribution cd, civicrm_pledge_payment cp';
        break;

      case 'Cancelled':
        $select = 'sum( total_amount ) as received_pledge , count( cd.id ) as received_count';
        $where[] = 'cp.status_id = ' . $statusId . ' AND cp.contribution_id = cd.id AND cd.is_test=0';
        $queryDate = 'receive_date';
        $from = ' civicrm_contribution cd, civicrm_pledge_payment cp';
        break;

      case 'Pending':
        $select = 'sum( scheduled_amount )as received_pledge , count( cp.id ) as received_count';
        $where[] = 'cp.status_id = ' . $statusId . ' AND pledge.is_test=0';
        $queryDate = 'scheduled_date';
        $from = ' civicrm_pledge_payment cp INNER JOIN civicrm_pledge pledge on cp.pledge_id = pledge.id';
        break;

      case 'Overdue':
        $select = 'sum( scheduled_amount ) as received_pledge , count( cp.id ) as received_count';
        $where[] = 'cp.status_id = ' . $statusId . ' AND pledge.is_test=0';
        $queryDate = 'scheduled_date';
        $from = ' civicrm_pledge_payment cp INNER JOIN civicrm_pledge pledge on cp.pledge_id = pledge.id';
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
      $dao = CRM_Core_DAO::executeQuery($query);
      $amount = [];
      $count = 0;

      while ($dao->fetch()) {
        $count += $dao->received_count;
        $amount[] = CRM_Utils_Money::format($dao->received_pledge, $dao->currency);
      }

      if ($count) {
        return array_merge($pledge_amount, [
          'received_amount' => implode(', ', $amount),
          'received_count' => $count,
          'url' => CRM_Utils_System::url('civicrm/pledge/search',
            "reset=1&force=1&status={$statusId}&start={$start}&end={$end}&test=0"
          ),
        ]);
      }
    }
    else {
      return $pledge_amount;
    }
    return [
      'purl' => '',
      'pledge_count' => 0,
      'received_count' => 0,
      'url' => '',
    ];
  }

  /**
   * Get list of pledges In Honor of contact Ids.
   *
   * @param int $honorId
   *   In Honor of Contact ID.
   *
   * @return array
   *   return the list of pledge fields
   */
  public static function getHonorContacts($honorId) {
    $params = [];
    $honorDAO = new CRM_Contribute_DAO_ContributionSoft();
    $honorDAO->contact_id = $honorId;
    $honorDAO->find();

    // get all status.
    while ($honorDAO->fetch()) {
      $pledgePaymentDAO = new CRM_Pledge_DAO_PledgePayment();
      $pledgePaymentDAO->contribution_id = $honorDAO->contribution_id;
      if ($pledgePaymentDAO->find(TRUE)) {
        $pledgeDAO = new CRM_Pledge_DAO_Pledge();
        $pledgeDAO->id = $pledgePaymentDAO->pledge_id;
        if ($pledgeDAO->find(TRUE)) {
          $params[$pledgeDAO->id] = [
            'honor_type' => CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $honorDAO->soft_credit_type_id),
            'honorId' => $pledgeDAO->contact_id,
            'amount' => $pledgeDAO->amount,
            'status' => CRM_Core_PseudoConstant::getLabel('CRM_Pledge_BAO_Pledge', 'status_id', $pledgeDAO->status_id),
            'create_date' => $pledgeDAO->create_date,
            'acknowledge_date' => $pledgeDAO->acknowledge_date,
            'type' => CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
              $pledgeDAO->financial_type_id, 'name'
            ),
            'display_name' => CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact',
              $pledgeDAO->contact_id, 'display_name'
            ),
          ];
        }
      }
    }

    return $params;
  }

  /**
   * Send Acknowledgment and create activity.
   *
   * @param CRM_Core_Form $form
   *   Form object.
   * @param array $params
   *   An assoc array of name/value pairs.
   */
  public static function sendAcknowledgment($form, $params) {
    //handle Acknowledgment.
    $allPayments = $payments = [];

    // get All Payments status types.
    $returnProperties = [
      'status_id',
      'scheduled_amount',
      'scheduled_date',
      'contribution_id',
    ];
    // get all paymnets details.
    CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'pledge_id', $params['id'], $allPayments, $returnProperties);

    if (!empty($allPayments)) {
      foreach ($allPayments as $payID => $values) {
        $contributionValue = $contributionStatus = [];
        if (isset($values['contribution_id'])) {
          $contributionParams = ['id' => $values['contribution_id']];
          $returnProperties = ['contribution_status_id', 'receive_date'];
          CRM_Core_DAO::commonRetrieve('CRM_Contribute_DAO_Contribution',
            $contributionParams, $contributionStatus, $returnProperties
          );
          $contributionValue = [
            'status' => $contributionStatus['contribution_status_id'] ?? NULL,
            'receive_date' => $contributionStatus['receive_date'] ?? NULL,
          ];
        }
        $payments[$payID] = array_merge($contributionValue,
          [
            'amount' => $values['scheduled_amount'] ?? NULL,
            'due_date' => $values['scheduled_date'] ?? NULL,
            'status' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
          ]
        );
      }
    }

    // assign pledge fields value to template.
    $pledgeFields = [
      'create_date',
      'total_pledge_amount',
      'frequency_interval',
      'frequency_unit',
      'installments',
      'frequency_day',
      'scheduled_amount',
      'currency',
    ];
    foreach ($pledgeFields as $field) {
      if (!empty($params[$field])) {
        $form->assign($field, $params[$field]);
      }
    }

    // assign all payments details.
    if ($payments) {
      $form->assign('payments', $payments);
    }

    // handle custom data.
    $customGroup = [];
    if (!empty($params['hidden_custom'])) {
      $groupTree = CRM_Core_BAO_CustomGroup::getTree('Pledge', NULL, $params['id']);
      $pledgeParams = [['pledge_id', '=', $params['id'], 0, 0]];
      // retrieve custom data
      foreach ($groupTree as $groupID => $group) {
        $customFields = $customValues = [];
        if ($groupID == 'info') {
          continue;
        }
        foreach ($group['fields'] as $k => $field) {
          $field['title'] = $field['label'];
          $customFields["custom_{$k}"] = $field;
        }

        // to build array of customgroup & customfields in it
        CRM_Core_BAO_UFGroup::getValues($params['contact_id'], $customFields, $customValues, FALSE, $pledgeParams);
        $customGroup[$group['title']] = $customValues;
      }

    }
    $form->assign('customGroup', $customGroup);

    // handle acknowledgment email stuff.
    [$pledgerDisplayName, $pledgerEmail] = CRM_Contact_BAO_Contact_Location::getEmailDetails($params['contact_id']);

    // check for online pledge.
    if (!empty($params['receipt_from_email'])) {
      $userName = $params['receipt_from_name'] ?? NULL;
      $userEmail = $params['receipt_from_email'] ?? NULL;
    }
    elseif (!empty($params['from_email_id'])) {
      $receiptFrom = $params['from_email_id'];
    }
    elseif ($userID = CRM_Core_Session::singleton()->get('userID')) {
      // check for logged in user.
      [
        $userName,
        $userEmail,
      ] = CRM_Contact_BAO_Contact_Location::getEmailDetails($userID);
    }
    else {
      // set the domain values.
      [$userName, $userEmail] = CRM_Core_BAO_Domain::getNameAndEmail();
    }

    if (!isset($receiptFrom)) {
      $receiptFrom = "$userName <$userEmail>";
    }

    [$sent, $subject, $message, $html] = CRM_Core_BAO_MessageTemplate::sendTemplate(
      [
        'groupName' => 'msg_tpl_workflow_pledge',
        'workflow' => 'pledge_acknowledge',
        'contactId' => $params['contact_id'],
        'from' => $receiptFrom,
        'toName' => $pledgerDisplayName,
        'toEmail' => $pledgerEmail,
      ]
    );

    // check if activity record exist for this pledge
    // Acknowledgment, if exist do not add activity.
    $activityType = 'Pledge Acknowledgment';
    $activity = new CRM_Activity_DAO_Activity();
    $activity->source_record_id = $params['id'];
    $activity->activity_type_id = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity',
      'activity_type_id',
      $activityType
    );

    // FIXME: Translate
    $details = 'Total Amount ' . CRM_Utils_Money::format($params['total_pledge_amount'], CRM_Utils_Array::value('currency', $params)) . ' To be paid in ' . $params['installments'] . ' installments of ' . CRM_Utils_Money::format($params['scheduled_amount'], CRM_Utils_Array::value('currency', $params)) . ' every ' . $params['frequency_interval'] . ' ' . $params['frequency_unit'] . '(s)';

    if (!$activity->find()) {
      $activityParams = [
        'subject' => $subject,
        'source_contact_id' => $params['contact_id'],
        'source_record_id' => $params['id'],
        'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity',
          'activity_type_id',
          $activityType
        ),
        'activity_date_time' => CRM_Utils_Date::isoToMysql($params['acknowledge_date']),
        'is_test' => $params['is_test'],
        'status_id' => 2,
        'details' => $details,
        'campaign_id' => $params['campaign_id'] ?? NULL,
      ];

      // lets insert assignee record.
      if (!empty($params['contact_id'])) {
        $activityParams['assignee_contact_id'] = $params['contact_id'];
      }

      if (is_a(CRM_Activity_BAO_Activity::create($activityParams), 'CRM_Core_Error')) {
        throw new CRM_Core_Exception('Failed creating Activity for acknowledgment');
      }
    }
  }

  /**
   * Combine all the exportable fields from the lower levels object.
   *
   * @param bool $checkPermission
   *
   * @return array
   *   array of exportable Fields
   */
  public static function exportableFields($checkPermission = TRUE) {
    if (!self::$_exportableFields) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = [];
      }

      $fields = CRM_Pledge_DAO_Pledge::export();

      $fields = array_merge($fields, CRM_Pledge_DAO_PledgePayment::export());

      // set title to calculated fields
      $calculatedFields = [
        'pledge_total_paid' => ['title' => ts('Total Paid')],
        'pledge_balance_amount' => ['title' => ts('Balance Amount')],
        'pledge_next_pay_date' => ['title' => ts('Next Payment Date')],
        'pledge_next_pay_amount' => ['title' => ts('Next Payment Amount')],
        'pledge_payment_paid_amount' => ['title' => ts('Paid Amount')],
        'pledge_payment_paid_date' => ['title' => ts('Paid Date')],
        'pledge_payment_status' => [
          'title' => ts('Pledge Payment Status'),
          'name' => 'pledge_payment_status',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
      ];

      $pledgeFields = [
        'pledge_status' => [
          'title' => ts('Pledge Status'),
          'name' => 'pledge_status',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
        'pledge_frequency_unit' => [
          'title' => ts('Pledge Frequency Unit'),
          'name' => 'pledge_frequency_unit',
          'data_type' => CRM_Utils_Type::T_ENUM,
        ],
        'pledge_frequency_interval' => [
          'title' => ts('Pledge Frequency Interval'),
          'name' => 'pledge_frequency_interval',
          'data_type' => CRM_Utils_Type::T_INT,
        ],
        'pledge_contribution_page_id' => [
          'title' => ts('Pledge Contribution Page Id'),
          'name' => 'pledge_contribution_page_id',
          'data_type' => CRM_Utils_Type::T_INT,
        ],
      ];

      $fields = array_merge($fields, $pledgeFields, $calculatedFields);

      // add custom data
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Pledge', FALSE, FALSE, FALSE, $checkPermission));
      self::$_exportableFields = $fields;
    }

    return self::$_exportableFields;
  }

  /**
   * Get pending or in progress pledges.
   *
   * @param int $contactID
   *   Contact id.
   *
   * @return array
   *   associated array of pledge id(s)
   */
  public static function getContactPledges($contactID) {
    $pledgeDetails = [];
    $pledgeStatuses = CRM_Core_OptionGroup::values('pledge_status',
      FALSE, FALSE, FALSE, NULL, 'name'
    );

    $status = [];

    // get pending and in progress status
    foreach (['Pending', 'In Progress', 'Overdue'] as $name) {
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

    $params[1] = [$contactID, 'Integer'];
    $pledge = CRM_Core_DAO::executeQuery($query, $params);

    while ($pledge->fetch()) {
      $pledgeDetails[] = $pledge->id;
    }

    return $pledgeDetails;
  }

  /**
   * Get pledge record count for a Contact.
   *
   * @param int $contactID
   *
   * @return int
   *   count of pledge records
   */
  public static function getContactPledgeCount($contactID) {
    $query = "SELECT count(*) FROM civicrm_pledge WHERE civicrm_pledge.contact_id = {$contactID} AND civicrm_pledge.is_test = 0";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * @param array $params
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function updatePledgeStatus(array $params): array {

    $returnMessages = [];

    $sendReminders = $params['send_reminders'] ?? FALSE;

    $allStatus = array_flip(CRM_Pledge_BAO_PledgePayment::buildOptions('status_id', 'validate'));
    // We are left with 'Pending' & 'Overdue' - ie. payment required - should we just filter in the ones we want?
    unset($allStatus['Completed'], $allStatus['Cancelled']);

    $allPledgeStatus = array_flip(CRM_Pledge_BAO_Pledge::buildOptions('status_id', 'validate'));
    // We are left with 'Pending' & 'Overdue', 'In Progress'
    unset($allPledgeStatus['Completed'], $allPledgeStatus['Cancelled']);

    $statusIds = implode(',', $allStatus);
    $pledgeStatusIds = implode(',', $allPledgeStatus);
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
        AND     payment.status_id IN ( {$statusIds} ) AND pledge.status_id IN ( {$pledgeStatusIds} )
        GROUP By  payment.id
        ";

    $dao = CRM_Core_DAO::executeQuery($query);

    $now = date('Ymd');
    $pledgeDetails = $contactIds = $pledgePayments = $pledgeStatus = [];
    while ($dao->fetch()) {
      $checksumValue = CRM_Contact_BAO_Contact_Utils::generateChecksum($dao->contact_id);

      $pledgeDetails[$dao->payment_id] = [
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
      ];

      $contactIds[$dao->contact_id] = $dao->contact_id;
      $pledgeStatus[$dao->pledge_id] = $dao->pledge_status;

      if (CRM_Utils_Date::overdue(CRM_Utils_Date::customFormat($dao->scheduled_date, '%Y%m%d'),
          $now
        ) && $dao->payment_status != $allStatus['Overdue']
      ) {
        $pledgePayments[$dao->pledge_id][$dao->payment_id] = $dao->payment_id;
      }
    }
    $allPledgeStatus = array_flip($allPledgeStatus);

    // process the updating script...
    foreach ($pledgePayments as $pledgeId => $paymentIds) {
      // 1. update the pledge /pledge payment status. returns new status when an update happens
      $returnMessages[] = "Checking if status update is needed for Pledge Id: {$pledgeId} (current status is {$allPledgeStatus[$pledgeStatus[$pledgeId]]})";

      $newStatus = CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeId, $paymentIds,
        $allStatus['Overdue'], NULL, 0, FALSE, TRUE
      );
      if ($newStatus != $pledgeStatus[$pledgeId]) {
        $returnMessages[] = "- status updated to: {$allPledgeStatus[$newStatus]}";
        ++$updateCnt;
      }
    }

    if ($sendReminders) {

      // retrieve contact tokens
      // this function does NOT return Deceased contacts since we don't want to send them email
      $contactDetails = civicrm_api3('Contact', 'get', [
        'is_deceased' => 0,
        'id' => ['IN' => $contactIds],
        'return' => ['id', 'display_name', 'email', 'do_not_email', 'email', 'on_hold'],
      ])['values'];

      // assign domain values to template
      $template = CRM_Core_Smarty::singleton();

      // set receipt from
      $receiptFrom = CRM_Core_BAO_Domain::getNameAndEmail(FALSE, TRUE);
      $receiptFrom = reset($receiptFrom);

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
          $details['initial_reminder_day'] = empty($details['initial_reminder_day']) ? 0 : $details['initial_reminder_day'];
          $nextReminderDate->modify("-" . $details['initial_reminder_day'] . "day");
          $nextReminderDate = $nextReminderDate->format("Ymd");
        }
        else {
          $nextReminderDate = new DateTime($details['reminder_date']);
          $details['additional_reminder_day'] = empty($details['additional_reminder_day']) ? 0 : $details['additional_reminder_day'];
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
            // assign value to template
            $template->assign('amount_paid', $details['amount_paid'] ?: 0);
            $template->assign('next_payment', $details['scheduled_date']);
            $template->assign('amount_due', $details['amount_due']);
            $template->assign('checksumValue', $details['checksumValue']);
            $template->assign('contribution_page_id', $details['contribution_page_id']);
            $template->assign('pledge_id', $details['pledge_id']);
            $template->assign('scheduled_payment_date', $details['scheduled_date']);
            $template->assign('amount', $details['amount']);
            $template->assign('create_date', $details['create_date']);
            $template->assign('currency', $details['currency']);
            [
              $mailSent,
              $subject,
              $message,
              $html,
            ] = CRM_Core_BAO_MessageTemplate::sendTemplate(
              [
                'groupName' => 'msg_tpl_workflow_pledge',
                'workflow' => 'pledge_reminder',
                'contactId' => $contactId,
                'from' => $receiptFrom,
                'toName' => $pledgerName,
                'toEmail' => $toEmail,
              ]
            );

            // 3. update pledge payment details
            if ($mailSent) {
              CRM_Pledge_BAO_PledgePayment::updateReminderDetails($paymentId);
              $activityType = 'Pledge Reminder';
              $activityParams = [
                'subject' => $subject,
                'source_contact_id' => $contactId,
                'source_record_id' => $paymentId,
                'assignee_contact_id' => $contactId,
                'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity',
                  'activity_type_id',
                  $activityType
                ),
                'due_date_time' => CRM_Utils_Date::isoToMysql($details['scheduled_date']),
                'is_test' => $details['is_test'],
                'status_id' => 2,
                'campaign_id' => $details['campaign_id'],
              ];
              try {
                civicrm_api3('activity', 'create', $activityParams);
              }
              catch (CRM_Core_Exception $e) {
                throw new CRM_Core_Exception('Failed creating Activity for Pledge Reminder: ' . $e->getMessage());
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

    return $returnMessages;
  }

  /**
   * Mark a pledge (and any outstanding payments) as cancelled.
   *
   * @param int $pledgeID
   */
  public static function cancel($pledgeID) {
    $paymentIDs = self::findCancelablePayments($pledgeID);
    $status = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $cancelled = array_search('Cancelled', $status);
    CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeID, $paymentIDs, NULL,
      $cancelled, 0, FALSE, TRUE
    );
  }

  /**
   * Find payments which can be safely canceled.
   *
   * @param int $pledgeID
   *
   * @return array
   *   Array of int (civicrm_pledge_payment.id)
   */
  public static function findCancelablePayments($pledgeID) {
    $statuses = array_flip(CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label'));

    $paymentDAO = new CRM_Pledge_DAO_PledgePayment();
    $paymentDAO->pledge_id = $pledgeID;
    $paymentDAO->whereAdd(sprintf("status_id IN (%d,%d)",
      $statuses['Overdue'],
      $statuses['Pending']
    ));
    $paymentDAO->find();

    $paymentIDs = [];
    while ($paymentDAO->fetch()) {
      $paymentIDs[] = $paymentDAO->id;
    }
    return $paymentIDs;
  }

  /**
   * Is this pledge free from financial transactions (this is important to know
   * as we allow editing when no transactions have taken place - the editing
   * process currently involves deleting all pledge payments & contributions
   * & recreating so we want to block that if appropriate).
   *
   * @param int $pledgeID
   * @param int $pledgeStatusID
   *
   * @return bool
   *   do financial transactions exist for this pledge?
   */
  public static function pledgeHasFinancialTransactions($pledgeID, $pledgeStatusID) {
    if (empty($pledgeStatusID)) {
      // why would this happen? If we can see where it does then we can see if we should look it up.
      // but assuming from form code it CAN be empty.
      return TRUE;
    }
    if (self::isTransactedStatus($pledgeStatusID)) {
      return TRUE;
    }

    return civicrm_api3('pledge_payment', 'getcount', [
      'pledge_id' => $pledgeID,
      'contribution_id' => ['IS NOT NULL' => TRUE],
    ]);
  }

  /**
   * Does this pledge / pledge payment status mean that a financial transaction
   * has taken place?
   *
   * @param int $statusID
   *   Pledge status id.
   *
   * @return bool
   *   is it a transactional status?
   */
  protected static function isTransactedStatus($statusID) {
    if (!in_array($statusID, self::getNonTransactionalStatus())) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get array of non transactional statuses.
   *
   * @return array
   *   non transactional status ids
   */
  protected static function getNonTransactionalStatus() {
    $paymentStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    return array_flip(array_intersect($paymentStatus, ['Overdue', 'Pending']));
  }

  /**
   * Create array for recur record for pledge.
   *
   * @return array
   *   params for recur record
   */
  public static function buildRecurParams($params) {
    $recurParams = [
      'is_recur' => TRUE,
      'auto_renew' => TRUE,
      'frequency_unit' => $params['pledge_frequency_unit'],
      'frequency_interval' => $params['pledge_frequency_interval'],
      'installments' => $params['pledge_installments'],
      'start_date' => $params['receive_date'],
    ];
    return $recurParams;
  }

  /**
   * Get pledge start date.
   *
   * @return string
   *   start date
   */
  public static function getPledgeStartDate($date, $pledgeBlock) {
    $startDate = (array) json_decode($pledgeBlock['pledge_start_date']);
    foreach ($startDate as $field => $value) {
      if (!empty($date) && empty($pledgeBlock['is_pledge_start_date_editable'])) {
        return $date;
      }
      if (empty($date)) {
        $date = $value;
      }
      switch ($field) {
        case 'contribution_date':
          if (empty($date)) {
            $date = date('Ymd');
          }
          break;

        case 'calendar_date':
          $date = date('Ymd', strtotime($date));
          break;

        case 'calendar_month':
          $date = self::getPaymentDate($date);
          $date = date('Ymd', strtotime($date));
          break;

        default:
          break;

      }
    }
    return $date;
  }

  /**
   * Get first payment date for pledge.
   *
   * @param int $day
   *
   * @return bool|string
   */
  public static function getPaymentDate($day) {
    if ($day == 31) {
      // Find out if current month has 31 days, if not, set it to 30 (last day).
      $t = date('t');
      if ($t != $day) {
        $day = $t;
      }
    }
    $current = date('d');
    switch (TRUE) {
      case ($day == $current):
        $date = date('m/d/Y');
        break;

      case ($day > $current):
        $date = date('m/d/Y', mktime(0, 0, 0, date('m'), $day, date('Y')));
        break;

      case ($day < $current):
        $date = date('m/d/Y', mktime(0, 0, 0, date('m', strtotime("+1 month")), $day, date('Y')));
        break;

      default:
        break;

    }
    return $date;
  }

  /**
   * Pseudoconstant condition_provider for status_id field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterStatus(string $fieldName, CRM_Utils_SQL_Select $conditions, $params) {
    if ($fieldName == 'status_id' && !$params['include_disabled']) {
      $conditions->where('name NOT IN (@status)', ['status' => ['Failed']]);
    }
  }

}
