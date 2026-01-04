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

use Brick\Money\Money;
use Civi\Api4\Contribution;
use Civi\Api4\ContributionRecur;
use Civi\Api4\LineItem;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contribute_BAO_ContributionRecur extends CRM_Contribute_DAO_ContributionRecur implements Civi\Core\HookInterface {

  /**
   * Create recurring contribution.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return object
   *   activity contact object
   *
   * @deprecated since 5.76 will be removed around 5.90.
   * Non-core users should all use the api.
   */
  public static function create(&$params) {
    CRM_Core_Error::deprecatedFunctionWarning('writeRecord');
    return self::add($params);
  }

  /**
   * Takes an associative array and creates a contribution object.
   *
   * the function extract all the params it needs to initialize the create a
   * contribution object. the params array could contain additional unused
   * name/value pairs
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return \CRM_Contribute_BAO_ContributionRecur
   * @throws \CRM_Core_Exception
   * @deprecated since 5.76 will be removed around 5.96.
   * Non-core users should all use the api. As of writing there
   * are some core users.
   */
  public static function add(&$params) {
    return self::writeRecord($params);
  }

  /**
   * Event fired before modifying an IM.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if (in_array($event->action, ['create', 'edit'])) {
      // make sure we're not creating a new recurring contribution with the same transaction ID
      // or invoice ID as an existing recurring contribution
      $duplicates = [];
      if (self::checkDuplicate($event->params, $duplicates)) {
        throw new CRM_Core_Exception('Found matching recurring contribution(s): ' . implode(', ', $duplicates));
      }
      if ($event->action === 'create' && empty($event->params['currency'])) {
        // set currency for CRM-1496
        $event->params['currency'] = \Civi::settings()->get('defaultCurrency');
      }
    }
  }

  /**
   * Event fired after modifying a recurring contribution.
   * @param \Civi\Core\Event\PostEvent $event
   */
  public static function self_hook_civicrm_post(\Civi\Core\Event\PostEvent $event) {
    if ($event->action === 'edit') {
      if (is_numeric($event->object->amount)) {
        $templateContribution = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution($event->object->id);
        if (empty($templateContribution['id'])) {
          return;
        }
        $lines = LineItem::get(FALSE)
          ->addWhere('contribution_id', '=', $templateContribution['id'])
          ->addWhere('contribution_id.is_template', '=', TRUE)
          ->addSelect('contribution_id.total_amount')
          ->execute();
        if (count($lines) === 1) {
          $contributionAmount = $lines->first()['contribution_id.total_amount'];
          // USD here is just ensuring both are in the same format.
          if (Money::of($contributionAmount, 'USD')->compareTo(Money::of($event->object->amount, 'USD'))) {
            // If different then we need to update
            // the contribution. Note that if this is being called
            // as a result of the contribution having been updated then there will
            // be no difference.
            Contribution::update(FALSE)
              ->addWhere('id', '=', $templateContribution['id'])
              ->setValues(['total_amount' => $event->object->amount])
              ->execute();
          }
        }
      }
    }
  }

  /**
   * Check if there is a recurring contribution with the same trxn_id or invoice_id.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $duplicates
   *   (reference ) store ids of duplicate contributions.
   *
   * @return bool
   *   true if duplicate, false otherwise
   */
  public static function checkDuplicate($params, &$duplicates) {
    $id = $params['id'] ?? NULL;
    $trxn_id = $params['trxn_id'] ?? NULL;
    $invoice_id = $params['invoice_id'] ?? NULL;

    $clause = [];
    $params = [];

    if ($trxn_id) {
      $clause[] = "trxn_id = %1";
      $params[1] = [$trxn_id, 'String'];
    }

    if ($invoice_id) {
      $clause[] = "invoice_id = %2";
      $params[2] = [$invoice_id, 'String'];
    }

    if (empty($clause)) {
      return FALSE;
    }

    $clause = implode(' OR ', $clause);
    if ($id) {
      $clause = "( $clause ) AND id != %3";
      $params[3] = [$id, 'Integer'];
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
   * Get the payment processor (array) for a recurring processor.
   *
   * @param int $id
   *
   * @return array|null
   */
  public static function getPaymentProcessor($id) {
    $paymentProcessorID = self::getPaymentProcessorID($id);
    return CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID);
  }

  /**
   * Get the processor object for the recurring contribution record.
   *
   * @param int $id
   *
   * @return CRM_Core_Payment|NULL
   *   Returns a processor object or NULL if the processor is disabled.
   *   Note this returns the 'Manual' processor object if no processor is attached
   *   (since it still makes sense to update / cancel
   */
  public static function getPaymentProcessorObject($id) {
    CRM_Core_Error::deprecatedFunctionWarning('Use Civi\Payment\System');
    $processor = self::getPaymentProcessor($id);
    return is_array($processor) ? $processor['object'] : NULL;
  }

  /**
   * Get the payment processor for the given recurring contribution.
   *
   * @param int $recurID
   *
   * @return int
   *   Payment processor id. If none found return 0 which represents the
   *   pseudo processor used for pay-later.
   */
  public static function getPaymentProcessorID($recurID) {
    $recur = civicrm_api3('ContributionRecur', 'getsingle', [
      'id' => $recurID,
      'return' => ['payment_processor_id'],
    ]);
    return (int) ($recur['payment_processor_id'] ?? 0);
  }

  /**
   * Get the number of installment done/completed for each recurring contribution.
   *
   * @param array $ids
   *   (reference ) an array of recurring contribution ids.
   *
   * @deprecated use the api.
   *
   * @return array
   *   an array of recurring ids count
   */
  public static function getCount(&$ids) {
    $recurID = implode(',', $ids);
    $totalCount = [];

    $query = "
         SELECT contribution_recur_id, count( contribution_recur_id ) as commpleted
         FROM civicrm_contribution
         WHERE contribution_recur_id IN ( {$recurID}) AND is_test = 0
         GROUP BY contribution_recur_id";

    $res = CRM_Core_DAO::executeQuery($query);

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
   * @param array $params
   *   Recur contribution params
   *
   * @return bool
   * @throws \CRM_Core_Exception
   * @internal
   *
   */
  public static function cancelRecurContribution(array $params): bool {
    if (!$params['id']) {
      return FALSE;
    }
    $transaction = new CRM_Core_Transaction();
    ContributionRecur::update(FALSE)
      ->addWhere('id', '=', $params['id'])
      ->setValues([
        'contribution_status_id:name' => 'Cancelled',
        'cancel_reason' => $params['cancel_reason'] ?? NULL,
        'cancel_date' => $params['cancel_date'] ?? 'now',
      ])->execute();

    // @todo - all of this should be moved to the post hook.
    // It seems to just create activities.
    $dao = CRM_Contribute_BAO_ContributionRecur::getSubscriptionDetails($params['id']);
    if ($dao && $dao->recur_id) {
      $details = $params['processor_message'] ?? NULL;
      if ($dao->auto_renew && $dao->membership_id) {
        // its auto-renewal membership mode
        $membershipTypes = CRM_Member_PseudoConstant::membershipType();
        $membershipType = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $dao->membership_id, 'membership_type_id');
        $membershipType = $membershipTypes[$membershipType] ?? NULL;
        $details .= '
<br/>' . ts('Automatic renewal of %1 membership cancelled.', [1 => $membershipType]);
      }
      else {
        $details .= '<br/>' . ts('The recurring contribution of %1, every %2 %3 has been cancelled.', [
          1 => $dao->amount,
          2 => $dao->frequency_interval,
          3 => $dao->frequency_unit,
        ]);
      }
      $activityParams = [
        'source_contact_id' => $dao->contact_id,
        'source_record_id' => $dao->recur_id,
        'activity_type_id' => 'Cancel Recurring Contribution',
        'subject' => !empty($params['membership_id']) ? ts('Auto-renewal membership cancelled') : ts('Recurring contribution cancelled'),
        'details' => $details,
        'status_id' => 'Completed',
      ];

      $cid = CRM_Core_Session::singleton()->get('userID');
      if ($cid) {
        $activityParams['target_contact_id'][] = $activityParams['source_contact_id'];
        $activityParams['source_contact_id'] = $cid;
      }
      civicrm_api3('Activity', 'create', $activityParams);
    }
    $transaction->commit();
    return TRUE;
  }

  /**
   * @param int $recurringContributionID
   *
   * @return null|Object
   */
  public static function getSubscriptionDetails($recurringContributionID) {
    // Note: processor_id used to be aliased as subscription_id so we include it here
    // both as processor_id and subscription_id for legacy compatibility.
    $sql = "
SELECT rec.id                   as recur_id,
       rec.processor_id         as subscription_id,
       rec.processor_id,
       rec.frequency_interval,
       rec.installments,
       rec.frequency_unit,
       rec.amount,
       rec.is_test,
       rec.auto_renew,
       rec.currency,
       rec.campaign_id,
       rec.financial_type_id,
       rec.next_sched_contribution_date,
       rec.failure_retry_date,
       rec.cycle_day,
       con.id as contribution_id,
       con.contribution_page_id,
       rec.contact_id,
       mp.membership_id
      FROM civicrm_contribution_recur rec
LEFT JOIN civicrm_contribution       con ON ( con.contribution_recur_id = rec.id )
LEFT  JOIN civicrm_membership_payment mp  ON ( mp.contribution_id = con.id )
     WHERE rec.id = %1";

    $dao = CRM_Core_DAO::executeQuery($sql, [1 => [$recurringContributionID, 'Integer']]);
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
    // At this stage only sites with no Financial ACLs will have the opportunity to edit the financial type.
    // this is to limit the scope of the change and because financial ACLs are still fairly new & settling down.
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      return FALSE;
    }
    $contribution = self::getTemplateContribution($id);
    return CRM_Contribute_BAO_Contribution::isSingleLineItem($contribution['id']);
  }

  /**
   * Create a template contribution based on the first contribution of an
   * recurring contribution.
   * When a template contribution already exists this function will not try to create
   * a new one.
   * This way we make sure only one template contribution exists.
   *
   * @param int $id
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @return int|NULL the ID of the newly created template contribution.
   */
  public static function ensureTemplateContributionExists(int $id) {
    // Check if a template contribution already exists.
    $templateContributions = Contribution::get(FALSE)
      ->addWhere('contribution_recur_id', '=', $id)
      ->addWhere('is_template', '=', 1)
      // we need this line otherwise the is test contribution don't work.
      ->addWhere('is_test', 'IN', [0, 1])
      ->addOrderBy('receive_date', 'DESC')
      ->setLimit(1)
      ->execute();
    if ($templateContributions->count()) {
      // A template contribution already exists.
      // Skip the creation of a new one.
      return $templateContributions->first()['id'];
    }

    // Retrieve the most recently added contribution
    $mostRecentContribution = Contribution::get(FALSE)
      ->addSelect('custom.*', 'id', 'contact_id', 'campaign_id', 'financial_type_id', 'payment_instrument_id', 'currency', 'source', 'amount_level', 'address_id', 'on_behalf', 'source_contact_id', 'tax_amount', 'contribution_page_id', 'total_amount', 'is_test')
      ->addWhere('contribution_recur_id', '=', $id)
      ->addWhere('is_template', '=', 0)
      // we need this line otherwise the is test contribution don't work.
      ->addWhere('is_test', 'IN', [0, 1])
      ->addOrderBy('receive_date', 'DESC')
      ->setLimit(1)
      ->execute()
      ->first();
    if (!$mostRecentContribution) {
      // No first contribution is found.
      return NULL;
    }

    $order = new CRM_Financial_BAO_Order();
    $order->setTemplateContributionID($mostRecentContribution['id']);
    $order->setOverrideFinancialTypeID($overrides['financial_type_id'] ?? NULL);
    $order->setOverridableFinancialTypeID($mostRecentContribution['financial_type_id']);
    $order->setOverrideTotalAmount($mostRecentContribution['total_amount'] ?? NULL);
    $order->setIsPermitOverrideFinancialTypeForMultipleLines(FALSE);
    $line_items = $order->getLineItems();
    $mostRecentContribution['line_item'][$order->getPriceSetID()] = $line_items;

    // If the template contribution was made on-behalf then add the
    // relevant values to ensure the activity reflects that.
    $relatedContact = CRM_Contribute_BAO_Contribution::getOnbehalfIds($mostRecentContribution['id']);

    $templateContributionParams = $mostRecentContribution;
    unset($templateContributionParams['id']);
    $templateContributionParams['is_template'] = '1';
    $templateContributionParams['contribution_status_id:name'] = 'Template';
    $templateContributionParams['skipRecentView'] = TRUE;
    $templateContributionParams['contribution_recur_id'] = $id;
    if (!empty($relatedContact['individual_id'])) {
      $templateContributionParams['on_behalf'] = TRUE;
      $templateContributionParams['source_contact_id'] = $relatedContact['individual_id'];
    }
    $templateContributionParams['source'] ??= ts('Recurring contribution');
    $templateContribution = Contribution::create(FALSE)
      ->setValues($templateContributionParams)
      ->execute()
      ->first();
    // Add new soft credit against current $contribution.
    CRM_Contribute_BAO_ContributionRecur::addrecurSoftCredit($templateContributionParams['contribution_recur_id'], $templateContribution['id']);
    return $templateContribution['id'];
  }

  /**
   * Get the contribution to be used as the template for later contributions.
   *
   * Later we might merge in data stored against the contribution recur record rather than just return the contribution.
   *
   * @param int $id
   * @param array $inputOverrides
   *   Parameters that should be overridden. Add unit tests if using parameters other than total_amount & financial_type_id.
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  public static function getTemplateContribution(int $id, array $inputOverrides = []): array {
    $recurringContribution = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $id)
      ->setSelect(['is_test', 'financial_type_id', 'amount', 'campaign_id'])
      ->execute()
      ->first();

    // Parameters passed into the function take precedences, falling back to those loaded from
    // the recurring contribution.
    // we filter out null, '' and FALSE but not zero - I'm on the fence about zero.
    $overrides = array_filter([
      'is_test' => $inputOverrides['is_test'] ?? $recurringContribution['is_test'],
      'financial_type_id' => $inputOverrides['financial_type_id'] ?? ($recurringContribution['financial_type_id'] ?? ''),
      'campaign_id' => $inputOverrides['campaign_id'] ?? ($recurringContribution['campaign_id'] ?? ''),
      'total_amount' => $inputOverrides['total_amount'] ?? $recurringContribution['amount'],
    ], 'strlen');

    // First look for new-style template contribution with is_template=1
    $templateContributions = Contribution::get(FALSE)
      ->addWhere('contribution_recur_id', '=', $id)
      ->addWhere('is_template', '=', 1)
      ->addWhere('is_test', '=', $recurringContribution['is_test'])
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute();
    if (!$templateContributions->count()) {
      // Fall back to old style template contributions
      $templateContributions = Contribution::get(FALSE)
        ->addWhere('contribution_recur_id', '=', $id)
        ->addWhere('is_test', '=', $recurringContribution['is_test'])
        ->addOrderBy('id', 'DESC')
        ->setLimit(1)
        ->execute();
    }
    if ($templateContributions->count()) {
      $templateContribution = $templateContributions->first();
      $order = new CRM_Financial_BAO_Order();
      $order->setTemplateContributionID($templateContribution['id']);
      $order->setOverrideFinancialTypeID($overrides['financial_type_id'] ?? NULL);
      $order->setOverridableFinancialTypeID($templateContribution['financial_type_id']);
      $order->setOverrideTotalAmount($overrides['total_amount'] ?? NULL);
      $order->setIsPermitOverrideFinancialTypeForMultipleLines(FALSE);
      $lineItems = $order->getLineItems();
      // We only permit the financial type to be overridden for single line items.
      // Otherwise we need to figure out a whole lot of extra complexity.
      // It's not UI-possible to alter financial_type_id for recurring contributions
      // with more than one line item.
      // The handling of the line items is managed in BAO_Order so this
      // is whether we should override on the contribution. Arguably the 2 should
      // be decoupled.
      if (count($lineItems) > 1) {
        unset($overrides['financial_type_id'], $overrides['total_amount']);
      }
      $result = array_merge($templateContribution, $overrides);
      // Line items aren't always written to a contribution, for mystery reasons.
      // Checking for their existence prevents $order->getPriceSetID returning NULL.
      if ($lineItems) {
        $result['line_item'][$order->getPriceSetID()] = $lineItems;
      }
      // If the template contribution was made on-behalf then add the
      // relevant values to ensure the activity reflects that.
      $relatedContact = CRM_Contribute_BAO_Contribution::getOnbehalfIds($result['id']);
      if (!empty($relatedContact['individual_id'])) {
        $result['on_behalf'] = TRUE;
        $result['source_contact_id'] = $relatedContact['individual_id'];
      }
      return $result;
    }
    return [];
  }

  public static function setSubscriptionContext() {
    // handle context redirection for subscription url
    $session = CRM_Core_Session::singleton();
    if ($session->get('userID')) {
      $url = FALSE;
      $cid = CRM_Utils_Request::retrieve('cid', 'Integer');
      $mid = CRM_Utils_Request::retrieve('mid', 'Integer');
      $qfkey = CRM_Utils_Request::retrieve('key', 'String');
      $context = CRM_Utils_Request::retrieve('context', 'Alphanumeric');
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
   *
   * @param array $fields
   *   The input form values.
   * @param array $files
   *   The uploaded files if any.
   * @param CRM_Core_Form $self
   * @param array $errors
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

  /**
   * Copy custom data of the initial contribution into its recurring contributions.
   *
   * @deprecated
   *
   * @param int $recurId
   * @param int $targetContributionId
   */
  public static function copyCustomValues($recurId, $targetContributionId) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    if ($recurId && $targetContributionId) {
      // get the initial contribution id of recur id
      $sourceContributionId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $recurId, 'id', 'contribution_recur_id');

      // if the same contribution is being processed then return
      if ($sourceContributionId == $targetContributionId) {
        return;
      }
      // check if proper recurring contribution record is being processed
      $targetConRecurId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $targetContributionId, 'contribution_recur_id');
      if ($targetConRecurId != $recurId) {
        return;
      }

      // copy custom data
      $groupTree = CRM_Core_BAO_CustomGroup::getAll(['extends' => ['Contribution'], 'is_active' => TRUE]);
      if ($groupTree) {
        foreach ($groupTree as $groupID => $group) {
          $table[$groupTree[$groupID]['table_name']] = ['entity_id'];
          foreach ($group['fields'] as $fieldID => $field) {
            $table[$groupTree[$groupID]['table_name']][] = $groupTree[$groupID]['fields'][$fieldID]['column_name'];
          }
        }

        foreach ($table as $tableName => $tableColumns) {
          $insert = 'INSERT IGNORE INTO ' . $tableName . ' (' . implode(', ', $tableColumns) . ') ';
          $tableColumns[0] = $targetContributionId;
          $select = 'SELECT ' . implode(', ', $tableColumns);
          $from = ' FROM ' . $tableName;
          $where = " WHERE {$tableName}.entity_id = {$sourceContributionId}";
          $query = $insert . $select . $from . $where;
          CRM_Core_DAO::executeQuery($query);
        }
      }
    }
  }

  /**
   * Add soft credit to for recurring payment.
   *
   * copy soft credit record of first recurring contribution.
   * and add new soft credit against $targetContributionId
   *
   * @param int $recurId
   * @param int $targetContributionId
   */
  public static function addrecurSoftCredit($recurId, $targetContributionId) {
    $soft_contribution = new CRM_Contribute_DAO_ContributionSoft();
    $soft_contribution->contribution_id = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $recurId, 'id', 'contribution_recur_id');

    // Check if first recurring contribution has any associated soft credit.
    if ($soft_contribution->find(TRUE)) {
      $soft_contribution->contribution_id = $targetContributionId;
      unset($soft_contribution->id);
      $soft_contribution->save();
    }
  }

  /**
   * Update pledge associated with a recurring contribution.
   *
   * If the contribution has a pledge_payment record pledge, then update the pledge_payment record & pledge based on that linkage.
   *
   * If a previous contribution in the recurring contribution sequence is linked with a pledge then we assume this contribution
   * should be  linked with the same pledge also. Currently only back-office users can apply a recurring payment to a pledge &
   * it should be assumed they
   * do so with the intention that all payments will be linked
   *
   * The pledge payment record should already exist & will need to be updated with the new contribution ID.
   * If not the contribution will also need to be linked to the pledge
   *
   * @param int $contributionID
   * @param int $contributionRecurID
   * @param int $contributionStatusID
   * @param float $contributionAmount
   *
   * @throws \CRM_Core_Exception
   */
  public static function updateRecurLinkedPledge($contributionID, $contributionRecurID, $contributionStatusID, $contributionAmount) {
    $returnProperties = ['id', 'pledge_id'];
    $paymentDetails = [];

    $relatedContributions = new CRM_Contribute_DAO_Contribution();
    $relatedContributions->contribution_recur_id = $contributionRecurID;
    $relatedContributions->find();

    while ($relatedContributions->fetch()) {
      CRM_Core_DAO::commonRetrieveAll('CRM_Pledge_DAO_PledgePayment', 'contribution_id', $relatedContributions->id,
        $paymentDetails, $returnProperties
      );
    }

    if (empty($paymentDetails)) {
      // payment is not linked with a pledge and neither are any other contributions on this
      return;
    }

    foreach ($paymentDetails as $value) {
      $pledgeId = $value['pledge_id'];
    }

    // we have a pledge now we need to get the oldest unpaid payment
    $paymentDetails = CRM_Pledge_BAO_PledgePayment::getOldestPledgePayment($pledgeId);
    if (empty($paymentDetails['id'])) {
      // we can assume this pledge is now completed
      // return now so we don't create a core error & roll back
      return;
    }
    $paymentDetails['contribution_id'] = $contributionID;
    $paymentDetails['status_id'] = $contributionStatusID;
    $paymentDetails['actual_amount'] = $contributionAmount;

    // put contribution against it
    civicrm_api3('PledgePayment', 'create', $paymentDetails);
  }

  /**
   * Recurring contribution fields.
   *
   * @param CRM_Contribute_Form_Search $form
   *
   * @throws \CRM_Core_Exception
   */
  public static function recurringContribution($form): void {
    // This assignment may be overwritten.
    $form->assign('contribution_recur_pane_open', FALSE);
    foreach (self::getRecurringFields() as $key) {
      if ($key === 'contribution_recur_payment_made' && !empty($form->_formValues) &&
        !CRM_Utils_System::isNull($form->_formValues[$key] ?? NULL)
      ) {
        $form->assign('contribution_recur_pane_open', TRUE);
        break;
      }
      // If data has been entered for a recurring field, tell the tpl layer to open the pane
      if (!empty($form->_formValues[$key . '_relative']) || !empty($form->_formValues[$key . '_low']) || !empty($form->_formValues[$key . '_high'])) {
        $form->assign('contribution_recur_pane_open', TRUE);
        break;
      }
    }

    // If values have been supplied for recurring contribution fields, open the recurring contributions pane.
    foreach (['contribution_status_id', 'payment_processor_id', 'processor_id', 'trxn_id'] as $fieldName) {
      if (!empty($form->_formValues['contribution_recur_' . $fieldName])) {
        $form->assign('contribution_recur_pane_open', TRUE);
        break;
      }
    }

    // Add field to check if payment is made for recurring contribution
    $recurringPaymentOptions = [
      1 => ts('All recurring contributions'),
      2 => ts('Recurring contributions with at least one payment'),
    ];
    $form->addRadio('contribution_recur_payment_made', NULL, $recurringPaymentOptions, ['allowClear' => TRUE]);

    // Add field for contribution status
    $form->addSelect('contribution_recur_contribution_status_id',
      ['entity' => 'contribution', 'multiple' => 'multiple', 'context' => 'search', 'options' => CRM_Contribute_BAO_Contribution::buildOptions('contribution_status_id', 'search')]
    );

    $form->addElement('text', 'contribution_recur_processor_id', ts('Processor ID'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur', 'processor_id'));
    $form->addElement('text', 'contribution_recur_trxn_id', ts('Transaction ID'), CRM_Core_DAO::getAttribute('CRM_Contribute_DAO_ContributionRecur', 'trxn_id'));

    $paymentProcessorOpts = CRM_Contribute_BAO_ContributionRecur::buildOptions('payment_processor_id', 'get');
    $form->add('select', 'contribution_recur_payment_processor_id', ts('Payment Processor ID'), $paymentProcessorOpts, FALSE, ['class' => 'crm-select2', 'multiple' => 'multiple']);

    CRM_Core_BAO_Query::addCustomFormFields($form, ['ContributionRecur']);

  }

  /**
   * Get the metadata for fields to be included on the search form.
   *
   * @throws \CRM_Core_Exception
   */
  public static function getContributionRecurSearchFieldMetadata() {
    $fields = [
      'contribution_recur_start_date',
      'contribution_recur_next_sched_contribution_date',
      'contribution_recur_cancel_date',
      'contribution_recur_end_date',
      'contribution_recur_create_date',
      'contribution_recur_modified_date',
      'contribution_recur_failure_retry_date',
    ];
    $metadata = civicrm_api3('ContributionRecur', 'getfields', [])['values'];
    return array_intersect_key($metadata, array_flip($fields));
  }

  /**
   * Get fields for recurring contributions.
   *
   * @return array
   */
  public static function getRecurringFields() {
    return [
      'contribution_recur_payment_made',
      'contribution_recur_start_date',
      'contribution_recur_next_sched_contribution_date',
      'contribution_recur_cancel_date',
      'contribution_recur_end_date',
      'contribution_recur_create_date',
      'contribution_recur_modified_date',
      'contribution_recur_failure_retry_date',
    ];
  }

  /**
   * Update recurring contribution based on incoming payment.
   *
   * Do not rename or move this function without updating https://issues.civicrm.org/jira/browse/CRM-17655.
   *
   * @param int $recurringContributionID
   * @param string $paymentStatus
   *   Payment status - this correlates to the machine name of the contribution status ID ie
   *   - Completed
   *   - Failed
   * @param string $effectiveDate
   *
   * @throws \CRM_Core_Exception
   */
  public static function updateOnNewPayment($recurringContributionID, string $paymentStatus, string $effectiveDate = 'now') {
    if (!in_array($paymentStatus, ['Completed', 'Failed'])) {
      return;
    }

    $existingRecur = \Civi\Api4\ContributionRecur::get(FALSE)
      ->addSelect('contribution_status_id:name', 'next_sched_contribution_date', 'frequency_unit', 'frequency_interval', 'installments', 'failure_count')
      ->addWhere('id', '=', $recurringContributionID)
      ->execute()
      ->first();

    $updatedRecurParams['id'] = $recurringContributionID;
    if (($paymentStatus === 'Completed')
      && ($existingRecur['contribution_status_id:name'] === 'Pending')) {
      // Update Recur to "In Progress"
      $updatedRecurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', 'In Progress');
    }
    if ($paymentStatus == 'Failed') {
      $updatedRecurParams['failure_count'] = $existingRecur['failure_count'];
    }
    $updatedRecurParams['modified_date'] = date('Y-m-d H:i:s');

    if (!empty($existingRecur['installments']) && self::isComplete($recurringContributionID, $existingRecur['installments'])) {
      // Update Recur to "Completed"
      $updatedRecurParams['contribution_status_id'] = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', 'Completed');
      $updatedRecurParams['next_sched_contribution_date'] = 'null';
      $updatedRecurParams['end_date'] = 'now';
    }
    else {
      // Only update next sched date if it's empty or up to 48 hours away because payment processors may be managing
      // the scheduled date themselves as core did not previously provide any help. This check can possibly be removed
      // as it's unclear if it actually is helpful...
      // We should allow payment processors to pass this value into repeattransaction in future.
      // Note 48 hours is a bit aribtrary but means that we can hopefully ignore the time being potentially
      // rounded down to midnight.
      $upperDateToConsiderProcessed = strtotime('+ 48 hours', ($effectiveDate ? strtotime($effectiveDate) : time()));
      if (empty($existingRecur['next_sched_contribution_date']) || strtotime($existingRecur['next_sched_contribution_date']) <=
        $upperDateToConsiderProcessed) {
        $updatedRecurParams['next_sched_contribution_date'] = date('Y-m-d', strtotime('+' . $existingRecur['frequency_interval'] . ' ' . $existingRecur['frequency_unit'], strtotime($effectiveDate)));
      }
    }
    civicrm_api3('ContributionRecur', 'create', $updatedRecurParams);
  }

  /**
   * If a template contribution is updated we need to update the amount on the recurring contribution.
   *
   * @param \CRM_Contribute_DAO_Contribution $contribution
   *
   * @throws \CRM_Core_Exception
   */
  public static function updateOnTemplateUpdated(CRM_Contribute_DAO_Contribution $contribution): void {
    if ($contribution->is_template === '0' || empty($contribution->contribution_recur_id)) {
      return;
    }

    if ($contribution->total_amount === NULL || $contribution->currency === NULL || $contribution->is_template === NULL) {
      // The contribution has not been fully loaded, so fetch a full copy now.
      $contribution->find(TRUE);
    }
    if (!$contribution->is_template) {
      return;
    }

    $contributionRecur = ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $contribution->contribution_recur_id)
      ->execute()
      ->first();

    if ($contribution->currency !== $contributionRecur['currency'] || !CRM_Utils_Money::equals($contributionRecur['amount'], $contribution->total_amount, $contribution->currency)) {
      ContributionRecur::update(FALSE)
        ->addValue('amount', $contribution->total_amount)
        ->addValue('currency', $contribution->currency)
        ->addValue('modified_date', 'now')
        ->addWhere('id', '=', $contributionRecur['id'])
        ->execute();
    }
  }

  /**
   * Is this recurring contribution now complete.
   *
   * Have all the payments expected been received now.
   *
   * @param int $recurringContributionID
   * @param int $installments
   *
   * @return bool
   */
  protected static function isComplete($recurringContributionID, $installments) {
    $paidInstallments = CRM_Core_DAO::singleValueQuery(
      'SELECT count(*) FROM civicrm_contribution
        WHERE contribution_recur_id = %1
        AND contribution_status_id = ' . CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      [1 => [$recurringContributionID, 'Integer']]
    );
    if ($paidInstallments >= $installments) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Returns array with statuses that are considered to make a recurring contribution inactive.
   *
   * @return array
   */
  public static function getInactiveStatuses() {
    return ['Cancelled', 'Failed', 'Completed'];
  }

  /**
   * Legacy option getter
   *
   * @deprecated
   *
   * @inheritDoc
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    $params = [];
    switch ($fieldName) {
      case 'payment_processor_id':
        if (isset(\Civi::$statics[__CLASS__]['buildoptions_payment_processor_id'][$context])) {
          return \Civi::$statics[__CLASS__]['buildoptions_payment_processor_id'][$context];
        }
        $baoName = 'CRM_Contribute_BAO_ContributionRecur';
        $params['condition']['test'] = "is_test = 0";
        $liveProcessors = CRM_Core_PseudoConstant::get($baoName, $fieldName, $params, $context);
        $params['condition']['test'] = "is_test != 0";
        $testProcessors = CRM_Core_PseudoConstant::get($baoName, $fieldName, $params, $context);
        foreach ($testProcessors as $key => $value) {
          if ($context === 'validate') {
            // @fixme: Ideally the names would be different in the civicrm_payment_processor table but they are not.
            //     So we append '_test' to the test one so that we can select the correct processor by name using the ContributionRecur.create API.
            $testProcessors[$key] = $value . '_test';
          }
          else {
            $testProcessors[$key] = CRM_Core_TestEntity::appendTestText($value);
          }
        }
        $allProcessors = $liveProcessors + $testProcessors;
        ksort($allProcessors);
        \Civi::$statics[__CLASS__]['buildoptions_payment_processor_id'][$context] = $allProcessors;
        return $allProcessors;
    }
    return parent::buildOptions($fieldName, $context, $props);
  }

  /**
   * @implements CRM_Utils_Hook::fieldOptions
   */
  public static function hook_civicrm_fieldOptions($entity, $field, &$options, $params) {
    // This faithfully recreates the hack in the above buildOptions() function, appending _test to the name of test processors,
    // which allows `CRM_Utils_TokenConsistencyTest::testContributionRecurTokenConsistency` to pass.
    // But one has to wonder: if we are doing this, why only do it for ContributionRecur, why not for all
    // option lists containing payment processors?
    if ($entity === 'ContributionRecur' && $field === 'payment_processor_id' && $params['context'] === 'full') {
      foreach ($options as $id => &$option) {
        $isTest = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_PaymentProcessor', $id, 'is_test');
        if ($isTest) {
          $option['name'] .= '_test';
          $option['label'] = CRM_Core_TestEntity::appendTestText($option['label']);
        }
      }
    }
  }

  /**
   * Get the from address to use for the recurring contribution.
   *
   * This uses the contribution page id, if there is one, or the default domain one.
   *
   * @param int $id
   *   Recurring contribution ID.
   *
   * @internal
   *
   * @return string
   * @throws \CRM_Core_Exception
   */
  public static function getRecurFromAddress(int $id): string {
    $details = Contribution::get(FALSE)
      ->addWhere('contribution_recur_id', '=', $id)
      ->addWhere('contribution_page_id', 'IS NOT NULL')
      ->addSelect('contribution_page_id.receipt_from_name', 'contribution_page_id.receipt_from_email')
      ->addOrderBy('receive_date', 'DESC')
      ->execute()->first();
    if (empty($details['contribution_page_id.receipt_from_email'])) {
      $domainValues = CRM_Core_BAO_Domain::getNameAndEmail();
      return "$domainValues[0] <$domainValues[1]>";
    }
    return '"' . $details['contribution_page_id.receipt_from_name'] . '" <' . $details['contribution_page_id.receipt_from_email'] . '>';
  }

}
