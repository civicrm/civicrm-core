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
