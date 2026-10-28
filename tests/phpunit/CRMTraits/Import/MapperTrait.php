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
 * Trait MapperTrait
 *
 * Helpers for the import mappers that tests pass in.
 */
trait CRMTraits_Import_MapperTrait {

  /**
   * Replace the names a data provider gives in a mapper with ids.
   *
   * Data providers run before the relationship and location types exist, so
   * they name them, e.g. ['Child of_a_b', 'phone', 'Home', 1] or ['email', 'Home'].
   *
   * @param array $mapper
   *   Mapping as entered on MapField form, e.g. [['first_name'], ['email', 'Home']].
   *
   * @return array
   */
  protected function convertMapperNamesToIDs(array $mapper): array {
    foreach ($mapper as &$mapperField) {
      $locationTypeIndex = 1;
      if (preg_match('/^(.+)_(a_b|b_a)$/', $mapperField[0] ?? '', $matches)) {
        if (!is_numeric($matches[1])) {
          $mapperField[0] = $this->getRelationshipTypeID($matches[1]) . '_' . $matches[2];
        }
        // Relationship rows are [relationship, field, location type, ...].
        $locationTypeIndex = 2;
      }
      $locationType = $mapperField[$locationTypeIndex] ?? NULL;
      if (is_string($locationType) && !is_numeric($locationType) && $locationType !== 'Primary') {
        $mapperField[$locationTypeIndex] = $this->getLocationTypeID($locationType);
      }
    }
    return $mapper;
  }

}
