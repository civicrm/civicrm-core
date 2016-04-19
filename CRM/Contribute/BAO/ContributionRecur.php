<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * @copyright CiviCRM LLC (c) 2004-2015
 * $Id$
 *
 */
class CRM_Contribute_BAO_ContributionRecur extends CRM_Contribute_DAO_ContributionRecur {

  /**
   * Create recurring contribution.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return object
   *   activity contact object
   */
  public static function create(&$params) {
    return self::add($params);
  }

  /**
   * Takes an associative array and creates a contribution object.
   *
   * the function extract all the params it needs to initialize the create a
   * contribution object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Contribute_BAO_Contribution
   * @todo move hook calls / extended logic to create - requires changing calls to call create not add
   */
  public static function add(&$params) {
    if (!empty($params['id'])) {
      CRM_Utils_Hook::pre('edit', 'ContributionRecur', $params['id'], $params);
    }
    else {
      CRM_Utils_Hook::pre('create', 'ContributionRecur', NULL, $params);
    }

    // make sure we're not creating a new recurring contribution with the same transaction ID
    // or invoice ID as an existing recurring contribution
    $duplicates = array();
    if (self::checkDuplicate($params, $duplicates)) {
      $error = CRM_Core_Error::singleton();
      $d = implode(', ', $duplicates);
      $error->push(CRM_Core_Error::DUPLICATE_CONTRIBUTION,
        'Fatal',
        array($d),
        "Found matching recurring contribution(s): $d"
      );
      return $error;
    }

    $recurring = new CRM_Contribute_BAO_ContributionRecur();
    $recurring->copyValues($params);
    $recurring->id = CRM_Utils_Array::value('id', $params);

    // set currency for CRM-1496
    if (!isset($recurring->currency)) {
      $config = CRM_Core_Config::singleton();
      $recurring->currency = $config->defaultCurrency;
    }
    $result = $recurring->save();

    if (!empty($params['id'])) {
      CRM_Utils_Hook::post('edit', 'ContributionRecur', $recurring->id, $recurring);
    }
    else {
      CRM_Utils_Hook::post('create', 'ContributionRecur', $recurring->id, $recurring);
    }

    return $result;
  }

  /**
   * Check if there is a recurring contribution with the same trxn_id or invoice_id.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $duplicates
   *   (reference ) store ids of duplicate contribs.
   *
   * @return bool
   *   true if duplicate, false otherwise
   */
  public static function checkDuplicate($params, &$duplicates) {
    $id = CRM_Utils_Array::value('id', $params);
    $trxn_id = CRM_Utils_Array::value('trxn_id', $params);
    $invoice_id = CRM_Utils_Array::value('invoice_id', $params);

    $clause = array();
    $params = array();

    if ($trxn_id) {
      $clause[] = "trxn_id = %1";
      $params[1] = array($trxn_id, 'String');
    }

    if ($invoice_id) {
      $clause[] = "invoice_id = %2";
      $params[2] = array($invoice_id, 'String');
    }

    if (empty($clause)) {
      return FALSE;
    }

    $clause = implode(' OR ', $clause);
    if ($id) {
      $clause = "( $clause ) AND id != %3";
      $params[3] = array($id, 'Integer');
    }

    $query = "SELECT id FROM civicrm_contribution_recur WHERE $clause";
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $result = FALSE;
    while ($dao->fetch()) {
      $duplicates[] = $dao->id;
      $result = TRUE;
    }
    return $result;
  }

  /**
   * @param int $id
   * @param $mode
   *
   * @return array|null
   */
  public static function getPaymentProcessor($id, $mode) {
    //FIX ME:
    $sql = "
SELECT r.payment_processor_id
  FROM civicrm_contribution_recur r
 WHERE r.id = %1";
    $params = array(1 => array($id, 'Integer'));
    $paymentProcessorID = &CRM_Core_DAO::singleValueQuery($sql,
      $params
    );
    if (!$paymentProcessorID) {
      return NULL;
    }

    return CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID, $mode);
  }

  /**
   * Get the number of installment done/completed for each recurring contribution
   *
   * @param array $ids
   *   (reference ) an array of recurring contribution ids.
   *
   * @return array
   *   an array of recurring ids count
   */
  public static function getCount(&$ids) {
    $recurID = implode(',', $ids);
    $totalCount = array();

    $query = "
         SELECT contribution_recur_id, count( contribution_recur_id ) as commpleted
         FROM civicrm_contribution
         WHERE contribution_recur_id IN ( {$recurID}) AND is_test = 0
         GROUP BY contribution_recur_id";

    $res = CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);

    while ($res->fetch()) {
      $totalCount[$res->contribution_recur_id] = $res->commpleted;
    }
    return $totalCount;
  }

  /**
   * Delete Recurring contribution.
   *
   * @param int $recurId
   *
   * @return bool
   */
  public static function deleteRecurContribution($recurId) {
    $result = FALSE;
    if (!$recurId) {
      return $result;
    }

    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    $result = $recur->delete();

    return $result;
  }

  /**
   * Cancel Recurring contribution.
   *
   * @param int $recurId
   *   Recur contribution id.
   * @param array $objects
   *   An array of objects that is to be cancelled like.
   *                          contribution, membership, event. At least contribution object is a must.
   *
   * @param array $activityParams
   *
   * @return bool
   */
  public static function cancelRecurContribution($recurId, $objects, $activityParams = array()) {
    if (!$recurId) {
      return FALSE;
    }

    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
    $canceledId = array_search('Cancelled', $contributionStatus);
    $recur = new CRM_Contribute_DAO_ContributionRecur();
    $recur->id = $recurId;
    $recur->whereAdd("contribution_status_id != $canceledId");

    if ($recur->find(TRUE)) {
      $transaction = new CRM_Core_Transaction();
      $recur->contribution_status_id = $canceledId;
      $recur->start_date = CRM_Utils_Date::isoToMysql($recur->start_date);
      $recur->create_date = CRM_Utils_Date::isoToMysql($recur->create_date);
      $recur->modified_date = CRM_Utils_Date::isoToMysql($recur->modified_date);
      $recur->cancel_date = date('YmdHis');
      $recur->save();

      $dao = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($recurId);
      if ($dao && $dao->recur_id) {
        $details = CRM_Utils_Array::value('details', $activityParams);
        if ($dao->auto_renew && $dao->membership_id) {
          // its auto-renewal membership mode
          $membershipTypes = CRM_Member_PseudoConstant::membershipType();
          $membershipType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $dao->membership_id, 'membership_type_id');
          $membershipType = CRM_Utils_Array::value($membershipType, $membershipTypes);
          $details .= '
<br/>' . ts('Automatic renewal of %1 membership cancelled.', array(1 => $membershipType));
        }
        else {
          $details .= '
<br/>' . ts('The recurring contribution of %1, every %2 %3 has been cancelled.', array(
              1 => $dao->amount,
              2 => $dao->frequency_interval,
              3 => $dao->frequency_unit,
            ));
        }
        $activityParams = array(
          'source_contact_id' => $dao->contact_id,
          'source_record_id' => CRM_Utils_Array::value('source_record_id', $activityParams),
          'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
            'Cancel Recurring Contribution',
            'name'
          ),
          'subject' => CRM_Utils_Array::value('subject', $activityParams, ts('Recurring contribution cancelled')),
          'details' => $details,
          'activity_date_time' => date('YmdHis'),
          'status_id' => CRM_Core_OptionGroup::getValue('activity_status',
            'Completed',
            'name'
          ),
        );
        $session = CRM_Core_Session::singleton();
        $cid = $session->get('userID');
        if ($cid) {
          $activityParams['target_contact_id'][] = $activityParams['source_contact_id'];
          $activityParams['source_contact_id'] = $cid;
        }
        CRM_Activity_BAO_Activity::create($activityParams);
      }

      // if there are associated objects, cancel them as well
      if ($objects == CRM_Core_DAO::$_nullObject) {
        $transaction->commit();
        return TRUE;
      }
      else {
        $baseIPN = new CRM_Core_Payment_BaseIPN();
        return $baseIPN->cancelled($objects, $transaction);
      }
    }
    else {
      // if already cancelled, return true
      $recur->whereAdd();
      $recur->whereAdd("contribution_status_id = $canceledId");
      if ($recur->find(TRUE)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get list of recurring contribution of contact Ids.
   *
   * @param int $contactId
   *   Contact ID.
   *
   * @return array
   *   list of recurring contribution fields
   *
   */
  public static function getRecurContributions($contactId) {
    $params = array();
    $recurDAO = new CRM_Contribute_DAO_ContributionRecur();
    $recurDAO->contact_id = $contactId;
    $recurDAO->find();
    $contributionStatus = CRM_Contribute_PseudoConstant::contributionStatus();

    while ($recurDAO->fetch()) {
      $params[$recurDAO->id]['id'] = $recurDAO->id;
      $params[$recurDAO->id]['contactId'] = $recurDAO->contact_id;
      $params[$recurDAO->id]['start_date'] = $recurDAO->start_date;
      $params[$recurDAO->id]['end_date'] = $recurDAO->end_date;
      $params[$recurDAO->id]['next_sched_contribution_date'] = $recurDAO->next_sched_contribution_date;
      $params[$recurDAO->id]['amount'] = $recurDAO->amount;
      $params[$recurDAO->id]['currency'] = $recurDAO->currency;
      $params[$recurDAO->id]['frequency_unit'] = $recurDAO->frequency_unit;
      $params[$recurDAO->id]['frequency_interval'] = $recurDAO->frequency_interval;
      $params[$recurDAO->id]['installments'] = $recurDAO->installments;
      $params[$recurDAO->id]['contribution_status_id'] = $recurDAO->contribution_status_id;
      $params[$recurDAO->id]['contribution_status'] = CRM_Utils_Array::value($recurDAO->contribution_status_id, $contributionStatus);
      $params[$recurDAO->id]['is_test'] = $recurDAO->is_test;
      $params[$recurDAO->id]['payment_processor_id'] = $recurDAO->payment_processor_id;
    }

    return $params;
  }

  /**
   * @param int $entityID
   * @param string $entity
   *
   * @return null|Object
   */
  public static function getSubscriptionDetails($entityID, $entity = 'recur') {
    $sql = "
SELECT rec.id                   as recur_id,
       rec.processor_id         as subscription_id,
       rec.frequency_interval,
       rec.installments,
       rec.frequency_unit,
       rec.amount,
       rec.is_test,
       rec.auto_renew,
       rec.currency,
       rec.campaign_id,
       rec.financial_type_id,
       con.id as contribution_id,
       con.contribution_page_id,
       rec.contact_id,
       mp.membership_id";

    if ($entity == 'recur') {
      $sql .= "
      FROM civicrm_contribution_recur rec
LEFT JOIN civicrm_contribution       con ON ( con.contribution_recur_id = rec.id )
LEFT  JOIN civicrm_membership_payment mp  ON ( mp.contribution_id = con.id )
     WHERE rec.id = %1
  GROUP BY rec.id";
    }
    elseif ($entity == 'contribution') {
      $sql .= "
      FROM civicrm_contribution       con
INNER JOIN civicrm_contribution_recur rec ON ( con.contribution_recur_id = rec.id )
LEFT  JOIN civicrm_membership_payment mp  ON ( mp.contribution_id = con.id )
     WHERE con.id = %1";
    }
    elseif ($entity == 'membership') {
      $sql .= "
      FROM civicrm_membership_payment mp
INNER JOIN civicrm_membership         mem ON ( mp.membership_id = mem.id )
INNER JOIN civicrm_contribution_recur rec ON ( mem.contribution_recur_id = rec.id )
INNER JOIN civicrm_contribution       con ON ( con.id = mp.contribution_id )
     WHERE mp.membership_id = %1";
    }

    $dao = CRM_Core_DAO::executeQuery($sql, array(1 => array($entityID, 'Integer')));
    if ($dao->fetch()) {
      return $dao;
    }
    else {
      return NULL;
    }
  }

  /**
   * Does the recurring contribution support financial type change.
   *
   * This is conditional on there being only one line item or if there are no contributions as yet.
   *
   * (This second is a bit of an unusual condition but might occur in the context of a
   *
   * @param int $id
   *
   * @return bool
   */
  public static function supportsFinancialTypeChange($id) {
    $contribution = self::getTemplateContribution($id);
    return CRM_Contribute_BAO_Contribution::isSingleLineItem($contribution['id']);
  }

  /**
   * Get the contribution to be used as the template for later contributions.
   *
   * Later we might merge in data stored against the contribution recur record rather than just return the contribution.
   *
   * @param int $id
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function getTemplateContribution($id) {
    $templateContribution = civicrm_api3('Contribution', 'get', array(
      'contribution_recur_id' => $id,
      'options' => array('limit' => 1, 'sort' => array('id DESC')),
      'sequential' => 1,
    ));
    if ($templateContribution['count']) {
      return $templateContribution['values'][0];
    }
    return array();
  }

  public static function setSubscriptionContext() {
    // handle context redirection for subscription url
    $session = CRM_Core_Session::singleton();
    if ($session->get('userID')) {
      $url = FALSE;
      $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
      $mid = CRM_Utils_Request::retrieve('mid', 'Integer');
      $qfkey = CRM_Utils_Request::retrieve('key', 'String');
      $context = CRM_Utils_Request::retrieve('context', 'String');
      if ($cid) {
        switch ($context) {
          case 'contribution':
            $url = CRM_Utils_System::url('civicrm/contact/view',
              "reset=1&selectedChild=contribute&cid={$cid}"
            );
            break;

          case 'membership':
            $url = CRM_Utils_System::url('civicrm/contact/view',
              "reset=1&selectedChild=member&cid={$cid}"
            );
            break;

          case 'dashboard':
            $url = CRM_Utils_System::url('civicrm/user', "reset=1&id={$cid}");
            break;
        }
      }
      if ($mid) {
        switch ($context) {
          case 'dashboard':
            $url = CRM_Utils_System::url('civicrm/member', "force=1&context={$context}&key={$qfkey}");
            break;

          case 'search':
            $url = CRM_Utils_System::url('civicrm/member/search', "force=1&context={$context}&key={$qfkey}");
            break;
        }
      }
      if ($url) {
        $session->pushUserContext($url);
      }
    }
  }

  /**
   * CRM-16285 - Function to handle validation errors on form, for recurring contribution field.
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param $self
   */
  public static function validateRecurContribution($fields, $files, $self, &$errors) {
    if (!empty($fields['is_recur'])) {
      if ($fields['frequency_interval'] <= 0) {
        $errors['frequency_interval'] = ts('Please enter a number for how often you want to make this recurring contribution (EXAMPLE: Every 3 months).');
      }
      if ($fields['frequency_unit'] == '0') {
        $errors['frequency_unit'] = ts('Please select a period (e.g. months, years ...) for how often you want to make this recurring contribution (EXAMPLE: Every 3 MONTHS).');
      }
    }
  }

}
