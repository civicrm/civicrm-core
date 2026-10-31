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

namespace api\v4\Action;

use api\v4\Api4TestBase;
use Civi\Api4\Address;
use Civi\Test\TransactionalInterface;

/**
 * Tests for the Api4 Address.geocode action.
 *
 * Uses CRM_Utils_Geocode_TestProvider, a mock provider that
 * records every address string it receives and returns fixed dummy coordinates.
 *
 * @group headless
 */
class AddressGeocodeTest extends Api4TestBase implements TransactionalInterface {

  /**
   * Dummy lat/long values returned by the mock provider.
   */
  private const GEO_CODE_1 = \CRM_Utils_Geocode_TestProvider::GEO_CODE_1;
  private const GEO_CODE_2 = \CRM_Utils_Geocode_TestProvider::GEO_CODE_2;

  public function setUp(): void {
    parent::setUp();
    \CRM_Utils_Geocode_TestProvider::reset();
    // Point the geocoding subsystem at our mock provider.
    \Civi\Api4\Setting::set()
      ->addValue('geoProvider', 'TestProvider')
      ->execute();
  }

  public function tearDown(): void {
    \Civi\Api4\Setting::revert()
      ->addSelect('geoProvider')
      ->addSelect('defaultContactCountry')
      ->execute();
    parent::tearDown();
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------

  /**
   * Create a contact + address record with the supplied field values.
   *
   * @param array $addressValues
   * @return array The created address record (id + supplied values).
   */
  private function createAddress(array $addressValues = []): array {
    $contactId = $this->createTestRecord('Contact')['id'];
    $address = Address::create(FALSE)
      ->setSkipGeocode(TRUE)
      ->setValues(array_merge(
        ['contact_id' => $contactId, 'location_type_id' => 1],
        $addressValues
      ))
      ->execute()
      ->single();
    // Register for automatic cleanup.
    $this->registerTestRecord('Address', $address['id']);
    return $address;
  }

  // ---------------------------------------------------------------------------
  // Tests
  // ---------------------------------------------------------------------------

  /**
   * The geocoding provider receives a well-formed, comma-separated address
   * string assembled from the non-empty address fields.
   */
  public function testGeocodeProviderReceivesWellFormedInput(): void {
    $address = $this->createAddress([
      // Use the TestProvider's known address so getCoordinates() returns real
      // coordinates rather than an empty array.
      'street_address' => '600 Pennsylvania Avenue NW',
      'city' => 'Washington',
      'postal_code' => '20500',
    ]);

    Address::geocode(FALSE)
      ->addWhere('id', '=', $address['id'])
      ->execute();

    $received = \CRM_Utils_Geocode_TestProvider::$receivedAddresses;
    $this->assertCount(1, $received, 'Provider should be called exactly once per address');

    $addressString = $received[0];

    // The string must be non-empty.
    $this->assertNotEmpty($addressString);

    // All non-empty parts that were supplied must appear in the string.
    $this->assertStringContainsString('600 Pennsylvania Avenue NW', $addressString);
    $this->assertStringContainsString('Washington', $addressString);
    $this->assertStringContainsString('20500', $addressString);

    // Parts are joined by ', ' – verify the separator is present.
    $this->assertStringContainsString(', ', $addressString);

    // Empty fields must NOT produce trailing/leading separators or
    // double-comma artefacts like ", ,".
    $this->assertStringNotContainsString(', ,', $addressString);
    $this->assertDoesNotMatchRegularExpression('/^, |, $/', $addressString);
  }

  /**
   * When an address has no country set, the default contact country name is
   * appended to the address string sent to the geocoding provider.
   */
  public function testDefaultCountryIsAppendedWhenMissing(): void {
    // Use the US as the default country (ID 1228 in the standard CiviCRM
    // country list). Look up its label the same way the action does so the
    // assertion is not tied to a hardcoded string.
    $usCountryId = 1228;
    $expectedCountryName = \CRM_Core_PseudoConstant::country()[$usCountryId];

    \Civi\Api4\Setting::set()
      ->addValue('defaultContactCountry', $usCountryId)
      ->execute();

    // defaultContactCountryName() uses a static cache; ensure it is primed
    // with the new setting value before the action reads it.
    \CRM_Core_BAO_Country::defaultContactCountryName();

    $address = $this->createAddress([
      'street_address' => '600 Pennsylvania Avenue NW',
      'city' => 'Washington',
      // No country_id – the action should fall back to the default.
    ]);

    Address::geocode(FALSE)
      ->addWhere('id', '=', $address['id'])
      ->execute();

    $addressString = \CRM_Utils_Geocode_TestProvider::$receivedAddresses[0] ?? '';
    $this->assertStringContainsString($expectedCountryName, $addressString,
      'Default contact country should be appended when address has no country'
    );
  }

  /**
   * After a successful geocode, the address record is updated with lat/long.
   */
  public function testGeocodeUpdatesAddressCoordinates(): void {
    $address = $this->createAddress([
      'street_address' => '600 Pennsylvania Avenue NW',
      'city' => 'Washington',
    ]);

    $result = Address::geocode(FALSE)
      ->addWhere('id', '=', $address['id'])
      ->execute();

    $this->assertCount(1, $result);
    $this->assertEquals('updated', $result->first()['status']);

    $updated = Address::get(FALSE)
      ->addWhere('id', '=', $address['id'])
      ->addSelect('geo_code_1', 'geo_code_2')
      ->execute()
      ->first();

    $this->assertEquals(self::GEO_CODE_1, $updated['geo_code_1']);
    $this->assertEquals(self::GEO_CODE_2, $updated['geo_code_2']);
  }

  /**
   * Addresses that already have geo_code_1 and geo_code_2 set are skipped
   * by default (includeAlreadyGeocoded = FALSE).
   *
   * Setting includeAlreadyGeocoded = TRUE forces them to be re-geocoded.
   */
  public function testAlreadyGeocodedAddressesSkippedByDefault(): void {
    // Address with pre-existing lat/long values.
    $alreadyGeocoded = $this->createAddress([
      'street_address' => '1600 Pennsylvania Ave NW',
      'city' => 'Washington',
      'geo_code_1' => '38.8977',
      'geo_code_2' => '-77.0366',
    ]);

    // Address without lat/long – should be processed.
    $notYetGeocoded = $this->createAddress([
      'street_address' => '221B Baker Street',
      'city' => 'London',
    ]);

    // --- Default behaviour: skip already-geocoded ---
    $result = Address::geocode(FALSE)
      ->addWhere('id', 'IN', [$alreadyGeocoded['id'], $notYetGeocoded['id']])
      ->execute()
      ->indexBy('id');

    $this->assertArrayNotHasKey($alreadyGeocoded['id'], (array) $result,
      'Already-geocoded address should be skipped by default'
    );
    $this->assertArrayHasKey($notYetGeocoded['id'], (array) $result,
      'Un-geocoded address should be processed'
    );
    $this->assertCount(1, \CRM_Utils_Geocode_TestProvider::$receivedAddresses,
      'Provider called only for the un-geocoded address'
    );

    // --- With includeAlreadyGeocoded: both are processed ---
    \CRM_Utils_Geocode_TestProvider::reset();

    $result = Address::geocode(FALSE)
      ->addWhere('id', 'IN', [$alreadyGeocoded['id'], $notYetGeocoded['id']])
      ->setIncludeAlreadyGeocoded(TRUE)
      ->execute()
      ->indexBy('id');

    $this->assertArrayHasKey($alreadyGeocoded['id'], (array) $result,
      'Already-geocoded address should be included when includeAlreadyGeocoded=TRUE'
    );
    $this->assertArrayHasKey($notYetGeocoded['id'], (array) $result);
    $this->assertCount(2, \CRM_Utils_Geocode_TestProvider::$receivedAddresses,
      'Provider should be called for both addresses'
    );
  }

  /**
   * Addresses flagged as manually geocoded are skipped by default
   * (includeManuallyGeocoded = FALSE).
   *
   * Setting includeManuallyGeocoded = TRUE forces them to be re-geocoded.
   */
  public function testManuallyGeocodedAddressesSkippedByDefault(): void {
    // Address marked as manually geocoded.
    $manuallyGeocoded = $this->createAddress([
      'street_address' => '742 Evergreen Terrace',
      'city' => 'Springfield',
      'manual_geo_code' => TRUE,
    ]);

    // Normal address without manual flag – should be processed.
    $normalAddress = $this->createAddress([
      'street_address' => '4 Privet Drive',
      'city' => 'Little Whinging',
    ]);

    // --- Default behaviour: skip manually geocoded ---
    $result = Address::geocode(FALSE)
      ->addWhere('id', 'IN', [$manuallyGeocoded['id'], $normalAddress['id']])
      ->execute()
      ->indexBy('id');

    $this->assertArrayNotHasKey($manuallyGeocoded['id'], (array) $result,
      'Manually geocoded address should be skipped by default'
    );
    $this->assertArrayHasKey($normalAddress['id'], (array) $result,
      'Normal address should be processed'
    );
    $this->assertCount(1, \CRM_Utils_Geocode_TestProvider::$receivedAddresses,
      'Provider called only for the normal address'
    );

    // --- With includeManuallyGeocoded: the manually-geocoded address is processed ---
    // Note: normalAddress was geocoded in the previous pass, so we also set
    // includeAlreadyGeocoded=TRUE to ensure it stays in scope for this assertion.
    \CRM_Utils_Geocode_TestProvider::reset();

    $result = Address::geocode(FALSE)
      ->addWhere('id', 'IN', [$manuallyGeocoded['id'], $normalAddress['id']])
      ->setIncludeManuallyGeocoded(TRUE)
      ->setIncludeAlreadyGeocoded(TRUE)
      ->execute()
      ->indexBy('id');

    $this->assertArrayHasKey($manuallyGeocoded['id'], (array) $result,
      'Manually geocoded address should be included when includeManuallyGeocoded=TRUE'
    );
    $this->assertArrayHasKey($normalAddress['id'], (array) $result);
    $this->assertCount(2, \CRM_Utils_Geocode_TestProvider::$receivedAddresses,
      'Provider should be called for both addresses'
    );
  }

}
