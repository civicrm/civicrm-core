<?php
namespace Civi\Payment;

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\Error\Deprecated as DeprecatedError;

/**
 * @group headless
 */
class PropertyBagTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  /**
   * @return \Civi\Test\CiviEnvBuilder
   */
  public function setUpHeadless() {
    static $reset = FALSE;
    $return = \Civi\Test::headless()->apply($reset);
    $reset = FALSE;
    return $return;
  }

  /**
   * Test we can set a contact ID.
   */
  public function testSetContactID() {
    // Do things proper.
    $propertyBag = new PropertyBag();
    $propertyBag->setContactID(123);
    $this->assertEquals(123, $propertyBag->getContactID());

    // Same but this time set contact ID with string.
    // (php should throw its own warnings about this because of the signature)
    $propertyBag = new PropertyBag();
    $propertyBag->setContactID('123');
    $this->assertIsInt($propertyBag->getContactID());
    $this->assertEquals(123, $propertyBag->getContactID());

    // Test we can have different labels
    $propertyBag = new PropertyBag();
    $propertyBag->setContactID(123);
    $propertyBag->setContactID(456, 'new');
    $this->assertEquals(123, $propertyBag->getContactID());
    $this->assertEquals(456, $propertyBag->getContactID('new'));
  }

  /**
   * Test we can set an amount.
   *
   * @see https://github.com/civicrm/civicrm-core/pull/18219
   *
   * @dataProvider setAmountDataProvider
   *
   * @param mixed $value input
   * @param mixed $expect output expected. Typically a string like '1.23' or NULL
   * @param string $expectedExceptionMessage if there is one expected.
   */
  public function testSetAmount($value, $expect, $expectedExceptionMessage = '') {
    $propertyBag = new PropertyBag();
    try {
      $propertyBag->setAmount($value);
    }
    catch (\Exception $e) {
      if ($expectedExceptionMessage) {
        $this->assertEquals($expectedExceptionMessage, $e->getMessage(), 'Expected a different exception.');
        // OK.
        return;
      }
      // not expecting an exception, re-throw it.
      throw $e;
    }
    $got = $propertyBag->getAmount();
    $this->assertIsString($got);
    $this->assertEquals($expect, $got);
  }

  /**
   *
   */
  public function setAmountDataProvider() {
    return [
      [1, '1'],
      [1.23, '1.23'],
      [1.234, '1.234'],
      [-1.23, '-1.23'],
      ['1', '1'],
      ['1.23', '1.23'],
      ['1.234', '1.234'],
      ['-1.23', '-1.23'],
      ['1,000.23', NULL, 'setAmount requires a numeric amount value'],
      ['1,000', NULL, 'setAmount requires a numeric amount value'],
      ['1,23', NULL, 'setAmount requires a numeric amount value'],
      ['1.230,12', NULL, 'setAmount requires a numeric amount value'],
    ];
  }

  /**
   * Test we cannot set an invalid contact ID.
   */
  public function testSetContactIDFailsIfInvalid() {
    $this->expectException(\InvalidArgumentException::class);
    $propertyBag = new PropertyBag();
    $propertyBag->setContactID(0);
  }

  /**
   * Test we can set a contact ID the wrong way
   */
  public function testSetContactIDLegacyWay() {
    $propertyBag = new PropertyBag();
    $propertyBag->setSuppressLegacyWarnings(FALSE);

    // To prevent E_USER_DEPRECATED errors during phpunit tests we take a copy
    // of the existing error_reporting.
    $oldLevel = error_reporting();
    $ignoreUserDeprecatedErrors = $oldLevel & ~E_USER_DEPRECATED;

    foreach (['contactID', 'contact_id'] as $prop) {
      // Set by array access should cause deprecated error.
      try {
        $propertyBag[$prop] = 123;
        $this->fail("Using array access to set a property '$prop' should trigger deprecated notice.");
      }
      catch (DeprecatedError $e) {
      }

      // But it should still work.
      error_reporting($ignoreUserDeprecatedErrors);
      $propertyBag[$prop] = 123;
      error_reporting($oldLevel);
      $this->assertEquals(123, $propertyBag->getContactID());

      // Getting by array access should also cause deprecation error.
      try {
        $_ = $propertyBag[$prop];
        $this->fail("Using array access to get a property '$prop' should trigger deprecated notice.");
      }
      catch (DeprecatedError $e) {
      }

      // But again, it should work.
      error_reporting($ignoreUserDeprecatedErrors);
      $this->assertEquals(123, $propertyBag[$prop], "Getting '$prop' by array access should work");
      error_reporting($oldLevel);
    }
  }

  /**
   * Test that emails set by the legacy method of 'email-5' can be retrieved with getEmail.
   */
  public function testSetBillingEmailLegacy() {
    $localPropertyBag = PropertyBag::cast(['email-' . \CRM_Core_BAO_LocationType::getBilling() => 'a@b.com']);
    $this->assertEquals('a@b.com', $localPropertyBag->getEmail());
  }

  /**
   * Test that null is valid for recurring contribution ID.
   *
   * See https://github.com/civicrm/civicrm-core/pull/17292
   */
  public function testRecurProcessorIDNull() {
    $bag = new PropertyBag();
    $bag->setRecurProcessorID(NULL);
    $value = $bag->getRecurProcessorID();
    $this->assertNull($value);
  }

  /**
   */
  public function testMergeInputs() {
    $propertyBag = PropertyBag::cast([
      'contactID' => 123,
      'contributionRecurID' => 456,
    ]);
    $this->assertEquals(123, $propertyBag->getContactID());
    $this->assertEquals(456, $propertyBag->getContributionRecurID());
  }

  /**
   * Test we can set and access custom props.
   */
  public function testSetCustomProp() {
    $oldLevel = error_reporting();
    $ignoreUserDeprecatedErrors = $oldLevel & ~E_USER_DEPRECATED;

    // The proper way.
    $propertyBag = new PropertyBag();
    $propertyBag->setCustomProperty('customThingForMyProcessor', 'fidget');
    $this->assertEquals('fidget', $propertyBag->getCustomProperty('customThingForMyProcessor'));
    $this->assertEquals('', $propertyBag->lastWarning);

    // Test we can do this with array, although we should get a warning.
    $propertyBag = new PropertyBag();
    $propertyBag->setSuppressLegacyWarnings(FALSE);

    // Set by array access should cause deprecated error.
    try {
      $propertyBag['customThingForMyProcessor'] = 'fidget';
      $this->fail("Using array access to set an implicitly custom property should trigger deprecated notice.");
    }
    catch (DeprecatedError $e) {
    }

    // But it should still work.
    error_reporting($ignoreUserDeprecatedErrors);
    $propertyBag['customThingForMyProcessor'] = 'fidget';
    error_reporting($oldLevel);
    $this->assertEquals('fidget', $propertyBag->getCustomProperty('customThingForMyProcessor'));

    // Getting by array access should also cause deprecation error.
    try {
      $_ = $propertyBag['customThingForMyProcessor'];
      $this->fail("Using array access to get an implicitly custom property should trigger deprecated notice.");
    }
    catch (DeprecatedError $e) {
    }

    // But again, it should work.
    error_reporting($ignoreUserDeprecatedErrors);
    $this->assertEquals('fidget', $propertyBag['customThingForMyProcessor']);
    error_reporting($oldLevel);

  }

  /**
   * Test we can't set a custom prop that we know about.
   */
  public function testSetCustomPropFails() {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Attempted to set \'contactID\' via setCustomProperty - must use using its setter.');
    $propertyBag = new PropertyBag();
    $propertyBag->setCustomProperty('contactID', 123);
  }

  /**
   * Test we get NULL for custom prop that was not set.
   *
   * This is only for backward compatibility/ease of transition. One day it would be nice to throw an exception instead.
   */
  public function testGetCustomPropFails() {
    $this->expectException(\BadMethodCallException::class);
    $this->expectExceptionMessage('Property \'aCustomProp\' has not been set.');
    $propertyBag = new PropertyBag();
    // Tricky test. We need to ignore deprecation errors, we're testing deprecated behaviour,
    // but we need to listen out for a different exception.
    $oldLevel = error_reporting();
    $ignoreUserDeprecatedErrors = $oldLevel & ~E_USER_DEPRECATED;
    error_reporting($ignoreUserDeprecatedErrors);

    // Do the do.
    try {
      $v = $propertyBag['aCustomProp'];
      error_reporting($oldLevel);
      $this->fail("Expected BadMethodCallException from accessing an unset custom prop.");
    }
    catch (\BadMethodCallException $e) {
      // reset error level.
      error_reporting($oldLevel);
      // rethrow for phpunit to catch.
      throw $e;
    }

  }

  /**
   *
   * @dataProvider otherParamsDataProvider
   */
  public function testOtherParams($prop, $legacy_names, $valid_values, $invalid_values) {
    $setter = 'set' . ucfirst($prop);
    $getter = 'get' . ucfirst($prop);

    // Using the setter and getter, check we can pass stuff in and get expected out.
    foreach ($valid_values as $_) {
      list($given, $expect) = $_;
      $propertyBag = new PropertyBag();
      try {
        $propertyBag->$setter($given);
      }
      catch (\Exception $e) {
        $this->fail("Expected to be able to set '$prop' to '$given' but got " . get_class($e) . ": " . $e->getMessage());
      }
      try {
        $this->assertEquals($expect, $propertyBag->$getter());
      }
      catch (\Exception $e) {
        $this->fail("Expected to be able to call $getter, having called $setter with '$given' but got " . get_class($e) . ": " . $e->getMessage());
      }
    }
    // Using the setter and getter, check we get an error for invalid data.
    foreach ($invalid_values as $given) {
      try {
        $propertyBag = new PropertyBag();
        $propertyBag->$setter($given);
      }
      catch (\InvalidArgumentException $e) {
        // counts this assertion.
        $this->assertTrue(TRUE);
        continue;
      }
      $this->fail("Expected an error trying to set $prop to " . json_encode($given) . " but did not get one.");
    }

    $oldLevel = error_reporting();
    $ignoreUserDeprecatedErrors = $oldLevel & ~E_USER_DEPRECATED;

    // Check array access for the proper property name and any aliases.
    // This is going to throw a bunch of deprecated errors, but we know this
    // (and have tested it elsewhere) so we turn those off.
    error_reporting($ignoreUserDeprecatedErrors);
    foreach (array_merge([$prop], $legacy_names) as $name) {
      foreach ($valid_values as $_) {
        list($given, $expect) = $_;
        $propertyBag = new PropertyBag();
        $propertyBag[$name] = $given;
        $this->assertEquals($expect, $propertyBag->$getter(), "Failed to set $prop via array access on $name");
        // Nb. I don't feel the need to repeat all the checks above for every alias.
        // We only really need to test that the array access works for each alias.
        break;
      }
    }
    error_reporting($oldLevel);
  }

  /**
   * Test the require method works.
   */
  public function testRequire() {
    $propertyBag = new PropertyBag();
    $propertyBag->setContactID(123);
    $propertyBag->setDescription('foo');
    // This one should not error.
    $propertyBag->require(['contactID', 'description']);
    try {
      $propertyBag->require(['contactID', 'description', 'contributionID', 'somethingthatdoesntexist']);
    }
    catch (\InvalidArgumentException $e) {
      $this->assertEquals('Required properties missing: contributionID, somethingthatdoesntexist', $e->getMessage());
    }
  }

  /**
   * Test retrieves using CRM_Utils_Array::value still work.
   */
  public function testUtilsArray() {
    $propertyBag = new PropertyBag();
    $propertyBag->setContactID(123);
    // This will throw deprecation notices but we don't care.
    $oldLevel = error_reporting();
    $ignoreUserDeprecatedErrors = $oldLevel & ~E_USER_DEPRECATED;
    error_reporting($ignoreUserDeprecatedErrors);
    $this->assertEquals(123, \CRM_Utils_Array::value('contact_id', $propertyBag));

    // Test that using utils array value to get a nonexistent property returns the default.
    $this->assertEquals(456, \CRM_Utils_Array::value('ISawAManWhoWasntThere', $propertyBag, 456));
    error_reporting($oldLevel);
  }

  /**
   */
  public function testEmpty() {
    $propertyBag = new PropertyBag();
    $propertyBag->setContactID(123);
    $propertyBag->setRecurProcessorID('');
    $propertyBag->setBillingPostalCode(NULL);
    $propertyBag->setFeeAmount(0);
    $propertyBag->setCustomProperty('custom_issue', 'black lives matter');
    $propertyBag->setCustomProperty('custom_null', NULL);
    $propertyBag->setCustomProperty('custom_false', FALSE);
    $propertyBag->setCustomProperty('custom_zls', '');
    $propertyBag->setCustomProperty('custom_0', 0);

    // To prevent E_USER_DEPRECATED errors during phpunit tests we take a copy
    // of the existing error_reporting.
    $oldLevel = error_reporting();
    $ignoreUserDeprecatedErrors = $oldLevel & ~E_USER_DEPRECATED;
    error_reporting($ignoreUserDeprecatedErrors);

    // Tests on known properties.
    $v = empty($propertyBag->getContactID());
    $this->assertFalse($v, "empty on a set, known property should return False");
    $v = empty($propertyBag['contactID']);
    $this->assertFalse($v, "empty on a set, known property accessed by ArrayAccess with correct name should return False");
    $v = empty($propertyBag['contact_id']);
    $this->assertFalse($v, "empty on a set, known property accessed by ArrayAccess with legacy name should return False");
    $v = empty($propertyBag['recurProcessorID']);
    $this->assertTrue($v, "empty on an unset, known property accessed by ArrayAccess should return True");
    $v = empty($propertyBag->getRecurProcessorID());
    $this->assertTrue($v, "empty on a set, but '' value should return True");
    $v = empty($propertyBag->getFeeAmount());
    $this->assertTrue($v, "empty on a set, but 0 value should return True");
    $v = empty($propertyBag->getBillingPostalCode());
    $this->assertTrue($v, "empty on a set, but NULL value should return True");

    // Test custom properties.
    $v = empty($propertyBag->getCustomProperty('custom_issue'));
    $this->assertFalse($v, "empty on a set custom property with non-empty value should return False");
    foreach (['null', 'false', 'zls', '0'] as $_) {
      $v = empty($propertyBag["custom_$_"]);
      $this->assertTrue($v, "empty on a set custom property with $_ value should return TRUE");
    }
    $v = empty($propertyBag['nonexistent_custom_field']);
    $this->assertTrue($v, "empty on a non-existent custom property should return True");

    $v = empty($propertyBag['custom_issue']);
    $this->assertFalse($v, "empty on a set custom property accessed by ArrayAccess should return False");

    error_reporting($oldLevel);
  }

  /**
   *
   * Data provider for testOtherParams
   *
   */
  public function otherParamsDataProvider() {
    $valid_bools = [['0' , FALSE], ['', FALSE], [0, FALSE], [FALSE, FALSE], [TRUE, TRUE], [1, TRUE], ['1', TRUE]];
    $valid_strings = [['foo' , 'foo'], ['', '']];
    $valid_strings_inc_null = [['foo' , 'foo'], ['', ''], [NULL, '']];
    $valid_ints = [[123, 123], ['123', 123]];
    $invalid_ints = [-1, 0, NULL, ''];
    return [
      ['billingStreetAddress', [], $valid_strings_inc_null, []],
      ['billingSupplementalAddress1', [], $valid_strings_inc_null, []],
      ['billingSupplementalAddress2', [], $valid_strings_inc_null, []],
      ['billingSupplementalAddress3', [], $valid_strings_inc_null, []],
      ['billingCity', [], $valid_strings_inc_null, []],
      ['billingPostalCode', [], $valid_strings_inc_null, []],
      ['billingCounty', [], $valid_strings_inc_null, []],
      ['billingCountry', [], [['GB', 'GB'], ['NZ', 'NZ']], ['XX', '', NULL, 0]],
      ['contributionID', ['contribution_id'], $valid_ints, $invalid_ints],
      ['contributionRecurID', ['contribution_recur_id'], $valid_ints, $invalid_ints],
      ['description', [], [['foo' , 'foo'], ['', '']], []],
      ['feeAmount', ['fee_amount'], [[1.23, 1.23], ['4.56', 4.56]], [NULL]],
      ['firstName', [], $valid_strings_inc_null, []],
      ['invoiceID', ['invoice_id'], $valid_strings, []],
      ['isBackOffice', ['is_back_office'], $valid_bools, [NULL]],
      ['isRecur', ['is_recur'], $valid_bools, [NULL]],
      ['lastName', [], $valid_strings_inc_null, []],
      ['paymentToken', [], $valid_strings, []],
      ['recurFrequencyInterval', ['frequency_interval'], $valid_ints, $invalid_ints],
      ['recurFrequencyUnit', [], [['month', 'month'], ['day', 'day'], ['year', 'year']], ['', NULL, 0]],
      ['recurProcessorID', [], [['foo', 'foo']], [str_repeat('x', 256)]],
      ['transactionID', ['transaction_id'], $valid_strings, []],
      ['trxnResultCode', [], $valid_strings, []],
    ];
  }

  /**
   * Test generic getter, setter methods.
   *
   */
  public function testGetterAndSetter() {
    $propertyBag = new PropertyBag();

    $propertyBag->setter('contactID', 123);
    $this->assertEquals(123, $propertyBag->getContactID(), "Failed testing that a valid property was set correctly");

    $result = $propertyBag->getter('contactID');
    $this->assertEquals(123, $result, "Failed testing the getter on a set property");

    $result = $propertyBag->getter('contactID', TRUE, 456);
    $this->assertEquals(123, $result, "Failed testing the getter on a set property when providing a default");

    $result = $propertyBag->getter('contributionRecurID', TRUE, 456);
    $this->assertEquals(456, $result, "Failed testing the getter on an unset property when providing a default");

    try {
      $result = $propertyBag->getter('contributionRecurID', FALSE);
      $this->fail("getter called with unset property should throw exception but none was thrown");
    }
    catch (\BadMethodCallException $e) {
    }

    $result = $propertyBag->getter('contribution_recur_id', TRUE, NULL);
    $this->assertNull($result, "Failed testing the getter on an invalid property when providing a default");

    try {
      $result = $propertyBag->getter('contribution_recur_id');
    }
    catch (\InvalidArgumentException $e) {
      $this->assertEquals("Attempted to get 'contribution_recur_id' via getCustomProperty - must use using its getter.", $e->getMessage());
    }

    // Nb. up to 5.26, the custom property getter did not throw an exception if the property is unset, it just returned NULL.
    // Now, we return NULL for array access (legacy) but for modern access
    // (getter, getPropX(), getCustomProperty())  then we throw an exception if
    // it is not set.
    try {
      $result = $propertyBag->getter('something_custom');
      $this->fail("Expected a BadMethodCallException when getting 'something_custom' which has not been set.");
    }
    catch (\BadMethodCallException $e) {
    }

    try {
      $propertyBag->setter('some_custom_thing', 'foo');
      $this->fail("Expected to get an exception when trying to use setter for a non-standard property.");
    }
    catch (\BadMethodCallException $e) {
      $this->assertEquals("Cannot use generic setter with non-standard properties; you must use setCustomProperty for custom properties.", $e->getMessage());
    }

    // Test labels.
    $propertyBag->setter('contactID', '100', 'original');
    $this->assertEquals(123, $propertyBag->getContactID(), "Looks like the setter did not respect the label.");
    $this->assertEquals(100, $propertyBag->getContactID('original'), "Failed to retrieve the labelled property");
    $this->assertEquals(100, $propertyBag->getter('contactID', FALSE, NULL, 'original'), "Failed using the getter to retrieve the labelled property");

  }

}
