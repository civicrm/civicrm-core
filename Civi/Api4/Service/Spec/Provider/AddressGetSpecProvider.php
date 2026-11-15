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

namespace Civi\Api4\Service\Spec\Provider;

use Civi\Api4\Address;
use Civi\Api4\Query\Api4SelectQuery;
use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\RequestSpec;

/**
 * @service
 * @internal
 */
class AddressGetSpecProvider extends \Civi\Core\Service\AutoService implements Generic\SpecProviderInterface {

  /**
   * @param \Civi\Api4\Service\Spec\RequestSpec $spec
   */
  public function modifySpec(RequestSpec $spec) {
    // Proximity search field
    $field = new FieldSpec('proximity', 'Address', 'Boolean');
    $field->setLabel(ts('Address Proximity'))
      ->setTitle(ts('Address Proximity'))
      ->setInputType('Location')
      ->setColumnName('geo_code_1')
      ->setDescription(ts('Address is within a given distance to a location'))
      ->setType('Filter')
      ->setOperators(['<='])
      ->addSqlFilter([__CLASS__, 'getProximitySql']);
    $spec->addFieldSpec($field);

    // display is a readonly calculated field derived from CRM_Utils_Address::format
    $field = new FieldSpec('display', 'Address', 'String');
    $field->setLabel(ts('Display'))
      ->setTitle(ts('Address Display'))
      ->setReadonly(TRUE)
      ->setColumnName('id')
      ->setDescription(ts('Entire address formatted for display'))
      ->addOutputFormatter([__CLASS__, 'displayFormat']);
    $spec->addFieldSpec($field);
  }

  /**
   * @param string $entity
   * @param string $action
   *
   * @return bool
   */
  public function applies($entity, $action) {
    return $entity === 'Address' && $action === 'get';
  }

  /**
   * Output formatter for the `display` field.
   *
   * The field's column is the address id; replace it with the formatted address.
   *
   * @param mixed $value
   *   On input the address id; replaced with the formatted address string.
   * @param array $row
   * @param array $fieldDefn
   */
  public static function displayFormat(&$value, array $row, array $fieldDefn): void {
    if (!$value) {
      $value = NULL;
      return;
    }
    // TODO: Optimize this list based on the actual tokens in Settings.address_format
    $fields = [
      'street_address', 'supplemental_address_1', 'supplemental_address_2', 'supplemental_address_3',
      'city', 'postal_code', 'postal_code_suffix', 'geo_code_1', 'geo_code_2', 'name',
      'state_province_id', 'county_id', 'country_id',
    ];
    // Only query the database if the row doesn't already contain everything needed
    if (array_diff_key(array_flip($fields), $row)) {
      $row = Address::get(FALSE)
        ->setSelect($fields)
        ->addWhere('id', '=', $value)
        ->execute()->first();
    }
    $value = $row ? trim(\CRM_Utils_Address::format($row)) : NULL;
  }

  /**
   * @param array $field
   * @param string $fieldAlias
   * @param string $operator
   * @param mixed $value
   * @param \Civi\Api4\Query\Api4SelectQuery $query
   * @param int $depth
   * return string
   */
  public static function getProximitySql(array $field, string $fieldAlias, string $operator, $value, Api4SelectQuery $query, int $depth): string {
    $unit = $value['distance_unit'] ?? 'km';
    $distance = $value['distance'] ?? 0;

    if ($unit === 'miles') {
      $distance = $distance * 1609.344;
    }
    else {
      $distance = $distance * 1000.00;
    }

    if (!isset($value['geo_code_1'], $value['geo_code_2'])) {
      $value = Address::getCoordinates(FALSE)
        ->setAddress($value['address'])
        ->execute()->first();
    }

    if (
      isset($value['geo_code_1']) && is_numeric($value['geo_code_1']) &&
      isset($value['geo_code_2']) && is_numeric($value['geo_code_2'])
    ) {
      return \CRM_Contact_BAO_ProximityQuery::where(
        $value['geo_code_1'],
        $value['geo_code_2'],
        $distance,
        explode('.', $fieldAlias)[0]
      );
    }

    return '(0)';
  }

}
