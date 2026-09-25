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

namespace Civi\Api4\Action\Address;

use Civi\Api4\Generic\Result;

/**
 * Fetch lat/long coordinates for an address record
 */
class Geocode extends \Civi\Api4\Generic\BasicBatchAction {

  protected const ADDRESS_PARTS = [
    'street_address',
    'supplemental_address_1',
    'supplemental_address_2',
    'supplemental_address_3',
    'city',
    'county_id:label',
    'state_province_id:label',
    'postal_code',
    'country_id:label',
  ];

  /**
   * @inheritdoc
   */
  protected function getSelect() {
    return ['id', ...self::ADDRESS_PARTS, 'manual_geo_code'];
  }

  /**
   * @var bool
   *
   * Skip records which have already been geocoded
   */
  protected bool $includeAlreadyGeocoded = FALSE;

  /**
   * @var bool
   *
   * Skip records which have been manually geocoded
   */
  protected bool $includeManuallyGeocoded = FALSE;


  /**
   * @var string
   *
   * Local cache for default country. Addresses with
   * no country set will use this.
   */
  private string $defaultCountryLabel;

  /**
   * @inheritdoc
   */
  protected function getBatchAction() {
    $action = parent::getBatchAction();

    if (!$this->includeAlreadyGeocoded) {
      $action->addWhere('geo_code_1', 'IS EMPTY');
      $action->addWhere('geo_code_2', 'IS EMPTY');
    }
    if (!$this->includeManuallyGeocoded) {
      $action->addWhere('manual_geo_code', 'IS EMPTY');
    }

    return $action;
  }

  protected function processBatch(Result $result, array $items): void {
    $this->defaultCountryLabel = \CRM_Core_BAO_Country::defaultContactCountryName();
    parent::processBatch($result, $items);
  }

  public function doTask($record) {
    try {
      $record['country_id:label'] ??= $this->defaultCountryLabel;

      // get the non-empty fields
      $addressStringParts = array_map(fn ($key) => $record[$key], self::ADDRESS_PARTS);
      $addressString = implode(', ', array_filter($addressStringParts));

      if (!$addressString) {
        throw new \CRM_Core_Exception('Empty address');
      }

      $coordinates = \Civi\Api4\Address::getCoordinates(FALSE)
        ->setAddress($addressString)
        ->execute()
        ->first();

      if (!$coordinates) {
        throw new \CRM_Core_Exception('No coordinates found');
      }

      \Civi\Api4\Address::update(FALSE)
        ->addWhere('id', '=', $record['id'])
        ->addValue('geo_code_1', $coordinates['geo_code_1'])
        ->addValue('geo_code_2', $coordinates['geo_code_2'])
        ->execute();

      return [
        'id' => $record['id'],
        'status' => 'updated',
      ];
    }
    catch (\Throwable $e) {
      $message = $e->getMessage();
      \Civi::log()->debug("Geocoding failed for Address ID {$record['id']}: {$message}");
      return [
        'id' => $record['id'],
        'status' => 'error',
        'message' => $message,
      ];
    }
  }

}
