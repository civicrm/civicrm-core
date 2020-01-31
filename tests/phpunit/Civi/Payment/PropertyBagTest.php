<?php
namespace Civi\Payment;

use Civi\Test\HeadlessInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class PropertyBagTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()->apply();
  }

  protected function setUp() {
    parent::setUp();
    // $this->useTransaction(TRUE);
  }

  public function tearDown() {
    parent::tearDown();
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
    $this->assertInternalType('int', $propertyBag->getContactID());
    $this->assertEquals(123, $propertyBag->getContactID());

    // Test we can have different labels
    $propertyBag = new PropertyBag();
    $propertyBag->setContactID(123);
    $propertyBag->setContactID(456, 'new');
    $this->assertEquals(123, $propertyBag->getContactID());
    $this->assertEquals(456, $propertyBag->getContactID('new'));
  }

  /**
   * Test we cannot set an invalid contact ID.
   *
   * @expectedException \InvalidArgumentException
   */
  public function testSetContactIDFailsIfInvalid() {
    $propertyBag = new PropertyBag();
    $propertyBag->setContactID(0);
  }

  /**
   * Test we can set a contact ID the wrong way
   */
  public function testSetContactIDLegacyWay() {
    $propertyBag = new PropertyBag();
    $propertyBag['contactID'] = 123;
    $this->assertEquals(123, $propertyBag->getContactID());
    $this->assertEquals(123, $propertyBag['contactID']);
    // There should not be any warnings yet.
    $this->assertEquals("", $propertyBag->lastWarning);

    // Now access via legacy name - should work but generate warning.
    $this->assertEquals(123, $propertyBag['contact_id']);
    $this->assertEquals("We have translated 'contact_id' to 'contactID' for you, but please update your code to use the propper setters and getters.", $propertyBag->lastWarning);

    // Repeat but this time set the property using a legacy name, fetch by new name.
    $propertyBag = new PropertyBag();
    $propertyBag['contact_id'] = 123;
    $this->assertEquals("We have translated 'contact_id' to 'contactID' for you, but please update your code to use the propper setters and getters.", $propertyBag->lastWarning);
    $this->assertEquals(123, $propertyBag->getContactID());
    $this->assertEquals(123, $propertyBag['contactID']);
    $this->assertEquals(123, $propertyBag['contact_id']);
  }

  /**
   */
  public function testMergeInputs() {
    $propertyBag = new PropertyBag();
    $propertyBag->mergeLegacyInputParams([
      'contactID' => 123,
      'contributionRecurID' => 456,
    ]);
    $this->assertEquals("We have merged input params into the property bag for now but please rewrite code to not use this.", $propertyBag->lastWarning);
    $this->assertEquals(123, $propertyBag->getContactID());
    $this->assertEquals(456, $propertyBag->getContributionRecurID());
  }

  /**
   * Test we can set and access custom props.
   */
  public function testSetCustomProp() {
    $propertyBag = new PropertyBag();
    $propertyBag->setCustomProperty('customThingForMyProcessor', 'fidget');
    $this->assertEquals('fidget', $propertyBag->getCustomProperty('customThingForMyProcessor'));
    $this->assertEquals('', $propertyBag->lastWarning);

    // Test we can do this with array, although we should get a warning.
    $propertyBag = new PropertyBag();
    $propertyBag['customThingForMyProcessor'] = 'fidget';
    $this->assertEquals('fidget', $propertyBag->getCustomProperty('customThingForMyProcessor'));
    $this->assertEquals("Unknown property 'customThingForMyProcessor'. We have merged this in for now as a custom property. Please rewrite your code to use PropertyBag->setCustomProperty if it is a genuinely custom property, or a standardised setter like PropertyBag->setContactID for standard properties", $propertyBag->lastWarning);
  }

  /**
   * Test we can't set a custom prop that we know about.
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage Attempted to set 'contactID' via setCustomProperty - must use using its setter.
   */
  public function testSetCustomPropFails() {
    $propertyBag = new PropertyBag();
    $propertyBag->setCustomProperty('contactID', 123);
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

    // Check array access for the proper property name and any aliases.
    foreach (array_merge([$prop], $legacy_names) as $name) {
      // Check array access
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
      ['recurProcessorID', [], [['foo', 'foo']], [str_repeat('x', 256), NULL, '', 0]],
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

    // Nb. hmmm. the custom property getter does not throw an exception if the property is unset, it just returns NULL.
    $result = $propertyBag->getter('something_custom');
    $this->assertNull($result, "Failed testing the getter on an unset custom property when not providing a default");

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
