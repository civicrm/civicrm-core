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
 * This class contains payment processor related functions.
 */
class CRM_Financial_BAO_PaymentProcessor extends CRM_Financial_DAO_PaymentProcessor {
  /**
   * Static holder for the default payment processor
   * @var object
   */
  public static $_defaultPaymentProcessor = NULL;

  /**
   * Create Payment Processor.
   *
   * @param array $params
   *   Parameters for Processor entity.
   *
   * @return CRM_Financial_DAO_PaymentProcessor
   * @throws Exception
   */
  public static function create($params) {
    // If we are creating a new PaymentProcessor and have not specified the payment instrument to use, get the default from the Payment Processor Type.
    if (empty($params['id']) && empty($params['payment_instrument_id'])) {
      $params['payment_instrument_id'] = civicrm_api3('PaymentProcessorType', 'getvalue', [
        'id' => $params['payment_processor_type_id'],
        'return' => 'payment_instrument_id',
      ]);
    }
    $processor = new CRM_Financial_DAO_PaymentProcessor();
    $processor->copyValues($params);

    if (empty($params['id'])) {
      $ppTypeDAO = new CRM_Financial_DAO_PaymentProcessorType();
      $ppTypeDAO->id = $params['payment_processor_type_id'];
      if (!$ppTypeDAO->find(TRUE)) {
        CRM_Core_Error::fatal(ts('Could not find payment processor meta information'));
      }

      // also copy meta fields from the info DAO
      $processor->is_recur = $ppTypeDAO->is_recur;
      $processor->billing_mode = $ppTypeDAO->billing_mode;
      $processor->class_name = $ppTypeDAO->class_name;
      $processor->payment_type = $ppTypeDAO->payment_type;
    }

    $processor->save();
    // CRM-11826, add entry in civicrm_entity_financial_account
    // if financial_account_id is not NULL
    if (!empty($params['financial_account_id'])) {
      $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
      $values = [
        'entity_table' => 'civicrm_payment_processor',
        'entity_id' => $processor->id,
        'account_relationship' => $relationTypeId,
        'financial_account_id' => $params['financial_account_id'],
      ];
      CRM_Financial_BAO_FinancialTypeAccount::add($values);
    }

    if (isset($params['id']) && isset($params['is_active']) && !isset($params['is_test'])) {
      // check if is_active has changed & if so update test instance is_active too.
      $test_id = self::getTestProcessorId($params['id']);
      $testDAO = new CRM_Financial_DAO_PaymentProcessor();
      $testDAO->id = $test_id;
      if ($testDAO->find(TRUE)) {
        $testDAO->is_active = $params['is_active'];
        $testDAO->save();
      }
    }

    Civi\Payment\System::singleton()->flushProcessors();
    return $processor;
  }

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * Retrieve array of allowed credit cards for this payment processor.
   * @param interger|null $paymentProcessorID id of processor.
   * @return array
   */
  public static function getCreditCards($paymentProcessorID = NULL) {
    if (!empty($paymentProcessorID)) {
      $processor = new CRM_Financial_DAO_PaymentProcessor();
      $processor->id = $paymentProcessorID;
      $processor->find(TRUE);
      $cards = json_decode($processor->accepted_credit_cards, TRUE);
      return $cards;
    }
    return [];
  }

  /**
   * Get options for a given contribution field.
   *
   * @param string $fieldName
   * @param string $context see CRM_Core_DAO::buildOptionsContext.
   * @param array $props whatever is known about this dao object.
   *
   * @return array|bool
   * @see CRM_Core_DAO::buildOptions
   *
   */
  public static function buildOptions($fieldName, $context = NULL, $props = []) {
    $params = [];
    if ($fieldName === 'financial_account_id') {
      // Pseudo-field - let's help out.
      return CRM_Core_BAO_FinancialTrxn::buildOptions('to_financial_account_id', $context, $props);
    }
    return CRM_Core_PseudoConstant::get(__CLASS__, $fieldName, $params, $context);
  }

  /**
   * Retrieve DB object based on input parameters.
   *
   * It also stores all the retrieved values in the default array.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Financial_DAO_PaymentProcessor|null
   *   object on success, null otherwise
   */
  public static function retrieve(&$params, &$defaults) {
    $paymentProcessor = new CRM_Financial_DAO_PaymentProcessor();
    $paymentProcessor->copyValues($params);
    if ($paymentProcessor->find(TRUE)) {
      CRM_Core_DAO::storeValues($paymentProcessor, $defaults);
      return $paymentProcessor;
    }
    return NULL;
  }

  /**
   * Update the is_active flag in the db.
   *
   * @param int $id
   *   Id of the database record.
   * @param bool $is_active
   *   Value we want to set the is_active field.
   *
   * @return bool
   *   true if we found and updated the object, else false
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_PaymentProcessor', $id, 'is_active', $is_active);
  }

  /**
   * Retrieve the default payment processor.
   *
   * @return CRM_Financial_DAO_PaymentProcessor|null
   *   The default payment processor object on success,
   *   null otherwise
   */
  public static function &getDefault() {
    if (self::$_defaultPaymentProcessor == NULL) {
      $params = ['is_default' => 1];
      $defaults = [];
      self::$_defaultPaymentProcessor = self::retrieve($params, $defaults);
    }
    return self::$_defaultPaymentProcessor;
  }

  /**
   * Delete payment processor.
   *
   * @param int $paymentProcessorID
   *
   * @return null
   */
  public static function del($paymentProcessorID) {
    if (!$paymentProcessorID) {
      CRM_Core_Error::fatal(ts('Invalid value passed to delete function.'));
    }

    $dao = new CRM_Financial_DAO_PaymentProcessor();
    $dao->id = $paymentProcessorID;
    if (!$dao->find(TRUE)) {
      return NULL;
    }

    $testDAO = new CRM_Financial_DAO_PaymentProcessor();
    $testDAO->name = $dao->name;
    $testDAO->is_test = 1;
    $testDAO->delete();

    $dao->delete();
    Civi\Payment\System::singleton()->flushProcessors();
  }

  /**
   * Get the payment processor details.
   *
   * This returns an array whereas Civi\Payment\System::singleton->getByID() returns an object.
   * The object is a key in the array.
   *
   * @param int $paymentProcessorID
   *   Payment processor id.
   * @param string $mode
   *   Payment mode ie test or live.
   *
   * @return array
   *   associated array with payment processor related fields
   */
  public static function getPayment($paymentProcessorID, $mode = 'based_on_id') {
    $capabilities = ($mode == 'test') ? ['TestMode'] : [];
    $processors = self::getPaymentProcessors($capabilities, [$paymentProcessorID]);
    return $processors[$paymentProcessorID];
  }

  /**
   * Given a live processor ID get the test id.
   *
   * @param int $id
   *
   * @return int
   *   Test payment processor ID.
   */
  public static function getTestProcessorId($id) {
    $liveProcessorName = civicrm_api3('payment_processor', 'getvalue', [
      'id' => $id,
      'return' => 'name',
    ]);
    return civicrm_api3('payment_processor', 'getvalue', [
      'return' => 'id',
      'name' => $liveProcessorName,
      'is_test' => 1,
      'domain_id' => CRM_Core_Config::domainID(),
    ]);
  }

  /**
   * Compare 2 payment processors to see which should go first based on is_default
   * (sort function for sortDefaultFirst)
   * @param array $processor1
   * @param array $processor2
   *
   * @return int
   */
  public static function defaultComparison($processor1, $processor2) {
    $p1 = CRM_Utils_Array::value('is_default', $processor1);
    $p2 = CRM_Utils_Array::value('is_default', $processor2);
    if ($p1 == $p2) {
      return 0;
    }
    return ($p1 > $p2) ? -1 : 1;
  }

  /**
   * Get all payment processors as an array of objects.
   *
   * @param string|NULL $mode
   * only return this mode - test|live or NULL for all
   * @param bool $reset
   * @param bool $isCurrentDomainOnly
   *   Do we only want to load payment processors associated with the current domain.
   *
   * @throws CiviCRM_API3_Exception
   * @return array
   */
  public static function getAllPaymentProcessors($mode = 'all', $reset = FALSE, $isCurrentDomainOnly = TRUE) {

    $cacheKey = 'CRM_Financial_BAO_Payment_Processor_' . $mode . '_' . $isCurrentDomainOnly . '_' . CRM_Core_Config::domainID();
    if (!$reset) {
      $processors = CRM_Utils_Cache::singleton()->get($cacheKey);
      if (!empty($processors)) {
        return $processors;
      }
    }

    $retrievalParameters = [
      'is_active' => TRUE,
      'options' => ['sort' => 'is_default DESC, name', 'limit' => 0],
      'api.payment_processor_type.getsingle' => 1,
    ];
    if ($isCurrentDomainOnly) {
      $retrievalParameters['domain_id'] = CRM_Core_Config::domainID();
    }
    if ($mode == 'test') {
      $retrievalParameters['is_test'] = 1;
    }
    elseif ($mode == 'live') {
      $retrievalParameters['is_test'] = 0;
    }

    $processors = civicrm_api3('payment_processor', 'get', $retrievalParameters);
    foreach ($processors['values'] as $processor) {
      $fieldsToProvide = [
        'id',
        'name',
        'payment_processor_type_id',
        'user_name',
        'password',
        'signature',
        'url_site',
        'url_api',
        'url_recur',
        'url_button',
        'subject',
        'class_name',
        'is_recur',
        'billing_mode',
        'is_test',
        'payment_type',
        'is_default',
      ];
      foreach ($fieldsToProvide as $field) {
        // Prevent e-notices in processor classes when not configured.
        if (!isset($processor[$field])) {
          $processors['values'][$processor['id']][$field] = NULL;
        }
      }
      $processors['values'][$processor['id']]['payment_processor_type'] = $processor['payment_processor_type'] = $processors['values'][$processor['id']]['api.payment_processor_type.getsingle']['name'];
      $processors['values'][$processor['id']]['object'] = Civi\Payment\System::singleton()->getByProcessor($processor);
    }

    // Add the pay-later pseudo-processor.
    $processors['values'][0] = [
      'object' => new CRM_Core_Payment_Manual(),
      'id' => 0,
      'payment_processor_type_id' => 0,
      // This shouldn't be required but there are still some processors hacked into core with nasty 'if's.
      'payment_processor_type' => 'Manual',
      'class_name' => 'Payment_Manual',
      'name' => 'pay_later',
      'billing_mode' => '',
      'is_default' => 0,
      'payment_instrument_id' => key(CRM_Core_OptionGroup::values('payment_instrument', FALSE, FALSE, FALSE, 'AND is_default = 1')),
      // Making this optionally recur would give lots of options -but it should
      // be a row in the payment processor table before we do that.
      'is_recur' => FALSE,
      'is_test' => FALSE,
    ];

    CRM_Utils_Cache::singleton()->set($cacheKey, $processors['values']);

    return $processors['values'];
  }

  /**
   * Get Payment processors with specified capabilities.
   * Note that both the singleton & the pseudoconstant function have caching so we don't add
   * arguably this could go on the pseudoconstant class
   *
   * @param array $capabilities
   *   capabilities of processor e.g
   *   - BackOffice
   *   - TestMode
   *   - LiveMode
   *   - FutureStartDate
   *
   * @param array|bool $ids
   *
   * @return array
   *   available processors
   */
  public static function getPaymentProcessors($capabilities = [], $ids = FALSE) {
    $testProcessors = in_array('TestMode', $capabilities) ? self::getAllPaymentProcessors('test') : [];
    if (is_array($ids)) {
      $processors = self::getAllPaymentProcessors('all', FALSE, FALSE);
      if (in_array('TestMode', $capabilities)) {
        $possibleLiveIDs = array_diff($ids, array_keys($testProcessors));
        foreach ($possibleLiveIDs as $possibleLiveID) {
          if (isset($processors[$possibleLiveID]) && ($liveProcessorName = $processors[$possibleLiveID]['name']) != FALSE) {
            foreach ($testProcessors as $index => $testProcessor) {
              if ($testProcessor['name'] == $liveProcessorName) {
                $ids[] = $testProcessor['id'];
              }
            }
          }
        }
        $processors = $testProcessors;
      }
    }
    else {
      $processors = self::getAllPaymentProcessors('all');
    }

    foreach ($processors as $index => $processor) {
      if (is_array($ids) && !in_array($processor['id'], $ids)) {
        unset($processors[$index]);
        continue;
      }
      // Invalid processors will store a null value in 'object' (e.g. if not all required config fields are present).
      // This is determined by calling when loading the processor via the $processorObject->checkConfig() function.
      if (!is_a($processor['object'], 'CRM_Core_Payment')) {
        unset($processors[$index]);
        continue;
      }
      foreach ($capabilities as $capability) {
        if (($processor['object']->supports($capability)) == FALSE) {
          unset($processors[$index]);
          continue 1;
        }
      }
    }

    return $processors;
  }

  /**
   * Is there a processor on this site with the specified capability.
   *
   * The capabilities are defined on CRM_Core_Payment and can be extended by
   * processors.
   *
   * examples are
   *  - supportsBackOffice
   *  - supportsLiveMode
   *  - supportsFutureRecurDate
   *  - supportsRecurring
   *  - supportsCancelRecurring
   *  - supportsRecurContributionsForPledges
   *
   * They are passed as array('BackOffice');
   *
   * Details of specific functions are in the docblocks on the CRM_Core_Payment class.
   *
   * @param array $capabilities
   *
   * @return bool
   */
  public static function hasPaymentProcessorSupporting($capabilities = []) {
    $capabilitiesString = implode('', $capabilities);
    if (!isset(\Civi::$statics[__CLASS__]['supported_capabilities'][$capabilitiesString])) {
      $result = self::getPaymentProcessors($capabilities);
      \Civi::$statics[__CLASS__]['supported_capabilities'][$capabilitiesString] = (!empty($result) && array_keys($result) !== [0]) ? TRUE : FALSE;
    }
    return \Civi::$statics[__CLASS__]['supported_capabilities'][$capabilitiesString];
  }

  /**
   * Retrieve payment processor id / info/ object based on component-id.
   *
   * @todo function needs revisiting. The whole 'info / obj' thing is an overload. Recommend creating new functions
   * that are entity specific as there is little shared code specific to obj or info
   *
   * Also, it does not accurately derive the processor - for a completed contribution the best place to look is in the
   * relevant financial_trxn record. For a recurring contribution it is in the contribution_recur table.
   *
   * For a membership the relevant contribution_recur should be derived & then resolved as above. The contribution page
   * is never a reliable place to look as there can be more than one configured. For a pending contribution there is
   * no way to derive the processor - but hey - what processor? it didn't go through!
   *
   * Query for membership might look something like:
   * SELECT fte.payment_processor_id
   * FROM civicrm_membership mem
   * INNER JOIN civicrm_line_item li  ON ( mem.id = li.entity_id AND li.entity_table = 'civicrm_membership')
   * INNER JOIN civicrm_contribution       con ON ( li.contribution_id = con.id )
   * LEFT JOIN civicrm_entity_financial_trxn ft ON ft.entity_id = con.id AND ft.entity_table =
   * 'civicrm_contribution'
   * LEFT JOIN civicrm_financial_trxn fte ON fte.id = ft.financial_trxn_id
   *
   * @param int $entityID
   * @param string $component
   *   Component.
   * @param string $type
   *   Type of payment information to be retrieved.
   *
   * @return int|array|object
   */
  public static function getProcessorForEntity($entityID, $component = 'contribute', $type = 'id') {
    $result = NULL;
    if (!in_array($component, [
      'membership',
      'contribute',
      'recur',
    ])
    ) {
      return $result;
    }

    if ($component == 'membership') {
      $sql = "
    SELECT cr.payment_processor_id as ppID1, cp.payment_processor as ppID2, con.is_test
      FROM civicrm_membership mem
INNER JOIN civicrm_membership_payment mp  ON ( mem.id = mp.membership_id )
INNER JOIN civicrm_contribution       con ON ( mp.contribution_id = con.id )
 LEFT JOIN civicrm_contribution_recur cr  ON ( mem.contribution_recur_id = cr.id )
 LEFT JOIN civicrm_contribution_page  cp  ON ( con.contribution_page_id  = cp.id )
     WHERE mp.membership_id = %1";
    }
    elseif ($component == 'contribute') {
      $sql = "
    SELECT cr.payment_processor_id as ppID1, cp.payment_processor as ppID2, con.is_test
      FROM civicrm_contribution       con
 LEFT JOIN civicrm_contribution_recur cr  ON ( con.contribution_recur_id = cr.id )
 LEFT JOIN civicrm_contribution_page  cp  ON ( con.contribution_page_id  = cp.id )
     WHERE con.id = %1";
    }
    elseif ($component == 'recur') {
      // @deprecated - use getPaymentProcessorForRecurringContribution.
      $sql = "
    SELECT cr.payment_processor_id as ppID1, NULL as ppID2, cr.is_test
      FROM civicrm_contribution_recur cr
     WHERE cr.id = %1";
    }

    // We are interested in a single record.
    $sql .= ' LIMIT 1';

    $params = [1 => [$entityID, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if (!$dao->fetch()) {

      return $result;

    }

    $ppID = (isset($dao->ppID1) && $dao->ppID1) ? $dao->ppID1 : (isset($dao->ppID2) ? $dao->ppID2 : NULL);
    $mode = (isset($dao->is_test) && $dao->is_test) ? 'test' : 'live';
    if (!$ppID || $type == 'id') {
      $result = $ppID;
    }
    elseif ($type == 'info') {
      $result = self::getPayment($ppID, $mode);
    }
    elseif ($type == 'obj' && is_numeric($ppID)) {
      try {
        $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', ['id' => $ppID]);
      }
      catch (API_Exception $e) {
        // Unable to load the processor because this function uses an unreliable method to derive it.
        // The function looks to load the payment processor ID from the contribution page, which
        // can support multiple processors.
      }

      $paymentProcessor['payment_processor_type'] = CRM_Core_PseudoConstant::getName('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', $paymentProcessor['payment_processor_type_id']);
      $result = Civi\Payment\System::singleton()->getByProcessor($paymentProcessor);
    }
    return $result;
  }

  /**
   * Get the payment processor associated with a recurring contribution series.
   *
   * @param int $contributionRecurID
   *
   * @return \CRM_Core_Payment
   */
  public static function getPaymentProcessorForRecurringContribution($contributionRecurID) {
    $paymentProcessorId = civicrm_api3('ContributionRecur', 'getvalue', [
      'id' => $contributionRecurID,
      'return' => 'payment_processor_id',
    ]);
    return Civi\Payment\System::singleton()->getById($paymentProcessorId);
  }

  /**
   * Get the name of the payment processor
   *
   * @param $paymentProcessorId
   *
   * @return null|string
   */
  public static function getPaymentProcessorName($paymentProcessorId) {
    try {
      $paymentProcessor = civicrm_api3('PaymentProcessor', 'getsingle', [
        'return' => ['name'],
        'id' => $paymentProcessorId,
      ]);
      return $paymentProcessor['name'];
    }
    catch (Exception $e) {
      return ts('Unknown') . ' (' . $paymentProcessorId . ')';
    }
  }

  /**
   * Generate and assign an arbitrary value to a field of a test object.
   *
   * @param string $fieldName
   * @param array $fieldDef
   * @param int $counter
   *   The globally-unique ID of the test object.
   */
  protected function assignTestValue($fieldName, &$fieldDef, $counter) {
    if ($fieldName === 'class_name') {
      $this->class_name = 'Payment_Dummy';
    }
    else {
      parent::assignTestValue($fieldName, $fieldDef, $counter);
    }
  }

  /**
   * Get the default financial account id for payment processor accounts.
   *
   * Note that there is only a 'name' field & no label field. If people customise
   * name then this won't work. This is new best-effort functionality so that's non-regressive.
   *
   * The fix for that is to add a label value to the financial account table.
   */
  public static function getDefaultFinancialAccountID() {
    return CRM_Core_PseudoConstant::getKey('CRM_Financial_DAO_EntityFinancialAccount', 'financial_account_id', 'Payment Processor Account');
  }

}
