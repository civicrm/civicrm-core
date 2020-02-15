<?php
namespace Civi\Payment;

use InvalidArgumentException;
use Civi;
use CRM_Core_PseudoConstant;

/**
 * @class
 *
 * This class provides getters and setters for arguments needed by CRM_Core_Payment methods.
 *
 * The setters know how to validate each setting that they are responsible for.
 *
 * Different methods need different settings and the concept is that by passing
 * in a property bag we can encapsulate the params needed for a particular
 * method call, rather than setting arguments for different methods on the main
 * CRM_Core_Payment object.
 *
 * This class is also supposed to help with transition away from array key naming nightmares.
 *
 */
class PropertyBag implements \ArrayAccess {
  /**
   * @var array
   * - see legacyWarning
   */
  public static $legacyWarnings = [];

  protected $props = ['default' => []];

  protected static $propMap = [
    'billingStreetAddress'        => TRUE,
    'billingSupplementalAddress1' => TRUE,
    'billingSupplementalAddress2' => TRUE,
    'billingSupplementalAddress3' => TRUE,
    'billingCity'                 => TRUE,
    'billingPostalCode'           => TRUE,
    'billingCounty'               => TRUE,
    'billingCountry'              => TRUE,
    'contactID'                   => TRUE,
    'contact_id'                  => 'contactID',
    'contributionID'              => TRUE,
    'contribution_id'             => 'contributionID',
    'contributionRecurID'         => TRUE,
    'contribution_recur_id'       => 'contributionRecurID',
    'currency'                    => TRUE,
    'currencyID'                  => 'currency',
    'description'                 => TRUE,
    'email'                       => TRUE,
    'feeAmount'                   => TRUE,
    'fee_amount'                  => 'feeAmount',
    'first_name'                  => 'firstName',
    'firstName'                   => TRUE,
    'invoiceID'                   => TRUE,
    'invoice_id'                  => 'invoiceID',
    'isBackOffice'                => TRUE,
    'is_back_office'              => 'isBackOffice',
    'isRecur'                     => TRUE,
    'is_recur'                    => 'isRecur',
    'last_name'                   => 'lastName',
    'lastName'                    => TRUE,
    'paymentToken'                => TRUE,
    'payment_token'               => 'paymentToken',
    'phone'                       => TRUE,
    'recurFrequencyInterval'      => TRUE,
    'frequency_interval'          => 'recurFrequencyInterval',
    'recurFrequencyUnit'          => TRUE,
    'frequency_unit'              => 'recurFrequencyUnit',
    'recurProcessorID'            => TRUE,
    'transactionID'               => TRUE,
    'transaction_id'              => 'transactionID',
    'trxnResultCode'              => TRUE,
  ];

  /**
   * Get the property bag.
   *
   * This allows us to swap a 'might be an array might be a property bag'
   * variable for a known PropertyBag.
   *
   * @param \Civi\Payment\PropertyBag|array $params
   *
   * @return \Civi\Payment\PropertyBag
   */
  public static function cast($params) {
    if ($params instanceof self) {
      return $params;
    }
    $propertyBag = new self();
    $propertyBag->mergeLegacyInputParams($params);
    return $propertyBag;
  }

  /**
   * Just for unit testing.
   *
   * @var string
   */
  public $lastWarning;

  /**
   * Implements ArrayAccess::offsetExists
   *
   * @param mixed $offset
   * @return bool TRUE if we have that value (on our default store)
   */
  public function offsetExists ($offset): bool {
    $prop = $this->handleLegacyPropNames($offset);
    return isset($this->props['default'][$prop]);
  }

  /**
   * Implements ArrayAccess::offsetGet
   *
   * @param mixed $offset
   * @return mixed
   */
  public function offsetGet ($offset) {
    $prop = $this->handleLegacyPropNames($offset);
    return $this->get($prop, 'default');
  }

  /**
   * Implements ArrayAccess::offsetSet
   *
   * @param mixed $offset
   * @param mixed $value
   */
  public function offsetSet($offset, $value) {
    try {
      $prop = $this->handleLegacyPropNames($offset);
    }
    catch (InvalidArgumentException $e) {
      // We need to make a lot of noise here, we're being asked to merge in
      // something we can't validate because we don't know what this property is.
      // This is fine if it's something particular to a payment processor
      // (which should be using setCustomProperty) however it could also lead to
      // things like 'my_weirly_named_contact_id'.
      $this->legacyWarning($e->getMessage() . " We have merged this in for now as a custom property. Please rewrite your code to use PropertyBag->setCustomProperty if it is a genuinely custom property, or a standardised setter like PropertyBag->setContactID for standard properties");
      $this->setCustomProperty($offset, $value, 'default');
      return;
    }

    // Coerce legacy values that were in use but shouldn't be in our new way of doing things.
    if ($prop === 'feeAmount' && $value === '') {
      // At least the following classes pass in ZLS for feeAmount.
      // CRM_Core_Payment_AuthorizeNetTest::testCreateSingleNowDated
      // CRM_Core_Payment_AuthorizeNetTest::testCreateSinglePostDated
      $value = 0;
    }

    // These lines are here (and not in try block) because the catch must only
    // catch the case when the prop is custom.
    $setter = 'set' . ucfirst($prop);
    $this->$setter($value, 'default');
  }

  /**
   * Implements ArrayAccess::offsetUnset
   *
   * @param mixed $offset
   */
  public function offsetUnset ($offset) {
    $prop = $this->handleLegacyPropNames($offset);
    unset($this->props['default'][$prop]);
  }

  /**
   * Log legacy warnings info.
   *
   * @param string $message
   */
  protected function legacyWarning($message) {
    if (empty(static::$legacyWarnings)) {
      // First time we have been called.
      register_shutdown_function([PropertyBag::class, 'writeLegacyWarnings']);
    }
    // Store warnings instead of logging immediately, as calls to Civi::log()
    // can take over half a second to work in some hosting environments.
    static::$legacyWarnings[$message] = TRUE;

    // For unit tests:
    $this->lastWarning = $message;
  }

  /**
   * Save any legacy warnings to log.
   *
   * Called as a shutdown function.
   */
  public static function writeLegacyWarnings() {
    if (!empty(static::$legacyWarnings)) {
      $message = "Civi\\Payment\\PropertyBag related deprecation warnings:\n"
        . implode("\n", array_keys(static::$legacyWarnings));
      Civi::log()->warning($message, ['civi.tag' => 'deprecated']);
    }
  }

  /**
   * @param string $prop
   * @return string canonical name.
   * @throws \InvalidArgumentException if prop name not known.
   */
  protected function handleLegacyPropNames($prop) {
    $newName = static::$propMap[$prop] ?? NULL;
    if ($newName === TRUE) {
      // Good, modern name.
      return $prop;
    }
    if ($newName === NULL) {
      throw new \InvalidArgumentException("Unknown property '$prop'.");
    }
    // Remaining case is legacy name that's been translated.
    $this->legacyWarning("We have translated '$prop' to '$newName' for you, but please update your code to use the propper setters and getters.");
    return $newName;
  }

  /**
   * Internal getter.
   *
   * @param mixed $prop Valid property name
   * @param string $label e.g. 'default'
   */
  protected function get($prop, $label) {
    if (isset($this->props['default'][$prop])) {
      return $this->props[$label][$prop];
    }
    throw new \BadMethodCallException("Property '$prop' has not been set.");
  }

  /**
   * Internal setter.
   *
   * @param mixed $prop Valid property name
   * @param string $label e.g. 'default'
   * @param mixed $value
   *
   * @return PropertyBag $this object so you can chain set setters.
   */
  protected function set($prop, $label = 'default', $value) {
    $this->props[$label][$prop] = $value;
    return $this;
  }

  /**
   * DRY code.
   */
  protected function coercePseudoConstantStringToInt(string $baoName, string $field, $input) {
    if (is_numeric($input)) {
      // We've been given a numeric ID.
      $_ = (int) $input;
    }
    elseif (is_string($input)) {
      // We've been given a named instrument.
      $_ = (int) CRM_Core_PseudoConstant::getKey($baoName, $field, $input);
    }
    else {
      throw new InvalidArgumentException("Expected an integer ID or a String name for $field.");
    }
    if (!($_ > 0)) {
      throw new InvalidArgumentException("Expected an integer greater than 0 for $field.");
    }
    return $_;
  }

  /**
   */
  public function has($prop, $label = 'default') {
    // We do NOT translate legacy prop names since only new code should be
    // using this method, and new code should be using canonical names.
    // $prop = $this->handleLegacyPropNames($prop);
    return isset($this->props[$label][$prop]);
  }

  /**
   * This is used to merge values from an array.
   * It's a transitional function and should not be used!
   *
   * @param array $data
   */
  public function mergeLegacyInputParams($data) {
    $this->legacyWarning('We have merged input params into the property bag for now but please rewrite code to not use this.');
    foreach ($data as $key => $value) {
      if ($value !== NULL && $value !== '') {
        $this->offsetSet($key, $value);
      }
    }
  }

  /**
   * Throw an exception if any of the props is unset.
   *
   * @param array $props Array of proper property names (no legacy aliases allowed).
   *
   * @return PropertyBag
   */
  public function require(array $props) {
    $missing = [];
    foreach ($props as $prop) {
      if (!isset($this->props['default'][$prop])) {
        $missing[] = $prop;
      }
    }
    if ($missing) {
      throw new \InvalidArgumentException("Required properties missing: " . implode(', ', $missing));
    }
    return $this;
  }

  // Public getters, setters.

  /**
   * Get a property by its name (but still using its getter).
   *
   * @param string $prop valid property name, like contactID
   * @param bool $allowUnset If TRUE, return the default value if the property is
   *               not set - normal behaviour would be to throw an exception.
   * @param mixed $default
   * @param string $label e.g. 'default' or 'old' or 'new'
   *
   * @return mixed
   */
  public function getter($prop, $allowUnset = FALSE, $default = NULL, $label = 'default') {

    if ((static::$propMap[$prop] ?? NULL) === TRUE) {
      // This is a standard property that will have a getter method.
      $getter = 'get' . ucfirst($prop);
      return (!$allowUnset || $this->has($prop, $label))
        ? $this->$getter($label)
        : $default;
    }

    // This is not a property name we know, but they could be requesting a
    // custom property.
    return (!$allowUnset || $this->has($prop, $label))
      ? $this->getCustomProperty($prop, $label)
      : $default;
  }

  /**
   * Set a property by its name (but still using its setter).
   *
   * @param string $prop valid property name, like contactID
   * @param mixed $value
   * @param string $label e.g. 'default' or 'old' or 'new'
   *
   * @return mixed
   */
  public function setter($prop, $value = NULL, $label = 'default') {
    if ((static::$propMap[$prop] ?? NULL) === TRUE) {
      // This is a standard property.
      $setter = 'set' . ucfirst($prop);
      return $this->$setter($value, $label);
    }
    // We don't allow using the setter for custom properties.
    throw new \BadMethodCallException("Cannot use generic setter with non-standard properties; you must use setCustomProperty for custom properties.");
  }

  /**
   * Get the monetary amount.
   */
  public function getAmount($label = 'default') {
    return $this->get('amount', $label);
  }

  /**
   * Get the monetary amount.
   */
  public function setAmount($value, $label = 'default') {
    if (!is_numeric($value)) {
      throw new \InvalidArgumentException("setAmount requires a numeric amount value");
    }

    return $this->set('amount', CRM_Utils_Money::format($value, NULL, NULL, TRUE), $label);
  }

  /**
   * BillingStreetAddress getter.
   *
   * @return string
   */
  public function getBillingStreetAddress($label = 'default') {
    return $this->get('billingStreetAddress', $label);
  }

  /**
   * BillingStreetAddress setter.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setBillingStreetAddress($input, $label = 'default') {
    return $this->set('billingStreetAddress', $label, (string) $input);
  }

  /**
   * BillingSupplementalAddress1 getter.
   *
   * @return string
   */
  public function getBillingSupplementalAddress1($label = 'default') {
    return $this->get('billingSupplementalAddress1', $label);
  }

  /**
   * BillingSupplementalAddress1 setter.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setBillingSupplementalAddress1($input, $label = 'default') {
    return $this->set('billingSupplementalAddress1', $label, (string) $input);
  }

  /**
   * BillingSupplementalAddress2 getter.
   *
   * @return string
   */
  public function getBillingSupplementalAddress2($label = 'default') {
    return $this->get('billingSupplementalAddress2', $label);
  }

  /**
   * BillingSupplementalAddress2 setter.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setBillingSupplementalAddress2($input, $label = 'default') {
    return $this->set('billingSupplementalAddress2', $label, (string) $input);
  }

  /**
   * BillingSupplementalAddress3 getter.
   *
   * @return string
   */
  public function getBillingSupplementalAddress3($label = 'default') {
    return $this->get('billingSupplementalAddress3', $label);
  }

  /**
   * BillingSupplementalAddress3 setter.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setBillingSupplementalAddress3($input, $label = 'default') {
    return $this->set('billingSupplementalAddress3', $label, (string) $input);
  }

  /**
   * BillingCity getter.
   *
   * @return string
   */
  public function getBillingCity($label = 'default') {
    return $this->get('billingCity', $label);
  }

  /**
   * BillingCity setter.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setBillingCity($input, $label = 'default') {
    return $this->set('billingCity', $label, (string) $input);
  }

  /**
   * BillingPostalCode getter.
   *
   * @return string
   */
  public function getBillingPostalCode($label = 'default') {
    return $this->get('billingPostalCode', $label);
  }

  /**
   * BillingPostalCode setter.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setBillingPostalCode($input, $label = 'default') {
    return $this->set('billingPostalCode', $label, (string) $input);
  }

  /**
   * BillingCounty getter.
   *
   * @return string
   */
  public function getBillingCounty($label = 'default') {
    return $this->get('billingCounty', $label);
  }

  /**
   * BillingCounty setter.
   *
   * Nb. we can't validate this unless we have the country ID too, so we don't.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setBillingCounty($input, $label = 'default') {
    return $this->set('billingCounty', $label, (string) $input);
  }

  /**
   * BillingCountry getter.
   *
   * @return string
   */
  public function getBillingCountry($label = 'default') {
    return $this->get('billingCountry', $label);
  }

  /**
   * BillingCountry setter.
   *
   * Nb. We require and we store a 2 character country code.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setBillingCountry($input, $label = 'default') {
    if (!is_string($input) || strlen($input) !== 2) {
      throw new \InvalidArgumentException("setBillingCountry expects ISO 3166-1 alpha-2 country code.");
    }
    if (!CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'country_id', $input)) {
      throw new \InvalidArgumentException("setBillingCountry expects ISO 3166-1 alpha-2 country code.");
    }
    return $this->set('billingCountry', $label, (string) $input);
  }

  /**
   * @return int
   */
  public function getContactID($label = 'default'): int {
    return $this->get('contactID', $label);
  }

  /**
   * @param int $contactID
   * @param string $label
   */
  public function setContactID($contactID, $label = 'default') {
    // We don't use this because it counts zero as positive: CRM_Utils_Type::validate($contactID, 'Positive');
    if (!($contactID > 0)) {
      throw new InvalidArgumentException("ContactID must be a positive integer");
    }

    return $this->set('contactID', $label, (int) $contactID);
  }

  /**
   * Getter for contributionID.
   *
   * @return int|null
   * @param string $label
   */
  public function getContributionID($label = 'default') {
    return $this->get('contributionID', $label);
  }

  /**
   * @param int $contributionID
   * @param string $label e.g. 'default'
   */
  public function setContributionID($contributionID, $label = 'default') {
    // We don't use this because it counts zero as positive: CRM_Utils_Type::validate($contactID, 'Positive');
    if (!($contributionID > 0)) {
      throw new InvalidArgumentException("ContributionID must be a positive integer");
    }

    return $this->set('contributionID', $label, (int) $contributionID);
  }

  /**
   * Getter for contributionRecurID.
   *
   * @return int|null
   * @param string $label
   */
  public function getContributionRecurID($label = 'default') {
    return $this->get('contributionRecurID', $label);
  }

  /**
   * @param int $contributionRecurID
   * @param string $label e.g. 'default'
   */
  public function setContributionRecurID($contributionRecurID, $label = 'default') {
    // We don't use this because it counts zero as positive: CRM_Utils_Type::validate($contactID, 'Positive');
    if (!($contributionRecurID > 0)) {
      throw new InvalidArgumentException("ContributionRecurID must be a positive integer");
    }

    return $this->set('contributionRecurID', $label, (int) $contributionRecurID);
  }

  /**
   * Three character currency code.
   *
   * https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
   *
   * @param string $label e.g. 'default'
   */
  public function getCurrency($label = 'default') {
    return $this->get('currency', $label);
  }

  /**
   * Three character currency code.
   *
   * @param string $value
   * @param string $label e.g. 'default'
   */
  public function setCurrency($value, $label = 'default') {
    if (!preg_match('/^[A-Z]{3}$/', $value)) {
      throw new \InvalidArgumentException("Attemted to setCurrency with a value that was not an ISO 3166-1 alpha 3 currency code");
    }
    return $this->set('currency', $label, $value);
  }

  /**
   *
   * @param string $label
   *
   * @return string
   */
  public function getDescription($label = 'default') {
    return $this->get('description', $label);
  }

  /**
   * @param string $description
   * @param string $label e.g. 'default'
   */
  public function setDescription($description, $label = 'default') {
    // @todo this logic was copied from a commit that then got deleted. Is it good?
    $uninformativeStrings = [
      ts('Online Event Registration: '),
      ts('Online Contribution: '),
    ];
    $cleanedDescription = str_replace($uninformativeStrings, '', $description);
    return $this->set('description', $label, $cleanedDescription);
  }

  /**
   * Email getter.
   *
   * @return string
   */
  public function getEmail($label = 'default') {
    return $this->get('email', $label);
  }

  /**
   * Email setter.
   *
   * @param string $email
   * @param string $label e.g. 'default'
   */
  public function setEmail($email, $label = 'default') {
    return $this->set('email', $label, (string) $email);
  }

  /**
   * Amount of money charged in fees by the payment processor.
   *
   * This is notified by (some) payment processers.
   *
   * @param string $label
   *
   * @return float
   */
  public function getFeeAmount($label = 'default') {
    return $this->get('feeAmount', $label);
  }

  /**
   * @param string $feeAmount
   * @param string $label e.g. 'default'
   */
  public function setFeeAmount($feeAmount, $label = 'default') {
    if (!is_numeric($feeAmount)) {
      throw new \InvalidArgumentException("feeAmount must be a number.");
    }
    return $this->set('feeAmount', $label, (float) $feeAmount);
  }

  /**
   * First name
   *
   * @return string
   */
  public function getFirstName($label = 'default') {
    return $this->get('firstName', $label);
  }

  /**
   * First name setter.
   *
   * @param string $firstName
   * @param string $label e.g. 'default'
   */
  public function setFirstName($firstName, $label = 'default') {
    return $this->set('firstName', $label, (string) $firstName);
  }

  /**
   * Getter for invoiceID.
   *
   * @param string $label
   *
   * @return string|null
   */
  public function getInvoiceID($label = 'default') {
    return $this->get('invoiceID', $label);
  }

  /**
   * @param string $invoiceID
   * @param string $label e.g. 'default'
   */
  public function setInvoiceID($invoiceID, $label = 'default') {
    return $this->set('invoiceID', $label, $invoiceID);
  }

  /**
   * Getter for isBackOffice.
   *
   * @param string $label
   *
   * @return bool|null
   */
  public function getIsBackOffice($label = 'default'):bool {
    // @todo should this return FALSE instead of exception to keep current situation?
    return $this->get('isBackOffice', $label);
  }

  /**
   * @param bool $isBackOffice
   * @param string $label e.g. 'default'
   */
  public function setIsBackOffice($isBackOffice, $label = 'default') {
    if (is_null($isBackOffice)) {
      throw new \InvalidArgumentException("isBackOffice must be a bool, received NULL.");
    }
    return $this->set('isBackOffice', $label, (bool) $isBackOffice);
  }

  /**
   * Getter for isRecur.
   *
   * @param string $label
   *
   * @return bool|null
   */
  public function getIsRecur($label = 'default'):bool {
    return $this->get('isRecur', $label);
  }

  /**
   * @param bool $isRecur
   * @param string $label e.g. 'default'
   */
  public function setIsRecur($isRecur, $label = 'default') {
    if (is_null($isRecur)) {
      throw new \InvalidArgumentException("isRecur must be a bool, received NULL.");
    }
    return $this->set('isRecur', $label, (bool) $isRecur);
  }

  /**
   * Last name
   *
   * @return string
   */
  public function getLastName($label = 'default') {
    return $this->get('lastName', $label);
  }

  /**
   * Last name setter.
   *
   * @param string $lastName
   * @param string $label e.g. 'default'
   */
  public function setLastName($lastName, $label = 'default') {
    return $this->set('lastName', $label, (string) $lastName);
  }

  /**
   * Getter for payment processor generated string for charging.
   *
   * A payment token could be a single use token (e.g generated by
   * a client side script) or a token that permits recurring or on demand charging.
   *
   * The key thing is it is passed to the processor in lieu of card details to
   * initiate a payment.
   *
   * Generally if a processor is going to pass in a payment token generated through
   * javascript it would add 'payment_token' to the array it returns in it's
   * implementation of getPaymentFormFields. This will add a hidden 'payment_token' field to
   * the form. A good example is client side encryption where credit card details are replaced by
   * an encrypted token using a gateway provided javascript script. In this case the javascript will
   * remove the credit card details from the form before submitting and populate the payment_token field.
   *
   * A more complex example is used by paypal checkout where the payment token is generated
   * via a pre-approval process. In that case the doPreApproval function is called on the processor
   * class to get information to then interact with paypal via js, finally getting a payment token.
   * (at this stage the pre-approve api is not in core but that is likely to change - we just need
   * to think about the permissions since we don't want to expose to anonymous user without thinking
   * through any risk of credit-card testing using it.
   *
   * If the token is not totally transient it would be saved to civicrm_payment_token.token.
   *
   * @param string $label
   *
   * @return string|null
   */
  public function getPaymentToken($label = 'default') {
    return $this->get('paymentToken', $label);
  }

  /**
   * @param string $paymentToken
   * @param string $label e.g. 'default'
   */
  public function setPaymentToken($paymentToken, $label = 'default') {
    return $this->set('paymentToken', $label, $paymentToken);
  }

  /**
   * Phone getter.
   *
   * @return string
   */
  public function getPhone($label = 'default') {
    return $this->get('phone', $label);
  }

  /**
   * Phone setter.
   *
   * @param string $phone
   * @param string $label e.g. 'default'
   */
  public function setPhone($phone, $label = 'default') {
    return $this->set('phone', $label, (string) $phone);
  }

  /**
   * Combined with recurFrequencyUnit this gives how often billing will take place.
   *
   * e.g every if this is 1 and recurFrequencyUnit is 'month' then it is every 1 month.
   * @return int|null
   */
  public function getRecurFrequencyInterval($label = 'default') {
    return $this->get('recurFrequencyInterval', $label);
  }

  /**
   * @param int $recurFrequencyInterval
   * @param string $label e.g. 'default'
   */
  public function setRecurFrequencyInterval($recurFrequencyInterval, $label = 'default') {
    if (!($recurFrequencyInterval > 0)) {
      throw new InvalidArgumentException("recurFrequencyInterval must be a positive integer");
    }

    return $this->set('recurFrequencyInterval', $label, (int) $recurFrequencyInterval);
  }

  /**
   * Getter for recurFrequencyUnit.
   * Combined with recurFrequencyInterval this gives how often billing will take place.
   *
   * e.g every if this is 'month' and recurFrequencyInterval is 1 then it is every 1 month.
   *
   *
   * @param string $label
   *
   * @return string month|day|year
   */
  public function getRecurFrequencyUnit($label = 'default') {
    return $this->get('recurFrequencyUnit', $label);
  }

  /**
   * @param string $recurFrequencyUnit month|day|week|year
   * @param string $label e.g. 'default'
   */
  public function setRecurFrequencyUnit($recurFrequencyUnit, $label = 'default') {
    if (!preg_match('/^day|week|month|year$/', $recurFrequencyUnit)) {
      throw new \InvalidArgumentException("recurFrequencyUnit must be day|week|month|year");
    }
    return $this->set('recurFrequencyUnit', $label, $recurFrequencyUnit);
  }

  /**
   * Set the unique payment processor service provided ID for a particular subscription.
   *
   * Nb. this is stored in civicrm_contribution_recur.processor_id and is NOT
   * in any way related to the payment processor ID.
   *
   * @return string
   */
  public function getRecurProcessorID($label = 'default') {
    return $this->get('recurProcessorID', $label);
  }

  /**
   * Set the unique payment processor service provided ID for a particular
   * subscription.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setRecurProcessorID($input, $label = 'default') {
    if (empty($input) || strlen($input) > 255) {
      throw new \InvalidArgumentException("processorID field has max length of 255");
    }
    return $this->set('recurProcessorID', $label, $input);
  }

  /**
   * Getter for payment processor generated string for the transaction ID.
   *
   * Note some gateways generate a reference for the order and one for the
   * payment. This is for the payment reference and is saved to
   * civicrm_financial_trxn.trxn_id.
   *
   * @param string $label
   *
   * @return string|null
   */
  public function getTransactionID($label = 'default') {
    return $this->get('transactionID', $label);
  }

  /**
   * @param string $transactionID
   * @param string $label e.g. 'default'
   */
  public function setTransactionID($transactionID, $label = 'default') {
    return $this->set('transactionID', $label, $transactionID);
  }

  /**
   * Getter for trxnResultCode.
   *
   * Additional information returned by the payment processor regarding the
   * payment outcome.
   *
   * This would normally be saved in civicrm_financial_trxn.trxn_result_code.
   *
   *
   * @param string $label
   *
   * @return string|null
   */
  public function getTrxnResultCode($label = 'default') {
    return $this->get('trxnResultCode', $label);
  }

  /**
   * @param string $trxnResultCode
   * @param string $label e.g. 'default'
   */
  public function setTrxnResultCode($trxnResultCode, $label = 'default') {
    return $this->set('trxnResultCode', $label, $trxnResultCode);
  }

  // Custom Properties.

  /**
   * Sometimes we may need to pass in things that are specific to the Payment
   * Processor.
   *
   * @param string $prop
   * @param string $label e.g. 'default' or 'old' or 'new'
   * @return mixed
   *
   * @throws InvalidArgumentException if trying to use this against a non-custom property.
   */
  public function getCustomProperty($prop, $label = 'default') {
    if (isset(static::$propMap[$prop])) {
      throw new \InvalidArgumentException("Attempted to get '$prop' via getCustomProperty - must use using its getter.");
    }
    return $this->props[$label][$prop] ?? NULL;
  }

  /**
   * We have to leave validation to the processor, but we can still give them a
   * way to store their data on this PropertyBag
   *
   * @param string $prop
   * @param mixed $value
   * @param string $label e.g. 'default' or 'old' or 'new'
   *
   * @throws InvalidArgumentException if trying to use this against a non-custom property.
   */
  public function setCustomProperty($prop, $value, $label = 'default') {
    if (isset(static::$propMap[$prop])) {
      throw new \InvalidArgumentException("Attempted to set '$prop' via setCustomProperty - must use using its setter.");
    }
    $this->props[$label][$prop] = $value;
  }

}
