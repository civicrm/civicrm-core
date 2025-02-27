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

use Civi\Api4\Activity;
use Civi\Api4\ActivityContact;
use Civi\Api4\Address;
use Civi\Api4\Contribution;
use Civi\Api4\ContributionRecur;
use Civi\Api4\EntityFinancialTrxn;
use Civi\Api4\LineItem;
use Civi\Api4\ContributionSoft;
use Civi\Api4\MembershipLog;
use Civi\Api4\PaymentProcessor;
use Civi\Api4\UFJoin;
use Civi\Core\Event\PostEvent;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Contribute_BAO_Contribution extends CRM_Contribute_DAO_Contribution implements Civi\Core\HookInterface {

  /**
   * Static field for all the contribution information that we can potentially import
   *
   * @var array
   */
  public static $_importableFields = NULL;

  /**
   * Static field for all the contribution information that we can potentially export
   *
   * @var array
   */
  public static $_exportableFields = NULL;

  /**
   * Field for all the objects related to this contribution.
   *
   * This is used from
   * 1) deprecated function transitionComponents
   * 2) function to send contribution receipts _assignMessageVariablesToTemplate
   * 3) some invoice code that is copied from 2
   * 4) odds & sods that need to be investigated and fixed.
   *
   * However, it is no longer used by completeOrder.
   *
   * @var \CRM_Member_BAO_Membership|\CRM_Event_BAO_Participant[]
   *
   * @deprecated
   */
  public $_relatedObjects = [];

  /**
   * Field for the component - either 'event' (participant) or 'contribute'
   * (any item related to a contribution page e.g. membership, pledge, contribution)
   * This is used for composing messages because they have dependency on the
   * contribution_page or event page - although over time we may eliminate that
   *
   * @var string
   * "contribution"\"event"
   */
  public $_component = NULL;

  /**
   * Possibly obsolete variable.
   *
   * If you use it please explain why it is set in the create function here.
   *
   * @var string
   */
  public $trxn_result_code;

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
   * @return \CRM_Contribute_BAO_Contribution
   * @throws \CRM_Core_Exception
   */
  public static function add(&$params) {
    if (empty($params)) {
      return NULL;
    }

    $contributionID = $params['id'] ?? NULL;
    $action = $contributionID ? 'edit' : 'create';
    $duplicates = [];
    if (self::checkDuplicate($params, $duplicates, $contributionID)) {
      $message = ts("Duplicate error - existing contribution record(s) have a matching Transaction ID or Invoice ID. Contribution record ID(s) are: %1", [1 => implode(', ', $duplicates)]);
      throw new CRM_Core_Exception($message);
    }

    //set defaults in create mode
    if (!$contributionID) {
      CRM_Core_DAO::setCreateDefaults($params, self::getDefaults());
    }

    $contributionStatusID = $params['contribution_status_id'] ?? NULL;
    if (CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', (int) $contributionStatusID) === 'Partially paid' && empty($params['is_post_payment_create'])) {
      CRM_Core_Error::deprecatedFunctionWarning('Setting status to partially paid other than by using Payment.create is deprecated and unreliable');
    }
    if (!$contributionStatusID) {
      // Since the fee amount is expecting this (later on) ensure it is always set.
      // It would only not be set for an update where it is unchanged.
      $params['contribution_status_id'] = Contribution::get(FALSE)
        ->addSelect('contribution_status_id')
        ->addWhere('id', '=', $contributionID)
        ->execute()
        ->first()['contribution_status_id'] ?? NULL;
    }
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', (int) $params['contribution_status_id']);

    if (!$contributionID
      && !empty($params['membership_id'])
      && Civi::settings()->get('deferred_revenue_enabled')
    ) {
      $memberStartDate = CRM_Core_DAO::getFieldValue('CRM_Member_DAO_Membership', $params['membership_id'], 'start_date');
      if ($memberStartDate) {
        $params['revenue_recognition_date'] = date('Ymd', strtotime($memberStartDate));
      }
    }
    self::calculateMissingAmountParams($params, $contributionID);

    if (!empty($params['payment_instrument_id'])) {
      $paymentInstruments = CRM_Contribute_PseudoConstant::paymentInstrument('name');
      if ($params['payment_instrument_id'] != array_search('Check', $paymentInstruments)) {
        $params['check_number'] = 'null';
      }
    }

    $setPrevContribution = TRUE;
    if ($contributionID && $setPrevContribution) {
      $params['prevContribution'] = self::getOriginalContribution($contributionID);
    }
    $previousContributionStatus = ($contributionID && !empty($params['prevContribution'])) ? CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', (int) $params['prevContribution']->contribution_status_id) : NULL;

    if ($contributionID && !empty($params['revenue_recognition_date'])
      && !($previousContributionStatus === 'Pending')
      && !self::allowUpdateRevenueRecognitionDate($contributionID)
    ) {
      unset($params['revenue_recognition_date']);
    }

    // Get Line Items if we don't have them already.
    if (empty($params['line_item'])) {
      CRM_Price_BAO_LineItem::getLineItemArray($params, $contributionID ? [$contributionID] : NULL);
    }

    // We should really ALWAYS calculate tax amount off the line items.
    // In order to be a bit cautious we are just messaging rather than
    // overwriting in cases where we were not previously setting it here.
    $taxAmount = $lineTotal = 0;
    foreach ($params['line_item'] ?? [] as $lineItems) {
      foreach ($lineItems as $lineItem) {
        $taxAmount += (float) ($lineItem['tax_amount'] ?? 0);
        $lineTotal += (float) ($lineItem['line_total'] ?? 0);
      }
    }
    if (($params['tax_amount'] ?? '') === 'null') {
      CRM_Core_Error::deprecatedWarning('tax_amount should be not passed in (preferable) or a float');
    }
    if (!isset($params['tax_amount']) && $setPrevContribution && (isset($params['total_amount']) ||
     isset($params['financial_type_id']))) {
      $params['tax_amount'] = $taxAmount;
    }
    if (isset($params['tax_amount']) && empty($params['skipLineItem'])
      && !CRM_Utils_Money::equals($params['tax_amount'], $taxAmount, ($params['currency'] ?? Civi::settings()->get('defaultCurrency')))
    ) {
      CRM_Core_Error::deprecatedWarning('passing in incorrect tax amounts is deprecated');
    }

    CRM_Utils_Hook::pre($action, 'Contribution', $contributionID, $params);

    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->copyValues($params);

    $contribution->id = $contributionID;

    if (empty($contribution->id)) {
      // (only) on 'create', make sure that a valid currency is set (CRM-16845)
      if (!CRM_Utils_Rule::currencyCode($contribution->currency)) {
        $contribution->currency = CRM_Core_Config::singleton()->defaultCurrency;
      }
    }

    $result = $contribution->save();
    // Save invoice number if appropriate. Note we used to try to
    // avoid a second save but check https://lab.civicrm.org/dev/core/-/issues/5004
    // to see how that worked out....
    if ($action === 'create' && empty($params['invoice_number']) && \Civi::settings()->get('invoicing')) {
      $contribution->invoice_number = self::getInvoiceNumber($contribution->id);
      $contribution->save();
    }

    // Add financial_trxn details as part of fix for CRM-4724
    $contribution->trxn_result_code = $params['trxn_result_code'] ?? NULL;
    $contribution->payment_processor = $params['payment_processor'] ?? NULL;

    // Loading contribution used to be required for recordFinancialAccounts.
    $params['contribution'] = $contribution;
    if (empty($params['is_post_payment_create'])) {
      // If this is being called from the Payment.create api/ BAO then that Entity
      // takes responsibility for the financial transactions. In fact calling Payment.create
      // to add payments & having it call completetransaction and / or contribution.create
      // to update related entities is the preferred flow.
      // Note that leveraging this parameter for any other code flow is not supported and
      // is likely to break in future and / or cause serious problems in your data.
      // https://github.com/civicrm/civicrm-core/pull/14673
      self::recordFinancialAccounts($params, $contribution);
    }

    if (self::isUpdateToRecurringContribution($params)) {
      CRM_Contribute_BAO_ContributionRecur::updateOnNewPayment(
        (!empty($params['contribution_recur_id']) ? $params['contribution_recur_id'] : $params['prevContribution']->contribution_recur_id),
        $contributionStatus,
        $params['receive_date'] ?? 'now'
      );
    }

    $params['contribution_id'] = $contribution->id;

    if (!empty($params['custom']) &&
      is_array($params['custom'])
    ) {
      CRM_Core_BAO_CustomValueTable::store($params['custom'], 'civicrm_contribution', $contribution->id, $action);
    }

    CRM_Contact_BAO_GroupContactCache::opportunisticCacheFlush();

    CRM_Utils_Hook::post($action, 'Contribution', $contribution->id, $contribution);
    return $result;
  }

  /**
   * Is this contribution updating an existing recurring contribution.
   *
   * We need to upd the status of the linked recurring contribution if we have a new payment against it, or the initial
   * pending payment is being confirmed (or failing).
   *
   * @param array $params
   *
   * @return bool
   */
  public static function isUpdateToRecurringContribution($params) {
    if (!empty($params['contribution_recur_id']) && empty($params['id'])) {
      return TRUE;
    }
    if (empty($params['prevContribution']) || empty($params['contribution_status_id'])) {
      return FALSE;
    }
    if (empty($params['contribution_recur_id']) && empty($params['prevContribution']->contribution_recur_id)) {
      return FALSE;
    }
    if ($params['prevContribution']->contribution_status_id == CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Get defaults for new entity.
   *
   * @return array
   */
  public static function getDefaults() {
    return [
      'payment_instrument_id' => key(CRM_Core_OptionGroup::values('payment_instrument',
        FALSE, FALSE, FALSE, 'AND is_default = 1')
      ),
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'receive_date' => date('Y-m-d H:i:s'),
    ];
  }

  /**
   * Fetch the object and store the values in the values array.
   *
   * @param array $params
   *   Input parameters to find object.
   * @param array $values
   *   Output values of the object.
   * @param array $ids
   *   The array that holds all the db ids.
   *
   * @return CRM_Contribute_BAO_Contribution|null
   *   The found object or null
   */
  public static function getValues($params, &$values = [], &$ids = []) {
    if (empty($params)) {
      return NULL;
    }
    $contribution = new CRM_Contribute_BAO_Contribution();

    $contribution->copyValues($params);

    if ($contribution->find(TRUE)) {
      $ids['contribution'] = $contribution->id;

      CRM_Core_DAO::storeValues($contribution, $values);

      return $contribution;
    }
    // return by reference
    $null = NULL;
    return $null;
  }

  /**
   * Deprecated contact.get call.
   *
   * Since contribution status is resolved in almost every function that calls getValues it makes
   * sense to have an extra function to resolve it rather than repeat the code.
   *
   * Think carefully before adding more mappings to be resolved as there could be performance implications
   * if this function starts to be called from more iterative functions.
   *
   * @param array $params
   *   Input parameters to find object.
   *
   * @return array
   *   Array of the found contribution.
   * @throws CRM_Core_Exception
   *
   * @deprecated
   */
  public static function getValuesWithMappings($params) {
    $values = $ids = [];
    $contribution = self::getValues($params, $values, $ids);
    if (is_null($contribution)) {
      throw new CRM_Core_Exception('No contribution found');
    }
    $values['contribution_status'] = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $values['contribution_status_id']);
    return $values;
  }

  /**
   * Calculate net_amount & fee_amount if they are not set.
   *
   * Net amount should be total - fee.
   * This should only be called for new contributions.
   *
   * @param array $params
   *   Params for a new contribution before they are saved.
   * @param int|null $contributionID
   *   Contribution ID if we are dealing with an update.
   *
   * @throws \CRM_Core_Exception
   */
  public static function calculateMissingAmountParams(&$params, $contributionID) {
    if (!$contributionID && (!isset($params['fee_amount']) || $params['fee_amount'] === '')) {
      if (isset($params['total_amount']) && isset($params['net_amount'])) {
        $params['fee_amount'] = $params['total_amount'] - $params['net_amount'];
      }
      else {
        $params['fee_amount'] = 0;
      }
    }
    if (!isset($params['net_amount'])) {
      if (!$contributionID) {
        $params['net_amount'] = $params['total_amount'] - $params['fee_amount'];
      }
      else {
        if (isset($params['fee_amount']) || isset($params['total_amount'])) {
          // We have an existing contribution and fee_amount or total_amount has been passed in but not net_amount.
          // net_amount may need adjusting.
          $contribution = Contribution::get(FALSE)
            ->addSelect('total_amount', 'net_amount', 'fee_amount')
            ->addWhere('id', '=', $contributionID)
            ->execute()
            ->first();
          $totalAmount = (isset($params['total_amount']) ? (float) $params['total_amount'] : (float) ($contribution['total_amount'] ?? 0));
          $feeAmount = (isset($params['fee_amount']) ? (float) $params['fee_amount'] : (float) ($contribution['fee_amount'] ?? 0));
          $params['net_amount'] = $totalAmount - $feeAmount;
        }
      }
    }
  }

  /**
   * @param $params
   * @param $billingLocationTypeID
   *
   * @return array
   */
  protected static function getBillingAddressParams($params, $billingLocationTypeID) {
    $hasBillingField = FALSE;
    $billingFields = [
      'street_address',
      'city',
      'state_province_id',
      'postal_code',
      'country_id',
    ];

    //build address array
    $addressParams = [];
    $addressParams['location_type_id'] = $billingLocationTypeID;
    $addressParams['is_billing'] = 1;

    $billingFirstName = $params['billing_first_name'] ?? NULL;
    $billingMiddleName = $params['billing_middle_name'] ?? NULL;
    $billingLastName = $params['billing_last_name'] ?? NULL;
    // Note this is NOT used when creating a billing address. It is probably passed
    // the the payment processor - which is horrible & could maybe change.
    $addressParams['address_name'] = "{$billingFirstName}" . CRM_Core_DAO::VALUE_SEPARATOR . "{$billingMiddleName}" . CRM_Core_DAO::VALUE_SEPARATOR . "{$billingLastName}";

    foreach ($billingFields as $value) {
      $addressParams[$value] = $params["billing_{$value}-{$billingLocationTypeID}"] ?? NULL;
      if (!empty($addressParams[$value])) {
        $hasBillingField = TRUE;
      }
    }
    return [$hasBillingField, $addressParams];
  }

  /**
   * Get address params ready to be passed to the payment processor.
   *
   * We need address params in a couple of formats. For the payment processor we wan state_province_id-5.
   * To create an address we need state_province_id.
   *
   * @param array $params
   *
   * @return array
   */
  public static function getPaymentProcessorReadyAddressParams($params) {
    $billingLocationTypeID = CRM_Core_BAO_LocationType::getBilling();
    [$hasBillingField, $addressParams] = self::getBillingAddressParams($params, $billingLocationTypeID);
    foreach ($addressParams as $name => $field) {
      if (substr($name, 0, 8) == 'billing_') {
        $addressParams[substr($name, 9)] = $addressParams[$field];
      }
    }
    return [$hasBillingField, $addressParams];
  }

  /**
   * Get the number of terms for this contribution for a given membership type
   * based on querying the line item table and relevant price field values
   * Note that any one contribution should only be able to have one line item relating to a particular membership
   * type
   *
   * @param int $membershipTypeID
   *
   * @param int $contributionID
   * @deprecated
   * @return int
   */
  public static function getNumTermsByContributionAndMembershipType($membershipTypeID, $contributionID) {
    CRM_Core_Error::deprecatedFunctionWarning('Use API4 LineItem::get');
    $numTerms = CRM_Core_DAO::singleValueQuery("
      SELECT v.membership_num_terms FROM civicrm_line_item li
      LEFT JOIN civicrm_price_field_value v ON li.price_field_value_id = v.id
      WHERE contribution_id = %1 AND membership_type_id = %2",
      [1 => [$contributionID, 'Integer'], 2 => [$membershipTypeID, 'Integer']]
    );
    // default of 1 is precautionary
    return empty($numTerms) ? 1 : $numTerms;
  }

  /**
   * Takes an associative array and creates a contribution object.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   *
   * @return CRM_Contribute_BAO_Contribution
   *
   * @throws \CRM_Core_Exception
   */
  public static function create(&$params) {

    $transaction = new CRM_Core_Transaction();

    try {
      $contribution = self::add($params);
    }
    catch (CRM_Core_Exception $e) {
      $transaction->rollback();
      throw $e;
    }

    $params['contribution_id'] = $contribution->id;
    $session = CRM_Core_Session::singleton();

    if (!empty($params['note'])) {
      $noteParams = [
        'entity_table' => 'civicrm_contribution',
        'note' => $params['note'],
        'entity_id' => $contribution->id,
        'contact_id' => $session->get('userID'),
      ];
      if (!$noteParams['contact_id']) {
        $noteParams['contact_id'] = $params['contact_id'];
      }
      CRM_Core_BAO_Note::add($noteParams);
    }

    CRM_Contribute_BAO_ContributionSoft::processSoftContribution($params, $contribution);

    $transaction->commit();

    if (empty($contribution->contact_id)) {
      $contribution->find(TRUE);
    }

    $isCompleted = ('Completed' === CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution->contribution_status_id));
    if (!empty($params['on_behalf'])
      ||  $isCompleted
    ) {
      $existingActivity = Activity::get(FALSE)->setWhere([
        ['source_record_id', '=', $contribution->id],
        ['activity_type_id:name', '=', 'Contribution'],
      ])->execute()->first();

      $activityParams = [
        'activity_type_id:name' => 'Contribution',
        'source_record_id' => $contribution->id,
        'activity_date_time' => $contribution->receive_date,
        'is_test' => (bool) $contribution->is_test,
        'status_id:name' => $isCompleted ? 'Completed' : 'Scheduled',
        'skipRecentView' => TRUE,
        'subject' => CRM_Activity_BAO_Activity::getActivitySubject($contribution),
        'campaign_id' => !is_numeric($contribution->campaign_id) ? NULL : $contribution->campaign_id,
        'id' => $existingActivity['id'] ?? NULL,
      ];
      if (!$activityParams['id']) {
        $activityParams['source_contact_id'] = (int) ($params['source_contact_id'] ?? (CRM_Core_Session::getLoggedInContactID() ?: $contribution->contact_id));
        $activityParams['target_contact_id'] = ($activityParams['source_contact_id'] === (int) $contribution->contact_id) ? [] : [$contribution->contact_id];
      }
      else {
        [$sourceContactId, $targetContactId] = self::getActivitySourceAndTarget($activityParams['id']);

        if (empty($targetContactId) && $sourceContactId != $contribution->contact_id) {
          // If no target contact exists and the source contact is not equal to
          // the contribution contact, update the source contact
          $activityParams['source_contact_id'] = $contribution->contact_id;
        }
        elseif (isset($targetContactId) && $targetContactId != $contribution->contact_id) {
          // If a target contact exists and it is not equal to the contribution
          // contact, update the target contact
          $activityParams['target_contact_id'] = [$contribution->contact_id];
        }
      }
      Activity::save(FALSE)->addRecord($activityParams)->execute();
    }

    // do not add to recent items for import, CRM-4399
    if (empty($params['skipRecentView'])) {
      $url = CRM_Utils_System::url('civicrm/contact/view/contribution',
        "action=view&reset=1&id={$contribution->id}&cid={$contribution->contact_id}&context=home"
      );
      // in some update cases we need to get extra fields - ie an update that doesn't pass in all these params
      $titleFields = [
        'contact_id',
        'total_amount',
        'currency',
        'financial_type_id',
      ];
      $retrieveRequired = 0;
      foreach ($titleFields as $titleField) {
        if (!isset($contribution->$titleField)) {
          $retrieveRequired = 1;
          break;
        }
      }
      if ($retrieveRequired == 1) {
        $contribution->find(TRUE);
      }
      $financialType = CRM_Contribute_PseudoConstant::financialType($contribution->financial_type_id);
      $title = CRM_Contact_BAO_Contact::displayName($contribution->contact_id) . ' - (' . CRM_Utils_Money::format($contribution->total_amount, $contribution->currency) . ' ' . ' - ' . $financialType . ')';

      $recentOther = [];
      if (CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::UPDATE)) {
        $recentOther['editUrl'] = CRM_Utils_System::url('civicrm/contact/view/contribution',
          "action=update&reset=1&id={$contribution->id}&cid={$contribution->contact_id}&context=home"
        );
      }

      if (CRM_Core_Permission::checkActionPermission('CiviContribute', CRM_Core_Action::DELETE)) {
        $recentOther['deleteUrl'] = CRM_Utils_System::url('civicrm/contact/view/contribution',
          "action=delete&reset=1&id={$contribution->id}&cid={$contribution->contact_id}&context=home"
        );
      }

      // add the recently created Contribution
      CRM_Utils_Recent::add($title,
        $url,
        $contribution->id,
        'Contribution',
        $contribution->contact_id,
        NULL,
        $recentOther
      );
    }

    return $contribution;
  }

  /**
   * Event fired after modifying a contribution.
   *
   * @param \Civi\Core\Event\PostEvent $event
   *
   * @throws \CRM_Core_Exception
   */
  public static function self_hook_civicrm_post(PostEvent $event): void {
    if ($event->action === 'edit') {
      CRM_Contribute_BAO_ContributionRecur::updateOnTemplateUpdated($event->object);
    }
  }

  /**
   * Get the values for pseudoconstants for name->value and reverse.
   *
   * @param array $defaults
   *   (reference) the default values, some of which need to be resolved.
   * @param bool $reverse
   *   True if we want to resolve the values in the reverse direction (value -> name).
   */
  public static function resolveDefaults(&$defaults, $reverse = FALSE) {
    self::lookupValue($defaults, 'financial_type', CRM_Contribute_PseudoConstant::financialType(), $reverse);
    self::lookupValue($defaults, 'payment_instrument', CRM_Contribute_PseudoConstant::paymentInstrument(), $reverse);
    self::lookupValue($defaults, 'contribution_status', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label'), $reverse);
    self::lookupValue($defaults, 'pcp', CRM_Contribute_PseudoConstant::pcPage(), $reverse);
  }

  /**
   * Convert associative array names to values and vice-versa.
   *
   * This function is used by both the web form layer and the api. Note that
   * the api needs the name => value conversion, also the view layer typically
   * requires value => name conversion
   *
   * @param array $defaults
   * @param string $property
   * @param array $lookup
   * @param bool $reverse
   *
   * @return bool
   */
  public static function lookupValue(&$defaults, $property, &$lookup, $reverse) {
    $id = $property . '_id';

    $src = $reverse ? $property : $id;
    $dst = $reverse ? $id : $property;

    if (!array_key_exists($src, $defaults)) {
      return FALSE;
    }

    $look = $reverse ? array_flip($lookup) : $lookup;

    if (is_array($look)) {
      if (!array_key_exists($defaults[$src], $look)) {
        return FALSE;
      }
    }
    $defaults[$dst] = $look[$defaults[$src]];
    return TRUE;
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the name / value pairs.
   *                        in a hierarchical manner
   * @param array $ids
   *   (reference) the array that holds all the db ids.
   *
   * @return CRM_Contribute_BAO_Contribution
   */
  public static function retrieve(&$params, &$defaults = [], &$ids = []) {
    $contribution = CRM_Contribute_BAO_Contribution::getValues($params, $defaults, $ids);
    return $contribution;
  }

  /**
   * Combine all the importable fields from the lower levels object.
   *
   * The ordering is important, since currently we do not have a weight
   * scheme. Adding weight is super important and should be done in the
   * next week or so, before this can be called complete.
   *
   * @param string $contactType
   * @param bool $status
   * @deprecated
   * @return array
   *   array of importable Fields
   */
  public static function &importableFields($contactType = 'Individual', $status = TRUE) {
    CRM_Core_Error::deprecatedFunctionWarning('api');
    if (!self::$_importableFields) {
      if (!self::$_importableFields) {
        self::$_importableFields = [];
      }

      if (!$status) {
        $fields = ['' => ['title' => ts('- do not import -')]];
      }
      else {
        $fields = ['' => ['title' => ts('- Contribution Fields -')]];
      }

      $note = CRM_Core_DAO_Note::import();
      $tmpFields = CRM_Contribute_DAO_Contribution::import();
      unset($tmpFields['option_value']);
      $contactFields = CRM_Contact_BAO_Contact::importableFields($contactType, NULL);

      // Using new Dedupe rule.
      $ruleParams = [
        'contact_type' => $contactType,
        'used' => 'Unsupervised',
      ];
      $fieldsArray = CRM_Dedupe_BAO_DedupeRule::dedupeRuleFields($ruleParams);
      $tmpContactField = [];
      if (is_array($fieldsArray)) {
        foreach ($fieldsArray as $value) {
          //skip if there is no dupe rule
          if ($value == 'none') {
            continue;
          }
          $customFieldId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomField',
            $value,
            'id',
            'column_name'
          );
          $value = $customFieldId ? 'custom_' . $customFieldId : $value;
          $tmpContactField[trim($value)] = $contactFields[trim($value)];
          if (!$status) {
            $title = $tmpContactField[trim($value)]['title'] . ' ' . ts('(match to contact)');
          }
          else {
            $title = $tmpContactField[trim($value)]['title'];
          }
          $tmpContactField[trim($value)]['title'] = $title;
        }
      }

      $tmpContactField['external_identifier'] = $contactFields['external_identifier'];
      $tmpContactField['external_identifier']['title'] = $contactFields['external_identifier']['title'] . ' ' . ts('(match to contact)');
      $tmpFields['contribution_contact_id']['title'] = $tmpFields['contribution_contact_id']['html']['label'] = $tmpFields['contribution_contact_id']['title'] . ' ' . ts('(match to contact)');
      $fields = array_merge($fields, $tmpContactField);
      $fields = array_merge($fields, $tmpFields);
      $fields = array_merge($fields, $note);
      $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Contribution'));
      self::$_importableFields = $fields;
    }
    return self::$_importableFields;
  }

  /**
   * Combine all the exportable fields from the lower level objects.
   *
   * @param bool $checkPermission
   *
   * @return array
   *   array of exportable Fields
   */
  public static function &exportableFields($checkPermission = TRUE) {
    if (!self::$_exportableFields) {
      if (!self::$_exportableFields) {
        self::$_exportableFields = [];
      }

      $fields = CRM_Contribute_DAO_Contribution::export();
      if (CRM_Contribute_BAO_Query::isSiteHasProducts()) {
        $fields = array_merge(
          $fields,
          CRM_Contribute_DAO_Product::export(),
          CRM_Contribute_DAO_ContributionProduct::export(),
          // CRM-16713 - contribution search by Premiums on 'Find Contribution' form.
          [
            'contribution_product_id' => [
              'title' => ts('Premium'),
              'name' => 'contribution_product_id',
              'where' => 'civicrm_product.id',
              'data_type' => CRM_Utils_Type::T_INT,
            ],
          ]
        );
      }

      $financialAccount = CRM_Financial_DAO_FinancialAccount::export();

      $contributionPage = [
        'contribution_page' => [
          'title' => ts('Contribution Page'),
          'name' => 'contribution_page',
          'where' => 'civicrm_contribution_page.title',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
      ];

      $contributionNote = [
        'contribution_note' => [
          'title' => ts('Contribution Note'),
          'name' => 'contribution_note',
          'data_type' => CRM_Utils_Type::T_TEXT,
        ],
      ];

      $extraFields = [
        'contribution_batch' => [
          'title' => ts('Batch Name'),
        ],
      ];

      // CRM-17787
      $campaignTitle = [
        'contribution_campaign_title' => [
          'title' => ts('Campaign Title'),
          'name' => 'campaign_title',
          'where' => 'civicrm_campaign.title',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
      ];
      $softCreditFields = [
        'contribution_soft_credit_name' => [
          'name' => 'contribution_soft_credit_name',
          'title' => ts('Soft Credit For'),
          'where' => 'civicrm_contact_d.display_name',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
        'contribution_soft_credit_amount' => [
          'name' => 'contribution_soft_credit_amount',
          'title' => ts('Soft Credit Amount'),
          'where' => 'civicrm_contribution_soft.amount',
          'data_type' => CRM_Utils_Type::T_MONEY,
        ],
        'contribution_soft_credit_type' => [
          'name' => 'contribution_soft_credit_type',
          'title' => ts('Soft Credit Type'),
          'where' => 'contribution_softcredit_type.label',
          'data_type' => CRM_Utils_Type::T_STRING,
        ],
        'contribution_soft_credit_contribution_id' => [
          'name' => 'contribution_soft_credit_contribution_id',
          'title' => ts('Soft Credit For Contribution ID'),
          'where' => 'civicrm_contribution_soft.contribution_id',
          'data_type' => CRM_Utils_Type::T_INT,
        ],
        'contribution_soft_credit_contact_id' => [
          'name' => 'contribution_soft_credit_contact_id',
          'title' => ts('Soft Credit For Contact ID'),
          'where' => 'civicrm_contact_d.id',
          'data_type' => CRM_Utils_Type::T_INT,
        ],
      ];

      $fields = array_merge($fields, $contributionPage,
        $contributionNote, $extraFields, $softCreditFields, $financialAccount, $campaignTitle,
        CRM_Core_BAO_CustomField::getFieldsForImport('Contribution', FALSE, FALSE, FALSE, $checkPermission)
      );

      self::$_exportableFields = $fields;
    }

    return self::$_exportableFields;
  }

  /**
   * Record an activity when a payment is received.
   *
   * @todo this is intended to be moved to payment BAO class as a protected function
   * on that class. Currently being cleaned up. The addActivityForPayment doesn't really
   * merit it's own function as it makes the code less rather than more readable.
   *
   * @param int $contributionId
   * @param int $participantId
   * @param string $totalAmount
   * @param string $currency
   * @param string $trxnDate
   *
   * @throws \CRM_Core_Exception
   */
  public static function recordPaymentActivity($contributionId, $participantId, $totalAmount, $currency, $trxnDate) {
    $activityType = ($totalAmount < 0) ? 'Refund' : 'Payment';

    if ($participantId) {
      $inputParams['id'] = $participantId;
      $values = [];
      $ids = [];
      $entityObj = CRM_Event_BAO_Participant::getValues($inputParams, $values, $ids);
      $entityObj = $entityObj[$participantId];
      $title = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Event', $entityObj->event_id, 'title');
    }
    else {
      $entityObj = new CRM_Contribute_BAO_Contribution();
      $entityObj->id = $contributionId;
      $entityObj->find(TRUE);
      $title = ts('Contribution');
    }
    // @todo per block above this is not a logical splitting off of functionality.
    self::addActivityForPayment($entityObj->contact_id, $activityType, $title, $contributionId, $totalAmount, $currency, $trxnDate);
  }

  /**
   * Get the value for the To Financial Account.
   *
   * @param $contribution
   * @param $params
   *
   * @return int
   */
  public static function getToFinancialAccount($contribution, $params) {
    if (!empty($params['payment_processor_id'])) {
      return CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($params['payment_processor_id'], NULL, 'civicrm_payment_processor');
    }
    if (!empty($params['payment_instrument_id'])) {
      return CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount($contribution['payment_instrument_id']);
    }
    else {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Asset' "));
      $queryParams = [1 => [$relationTypeId, 'Integer']];
      return CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = %1", $queryParams);
    }
  }

  /**
   * Get memberships related to the contribution.
   *
   * @param int $contributionID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected static function getRelatedMemberships(int $contributionID): array {
    $membershipIDs = array_keys((array) LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contributionID)
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->addSelect('entity_id')
      ->execute()->indexBy('entity_id'));

    $doubleCheckParams = [
      'return' => 'membership_id',
      'contribution_id' => $contributionID,
    ];
    if (!empty($membershipIDs)) {
      $doubleCheckParams['membership_id'] = ['NOT IN' => $membershipIDs];
    }
    $membershipPayments = civicrm_api3('MembershipPayment', 'get', $doubleCheckParams)['values'];
    if (!empty($membershipPayments)) {
      $membershipIDs = [];
      CRM_Core_Error::deprecatedWarning('Not having valid line items for membership payments is invalid.');
      foreach ($membershipPayments as $membershipPayment) {
        $membershipIDs[] = $membershipPayment['membership_id'];
      }
    }
    if (empty($membershipIDs)) {
      return [];
    }
    // We could combine this with the MembershipPayment.get - we'd
    // need to re-wrangle the params (here or in the calling function)
    // as they would then me membership.contact_id, membership.is_test etc
    return civicrm_api3('Membership', 'get', [
      'id' => ['IN' => $membershipIDs],
      'return' => ['id', 'contact_id', 'membership_type_id', 'is_test', 'status_id', 'end_date'],
    ])['values'];
  }

  /**
   * Get transaction information about the contribution.
   *
   * @param int $contributionId
   * @param int $financialTypeID
   *
   * @return mixed
   */
  protected static function getContributionTransactionInformation($contributionId, int $financialTypeID) {
    $rows = [];
    $feeFinancialAccount = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($financialTypeID, 'Expense Account is');

    // Need to exclude fee trxn rows so filter out rows where TO FINANCIAL ACCOUNT is expense account
    $sql = "
        SELECT GROUP_CONCAT(fa.`name`) as financial_account,
          ft.total_amount,
          ft.payment_instrument_id,
          ft.trxn_date, ft.trxn_id, ft.status_id, ft.check_number, ft.currency, ft.pan_truncation, ft.card_type_id, ft.id

        FROM civicrm_contribution con
          LEFT JOIN civicrm_entity_financial_trxn eft ON (eft.entity_id = con.id AND eft.entity_table = 'civicrm_contribution')
          INNER JOIN civicrm_financial_trxn ft ON ft.id = eft.financial_trxn_id
            AND ft.to_financial_account_id != %2
          LEFT JOIN civicrm_entity_financial_trxn ef ON (ef.financial_trxn_id = ft.id AND ef.entity_table = 'civicrm_financial_item')
          LEFT JOIN civicrm_financial_item fi ON fi.id = ef.entity_id
          LEFT JOIN civicrm_financial_account fa ON fa.id = fi.financial_account_id

        WHERE con.id = %1 AND ft.is_payment = 1
        GROUP BY ft.id";
    $queryParams = [
      1 => [$contributionId, 'Integer'],
      2 => [$feeFinancialAccount, 'Integer'],
    ];
    $resultDAO = CRM_Core_DAO::executeQuery($sql, $queryParams);
    $statuses = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'label');

    while ($resultDAO->fetch()) {
      $paidByLabel = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_FinancialTrxn', 'payment_instrument_id', $resultDAO->payment_instrument_id);
      $paidByName = CRM_Core_PseudoConstant::getName('CRM_Core_BAO_FinancialTrxn', 'payment_instrument_id', $resultDAO->payment_instrument_id);
      if ($resultDAO->card_type_id) {
        $creditCardType = CRM_Core_PseudoConstant::getLabel('CRM_Core_BAO_FinancialTrxn', 'card_type_id', $resultDAO->card_type_id);
        $pantruncation = '';
        if ($resultDAO->pan_truncation) {
          $pantruncation = ": {$resultDAO->pan_truncation}";
        }
        $paidByLabel .= " ({$creditCardType}{$pantruncation})";
      }

      // show payment edit link only for payments done via backoffice form
      $paymentEditLink = '';
      if (empty($resultDAO->payment_processor_id) && CRM_Core_Permission::check('edit contributions')) {
        $links = [
          CRM_Core_Action::UPDATE => [
            'name' => ts('Edit Payment'),
            'icon' => 'fa-pencil',
            'url' => 'civicrm/payment/edit',
            'class' => 'medium-popup',
            'qs' => "reset=1&id=%%id%%&contribution_id=%%contribution_id%%",
            'title' => ts('Edit Payment'),
            'weight' => CRM_Core_Action::getWeight(CRM_Core_Action::UPDATE),
          ],
        ];
        $paymentEditLink = CRM_Core_Action::formLink(
          $links,
          CRM_Core_Action::mask([CRM_Core_Permission::EDIT]),
          [
            'id' => $resultDAO->id,
            'contribution_id' => $contributionId,
          ],
          ts('more'),
          FALSE,
          'Payment.edit.action',
          'Payment',
          $resultDAO->id,
          'icon'
        );
      }

      $val = [
        'id' => $resultDAO->id,
        'total_amount' => $resultDAO->total_amount,
        'financial_type' => $resultDAO->financial_account,
        'payment_instrument' => $paidByLabel,
        'receive_date' => $resultDAO->trxn_date,
        'trxn_id' => $resultDAO->trxn_id,
        'status' => $statuses[$resultDAO->status_id],
        'currency' => $resultDAO->currency,
        'action' => $paymentEditLink,
      ];
      if ($paidByName === 'Check') {
        $val['check_number'] = $resultDAO->check_number;
      }
      else {
        $val['check_number'] = NULL;
      }
      $rows[] = $val;
    }
    return $rows;
  }

  /**
   * Should an email receipt be sent for this contribution on completion.
   *
   * @param array $input
   * @param int $contributionID
   * @param int $recurringContributionID
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected static function isEmailReceipt(array $input, int $contributionID, $recurringContributionID): bool {
    if (isset($input['is_email_receipt'])) {
      return (bool) $input['is_email_receipt'];
    }
    if ($recurringContributionID) {
      //CRM-13273 - is_email_receipt setting on recurring contribution should take precedence over contribution page setting
      // but CRM-16124 if $input['is_email_receipt'] is set then that should not be overridden.
      // dev/core#1245 this maybe not the desired effect because the default value for is_email_receipt is set to 0 rather than 1 in
      // Instance that had the table added via an upgrade in 4.1
      // see also https://github.com/civicrm/civicrm-svn/commit/7f39befd60bc735408d7866b02b3ac7fff1d4eea#diff-9ad8e290180451a2d6eacbd3d1ca7966R354
      // https://lab.civicrm.org/dev/core/issues/1245
      return (bool) ContributionRecur::get(FALSE)->addWhere('id', '=', $recurringContributionID)->addSelect('is_email_receipt')->execute()->first()['is_email_receipt'];
    }
    $contributionPage = Contribution::get(FALSE)
      ->addSelect('contribution_page_id.is_email_receipt')
      ->addWhere('contribution_page_id', 'IS NOT NULL')
      ->addWhere('id', '=', $contributionID)
      ->execute()->first();

    if (!empty($contributionPage)) {
      return (bool) $contributionPage['contribution_page_id.is_email_receipt'];
    }
    // This would be the case for backoffice (where is_email_receipt is not passed in) or events, where Event::sendMail will filter
    // again anyway.
    return TRUE;
  }

  /**
   * @param string $status
   * @param null $startDate
   * @param null $endDate
   *
   * @return array|null
   */
  public static function getTotalAmountAndCount($status = NULL, $startDate = NULL, $endDate = NULL) {
    $where = [];
    switch ($status) {
      case 'Valid':
        $where[] = 'contribution_status_id = 1';
        break;

      case 'Cancelled':
        $where[] = 'contribution_status_id = 3';
        break;
    }

    if ($startDate) {
      $where[] = "receive_date >= '" . CRM_Utils_Type::escape($startDate, 'Timestamp') . "'";
    }
    if ($endDate) {
      $where[] = "receive_date <= '" . CRM_Utils_Type::escape($endDate, 'Timestamp') . "'";
    }
    $financialTypeACLJoin = '';
    if (CRM_Financial_BAO_FinancialType::isACLFinancialTypeStatus()) {
      $financialTypeACLJoin = " LEFT JOIN civicrm_line_item i ON (i.contribution_id = c.id AND i.entity_table = 'civicrm_contribution') ";
      $financialTypes = CRM_Contribute_PseudoConstant::financialType();
      CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes($financialTypes);
      if ($financialTypes) {
        $where[] = "c.financial_type_id IN (" . implode(',', array_keys($financialTypes)) . ")";
        $where[] = "i.financial_type_id IN (" . implode(',', array_keys($financialTypes)) . ")";
      }
      else {
        $where[] = "c.financial_type_id IN (0)";
      }
    }

    $whereCond = implode(' AND ', $where);

    $query = "
    SELECT  sum( total_amount ) as total_amount,
            count( c.id ) as total_count,
            currency
      FROM  civicrm_contribution c
INNER JOIN  civicrm_contact contact ON ( contact.id = c.contact_id )
     $financialTypeACLJoin
     WHERE  $whereCond
       AND  is_test = 0
       AND  contact.is_deleted = 0
  GROUP BY  currency
";

    $dao = CRM_Core_DAO::executeQuery($query);
    $amount = [];
    $count = 0;
    while ($dao->fetch()) {
      $count += $dao->total_count;
      $amount[] = CRM_Utils_Money::format($dao->total_amount, $dao->currency);
    }
    if ($count) {
      return [
        'amount' => implode(', ', $amount),
        'count' => $count,
      ];
    }
    return NULL;
  }

  /**
   * Delete the indirect records associated with this contribution first.
   *
   * @param int $id
   *
   * @return mixed|null
   *   $results no of deleted Contribution on success, false otherwise
   * @throws \CRM_Core_Exception
   */
  public static function deleteContribution($id) {
    CRM_Utils_Hook::pre('delete', 'Contribution', $id);

    $transaction = new CRM_Core_Transaction();

    //delete activity record
    $params = [
      'source_record_id' => $id,
      // activity type id for contribution
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Contribution'),
    ];

    CRM_Activity_BAO_Activity::deleteActivity($params);

    //delete billing address if exists for this contribution.
    self::deleteAddress($id);

    //update pledge and pledge payment, CRM-3961
    CRM_Pledge_BAO_PledgePayment::resetPledgePayment($id);

    // remove entry from civicrm_price_set_entity, CRM-5095
    if (CRM_Price_BAO_PriceSet::getFor('civicrm_contribution', $id)) {
      CRM_Price_BAO_PriceSet::removeFrom('civicrm_contribution', $id);
    }

    // delete any related financial records.
    CRM_Core_BAO_FinancialTrxn::deleteFinancialTrxn($id);
    LineItem::delete(FALSE)->addWhere('contribution_id', '=', $id)->execute();

    //delete note.
    $note = CRM_Core_BAO_Note::getNote($id, 'civicrm_contribution');
    $noteId = key($note);
    if ($noteId) {
      CRM_Core_BAO_Note::deleteRecord(['id' => $noteId]);
    }

    $dao = new CRM_Contribute_DAO_Contribution();
    $dao->id = $id;
    $results = $dao->delete();
    $transaction->commit();
    CRM_Utils_Hook::post('delete', 'Contribution', $dao->id, $dao);

    return $results;
  }

  /**
   * Bulk delete multiple records.
   *
   * @param array[] $records
   *
   * @return static[]
   * @throws \CRM_Core_Exception
   */
  public static function deleteRecords(array $records): array {
    $results = [];
    foreach ($records as $record) {
      if (self::deleteContribution($record['id'])) {
        $returnObject = new CRM_Contribute_BAO_Contribution();
        $returnObject->id = $record['id'];
        $results[] = $returnObject;
      }
    }
    return $results;
  }

  /**
   * React to a financial transaction (payment) failure.
   *
   * Prior to CRM-16417 these were simply removed from the database but it has been agreed that seeing attempted
   * payments is important for forensic and outreach reasons.
   *
   * @param int $contributionID
   * @param int $contactID
   * @param string $message
   *
   * @throws \CRM_Core_Exception
   */
  public static function failPayment($contributionID, $contactID, $message) {
    civicrm_api3('activity', 'create', [
      'activity_type_id' => 'Failed Payment',
      'details' => $message,
      'subject' => ts('Payment failed at payment processor'),
      'source_record_id' => $contributionID,
      'source_contact_id' => CRM_Core_Session::getLoggedInContactID() ?: $contactID,
    ]);

    // CRM-20336 Make sure that the contribution status is Failed, not Pending.
    civicrm_api3('contribution', 'create', [
      'id' => $contributionID,
      'contribution_status_id' => 'Failed',
    ]);
  }

  /**
   * Check if there is a contribution with the same trxn_id or invoice_id.
   *
   * @param array $input
   *   An assoc array of name/value pairs.
   * @param array $duplicates
   *   (reference) store ids of duplicate contribs.
   * @param int $id
   *
   * @return bool
   *   true if duplicate, false otherwise
   */
  public static function checkDuplicate($input, &$duplicates, $id = NULL) {
    if (!$id) {
      $id = $input['id'] ?? NULL;
    }
    $trxn_id = $input['trxn_id'] ?? NULL;
    $invoice_id = $input['invoice_id'] ?? NULL;

    $clause = [];
    $input = [];

    if ($trxn_id) {
      $clause[] = 'trxn_id = %1';
      $input[1] = [$trxn_id, 'String'];
    }

    if ($invoice_id) {
      $clause[] = "invoice_id = %2";
      $input[2] = [$invoice_id, 'String'];
    }

    if (empty($clause)) {
      return FALSE;
    }

    $clause = implode(' OR ', $clause);
    if ($id) {
      $clause = "( $clause ) AND id != %3";
      $input[3] = [$id, 'Integer'];
    }

    $query = "SELECT id FROM civicrm_contribution WHERE $clause";
    $dao = CRM_Core_DAO::executeQuery($query, $input);
    $result = FALSE;
    while ($dao->fetch()) {
      $duplicates[] = $dao->id;
      $result = TRUE;
    }
    return $result;
  }

  /**
   * Takes an associative array and creates a contribution_product object.
   *
   * the function extract all the params it needs to initialize the create a
   * contribution_product object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params
   *   (reference) an assoc array of name/value pairs.
   *
   * @return CRM_Contribute_DAO_ContributionProduct
   */
  public static function addPremium(&$params) {
    $contributionProduct = new CRM_Contribute_DAO_ContributionProduct();
    $contributionProduct->copyValues($params);
    return $contributionProduct->save();
  }

  /**
   * Get list of contribution fields for profile.
   * For now we only allow custom contribution fields to be in
   * profile
   *
   * @param bool $addExtraFields
   *   True if special fields needs to be added.
   *
   * @return array
   *   the list of contribution fields
   */
  public static function getContributionFields($addExtraFields = TRUE) {
    $contributionFields = CRM_Contribute_DAO_Contribution::export();
    // @todo remove this - this line was added because payment_instrument_id was not
    // set to exportable - but now it is.
    $contributionFields = array_merge($contributionFields, CRM_Core_OptionValue::getFields($mode = 'contribute'));

    if ($addExtraFields) {
      $contributionFields = array_merge($contributionFields, self::getSpecialContributionFields());
    }

    $contributionFields = array_merge($contributionFields, CRM_Financial_DAO_FinancialType::export());

    foreach ($contributionFields as $key => $var) {
      if ($key === 'contribution_contact_id') {
        continue;
      }
      elseif ($key === 'contribution_campaign_id') {
        $var['title'] = ts('Campaign');
      }
      $fields[$key] = $var;
    }

    $fields = array_merge($fields, CRM_Core_BAO_CustomField::getFieldsForImport('Contribution'));
    return $fields;
  }

  /**
   * Add extra fields specific to contribution.
   */
  public static function getSpecialContributionFields() {
    $extraFields = [
      'contribution_soft_credit_name' => [
        'name' => 'contribution_soft_credit_name',
        'title' => ts('Soft Credit Name'),
        'headerPattern' => '/^soft_credit_name$/i',
        'where' => 'civicrm_contact_d.display_name',
      ],
      'contribution_soft_credit_email' => [
        'name' => 'contribution_soft_credit_email',
        'title' => ts('Soft Credit Email'),
        'headerPattern' => '/^soft_credit_email$/i',
        'where' => 'soft_email.email',
      ],
      'contribution_soft_credit_phone' => [
        'name' => 'contribution_soft_credit_phone',
        'title' => ts('Soft Credit Phone'),
        'headerPattern' => '/^soft_credit_phone$/i',
        'where' => 'soft_phone.phone',
      ],
      'contribution_soft_credit_contact_id' => [
        'name' => 'contribution_soft_credit_contact_id',
        'title' => ts('Soft Credit Contact ID'),
        'headerPattern' => '/^soft_credit_contact_id$/i',
        'where' => 'civicrm_contribution_soft.contact_id',
      ],
      'contribution_pcp_title' => [
        'name' => 'contribution_pcp_title',
        'title' => ts('Personal Campaign Page Title'),
        'headerPattern' => '/^contribution_pcp_title$/i',
        'where' => 'contribution_pcp.title',
      ],
    ];

    return $extraFields;
  }

  /**
   * @param int $pageID
   *
   * @return array
   */
  public static function getCurrentandGoalAmount($pageID) {
    $query = "
SELECT p.goal_amount as goal, sum( c.total_amount ) as total
  FROM civicrm_contribution_page p,
       civicrm_contribution      c
 WHERE p.id = c.contribution_page_id
   AND p.id = %1
   AND c.cancel_date is null
GROUP BY p.id
";

    $config = CRM_Core_Config::singleton();
    $params = [1 => [$pageID, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $params);

    if ($dao->fetch()) {
      return [$dao->goal, $dao->total];
    }
    else {
      return [NULL, NULL];
    }
  }

  /**
   * Get list of contributions which credit the passed in contact ID.
   *
   * The returned array provides details about the original contribution & donor.
   *
   * @param int $honorId
   *   In Honor of Contact ID.
   *
   * @return array
   *   list of contribution fields
   * @todo - this is a confusing function called from one place. It has a test. It would be
   * nice to deprecate it.
   *
   * @deprecated
   */
  public static function getHonorContacts($honorId) {
    CRM_Core_Error::deprecatedFunctionWarning('apiv4');
    $params = [];
    $honorDAO = new CRM_Contribute_DAO_ContributionSoft();
    $honorDAO->contact_id = $honorId;
    $honorDAO->find();

    $type = CRM_Contribute_PseudoConstant::financialType();

    while ($honorDAO->fetch()) {
      $contributionDAO = new CRM_Contribute_DAO_Contribution();
      $contributionDAO->id = $honorDAO->contribution_id;

      if ($contributionDAO->find(TRUE)) {
        $params[$contributionDAO->id]['honor_type'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $honorDAO->soft_credit_type_id);
        $params[$contributionDAO->id]['honorId'] = $contributionDAO->contact_id;
        $params[$contributionDAO->id]['display_name'] = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contributionDAO->contact_id, 'display_name');
        $params[$contributionDAO->id]['type'] = $type[$contributionDAO->financial_type_id];
        $params[$contributionDAO->id]['type_id'] = $contributionDAO->financial_type_id;
        $params[$contributionDAO->id]['amount'] = CRM_Utils_Money::format($contributionDAO->total_amount, $contributionDAO->currency);
        $params[$contributionDAO->id]['source'] = $contributionDAO->source;
        $params[$contributionDAO->id]['receive_date'] = $contributionDAO->receive_date;
        $params[$contributionDAO->id]['contribution_status'] = CRM_Contribute_PseudoConstant::contributionStatus($contributionDAO->contribution_status_id, 'label');
      }
    }

    return $params;
  }

  /**
   * Get the sort name of a contact for a particular contribution.
   *
   * @param int $id
   *   Id of the contribution.
   *
   * @return null|string
   *   sort name of the contact if found
   */
  public static function sortName($id) {
    $id = CRM_Utils_Type::escape($id, 'Integer');

    $query = "
SELECT civicrm_contact.sort_name
FROM   civicrm_contribution, civicrm_contact
WHERE  civicrm_contribution.contact_id = civicrm_contact.id
  AND  civicrm_contribution.id = {$id}
";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Generate summary of amount received in the current fiscal year to date from the contact or contacts.
   *
   * @param int|array $contactIDs
   *
   * @return array
   */
  public static function annual($contactIDs) {
    if (!is_array($contactIDs)) {
      // In practice I can't fine any evidence that this function is ever called with
      // anything other than a single contact id, but left like this due to .... fear.
      $contactIDs = explode(',', $contactIDs);
    }

    $query = self::getAnnualQuery($contactIDs);
    $dao = CRM_Core_DAO::executeQuery($query);
    $count = 0;
    $amount = $average = [];
    while ($dao->fetch()) {
      if ($dao->count > 0 && $dao->amount > 0) {
        $count += $dao->count;
        $amount[] = CRM_Utils_Money::format($dao->amount, $dao->currency);
        $average[] = CRM_Utils_Money::format($dao->average, $dao->currency);
      }
    }
    if ($count > 0) {
      return [
        $count,
        implode(',&nbsp;', $amount),
        implode(',&nbsp;', $average),
      ];
    }
    return [0, 0, 0];
  }

  /**
   * Check if there is a contribution with the params passed in.
   *
   * Used for trxn_id,invoice_id and contribution_id
   *
   * @param array $params
   *   An assoc array of name/value pairs.
   *
   * @return array
   *   contribution id if success else NULL
   */
  public static function checkDuplicateIds($params) {
    $dao = new CRM_Contribute_DAO_Contribution();

    $clause = [];
    $input = [];
    foreach ($params as $k => $v) {
      if ($v) {
        $clause[] = "$k = '$v'";
      }
    }
    $clause = implode(' AND ', $clause);
    $query = "SELECT id FROM civicrm_contribution WHERE $clause";
    $dao = CRM_Core_DAO::executeQuery($query, $input);

    while ($dao->fetch()) {
      $result = $dao->id;
      return $result;
    }
    return NULL;
  }

  /**
   * Get the contribution details for component export.
   *
   * @param int $exportMode
   *   Export mode.
   * @param array $componentIds
   *   Component ids.
   *
   * @deprecated since 5.72 will be removed around 5.90
   *
   * @return array
   *   associated array
   */
  public static function getContributionDetails($exportMode, $componentIds) {
    CRM_Core_Error::deprecatedFunctionWarning('no alternative');
    $paymentDetails = [];
    $componentClause = ' IN ( ' . implode(',', $componentIds) . ' ) ';

    if ($exportMode == CRM_Export_Form_Select::EVENT_EXPORT) {
      $componentSelect = " civicrm_participant_payment.participant_id id";
      $additionalClause = "
INNER JOIN civicrm_participant_payment ON (civicrm_contribution.id = civicrm_participant_payment.contribution_id
AND civicrm_participant_payment.participant_id {$componentClause} )
";
    }
    elseif ($exportMode == CRM_Export_Form_Select::MEMBER_EXPORT) {
      $componentSelect = " civicrm_membership_payment.membership_id id";
      $additionalClause = "
INNER JOIN civicrm_membership_payment ON (civicrm_contribution.id = civicrm_membership_payment.contribution_id
AND civicrm_membership_payment.membership_id {$componentClause} )
";
    }
    elseif ($exportMode == CRM_Export_Form_Select::PLEDGE_EXPORT) {
      $componentSelect = " civicrm_pledge_payment.id id";
      $additionalClause = "
INNER JOIN civicrm_pledge_payment ON (civicrm_contribution.id = civicrm_pledge_payment.contribution_id
AND civicrm_pledge_payment.pledge_id {$componentClause} )
";
    }

    $query = " SELECT total_amount, contribution_status.name as status_id, contribution_status.label as status, payment_instrument.name as payment_instrument, receive_date,
                          trxn_id, {$componentSelect}
FROM civicrm_contribution
LEFT JOIN civicrm_option_group option_group_payment_instrument ON ( option_group_payment_instrument.name = 'payment_instrument')
LEFT JOIN civicrm_option_value payment_instrument ON (civicrm_contribution.payment_instrument_id = payment_instrument.value
     AND option_group_payment_instrument.id = payment_instrument.option_group_id )
LEFT JOIN civicrm_option_group option_group_contribution_status ON (option_group_contribution_status.name = 'contribution_status')
LEFT JOIN civicrm_option_value contribution_status ON (civicrm_contribution.contribution_status_id = contribution_status.value
                               AND option_group_contribution_status.id = contribution_status.option_group_id )
{$additionalClause}
";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $paymentDetails[$dao->id] = [
        'total_amount' => $dao->total_amount,
        'contribution_status' => $dao->status,
        'receive_date' => $dao->receive_date,
        'pay_instru' => $dao->payment_instrument,
        'trxn_id' => $dao->trxn_id,
      ];
    }

    return $paymentDetails;
  }

  /**
   * Create address associated with contribution record.
   *
   * As long as there is one or more billing field in the parameters we will create the address.
   *
   * (historically the decision to create or not was based on the payment 'type' but these lines are greyer than once
   * thought).
   *
   * @param array $params
   *
   * @return int
   *   address id
   */
  public static function createAddress($params) {
    $billingLocationTypeID = CRM_Core_BAO_LocationType::getBilling();
    [$hasBillingField, $addressParams] = self::getBillingAddressParams($params, $billingLocationTypeID);
    if ($hasBillingField) {
      $nameFields = [
        $params['billing_first_name'] ?? NULL,
        $params['billing_middle_name'] ?? NULL,
        $params['billing_last_name'] ?? NULL,
      ];
      $addressParams['name'] = implode(' ', array_filter($nameFields));
      return (int) CRM_Core_BAO_Address::writeRecord($addressParams)->id;
    }
    return NULL;

  }

  /**
   * Delete billing address record related contribution.
   *
   * @param int $contributionId
   * @param int $contactId
   */
  public static function deleteAddress($contributionId = NULL, $contactId = NULL) {
    $clauses = [];
    $contactJoin = NULL;

    if ($contributionId) {
      $clauses[] = "cc.id = {$contributionId}";
    }

    if ($contactId) {
      $clauses[] = "cco.id = {$contactId}";
      $contactJoin = "INNER JOIN civicrm_contact cco ON cc.contact_id = cco.id";
    }

    if (empty($clauses)) {
      throw new CRM_Core_Exception('No Where clauses defined when deleting address');
    }

    $condition = implode(' OR ', $clauses);

    $query = "
SELECT     ca.id
FROM       civicrm_address ca
INNER JOIN civicrm_contribution cc ON cc.address_id = ca.id
           $contactJoin
WHERE      $condition
";
    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      Address::delete(FALSE)->addWhere('id', '=', $dao->id)->execute();
    }
  }

  /**
   * This function check online pending contribution associated w/
   * Online Event Registration or Online Membership signup.
   *
   * @param int $componentId
   *   Participant id.
   *
   * @return int
   *   pending contribution id.
   */
  public static function checkOnlinePendingContribution($componentId) {
    $contributionId = NULL;

    $idName = 'participant_id';
    $componentTable = 'civicrm_participant';
    $paymentTable = 'civicrm_participant_payment';
    $source = ts('Online Event Registration');
    $pendingStatusId = array_search('Pending', CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name'));

    $query = "
   SELECT  component.id as {$idName},
           componentPayment.contribution_id as contribution_id,
           contribution.source source,
           contribution.contribution_status_id as contribution_status_id,
           contribution.is_pay_later as is_pay_later
     FROM  $componentTable component
LEFT JOIN  $paymentTable componentPayment    ON ( componentPayment.{$idName} = component.id )
LEFT JOIN  civicrm_contribution contribution ON ( componentPayment.contribution_id = contribution.id )
    WHERE  component.id = {$componentId}";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      if ($dao->contribution_id &&
        $dao->is_pay_later &&
        $dao->contribution_status_id == $pendingStatusId &&
        str_contains($dao->source, $source)
      ) {
        $contributionId = $dao->contribution_id;
      }
    }

    return $contributionId;
  }

  /**
   * Returns all contribution related object ids.
   *
   * @param $contributionId
   *
   * @return array
   */
  public static function getComponentDetails($contributionId) {
    $componentDetails = $pledgePayment = [];
    if (!$contributionId) {
      return $componentDetails;
    }

    $query = "
      SELECT    c.id                 as contribution_id,
                c.contact_id         as contact_id,
                c.contribution_recur_id,
                mp.membership_id     as membership_id,
                m.membership_type_id as membership_type_id,
                pp.participant_id    as participant_id,
                p.event_id           as event_id,
                pgp.id               as pledge_payment_id
      FROM      civicrm_contribution c
      LEFT JOIN civicrm_membership_payment  mp   ON mp.contribution_id = c.id
      LEFT JOIN civicrm_participant_payment pp   ON pp.contribution_id = c.id
      LEFT JOIN civicrm_participant         p    ON pp.participant_id  = p.id
      LEFT JOIN civicrm_membership          m    ON m.id  = mp.membership_id
      LEFT JOIN civicrm_pledge_payment      pgp  ON pgp.contribution_id  = c.id
      WHERE     c.id = $contributionId
      -- only get the primary recipient
      AND (p.registered_by_id IS NULL OR p.registered_by_id = 0)
      ";

    $dao = CRM_Core_DAO::executeQuery($query);
    $componentDetails = [];

    while ($dao->fetch()) {
      $componentDetails['component'] = $dao->participant_id ? 'event' : 'contribute';
      $componentDetails['contact_id'] = $dao->contact_id;
      if ($dao->event_id) {
        $componentDetails['event'] = $dao->event_id;
      }
      if ($dao->participant_id) {
        $componentDetails['participant'] = $dao->participant_id;
      }
      if ($dao->membership_id) {
        if (!isset($componentDetails['membership'])) {
          $componentDetails['membership'] = $componentDetails['membership_type'] = [];
        }
        $componentDetails['membership'][] = $dao->membership_id;
        $componentDetails['membership_type'][] = $dao->membership_type_id;
      }
      if ($dao->pledge_payment_id) {
        $pledgePayment[] = $dao->pledge_payment_id;
      }
      if ($dao->contribution_recur_id) {
        $componentDetails['contributionRecur'] = $dao->contribution_recur_id;
      }
    }

    if ($pledgePayment) {
      $componentDetails['pledge_payment'] = $pledgePayment;
    }

    return $componentDetails;
  }

  /**
   * @param int $contactId
   * @param bool $includeSoftCredit
   *
   * @return null|string
   */
  public static function contributionCount($contactId, $includeSoftCredit = TRUE) {
    if (!$contactId) {
      return 0;
    }
    $financialTypes = CRM_Financial_BAO_FinancialType::getAllAvailableFinancialTypes();
    $additionalWhere = " AND contribution.financial_type_id IN (0)";
    $liWhere = " AND i.financial_type_id IN (0)";
    if (!empty($financialTypes)) {
      $additionalWhere = " AND contribution.financial_type_id IN (" . implode(',', array_keys($financialTypes)) . ")";
      $liWhere = " AND i.financial_type_id NOT IN (" . implode(',', array_keys($financialTypes)) . ")";
    }
    $contactContributionsSQL = "
      SELECT contribution.id AS id
      FROM civicrm_contribution contribution
      LEFT JOIN civicrm_line_item i ON i.contribution_id = contribution.id AND i.entity_table = 'civicrm_contribution' $liWhere
      WHERE contribution.is_test = 0 AND contribution.is_template != '1' AND contribution.contact_id = {$contactId}
      $additionalWhere
      AND i.id IS NULL";

    $contactSoftCreditContributionsSQL = "
      SELECT contribution.id
      FROM civicrm_contribution contribution INNER JOIN civicrm_contribution_soft softContribution
      ON ( contribution.id = softContribution.contribution_id )
      WHERE contribution.is_test = 0 AND contribution.is_template != '1' AND softContribution.contact_id = {$contactId}
      $additionalWhere ";
    $query = "SELECT count( x.id ) count FROM ( ";
    $query .= $contactContributionsSQL;

    if ($includeSoftCredit) {
      $query .= " UNION ";
      $query .= $contactSoftCreditContributionsSQL;
    }

    $query .= ") x";

    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * Repeat a transaction as part of a recurring series.
   *
   * @internal NOT supported to be called from outside of core. Note this function
   * was made public to be called from the v3 api which IS supported so we can
   * amend it without regards to possible external callers as this warning
   * was added in the same commit as it was made public rather than protected.
   *
   * The ideal flow is
   * 1) Processor calls contribution.repeattransaction with contribution_status_id = Pending
   * 2) The repeattransaction loads the 'template contribution' and calls a hook to allow altering of it .
   * 3) Repeat transaction calls order.create to create the pending contribution with correct line items
   *   and associated entities.
   * 4) The  calling code calls Payment.create which in turn calls CompleteOrder (if completing)
   *   which updates the various entities and sends appropriate emails.
   *
   * Gaps in the above (
   *
   * @param array $input
   *    - total_amount
   *    - financial_type_id
   *    - campaign_id
   *
   *    These keys are all optional, and are combined with the values from the contribution_recur
   *    record to override values from the template contribution. Overrides are
   *    subject to the following limitations
   *    1) the campaign id & is_test always apply (is_test is available on the recurring but not as input)
   *    2) the total amount & financial type ID overrides ONLY apply if the contribution has
   *    only one line item.
   *
   *    The template contribution is derived from a contribution linked to
   *    the recurring contribution record. A true template contribution is only used
   *    as a template and is_template is set to TRUE. If this cannot be found the latest added contribution
   *    is used.
   *
   * @param int $recurringContributionID
   *
   * @return bool|array
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @todo
   *
   *  2) repeattransaction code is callable from completeTransaction code for historical bad coding reasons
   *  3) Repeat transaction duplicates rather than calls Order.create
   *  4) Use of payment.create still limited - completetransaction is more common.
   */
  public static function repeatTransaction(array $input, int $recurringContributionID) {
    // @todo - this was shared with `completeOrder` and not all necessarily apply.
    $inputContributionWhiteList = [
      'fee_amount',
      'net_amount',
      'trxn_id',
      'check_number',
      'payment_instrument_id',
      'is_test',
      'campaign_id',
      'receive_date',
      'receipt_date',
      'contribution_status_id',
      'card_type_id',
      'pan_truncation',
    ];

    $paymentProcessorId = $input['payment_processor_id'] ?? NULL;

    $completedContributionStatusID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

    // @todo this was taken from completeContribution - it may be this just duplicates
    // upcoming filtering & can go.
    $contributionParams = array_merge([
      'contribution_status_id' => $completedContributionStatusID,
    ], array_intersect_key($input, array_fill_keys($inputContributionWhiteList, 1)
    ));

    $contributionParams['payment_processor'] = $paymentProcessorId;

    if (empty($contributionParams['payment_instrument_id']) && $paymentProcessorId) {
      $contributionParams['payment_instrument_id'] = PaymentProcessor::get(FALSE)->addWhere('id', '=', $paymentProcessorId)->addSelect('payment_instrument_id')->execute()->first()['payment_instrument_id'];
    }

    if ($recurringContributionID) {
      $contributionParams['contribution_recur_id'] = $recurringContributionID;
    }
    $isCompleted = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contributionParams['contribution_status_id']) === 'Completed';
    $templateContribution = CRM_Contribute_BAO_ContributionRecur::getTemplateContribution(
      (int) $contributionParams['contribution_recur_id'],
      [
        'total_amount' => $input['total_amount'] ?? NULL,
        'financial_type_id' => $input['financial_type_id'] ?? NULL,
        'campaign_id' => $input['campaign_id'] ?? NULL,
      ]
    );
    $contributionParams['line_item'] = $templateContribution['line_item'];
    $contributionParams['status_id'] = 'Pending';

    foreach (['contact_id', 'campaign_id', 'financial_type_id', 'currency', 'source', 'amount_level', 'address_id', 'on_behalf', 'source_contact_id', 'contribution_page_id', 'total_amount'] as $fieldName) {
      if (isset($templateContribution[$fieldName])) {
        $contributionParams[$fieldName] = $templateContribution[$fieldName];
      }
    }

    $contributionParams['source'] ??= ts('Recurring contribution');

    $createContribution = civicrm_api3('Contribution', 'create', $contributionParams);
    $temporaryObject = new CRM_Contribute_BAO_Contribution();
    $temporaryObject->copyCustomFields($templateContribution['id'], $createContribution['id']);
    // Add new soft credit against current $contribution.
    CRM_Contribute_BAO_ContributionRecur::addrecurSoftCredit($contributionParams['contribution_recur_id'], $createContribution['id']);
    CRM_Contribute_BAO_ContributionRecur::updateRecurLinkedPledge($createContribution['id'], $contributionParams['contribution_recur_id'],
      $contributionParams['status_id'], $contributionParams['total_amount']);

    if ($isCompleted) {
      // Ideally add deprecation notice here & only accept pending for repeattransaction.
      return self::completeOrder($input, NULL, $createContribution['id']);
    }
    return $createContribution;
  }

  /**
   * Get individual id for onbehalf contribution.
   *
   * @param int $contributionId
   *   Contribution id.
   * @param int $contributorId
   *   Contributor id.
   *
   * @return array
   *   containing organization id and individual id
   */
  public static function getOnbehalfIds($contributionId, $contributorId = NULL) {

    $ids = [];

    if (!$contributionId) {
      return $ids;
    }

    // fetch contributor id if null
    if (!$contributorId) {
      $contributorId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution',
        $contributionId, 'contact_id'
      );
    }

    $activityTypeIds = CRM_Core_PseudoConstant::activityType(TRUE, FALSE, FALSE, 'name');
    $activityTypeId = array_search('Contribution', $activityTypeIds);

    if ($activityTypeId && $contributorId) {
      $activityQuery = "
SELECT     civicrm_activity_contact.contact_id
  FROM     civicrm_activity_contact
INNER JOIN civicrm_activity ON civicrm_activity_contact.activity_id = civicrm_activity.id
 WHERE     civicrm_activity.activity_type_id   = %1
   AND     civicrm_activity.source_record_id   = %2
   AND     civicrm_activity_contact.record_type_id = %3
";

      $activityContacts = CRM_Activity_BAO_ActivityContact::buildOptions('record_type_id', 'validate');
      $sourceID = CRM_Utils_Array::key('Activity Source', $activityContacts);

      $params = [
        1 => [$activityTypeId, 'Integer'],
        2 => [$contributionId, 'Integer'],
        3 => [$sourceID, 'Integer'],
      ];

      $sourceContactId = CRM_Core_DAO::singleValueQuery($activityQuery, $params);

      // for on behalf contribution source is individual and contributor is organization
      if ($sourceContactId && $sourceContactId != $contributorId) {
        $relationshipTypeIds = CRM_Core_PseudoConstant::relationshipType('name');
        // get rel type id for employee of relation
        foreach ($relationshipTypeIds as $id => $typeVals) {
          if ($typeVals['name_a_b'] == 'Employee of') {
            $relationshipTypeId = $id;
            break;
          }
        }

        $rel = new CRM_Contact_DAO_Relationship();
        $rel->relationship_type_id = $relationshipTypeId;
        $rel->contact_id_a = $sourceContactId;
        $rel->contact_id_b = $contributorId;
        if ($rel->find(TRUE)) {
          $ids['individual_id'] = $rel->contact_id_a;
          $ids['organization_id'] = $rel->contact_id_b;
        }
      }
    }

    return $ids;
  }

  /**
   * @return array
   */
  public static function getContributionDates() {
    $config = CRM_Core_Config::singleton();
    $currentMonth = date('m');
    $currentDay = date('d');
    if ((int ) $config->fiscalYearStart['M'] > $currentMonth ||
      ((int ) $config->fiscalYearStart['M'] == $currentMonth &&
        (int ) $config->fiscalYearStart['d'] > $currentDay
      )
    ) {
      $year = date('Y') - 1;
    }
    else {
      $year = date('Y');
    }
    $year = ['Y' => $year];
    $yearDate = $config->fiscalYearStart;
    $yearDate = array_merge($year, $yearDate);
    $yearDate = CRM_Utils_Date::format($yearDate);

    $monthDate = date('Ym') . '01';

    $now = date('Ymd');

    return [
      'now' => $now,
      'yearDate' => $yearDate,
      'monthDate' => $monthDate,
    ];
  }

  /**
   * Load objects relations to contribution object.
   * Objects are stored in the $_relatedObjects property
   * In the first instance we are just moving functionality from BASEIpn -
   *
   * @see http://issues.civicrm.org/jira/browse/CRM-9996
   *
   * Note that the unit test for the BaseIPN class tests this function
   *
   * @param int $paymentProcessorID
   *   Payment Processor ID.
   * @param array $ids
   *   Ids as Loaded by Payment Processor.
   *
   * @deprecated since 5.75 will be removed around 5.99.
   * Note some universe usages exist but may be in unused extensions.
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  public function loadRelatedObjects($paymentProcessorID, &$ids) {
    CRM_Core_Error::deprecatedFunctionWarning('use Payment.create to complete orders');
    // @todo deprecate this function - we are slowly returning the functionality to
    // the calling functions so this can be unravelled. It is only called from
    // tests, composeMessage & transitionComponents. The last of these is itself
    // deprecated, to be replaced by using Payment.create. Many parts of this are
    // used by only one, or neither, of the actual calling functions.

    $ids['contributionType'] = $this->financial_type_id;
    $ids['financialType'] = $this->financial_type_id;
    if ($this->contribution_page_id) {
      $ids['contributionPage'] = $this->contribution_page_id;
    }

    $this->loadRelatedEntitiesByID($ids);

    if (!empty($ids['contributionRecur']) && !$paymentProcessorID) {
      $paymentProcessorID = $this->_relatedObjects['contributionRecur']->payment_processor_id;
    }

    if (!empty($ids['pledge_payment'])) {
      foreach ($ids['pledge_payment'] as $key => $paymentID) {
        if (empty($paymentID)) {
          continue;
        }
        $payment = new CRM_Pledge_BAO_PledgePayment();
        $payment->id = $paymentID;
        if (!$payment->find(TRUE)) {
          throw new CRM_Core_Exception("Could not find pledge payment record: " . $paymentID);
        }
        $this->_relatedObjects['pledge_payment'][] = $payment;
      }
    }

    // These are probably no longer accessed from anywhere
    // @todo remove this line, after ensuring not used.
    $ids = $this->loadRelatedMembershipObjects($ids);

    if ($this->_component != 'contribute') {
      // we are in event mode
      // make sure event exists and is valid
      $event = new CRM_Event_BAO_Event();
      $event->id = $ids['event'];
      if ($ids['event'] &&
        !$event->find(TRUE)
      ) {
        throw new CRM_Core_Exception("Could not find event: " . $ids['event']);
      }

      $this->_relatedObjects['event'] = &$event;

      $participant = new CRM_Event_BAO_Participant();
      $participant->id = $ids['participant'];
      if ($ids['participant'] &&
        !$participant->find(TRUE)
      ) {
        throw new CRM_Core_Exception("Could not find participant: " . $ids['participant']);
      }
      $participant->register_date = CRM_Utils_Date::isoToMysql($participant->register_date);

      $this->_relatedObjects['participant'] = &$participant;

      // get the payment processor id from event - this is inaccurate see CRM-16923
      // in future we should look at throwing an exception here rather than an dubious guess.
      if (!$paymentProcessorID) {
        $paymentProcessorID = $this->_relatedObjects['event']->payment_processor;
        if ($paymentProcessorID) {
          $intentionalEnotice = $CRM16923AnUnreliableMethodHasBeenUserToDeterminePaymentProcessorFromEvent;
        }
      }
    }

    $relatedContact = CRM_Contribute_BAO_Contribution::getOnbehalfIds($this->id);
    if (!empty($relatedContact['individual_id'])) {
      $ids['related_contact'] = $relatedContact['individual_id'];
    }

    if ($paymentProcessorID) {
      $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID,
        $this->is_test ? 'test' : 'live'
      );
      $ids['paymentProcessor'] = $paymentProcessorID;
      $this->_relatedObjects['paymentProcessor'] = $paymentProcessor;
    }

    // Add contribution id to $ids. CRM-20401
    $ids['contribution'] = $this->id;
    return TRUE;
  }

  /**
   * Create array of message information - ie. return html version, txt
   * version, to field
   *
   * @param array $input
   *   Incoming information.
   *   - is_recur - should this be treated as recurring (not sure why you
   *   wouldn't just check presence of recur object but maintaining legacy
   *   approach to be careful)
   * @param array $ids
   *   IDs of related objects.
   * @param array $values
   *   Any values that may have already been compiled by calling process.
   *   This is augmented by values 'gathered' by gatherMessageValues
   * @param bool $returnMessageText
   *   Distinguishes between whether to send message or return.
   *   message text. We are working towards this function ALWAYS returning
   *   message text & calling function doing emails / pdfs with it
   *
   * @return array
   *   messages
   * @throws \CRM_Core_Exception
   */
  public function composeMessageArray($input, $ids, $values = [], $returnMessageText = TRUE) {
    $ids = array_merge(self::getComponentDetails($this->id), $ids);
    if (empty($ids['contact']) && isset($this->contact_id)) {
      $ids['contact'] = $this->contact_id;
    }
    if (!empty($ids['event'])) {
      $this->_component = 'event';
    }
    else {
      $this->_component = 'contribute';
    }

    // If the object is not fully populated then make sure it is - this is a more about legacy paths & cautious
    // refactoring than anything else, and has unit test coverage.
    if (empty($this->financial_type_id)) {
      $this->find(TRUE);
    }

    $paymentProcessorID = $input['payment_processor_id'] ?? NULL;

    $ids['contributionType'] = $this->financial_type_id;
    $ids['financialType'] = $this->financial_type_id;
    if ($this->contribution_page_id) {
      $ids['contributionPage'] = $this->contribution_page_id;
    }

    $entities = [
      'contact' => 'CRM_Contact_BAO_Contact',
      'contributionRecur' => 'CRM_Contribute_BAO_ContributionRecur',
    ];
    foreach ($entities as $entity => $bao) {
      if (!empty($ids[$entity])) {
        $this->_relatedObjects[$entity] = new $bao();
        $this->_relatedObjects[$entity]->id = $ids[$entity];
        if (!$this->_relatedObjects[$entity]->find(TRUE)) {
          throw new CRM_Core_Exception($entity . ' could not be loaded');
        }
      }
    }

    if (!empty($ids['contributionRecur']) && !$paymentProcessorID) {
      $paymentProcessorID = $this->_relatedObjects['contributionRecur']->payment_processor_id;
    }

    // These are probably no longer accessed from anywhere
    $query = "
      SELECT membership_id
      FROM   civicrm_membership_payment
      WHERE  contribution_id = %1 ";
    $params = [1 => [$this->id, 'Integer']];
    $ids['membership'] = (array) ($ids['membership'] ?? []);

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      if ($dao->membership_id && !in_array($dao->membership_id, $ids['membership'])) {
        $ids['membership'][$dao->membership_id] = $dao->membership_id;
      }
    }

    if (array_key_exists('membership', $ids) && is_array($ids['membership'])) {
      foreach ($ids['membership'] as $id) {
        if (!empty($id)) {
          $membership = new CRM_Member_BAO_Membership();
          $membership->id = $id;
          if (!$membership->find(TRUE)) {
            throw new Exception("Could not find membership record: $id");
          }
          $membership->join_date = CRM_Utils_Date::isoToMysql($membership->join_date);
          $membership->start_date = CRM_Utils_Date::isoToMysql($membership->start_date);
          $membership->end_date = CRM_Utils_Date::isoToMysql($membership->end_date);
          $this->_relatedObjects['membership'][$membership->id . '_' . $membership->membership_type_id] = $membership;

        }
      }
    }

    $eventID = isset($ids['event']) ? (int) $ids['event'] : NULL;
    $participantID = isset($ids['participant']) ? (int) $ids['participant'] : NULL;
    $contributionID = (int) $this->id;
    $contactID = (int) $ids['contact'];
    $onbehalfDedupeAlert = $ids['onbehalf_dupe_alert'] ?? NULL;
    // not sure whether it is possible for this not to be an array - load related contacts loads an array but this code was expecting a string
    // the addition of the casting is in case it could get here & be a string. Added in 4.6 - maybe remove later? This AuthorizeNetIPN & PaypalIPN tests hit this
    // line having loaded an array
    $membershipIDs = !empty($ids['membership']) ? (array) $ids['membership'] : NULL;
    unset($ids);

    if ($this->_component != 'contribute') {
      // we are in event mode
      // make sure event exists and is valid
      $event = new CRM_Event_BAO_Event();
      $event->id = $eventID;
      if ($eventID &&
        !$event->find(TRUE)
      ) {
        throw new CRM_Core_Exception("Could not find event: " . $eventID);
      }

      $this->_relatedObjects['event'] = &$event;

      $participant = new CRM_Event_BAO_Participant();
      $participant->id = $participantID;
      if ($participantID &&
        !$participant->find(TRUE)
      ) {
        throw new CRM_Core_Exception("Could not find participant: " . $participantID);
      }
      $participant->register_date = CRM_Utils_Date::isoToMysql($participant->register_date);

      $this->_relatedObjects['participant'] = &$participant;
    }

    //not really sure what params might be passed in but lets merge em into values
    $values = array_merge($this->_gatherMessageValues($values, $eventID, $participantID), $values);
    $values['is_email_receipt'] = !$returnMessageText;
    foreach (['receipt_date', 'cc_receipt', 'bcc_receipt', 'receipt_from_name', 'receipt_from_email', 'receipt_text', 'pay_later_receipt'] as $fld) {
      if (!empty($input[$fld])) {
        $values[$fld] = $input[$fld];
      }
    }

    $template = $this->_assignMessageVariablesToTemplate($values, $input, $returnMessageText);
    //what does recur 'mean here - to do with payment processor return functionality but
    // what is the importance
    if (!empty($this->contribution_recur_id) && $paymentProcessorID) {
      // Probably we don't need these 2 lines & can do Civi\Payment\System::singleton()->getById()
      $paymentProcessor = CRM_Financial_BAO_PaymentProcessor::getPayment($paymentProcessorID,
        $this->is_test ? 'test' : 'live'
      );
      $paymentObject = Civi\Payment\System::singleton()->getByProcessor($paymentProcessor);

      $entityID = $entity = NULL;
      if ($contributionID) {
        $entity = 'contribution';
        $entityID = $contributionID;
      }
      if ($membershipIDs) {
        $entity = 'membership';
        $entityID = $membershipIDs[0];
      }

      $template->assign('cancelSubscriptionUrl', $paymentObject->subscriptionURL($entityID, $entity, 'cancel'));
      $template->assign('updateSubscriptionBillingUrl', $paymentObject->subscriptionURL($entityID, $entity, 'billing'));
      $template->assign('updateSubscriptionUrl', $paymentObject->subscriptionURL($entityID, $entity, 'update'));
    }

    if ($this->_component === 'event') {
      $eventParams = ['id' => $eventID];
      $values['event'] = [];

      CRM_Event_BAO_Event::retrieve($eventParams, $values['event']);

      //get location details
      $locationParams = [
        'entity_id' => $eventID,
        'entity_table' => 'civicrm_event',
      ];
      $values['location'] = CRM_Core_BAO_Location::getValues($locationParams);

      //for tasks 'Change Participant Status' and 'Update multiple Contributions' case
      //and cases involving status updation through ipn
      // whatever that means!
      // total_amount appears to be the preferred input param & it is unclear why we support amount here
      // perhaps we should throw an e-notice if amount is set & force total_amount?
      if (!empty($input['amount'])) {
        $values['totalAmount'] = $input['amount'];
      }
      // @todo set this in is_email_receipt, based on $this->_relatedObjects.
      if ($values['event']['is_email_confirm']) {
        $values['is_email_receipt'] = 1;
      }

      if ($contributionID) {
        $values['contributionId'] = $contributionID;
      }

      return CRM_Event_BAO_Event::sendMail($contactID, $values,
        $participantID, $this->is_test, $returnMessageText
      );
    }
    else {
      $values['contribution_id'] = $this->id;
      $relatedContactID = CRM_Contribute_BAO_Contribution::getOnbehalfIds($this->id)['individual_id'] ?? NULL;
      if ($relatedContactID) {
        $values['related_contact'] = $relatedContactID;
        if ($onbehalfDedupeAlert) {
          $values['onbehalf_dupe_alert'] = $onbehalfDedupeAlert;
        }
        $entityBlock = [
          'contact_id' => $contactID,
          'location_type_id' => CRM_Core_DAO::getFieldValue('CRM_Core_DAO_LocationType',
            'Home', 'id', 'name'
          ),
        ];
        $address = CRM_Core_BAO_Address::getValues($entityBlock);
        $template->assign('onBehalfAddress', $address[$entityBlock['location_type_id']]['display'] ?? NULL);
      }
      $isTest = FALSE;
      if ($this->is_test) {
        $isTest = TRUE;
      }
      $values['modelProps'] = $input['modelProps'] ?? [];
      if (!empty($this->_relatedObjects['membership'])) {
        foreach ($this->_relatedObjects['membership'] as $membership) {
          if ($membership->id) {
            $values['membership_id'] = $membership->id;
            $membership_status = CRM_Member_PseudoConstant::membershipStatus($membership->status_id, NULL, 'label');
            if ($membership_status === 'Pending' && $membership->is_pay_later == 1) {
              $values['is_pay_later'] = 1;
            }
            // Pass amount to floatval as string '0.00' is considered a
            // valid amount and includes Fee section in the mail.
            if (isset($values['amount'])) {
              $values['amount'] = floatval($values['amount']);
            }

            if (!empty($this->contribution_recur_id) && $paymentObject) {
              $url = $paymentObject->subscriptionURL($membership->id, 'membership', 'cancel');
              $template->assign('cancelSubscriptionUrl', $url);
              $url = $paymentObject->subscriptionURL($membership->id, 'membership', 'billing');
              $template->assign('updateSubscriptionBillingUrl', $url);
              $url = $paymentObject->subscriptionURL($entityID, $entity, 'update');
              $template->assign('updateSubscriptionUrl', $url);
            }

            $result = CRM_Contribute_BAO_ContributionPage::sendMail($contactID, $values, $isTest, $returnMessageText);

            return $result;
            // otherwise if its about sending emails, continue sending without return, as we
            // don't want to exit the loop.
          }
        }
      }
      else {
        return CRM_Contribute_BAO_ContributionPage::sendMail($contactID, $values, $isTest, $returnMessageText);
      }
    }
  }

  /**
   * Gather values for contribution mail - this function has been created
   * as part of CRM-9996 refactoring as a step towards simplifying the composeMessage function
   * Values related to the contribution in question are gathered
   *
   * @param array $values
   * @param int|null $eventID
   * @param int|null $participantID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function _gatherMessageValues($values, ?int $eventID, ?int $participantID) {
    // set display address of contributor
    $values['billingName'] = '';
    if ($this->address_id) {
      $addressDetails = CRM_Core_BAO_Address::getValues(['id' => $this->address_id], FALSE, 'id');
      $addressDetails = reset($addressDetails);
      $values['billingName'] = $addressDetails['name'] ?? '';
    }
    // Else we assign the billing address of the contribution contact.
    else {
      $addressDetails = (array) CRM_Core_BAO_Address::getValues(['contact_id' => $this->contact_id, 'is_billing' => 1]);
      $addressDetails = reset($addressDetails);
    }
    $values['address'] = $addressDetails['display'] ?? '';

    if ($this->_component === 'contribute') {
      //get soft contributions
      $softContributions = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($this->id, TRUE);
      if (!empty($softContributions)) {
        // For pcp soft credit, there is no 'soft_credit' member it comes
        // back in different array members, but shortly after returning from
        // this function it calls _assignMessageVariablesToTemplate which does
        // its own lookup of any pcp soft credit, so we can skip it here.
        $values['softContributions'] = $softContributions['soft_credit'] ?? NULL;
      }
      if (isset($this->contribution_page_id)) {
        // This is a call we want to use less, in favour of loading related objects.
        $values = $this->addContributionPageValuesToValuesHeavyHandedly($values);
        if ($this->contribution_page_id) {
          CRM_Contribute_BAO_Contribution_Utils::overrideDefaultCurrency($values);
        }
      }
      // no contribution page -probably back office
      else {
        // Handle re-print receipt for offline contributions (call from PDF.php - no contribution_page_id)
        $values['title'] = 'Contribution';
      }
      // set lineItem for contribution
      if ($this->id) {
        $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($this->id);
        if (!empty($lineItems)) {
          $firstLineItem = reset($lineItems);
          $priceSet = [];
          if (!empty($firstLineItem['price_set_id'])) {
            $priceSet = civicrm_api3('PriceSet', 'getsingle', [
              'id' => $firstLineItem['price_set_id'],
              'return' => 'is_quick_config, id',
            ]);
            $values['priceSetID'] = $priceSet['id'];
          }
          foreach ($lineItems as &$eachItem) {
            if ($eachItem['entity_table'] === 'civicrm_membership') {
              $membership = reset(civicrm_api3('Membership', 'get', [
                'id' => $eachItem['entity_id'],
                'return' => ['join_date', 'start_date', 'end_date'],
              ])['values']);
              if ($membership) {
                $eachItem['join_date'] = CRM_Utils_Date::customFormat($membership['join_date']);
                $eachItem['start_date'] = CRM_Utils_Date::customFormat($membership['start_date']);
                $eachItem['end_date'] = CRM_Utils_Date::customFormat($membership['end_date']);
              }
            }
            // This is actually used in conjunction with is_quick_config in the template & we should deprecate it.
            // However, that does create upgrade pain so would be better to be phased in.
            $values['useForMember'] = empty($priceSet['is_quick_config']);
          }
          $values['lineItem'][0] = $lineItems;
        }
      }

      $relatedContact = CRM_Contribute_BAO_Contribution::getOnbehalfIds(
        $this->id,
        $this->contact_id
      );
      // if this is onbehalf of contribution then set related contact
      if (!empty($relatedContact['individual_id'])) {
        $values['related_contact'] = $relatedContact['individual_id'];
      }
    }
    else {
      $values = array_merge($values, $this->loadEventMessageTemplateParams($eventID, $participantID, $this->id));
    }

    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Contribution', NULL, $this->id);

    $customGroup = [];
    foreach ($groupTree as $key => $group) {
      if ($key === 'info') {
        continue;
      }

      foreach ($group['fields'] as $k => $customField) {
        $groupLabel = $group['title'];
        if (!empty($customField['customValue'])) {
          foreach ($customField['customValue'] as $customFieldValues) {
            $customGroup[$groupLabel][$customField['label']] = $customFieldValues['data'] ?? NULL;
          }
        }
      }
    }
    $values['customGroup'] = $customGroup;

    $values['is_pay_later'] = $this->is_pay_later;

    return $values;
  }

  /**
   * Assign message variables to template but try to break the habit.
   *
   * In order to get away from leaky variables it is better to ensure variables are set in values and assign them
   * from the send function. Otherwise smarty variables can leak if this is called more than once - e.g. processing
   * multiple recurring payments for processors like IATS that use tokens.
   *
   * Apply variables for message to smarty template - this function is part of analysing what is in the huge
   * function & breaking it down into manageable chunks. Eventually it will be refactored into something else
   * Note we send directly from this function in some cases because it is only partly refactored.
   *
   * Don't call this function directly as the signature will change.
   *
   * @param $values
   * @param $input
   * @param bool $returnMessageText
   *
   * @return mixed
   */
  public function _assignMessageVariablesToTemplate(&$values, $input, $returnMessageText = TRUE) {
    // @todo - this should have a better separation of concerns - ie.
    // gatherMessageValues should build an array of values to be assigned to the template
    // and this function should assign them (assigning null if not set).
    // the way the pcpParams & honor Params section works is a baby-step towards this.
    $template = CRM_Core_Smarty::singleton();
    $template->assign('billingName', $values['billingName']);
    // It is unclear if onBehalfProfile is still assigned & where - but
    // it is still referred to in templates so avoid an e-notice.
    // Credit card type is assigned on the form layer but should also be
    // assigned when payment.create is called....
    $template->ensureVariablesAreAssigned(['onBehalfProfile', 'credit_card_type']);

    //assign honor information to receipt message
    $softRecord = CRM_Contribute_BAO_ContributionSoft::getSoftContribution($this->id);

    $honorParams = [
      'soft_credit_type' => NULL,
      'honor_block_is_active' => NULL,
    ];
    if (isset($softRecord['soft_credit'])) {
      //if id of contribution page is present
      if (!empty($values['id'])) {
        $values['honor'] = [
          'honor_profile_values' => [],
          'honor_profile_id' => UFJoin::get(FALSE)->addWhere('entity_id', '=', $values['id'])->addWhere('entity_table', '=', 'civicrm_contribution_page')->addWhere('module', '=', 'soft_credit')->execute()->first()['uf_group_id'] ?? 0,
          'honor_id' => $softRecord['soft_credit'][1]['contact_id'],
        ];

        $honorParams['soft_credit_type'] = $softRecord['soft_credit'][1]['soft_credit_type_label'];
        $honorParams['honor_block_is_active'] = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_UFJoin', $values['id'], 'is_active', 'entity_id');
      }
      else {
        //offline contribution
        $softCreditTypes = $softCredits = [];
        foreach ($softRecord['soft_credit'] as $key => $softCredit) {
          $softCreditTypes[$key] = $softCredit['soft_credit_type_label'];
          $softCredits[$key] = [
            'Name' => $softCredit['contact_name'],
            'Amount' => CRM_Utils_Money::format($softCredit['amount'], $softCredit['currency']),
          ];
        }
      }
    }
    $template->assign('softCreditTypes', $softCreditTypes ?? NULL);
    $template->assign('softCredits', $softCredits ?? NULL);

    $dao = new CRM_Contribute_DAO_ContributionProduct();
    $dao->contribution_id = $this->id;
    if ($dao->find(TRUE)) {
      $premiumId = $dao->product_id;
      $template->assign('option', $dao->product_option);

      $productDAO = new CRM_Contribute_DAO_Product();
      $productDAO->id = $premiumId;
      $productDAO->find(TRUE);
      $template->assign('selectPremium', TRUE);
      $template->assign('product_name', $productDAO->name);
      $template->assign('price', $productDAO->price);
      $template->assign('sku', $productDAO->sku);
    }
    else {
      $template->assign('selectPremium', FALSE);
    }
    $template->assign('title', $values['title'] ?? NULL);
    $values['amount'] = $input['total_amount'] ?? $input['amount'] ?? NULL;
    if (!$values['amount'] && isset($this->total_amount)) {
      $values['amount'] = $this->total_amount;
    }

    $pcpParams = [
      'pcpBlock' => NULL,
      'pcp_display_in_roll' => NULL,
      'pcp_roll_nickname' => NULL,
      'pcp_personal_note' => NULL,
      'title' => NULL,
    ];

    if (strtolower($this->_component) === 'contribute') {
      //PCP Info
      $softDAO = new CRM_Contribute_DAO_ContributionSoft();
      $softDAO->contribution_id = $this->id;
      if ($softDAO->find(TRUE)) {
        $pcpParams['pcp_display_in_roll'] = $softDAO->pcp_display_in_roll;
        $pcpParams['pcp_roll_nickname'] = $softDAO->pcp_roll_nickname;
        $pcpParams['pcp_personal_note'] = $softDAO->pcp_personal_note;

        //assign the pcp page title for email subject
        if ($softDAO->pcp_id) {
          $pcpDAO = new CRM_PCP_DAO_PCP();
          $pcpDAO->id = $softDAO->pcp_id;
          if ($pcpDAO->find(TRUE)) {
            $pcpParams['title'] = $pcpDAO->title;

            // Only display PCP block in receipt if enabled for the PCP page
            if (!empty($pcpDAO->is_honor_roll)) {
              $pcpParams['pcpBlock'] = TRUE;
            }
          }
        }
      }
    }
    foreach (array_merge($honorParams, $pcpParams) as $templateKey => $templateValue) {
      $template->assign($templateKey, $templateValue);
    }

    if ($this->financial_type_id) {
      $values['financial_type_id'] = $this->financial_type_id;
    }

    $template->assign('trxn_id', $this->trxn_id);
    $template->assign('receive_date',
      CRM_Utils_Date::processDate($this->receive_date)
    );
    $values['receipt_date'] = (empty($this->receipt_date) ? NULL : $this->receipt_date);
    $template->assign('action', $this->is_test ? 1024 : 1);
    $template->assign('receipt_text', $values['receipt_text'] ?? NULL);
    $template->assign('is_monetary', 1);
    $template->assign('is_recur', !empty($this->contribution_recur_id));
    $template->assign('currency', $this->currency);
    $template->assign('address', CRM_Utils_Address::format($input));
    if (!empty($values['customGroup'])) {
      $template->assign('customGroup', $values['customGroup']);
    }
    if (!empty($values['softContributions'])) {
      $template->assign('softContributions', $values['softContributions']);
    }
    if ($this->_component === 'event') {
      $template->assign('title', $values['event']['title']);
      $participantRoles = CRM_Event_PseudoConstant::participantRole();
      $viewRoles = [];
      foreach (explode(CRM_Core_DAO::VALUE_SEPARATOR, $this->_relatedObjects['participant']->role_id) as $k => $v) {
        $viewRoles[] = $participantRoles[$v];
      }
      $values['event']['participant_role'] = implode(', ', $viewRoles);
      $template->assign('event', $values['event']);
      $template->assign('participant', $values['participant']);
      $template->assign('location', $values['location']);
      $template->assign('customPre', $values['custom_pre_id']);
      $template->assign('customPost', $values['custom_post_id']);

      $isTest = FALSE;
      if ($this->_relatedObjects['participant']->is_test) {
        $isTest = TRUE;
      }

      $values['params'] = [];
      //to get email of primary participant.
      $primaryEmail = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', $this->_relatedObjects['participant']->contact_id, 'email', 'contact_id');
      $primaryAmount[] = [
        'label' => $this->_relatedObjects['participant']->fee_level . ' - ' . $primaryEmail,
        'amount' => $this->_relatedObjects['participant']->fee_amount,
      ];
      $primaryParticipantID = $this->_relatedObjects['participant']->id;
      $additionalIDs = CRM_Event_BAO_Participant::getAdditionalParticipantIds($primaryParticipantID);
      //build an array of cId/pId of participants
      //send receipt to additional participant if exists
      if (count($additionalIDs)) {
        $template->assign('isPrimary', 0);
        $template->assign('customProfile', NULL);
        //set additionalParticipant true
        $values['params']['additionalParticipant'] = TRUE;
        foreach ($additionalIDs as $participantID) {
          $amount = [];
          //to change the status pending to completed
          $additional = new CRM_Event_DAO_Participant();
          $additional->id = $participantID;
          $additional->find(TRUE);
          $contactID = (int) $additional->contact_id;
          $additionalParticipantInfo = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Email', $additional->contact_id, 'email', 'contact_id');
          //if additional participant dont have email
          //use display name.
          if (!$additionalParticipantInfo) {
            $additionalParticipantInfo = CRM_Contact_BAO_Contact::displayName($contactID);
          }
          $amount[0] = [
            'label' => $additional->fee_level,
            'amount' => $additional->fee_amount,
          ];
          $primaryAmount[] = [
            'label' => $additional->fee_level . ' - ' . $additionalParticipantInfo,
            'amount' => $additional->fee_amount,
          ];
          $template->assign('amount', $amount);
          CRM_Event_BAO_Event::sendMail($contactID, $values, $participantID, $isTest, $returnMessageText);
        }
      }

      //build an array of custom profile and assigning it to template
      $customProfile = CRM_Event_BAO_Event::buildCustomProfile($this->_relatedObjects['participant']->id, $values, NULL, $isTest);

      if (count($customProfile)) {
        $template->assign('customProfile', $customProfile);
      }

      // for primary contact
      $values['params']['additionalParticipant'] = FALSE;
      $template->assign('isPrimary', 1);
      $template->assign('amount', $primaryAmount);
      $template->assign('register_date', CRM_Utils_Date::isoToMysql($this->_relatedObjects['participant']->register_date));
      if ($this->payment_instrument_id) {
        $paymentInstrument = CRM_Contribute_PseudoConstant::paymentInstrument();
        $template->assign('paidBy', $paymentInstrument[$this->payment_instrument_id]);
      }
      // carry paylater, since we did not created billing,
      // so need to pull email from primary location, CRM-4395
      $values['params']['is_pay_later'] = $this->_relatedObjects['participant']->is_pay_later;
    }
    return $template;
  }

  /**
   * Check whether payment processor supports
   * cancellation of contribution subscription
   *
   * @param int $contributionId
   *   Contribution id.
   *
   * @param bool $isNotCancelled
   *
   * @return bool
   */
  public static function isCancelSubscriptionSupported($contributionId, $isNotCancelled = TRUE) {
    $cacheKeyString = "$contributionId";
    $cacheKeyString .= $isNotCancelled ? '_1' : '_0';

    static $supportsCancel = [];

    if (!array_key_exists($cacheKeyString, $supportsCancel)) {
      $supportsCancel[$cacheKeyString] = FALSE;
      $isCancelled = FALSE;

      if ($isNotCancelled) {
        $isCancelled = self::isSubscriptionCancelled($contributionId);
      }

      $paymentObject = CRM_Financial_BAO_PaymentProcessor::getProcessorForEntity($contributionId, 'contribute', 'obj');
      if (!empty($paymentObject)) {
        $supportsCancel[$cacheKeyString] = $paymentObject->supports('cancelRecurring') && !$isCancelled;
      }
    }
    return $supportsCancel[$cacheKeyString];
  }

  /**
   * Check whether subscription is already cancelled.
   *
   * @param int $contributionId
   *   Contribution id.
   *
   * @return string
   *   contribution status
   */
  public static function isSubscriptionCancelled($contributionId) {
    $sql = "
       SELECT cr.contribution_status_id
         FROM civicrm_contribution_recur cr
    LEFT JOIN civicrm_contribution con ON ( cr.id = con.contribution_recur_id )
        WHERE con.id = %1 LIMIT 1";
    $params = [1 => [$contributionId, 'Integer']];
    $statusId = CRM_Core_DAO::singleValueQuery($sql, $params);
    $status = CRM_Core_Pseudoconstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $statusId);
    if ($status === 'Cancelled') {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Create all financial accounts entry.
   *
   * @param array $params
   *   Contribution object, line item array and params for trxn.
   * @param \CRM_Contribute_DAO_Contribution $contribution
   *
   * @return null|\CRM_Core_BAO_FinancialTrxn
   */
  public static function recordFinancialAccounts(&$params, CRM_Contribute_DAO_Contribution $contribution) {
    $skipRecords = $return = FALSE;
    $isUpdate = !empty($params['prevContribution']);

    $additionalParticipantId = [];
    $contributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $params['contribution_status_id'] ?? NULL);

    if (($params['contribution_mode'] ?? NULL) === 'participant') {
      $entityId = $params['participant_id'];
      $entityTable = 'civicrm_participant';
      $additionalParticipantId = CRM_Event_BAO_Participant::getAdditionalParticipantIds($entityId);
    }
    elseif (!empty($params['membership_id'])) {
      //so far $params['membership_id'] should only be set coming in from membershipBAO::create so the situation where multiple memberships
      // are created off one contribution should be handled elsewhere
      $entityId = $params['membership_id'];
      $entityTable = 'civicrm_membership';
    }
    else {
      $entityId = $contribution->id;
      $entityTable = 'civicrm_contribution';
    }

    $entityID[] = $entityId;
    if (!empty($additionalParticipantId)) {
      $entityID += $additionalParticipantId;
      // build line item array if necessary
      if ($additionalParticipantId) {
        CRM_Price_BAO_LineItem::getLineItemArray($params, $entityID, str_replace('civicrm_', '', $entityTable));
      }
    }
    // prevContribution appears to mean - original contribution object- ie copy of contribution from before the update started that is being updated
    if (empty($params['prevContribution'])) {
      $entityID = NULL;
    }

    $statusId = $params['contribution']->contribution_status_id;

    if ($contributionStatus !== 'Failed' &&
      !($contributionStatus === 'Pending' && !$params['contribution']->is_pay_later)
    ) {
      $skipRecords = TRUE;
      $pendingStatus = [
        'Pending',
        'In Progress',
      ];
      if (in_array($contributionStatus, $pendingStatus)) {
        $params['to_financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
          $params['financial_type_id'],
          'Accounts Receivable Account is'
        );
      }
      elseif (!empty($params['payment_processor'])) {
        $params['to_financial_account_id'] = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($params['payment_processor'], NULL, 'civicrm_payment_processor');
        $params['payment_instrument_id'] = civicrm_api3('PaymentProcessor', 'getvalue', [
          'id' => $params['payment_processor'],
          'return' => 'payment_instrument_id',
        ]);
      }
      elseif (!empty($params['payment_instrument_id'])) {
        $params['to_financial_account_id'] = CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount($params['payment_instrument_id']);
      }
      // dev/financial#160 - If this is a contribution update, also check for an existing payment_instrument_id.
      elseif ($isUpdate && $params['prevContribution']->payment_instrument_id) {
        $params['to_financial_account_id'] = CRM_Financial_BAO_EntityFinancialAccount::getInstrumentFinancialAccount((int) $params['prevContribution']->payment_instrument_id);
      }
      else {
        $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('financial_account_type', NULL, " AND v.name LIKE 'Asset' "));
        $queryParams = [1 => [$relationTypeId, 'Integer']];
        $params['to_financial_account_id'] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_account WHERE is_default = 1 AND financial_account_type_id = %1", $queryParams);
      }

      $totalAmount = $params['total_amount'] ?? NULL;
      if (!isset($totalAmount) && !empty($params['prevContribution'])) {
        $totalAmount = $params['total_amount'] = $params['prevContribution']->total_amount;
      }
      if (empty($contribution->currency)) {
        $contribution->find(TRUE);
      }
      //build financial transaction params
      $trxnParams = [
        'contribution_id' => $contribution->id,
        'to_financial_account_id' => $params['to_financial_account_id'],
        // If receive_date is not deliberately passed in we assume 'now'.
        // test testCompleteTransactionWithReceiptDateSet ensures we don't
        // default to loading the stored contribution receive_date.
        // Note that as we deprecate completetransaction in favour
        // of Payment.create handling of trxn_date will tighten up.
        'trxn_date' => $params['receive_date'] ?? date('YmdHis'),
        'total_amount' => $totalAmount,
        'fee_amount' => $params['fee_amount'] ?? NULL,
        'net_amount' => $params['net_amount'] ?? $totalAmount,
        'currency' => $contribution->currency,
        'trxn_id' => $contribution->trxn_id,
        // @todo - this is getting the status id from the contribution - that is BAD - ie the contribution could be partially
        // paid but each payment is completed. The work around is to pass in the status_id in the trxn_params but
        // this should really default to completed (after discussion).
        'status_id' => $statusId,
        'payment_instrument_id' => $params['payment_instrument_id'] ?? $params['contribution']->payment_instrument_id,
        'check_number' => $params['check_number'] ?? NULL,
        'pan_truncation' => $params['pan_truncation'] ?? NULL,
        'card_type_id' => $params['card_type_id'] ?? NULL,
      ];
      if ($contributionStatus === 'Refunded' || $contributionStatus === 'Chargeback' || $contributionStatus === 'Cancelled') {
        $trxnParams['trxn_date'] = !empty($params['contribution']->cancel_date) ? $params['contribution']->cancel_date : date('YmdHis');
        if (isset($params['refund_trxn_id'])) {
          // CRM-17751 allow a separate trxn_id for the refund to be passed in via api & form.
          $trxnParams['trxn_id'] = $params['refund_trxn_id'];
        }
      }
      //CRM-16259, set is_payment flag for non pending status
      if (!in_array($contributionStatus, $pendingStatus)) {
        $trxnParams['is_payment'] = 1;
      }
      if (!empty($params['payment_processor'])) {
        $trxnParams['payment_processor_id'] = $params['payment_processor'];
      }

      if (empty($trxnParams['payment_processor_id'])) {
        unset($trxnParams['payment_processor_id']);
      }

      $params['trxnParams'] = $trxnParams;

      if ($isUpdate) {
        $updated = FALSE;
        $params['trxnParams']['total_amount'] = $trxnParams['total_amount'] = $params['total_amount'] = $params['prevContribution']->total_amount;
        $params['trxnParams']['fee_amount'] = $params['prevContribution']->fee_amount;
        $params['trxnParams']['net_amount'] = $params['prevContribution']->net_amount;
        if (!isset($params['trxnParams']['trxn_id'])) {
          // Actually I have no idea why we are overwriting any values from the previous contribution.
          // (filling makes sense to me). However, only protecting this value as I really really know we
          // don't want this one overwritten.
          // CRM-17751.
          $params['trxnParams']['trxn_id'] = $params['prevContribution']->trxn_id;
        }
        $params['trxnParams']['status_id'] = $params['prevContribution']->contribution_status_id;
        $previousContributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $params['prevContribution']->contribution_status_id);
        if (!(($previousContributionStatus === 'Pending' || $previousContributionStatus === 'In Progress')
          && $contributionStatus === 'Completed')
        ) {
          $params['trxnParams']['payment_instrument_id'] = $params['prevContribution']->payment_instrument_id;
          $params['trxnParams']['check_number'] = $params['prevContribution']->check_number;
        }

        //if financial type is changed
        if (!empty($params['financial_type_id']) &&
          $params['contribution']->financial_type_id != $params['prevContribution']->financial_type_id
        ) {
          $accountRelationship = 'Income Account is';
          if (!empty($params['revenue_recognition_date']) || $params['prevContribution']->revenue_recognition_date) {
            $accountRelationship = 'Deferred Revenue Account is';
          }
          $oldFinancialAccount = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($params['prevContribution']->financial_type_id, $accountRelationship);
          $newFinancialAccount = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($params['financial_type_id'], $accountRelationship);
          if ($oldFinancialAccount != $newFinancialAccount) {
            $params['trxnParams']['trxn_date'] = date('YmdHis');
            $params['total_amount'] = 0;
            // If we have a fee amount set reverse this as well.
            if (isset($params['fee_amount'])) {
              $params['trxnParams']['fee_amount'] = 0 - $params['fee_amount'];
            }
            if (in_array($params['contribution']->contribution_status_id, $pendingStatus)) {
              $params['trxnParams']['to_financial_account_id'] = CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(
                $params['prevContribution']->financial_type_id, $accountRelationship);
            }
            else {
              $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($params['prevContribution']->id, 'DESC');
              if (!empty($lastFinancialTrxnId['financialTrxnId'])) {
                $params['trxnParams']['to_financial_account_id'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialTrxn', $lastFinancialTrxnId['financialTrxnId'], 'to_financial_account_id');
              }
            }
            CRM_Contribute_BAO_FinancialProcessor::updateFinancialAccounts($params, 'changeFinancialType');
            $params['skipLineItem'] = FALSE;
            foreach ($params['line_item'] as &$lineItems) {
              foreach ($lineItems as &$line) {
                $line['financial_type_id'] = $params['financial_type_id'];
              }
            }
            CRM_Core_BAO_FinancialTrxn::createDeferredTrxn($params['line_item'] ?? NULL, $params['contribution'], TRUE, 'changeFinancialType');
            /* $params['trxnParams']['to_financial_account_id'] = $trxnParams['to_financial_account_id']; */
            $params['financial_account_id'] = $newFinancialAccount;
            $params['total_amount'] = $params['trxnParams']['total_amount'] = $params['trxnParams']['net_amount'] = $trxnParams['total_amount'];
            // Set the transaction fee amount back to the original value for creating the new positive financial trxn.
            if (isset($params['fee_amount'])) {
              $params['trxnParams']['fee_amount'] = $params['fee_amount'];
            }
            CRM_Contribute_BAO_FinancialProcessor::updateFinancialAccounts($params);
            CRM_Core_BAO_FinancialTrxn::createDeferredTrxn($params['line_item'] ?? NULL, $params['contribution'], TRUE);
            $params['trxnParams']['to_financial_account_id'] = $trxnParams['to_financial_account_id'];
            $updated = TRUE;
            $params['deferred_financial_account_id'] = $newFinancialAccount;
          }
        }

        //Update contribution status
        $params['trxnParams']['status_id'] = $params['contribution']->contribution_status_id;
        if (!isset($params['refund_trxn_id'])) {
          // CRM-17751 This has previously been deliberately set. No explanation as to why one variant
          // gets preference over another so I am only 'protecting' a very specific tested flow
          // and letting natural justice take care of the rest.
          $params['trxnParams']['trxn_id'] = $params['contribution']->trxn_id;
        }
        if (!empty($params['contribution_status_id']) &&
          $params['prevContribution']->contribution_status_id != $params['contribution']->contribution_status_id
        ) {
          //Update Financial Records
          $callUpdateFinancialAccounts = CRM_Contribute_BAO_FinancialProcessor::updateFinancialAccountsOnContributionStatusChange($params);
          if ($callUpdateFinancialAccounts) {
            CRM_Contribute_BAO_FinancialProcessor::updateFinancialAccounts($params, 'changedStatus');
            CRM_Core_BAO_FinancialTrxn::createDeferredTrxn($params['line_item'] ?? NULL, $params['contribution'], TRUE, 'changedStatus');
          }
          $updated = TRUE;
        }

        // change Payment Instrument for a Completed contribution
        // first handle special case when contribution is changed from Pending to Completed status when initial payment
        // instrument is null and now new payment instrument is added along with the payment
        if (!$params['contribution']->payment_instrument_id) {
          $params['contribution']->find(TRUE);
        }
        $params['trxnParams']['payment_instrument_id'] = $params['contribution']->payment_instrument_id;
        $params['trxnParams']['check_number'] = $params['check_number'] ?? NULL;

        if (CRM_Contribute_BAO_FinancialProcessor::isPaymentInstrumentChange($params, $pendingStatus)) {
          $updated = CRM_Core_BAO_FinancialTrxn::updateFinancialAccountsOnPaymentInstrumentChange($params);
        }

        //if Change contribution amount
        $params['trxnParams']['fee_amount'] = $params['fee_amount'] ?? NULL;
        $params['trxnParams']['net_amount'] = $params['net_amount'] ?? NULL;
        $params['trxnParams']['total_amount'] = $trxnParams['total_amount'] = $params['total_amount'] = $totalAmount;
        $params['trxnParams']['trxn_id'] = $params['contribution']->trxn_id;
        if (isset($totalAmount) &&
          $totalAmount != $params['prevContribution']->total_amount
        ) {
          //Update Financial Records
          $params['trxnParams']['from_financial_account_id'] = NULL;
          CRM_Contribute_BAO_FinancialProcessor::updateFinancialAccounts($params, 'changedAmount');
          CRM_Core_BAO_FinancialTrxn::createDeferredTrxn($params['line_item'] ?? NULL, $params['contribution'], TRUE, 'changedAmount');
          $updated = TRUE;
        }

        if (!$updated) {
          // Looks like we might have a data correction update.
          // This would be a case where a transaction id has been entered but it is incorrect &
          // the person goes back in & fixes it, as opposed to a new transaction.
          // Currently the UI doesn't support multiple refunds against a single transaction & we are only supporting
          // the data fix scenario.
          // CRM-17751.
          if (isset($params['refund_trxn_id'])) {
            $refundIDs = CRM_Core_BAO_FinancialTrxn::getRefundTransactionIDs($params['id']);
            if (!empty($refundIDs['financialTrxnId']) && $refundIDs['trxn_id'] != $params['refund_trxn_id']) {
              civicrm_api3('FinancialTrxn', 'create', [
                'id' => $refundIDs['financialTrxnId'],
                'trxn_id' => $params['refund_trxn_id'],
              ]);
            }
          }
          $cardType = $params['card_type_id'] ?? NULL;
          $panTruncation = $params['pan_truncation'] ?? NULL;
          CRM_Core_BAO_FinancialTrxn::updateCreditCardDetails($params['contribution']->id, $panTruncation, $cardType);
        }
      }

      else {
        // records finanical trxn and entity financial trxn
        // also make it available as return value
        CRM_Contribute_BAO_FinancialProcessor::recordAlwaysAccountsReceivable($trxnParams, $params);
        $trxnParams['pan_truncation'] = $params['pan_truncation'] ?? NULL;
        $trxnParams['card_type_id'] = $params['card_type_id'] ?? NULL;
        $return = $financialTxn = CRM_Core_BAO_FinancialTrxn::create($trxnParams);
        $params['entity_id'] = $financialTxn->id;
      }
    }
    // record line items and financial items
    if (empty($params['skipLineItem'])) {
      CRM_Price_BAO_LineItem::processPriceSet($entityId, $params['line_item'] ?? NULL, $params['contribution'], $entityTable, $isUpdate);
    }

    // create batch entry if batch_id is passed and
    // ensure no batch entry is been made on 'Pending' or 'Failed' contribution, CRM-16611
    if (!empty($params['batch_id']) && !empty($financialTxn)) {
      $entityParams = [
        'batch_id' => $params['batch_id'],
        'entity_table' => 'civicrm_financial_trxn',
        'entity_id' => $financialTxn->id,
      ];
      CRM_Batch_BAO_EntityBatch::create($entityParams);
    }

    // when a fee is charged
    if (!empty($params['fee_amount']) && (empty($params['prevContribution']) || $params['contribution']->fee_amount != $params['prevContribution']->fee_amount) && $skipRecords) {
      CRM_Core_BAO_FinancialTrxn::recordFees($params);
    }

    if (!empty($params['prevContribution']) && $entityTable === 'civicrm_participant'
      && $params['prevContribution']->contribution_status_id != $params['contribution']->contribution_status_id
    ) {
      $eventID = CRM_Core_DAO::getFieldValue('CRM_Event_DAO_Participant', $entityId, 'event_id');
      $feeLevel = str_replace('�', '', $params['prevContribution']->amount_level);
      CRM_Event_BAO_Participant::createDiscountTrxn($eventID, $params, $feeLevel);
    }
    unset($params['line_item']);
    return $return;
  }

  /**
   * Is this contribution status a reversal.
   *
   * If so we would expect to record a negative value in the financial_trxn table.
   *
   * @param int $status_id
   *
   * @return bool
   */
  public static function isContributionStatusNegative($status_id) {
    $reversalStatuses = ['Cancelled', 'Chargeback', 'Refunded'];
    return in_array(CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $status_id), $reversalStatuses, TRUE);
  }

  /**
   * Check status validation on update of a contribution.
   *
   * @param array $oldContributionValues
   *   Previous form values before submit.
   *
   * @param array $newContributionValues
   *   The input form values.
   *
   * @throws \CRM_Core_Exception
   */
  public static function checkStatusValidation(array $oldContributionValues, array $newContributionValues): void {
    $newContributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $newContributionValues['contribution_status_id']);
    $oldContributionStatus = CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $oldContributionValues['contribution_status_id']);

    $checkStatus = [
      'Cancelled' => ['Completed', 'Refunded'],
      'Completed' => ['Cancelled', 'Refunded', 'Chargeback'],
      'Pending' => ['Cancelled', 'Completed', 'Failed', 'Partially paid'],
      'In Progress' => ['Cancelled', 'Completed', 'Failed'],
      'Refunded' => ['Cancelled', 'Completed'],
      'Partially paid' => ['Completed'],
      'Pending refund' => ['Completed', 'Refunded'],
      'Failed' => ['Pending'],
    ];
    $validNewStatues = $checkStatus[$oldContributionStatus] ?? [];

    if (!in_array($newContributionStatus, $validNewStatues, TRUE)) {
      throw new CRM_Core_Exception(ts('Cannot change contribution status from %1 to %2.', [
        1 => $oldContributionStatus,
        2 => $newContributionStatus,
      ]));
    }
  }

  /**
   * Delete contribution of contact.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-12155
   *
   * @param int $contactId
   *   Contact id.
   *
   */
  public static function deleteContactContribution($contactId) {
    $contribution = new CRM_Contribute_DAO_Contribution();
    $contribution->contact_id = $contactId;
    $contribution->find();
    while ($contribution->fetch()) {
      self::deleteContribution($contribution->id);
    }
  }

  /**
   * Legacy option getter
   *
   * @deprecated
   *
   * @param string $fieldName
   * @param string $context
   * @param array $props
   *
   * @return array|bool
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    switch ($fieldName) {
      // This field is not part of this object but the api supports it
      // TODO: Legacy APIv3 support. Could be moved to Api3 & out of the BAO.
      case 'payment_processor':
        $className = 'CRM_Contribute_BAO_ContributionPage';
        // Filter results by contribution page
        if (!empty($props['contribution_page_id'])) {
          $page = civicrm_api('contribution_page', 'getsingle', [
            'version' => 3,
            'id' => ($props['contribution_page_id']),
          ]);
          $types = (array) $page['payment_processor'] ?? 0;
          $params['condition'] = 'id IN (' . implode(',', $types) . ')';
        }
        return CRM_Core_PseudoConstant::get($className, $fieldName, $params, $context);

      // CRM-13981 This field was combined with soft_credits in 4.5 but the api still supports it
      // TODO: Legacy APIv3 support. Could be moved to Api3 & out of the BAO.
      case 'honor_type_id':
        $className = 'CRM_Contribute_BAO_ContributionSoft';
        $fieldName = 'soft_credit_type_id';
        $params['condition'] = "v.name IN ('in_honor_of','in_memory_of')";
        return CRM_Core_PseudoConstant::get($className, $fieldName, $params, $context);
    }
    return parent::buildOptions($fieldName, $context, $props);
  }

  /**
   * Pseudoconstant condition_provider for contribution_status_id field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterStatus(string $fieldName, CRM_Utils_SQL_Select $conditions, $params) {
    if (!$params['include_disabled']) {
      $conditions->where("name <> 'Template'");
    }
  }

  /**
   * Pseudoconstant condition_provider for payment_instrument_id field.
   * @see \Civi\Schema\EntityMetadataBase::getConditionFromProvider
   */
  public static function alterPaymentInstrument(string $fieldName, CRM_Utils_SQL_Select $conditions, $params) {
    if (isset($params['values']['filter'])) {
      $conditions->where('filter = #filter', ['filter' => (int) $params['values']['filter']]);
    }
  }

  /**
   * Validate financial type.
   *
   * @see https://issues.civicrm.org/jira/browse/CRM-13231
   *
   * @param int $financialTypeId
   *   Financial Type id.
   *
   * @param string $relationName
   *
   * @return array|bool
   */
  public static function validateFinancialType($financialTypeId, $relationName = 'Expense Account is') {
    $financialAccount = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($financialTypeId, $relationName);

    if (!$financialAccount) {
      return CRM_Contribute_PseudoConstant::financialType($financialTypeId);
    }
    return FALSE;
  }

  /**
   * @param int $targetCid
   * @param $activityType
   * @param string $title
   * @param int $contributionId
   * @param string $totalAmount
   * @param string $currency
   * @param string $trxn_date
   *
   * @throws \CRM_Core_Exception
   */
  public static function addActivityForPayment($targetCid, $activityType, $title, $contributionId, $totalAmount, $currency, $trxn_date) {
    $paymentAmount = CRM_Utils_Money::format($totalAmount, $currency);
    $subject = "{$paymentAmount} - {$activityType} for {$title}";
    $date = CRM_Utils_Date::isoToMysql($trxn_date);
    // source record id would be the contribution id
    $srcRecId = $contributionId;

    // activity params
    $activityParams = [
      'source_contact_id' => $targetCid,
      'source_record_id' => $srcRecId,
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', $activityType),
      'subject' => $subject,
      'activity_date_time' => $date,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_status_id', 'Completed'),
      'skipRecentView' => TRUE,
    ];

    // create activity with target contacts
    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if ($id) {
      $activityParams['source_contact_id'] = $id;
      $activityParams['target_contact_id'][] = $targetCid;
    }
    civicrm_api3('Activity', 'create', $activityParams);
  }

  /**
   * Get list of payments displayed by Contribute_Page_PaymentInfo.
   *
   * @param int $id
   * @param string $component
   * @param bool $getTrxnInfo
   *
   * @return mixed
   *
   * @throws \CRM_Core_Exception
   */
  public static function getPaymentInfo($id, $component = 'contribution', $getTrxnInfo = FALSE) {
    // @todo deprecate passing in component - always call with contribution.
    if ($component == 'event') {
      $contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantPayment', $id, 'contribution_id', 'participant_id');

      if (!$contributionId) {
        if ($primaryParticipantId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_Participant', $id, 'registered_by_id')) {
          $contributionId = CRM_Core_DAO::getFieldValue('CRM_Event_BAO_ParticipantPayment', $primaryParticipantId, 'contribution_id', 'participant_id');
          $id = $primaryParticipantId;
        }
        if (!$contributionId) {
          return;
        }
      }
    }
    elseif ($component == 'membership') {
      $contributionId = CRM_Member_BAO_MembershipPayment::getLatestContributionIDFromLineitemAndFallbackToMembershipPayment($id);
    }
    else {
      $contributionId = $id;
    }

    // The balance used to be calculated this way - we really want to remove this 'oldCalculation'
    // but need to unpick the whole trxn_id it's returning first.
    $oldCalculation = CRM_Core_BAO_FinancialTrxn::getBalanceTrxnAmt($contributionId);
    $baseTrxnId = !empty($oldCalculation['trxn_id']) ? $oldCalculation['trxn_id'] : NULL;
    if (!$baseTrxnId) {
      $baseTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contributionId);
      $baseTrxnId = $baseTrxnId['financialTrxnId'];
    }
    $total = CRM_Price_BAO_LineItem::getLineTotal($contributionId);

    $paymentBalance = CRM_Contribute_BAO_Contribution::getContributionBalance($contributionId, $total);

    $contribution = Contribution::get(FALSE)
      ->addSelect('currency', 'is_pay_later', 'contribution_status_id:name', 'financial_type_id')
      ->addWhere('id', '=', $contributionId)
      ->execute()
      ->first();

    $info['payLater'] = $contribution['is_pay_later'];
    $info['contribution_status'] = $contribution['contribution_status_id:name'];
    $info['currency'] = $contribution['currency'];

    $info['total'] = $total;
    $info['paid'] = $total - $paymentBalance;
    $info['balance'] = $paymentBalance;
    $info['id'] = $id;
    $info['component'] = $component;
    if ($getTrxnInfo && $baseTrxnId) {
      $info['transaction'] = self::getContributionTransactionInformation($contributionId, $contribution['financial_type_id']);
    }

    $info['payment_links'] = self::getContributionPaymentLinks($contributionId, $info['contribution_status']);
    return $info;
  }

  /**
   * Get the outstanding balance on a contribution.
   *
   * @param int $contributionId
   * @param float $contributionTotal
   *   Optional amount to override the saved amount paid (e.g if calculating what it WILL be).
   *
   * @return float
   * @throws \CRM_Core_Exception
   */
  public static function getContributionBalance($contributionId, $contributionTotal = NULL) {
    if ($contributionTotal === NULL) {
      $contributionTotal = CRM_Price_BAO_LineItem::getLineTotal($contributionId);
    }

    return (float) CRM_Utils_Money::subtractCurrencies(
      $contributionTotal,
      CRM_Core_BAO_FinancialTrxn::getTotalPayments($contributionId, TRUE),
      CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'currency')
    );
  }

  /**
   * Check financial type validation on update of a contribution.
   *
   * @param int $financialTypeId
   *   Value of latest Financial Type.
   *
   * @param int $contributionId
   *   Contribution Id.
   *
   * @param array $errors
   *   List of errors.
   *
   * @return void
   */
  public static function checkFinancialTypeChange($financialTypeId, $contributionId, &$errors) {
    if (!empty($financialTypeId)) {
      $oldFinancialTypeId = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $contributionId, 'financial_type_id');
      if ($oldFinancialTypeId == $financialTypeId) {
        return;
      }
    }
    $sql = 'SELECT financial_type_id FROM civicrm_line_item WHERE contribution_id = %1 GROUP BY financial_type_id;';
    $params = [
      '1' => [$contributionId, 'Integer'],
    ];
    $result = CRM_Core_DAO::executeQuery($sql, $params);
    if ($result->N > 1) {
      $errors['financial_type_id'] = ts('One or more line items have a different financial type than the contribution. Editing the financial type is not yet supported in this situation.');
    }
  }

  /**
   * Update related pledge payment payments.
   *
   * This function has been refactored out of the back office contribution form and may
   * still overlap with other functions.
   *
   * @param string $action
   * @param int $pledgePaymentID
   * @param int $contributionID
   * @param bool $adjustTotalAmount
   * @param float $total_amount
   * @param float $original_total_amount
   * @param int $contribution_status_id
   * @param int $original_contribution_status_id
   */
  public static function updateRelatedPledge(
    $action,
    $pledgePaymentID,
    $contributionID,
    $adjustTotalAmount,
    $total_amount,
    $original_total_amount,
    $contribution_status_id,
    $original_contribution_status_id
  ) {
    if (!$pledgePaymentID && $action & CRM_Core_Action::ADD && !$contributionID) {
      return;
    }

    if ($pledgePaymentID) {
      //store contribution id in payment record.
      CRM_Core_DAO::setFieldValue('CRM_Pledge_DAO_PledgePayment', $pledgePaymentID, 'contribution_id', $contributionID);
    }
    else {
      $pledgePaymentID = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment',
        $contributionID,
        'id',
        'contribution_id'
      );
    }

    if (!$pledgePaymentID) {
      return;
    }
    $pledgeID = CRM_Core_DAO::getFieldValue('CRM_Pledge_DAO_PledgePayment',
      $contributionID,
      'pledge_id',
      'contribution_id'
    );

    $updatePledgePaymentStatus = FALSE;

    // If either the status or the amount has changed we update the pledge status.
    if ($action & CRM_Core_Action::ADD) {
      $updatePledgePaymentStatus = TRUE;
    }
    elseif ($action & CRM_Core_Action::UPDATE && (($original_contribution_status_id != $contribution_status_id) ||
        ($original_total_amount != $total_amount))
    ) {
      $updatePledgePaymentStatus = TRUE;
    }

    if ($updatePledgePaymentStatus) {
      CRM_Pledge_BAO_PledgePayment::updatePledgePaymentStatus($pledgeID,
        [$pledgePaymentID],
        $contribution_status_id,
        NULL,
        $total_amount,
        $adjustTotalAmount
      );
    }
  }

  /**
   * Is there only one line item attached to the contribution.
   *
   * @param int $id
   *   Contribution ID.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function isSingleLineItem($id) {
    $lineItemCount = civicrm_api3('LineItem', 'getcount', ['contribution_id' => $id]);
    return ($lineItemCount == 1);
  }

  /**
   * Complete an order.
   *
   * @todo This function needs further simplification/cleanup. Signature may change in the future. Do not call outside of core!
   *
   * @param array $input
   * @param int $recurringContributionID
   * @param int|null $contributionID
   * @param bool $isPostPaymentCreate
   *   Is this being called from the payment.create api. If so the api has taken care of financial entities.
   *   Note that our goal is that this would only ever be called from payment.create and never handle financials (only
   *   transitioning related elements).
   * @param bool $disableActionsOnCompleteOrder Should we not do any processing for memberships,participants when completing the order.
   *   At the moment this is only passed through by the APIv4 Payment.create and this should not be relied on by extensions as will likely change.
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @internal
   */
  public static function completeOrder($input, $recurringContributionID, $contributionID, $isPostPaymentCreate = FALSE, $disableActionsOnCompleteOrder = FALSE) {
    $transaction = new CRM_Core_Transaction();

    $inputContributionWhiteList = [
      'fee_amount',
      'net_amount',
      'trxn_id',
      'check_number',
      'payment_instrument_id',
      'is_test',
      'campaign_id',
      'receive_date',
      'receipt_date',
      'contribution_status_id',
      'card_type_id',
      'pan_truncation',
    ];

    $paymentProcessorId = $input['payment_processor_id'] ?? NULL;

    $completedContributionStatusID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

    $contributionParams = array_merge([
      'contribution_status_id' => $completedContributionStatusID,
    ], array_intersect_key($input, array_fill_keys($inputContributionWhiteList, 1)
    ));

    $contributionParams['payment_processor'] = $paymentProcessorId;

    if (empty($contributionParams['payment_instrument_id']) && $paymentProcessorId) {
      $contributionParams['payment_instrument_id'] = PaymentProcessor::get(FALSE)->addWhere('id', '=', $paymentProcessorId)->addSelect('payment_instrument_id')->execute()->first()['payment_instrument_id'];
    }

    if ($recurringContributionID) {
      $contributionParams['contribution_recur_id'] = $recurringContributionID;
    }

    if ($contributionParams['contribution_status_id'] === $completedContributionStatusID && !$disableActionsOnCompleteOrder) {
      self::updateMembershipBasedOnCompletionOfContribution(
        $contributionID,
        $input['trxn_date'] ?? date('YmdHis')
      );
    }

    $participantPayments = civicrm_api3('ParticipantPayment', 'get', ['contribution_id' => $contributionID, 'return' => 'participant_id', 'sequential' => 1])['values'];
    if (!empty($participantPayments) && empty($input['IAmAHorribleNastyBeyondExcusableHackInTheCRMEventFORMTaskClassThatNeedsToBERemoved']) && !$disableActionsOnCompleteOrder) {
      foreach ($participantPayments as $participantPayment) {
        $participantParams['id'] = $participantPayment['participant_id'];
        $participantParams['status_id'] = 'Registered';
        civicrm_api3('Participant', 'create', $participantParams);
      }
    }

    $contributionParams['id'] = $contributionID;
    $contributionParams['is_post_payment_create'] = $isPostPaymentCreate;

    $contributionResult = civicrm_api3('Contribution', 'create', $contributionParams);

    $transaction->commit();
    \Civi::log()->info("Contribution {$contributionParams['id']} updated successfully");

    $contributionSoft = ContributionSoft::get(FALSE)
      ->addWhere('contribution_id', '=', $contributionID)
      ->addWhere('pcp_id', '>', 0)
      ->addSelect('*')
      ->execute()->first();
    if (!empty($contributionSoft) && ((isset($input['is_email_receipt']) && !$input['is_email_receipt']) || !isset($input['is_email_receipt']))) {
      CRM_Contribute_BAO_ContributionSoft::pcpNotifyOwner($contributionID, $contributionSoft);
    }

    if (self::isEmailReceipt($input, $contributionID, $recurringContributionID)) {
      try {
        civicrm_api3('Contribution', 'sendconfirmation', [
          'id' => $contributionID,
          'payment_processor_id' => $paymentProcessorId,
        ]);
        \Civi::log()->info("Contribution {$contributionParams['id']} Receipt sent");
      }
      catch (Exception $e) {
        \Civi::log()->warning("Contribution {$contributionParams['id']} Failed to send receipt: " . $e->getMessage());
      }
    }

    return $contributionResult;
  }

  /**
   * Send receipt from contribution.
   *
   * Do not call this directly - it is being refactored. use contribution.sendmessage api call.
   *
   * Note that the compose message part has been moved to contribution
   * In general LoadObjects is called first to get the objects but the composeMessageArray function now calls it.
   *
   * @param array $input
   *   Incoming data from Payment processor.
   * @param array $ids
   *   Related object IDs.
   * @param int $contributionID
   * @param bool $returnMessageText
   *   Should text be returned instead of sent. This.
   *   is because the function is also used to generate pdfs
   *
   * @return array
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public static function sendMail($input, $ids, $contributionID, $returnMessageText = FALSE) {
    $values = [];
    $contribution = new CRM_Contribute_BAO_Contribution();
    $contribution->id = $contributionID;
    if (!$contribution->find(TRUE)) {
      throw new CRM_Core_Exception('Contribution does not exist');
    }
    // set receipt from e-mail and name in value
    if (!$returnMessageText) {
      [$values['receipt_from_name'], $values['receipt_from_email']] = self::generateFromEmailAndName($input, $contribution);
    }
    $values['contribution_status'] = CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'contribution_status_id', $contribution->contribution_status_id);
    $return = $contribution->composeMessageArray($input, $ids, $values, $returnMessageText);
    if ((!isset($input['receipt_update']) || $input['receipt_update']) && empty($contribution->receipt_date)) {
      civicrm_api3('Contribution', 'create', [
        'receipt_date' => 'now',
        'id' => $contribution->id,
      ]);
    }
    return $return;
  }

  /**
   * Generate From email and from name in an array values
   *
   * @param array $input
   * @param \CRM_Contribute_BAO_Contribution $contribution
   *
   * @return array
   */
  public static function generateFromEmailAndName($input, $contribution) {
    // Use input value if supplied.
    if (!empty($input['receipt_from_email'])) {
      return [
        $input['receipt_from_name'] ?? '',
        $input['receipt_from_email'],
      ];
    }
    // if we are still empty see if we can use anything from a contribution page.
    $pageValues = [];
    if (!empty($contribution->contribution_page_id)) {
      $pageValues = civicrm_api3('ContributionPage', 'getsingle', ['id' => $contribution->contribution_page_id]);
    }
    // if we are still empty see if we can use anything from a contribution page.
    if (!empty($pageValues['receipt_from_email'])) {
      return [
        $pageValues['receipt_from_name'] ?? NULL,
        $pageValues['receipt_from_email'],
      ];
    }
    // If we are still empty fall back to the domain or logged in user information.
    return CRM_Core_BAO_Domain::getDefaultReceiptFrom();
  }

  /**
   * Load related memberships.
   *
   * @param array $ids
   *
   * @return array $ids
   *
   * @throws Exception
   * @deprecated since well before 5.75 will be removed around 5.99.
   *  Note some universe usages exist but may be in unused extensions.
   *
   * Note that in theory it should be possible to retrieve these from the line_item table
   * with the membership_payment table being deprecated. Attempting to do this here causes tests to fail
   * as it seems the api is not correctly linking the line items when the contribution is created in the flow
   * where the contribution is created in the API, followed by the membership (using the api) followed by the membership
   * payment. The membership payment BAO does have code to address this but it doesn't appear to be working.
   *
   * I don't know if it never worked or broke as a result of https://issues.civicrm.org/jira/browse/CRM-14918.
   *
   */
  public function loadRelatedMembershipObjects($ids = []) {
    CRM_Core_Error::deprecatedFunctionWarning('use api');
    $query = "
      SELECT membership_id
      FROM   civicrm_membership_payment
      WHERE  contribution_id = %1 ";
    $params = [1 => [$this->id, 'Integer']];
    $ids['membership'] = (array) ($ids['membership'] ?? []);

    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      if ($dao->membership_id && !in_array($dao->membership_id, $ids['membership'])) {
        $ids['membership'][$dao->membership_id] = $dao->membership_id;
      }
    }

    if (array_key_exists('membership', $ids) && is_array($ids['membership'])) {
      foreach ($ids['membership'] as $id) {
        if (!empty($id)) {
          $membership = new CRM_Member_BAO_Membership();
          $membership->id = $id;
          if (!$membership->find(TRUE)) {
            throw new Exception("Could not find membership record: $id");
          }
          $membership->join_date = CRM_Utils_Date::isoToMysql($membership->join_date);
          $membership->start_date = CRM_Utils_Date::isoToMysql($membership->start_date);
          $membership->end_date = CRM_Utils_Date::isoToMysql($membership->end_date);
          $this->_relatedObjects['membership'][$membership->id . '_' . $membership->membership_type_id] = $membership;

        }
      }
    }
    return $ids;
  }

  /**
   * Function use to store line item proportionally in in entity financial trxn table
   *
   * @param array $trxnParams
   *
   * @param int $trxnId
   *
   * @param float $contributionTotalAmount
   *
   * @throws \CRM_Core_Exception
   */
  public static function assignProportionalLineItems($trxnParams, $trxnId, $contributionTotalAmount) {
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($trxnParams['contribution_id']);
    if (!empty($lineItems)) {
      // get financial item
      [$ftIds, $taxItems] = self::getLastFinancialItemIds($trxnParams['contribution_id']);
      $entityParams = [
        'contribution_total_amount' => $contributionTotalAmount,
        'trxn_total_amount' => $trxnParams['total_amount'],
        'trxn_id' => $trxnId,
      ];
      self::createProportionalFinancialEntries($entityParams, $lineItems, $ftIds, $taxItems);
    }
  }

  /**
   * ContributionPage values were being imposed onto values.
   *
   * I have made this explicit and removed the couple (is_recur, is_pay_later) we
   * REALLY didn't want superimposed. The rest are left there in their overkill out
   * of cautiousness.
   *
   * The rationale for making this explicit is that it was a case of carefully set values being
   * seemingly randonly overwritten without much care. In general I think array randomly setting
   * variables en mass is risky.
   *
   * @param array $values
   *
   * @return array
   */
  protected function addContributionPageValuesToValuesHeavyHandedly(&$values) {
    $contributionPageValues = [];
    CRM_Contribute_BAO_ContributionPage::setValues(
      $this->contribution_page_id,
      $contributionPageValues
    );
    $valuesToCopy = [
      // These are the values that I believe to be useful.
      'id',
      'title',
      'pay_later_receipt',
      'pay_later_text',
      'receipt_from_email',
      'receipt_from_name',
      'receipt_text',
      'custom_pre_id',
      'custom_post_id',
      'honoree_profile_id',
      'onbehalf_profile_id',
      'honor_block_is_active',
      // Kinda might be - but would be on the contribution...
      'campaign_id',
      'currency',
      // Included for 'fear of regression' but can't justify any use for these....
      'intro_text',
      'payment_processor',
      'financial_type_id',
      'amount_block_is_active',
      'bcc_receipt',
      'cc_receipt',
      'created_date',
      'created_id',
      'default_amount_id',
      'end_date',
      'footer_text',
      'goal_amount',
      'initial_amount_help_text',
      'initial_amount_label',
      'intro_text',
      'is_allow_other_amount',
      'is_billing_required',
      'is_confirm_enabled',
      'is_credit_card_only',
      'is_monetary',
      'is_partial_payment',
      'is_recur_installments',
      'is_recur_interval',
      'is_share',
      'max_amount',
      'min_amount',
      'min_initial_amount',
      'recur_frequency_unit',
      'start_date',
      'thankyou_footer',
      'thankyou_text',
      'thankyou_title',

    ];
    foreach ($valuesToCopy as $valueToCopy) {
      if (isset($contributionPageValues[$valueToCopy])) {
        if ($valueToCopy === 'title') {
          $values[$valueToCopy] = CRM_Contribute_BAO_Contribution_Utils::getContributionPageTitle($this->contribution_page_id);
        }
        else {
          $values[$valueToCopy] = $contributionPageValues[$valueToCopy];
        }
      }
    }
    return $values;
  }

  /**
   * Get values of CiviContribute Settings
   * and check if its enabled or not.
   * Note: The CiviContribute settings are stored as single entry in civicrm_setting
   * in serialized form. Usually this should be stored as flat settings for each form fields
   * as per CiviCRM standards. Since this would take more effort to change the current behaviour of CiviContribute
   * settings we will live with an inconsistency because it's too hard to change for now.
   * https://github.com/civicrm/civicrm-core/pull/8562#issuecomment-227874245
   *
   * @param string $name
   *
   * @return string
   *
   * @deprecated since 5.68 will be removed around 5.74.
   */
  public static function checkContributeSettings($name) {
    CRM_Core_Error::deprecatedFunctionWarning('Use \Civi::settings()->get() with the actual setting name');
    $contributeSettings = Civi::settings()->get('contribution_invoice_settings');
    return $contributeSettings[$name] ?? NULL;
  }

  /**
   * Get the contribution as it is in the database before being updated.
   *
   * @param int $contributionID
   *
   * @return \CRM_Contribute_BAO_Contribution|null
   */
  private static function getOriginalContribution($contributionID) {
    return self::getValues(['id' => $contributionID]);
  }

  /**
   * Update the memberships associated with a contribution if it has been completed.
   *
   * Note that the way in which $memberships are loaded as objects is pretty messy & I think we could just
   * load them in this function. Code clean up would compensate for any minor performance implication.
   *
   * @param int $contributionID
   *   The Contribution ID that was Completed
   * @param string $changeDate
   *   If provided, specify an alternative date to use as "today" calculation of membership dates
   *
   * @throws \CRM_Core_Exception
   */
  public static function updateMembershipBasedOnCompletionOfContribution($contributionID, $changeDate) {
    $memberships = self::getRelatedMemberships((int) $contributionID);
    foreach ($memberships as $membership) {
      $membershipParams = [
        'id' => $membership['id'],
        'contact_id' => $membership['contact_id'],
        'is_test' => $membership['is_test'],
        'membership_type_id' => $membership['membership_type_id'],
        'membership_activity_status' => 'Completed',
        'skipStatusCal' => 1,
      ];

      $currentMembership = CRM_Member_BAO_Membership::getContactMembership(
        $membershipParams['contact_id'],
        $membershipParams['membership_type_id'],
        $membershipParams['is_test'],
        $membershipParams['id']
      );

      // CRM-8141 update the membership type with the value recorded in log when membership created/renewed
      // this picks up membership type changes during renewals
      $preChangeMembership = MembershipLog::get(FALSE)
        ->addSelect('membership_type_id')
        ->addWhere('membership_id', '=', $membershipParams['id'])
        ->addOrderBy('id', 'DESC')
        ->execute()
        ->first();
      if (!empty($preChangeMembership['membership_type_id'])) {
        $membershipParams['membership_type_id'] = $preChangeMembership['membership_type_id'];
      }
      if (empty($membership['end_date']) || (int) $membership['status_id'] !== CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending')) {
        // Passing num_terms to the api triggers date calculations, but for pending memberships these may be already calculated.
        // sigh - they should  be  consistent but removing the end date check causes test failures & maybe UI too?
        // The api assumes num_terms is a special sauce for 'is_renewal' so we need to not pass it when updating a pending to completed.
        // ... except testCompleteTransactionMembershipPriceSetTwoTerms hits this line so the above is obviously not true....
        $lineItem = LineItem::get(FALSE)
          ->addSelect('membership_num_terms')
          ->addJoin('PriceFieldValue AS price_field_value', 'LEFT')
          ->addWhere('contribution_id', '=', $contributionID)
          ->addWhere('price_field_value.membership_type_id', '=', $membershipParams['membership_type_id'])
          ->execute()
          ->first();
        // default of 1 is precautionary
        $membershipParams['num_terms'] = empty($lineItem['membership_num_terms']) ? 1 : $lineItem['membership_num_terms'];
      }
      // @todo remove all this stuff in favour of letting the api call further down handle in
      // (it is a duplication of what the api does).
      $dates = [];
      if ($currentMembership) {
        /*
         * Fixed FOR CRM-4433
         * In BAO/Membership.php(renewMembership function), we skip the extend membership date and status
         * when Contribution mode is notify and membership is for renewal )
         */
        // Test cover for this is in testRepeattransactionRenewMembershipOldMembership
        // Be afraid.
        CRM_Member_BAO_Membership::fixMembershipStatusBeforeRenew($currentMembership, $changeDate);

        // @todo - we should pass membership_type_id instead of null here but not
        // adding as not sure of testing
        $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType(
          $membershipParams['id'], $changeDate, NULL, $membershipParams['num_terms']
        );
        $dates['join_date'] = $currentMembership['join_date'];
      }
      if ('Pending' === CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membership['status_id'])) {
        $membershipParams['skipStatusCal'] = '';
      }
      else {
        //get the status for membership.
        $calcStatus = CRM_Member_BAO_MembershipStatus::getMembershipStatusByDate(
          $dates['start_date'] ?? NULL,
          $dates['end_date'] ?? NULL,
          $dates['join_date'] ?? NULL,
          'now',
         TRUE,
          $membershipParams['membership_type_id'],
          $membershipParams
        );

        unset($dates['end_date']);
        $membershipParams['status_id'] = $calcStatus['id'] ?? 'New';
      }
      // We might be renewing membership so make status override false.
      $membershipParams['is_override'] = FALSE;
      $membershipParams['status_override_end_date'] = 'null';
      $membership = civicrm_api3('Membership', 'create', $membershipParams);
      $membership = $membership['values'][$membership['id']];
      // Update activity to Completed.
      // Perhaps this should be in Membership::create? Test cover in
      // api_v3_ContributionTest.testPendingToCompleteContribution.
      $priorMembershipStatus = $memberships[$membership['id']]['status_id'] ?? NULL;
      Activity::update(FALSE)->setValues([
        'status_id:name' => 'Completed',
        'subject' => ts('Status changed from %1 to %2'), [
          1 => CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'status_id', $priorMembershipStatus),
          2 => CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'status_id', $membership['status_id']),
        ],

      ])->addWhere('source_record_id', '=', $membership['id'])
        ->addWhere('status_id:name', '=', 'Scheduled')
        ->addWhere('activity_type_id:name', 'IN', ['Membership Signup', 'Membership Renewal'])
        ->execute();
    }
  }

  /**
   * Get payment links as they relate to a contribution.
   *
   * If a payment can be made then include a payment link & if a refund is appropriate
   * then a refund link.
   *
   * @param int $id
   * @param string $contributionStatus
   *
   * @return array
   *   $actionLinks Links array containing:
   *     -url
   *     -title
   *
   * @internal - not supported for use outside of core.
   */
  public static function getContributionPaymentLinks(int $id, string $contributionStatus): array {
    if ($contributionStatus === 'Failed' || !CRM_Core_Permission::check('edit contributions')) {
      // In general the balance is the best way to determine if a payment can be added or not,
      // but not for Failed contributions, where we don't accept additional payments at the moment.
      // (in some cases the contribution is 'Pending' and only the payment is failed. In those we
      // do accept more payments agains them.
      return [];
    }
    $actionLinks = [];
    $actionLinks[] = [
      'url' => 'civicrm/payment',
      'title' => ts('Record Payment'),
      'accessKey' => '',
      'ref' => '',
      'name' => '',
      'qs' => [
        'action' => 'add',
        'reset' => 1,
        'id' => $id,
        'is_refund' => 0,
      ],
      'extra' => '',
      'weight' => 0,
    ];

    if (CRM_Core_Config::isEnabledBackOfficeCreditCardPayments()) {
      $actionLinks[] = [
        'url' => 'civicrm/payment',
        'title' => ts('Submit Credit Card payment'),
        'accessKey' => '',
        'ref' => '',
        'name' => '',
        'qs' => [
          'action' => 'add',
          'reset' => 1,
          'is_refund' => 0,
          'id' => $id,
          'mode' => 'live',
        ],
        'extra' => '',
        'weight' => 0,
      ];
    }
    if ($contributionStatus !== 'Pending') {
      $actionLinks[] = [
        'url' => 'civicrm/payment',
        'title' => ts('Record Refund'),
        'accessKey' => '',
        'ref' => '',
        'name' => '',
        'qs' => [
          'action' => 'add',
          'reset' => 1,
          'id' => $id,
          'is_refund' => 1,
        ],
        'extra' => '',
        'weight' => 0,
      ];
    }

    CRM_Utils_Hook::links('contribution.edit.action', 'Contribution', $id, $actionLinks);

    return $actionLinks;
  }

  /**
   * Get a query to determine the amount donated by the contact/s in the current financial year.
   *
   * @param array $contactIDs
   *
   * @return string
   */
  public static function getAnnualQuery($contactIDs) {
    $contactIDs = implode(',', $contactIDs);
    $config = CRM_Core_Config::singleton();
    $currentMonth = date('m');
    $currentDay = date('d');
    if (
      (int) $config->fiscalYearStart['M'] > $currentMonth ||
      (
        (int) $config->fiscalYearStart['M'] == $currentMonth &&
        (int) $config->fiscalYearStart['d'] > $currentDay
      )
    ) {
      $year = date('Y') - 1;
    }
    else {
      $year = date('Y');
    }
    $nextYear = $year + 1;

    if ($config->fiscalYearStart) {
      $newFiscalYearStart = $config->fiscalYearStart;
      if ($newFiscalYearStart['M'] < 10) {
        // This is just a clumsy way of adding padding.
        // @todo next round look for a nicer way.
        $newFiscalYearStart['M'] = '0' . $newFiscalYearStart['M'];
      }
      if ($newFiscalYearStart['d'] < 10) {
        // This is just a clumsy way of adding padding.
        // @todo next round look for a nicer way.
        $newFiscalYearStart['d'] = '0' . $newFiscalYearStart['d'];
      }
      $config->fiscalYearStart = $newFiscalYearStart;
      $monthDay = $config->fiscalYearStart['M'] . $config->fiscalYearStart['d'];
    }
    else {
      // First of January.
      $monthDay = '0101';
    }
    $startDate = "$year$monthDay";
    $endDate = "$nextYear$monthDay";
    $havingClause = 'contribution_status_id = ' . (int) CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed');

    $clauses = CRM_Contribute_BAO_Contribution::getSelectWhereClause('b');
    $clauses[] = 'b.contact_id IN (' . $contactIDs . ')';
    $clauses[] = 'b.is_test = 0';
    $clauses[] = 'b.receive_date >=' . $startDate . ' AND b.receive_date < ' . $endDate;
    $whereClauseString = implode(' AND ', $clauses);

    // See https://github.com/civicrm/civicrm-core/pull/13512 for discussion of how
    // this group by + having on contribution_status_id improves performance
    $query = '
      SELECT COUNT(*) as count,
             SUM(total_amount) as amount,
             AVG(total_amount) as average,
             currency
      FROM civicrm_contribution b
      WHERE ' . $whereClauseString . "
      GROUP BY currency, contribution_status_id
      HAVING $havingClause
      ";
    return $query;
  }

  /**
   * Assign Test Value.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter) {
    if ($fieldName === 'tax_amount') {
      $this->{$fieldName} = "0.00";
    }
    elseif ($fieldName === 'net_amount') {
      $this->{$fieldName} = '2.00';
    }
    elseif ($fieldName === 'total_amount') {
      $this->{$fieldName} = "3.00";
    }
    elseif ($fieldName === 'fee_amount') {
      $this->{$fieldName} = '1.00';
    }
    else {
      parent::assignTestValues($fieldName, $fieldDef, $counter);
    }
  }

  /**
   * Check if contribution has participant/membership payment.
   *
   * @param int $contributionId
   *   Contribution ID
   *
   * @return bool
   */
  public static function allowUpdateRevenueRecognitionDate($contributionId) {
    // get line item for contribution
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($contributionId);
    // check if line item is for membership or participant
    foreach ($lineItems as $items) {
      if ($items['entity_table'] == 'civicrm_participant') {
        $flag = FALSE;
        break;
      }
      elseif ($items['entity_table'] == 'civicrm_membership') {
        $flag = FALSE;
      }
      else {
        $flag = TRUE;
        break;
      }
    }
    return $flag;
  }

  /**
   * Retrieve Sales Tax Financial Accounts.
   *
   *
   * @return array
   *
   */
  public static function getSalesTaxFinancialAccounts() {
    $query = "SELECT cfa.id FROM civicrm_entity_financial_account ce
 INNER JOIN civicrm_financial_account cfa ON ce.financial_account_id = cfa.id
 WHERE `entity_table` = 'civicrm_financial_type' AND cfa.is_tax = 1 AND ce.account_relationship = %1 GROUP BY cfa.id";
    $accountRel = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Sales Tax Account is' "));
    $queryParams = [1 => [$accountRel, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $financialAccount = [];
    while ($dao->fetch()) {
      $financialAccount[(int) $dao->id] = (int) $dao->id;
    }
    return $financialAccount;
  }

  /**
   * Create tax entry in civicrm_entity_financial_trxn table.
   *
   * @param array $entityParams
   *
   * @param array $eftParams
   *
   * @throws \CRM_Core_Exception
   */
  public static function createProportionalEntry(array $entityParams, array $eftParams): void {
    $eftParams['amount'] = 0;
    if ($entityParams['contribution_total_amount'] != 0) {
      $eftParams['amount'] = $entityParams['line_item_amount'] * ($entityParams['trxn_total_amount'] / $entityParams['contribution_total_amount']);
    }
    // Record Entity Financial Trxn; CRM-20145
    EntityFinancialTrxn::create(FALSE)->setValues($eftParams)->execute();
  }

  /**
   * Create array of last financial item id's.
   *
   * @param int $contributionId
   *
   * @return array
   */
  public static function getLastFinancialItemIds($contributionId) {
    $sql = "SELECT fi.id, li.price_field_value_id, li.tax_amount, fi.financial_account_id
      FROM civicrm_financial_item fi
      INNER JOIN civicrm_line_item li ON li.id = fi.entity_id and fi.entity_table = 'civicrm_line_item'
      WHERE li.contribution_id = %1";
    $dao = CRM_Core_DAO::executeQuery($sql, [
      1 => [
        $contributionId,
        'Integer',
      ],
    ]);
    $ftIds = $taxItems = [];
    $salesTaxFinancialAccount = self::getSalesTaxFinancialAccounts();
    while ($dao->fetch()) {
      /* if sales tax item*/
      if (in_array($dao->financial_account_id, $salesTaxFinancialAccount)) {
        $taxItems[$dao->price_field_value_id] = [
          'financial_item_id' => $dao->id,
          'amount' => $dao->tax_amount,
        ];
      }
      else {
        $ftIds[$dao->price_field_value_id] = $dao->id;
      }
    }
    return [$ftIds, $taxItems];
  }

  /**
   * Create proportional entries in civicrm_entity_financial_trxn.
   *
   * @param array $entityParams
   *
   * @param array $lineItems
   *
   * @param array $ftIds
   *
   * @param array $taxItems
   *
   * @throws \CRM_Core_Exception
   */
  public static function createProportionalFinancialEntries($entityParams, $lineItems, $ftIds, $taxItems) {
    $eftParams = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $entityParams['trxn_id'],
    ];
    foreach ($lineItems as $key => $value) {
      if ($value['qty'] == 0) {
        continue;
      }
      $eftParams['entity_id'] = $ftIds[$value['price_field_value_id']];
      $entityParams['line_item_amount'] = $value['line_total'];
      self::createProportionalEntry($entityParams, $eftParams);
      if (array_key_exists($value['price_field_value_id'], $taxItems)) {
        $entityParams['line_item_amount'] = $taxItems[$value['price_field_value_id']]['amount'];
        $eftParams['entity_id'] = $taxItems[$value['price_field_value_id']]['financial_item_id'];
        self::createProportionalEntry($entityParams, $eftParams);
      }
    }
  }

  /**
   * Load entities related to the contribution into $this->_relatedObjects.
   *
   * @param array $ids
   *
   * @throws \CRM_Core_Exception
   *
   * @deprecated since 5.75 will be removed around 5.99.
   */
  protected function loadRelatedEntitiesByID($ids) {
    CRM_Core_Error::deprecatedFunctionWarning('use api');
    $entities = [
      'contact' => 'CRM_Contact_BAO_Contact',
      'contributionRecur' => 'CRM_Contribute_BAO_ContributionRecur',
    ];
    foreach ($entities as $entity => $bao) {
      if (!empty($ids[$entity])) {
        $this->_relatedObjects[$entity] = new $bao();
        $this->_relatedObjects[$entity]->id = $ids[$entity];
        if (!$this->_relatedObjects[$entity]->find(TRUE)) {
          throw new CRM_Core_Exception($entity . ' could not be loaded');
        }
      }
    }
  }

  /**
   * Do not use - still called from CRM_Contribute_Form_Task_PDFLetter
   *
   * This needs to be refactored out of use & deprecated out of existence.
   *
   * Get the contribution fields for $id and display labels where
   * appropriate (if the token is present).
   *
   * @deprecated
   *
   * @param int $id
   * @param array $messageToken
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public static function getContributionTokenValues($id, $messageToken) {
    if (empty($id)) {
      return [];
    }
    $result = civicrm_api3('Contribution', 'get', ['id' => $id]);
    if (!empty($messageToken['contribution'])) {
      // lab.c.o mail#46 - show labels, not values, for custom fields with option values.
      foreach ($result['values'][$id] as $fieldName => $fieldValue) {
        if (str_starts_with($fieldName, 'custom_') && array_search($fieldName, $messageToken['contribution']) !== FALSE) {
          $result['values'][$id][$fieldName] = CRM_Core_BAO_CustomField::displayValue($result['values'][$id][$fieldName], $fieldName);
        }
      }

      $pseudoFields = [
        'financial_type_id:label',
        'financial_type_id:name',
        'contribution_page_id:label',
        'contribution_page_id:name',
        'payment_instrument_id:label',
        'payment_instrument_id:name',
        'is_test:label',
        'is_pay_later:label',
        'contribution_status_id:label',
        'contribution_status_id:name',
        'is_template:label',
        'campaign_id:label',
        'campaign_id:name',
      ];
      foreach ($pseudoFields as $pseudoField) {
        $split = explode(':', $pseudoField);
        $pseudoKey = $split[1];
        $realField = $split[0];
        $fieldValue = $result['values'][$id][$realField] ?? '';
        if ($pseudoKey === 'name') {
          $fieldValue = (string) CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', $realField, $fieldValue);
        }
        if ($pseudoKey === 'label') {
          $fieldValue = (string) CRM_Core_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', $realField, $fieldValue);
        }
        $result['values'][$id][$pseudoField] = $fieldValue;
      }
    }
    return $result;
  }

  /**
   * Get invoice_number for contribution.
   *
   * @param int $contributionID
   *
   * @return string|null
   */
  public static function getInvoiceNumber(int $contributionID): ?string {
    $invoicePrefix = Civi::settings()->get('invoice_prefix');
    return $invoicePrefix ? $invoicePrefix . $contributionID : NULL;
  }

  /**
   * Load the values needed for the event message.
   *
   * @param int $eventID
   * @param int $participantID
   * @param int|null $contributionID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function loadEventMessageTemplateParams(int $eventID, int $participantID, $contributionID): array {

    $eventParams = [
      'id' => $eventID,
    ];
    $values = ['event' => []];

    CRM_Event_BAO_Event::retrieve($eventParams, $values['event']);

    // add custom fields for event
    $eventGroupTree = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, $eventID);

    $eventCustomGroup = [];
    foreach ($eventGroupTree as $key => $group) {
      if ($key === 'info') {
        continue;
      }

      foreach ($group['fields'] as $k => $customField) {
        $groupLabel = $group['title'];
        if (!empty($customField['customValue'])) {
          foreach ($customField['customValue'] as $customFieldValues) {
            $eventCustomGroup[$groupLabel][$customField['label']] = $customFieldValues['data'] ?? NULL;
          }
        }
      }
    }
    $values['event']['customGroup'] = $eventCustomGroup;

    //get participant details
    $participantParams = [
      'id' => $participantID,
    ];

    $values['participant'] = [];

    CRM_Event_BAO_Participant::getValues($participantParams, $values['participant'], $participantIds);
    // add custom fields for event
    $participantGroupTree = CRM_Core_BAO_CustomGroup::getTree('Participant', NULL, $participantID);
    $participantCustomGroup = [];
    foreach ($participantGroupTree as $key => $group) {
      if ($key === 'info') {
        continue;
      }

      foreach ($group['fields'] as $k => $customField) {
        $groupLabel = $group['title'];
        if (!empty($customField['customValue'])) {
          foreach ($customField['customValue'] as $customFieldValues) {
            $participantCustomGroup[$groupLabel][$customField['label']] = $customFieldValues['data'] ?? NULL;
          }
        }
      }
    }
    $values['participant']['customGroup'] = $participantCustomGroup;

    //get location details
    $locationParams = [
      'entity_id' => $eventID,
      'entity_table' => 'civicrm_event',
    ];
    $values['location'] = CRM_Core_BAO_Location::getValues($locationParams);

    $ufJoinParams = [
      'entity_table' => 'civicrm_event',
      'entity_id' => $eventID,
      'module' => 'CiviEvent',
    ];

    [$custom_pre_id, $custom_post_ids] = CRM_Core_BAO_UFJoin::getUFGroupIds($ufJoinParams);

    $values['custom_pre_id'] = $custom_pre_id;
    $values['custom_post_id'] = $custom_post_ids;

    // set lineItem for event contribution
    if ($contributionID) {
      $participantIds = CRM_Event_BAO_Participant::getParticipantIds($contributionID);
      if (!empty($participantIds)) {
        foreach ($participantIds as $pIDs) {
          $lineItem = CRM_Price_BAO_LineItem::getLineItems($pIDs);
          if (!CRM_Utils_System::isNull($lineItem)) {
            $values['lineItem'][] = $lineItem;
          }
        }
      }
    }
    return $values;
  }

  /**
   * Get the activity source and target contacts linked to a contribution
   *
   * @param $activityId
   *
   * @return array
   */
  private static function getActivitySourceAndTarget($activityId): array {
    $activityContactQuery = ActivityContact::get(FALSE)->setWhere([
      ['activity_id', '=', $activityId],
      ['record_type_id:name', 'IN', ['Activity Source', 'Activity Targets']],
    ])->execute();

    $sourceContactKey = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Source');
    $targetContactKey = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_ActivityContact', 'record_type_id', 'Activity Targets');

    $sourceContactId = NULL;
    $targetContactId = NULL;

    for ($i = 0; $i < $activityContactQuery->count(); $i++) {
      $record = $activityContactQuery->itemAt($i);

      if ($record['record_type_id'] === $sourceContactKey) {
        $sourceContactId = $record['contact_id'];
      }

      if ($record['record_type_id'] === $targetContactKey) {
        $targetContactId = $record['contact_id'];
      }
    }

    return [$sourceContactId, $targetContactId];
  }

  /**
   * Get the unit label with the plural option
   *
   * @param string $unit
   * @return string
   */
  public static function getUnitLabelWithPlural($unit) {
    switch ($unit) {
      case 'day':
        return ts('day(s)');

      case 'week':
        return ts('week(s)');

      case 'month':
        return ts('month(s)');

      case 'year':
        return ts('year(s)');

      default:
        throw new CRM_Core_Exception('Unknown unit: ' . $unit);
    }
  }

}
