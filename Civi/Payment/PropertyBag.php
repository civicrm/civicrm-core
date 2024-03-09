<?php
namespace Civi\Payment;

use InvalidArgumentException;
use CRM_Core_Error;
use CRM_Core_PseudoConstant;
use Civi\Api4\Country;

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

  protected $props = ['default' => []];

  protected static $propMap = [
    'amount'                      => TRUE,
    'billingStreetAddress'        => TRUE,
    'billing_street_address'      => 'billingStreetAddress',
    'street_address'              => 'billingStreetAddress',
    'billingSupplementalAddress1' => TRUE,
    'billingSupplementalAddress2' => TRUE,
    'billingSupplementalAddress3' => TRUE,
    'billingCity'                 => TRUE,
    'billing_city'                => 'billingCity',
    'city'                        => 'billingCity',
    'billingPostalCode'           => TRUE,
    'billing_postal_code'         => 'billingPostalCode',
    'postal_code'                 => 'billingPostalCode',
    'billingCounty'               => TRUE,
    'billingStateProvince'        => TRUE,
    'billing_state_province'      => 'billingStateProvince',
    'state_province'              => 'billingStateProvince',
    'billingCountry'              => TRUE,
    'billing_country'             => 'billingCountry',
    'country'                     => 'billingCountry',
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
    'recurInstallments'           => TRUE,
    'installments'                => 'recurInstallments',
    'subscriptionId'              => 'recurProcessorID',
    'recurProcessorID'            => TRUE,
    'transactionID'               => TRUE,
    'transaction_id'              => 'transactionID',
    'trxn_id'                     => 'transactionID',
    'trxnResultCode'              => TRUE,
    'isNotifyProcessorOnCancelRecur' => TRUE,
  ];

  /**
   * For unit tests only.
   *
   * @var array
   */
  public $logs = [];

  /**
   * For unit tests only. Set to the name of a function, e.g. setBillingCountry
   * to suppress calling CRM_Core_Error::deprecatedWarning which will break tests.
   * Useful when a test is testing THAT a deprecatedWarning is thrown.
   *
   * @var string
   */
  public $ignoreDeprecatedWarningsInFunction = '';

  /**
   * @var bool
   * Temporary, internal variable to help ease transition to PropertyBag.
   * Used by cast() to suppress legacy warnings.
   * For paymentprocessors that have not converted to propertyBag we need to support "legacy" properties - eg. "is_recur"
   *   without warnings. Setting this allows us to pass a propertyBag into doPayment() and expect it to "work" with
   *   existing payment processors.
   */
  protected $suppressLegacyWarnings = TRUE;

  /**
   * Get the value of the suppressLegacyWarnings parameter
   * @return bool
   */
  public function getSuppressLegacyWarnings() {
    return $this->suppressLegacyWarnings;
  }

  /**
   * Set the suppressLegacyWarnings parameter - useful for unit tests.
   * Eg. you could set to FALSE for unit tests on a paymentprocessor to capture use of legacy keys in that processor
   * code.
   * @param bool $suppressLegacyWarnings
   */
  public function setSuppressLegacyWarnings(bool $suppressLegacyWarnings) {
    $this->suppressLegacyWarnings = $suppressLegacyWarnings;
  }

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
    $prop = $this->handleLegacyPropNames($offset, TRUE);
    // If there's no prop, assume it's a custom property.
    $prop ??= $offset;
    return array_key_exists($prop, $this->props['default']);
  }

  /**
   * Implements ArrayAccess::offsetGet
   *
   * @param mixed $offset
   * @return mixed
   */
  #[\ReturnTypeWillChange]
  public function offsetGet($offset) {
    try {
      $prop = $this->handleLegacyPropNames($offset);
    }
    catch (InvalidArgumentException $e) {

      if (!$this->getSuppressLegacyWarnings()) {
        CRM_Core_Error::deprecatedFunctionWarning(
          "proper getCustomProperty('$offset') for non-core properties. "
          . $e->getMessage(),
          "PropertyBag array access to get '$offset'"
        );
      }

      try {
        return $this->getCustomProperty($offset, 'default');
      }
      catch (BadMethodCallException $e) {
        CRM_Core_Error::deprecatedFunctionWarning(
          "proper setCustomProperty('$offset', \$value) to store the value (since it is not a core value), then access it with getCustomProperty('$offset'). NULL is returned but in future an exception will be thrown."
          . $e->getMessage(),
          "PropertyBag array access to get unset property '$offset'"
        );
        return NULL;
      }
    }

    if (!$this->getSuppressLegacyWarnings()) {
      CRM_Core_Error::deprecatedFunctionWarning(
        "get" . ucfirst($offset) . "()",
        "PropertyBag array access for core property '$offset'"
      );
    }
    return $this->get($prop, 'default');
  }

  /**
   * Implements ArrayAccess::offsetSet
   *
   * @param mixed $offset
   * @param mixed $value
   */
  public function offsetSet($offset, $value): void {
    try {
      $prop = $this->handleLegacyPropNames($offset);
    }
    catch (InvalidArgumentException $e) {
      // We need to make a lot of noise here, we're being asked to merge in
      // something we can't validate because we don't know what this property is.
      // This is fine if it's something particular to a payment processor
      // (which should be using setCustomProperty) however it could also lead to
      // things like 'my_weirly_named_contact_id'.
      //
      // From 5.28 we suppress this when using PropertyBag::cast() to ease transition.
      if (!$this->suppressLegacyWarnings) {
        CRM_Core_Error::deprecatedFunctionWarning(
          "proper setCustomProperty('$offset', \$value) for non-core properties. "
          . $e->getMessage(),
          "PropertyBag array access to set '$offset'"
        );
      }
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
    if (!$this->suppressLegacyWarnings) {
      CRM_Core_Error::deprecatedFunctionWarning(
        "$setter()",
        "PropertyBag array access to set core property '$offset'"
      );
    }
    $this->$setter($value, 'default');
  }

  /**
   * Implements ArrayAccess::offsetUnset
   *
   * @param mixed $offset
   */
  public function offsetUnset ($offset): void {
    $prop = $this->handleLegacyPropNames($offset);
    unset($this->props['default'][$prop]);
  }

  /**
   * @param string $prop
   * @param bool $silent if TRUE return NULL instead of throwing an exception. This is because offsetExists should be safe and not throw exceptions.
   * @return string canonical name.
   * @throws \InvalidArgumentException if prop name not known.
   */
  protected function handleLegacyPropNames($prop, $silent = FALSE) {
    $newName = static::$propMap[$prop] ?? NULL;
    if ($newName === TRUE) {
      // Good, modern name.
      return $prop;
    }
    // Handling for legacy addition of billing details.
    if ($newName === NULL && substr($prop, -2) === '-' . \CRM_Core_BAO_LocationType::getBilling()
      && isset(static::$propMap[substr($prop, 0, -2)])
    ) {
      $billingAddressProp = substr($prop, 0, -2);
      $newName = static::$propMap[$billingAddressProp] ?? NULL;
      if ($newName === TRUE) {
        // Good, modern name.
        return $billingAddressProp;
      }
    }

    if ($newName === NULL) {
      if ($silent) {
        // Only for use by offsetExists
        return NULL;
      }
      throw new \InvalidArgumentException("Unknown property '$prop'.");
    }
    // Remaining case is legacy name that's been translated.
    if (!$this->getSuppressLegacyWarnings()) {
      CRM_Core_Error::deprecatedFunctionWarning("Canonical property name '$newName'", "Legacy property name '$prop'");
    }

    return $newName;
  }

  /**
   * Internal getter.
   *
   * @param mixed $prop Valid property name
   * @param string $label e.g. 'default'
   *
   * @return mixed
   */
  protected function get($prop, $label) {
    if (array_key_exists($prop, $this->props[$label] ?? [])) {
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
  protected function set($prop, $label, $value) {
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
   * It's a transitional, internal function and should not be used!
   *
   * @param array $data
   */
  public function mergeLegacyInputParams($data) {
    // Suppress legacy warnings for merging an array of data as this
    // suits our migration plan at this moment. Future behaviour may differ.
    // @see https://github.com/civicrm/civicrm-core/pull/17643
    $suppressLegacyWarnings = $this->getSuppressLegacyWarnings();
    $this->setSuppressLegacyWarnings(TRUE);
    foreach ($data as $key => $value) {
      if ($value !== NULL && $value !== '') {
        $this->offsetSet($key, $value);
      }
    }
    $this->setSuppressLegacyWarnings($suppressLegacyWarnings);
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
   * Set the monetary amount.
   *
   * - We expect to be called with a string amount with optional decimals using
   *   a '.' as the decimal point (not a ',').
   *
   * - We're ok with floats/ints being passed in, too, but we'll cast them to a
   *   string.
   *
   * - Negatives are fine.
   *
   * @see https://github.com/civicrm/civicrm-core/pull/18219
   *
   * @param string|float|int $value
   * @param string $label
   */
  public function setAmount($value, $label = 'default') {
    if (!is_numeric($value)) {
      throw new \InvalidArgumentException("setAmount requires a numeric amount value");
    }
    return $this->set('amount', $label, filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
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
   *
   * @return \Civi\Payment\PropertyBag
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
   * BillingStateProvince getter.
   *
   * @return string
   */
  public function getBillingStateProvince($label = 'default') {
    return $this->get('billingStateProvince', $label);
  }

  /**
   * BillingStateProvince setter.
   *
   * Nb. we can't validate this unless we have the country ID too, so we don't.
   *
   * @param string $input
   * @param string $label e.g. 'default'
   */
  public function setBillingStateProvince($input, $label = 'default') {
    return $this->set('billingStateProvince', $label, (string) $input);
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
    $warnings = [];
    $munged = $input;
    if (!is_string($input)) {
      $warnings[] = 'Expected string';
    }
    else {
      if (!(strlen($input) === 2 && CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_Address', 'country_id', $input))) {
        $warnings[] = 'Not ISO 3166-1 alpha-2 code.';
      }
    }

    if ($warnings) {
      // Try to munge.
      if (empty($input)) {
        $munged = '';
      }
      else {
        if ((is_int($input) || preg_match('/^\d+$/', $input))) {
          // Got a number. Maybe it's an ID?
          $munged = Country::get(FALSE)->addSelect('iso_code')->addWhere('id', '=', $input)->execute()->first()['iso_code'] ?? '';
          if ($munged) {
            $warnings[] = "Given input matched a country ID, assuming it was that.";
          }
          else {
            $warnings[] = "Given input looked like it could be a country ID but did not match a country.";
          }
        }
        elseif (is_string($input)) {
          $munged = Country::get(FALSE)->addSelect('iso_code')->addWhere('name', '=', $input)->execute()->first()['iso_code'] ?? '';
          if ($munged) {
            $warnings[] = "Given input matched a country name, assuming it was that.";
          }
          else {
            $warnings[] = "Given input did not match a country name.";
          }
        }
        else {
          $munged = '';
          $warnings[] = "Given input is plain weird.";
        }
      }
    }

    if ($warnings) {
      $warnings[] = "Input: " . json_encode($input) . " was munged to: " . json_encode($munged);
      $warnings = "PropertyBag::setBillingCountry input warnings (may be errors in future):\n" . implode("\n", $warnings);
      $this->logs[] = $warnings;
      // Emit a deprecatedWarning except in the case that we're testing this function.
      if (__FUNCTION__ !== $this->ignoreDeprecatedWarningsInFunction) {
        CRM_Core_Error::deprecatedWarning($warnings);
      }
    }

    return $this->set('billingCountry', $label, $munged);
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
   *
   * @return \Civi\Payment\PropertyBag
   */
  public function setContributionRecurID($contributionRecurID, $label = 'default') {
    // We don't use this because it counts zero as positive: CRM_Utils_Type::validate($contactID, 'Positive');
    if (!($contributionRecurID > 0)) {
      throw new InvalidArgumentException('ContributionRecurID must be a positive integer');
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
      throw new \InvalidArgumentException("Attempted to setCurrency with a value that was not an ISO 3166-1 alpha 3 currency code");
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
   * @return bool
   */
  public function getIsRecur($label = 'default'):bool {
    if (!$this->has('isRecur')) {
      return FALSE;
    }
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
   * Set whether the user has selected to notify the processor of a cancellation request.
   *
   * When cancelling the user may be presented with an option to notify the processor. The payment
   * processor can take their response, if present, into account.
   *
   * @param bool $value
   * @param string $label e.g. 'default'
   *
   * @return \Civi\Payment\PropertyBag
   */
  public function setIsNotifyProcessorOnCancelRecur($value, $label = 'default') {
    return $this->set('isNotifyProcessorOnCancelRecur', $label, (bool) $value);
  }

  /**
   * Get whether the user has selected to notify the processor of a cancellation request.
   *
   * When cancelling the user may be presented with an option to notify the processor. The payment
   * processor can take their response, if present, into account.
   *
   * @param string $label e.g. 'default'
   *
   * @return \Civi\Payment\PropertyBag
   */
  public function getIsNotifyProcessorOnCancelRecur($label = 'default') {
    return $this->get('isNotifyProcessorOnCancelRecur', $label);
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
    if (!preg_match('/^day|week|month|year$/', ($recurFrequencyUnit ?? ''))) {
      throw new \InvalidArgumentException("recurFrequencyUnit must be day|week|month|year");
    }
    return $this->set('recurFrequencyUnit', $label, $recurFrequencyUnit);
  }

  /**
   * @param string $label
   *
   * @return int
   */
  public function getRecurInstallments($label = 'default') {
    return $this->get('recurInstallments', $label);
  }

  /**
   * @param int $recurInstallments
   * @param string $label
   *
   * @return \Civi\Payment\PropertyBag
   * @throws \CRM_Core_Exception
   */
  public function setRecurInstallments($recurInstallments, $label = 'default') {
    // Counts zero as positive which is ok - means no installments
    try {
      \CRM_Utils_Type::validate($recurInstallments, 'Positive');
    }
    catch (\CRM_Core_Exception $e) {
      throw new InvalidArgumentException('recurInstallments must be 0 or a positive integer');
    }

    return $this->set('recurInstallments', $label, (int) $recurInstallments);
  }

  /**
   * Set the unique payment processor service provided ID for a particular subscription.
   *
   * Nb. this is stored in civicrm_contribution_recur.processor_id and is NOT
   * in any way related to the payment processor ID.
   *
   * @param string $label
   *
   * @return string|null
   */
  public function getRecurProcessorID($label = 'default') {
    return $this->get('recurProcessorID', $label);
  }

  /**
   * Set the unique payment processor service provided ID for a particular
   * subscription.
   *
   * See https://github.com/civicrm/civicrm-core/pull/17292 for discussion
   * of how this function accepting NULL fits with standard / planned behaviour.
   *
   * @param string|null $input
   * @param string $label e.g. 'default'
   *
   * @return \Civi\Payment\PropertyBag
   */
  public function setRecurProcessorID($input, $label = 'default') {
    if ($input === '') {
      $input = NULL;
    }
    if (strlen($input ?? '') > 255 || in_array($input, [FALSE, 0], TRUE)) {
      throw new \InvalidArgumentException('processorID field has max length of 255');
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

    if (!array_key_exists($prop, $this->props[$label] ?? [])) {
      throw new \BadMethodCallException("Property '$prop' has not been set.");
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
